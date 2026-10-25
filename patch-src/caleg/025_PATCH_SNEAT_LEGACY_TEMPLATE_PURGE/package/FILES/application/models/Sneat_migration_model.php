<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sneat_migration_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Sneat_legacy_cleanup');
    }

    public function status()
    {
        $items = array(
            'Sneat core CSS' => FCPATH . 'assets/sneat/vendor/css/core.css',
            'Sneat theme CSS' => FCPATH . 'assets/sneat/vendor/css/theme-default.css',
            'Sneat menu JS' => FCPATH . 'assets/sneat/vendor/js/menu.js',
            'Sneat main JS' => FCPATH . 'assets/sneat/js/main.js',
            'Boxicons' => FCPATH . 'assets/sneat/vendor/fonts/boxicons.css',
            'Compatibility CSS' => FCPATH . 'assets/sneat/css/umsr-compat.css',
            'Compatibility JS' => FCPATH . 'assets/sneat/js/umsr-compat.js',
            'Sneat hook' => APPPATH . 'hooks/SneatTemplateHook.php',
        );

        $rows = array();

        foreach ($items as $label => $path) {
            $rows[] = array(
                'label' => $label,
                'path' => $path,
                'exists' => is_file($path),
                'size' => is_file($path) ? filesize($path) : 0,
            );
        }

        return $rows;
    }

    public function legacyCandidates()
    {
        return $this->sneat_legacy_cleanup->preview();
    }

    public function cleanup()
    {
        return $this->sneat_legacy_cleanup->execute();
    }

    public function restore()
    {
        return $this->sneat_legacy_cleanup->restore();
    }

    public function cleanupManifest()
    {
        return $this->sneat_legacy_cleanup->manifest();
    }
}
