<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ElectionDay_model extends CI_Model
{
    public function events()
    {
        return $this->db
            ->order_by('election_year', 'DESC')
            ->order_by('id', 'DESC')
            ->get('election_events')
            ->result_array();
    }

    public function candidates($eventId)
    {
        return $this->db
            ->where('event_id', (int) $eventId)
            ->where('status', 'ACTIVE')
            ->order_by('serial_number', 'ASC')
            ->get('election_candidates')
            ->result_array();
    }

    public function monitor($eventId)
    {
        $eventId = (int) $eventId;
        $totalTps = $this->eventTpsCount($eventId);

        $witnessTotal = $this->count('campaign_witnesses', array(
            'event_id' => $eventId,
            'is_active' => 1,
        ));

        $checkin = (int) $this->db
            ->select('COUNT(DISTINCT w.id_tps) AS n', false)
            ->from('campaign_witness_day d')
            ->join('campaign_witnesses w', 'w.id=d.witness_id')
            ->where('w.event_id', $eventId)
            ->where('d.checkin_at IS NOT NULL', null, false)
            ->get()
            ->row()->n;

        $resultIn = (int) $this->db
            ->select('COUNT(DISTINCT id_tps) AS n', false)
            ->where('event_id', $eventId)
            ->where('status !=', 'DRAFT')
            ->get('witness_result_headers')
            ->row()->n;

        $verified = (int) $this->db
            ->select('COUNT(DISTINCT id_tps) AS n', false)
            ->where('event_id', $eventId)
            ->where('status', 'VERIFIED')
            ->get('witness_result_headers')
            ->row()->n;

        $pending = (int) $this->db
            ->select('COUNT(DISTINCT id_tps) AS n', false)
            ->where('event_id', $eventId)
            ->where_in('status', array('FINALIZED', 'VALIDATED', 'REVISION'))
            ->get('witness_result_headers')
            ->row()->n;

        $incidents = $this->db
            ->where('event_id', $eventId)
            ->where('status !=', 'DONE')
            ->count_all_results('campaign_incidents');

        $problemTps = (int) $this->db
            ->select('COUNT(DISTINCT id_tps) AS n', false)
            ->where('event_id', $eventId)
            ->where('status !=', 'DONE')
            ->get('campaign_incidents')
            ->row()->n;

        return array(
            'tps_total' => $totalTps,
            'witness_total' => $witnessTotal,
            'checkin' => $checkin,
            'not_checkin' => max(0, $totalTps - $checkin),
            'result_in' => $resultIn,
            'result_missing' => max(0, $totalTps - $resultIn),
            'pending' => $pending,
            'verified' => $verified,
            'problem_tps' => $problemTps,
            'incidents' => $incidents,
        );
    }

    public function progressByKecamatan($eventId)
    {
        $eventId = (int) $eventId;
        $mapping = $this->hasEventTpsMapping($eventId);

        $mappingJoin = $mapping
            ? ' INNER JOIN election_event_tps etm ON etm.id_tps=t.id_tps AND etm.event_id=' . $eventId . ' AND etm.is_active=1 '
            : ' ';

        $sql = '
            SELECT
                k.id_kecamatan,
                k.nama_kecamatan,
                COUNT(DISTINCT t.id_tps) total_tps,
                COUNT(DISTINCT wd_t.id_tps) checkin_tps,
                COUNT(DISTINCT rh.id_tps) result_tps,
                COUNT(DISTINCT vr.id_tps) verified_tps,
                COUNT(DISTINCT ci.id_tps) problem_tps
            FROM master_kecamatan k
            LEFT JOIN master_tps t ON t.id_kecamatan=k.id_kecamatan
            ' . $mappingJoin . '
            LEFT JOIN (
                SELECT DISTINCT w.id_tps
                FROM campaign_witness_day d
                JOIN campaign_witnesses w ON w.id=d.witness_id
                WHERE w.event_id=? AND d.checkin_at IS NOT NULL
            ) wd_t ON wd_t.id_tps=t.id_tps
            LEFT JOIN (
                SELECT DISTINCT id_tps
                FROM witness_result_headers
                WHERE event_id=? AND status<>"DRAFT"
            ) rh ON rh.id_tps=t.id_tps
            LEFT JOIN (
                SELECT DISTINCT id_tps
                FROM witness_result_headers
                WHERE event_id=? AND status="VERIFIED"
            ) vr ON vr.id_tps=t.id_tps
            LEFT JOIN (
                SELECT DISTINCT id_tps
                FROM campaign_incidents
                WHERE event_id=? AND status<>"DONE"
            ) ci ON ci.id_tps=t.id_tps
            GROUP BY k.id_kecamatan,k.nama_kecamatan
            HAVING total_tps > 0
            ORDER BY k.nama_kecamatan
        ';

        return $this->db->query($sql, array($eventId, $eventId, $eventId, $eventId))->result_array();
    }

    public function incidents($eventId, $status = '')
    {
        $this->db
            ->select('i.*,k.nama_kecamatan,d.nama_desa,t.nama_tps,p.full_name pic_name')
            ->from('campaign_incidents i')
            ->join('master_kecamatan k', 'k.id_kecamatan=i.id_kecamatan', 'left')
            ->join('master_desa d', 'd.id_desa=i.id_desa', 'left')
            ->join('master_tps t', 't.id_tps=i.id_tps', 'left')
            ->join('persons p', 'p.id=i.pic_person_id', 'left')
            ->where('i.event_id', (int) $eventId);

        if ($status) {
            $this->db->where('i.status', $status);
        }

        return $this->db
            ->order_by("FIELD(i.priority,'CRITICAL','HIGH','MEDIUM','LOW')", '', false)
            ->order_by('i.incident_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function saveIncident(array $data, $id = 0, $actor = '')
    {
        $now = date('Y-m-d H:i:s');
        $data['updated_at'] = $now;

        $this->db->trans_start();

        if ($id) {
            $this->db->where('id', (int) $id)->update('campaign_incidents', $data);
            $incidentId = (int) $id;
            $action = 'UPDATE';
        } else {
            $data['created_at'] = $now;
            $this->db->insert('campaign_incidents', $data);
            $incidentId = (int) $this->db->insert_id();
            $action = 'CREATE';
        }

        $this->activity(
            (int) $data['event_id'],
            'INCIDENT_' . $action,
            'INCIDENT',
            $incidentId,
            'Insiden ' . $data['category'] . ' pada TPS ' . $data['id_tps'],
            $actor,
            array('priority' => $data['priority'], 'status' => $data['status'])
        );

        $this->db->trans_complete();
        return $this->db->trans_status() ? $incidentId : 0;
    }

    public function results($eventId, $status = '')
    {
        $this->db
            ->select('h.*,t.nama_tps,d.nama_desa,k.nama_kecamatan,p.full_name witness_name,v.full_name verifier_name')
            ->from('witness_result_headers h')
            ->join('campaign_witnesses w', 'w.id=h.witness_id', 'left')
            ->join('persons p', 'p.id=w.person_id', 'left')
            ->join('persons v', 'v.id=h.verifier_person_id', 'left')
            ->join('master_tps t', 't.id_tps=h.id_tps', 'left')
            ->join('master_desa d', 'd.id_desa=t.id_desa', 'left')
            ->join('master_kecamatan k', 'k.id_kecamatan=t.id_kecamatan', 'left')
            ->where('h.event_id', (int) $eventId);

        if ($status) {
            $this->db->where('h.status', $status);
        }

        return $this->db->order_by('h.updated_at', 'DESC')->get()->result_array();
    }

    public function resultDetail($id)
    {
        $header = $this->db->get_where('witness_result_headers', array('id' => (int) $id))->row_array();
        if (!$header) {
            return array();
        }

        $lines = $this->db
            ->select('l.*,c.full_name,c.serial_number')
            ->from('witness_result_lines l')
            ->join('election_candidates c', 'c.id=l.candidate_id', 'left')
            ->where('l.header_id', (int) $id)
            ->order_by('c.serial_number', 'ASC')
            ->get()
            ->result_array();

        return array('header' => $header, 'lines' => $lines);
    }

    public function verifyResult($id, $decision, $actorPersonId, $actor = '', $notes = '')
    {
        $row = $this->db->get_where('witness_result_headers', array('id' => (int) $id))->row_array();
        if (!$row) {
            return false;
        }

        if ((int) $actorPersonId <= 0) {
            $actorPersonId = $this->resolveCurrentPersonId();
        }

        $newStatus = $decision === 'VERIFY' ? 'VERIFIED' : 'REVISION';
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->db->insert('real_count_revision_history', array(
            'header_id' => (int) $id,
            'revision_no' => (int) $row['revision_no'] + 1,
            'before_json' => json_encode($row),
            'action_code' => $newStatus,
            'notes' => $notes,
            'actor_id' => $actor,
            'created_at' => $now,
        ));

        $this->db->where('id', (int) $id)->update('witness_result_headers', array(
            'status' => $newStatus,
            'verifier_person_id' => (int) $actorPersonId,
            'verified_at' => $newStatus === 'VERIFIED' ? $now : null,
            'verification_notes' => $notes,
            'revision_no' => (int) $row['revision_no'] + 1,
            'updated_at' => $now,
        ));

        if ($this->db->table_exists('real_count_tps_registry')) {
            $this->db
                ->where('event_id', (int) $row['event_id'])
                ->where('id_tps', (string) $row['id_tps'])
                ->update('real_count_tps_registry', array(
                    'witness_id' => (int) $row['witness_id'],
                    'header_id' => (int) $id,
                    'state' => $newStatus,
                    'updated_at' => $now,
                ));
        }

        $this->activity(
            (int) $row['event_id'],
            $newStatus === 'VERIFIED' ? 'RESULT_VERIFIED' : 'RESULT_REVISION',
            'RESULT',
            (int) $id,
            $newStatus === 'VERIFIED'
                ? 'Hasil TPS ' . $row['id_tps'] . ' telah diverifikasi.'
                : 'Hasil TPS ' . $row['id_tps'] . ' dikembalikan untuk revisi.',
            $actor,
            array('id_tps' => $row['id_tps'])
        );

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function recap($eventId)
    {
        $eventId = (int) $eventId;

        return $this->db
            ->select(
                'c.id,c.serial_number,c.full_name,' .
                'COALESCE(SUM(CASE WHEN h.status="VERIFIED" AND h.event_id=' . $eventId .
                ' THEN l.votes ELSE 0 END),0) votes',
                false
            )
            ->from('election_candidates c')
            ->join('witness_result_lines l', 'l.candidate_id=c.id', 'left')
            ->join('witness_result_headers h', 'h.id=l.header_id', 'left')
            ->where('c.event_id', $eventId)
            ->group_by(array('c.id', 'c.serial_number', 'c.full_name'))
            ->order_by('c.serial_number', 'ASC')
            ->get()
            ->result_array();
    }

    public function recapTotals($eventId)
    {
        $result = $this->db
            ->select(
                'COUNT(DISTINCT id_tps) tps_in,' .
                'COALESCE(SUM(total_valid),0) valid_votes,' .
                'COALESCE(SUM(total_invalid),0) invalid_votes,' .
                'COALESCE(SUM(voters_present),0) voters_present',
                false
            )
            ->where('event_id', (int) $eventId)
            ->where('status', 'VERIFIED')
            ->get('witness_result_headers')
            ->row_array();

        $result['total_tps'] = $this->eventTpsCount((int) $eventId);
        return $result;
    }

    public function activity($eventId, $eventCode, $entityType, $entityId, $message, $actor = '', $meta = array())
    {
        $now = date('Y-m-d H:i:s');

        $this->db->insert('campaign_live_activity', array(
            'event_id' => (int) $eventId,
            'event_code' => (string) $eventCode,
            'entity_type' => (string) $entityType,
            'entity_id' => (int) $entityId,
            'message' => (string) $message,
            'actor_id' => (string) $actor,
            'meta_json' => json_encode($meta),
            'created_at' => $now,
        ));

        $this->db->insert('campaign_notifications', array(
            'event_id' => (int) $eventId,
            'recipient_role' => 'SUPER_ADMIN',
            'recipient_person_id' => 0,
            'title' => ucwords(strtolower(str_replace('_', ' ', (string) $eventCode))),
            'message' => (string) $message,
            'entity_type' => (string) $entityType,
            'entity_id' => (int) $entityId,
            'is_read' => 0,
            'created_at' => $now,
        ));
    }

    public function activities($eventId, $limit = 100)
    {
        return $this->db
            ->where('event_id', (int) $eventId)
            ->order_by('id', 'DESC')
            ->limit(max(1, min(500, (int) $limit)))
            ->get('campaign_live_activity')
            ->result_array();
    }

    public function notifications($eventId, $limit = 100)
    {
        return $this->db
            ->where('event_id', (int) $eventId)
            ->order_by('id', 'DESC')
            ->limit(max(1, min(500, (int) $limit)))
            ->get('campaign_notifications')
            ->result_array();
    }

    public function rotateDisplayToken($eventId)
    {
        $eventId = (int) $eventId;
        $plain = bin2hex(random_bytes(24));
        $hash = hash('sha256', $plain);
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $this->db->where('event_id', $eventId)->update('real_count_display_tokens', array('is_active' => 0));
        $this->db->insert('real_count_display_tokens', array(
            'event_id' => $eventId,
            'token_hash' => $hash,
            'token_preview' => substr($plain, 0, 12),
            'is_active' => 1,
            'created_at' => $now,
        ));
        $id = (int) $this->db->insert_id();
        $this->db->trans_complete();

        return array(
            'ok' => $this->db->trans_status(),
            'id' => $id,
            'plain_token' => $plain,
            'preview' => substr($plain, 0, 12),
        );
    }

    public function activeDisplayToken($eventId)
    {
        return $this->db
            ->where('event_id', (int) $eventId)
            ->where('is_active', 1)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('real_count_display_tokens')
            ->row_array();
    }

    public function validateDisplayToken($eventId, $plainToken)
    {
        if ($plainToken === '') {
            return false;
        }

        $hash = hash('sha256', (string) $plainToken);
        $row = $this->db
            ->where('event_id', (int) $eventId)
            ->where('token_hash', $hash)
            ->where('is_active', 1)
            ->limit(1)
            ->get('real_count_display_tokens')
            ->row_array();

        if (!$row) {
            return false;
        }

        $this->db->where('id', (int) $row['id'])->update(
            'real_count_display_tokens',
            array('last_used_at' => date('Y-m-d H:i:s'))
        );

        return true;
    }

    public function reportRows($type, $eventId)
    {
        $eventId = (int) $eventId;

        switch ($type) {
            case 'organization':
                return $this->db
                    ->select('a.event_id,a.role_code,p.full_name,a.id_kecamatan,a.id_desa,a.id_tps,a.status,a.start_date,a.end_date')
                    ->from('campaign_org_assignments a')
                    ->join('persons p', 'p.id=a.person_id', 'left')
                    ->where('a.event_id', $eventId)
                    ->order_by('a.role_code', 'ASC')
                    ->get()
                    ->result_array();

            case 'members':
                return $this->db
                    ->select('p.full_name,p.phone,p.email,m.verification_status')
                    ->from('campaign_member_profiles m')
                    ->join('persons p', 'p.id=m.person_id', 'left')
                    ->join('campaign_org_assignments a', 'a.person_id=p.id AND a.event_id=' . $eventId, 'inner')
                    ->group_by(array('p.id', 'p.full_name', 'p.phone', 'p.email', 'm.verification_status'))
                    ->get()
                    ->result_array();

            case 'activities':
                return $this->db
                    ->select('activity_type,title,activity_date,start_time,end_time,location_name,id_kecamatan,id_desa,id_tps,participant_count,status,result_notes,follow_up')
                    ->where('event_id', $eventId)
                    ->order_by('activity_date', 'DESC')
                    ->get('campaign_activities')
                    ->result_array();

            case 'witnesses':
                return $this->db
                    ->select('w.event_id,p.full_name,w.id_kecamatan,w.id_desa,w.id_tps,w.witness_type,w.training_status,w.mandate_status,w.readiness_status,w.is_active')
                    ->from('campaign_witnesses w')
                    ->join('persons p', 'p.id=w.person_id', 'left')
                    ->where('w.event_id', $eventId)
                    ->get()
                    ->result_array();

            case 'incidents':
                return $this->db
                    ->select('event_id,id_kecamatan,id_desa,id_tps,incident_at,category,priority,status,description')
                    ->where('event_id', $eventId)
                    ->order_by('incident_at', 'DESC')
                    ->get('campaign_incidents')
                    ->result_array();

            case 'real_count':
                return $this->db
                    ->select('event_id,id_tps,total_valid,total_invalid,voters_present,status,finalized_at,verified_at,verifier_person_id')
                    ->where('event_id', $eventId)
                    ->where('status', 'VERIFIED')
                    ->order_by('id_tps', 'ASC')
                    ->get('witness_result_headers')
                    ->result_array();

            case 'activity_log':
                return $this->db
                    ->select('created_at,event_code,entity_type,entity_id,message,actor_id')
                    ->where('event_id', $eventId)
                    ->order_by('id', 'DESC')
                    ->limit(10000)
                    ->get('campaign_live_activity')
                    ->result_array();
        }

        return array();
    }

    private function hasEventTpsMapping($eventId)
    {
        if (!$this->db->table_exists('election_event_tps')) {
            return false;
        }

        return $this->db
            ->where('event_id', (int) $eventId)
            ->where('is_active', 1)
            ->count_all_results('election_event_tps') > 0;
    }

    private function eventTpsCount($eventId)
    {
        if ($this->hasEventTpsMapping($eventId)) {
            return (int) $this->db
                ->where('event_id', (int) $eventId)
                ->where('is_active', 1)
                ->count_all_results('election_event_tps');
        }

        return (int) $this->db->count_all('master_tps');
    }

    private function resolveCurrentPersonId()
    {
        if (!isset($this->session)) {
            return 0;
        }

        $source = $this->session->userdata('id_anggota') ? 'ANGGOTA' : 'USER';
        $sourceId = $source === 'ANGGOTA'
            ? $this->session->userdata('id_anggota')
            : $this->session->userdata('id');

        if (!$sourceId || !$this->db->table_exists('legacy_identity_map')) {
            return 0;
        }

        $row = $this->db
            ->where('source_type', $source)
            ->where('source_id', (string) $sourceId)
            ->limit(1)
            ->get('legacy_identity_map')
            ->row_array();

        return $row ? (int) $row['person_id'] : 0;
    }

    private function count($table, array $where)
    {
        $this->db->from($table);
        foreach ($where as $key => $value) {
            $this->db->where($key, $value);
        }
        return (int) $this->db->count_all_results();
    }
}
