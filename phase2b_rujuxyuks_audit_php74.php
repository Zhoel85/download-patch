<?php
declare(strict_types=1);

@set_time_limit(180);
@ini_set('memory_limit', '256M');
@ini_set('display_errors', '0');

define('ACCESS_KEY', 'uA4gLm8cMT1pVISadtY_8jsQ');
define('MAX_FILES', 100000);

if (!isset($_GET['key']) || !hash_equals(ACCESS_KEY, (string) $_GET['key'])) {
    http_response_code(403);
    exit('403 Forbidden');
}

$root = '/home/u278918108/domains/mydokumen.space/public_html/RujUxYuks';
$real = realpath($root);

if ($real === false || !is_dir($real)) {
    http_response_code(500);
    exit('RujUxYuks not found');
}

$dangerExt = array(
    'php','php3','php4','php5','php7','php8','phtml','phar',
    'cgi','pl','py','sh','bash','exe','dll','so','bin','com','scr','htaccess'
);

$counts = array();
$suspicious = array();
$files = 0;
$errors = array();

try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($real, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $files++;

        if ($files > MAX_FILES) {
            $errors[] = 'file_limit_reached';
            break;
        }

        $path = $fileInfo->getPathname();
        $name = $fileInfo->getFilename();

        if ($name === '.htaccess') {
            $ext = 'htaccess';
        } else {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === '') {
                $ext = '[no_ext]';
            }
        }

        if (!isset($counts[$ext])) {
            $counts[$ext] = 0;
        }
        $counts[$ext]++;

        $isDanger = in_array($ext, $dangerExt, true);
        $isHidden = (substr($name, 0, 1) === '.');

        if ($isDanger || $isHidden) {
            $hash = @hash_file('sha256', $path);

            $suspicious[] = array(
                'path' => $path,
                'name' => $name,
                'ext' => $ext,
                'size' => @filesize($path),
                'mtime' => @date('Y-m-d H:i:s O', @filemtime($path)),
                'perm' => substr(sprintf('%o', @fileperms($path)), -4),
                'sha256' => ($hash !== false ? $hash : 'HASH_ERROR')
            );
        }
    }
} catch (Throwable $e) {
    $errors[] = get_class($e) . ': ' . $e->getMessage();
}

ksort($counts);

$txt  = "PHASE 2B - RUJUXYUKS PUBLIC ASSET AUDIT PHP 7.4\n";
$txt .= "ROOT: " . $real . "\n";
$txt .= "FILES SEEN: " . $files . "\n";
$txt .= "SUSPICIOUS/EXECUTABLE: " . count($suspicious) . "\n";
$txt .= "ERRORS: " . (count($errors) ? implode(' | ', $errors) : 'NONE') . "\n";
$txt .= str_repeat('=', 78) . "\n\n";

$txt .= "EXTENSION COUNTS\n";
$txt .= str_repeat('-', 78) . "\n";
foreach ($counts as $ext => $count) {
    $txt .= str_pad($ext, 18) . " : " . $count . "\n";
}

$txt .= "\nSUSPICIOUS / EXECUTABLE FILES\n";
$txt .= str_repeat('-', 78) . "\n";

foreach ($suspicious as $item) {
    $txt .= "PATH: " . $item['path'] . "\n";
    $txt .= "EXT: " . $item['ext'] . "\n";
    $txt .= "SIZE: " . $item['size'] . "\n";
    $txt .= "MTIME: " . $item['mtime'] . "\n";
    $txt .= "PERM: " . $item['perm'] . "\n";
    $txt .= "SHA256: " . $item['sha256'] . "\n";
    $txt .= str_repeat('-', 78) . "\n";
}

if (isset($_GET['download'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="PHASE2B_RUJUxYUKS_AUDIT_PHP74_' . date('Ymd_His') . '.txt"');
    echo $txt;
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Phase 2B RujUxYuks Audit PHP 7.4</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;margin:24px}
.card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px}
table{border-collapse:collapse;width:100%;font-size:13px;margin-top:12px}
th,td{border:1px solid #ddd;padding:7px;text-align:left}
th{background:#f1f1f1}
.ok{color:#137333;font-weight:bold}
.bad{color:#b42318;font-weight:bold}
.btn{display:inline-block;padding:8px 12px;border:1px solid #777;border-radius:5px;text-decoration:none;color:#222}
</style>
</head>
<body>
<div class="card">
<h2>Phase 2B — Audit RujUxYuks (PHP 7.4)</h2>
<p>Files seen: <b><?php echo htmlspecialchars((string)$files, ENT_QUOTES, 'UTF-8'); ?></b></p>
<p>Suspicious / executable:
<b class="<?php echo count($suspicious) ? 'bad' : 'ok'; ?>">
<?php echo count($suspicious); ?>
</b></p>
<p>Errors: <?php echo htmlspecialchars(count($errors) ? implode(' | ', $errors) : 'NONE', ENT_QUOTES, 'UTF-8'); ?></p>

<a class="btn" href="?key=<?php echo urlencode(ACCESS_KEY); ?>&download=1">Download TXT Report</a>

<h3>Extension Counts</h3>
<table>
<tr><th>Extension</th><th>Count</th></tr>
<?php foreach ($counts as $ext => $count): ?>
<tr>
<td><?php echo htmlspecialchars($ext, ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo (int)$count; ?></td>
</tr>
<?php endforeach; ?>
</table>

<h3>Suspicious / Executable Files</h3>
<table>
<tr><th>Path</th><th>Ext</th><th>Size</th><th>SHA256</th></tr>
<?php foreach ($suspicious as $item): ?>
<tr>
<td><?php echo htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($item['ext'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars((string)$item['size'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($item['sha256'], ENT_QUOTES, 'UTF-8'); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
