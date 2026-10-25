<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SneatTemplateHook
{
    private $CI;
    private $baseUrl;
    private $assetsUrl;
    private $currentUri;

    public function render()
    {
        $this->CI =& get_instance();
        $output = (string) $this->CI->output->get_output();

        if (!$this->isHtmlDocument($output)) {
            echo $output;
            return;
        }

        $this->baseUrl = rtrim(base_url(), '/') . '/';
        $this->assetsUrl = $this->baseUrl . 'assets/sneat/';
        $this->currentUri = strtolower(trim((string) $this->CI->uri->uri_string(), '/'));

        $isAuthenticated = (bool) (
            $this->CI->session->userdata('id') ||
            $this->CI->session->userdata('id_anggota')
        );

        if (!$isAuthenticated && !$this->isAuthRoute()) {
            echo $output;
            return;
        }

        $context = $this->parseLegacyDocument($output);

        if (!$isAuthenticated) {
            echo $this->renderAuth($context);
            return;
        }

        echo $this->renderApplication($context);
    }

    private function isHtmlDocument($output)
    {
        if ($output === '') {
            return false;
        }

        return stripos($output, '<html') !== false &&
            stripos($output, '<body') !== false;
    }

    private function isAuthRoute()
    {
        return $this->currentUri === '' ||
            strpos($this->currentUri, 'auth') === 0 ||
            strpos($this->currentUri, 'login') === 0;
    }

    private function parseLegacyDocument($output)
    {
        $context = array(
            'title' => 'SAR DIGITAL',
            'content' => '',
            'menu' => '',
            'head_assets' => '',
            'body_scripts' => '',
            'user_name' => '',
        );

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $output, $match)) {
            $context['title'] = trim(strip_tags($match[1]));
        }

        $context['head_assets'] = $this->extractHeadAssets($output);
        $context['body_scripts'] = $this->extractBodyScripts($output);

        if (!class_exists('DOMDocument')) {
            $context['content'] = $this->fallbackBodyContent($output);
            return $context;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $html = function_exists('mb_convert_encoding')
            ? mb_convert_encoding($output, 'HTML-ENTITIES', 'UTF-8')
            : $output;

        if (!$dom->loadHTML($html)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $context['content'] = $this->fallbackBodyContent($output);
            return $context;
        }

        $xpath = new DOMXPath($dom);

        $userNodes = $xpath->query(
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' user-profile ')]"
        );
        if ($userNodes && $userNodes->length) {
            $context['user_name'] = $this->cleanText($userNodes->item(0)->textContent);
        }

        $menuNode = $xpath->query("//*[@id='sidebar-menu']")->item(0);
        if ($menuNode) {
            $context['menu'] = $this->convertLegacyMenu($menuNode);
        }

        $contentNode = $xpath->query(
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' right_col ')]"
        )->item(0);

        if (!$contentNode) {
            $contentNode = $xpath->query('//body')->item(0);
        }

        if ($contentNode) {
            $this->removeDescendantsByClass($xpath, $contentNode, 'page-title');
            $this->removeDescendantsByClass($xpath, $contentNode, 'top_nav');
            $this->removeDescendantsByClass($xpath, $contentNode, 'left_col');
            $this->removeDescendantsByTag($xpath, $contentNode, 'footer');
            $this->removeDescendantsByTag($xpath, $contentNode, 'script');

            $context['content'] = $this->innerHtml($contentNode);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (trim($context['content']) === '') {
            $context['content'] = $this->fallbackBodyContent($output);
        }

        return $context;
    }

    private function extractHeadAssets($output)
    {
        $head = '';
        if (preg_match('/<head[^>]*>(.*?)<\/head>/is', $output, $match)) {
            $head = $match[1];
        }

        if ($head === '') {
            return '';
        }

        $assets = array();

        if (preg_match_all('/<link\b[^>]*>/is', $head, $links)) {
            foreach ($links[0] as $tag) {
                if ($this->isLegacyTemplateAsset($tag)) {
                    continue;
                }
                if (stripos($tag, 'stylesheet') !== false) {
                    $assets[] = $tag;
                }
            }
        }

        if (preg_match_all('/<style\b[^>]*>.*?<\/style>/is', $head, $styles)) {
            foreach ($styles[0] as $tag) {
                if (
                    stripos($tag, 'campaign-ui-v2') !== false ||
                    stripos($tag, 'final-navigation') !== false
                ) {
                    continue;
                }
                $assets[] = $tag;
            }
        }

        return implode("\n", $assets);
    }

    private function extractBodyScripts($output)
    {
        $scripts = array();

        if (!preg_match_all('/<script\b[^>]*>.*?<\/script>/is', $output, $matches)) {
            return '';
        }

        foreach ($matches[0] as $tag) {
            if ($this->isLegacyTemplateAsset($tag)) {
                continue;
            }

            if (
                stripos($tag, 'window.CAMPAIGN_PORTAL_UI') !== false ||
                stripos($tag, 'final-navigation') !== false
            ) {
                continue;
            }

            $scripts[] = $tag;
        }

        return implode("\n", array_unique($scripts));
    }

    private function isLegacyTemplateAsset($tag)
    {
        $value = strtolower($tag);

        $needles = array(
            'campaign-portal-v2',
            'build/css/custom',
            'build/js/custom',
            'gentelella',
            'vendors/bootstrap/dist/css/bootstrap',
            'vendors/bootstrap/dist/js/bootstrap',
            'vendors/jquery/dist/jquery',
            'vendors/fastclick',
            'vendors/nprogress',
            'vendors/icheck',
            'finalnavigationhook',
        );

        foreach ($needles as $needle) {
            if (strpos($value, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function fallbackBodyContent($output)
    {
        if (!preg_match('/<body[^>]*>(.*?)<\/body>/is', $output, $match)) {
            return $output;
        }

        $content = $match[1];
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);

        return $content;
    }

    private function removeDescendantsByClass($xpath, $node, $class)
    {
        $nodes = $xpath->query(
            ".//*[contains(concat(' ', normalize-space(@class), ' '), ' " .
                $class .
                " ')]",
            $node
        );

        if (!$nodes) {
            return;
        }

        $items = array();
        foreach ($nodes as $item) {
            $items[] = $item;
        }

        foreach ($items as $item) {
            if ($item->parentNode) {
                $item->parentNode->removeChild($item);
            }
        }
    }

    private function removeDescendantsByTag($xpath, $node, $tag)
    {
        $nodes = $xpath->query('.//' . $tag, $node);

        if (!$nodes) {
            return;
        }

        $items = array();
        foreach ($nodes as $item) {
            $items[] = $item;
        }

        foreach ($items as $item) {
            if ($item->parentNode) {
                $item->parentNode->removeChild($item);
            }
        }
    }

    private function innerHtml($node)
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    private function cleanText($value)
    {
        $value = preg_replace('/\s+/', ' ', trim((string) $value));
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    private function convertLegacyMenu($menuNode)
    {
        if ($this->isSuperAdmin()) {
            return $this->superAdminMenu();
        }

        $html = '';
        $uls = $menuNode->getElementsByTagName('ul');

        if ($uls->length) {
            $root = $uls->item(0);
            foreach ($root->childNodes as $child) {
                if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                    $html .= $this->convertLegacyMenuItem($child);
                }
            }
        }

        if ($html === '') {
            $html =
                $this->menuItem(
                    'Mobile Workspace',
                    'bx-mobile-alt',
                    $this->baseUrl . 'MobileWorkspace'
                ) .
                $this->menuItem(
                    'Keluar',
                    'bx-log-out',
                    $this->baseUrl . 'Auth/logout'
                );
        }

        return $html;
    }

    private function convertLegacyMenuItem($li)
    {
        $anchor = null;
        $childList = null;

        foreach ($li->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }

            if (strtolower($child->tagName) === 'a' && !$anchor) {
                $anchor = $child;
            }

            if (strtolower($child->tagName) === 'ul' && !$childList) {
                $childList = $child;
            }
        }

        if (!$anchor) {
            return '';
        }

        $label = $this->cleanText($anchor->textContent);
        $label = preg_replace('/\s*[›»⌄]+\s*$/u', '', $label);
        $href = trim((string) $anchor->getAttribute('href'));

        if ($label === '') {
            return '';
        }

        $icon = $this->iconForLabel($label);

        if ($childList) {
            $children = '';
            foreach ($childList->childNodes as $child) {
                if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                    $children .= $this->convertLegacyMenuItem($child);
                }
            }

            if ($children === '') {
                return '';
            }

            return '<li class="menu-item ' .
                ($this->menuContainsActive($childList) ? 'active open' : '') .
                '">' .
                '<a href="javascript:void(0);" class="menu-link menu-toggle">' .
                '<i class="menu-icon tf-icons bx ' .
                html_escape($icon) .
                '"></i>' .
                '<div>' .
                html_escape($label) .
                '</div></a>' .
                '<ul class="menu-sub">' .
                $children .
                '</ul></li>';
        }

        if ($href === '' || $href === '#') {
            $href = 'javascript:void(0);';
        }

        return $this->menuItem($label, $icon, $href);
    }

    private function menuContainsActive($node)
    {
        $anchors = $node->getElementsByTagName('a');

        foreach ($anchors as $anchor) {
            if ($this->isHrefActive((string) $anchor->getAttribute('href'))) {
                return true;
            }
        }

        return false;
    }

    private function isHrefActive($href)
    {
        if ($href === '' || $href === '#' || strpos($href, 'javascript:') === 0) {
            return false;
        }

        $path = parse_url($href, PHP_URL_PATH);
        if (!$path) {
            $path = $href;
        }

        $basePath = parse_url($this->baseUrl, PHP_URL_PATH);
        if ($basePath && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        $path = strtolower(trim($path, '/'));

        return $path !== '' &&
            ($this->currentUri === $path ||
                strpos($this->currentUri, $path . '/') === 0);
    }

    private function menuItem($label, $icon, $href)
    {
        return '<li class="menu-item ' .
            ($this->isHrefActive($href) ? 'active' : '') .
            '">' .
            '<a href="' .
            html_escape($href) .
            '" class="menu-link">' .
            '<i class="menu-icon tf-icons bx ' .
            html_escape($icon) .
            '"></i>' .
            '<div>' .
            html_escape($label) .
            '</div></a></li>';
    }

    private function superAdminMenu()
    {
        $groups = array(
            array(
                'label' => 'Command Center',
                'icon' => 'bx-grid-alt',
                'href' => 'admin/Operations360/dashboard',
            ),
            array(
                'label' => 'Pemilihan & Data',
                'icon' => 'bx-target-lock',
                'children' => array(
                    array('Event', 'admin/ElectionCenter/events'),
                    array('Kandidat & Paslon', 'admin/ElectionCenter/candidates'),
                    array('Wilayah & Dapil', 'admin/ElectionCenter/wilayah'),
                    array('DPT / Pemilih', 'admin/ElectionCenter/dpt'),
                ),
            ),
            array(
                'label' => 'Operasional',
                'icon' => 'bx-sitemap',
                'children' => array(
                    array('Organisasi', 'admin/Operations360/organization'),
                    array('Anggota & Relawan', 'admin/Operations360/members'),
                    array('Kegiatan & Timeline', 'admin/Operations360/activities'),
                    array('Peta Operasional', 'admin/Operations360/map'),
                ),
            ),
            array(
                'label' => 'Hari H',
                'icon' => 'bx-flag',
                'children' => array(
                    array('Saksi 360', 'admin/Operations360/witnesses'),
                    array('Election Day', 'admin/ElectionDayCenter'),
                    array('Incident Center', 'admin/ElectionDayCenter/incidents'),
                    array('Real Count', 'admin/ElectionDayCenter/real_count'),
                    array('Live Activity', 'admin/ElectionDayCenter/activity'),
                    array('Big Screen', 'admin/ElectionDayCenter/big_screen'),
                ),
            ),
            array(
                'label' => 'Laporan & Analytics',
                'icon' => 'bx-bar-chart-alt-2',
                'href' => 'admin/ElectionDayCenter/reports',
            ),
            array(
                'label' => 'Sistem',
                'icon' => 'bx-cog',
                'children' => array(
                    array('User, Role & Permission', 'admin/RolePermissionCenter'),
                    array('Import / Export', 'admin/IntegratedDataCenter'),
                    array('Audit & Privasi', 'admin/AuditPrivacyCenter'),
                    array('Deployment Safety Center', 'admin/DeploymentSafetyCenter'),
                    array('Database Normalization', 'admin/DatabaseNormalizationCenter'),
                    array('Legacy / Compatibility', 'admin/LegacyCompatibility'),
                    array('Production Readiness', 'admin/ProductionReadiness'),
                    array('Sneat Migration Center', 'admin/SneatMigrationCenter'),
                ),
            ),
        );

        $html = '';

        foreach ($groups as $group) {
            if (!empty($group['href'])) {
                $html .= $this->menuItem(
                    $group['label'],
                    $group['icon'],
                    $this->baseUrl . $group['href']
                );
                continue;
            }

            $children = '';
            $open = false;

            foreach ($group['children'] as $child) {
                $href = $this->baseUrl . $child[1];
                $active = $this->isHrefActive($href);
                $open = $open || $active;
                $children .= '<li class="menu-item ' .
                    ($active ? 'active' : '') .
                    '"><a href="' .
                    html_escape($href) .
                    '" class="menu-link"><div>' .
                    html_escape($child[0]) .
                    '</div></a></li>';
            }

            $html .= '<li class="menu-item ' .
                ($open ? 'active open' : '') .
                '">' .
                '<a href="javascript:void(0);" class="menu-link menu-toggle">' .
                '<i class="menu-icon tf-icons bx ' .
                html_escape($group['icon']) .
                '"></i>' .
                '<div>' .
                html_escape($group['label']) .
                '</div></a>' .
                '<ul class="menu-sub">' .
                $children .
                '</ul></li>';
        }

        return $html;
    }

    private function iconForLabel($label)
    {
        $label = strtolower($label);

        $map = array(
            'dashboard' => 'bx-home-circle',
            'command' => 'bx-grid-alt',
            'pemilihan' => 'bx-target-lock',
            'event' => 'bx-calendar-event',
            'kandidat' => 'bx-user-pin',
            'wilayah' => 'bx-map',
            'dpt' => 'bx-id-card',
            'organisasi' => 'bx-sitemap',
            'anggota' => 'bx-group',
            'relawan' => 'bx-group',
            'kegiatan' => 'bx-calendar-check',
            'peta' => 'bx-map-alt',
            'saksi' => 'bx-user-check',
            'real count' => 'bx-bar-chart-square',
            'laporan' => 'bx-file',
            'sistem' => 'bx-cog',
            'role' => 'bx-shield-quarter',
            'import' => 'bx-transfer',
            'audit' => 'bx-lock-alt',
            'password' => 'bx-key',
            'keluar' => 'bx-log-out',
        );

        foreach ($map as $needle => $icon) {
            if (strpos($label, $needle) !== false) {
                return $icon;
            }
        }

        return 'bx-circle';
    }

    private function isSuperAdmin()
    {
        return (bool) (
            $this->CI->session->userdata('id') &&
            (string) $this->CI->session->userdata('hak_akses') === '1'
        );
    }

    private function roleLabel()
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin';
        }

        if ($this->CI->session->userdata('id_anggota')) {
            $role = (string) $this->CI->session->userdata('hak_akses');

            $labels = array(
                '1' => 'Kordes',
                '2' => 'Korcam',
                '3' => 'Tim',
                '4' => 'Korkab',
                '9' => 'Relawan',
                '10' => 'Tim',
            );

            return isset($labels[$role]) ? $labels[$role] : 'Tim Lapangan';
        }

        return 'Pengguna';
    }

    private function userName($context)
    {
        $keys = array('nama_lengkap', 'nama', 'username');

        foreach ($keys as $key) {
            $value = trim((string) $this->CI->session->userdata($key));
            if ($value !== '') {
                return $value;
            }
        }

        if (!empty($context['user_name'])) {
            return $context['user_name'];
        }

        return $this->roleLabel();
    }

    private function eventContext()
    {
        $event = array();

        if (isset($this->CI->db) && $this->CI->db->table_exists('election_events')) {
            $event = $this->CI->db
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

        return array(
            'name' => $event && !empty($event['event_name'])
                ? $event['event_name']
                : 'Belum ada event aktif',
            'status' => $event && !empty($event['status'])
                ? $event['status']
                : 'SETUP',
        );
    }

    private function pageMeta()
    {
        $uri = $this->currentUri;

        $routes = array(
            'admin/operations360/dashboard' => array(
                'Command Center',
                'Kondisi event, coverage, kesiapan operasional dan aktivitas terbaru.'
            ),
            'admin/electioncenter/events' => array(
                'Event',
                'Kelola event pemilihan dan konteks data aktif.'
            ),
            'admin/electioncenter/candidates' => array(
                'Kandidat & Paslon',
                'Kelola kandidat, paslon, caleg, partai, nomor urut dan dapil.'
            ),
            'admin/electioncenter/wilayah' => array(
                'Wilayah & Dapil',
                'Kabupaten, dapil, kecamatan, desa/kelurahan dan TPS.'
            ),
            'admin/electioncenter/dpt' => array(
                'DPT / Pemilih',
                'Referensi pemilih per event dengan audit akses.'
            ),
            'admin/operations360/organization' => array(
                'Organisasi',
                'Struktur Korkab sampai Relawan dan coverage wilayah.'
            ),
            'admin/operations360/members' => array(
                'Anggota & Relawan',
                'Identitas tunggal, assignment, referral dan verifikasi.'
            ),
            'admin/operations360/activities' => array(
                'Kegiatan & Timeline',
                'Rencana, jadwal, pelaksanaan, laporan dan tindak lanjut.'
            ),
            'admin/operations360/map' => array(
                'Peta Operasional',
                'Kesiapan struktur, TPS, kegiatan, relawan dan saksi.'
            ),
            'admin/operations360/witnesses' => array(
                'Saksi 360',
                'Penempatan, mandat, check-in, insiden dan hasil TPS.'
            ),
            'admin/electiondaycenter' => array(
                'Election Day',
                'Pantau TPS, check-in, hasil, verifikasi dan insiden.'
            ),
            'admin/electiondaycenter/incidents' => array(
                'Incident Center',
                'Kelola kejadian lapangan sampai selesai.'
            ),
            'admin/electiondaycenter/real_count' => array(
                'Real Count Internal',
                'Hasil internal sementara, validasi dan revision history.'
            ),
            'admin/electiondaycenter/reports' => array(
                'Laporan & Analytics',
                'Rekap operasional sesuai role dan scope wilayah.'
            ),
            'admin/deploymentsafetycenter' => array(
                'Deployment Safety Center',
                'Preflight, backup, dry-run, apply, rollback dan log.'
            ),
            'admin/sneatmigrationcenter' => array(
                'Sneat Migration Center',
                'Status cutover template dan kandidat cleanup legacy.'
            ),
        );

        foreach ($routes as $prefix => $meta) {
            if ($uri === $prefix || strpos($uri, $prefix . '/') === 0) {
                return $meta;
            }
        }

        return array('Dashboard', 'Kelola data dan aktivitas aplikasi.');
    }

    private function renderApplication($context)
    {
        $event = $this->eventContext();
        $meta = $this->pageMeta();
        $menu = $context['menu'] !== '' ? $context['menu'] : $this->superAdminMenu();
        $user = $this->userName($context);
        $role = $this->roleLabel();

        $content = $context['content'];
        $content = preg_replace(
            '/<script\b[^>]*>.*?<\/script>/is',
            '',
            $content
        );

        $headAssets = $context['head_assets'];
        $bodyScripts = $context['body_scripts'];

        return '<!DOCTYPE html>
<html lang="id" class="light-style layout-menu-fixed" dir="ltr"
  data-theme="theme-default"
  data-assets-path="' . html_escape($this->assetsUrl) . '"
  data-template="vertical-menu-template-free">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>' . html_escape($meta[0]) . ' | SAR DIGITAL</title>
  <meta name="description" content="" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'vendor/fonts/boxicons.css') . '" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'vendor/css/core.css') . '" class="template-customizer-core-css" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'vendor/css/theme-default.css') . '" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'css/demo.css') . '" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'vendor/libs/perfect-scrollbar/perfect-scrollbar.css') . '" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'css/umsr-compat.css?v=024') . '" />
  ' . $headAssets . '
  <script src="' . html_escape($this->assetsUrl . 'vendor/js/helpers.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'js/config.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'vendor/libs/jquery/jquery.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'vendor/libs/popper/popper.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'vendor/js/bootstrap.js') . '"></script>
