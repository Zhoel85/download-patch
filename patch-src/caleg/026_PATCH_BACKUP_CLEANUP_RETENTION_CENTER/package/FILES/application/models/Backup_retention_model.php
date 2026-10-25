<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Backup_retention_model extends CI_Model
{
    private $maxScanFiles = 100000;

    public function defaultPolicy()
    {
        return array(
            'keep_days' => 30,
            'keep_source' => 3,
            'keep_database' => 3,
            'keep_deployment' => 5,
            'keep_baseline' => 1,
            'keep_sneat' => 1,
        );
    }

    public function inventory($policy)
    {
        $items = array();

        foreach ($this->roots() as $root) {
            if (!is_dir($root['path'])) {
                continue;
            }

            $names = @scandir($root['path']);
            if (!is_array($names)) {
                continue;
            }

            foreach ($names as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }

                if (
                    !empty($root['skip']) &&
                    in_array($name, $root['skip'], true)
                ) {
                    continue;
                }

                $full = rtrim($root['path'], DIRECTORY_SEPARATOR) .
                    DIRECTORY_SEPARATOR .
                    $name;

                if (!file_exists($full) && !is_link($full)) {
                    continue;
                }

                $items[] = $this->describeItem($full, $root['label']);
            }
        }

        usort($items, function ($a, $b) {
            return $b['mtime'] <=> $a['mtime'];
        });

        $categoryIndex = array();

        foreach ($items as $idx => $item) {
            $category = $item['category'];
            if (!isset($categoryIndex[$category])) {
                $categoryIndex[$category] = 0;
            }

            $categoryIndex[$category]++;
            $items[$idx]['category_rank'] = $categoryIndex[$category];
        }

        $now = time();
        $total = 0;
        $candidateBytes = 0;
        $candidateCount = 0;
        $protectedCount = 0;

        foreach ($items as $idx => $item) {
            $ageDays = max(0, (int) floor(($now - $item['mtime']) / 86400));
            $reason = $this->protectionReason($item, $policy, $ageDays);
            $recommended = $reason === '';

            $items[$idx]['age_days'] = $ageDays;
            $items[$idx]['protected_reason'] = $reason;
            $items[$idx]['recommended_delete'] = $recommended;

            $total += $item['size_bytes'];

            if ($recommended) {
                $candidateCount++;
                $candidateBytes += $item['size_bytes'];
            } else {
                $protectedCount++;
            }
        }

        return array(
            'items' => $items,
            'total_bytes' => $total,
            'candidate_bytes' => $candidateBytes,
            'candidate_count' => $candidateCount,
            'protected_count' => $protectedCount,
            'roots' => $this->rootStatus(),
        );
    }

    public function cleanup($items, $policy)
    {
        $deleted = 0;
        $failed = 0;
        $freed = 0;
        $detail = array();

        foreach ($items as $item) {
            if (empty($item['recommended_delete'])) {
                continue;
            }

            $path = $item['absolute_path'];

            if (!$this->isSafeTarget($path)) {
                $failed++;
                $detail[] = array(
                    'path' => $item['relative_path'],
                    'status' => 'BLOCKED',
                    'message' => 'Target berada di luar root backup yang diizinkan.',
                );
                continue;
            }

            $ok = $this->deleteTarget($path);

            if ($ok) {
                $deleted++;
                $freed += (int) $item['size_bytes'];
                $detail[] = array(
                    'path' => $item['relative_path'],
                    'status' => 'DELETED',
                    'message' => '',
                );
            } else {
                $failed++;
                $detail[] = array(
                    'path' => $item['relative_path'],
                    'status' => 'FAILED',
                    'message' => 'File/folder tidak dapat dihapus.',
                );
            }
        }

        $this->writeLog(
            $deleted,
            $failed,
            $freed,
            $policy,
            $detail
        );

        return array(
            'deleted' => $deleted,
            'failed' => $failed,
            'freed_bytes' => $freed,
            'detail' => $detail,
        );
    }

    public function recentLogs($limit)
    {
        if (!$this->db->table_exists('backup_retention_logs')) {
            return array();
        }

        return $this->db
            ->order_by('id', 'DESC')
            ->limit(max(1, (int) $limit))
            ->get('backup_retention_logs')
            ->result_array();
    }

    public function formatBytes($bytes)
    {
        $bytes = max(0, (float) $bytes);
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, $i === 0 ? 0 : 2) . ' ' . $units[$i];
    }

    private function roots()
    {
        return array(
            array(
                'label' => 'Deployment',
                'path' => APPPATH . 'backups' . DIRECTORY_SEPARATOR . 'deployment',
                'skip' => array(),
            ),
            array(
                'label' => 'Application Backups',
                'path' => APPPATH . 'backups',
                'skip' => array('deployment'),
            ),
            array(
                'label' => 'Private Backups',
                'path' => APPPATH . 'backups_private',
                'skip' => array(),
            ),
        );
    }

    private function rootStatus()
    {
        $rows = array();

        foreach ($this->roots() as $root) {
            $rows[] = array(
                'label' => $root['label'],
                'path' => $this->relativePath($root['path']),
                'exists' => is_dir($root['path']),
                'writable' => is_dir($root['path']) && is_writable($root['path']),
            );
        }

        return $rows;
    }

    private function describeItem($path, $rootLabel)
    {
        $mtime = @filemtime($path);
        if (!$mtime) {
            $mtime = 0;
        }

        return array(
            'name' => basename($path),
            'root_label' => $rootLabel,
            'absolute_path' => $path,
            'relative_path' => $this->relativePath($path),
            'category' => $this->category($path),
            'is_dir' => is_dir($path) && !is_link($path),
            'is_link' => is_link($path),
            'size_bytes' => $this->itemSize($path),
            'mtime' => (int) $mtime,
        );
    }

    private function category($path)
    {
        $value = strtolower(str_replace('\\', '/', $path));
        $name = strtolower(basename($path));

        if (strpos($value, 'sneat_template_cleanup_025') !== false) {
            return 'sneat';
        }

        if (strpos($name, 'baseline') !== false) {
            return 'baseline';
        }

        if (
            preg_match('/\.(sql|sql\.gz|dump|dump\.gz|gz)$/i', $name) ||
            strpos($name, 'database') !== false ||
            strpos($name, '_db_') !== false
        ) {
            return 'database';
        }

        if (
            preg_match('/\.(zip|tar|tgz|tar\.gz)$/i', $name) ||
            strpos($name, 'source') !== false
        ) {
            return 'source';
        }

        return 'deployment';
    }

    private function protectionReason($item, $policy, $ageDays)
    {
        if (!empty($item['is_link'])) {
            return 'Symlink tidak pernah dihapus otomatis.';
        }

        $lower = strtolower($item['relative_path']);

        if (
            file_exists($item['absolute_path'] . '.keep') ||
            strpos($lower, '/critical/') !== false ||
            strpos($lower, '_critical') !== false
        ) {
            return 'Ditandai sebagai backup kritis.';
        }

        if ($ageDays <= (int) $policy['keep_days']) {
            return 'Masih dalam retention window ' .
                (int) $policy['keep_days'] .
                ' hari.';
        }

        $limits = array(
            'source' => (int) $policy['keep_source'],
            'database' => (int) $policy['keep_database'],
            'deployment' => (int) $policy['keep_deployment'],
            'baseline' => (int) $policy['keep_baseline'],
            'sneat' => (int) $policy['keep_sneat'],
        );

        $category = $item['category'];
        $limit = isset($limits[$category]) ? $limits[$category] : 1;

        if ((int) $item['category_rank'] <= $limit) {
            return 'Termasuk ' .
                $limit .
                ' backup ' .
                $category .
                ' terbaru.';
        }

        return '';
    }

    private function itemSize($path)
    {
        if (is_link($path)) {
            return 0;
        }

        if (is_file($path)) {
            $size = @filesize($path);
            return $size === false ? 0 : (int) $size;
        }

        if (!is_dir($path)) {
            return 0;
        }

        $total = 0;
        $count = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                $count++;

                if ($count > $this->maxScanFiles) {
                    break;
                }

                if ($file->isLink() || !$file->isFile()) {
                    continue;
                }

                $total += (int) $file->getSize();
            }
        } catch (Exception $e) {
            return $total;
        }

        return $total;
    }

    private function isSafeTarget($path)
    {
        if (strpos($path, '..') !== false || is_link($path)) {
            return false;
        }

        $real = realpath($path);
        if ($real === false) {
            return false;
        }

        foreach ($this->roots() as $root) {
            $rootReal = realpath($root['path']);
            if ($rootReal === false) {
                continue;
            }

            $prefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (
                strpos($real . DIRECTORY_SEPARATOR, $prefix) === 0 &&
                $real !== $rootReal
            ) {
                return true;
            }
        }

        return false;
    }

    private function deleteTarget($path)
    {
        if (is_link($path)) {
            return false;
        }

        if (is_file($path)) {
            return @unlink($path);
        }

        if (!is_dir($path)) {
            return false;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    return false;
                }

                $target = $item->getPathname();

                if ($item->isDir()) {
                    if (!@rmdir($target)) {
                        return false;
                    }
                } else {
                    if (!@unlink($target)) {
                        return false;
                    }
                }
            }

            return @rmdir($path);
        } catch (Exception $e) {
            return false;
        }
    }

    private function relativePath($path)
    {
        $app = rtrim(str_replace('\\', '/', APPPATH), '/') . '/';
        $value = str_replace('\\', '/', $path);

        if (strpos($value, $app) === 0) {
            return 'application/' . substr($value, strlen($app));
        }

        return $value;
    }

    private function writeLog($deleted, $failed, $freed, $policy, $detail)
    {
        if (!$this->db->table_exists('backup_retention_logs')) {
            return;
        }

        $this->db->insert('backup_retention_logs', array(
            'user_id' => (int) $this->session->userdata('id'),
            'deleted_count' => (int) $deleted,
            'failed_count' => (int) $failed,
            'freed_bytes' => (string) $freed,
            'policy_json' => json_encode($policy),
            'detail_json' => json_encode($detail),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }
}
