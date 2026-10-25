<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sneat_legacy_cleanup
{
    private $CI;
    private $backupRoot;
    private $manifestFile;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->backupRoot = APPPATH . 'backups/sneat_template_cleanup_025/';
        $this->manifestFile = $this->backupRoot . 'manifest.json';
    }

    public function candidates()
    {
        return array(
            array(
                'label' => 'Portal Blue CSS',
                'path' => FCPATH . 'assets/css/campaign-portal-v2.css',
                'relative' => 'public/assets/css/campaign-portal-v2.css',
                'reference' => 'campaign-portal-v2.css',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'Portal Blue JS',
                'path' => FCPATH . 'assets/js/campaign-portal-v2.js',
                'relative' => 'public/assets/js/campaign-portal-v2.js',
                'reference' => 'campaign-portal-v2.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'FinalNavigationHook',
                'path' => APPPATH . 'hooks/FinalNavigationHook.php',
                'relative' => 'application/hooks/FinalNavigationHook.php',
                'reference' => 'FinalNavigationHook',
                'ignore_template_views' => false,
            ),
            array(
                'label' => 'Gentelella custom CSS min',
                'path' => FCPATH . 'build/css/custom.min.css',
                'relative' => 'public/build/css/custom.min.css',
                'reference' => 'build/css/custom.min.css',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'Gentelella custom CSS',
                'path' => FCPATH . 'build/css/custom.css',
                'relative' => 'public/build/css/custom.css',
                'reference' => 'build/css/custom.css',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'Gentelella custom JS min',
                'path' => FCPATH . 'build/js/custom.min.js',
                'relative' => 'public/build/js/custom.min.js',
                'reference' => 'build/js/custom.min.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'Gentelella custom JS',
                'path' => FCPATH . 'build/js/custom.js',
                'relative' => 'public/build/js/custom.js',
                'reference' => 'build/js/custom.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'Bootstrap 3 CSS legacy template',
                'path' => FCPATH . 'vendors/bootstrap/dist/css/bootstrap.min.css',
                'relative' => 'public/vendors/bootstrap/dist/css/bootstrap.min.css',
                'reference' => 'vendors/bootstrap/dist/css/bootstrap.min.css',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'Bootstrap 3 JS legacy template',
                'path' => FCPATH . 'vendors/bootstrap/dist/js/bootstrap.min.js',
                'relative' => 'public/vendors/bootstrap/dist/js/bootstrap.min.js',
                'reference' => 'vendors/bootstrap/dist/js/bootstrap.min.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'jQuery legacy template',
                'path' => FCPATH . 'vendors/jquery/dist/jquery.min.js',
                'relative' => 'public/vendors/jquery/dist/jquery.min.js',
                'reference' => 'vendors/jquery/dist/jquery.min.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'FastClick legacy template',
                'path' => FCPATH . 'vendors/fastclick/lib/fastclick.js',
                'relative' => 'public/vendors/fastclick/lib/fastclick.js',
                'reference' => 'vendors/fastclick/lib/fastclick.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'NProgress CSS legacy template',
                'path' => FCPATH . 'vendors/nprogress/nprogress.css',
                'relative' => 'public/vendors/nprogress/nprogress.css',
                'reference' => 'vendors/nprogress/nprogress.css',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'NProgress JS legacy template',
                'path' => FCPATH . 'vendors/nprogress/nprogress.js',
                'relative' => 'public/vendors/nprogress/nprogress.js',
                'reference' => 'vendors/nprogress/nprogress.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'iCheck JS legacy template',
                'path' => FCPATH . 'vendors/iCheck/icheck.min.js',
                'relative' => 'public/vendors/iCheck/icheck.min.js',
                'reference' => 'vendors/iCheck/icheck.min.js',
                'ignore_template_views' => true,
            ),
            array(
                'label' => 'iCheck Green CSS legacy template',
                'path' => FCPATH . 'vendors/iCheck/skins/flat/green.css',
                'relative' => 'public/vendors/iCheck/skins/flat/green.css',
                'reference' => 'vendors/iCheck/skins/flat/green.css',
                'ignore_template_views' => true,
            ),
        );
    }

    public function preview()
    {
        $rows = array();

        foreach ($this->candidates() as $candidate) {
            $candidate['exists'] = is_file($candidate['path']);
            $candidate['referenced'] = $candidate['exists']
                ? $this->isReferenced(
                    $candidate['reference'],
                    $candidate['ignore_template_views']
                )
                : false;
            $candidate['action'] = !$candidate['exists']
                ? 'NONE'
                : ($candidate['referenced'] ? 'KEEP' : 'BACKUP_AND_REMOVE');
            $rows[] = $candidate;
        }

        return $rows;
    }

    public function execute()
    {
        $this->ensureDirectory($this->backupRoot . 'files/');

        $result = array(
            'created_at' => date('c'),
            'moved' => array(),
            'kept' => array(),
            'missing' => array(),
        );

        foreach ($this->preview() as $candidate) {
            if (!$candidate['exists']) {
                $result['missing'][] = $candidate['relative'];
                continue;
            }

            if ($candidate['referenced']) {
                $result['kept'][] = array(
                    'path' => $candidate['relative'],
                    'reason' => 'still_referenced',
                );
                continue;
            }

            $backupPath = $this->backupRoot .
                'files/' .
                str_replace(array('../', '..\\'), '', $candidate['relative']);

            $this->ensureDirectory(dirname($backupPath));

            if (!@copy($candidate['path'], $backupPath)) {
                $result['kept'][] = array(
                    'path' => $candidate['relative'],
                    'reason' => 'backup_failed',
                );
                continue;
            }

            if (!@unlink($candidate['path'])) {
                @unlink($backupPath);
                $result['kept'][] = array(
                    'path' => $candidate['relative'],
                    'reason' => 'remove_failed',
                );
                continue;
            }

            $result['moved'][] = array(
                'original' => $candidate['path'],
                'relative' => $candidate['relative'],
                'backup' => $backupPath,
            );
        }

        @file_put_contents(
            $this->manifestFile,
            json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $result;
    }

    public function restore()
    {
        if (!is_file($this->manifestFile)) {
            return array(
                'restored' => array(),
                'failed' => array(),
                'message' => 'Manifest cleanup belum tersedia.',
            );
        }

        $manifest = json_decode(
            (string) file_get_contents($this->manifestFile),
            true
        );

        $result = array(
            'restored' => array(),
            'failed' => array(),
            'message' => '',
        );

        foreach ((array) ($manifest['moved'] ?? array()) as $item) {
            if (empty($item['backup']) || empty($item['original'])) {
                continue;
            }

            if (!is_file($item['backup'])) {
                $result['failed'][] = $item['original'];
                continue;
            }

            $this->ensureDirectory(dirname($item['original']));

            if (@copy($item['backup'], $item['original'])) {
                $result['restored'][] = $item['original'];
            } else {
                $result['failed'][] = $item['original'];
            }
        }

        return $result;
    }

    public function manifest()
    {
        if (!is_file($this->manifestFile)) {
            return array();
        }

        $data = json_decode(
            (string) file_get_contents($this->manifestFile),
            true
        );

        return is_array($data) ? $data : array();
    }

    private function isReferenced($needle, $ignoreTemplateViews)
    {
        $roots = array(
            APPPATH . 'controllers',
            APPPATH . 'models',
            APPPATH . 'views',
            APPPATH . 'hooks',
            APPPATH . 'config',
            APPPATH . 'libraries',
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

                $pathname = str_replace('\\', '/', $file->getPathname());

                if (
                    strpos($pathname, '/backups/') !== false ||
                    basename($pathname) === 'Sneat_legacy_cleanup.php'
                ) {
                    continue;
                }

                if (
                    $ignoreTemplateViews &&
                    (
                        strpos($pathname, '/views/templates/') !== false ||
                        strpos($pathname, '/views/login/') !== false
                    )
                ) {
                    continue;
                }

                $extension = strtolower($file->getExtension());
                if (!in_array($extension, array('php', 'js', 'css', 'html'), true)) {
                    continue;
                }

                $content = @file_get_contents($pathname);
                if ($content === false) {
                    continue;
                }

                if (stripos(str_replace('\\', '/', $content), $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ensureDirectory($path)
    {
        if (!is_dir($path)) {
            @mkdir($path, 0750, true);
        }
    }
}