</head>
<body>
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
      <div class="app-brand demo">
        <a href="' . html_escape($this->baseUrl . 'admin/Operations360/dashboard') . '" class="app-brand-link">
          <span class="app-brand-logo demo sneat-sar-logo"><i class="bx bx-shape-circle"></i></span>
          <span class="app-brand-text demo menu-text fw-bolder ms-2">SAR DIGITAL</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
          <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
      </div>
      <div class="menu-inner-shadow"></div>
      <div class="sneat-role-card mx-3 mb-2">
        <small>AKSES LOGIN</small>
        <strong>' . html_escape($role) . '</strong>
        <span>' . html_escape($event['name']) . '</span>
      </div>
      <ul class="menu-inner py-1">' . $menu . '</ul>
    </aside>

    <div class="layout-page">
      <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
          <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
          </a>
        </div>
        <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
          <div class="navbar-nav align-items-center">
            <div class="nav-item d-flex flex-column">
              <strong class="sneat-portal-title">PORTAL PEMENANGAN</strong>
              <small class="text-muted">' . html_escape($event['name']) . '</small>
            </div>
          </div>
          <ul class="navbar-nav flex-row align-items-center ms-auto">
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
              <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                  <span class="avatar-initial rounded-circle bg-label-primary">' . html_escape(strtoupper(substr($user, 0, 1))) . '</span>
                </div>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text"><strong>' . html_escape($user) . '</strong><br><small class="text-muted">' . html_escape($role) . '</small></span></li>
                <li><div class="dropdown-divider"></div></li>
                <li><a class="dropdown-item" href="' . html_escape($this->baseUrl . 'UbahPassword') . '"><i class="bx bx-key me-2"></i> Ubah Password</a></li>
                <li><a class="dropdown-item" href="' . html_escape($this->baseUrl . 'Auth/logout') . '"><i class="bx bx-power-off me-2"></i> Keluar</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>

      <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
              <h4 class="fw-bold mb-1">' . html_escape($meta[0]) . '</h4>
              <p class="text-muted mb-0">' . html_escape($meta[1]) . '</p>
            </div>
            <span class="badge bg-label-primary">' . html_escape($event['status']) . '</span>
          </div>
          <div id="legacy-content-bridge" class="sneat-content-bridge">' . $content . '</div>
        </div>

        <footer class="content-footer footer bg-footer-theme">
          <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">
              © ' . date('Y') . ' SAR DIGITAL ·
              <a href="https://themeselection.com/" target="_blank" rel="noopener" class="footer-link fw-bolder">Sneat by ThemeSelection</a>
            </div>
            <div><span class="footer-link">Portal Pemenangan Multi Event</span></div>
          </div>
        </footer>
        <div class="content-backdrop fade"></div>
      </div>
    </div>
  </div>
  <div class="layout-overlay layout-menu-toggle"></div>
