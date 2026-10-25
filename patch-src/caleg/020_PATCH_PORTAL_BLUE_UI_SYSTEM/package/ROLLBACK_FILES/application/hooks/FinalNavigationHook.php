<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FinalNavigationHook
{
    public function render()
    {
        $CI =& get_instance();
        $output = (string) $CI->output->get_output();

        if (
            !$CI->session->userdata('id') ||
            (string) $CI->session->userdata('hak_akses') !== '1' ||
            stripos($output, '</body>') === false ||
            strpos($output, 'id="sidebar-menu"') === false
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

        $baseUrl = rtrim(base_url(), '/') . '/';
        $eventName = $event ? (string) $event['event_name'] : 'Belum ada event aktif';
        $eventStatus = $event ? (string) $event['status'] : 'SETUP';
        $eventDate = $event && !empty($event['election_date']) ? date('d-m-Y', strtotime($event['election_date'])) : '-';

        $payload = array(
            'base' => $baseUrl,
            'eventName' => $eventName,
            'eventStatus' => $eventStatus,
            'eventDate' => $eventDate,
        );

        $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $injection = <<<'HTML'
<script id="final-navigation-script">
(function () {
    var cfg = __FINAL_NAV_CONFIG__;
    var root = document.getElementById('sidebar-menu');
    if (!root) return;
    var menu = root.querySelector('.nav.side-menu');
    if (!menu) return;
})();
</script>
HTML;
        $injection = str_replace('__FINAL_NAV_CONFIG__', $json, $injection);
        echo str_ireplace('</body>', $injection . "
</body>", $output);
    }
}
