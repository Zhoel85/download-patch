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
<style id="final-navigation-style">
#sidebar-menu.final-navigation-ready .menu_section > h3 { display:none; }
.final-event-context{margin:8px 10px 14px;padding:12px;border-radius:12px;background:linear-gradient(135deg,#1f5f5b,#2f7b75);color:#fff;box-shadow:0 5px 18px rgba(0,0,0,.15)}
.final-event-context .fec-label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;opacity:.75}
.final-event-context .fec-name{font-size:14px;font-weight:700;line-height:1.25;margin:3px 0 7px}
.final-event-context .fec-meta{font-size:11px;opacity:.9}
.final-prod-menu>li>a{border-radius:8px;margin:2px 7px;padding:10px 9px!important}
.final-prod-menu>li>a:hover,.final-prod-menu>li.current-page>a{background:rgba(255,255,255,.08)!important}
.final-prod-menu .final-group.open>a{background:rgba(255,255,255,.06)!important}
.final-prod-menu .final-child{display:none;padding-left:0}
.final-prod-menu .final-group.open>.final-child{display:block}
.final-prod-menu .final-child li a{padding:8px 12px 8px 39px!important;font-size:12px}
.final-prod-menu .final-child li.active a{font-weight:700;color:#fff!important}
.final-prod-menu .final-subtitle{padding:7px 12px 4px 39px;color:#8fa7a5;font-size:10px;text-transform:uppercase;letter-spacing:.08em;pointer-events:none}
.final-prod-menu .final-divider{border-top:1px solid rgba(255,255,255,.08);margin:7px 12px}
.final-prod-menu .fa-chevron-down{float:right;margin-top:4px;font-size:10px}
</style>
<script id="final-navigation-script">
(function () {
    var cfg = __FINAL_NAV_CONFIG__;
    var root = document.getElementById('sidebar-menu');
    if (!root) return;
    var menu = root.querySelector('.nav.side-menu');
    if (!menu) return;

    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
        });
    }
    function url(path) { return cfg.base + String(path).replace(/^\/+/, ''); }
    function item(path, label, icon) {
        return '<li><a href="' + url(path) + '"><i class="fa ' + icon + '"></i> ' + label + '</a></li>';
    }
    function child(path, label) {
        return '<li><a href="' + url(path) + '">' + label + '</a></li>';
    }
    function group(label, icon, children) {
        return '<li class="final-group"><a href="#" class="final-group-toggle"><i class="fa ' + icon + '"></i> ' +
            label + '<span class="fa fa-chevron-down"></span></a><ul class="nav child_menu final-child">' +
            children + '</ul></li>';
    }

    var html = '';
    html += item('admin/Operations360/dashboard', 'Command Center', 'fa-dashboard');
    html += group('Pemilihan & Data', 'fa-bullseye',
        child('admin/ElectionCenter/events', 'Event') +
        child('admin/ElectionCenter/candidates', 'Kandidat & Paslon') +
        child('admin/ElectionCenter/wilayah', 'Wilayah & Dapil') +
        child('admin/ElectionCenter/dpt', 'DPT / Pemilih')
    );
    html += group('Operasional', 'fa-sitemap',
        child('admin/Operations360/organization', 'Organisasi') +
        child('admin/Operations360/members', 'Anggota & Relawan') +
        child('admin/Operations360/activities', 'Kegiatan & Timeline') +
        child('admin/Operations360/map', 'Peta Operasional')
    );
    html += group('Hari H', 'fa-flag-checkered',
        child('admin/Operations360/witnesses', 'Saksi 360') +
        child('admin/ElectionDayCenter', 'Election Day') +
        child('admin/ElectionDayCenter/incidents', 'Incident Center') +
        child('admin/ElectionDayCenter/real_count', 'Real Count') +
        child('admin/ElectionDayCenter/activity', 'Live Activity') +
        child('admin/ElectionDayCenter/big_screen', 'Big Screen')
    );
    html += item('admin/ElectionDayCenter/reports', 'Laporan & Analytics', 'fa-bar-chart');
    html += group('Sistem', 'fa-cogs',
        '<li class="final-subtitle">Akses & Data</li>' +
        child('admin/RolePermissionCenter', 'User, Role & Permission') +
        child('admin/IntegratedDataCenter', 'Import / Export') +
        child('admin/AuditPrivacyCenter', 'Audit & Privasi') +
        '<li class="final-divider"></li><li class="final-subtitle">Advanced</li>' +
        child('admin/DeploymentSafetyCenter', 'Deployment Safety Center') +
        child('admin/DatabaseNormalizationCenter', 'Database Normalization') +
        child('admin/LegacyCompatibility', 'Legacy / Compatibility') +
        '<li class="final-divider"></li>' +
        child('UbahPassword', 'Ubah Password') +
        child('Auth/logout', 'Keluar')
    );

    menu.innerHTML = html;
    menu.classList.add('final-prod-menu');
    root.classList.add('final-navigation-ready');

    var section = menu.closest ? menu.closest('.menu_section') : null;
    if (section && !document.getElementById('final-event-context')) {
        var card = document.createElement('div');
        card.id = 'final-event-context';
        card.className = 'final-event-context';
        card.innerHTML =
            '<div class="fec-label">Event Aktif</div>' +
            '<div class="fec-name">' + esc(cfg.eventName) + '</div>' +
            '<div class="fec-meta">' + esc(cfg.eventStatus) + ' &nbsp;•&nbsp; ' + esc(cfg.eventDate) + '</div>';
        section.insertBefore(card, menu);
    }

    var current = window.location.href.split('?')[0].replace(/\/+$/, '');
    Array.prototype.forEach.call(menu.querySelectorAll('a[href]'), function (a) {
        var href = a.href.split('?')[0].replace(/\/+$/, '');
        if (href && current === href) {
            var li = a.parentNode;
            li.classList.add('active');
            var parent = li.parentNode && li.parentNode.parentNode;
            if (parent && parent.classList.contains('final-group')) parent.classList.add('open');
        }
    });

    Array.prototype.forEach.call(menu.querySelectorAll('.final-group-toggle'), function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var li = a.parentNode;
            li.classList.toggle('open');
        });
    });
})();
</script>
HTML;
        $injection = str_replace('__FINAL_NAV_CONFIG__', $json, $injection);
        echo str_ireplace('</body>', $injection . "
</body>", $output);
    }
}
