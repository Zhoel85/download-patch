<?php
declare(strict_types=1);

@set_time_limit(240);
@ini_set('memory_limit','512M');
@ini_set('display_errors','0');

define('ACCESS_KEY','P2C_UMSR_20261015_K7m2Qx9V');

if (!isset($_GET['key']) || !hash_equals(ACCESS_KEY,(string)$_GET['key'])) {
    http_response_code(403);
    exit('403 Forbidden');
}

$oldRoot   = '/home/u278918108/domains/mydokumen.space/public_html/plugins';
$cleanRoot = '/home/u278918108/domains/mydokumen.space/public_html_clean_ojs/plugins';

if (!is_dir($oldRoot) || !is_dir($cleanRoot)) {
    http_response_code(500);
    exit('Plugin root missing. Check old/staging paths.');
}

function norm_path($p) {
    return str_replace('\\','/',$p);
}

function plugin_dirs($root) {
    $out = array();
    $cats = @scandir($root);
    if ($cats === false) return $out;

    foreach ($cats as $cat) {
        if ($cat === '.' || $cat === '..') continue;
        $catPath = $root . '/' . $cat;
        if (!is_dir($catPath)) continue;

        $items = @scandir($catPath);
        if ($items === false) continue;

        foreach ($items as $name) {
            if ($name === '.' || $name === '..') continue;
            $p = $catPath . '/' . $name;
            if (is_dir($p)) {
                $key = $cat . '/' . $name;
                $out[$key] = $p;
            }
        }
    }

    ksort($out);
    return $out;
}

function file_map($base) {
    $out = array();
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $f) {
            if (!$f->isFile() || $f->isLink()) continue;

            $full = norm_path($f->getPathname());
            $prefix = rtrim(norm_path($base),'/') . '/';

            if (strpos($full,$prefix) !== 0) continue;

            $rel = substr($full,strlen($prefix));
            $size = $f->getSize();
            $hash = @hash_file('sha256',$f->getPathname());

            $out[$rel] = array(
                'size' => $size,
                'hash' => ($hash !== false ? $hash : 'HASH_ERROR')
            );
        }
    } catch (Throwable $e) {
        $out['__SCAN_ERROR__'] = array('size'=>0,'hash'=>$e->getMessage());
    }

    ksort($out);
    return $out;
}

$oldPlugins = plugin_dirs($oldRoot);
$cleanPlugins = plugin_dirs($cleanRoot);

$all = array_unique(array_merge(array_keys($oldPlugins), array_keys($cleanPlugins)));
sort($all,SORT_NATURAL|SORT_FLAG_CASE);

$rows = array();
$details = array();

foreach ($all as $plugin) {
    $oldExists = isset($oldPlugins[$plugin]);
    $cleanExists = isset($cleanPlugins[$plugin]);

    if ($oldExists && !$cleanExists) {
        $rows[] = array(
            'plugin'=>$plugin,
            'status'=>'ONLY_OLD',
            'old_files'=>'-',
            'clean_files'=>'-',
            'changed'=>'-',
            'added'=>'-',
            'missing'=>'-'
        );
        continue;
    }

    if (!$oldExists && $cleanExists) {
        $rows[] = array(
            'plugin'=>$plugin,
            'status'=>'ONLY_CLEAN',
            'old_files'=>'-',
            'clean_files'=>'-',
            'changed'=>'-',
            'added'=>'-',
            'missing'=>'-'
        );
        continue;
    }

    $oldMap = file_map($oldPlugins[$plugin]);
    $cleanMap = file_map($cleanPlugins[$plugin]);

    $changed = array();
    $added = array();
    $missing = array();

    foreach ($oldMap as $rel=>$meta) {
        if ($rel === '__SCAN_ERROR__') continue;

        if (!isset($cleanMap[$rel])) {
            $added[] = $rel;
            continue;
        }

        if ($meta['hash'] !== $cleanMap[$rel]['hash']) {
            $changed[] = $rel;
        }
    }

    foreach ($cleanMap as $rel=>$meta) {
        if ($rel === '__SCAN_ERROR__') continue;
        if (!isset($oldMap[$rel])) {
            $missing[] = $rel;
        }
    }

    $status = (!count($changed) && !count($added) && !count($missing)) ? 'MATCH' : 'DIFF';

    $rows[] = array(
        'plugin'=>$plugin,
        'status'=>$status,
        'old_files'=>count($oldMap),
        'clean_files'=>count($cleanMap),
        'changed'=>count($changed),
        'added'=>count($added),
        'missing'=>count($missing)
    );

    if ($status === 'DIFF') {
        $details[$plugin] = array(
            'changed'=>$changed,
            'added'=>$added,
            'missing'=>$missing
        );
    }
}