</div>

<script src="' . html_escape($this->assetsUrl . 'vendor/libs/perfect-scrollbar/perfect-scrollbar.js') . '"></script>
<script src="' . html_escape($this->assetsUrl . 'vendor/js/menu.js') . '"></script>
<script src="' . html_escape($this->assetsUrl . 'js/main.js') . '"></script>
<script src="' . html_escape($this->assetsUrl . 'js/umsr-compat.js?v=024') . '"></script>
' . $bodyScripts . '
</body>
</html>';
    }

    private function renderAuth($context)
    {
        $body = preg_replace(
            '/<script\b[^>]*>.*?<\/script>/is',
            '',
            $context['content']
        );

        return '<!DOCTYPE html>
<html lang="id" class="light-style customizer-hide" dir="ltr"
  data-theme="theme-default"
  data-assets-path="' . html_escape($this->assetsUrl) . '">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | SAR DIGITAL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'vendor/fonts/boxicons.css') . '" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'vendor/css/core.css') . '" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'vendor/css/theme-default.css') . '" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'css/demo.css') . '" />
  <link rel="stylesheet" href="' . html_escape($this->assetsUrl . 'css/umsr-compat.css?v=024') . '" />
  ' . $context['head_assets'] . '
  <script src="' . html_escape($this->assetsUrl . 'vendor/js/helpers.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'js/config.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'vendor/libs/jquery/jquery.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'vendor/libs/popper/popper.js') . '"></script>
  <script src="' . html_escape($this->assetsUrl . 'vendor/js/bootstrap.js') . '"></script>
</head>
<body>
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4">
            <span class="app-brand-logo demo sneat-sar-logo"><i class="bx bx-shape-circle"></i></span>
            <span class="app-brand-text demo text-body fw-bolder ms-2">SAR DIGITAL</span>
          </div>
          <div class="sneat-auth-legacy">' . $body . '</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="' . html_escape($this->assetsUrl . 'js/umsr-compat.js?v=024') . '"></script>
' . $context['body_scripts'] . '
</body>
</html>';
    }
}
