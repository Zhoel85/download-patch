<?php
defined('BASEPATH') or exit('No direct script access allowed');

class LegacyDependencyHook
{
    public function handle()
    {
        $CI =& get_instance();

        if (!isset($CI->db) || !$CI->db->table_exists('legacy_freeze_rules')) {
            return;
        }

        $uri = strtolower(trim((string) $CI->uri->uri_string(), '/'));
        if ($uri === '') {
            return;
        }

        $resource = $this->matchResource($uri);
        if ($resource === null) {
            return;
        }

        $method = strtoupper((string) $CI->input->method(TRUE));
        $accessType = in_array($method, array('POST','PUT','PATCH','DELETE'), TRUE) ? 'WRITE' : 'READ';

        $actorType = 'GUEST';
        $actorId = '';
        if ($CI->session->userdata('id')) {
            $actorType = 'ADMIN';
            $actorId = (string) $CI->session->userdata('id');
        } elseif ($CI->session->userdata('id_anggota')) {
            $actorType = 'ANGGOTA';
            $actorId = (string) $CI->session->userdata('id_anggota');
        }

        if ($CI->db->table_exists('legacy_dependency_events')) {
            $CI->db->insert('legacy_dependency_events', array(
                'resource_code' => $resource,
                'access_type' => $accessType,
                'http_method' => $method,
                'request_uri' => substr($uri, 0, 255),
                'actor_type' => $actorType,
                'actor_id' => substr($actorId, 0, 64),
                'ip_address' => substr((string) $CI->input->ip_address(), 0, 64),
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }

        $rule = $CI->db->where('resource_code', $resource)->limit(1)
            ->get('legacy_freeze_rules')->row_array();
        $mode = isset($rule['mode']) ? strtoupper((string) $rule['mode']) : 'MONITOR';

        if ($mode === 'DISABLED' || ($mode === 'READ_ONLY' && $accessType === 'WRITE')) {
            if (isset($CI->session)) {
                $CI->session->set_flashdata('error', 'Jalur legacy sudah dibekukan. Gunakan modul canonical pengganti.');
            }
            show_error(
                'Modul legacy "' . html_escape($resource) . '" berada dalam mode ' .
                html_escape($mode) . '. Gunakan modul produksi/canonical pengganti.',
                409,
                'Legacy Compatibility Guard'
            );
        }
    }

    private function matchResource($uri)
    {
        $maps = array(
            'LEGACY_REAL_COUNT' => array(
                'admin/saksi/suaramasuk','admin/saksi/realcount',
                'admins/saksi/suaramasuk','admins/saksi/realcount',
                'adminrealcount/','admin/adminrealcount'
            ),
            'LEGACY_SAKSI' => array('admin/saksi','admins/saksi'),
            'LEGACY_ADMIN_ROLE' => array(
                'admin/datauser','admins/datauser',
                'admin/adminkabupaten','admins/adminkabupaten',
                'admin/adminkecamatan','admins/adminkecamatan',
                'admin/admindesa','admins/admindesa'
            ),
            'LEGACY_FIELD_ROLE' => array('korkab/','korcam/','kordes/','tim10/','relawan/')
        );

        foreach ($maps as $resource => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($uri === rtrim($prefix, '/') || strpos($uri, $prefix) === 0) {
                    return $resource;
                }
            }
        }
        return null;
    }
}
