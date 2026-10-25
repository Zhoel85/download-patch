<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ProductionHardeningHook
{
    private $claimSessionKey = 'rc_finalization_claim';

    public function handle()
    {
        $CI =& get_instance();

        $this->securityHeaders($CI);

        if (!isset($CI->db)) {
            return;
        }

        $this->rateLimit($CI);
        $this->claimFinalization($CI);
    }

    public function after()
    {
        $CI =& get_instance();

        if (!isset($CI->session) || !isset($CI->db)) {
            return;
        }

        $claim = $CI->session->userdata($this->claimSessionKey);
        if (!is_array($claim) || empty($claim['event_id']) || empty($claim['id_tps']) || empty($claim['witness_id'])) {
            return;
        }

        $header = $CI->db
            ->where('event_id', (int) $claim['event_id'])
            ->where('id_tps', (string) $claim['id_tps'])
            ->where('witness_id', (int) $claim['witness_id'])
            ->where_in('status', array('FINALIZED', 'VALIDATED', 'VERIFIED', 'REVISION'))
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('witness_result_headers')
            ->row_array();

        if ($header) {
            $CI->db
                ->where('event_id', (int) $claim['event_id'])
                ->where('id_tps', (string) $claim['id_tps'])
                ->where('witness_id', (int) $claim['witness_id'])
                ->update('real_count_tps_registry', array(
                    'header_id' => (int) $header['id'],
                    'state' => (string) $header['status'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
        } else {
            $CI->db
                ->where('event_id', (int) $claim['event_id'])
                ->where('id_tps', (string) $claim['id_tps'])
                ->where('witness_id', (int) $claim['witness_id'])
                ->where('header_id', 0)
                ->delete('real_count_tps_registry');
        }

        $CI->session->unset_userdata($this->claimSessionKey);
    }

    private function securityHeaders($CI)
    {
        if (!isset($CI->output)) {
            return;
        }

        $CI->output->set_header('X-Content-Type-Options: nosniff');
        $CI->output->set_header('X-Frame-Options: SAMEORIGIN');
        $CI->output->set_header('Referrer-Policy: same-origin');
        $CI->output->set_header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');

        if (
            isset($CI->session) &&
            ($CI->session->userdata('id') || $CI->session->userdata('id_anggota'))
        ) {
            $CI->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            $CI->output->set_header('Pragma: no-cache');
        }
    }

    private function rateLimit($CI)
    {
        if (!$CI->db->table_exists('security_runtime_rate_limits')) {
            return;
        }

        $uri = strtolower(trim((string) $CI->uri->uri_string(), '/'));
        $method = strtoupper((string) $CI->input->method(TRUE));

        $rules = array(
            array('needle' => 'witness/witness360/result', 'method' => 'POST', 'key' => 'WITNESS_RESULT', 'limit' => 20),
            array('needle' => 'witness/witness360/incident', 'method' => 'POST', 'key' => 'WITNESS_INCIDENT', 'limit' => 20),
            array('needle' => 'admin/electiondaycenter/verify', 'method' => 'POST', 'key' => 'REALCOUNT_VERIFY', 'limit' => 30),
            array('needle' => 'admin/integrateddatacenter/apply', 'method' => 'POST', 'key' => 'IMPORT_APPLY', 'limit' => 10),
            array('needle' => 'admin/integrateddatacenter/rollback', 'method' => 'POST', 'key' => 'IMPORT_ROLLBACK', 'limit' => 10),
            array('needle' => 'admin/electiondaycenter/export', 'method' => 'GET', 'key' => 'REPORT_EXPORT', 'limit' => 30),
        );

        foreach ($rules as $rule) {
            if ($method !== $rule['method'] || strpos($uri, $rule['needle']) !== 0) {
                continue;
            }

            $actor = 'guest';
            if (isset($CI->session)) {
                if ($CI->session->userdata('id')) {
                    $actor = 'admin:' . (string) $CI->session->userdata('id');
                } elseif ($CI->session->userdata('id_anggota')) {
                    $actor = 'anggota:' . (string) $CI->session->userdata('id_anggota');
                }
            }

            $actorKey = hash('sha256', $actor . '|' . (string) $CI->input->ip_address());
            $window = date('Y-m-d H:i:00');

            $CI->db->query(
                'INSERT INTO security_runtime_rate_limits
                    (endpoint_key, actor_key, window_start, hit_count, updated_at)
                 VALUES (?, ?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE hit_count = hit_count + 1, updated_at = NOW()',
                array($rule['key'], $actorKey, $window)
            );

            $row = $CI->db
                ->where('endpoint_key', $rule['key'])
                ->where('actor_key', $actorKey)
                ->where('window_start', $window)
                ->limit(1)
                ->get('security_runtime_rate_limits')
                ->row_array();

            if ($row && (int) $row['hit_count'] > (int) $rule['limit']) {
                show_error('Terlalu banyak request. Silakan ulangi setelah satu menit.', 429, 'Rate Limit');
            }

            return;
        }
    }

    private function claimFinalization($CI)
    {
        if (
            !$CI->db->table_exists('real_count_tps_registry') ||
            strtoupper((string) $CI->input->method(TRUE)) !== 'POST'
        ) {
            return;
        }

        $uri = strtolower(trim((string) $CI->uri->uri_string(), '/'));
        if (strpos($uri, 'witness/witness360/result') !== 0 || !$CI->input->post('finalize')) {
            return;
        }

        $personId = 0;
        if (isset($CI->session) && $CI->session->userdata('id_anggota')) {
            $map = $CI->db
                ->where('source_type', 'ANGGOTA')
                ->where('source_id', (string) $CI->session->userdata('id_anggota'))
                ->limit(1)
                ->get('legacy_identity_map')
                ->row_array();
            $personId = $map ? (int) $map['person_id'] : 0;
        }

        if (!$personId) {
            return;
        }

        $witness = $CI->db
            ->where('person_id', $personId)
            ->where('is_active', 1)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('campaign_witnesses')
            ->row_array();

        if (!$witness || empty($witness['event_id']) || (string) $witness['id_tps'] === '') {
            return;
        }

        $eventId = (int) $witness['event_id'];
        $idTps = (string) $witness['id_tps'];
        $witnessId = (int) $witness['id'];
        $now = date('Y-m-d H:i:s');

        $CI->db->trans_begin();

        $registry = $CI->db->query(
            'SELECT * FROM real_count_tps_registry
             WHERE event_id = ? AND id_tps = ?
             LIMIT 1 FOR UPDATE',
            array($eventId, $idTps)
        )->row_array();

        if ($registry && (int) $registry['witness_id'] !== $witnessId) {
            $authoritative = $CI->db
                ->where('event_id', $eventId)
                ->where('id_tps', $idTps)
                ->where_in('status', array('FINALIZED', 'VALIDATED', 'VERIFIED'))
                ->count_all_results('witness_result_headers');

            $stale = !empty($registry['claimed_at']) &&
                strtotime($registry['claimed_at']) < strtotime('-10 minutes');

            if ($authoritative > 0 || !$stale) {
                $CI->db->trans_rollback();
                show_error(
                    'TPS ini sudah memiliki proses finalisasi otoritatif. Hubungi verifikator bila perlu revisi.',
                    409,
                    'Finalisasi TPS Terkunci'
                );
            }

            $CI->db
                ->where('event_id', $eventId)
                ->where('id_tps', $idTps)
                ->update('real_count_tps_registry', array(
                    'witness_id' => $witnessId,
                    'header_id' => 0,
                    'state' => 'CLAIMED',
                    'claimed_at' => $now,
                    'updated_at' => $now,
                ));
        } elseif (!$registry) {
            $CI->db->insert('real_count_tps_registry', array(
                'event_id' => $eventId,
                'id_tps' => $idTps,
                'witness_id' => $witnessId,
                'header_id' => 0,
                'state' => 'CLAIMED',
                'claimed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ));

            if (!$CI->db->trans_status()) {
                $CI->db->trans_rollback();
                show_error('Finalisasi TPS sedang diproses oleh request lain.', 409, 'Finalisasi TPS Terkunci');
            }
        }

        if ($CI->db->trans_status() === FALSE) {
            $CI->db->trans_rollback();
            show_error('Gagal mengunci finalisasi TPS.', 409, 'Finalisasi TPS Terkunci');
        }

        $CI->db->trans_commit();

        if (isset($CI->session)) {
            $CI->session->set_userdata($this->claimSessionKey, array(
                'event_id' => $eventId,
                'id_tps' => $idTps,
                'witness_id' => $witnessId,
            ));
        }
    }
}
