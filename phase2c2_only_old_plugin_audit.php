<?php
declare(strict_types=1);

@set_time_limit(240);
@ini_set('memory_limit','512M');
@ini_set('display_errors','0');

define('ACCESS_KEY','P2C2_UMSR_20261015_R8v4Lm2N');

if (!isset($_GET['key']) || !hash_equals(ACCESS_KEY,(string)$_GET['key'])) {
    http_response_code(403);
    exit('403 Forbidden');
}

$root='/home/u278918108/domains/mydokumen.space/public_html/plugins';

$targets=array(
'blocks/ojs3-keywordcloud-plugin',
'generic/backup',
'generic/browseBySection',
'generic/customHeader',
'generic/mostRead',
'generic/translator',
'importexport/quickSubmit',
'themes/bootstrap3',
'themes/classic',
'themes/default-child',
'themes/defaultManuscript',
'themes/healthSciences',
'themes/oldGregg'
);

$known=array(
'325ac1929d3bb3a2882ca725e0029c38849b306cc127de2214e7d8a61b69d74b',
'2fd7e6d8f987b243ab1839249551f62adce19704c47d3d0c8dd9e57ea5b9c6b3',
'5b6af2e2ce89956928535043a5aa6de00bec546b949b66f0fee28120955fc208',
'43a66916abea3180563cf0c39fa131ac7af398569a561c2c89e20c64e581f2c9',
'd23d8912d2c2f5005cff6071141353a155ec6043a43ddcf8a9a47e8c6ebada01',
'f77b492aeeadc5b29e706f67239f3577b655ec67c2280fd9de8481cc17bd70e3',
'05432134c5007059c9a9f672b8463d78705f075ca587263209ee39a79c72bfb0',
'47d4ff3a5e54aee590dd968ff29d94040d910e4ed7fa2216e2e75b80a66c4f73',
'9f12dec240ace528d013ce10aacfcb3bc6dc1df1ff3f15d7de7df42202c3cd1c',
'd1179dd67fcff456b56cd0cfbe484cbf692043123b59987d2f9e8cb069a38190',
'a9b64429701b3670453db3e2a7dcef4e858bf9fae69030708f8826724d690f1a',
'653059a31f870937373fbbc7c5e5946ec7d1ebf5061857f5269e444823ded0ad',
'763e84c61986af314415ed00a39f463ae7c0bc8ac4922dd50c75cfab649f4815'
);

function is_elf($p){
    $h=@fopen($p,'rb');
    if(!$h)return false;
    $b=@fread($h,4);
    @fclose($h);
    return $b==="\x7fELF";
}

function plugin_version($base){
    $candidates=array($base.'/version.xml',$base.'/version.xml.inc',$base.'/locale/en_US/locale.xml');
    foreach($candidates as $p){
        if(is_file($p)){
            $txt=@file_get_contents($p);
            if($txt!==false){
                if(preg_match('/<release>([^<]+)<\/release>/i',$txt,$m)) return trim($m[1]);
                if(preg_match('/<version>([^<]+)<\/version>/i',$txt,$m)) return trim($m[1]);
            }
        }
    }
    return '';
}

$report=array();

