<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FinalNavigationHook
{
    public function render()
    {
        $CI =& get_instance();
        $output = (string) $CI->output->get_output();

        if (
            stripos($output, '</body>') === false ||
            strpos($output, 'id="sidebar-menu"') === false ||
            !isset($CI->session) ||
            (!$CI->session->userdata('id') && !$CI->session->userdata('id_anggota'))
        ) {
            echo $output;
            return;
        }

        $event = array();
        if (isset($CI->db) && $CI->db->table_exists('election_events')) {
            $event = $CI->db
                ->group_start()
                    ->where('is_active', 1)
                    ->or_where('status', 'ACTIVE')
                ->group_end()
                ->order_by('election_date', 'ASC')
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get('election_events')
                ->row_array();
        }

        $isSuper = (bool) (
            $CI->session->userdata('id') &&
            (string) $CI->session->userdata('hak_akses') === '1'
        );

        $roleLabel = $isSuper ? 'Super Admin' : 'Pengguna';
        if (!$isSuper && $CI->session->userdata('id_anggota')) {
            $roleLabel = 'Tim Lapangan';
        }

        $eventName = $event && !empty($event['event_name'])
            ? (string) $event['event_name']
            : 'Belum ada event aktif';

        $eventYear = '';
        if ($event) {
            if (!empty($event['election_year'])) {
                $eventYear = (string) $event['election_year'];
            } elseif (!empty($event['election_date'])) {
                $eventYear = date('Y', strtotime($event['election_date']));
            }
        }

        $payload = array(
            'base' => rtrim(base_url(), '/') . '/',
            'isSuper' => $isSuper,
            'roleLabel' => $roleLabel,
            'eventName' => $eventName,
            'eventYear' => $eventYear,
        );

        $json = json_encode(
            $payload,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        $css = base_url('assets/css/campaign-portal-v2.css?v=022');
        $js = base_url('assets/js/campaign-portal-v2.js?v=021');

        $injection =
            '<link rel="stylesheet" href="' . html_escape($css) . '">' .
            '<script>window.CAMPAIGN_PORTAL_UI=' . $json . ';</script>' .
            '<script src="' . html_escape($js) . '"></script>';

        echo str_ireplace('</body>', $injection . "
</body>", $output);
    }
}
