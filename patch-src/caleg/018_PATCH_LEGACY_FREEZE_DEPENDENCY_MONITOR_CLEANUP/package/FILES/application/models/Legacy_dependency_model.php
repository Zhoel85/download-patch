<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Legacy_dependency_model extends CI_Model
{
    public function rule($resourceCode)
    {
        return $this->db->where('resource_code', $resourceCode)->limit(1)
            ->get('legacy_freeze_rules')->row_array();
    }

    public function stats($days = 7)
    {
        $days = max(1, min(365, (int) $days));
        $since = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        $sql = '
            SELECT r.resource_code,r.mode,r.description,r.replacement_module,
                   r.cleanup_status,r.updated_at,
                   SUM(CASE WHEN e.created_at >= ? AND e.access_type="READ" THEN 1 ELSE 0 END) read_count,
                   SUM(CASE WHEN e.created_at >= ? AND e.access_type="WRITE" THEN 1 ELSE 0 END) write_count,
                   MAX(e.created_at) last_access
            FROM legacy_freeze_rules r
            LEFT JOIN legacy_dependency_events e ON e.resource_code=r.resource_code
            GROUP BY r.resource_code,r.mode,r.description,r.replacement_module,
                     r.cleanup_status,r.updated_at
            ORDER BY r.resource_code ASC
        ';
        return $this->db->query($sql, array($since, $since))->result_array();
    }

    public function recent($limit = 100)
    {
        return $this->db->order_by('id','DESC')->limit(max(1,min(500,(int)$limit)))
            ->get('legacy_dependency_events')->result_array();
    }

    public function setMode($resourceCode, $newMode, $actorId)
    {
        $old = $this->rule($resourceCode);
        if (!$old) {
            return false;
        }

        $this->db->trans_start();
        $this->db->where('resource_code', $resourceCode)->update('legacy_freeze_rules', array(
            'mode' => $newMode,
            'updated_by' => (string) $actorId,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $this->db->insert('legacy_freeze_history', array(
            'resource_code' => $resourceCode,
            'old_mode' => (string) $old['mode'],
            'new_mode' => $newMode,
            'actor_id' => (string) $actorId,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