$txt  = "PHASE 2C - OJS PLUGIN/THEME BASELINE COMPARISON\n";
$txt .= "OLD:   " . $oldRoot . "\n";
$txt .= "CLEAN: " . $cleanRoot . "\n";
$txt .= "PLUGIN DIRS OLD: " . count($oldPlugins) . "\n";
$txt .= "PLUGIN DIRS CLEAN: " . count($cleanPlugins) . "\n";
$txt .= str_repeat('=',80) . "\n\n";

foreach ($rows as $r) {
    $txt .= "PLUGIN: " . $r['plugin'] . "\n";
    $txt .= "STATUS: " . $r['status'] . "\n";
    $txt .= "OLD_FILES: " . $r['old_files'] . "\n";
    $txt .= "CLEAN_FILES: " . $r['clean_files'] . "\n";
    $txt .= "CHANGED: " . $r['changed'] . "\n";
    $txt .= "ADDED_IN_OLD: " . $r['added'] . "\n";
    $txt .= "MISSING_FROM_OLD: " . $r['missing'] . "\n";

    if (isset($details[$r['plugin']])) {
        foreach ($details[$r['plugin']]['changed'] as $f) {
            $txt .= "  CHANGED_FILE: " . $f . "\n";
        }
        foreach ($details[$r['plugin']]['added'] as $f) {
            $txt .= "  ADDED_FILE: " . $f . "\n";
        }
        foreach ($details[$r['plugin']]['missing'] as $f) {
            $txt .= "  MISSING_FILE: " . $f . "\n";
        }
    }

    $txt .= str_repeat('-',80) . "\n";
}

if (isset($_GET['download'])) {
    header('Content-Type:text/plain;charset=UTF-8');
    header('Content-Disposition:attachment; filename="PHASE2C_PLUGIN_THEME_AUDIT_' . date('Ymd_His') . '.txt"');
    echo $txt;
    exit;
}

header('Content-Type:text/html;charset=UTF-8');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Phase 2C Plugin/Theme Audit</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;margin:24px}
.card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px}
table{border-collapse:collapse;width:100%;font-size:12px}
th,td{border:1px solid #ddd;padding:6px;text-align:left}
th{background:#eee}
.ok{color:#137333;font-weight:bold}
.bad{color:#b42318;font-weight:bold}
.warn{color:#a15c00;font-weight:bold}
.btn{display:inline-block;padding:8px 12px;border:1px solid #777;border-radius:5px;text-decoration:none;color:#222;margin-bottom:14px}
</style>
</head>
<body>
<div class="card">
<h2>Phase 2C — OJS Plugin / Theme Baseline Audit</h2>
<p>Old plugin dirs: <b><?php echo count($oldPlugins); ?></b></p>
<p>Clean plugin dirs: <b><?php echo count($cleanPlugins); ?></b></p>
<a class="btn" href="?key=<?php echo urlencode(ACCESS_KEY); ?>&download=1">Download TXT Report</a>

<table>
<tr>
<th>Plugin / Theme</th>
<th>Status</th>
<th>Old Files</th>
<th>Clean Files</th>
<th>Changed</th>
<th>Added in Old</th>
<th>Missing from Old</th>
</tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['plugin'],ENT_QUOTES,'UTF-8'); ?></td>
<td class="<?php echo $r['status']==='MATCH'?'ok':($r['status']==='DIFF'?'bad':'warn'); ?>">
<?php echo htmlspecialchars($r['status'],ENT_QUOTES,'UTF-8'); ?>
</td>
<td><?php echo htmlspecialchars((string)$r['old_files'],ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo htmlspecialchars((string)$r['clean_files'],ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo htmlspecialchars((string)$r['changed'],ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo htmlspecialchars((string)$r['added'],ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo htmlspecialchars((string)$r['missing'],ENT_QUOTES,'UTF-8'); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
