<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sneat_migration_model extends CI_Model
{
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
        $candidates = array(
            'Portal Blue CSS' => FCPATH . 'assets/css/campaign-portal-v2.css',
            'Portal Blue JS' => FCPATH . 'assets/js/campaign-portal-v2.js',
            'FinalNavigationHook' => APPPATH . 'hooks/FinalNavigationHook.php',
            'Gentelella custom CSS min' => FCPATH . 'build/css/custom.min.css',
            'Gentelella custom CSS' => FCPATH . 'build/css/custom.css',
            'Gentelella custom JS min' => FCPATH . 'build/js/custom.min.js',
            'Gentelella custom JS' => FCPATH . 'build/js/custom.js',
        );

        $rows = array();

        foreach ($candidates as $label => $path) {
            $rows[] = array(
                'label' => $label,
                'path' => $path,
                'exists' => file_exists($path),
                'referenced' => $this->isReferenced(basename($path)),
            );
        }

        return $rows;
    }

    private function isReferenced($needle)
    {
        $roots = array(
            APPPATH . 'controllers',
            APPPATH . 'models',
            APPPATH . 'views',
            APPPATH . 'hooks',
            APPPATH . 'config',
        );

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $extension = strtolower($file->getExtension());
                if (!in_array($extension, array('php', 'js', 'css', 'html'), true)) {
                    continue;
                }

                $content = @file_get_contents($file->getPathname());
                if ($content !== false && stripos($content, $needle) !== false) {
                    if (basename($file->getPathname()) === 'Sneat_migration_model.php') {
                        continue;
                    }
                    return true;
                }
            }
        }

        return false;
    }
}