foreach($targets as $rel){
    $base=$root.'/'.$rel;
    $row=array(
        'plugin'=>$rel,'exists'=>is_dir($base)?'YES':'NO','version'=>'',
        'files'=>0,'php'=>0,'hidden'=>0,'htaccess'=>0,'elf'=>0,'ioc'=>0,'flags'=>array()
    );

    if(!is_dir($base)){
        $report[]=$row;
        continue;
    }

    $row['version']=plugin_version($base);

    try{
        $it=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach($it as $f){
            if(!$f->isFile())continue;

            $row['files']++;
            $p=$f->getPathname();
            $name=$f->getFilename();
            $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));

            if($ext==='php'||$ext==='phtml'||preg_match('/^php[0-9]?$/',$ext)) $row['php']++;

            if(substr($name,0,1)==='.') {
                $row['hidden']++;
                $row['flags'][]='HIDDEN: '.$p;
            }

            if($name==='.htaccess'){
                $row['htaccess']++;
                $row['flags'][]='HTACCESS: '.$p;
            }

            if(is_elf($p)){
                $row['elf']++;
                $row['flags'][]='ELF: '.$p;
            }

            $hash=@hash_file('sha256',$p);
            if($hash!==false && in_array($hash,$known,true)){
                $row['ioc']++;
                $row['flags'][]='EXACT_IOC: '.$p.' SHA256='.$hash;
            }

            if(preg_match('/(^|[^a-z])(shell|wso|cmd|backdoor|bypass|mailer|upload|eval|adminer)([^a-z]|$)/i',$name)){
                $row['flags'][]='SUSPICIOUS_NAME: '.$p;
            }
        }
    }catch(Throwable $e){
        $row['flags'][]='SCAN_ERROR: '.$e->getMessage();
    }

    $report[]=$row;
}

$txt="PHASE 2C-2 - ONLY_OLD PLUGIN/THEME SECURITY AUDIT\n";
$txt.="ROOT: ".$root."\n";
$txt.=str_repeat('=',80)."\n";

foreach($report as $r){
    $txt.="PLUGIN: ".$r['plugin']."\n";
    $txt.="EXISTS: ".$r['exists']."\n";
    $txt.="VERSION: ".($r['version']!==''?$r['version']:'UNKNOWN')."\n";
    $txt.="FILES: ".$r['files']."\n";
    $txt.="PHP_FILES: ".$r['php']."\n";
    $txt.="HIDDEN: ".$r['hidden']."\n";
    $txt.="HTACCESS: ".$r['htaccess']."\n";
    $txt.="ELF: ".$r['elf']."\n";
    $txt.="EXACT_IOC_MATCHES: ".$r['ioc']."\n";
    foreach($r['flags'] as $f){$txt.="  ".$f."\n";}
    $txt.=str_repeat('-',80)."\n";
}

if(isset($_GET['download'])){
    header('Content-Type:text/plain;charset=UTF-8');
    header('Content-Disposition:attachment; filename="PHASE2C2_ONLY_OLD_AUDIT_'.date('Ymd_His').'.txt"');
    echo $txt;
    exit;
}

header('Content-Type:text/html;charset=UTF-8');
?>
<!doctype html><html><head><meta charset="utf-8"><title>Phase 2C-2 ONLY_OLD Audit</title>
<style>
body{font-family:Arial;background:#f6f7f9;margin:24px}
.card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px}
table{border-collapse:collapse;width:100%;font-size:12px}
th,td{border:1px solid #ddd;padding:7px;text-align:left}
th{background:#eee}.bad{color:#b42318;font-weight:bold}.ok{color:#137333;font-weight:bold}
.btn{display:inline-block;padding:8px 12px;border:1px solid #777;border-radius:5px;text-decoration:none;color:#222;margin-bottom:14px}
</style></head><body><div class="card">
<h2>Phase 2C-2 — ONLY_OLD Plugin/Theme Audit</h2>
<a class="btn" href="?key=<?php echo urlencode(ACCESS_KEY); ?>&download=1">Download TXT Report</a>
<table><tr><th>Plugin</th><th>Version</th><th>Files</th><th>PHP</th><th>Hidden</th><th>.htaccess</th><th>ELF</th><th>Exact IOC</th></tr>
<?php foreach($report as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['plugin'],ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['version']!==''?$r['version']:'UNKNOWN',ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo (int)$r['files']; ?></td>
<td><?php echo (int)$r['php']; ?></td>
<td><?php echo (int)$r['hidden']; ?></td>
<td><?php echo (int)$r['htaccess']; ?></td>
<td class="<?php echo $r['elf']?'bad':'ok'; ?>"><?php echo (int)$r['elf']; ?></td>
<td class="<?php echo $r['ioc']?'bad':'ok'; ?>"><?php echo (int)$r['ioc']; ?></td>
</tr>
<?php endforeach; ?>
</table></div></body></html>