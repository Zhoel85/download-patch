<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Production_readiness_model extends CI_Model
{
    public function audit()
    {
        $checks = array();

        $requiredTables = array(
            'election_events',
            'election_candidates',
            'election_event_tps',
            'dpt_records',
            'campaign_org_assignments',
            'campaign_witnesses',
            'campaign_incidents',
            'witness_result_headers',
            'witness_result_lines',
            'real_count_revision_history',
            'campaign_live_activity',
            'legacy_freeze_rules',
            'legacy_dependency_events',
            'real_count_tps_registry',
            'security_runtime_rate_limits',
        );

        foreach ($requiredTables as $table) {
            $exists = $this->db->table_exists($table);
            $checks[] = $this->item(
                $exists ? 'PASS' : 'BLOCK',
                'Tabel ' . $table,
                $exists ? 'Tersedia.' : 'Tidak ditemukan.'
            );
        }

        $activeEvent = $this->db
            ->group_start()
                ->where('is_active', 1)
                ->or_where('status', 'ACTIVE')
            ->group_end()
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('election_events')
            ->row_array();

        if ($activeEvent) {
            $eventId = (int) $activeEvent['id'];
            $mapped = $this->db
                ->where('event_id', $eventId)
                ->where('is_active', 1)
                ->count_all_results('election_event_tps');
            $checks[] = $this->item(
                $mapped > 0 ? 'PASS' : 'WARN',
                'TPS event aktif',
                $mapped > 0 ? $mapped . ' TPS ter-mapping.' : 'Belum ada mapping TPS; sistem akan memakai fallback master TPS.'
            );
        } else {
            $checks[] = $this->item('WARN', 'Event aktif', 'Belum ada event aktif.');
        }

        $duplicates = $this->db->query(
            'SELECT COUNT(*) n FROM (
                SELECT event_id,id_tps,COUNT(*) c
                FROM witness_result_headers
                WHERE status IN ("FINALIZED","VALIDATED","VERIFIED")
                GROUP BY event_id,id_tps
                HAVING COUNT(*) > 1
            ) x'
        )->row_array();

        $checks[] = $this->item(
            (int) $duplicates['n'] === 0 ? 'PASS' : 'BLOCK',
            'Concurrency Real Count',
            (int) $duplicates['n'] === 0
                ? 'Tidak ada TPS dengan lebih dari satu hasil otoritatif.'
                : $duplicates['n'] . ' kombinasi Event+TPS memiliki hasil otoritatif ganda.'
        );

        $orphans = $this->db->query(
            'SELECT COUNT(*) n
             FROM witness_result_lines l
             LEFT JOIN witness_result_headers h ON h.id=l.header_id
             WHERE h.id IS NULL'
        )->row_array();

        $checks[] = $this->item(
            (int) $orphans['n'] === 0 ? 'PASS' : 'BLOCK',
            'Integritas baris hasil',
            (int) $orphans['n'] === 0 ? 'Tidak ada orphan result line.' : $orphans['n'] . ' orphan result line ditemukan.'
        );

        $mismatch = $this->db->query(
            'SELECT COUNT(*) n FROM (
                SELECT h.id,h.total_valid,COALESCE(SUM(l.votes),0) votes
                FROM witness_result_headers h
                LEFT JOIN witness_result_lines l ON l.header_id=h.id
                WHERE h.status="VERIFIED"
                GROUP BY h.id,h.total_valid
                HAVING h.total_valid <> COALESCE(SUM(l.votes),0)
            ) x'
        )->row_array();

        $checks[] = $this->item(
            (int) $mismatch['n'] === 0 ? 'PASS' : 'BLOCK',
            'Rekonsiliasi suara VERIFIED',
            (int) $mismatch['n'] === 0
                ? 'Total suara kandidat konsisten dengan total_valid.'
                : $mismatch['n'] . ' hasil VERIFIED tidak cocok dengan jumlah baris kandidat.'
        );

        $pending = $this->db
            ->where_in('status', array('FINALIZED', 'VALIDATED', 'REVISION'))
            ->count_all_results('witness_result_headers');

        $checks[] = $this->item(
            $pending === 0 ? 'PASS' : 'WARN',
            'Antrian verifikasi',
            $pending === 0 ? 'Tidak ada hasil tertunda.' : $pending . ' hasil menunggu verifikasi/revisi.'
        );

        $legacyWrites = 0;
        if ($this->db->table_exists('legacy_dependency_events')) {
            $legacyWrites = $this->db
                ->where('access_type', 'WRITE')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->count_all_results('legacy_dependency_events');
        }

        $checks[] = $this->item(
            $legacyWrites === 0 ? 'PASS' : 'WARN',
            'Legacy write 7 hari',
            $legacyWrites === 0 ? 'Tidak ada write legacy terdeteksi.' : $legacyWrites . ' write legacy terdeteksi; evaluasi sebelum delete/archive.'
        );

        $md5User = $this->countLegacyMd5('data_user');
        $md5Anggota = $this->countLegacyMd5('data_anggota');
        $legacyMd5 = $md5User + $md5Anggota;

        $checks[] = $this->item(
            $legacyMd5 === 0 ? 'PASS' : 'WARN',
            'Password legacy MD5',
            $legacyMd5 === 0
                ? 'Tidak ada password MD5 legacy yang terdeteksi.'
                : $legacyMd5 . ' password legacy masih berbentuk MD5 dan harus dimigrasikan saat login/reset.'
        );

        $displayErrors = strtolower((string) ini_get('display_errors'));
        $checks[] = $this->item(
            in_array($displayErrors, array('', '0', 'off'), true) ? 'PASS' : 'WARN',
            'display_errors',
            'Nilai runtime: ' . ($displayErrors === '' ? '(kosong)' : $displayErrors)
        );

        $protected = array(
            APPPATH . 'uploads/import_center/.htaccess',
            APPPATH . 'uploads/election_incidents/.htaccess',
            APPPATH . 'uploads/real_count/.htaccess',
            APPPATH . 'uploads/campaign_docs/.htaccess',
        );

        $missingProtected = array();
        foreach ($protected as $path) {
            if (!is_file($path)) {
                $missingProtected[] = str_replace(FCPATH, '', $path);
            }
        }

        $checks[] = $this->item(
            empty($missingProtected) ? 'PASS' : 'WARN',
            'Protected upload folders',
            empty($missingProtected) ? 'Folder sensitif memiliki .htaccess.' : 'Belum terlindungi: ' . implode(', ', $missingProtected)
        );

        $indexChecks = array(
            array('witness_result_headers', 'idx_rc_event_tps_status'),
            array('witness_result_headers', 'idx_rc_event_status_updated'),
            array('campaign_incidents', 'idx_incident_event_tps_status'),
            array('campaign_witnesses', 'idx_witness_event_tps_active'),
            array('campaign_live_activity', 'idx_live_event_created'),
            array('dpt_records', 'idx_dpt_event_area'),
            array('campaign_org_assignments', 'idx_org_event_role_area'),
        );

        foreach ($indexChecks as $index) {
            $exists = $this->indexExists($index[0], $index[1]);
            $checks[] = $this->item(
                $exists ? 'PASS' : 'WARN',
                'Index ' . $index[1],
                $exists ? 'Tersedia pada ' . $index[0] . '.' : 'Belum ditemukan pada ' . $index[0] . '.'
            );
        }

        $block = 0;
        $warn = 0;
        foreach ($checks as $check) {
            if ($check['level'] === 'BLOCK') {
                $block++;
            } elseif ($check['level'] === 'WARN') {
                $warn++;
            }
        }

        $status = $block > 0 ? 'NO-GO' : ($warn > 0 ? 'CONDITIONAL' : 'GO');

        return array(
            'status' => $status,
            'block_count' => $block,
            'warn_count' => $warn,
            'checks' => $checks,
            'generated_at' => date('Y-m-d H:i:s'),
        );
    }

    private function countLegacyMd5($table)
    {
        if (!$this->db->table_exists($table) || !$this->db->field_exists('password', $table)) {
            return 0;
        }

        $row = $this->db->query(
            'SELECT COUNT(*) n FROM ' . $this->db->escape_identifiers($table) .
            ' WHERE password REGEXP "^[0-9a-fA-F]{32}$"'
        )->row_array();

        return isset($row['n']) ? (int) $row['n'] : 0;
    }

    private function indexExists($table, $index)
    {
        $row = $this->db->query(
            'SELECT COUNT(*) n
             FROM information_schema.statistics
             WHERE table_schema=DATABASE() AND table_name=? AND index_name=?',
            array($table, $index)
        )->row_array();

        return isset($row['n']) && (int) $row['n'] > 0;
    }

    private function item($level, $name, $detail)
    {
        return array(
            'level' => $level,
            'name' => $name,
            'detail' => $detail,
        );
    }
}
