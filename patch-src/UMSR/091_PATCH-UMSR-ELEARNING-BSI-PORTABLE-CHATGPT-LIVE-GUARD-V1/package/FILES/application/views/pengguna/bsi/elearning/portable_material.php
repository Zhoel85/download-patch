<?php
defined('BASEPATH') or exit('No direct script access allowed');

$material = isset($payload['material']) && is_array($payload['material']) ? $payload['material'] : array();
$sources = isset($payload['sources']) && is_array($payload['sources']) ? $payload['sources'] : array();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title) ?> · E-Learning UMS Rappang</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#f5f7fb;color:#263238;line-height:1.65}
        .wrap{max-width:920px;margin:32px auto;padding:0 18px}
        .card{background:#fff;border:1px solid #e8edf5;border-radius:16px;box-shadow:0 8px 28px rgba(31,45,61,.06);overflow:hidden}
        .head{padding:24px 28px;border-bottom:1px solid #edf1f7;background:linear-gradient(180deg,#f8faff,#fff)}
        .body{padding:28px}
        h1{font-size:24px;line-height:1.3;margin:0 0 8px;color:#243b67}
        h2{font-size:17px;color:#243b67;margin:28px 0 10px}
        .muted{color:#6f7d91;font-size:13px}
        .content{white-space:pre-wrap}
        .pill{display:inline-block;padding:4px 9px;border-radius:999px;background:#edf3ff;color:#365fa7;font-size:12px;margin-right:6px}
        ul{padding-left:20px}
        .footer{font-size:12px;color:#7b8798;margin-top:20px;text-align:center}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="head">
            <span class="pill">UMS Rappang</span>
            <span class="pill">Materi E-Learning</span>
            <h1><?= html_escape(isset($material['title']) ? $material['title'] : $title) ?></h1>
            <div class="muted">Konten disiapkan melalui workflow BSI Portable ChatGPT dan tetap berada di bawah kendali kelas/dosen.</div>
        </div>
        <div class="body">
            <?php if (!empty($payload['summary'])) : ?>
                <h2>Ringkasan</h2>
                <div class="content"><?= html_escape($payload['summary']) ?></div>
            <?php endif; ?>

            <h2>Modul Pembelajaran</h2>
            <div class="content"><?= html_escape(isset($material['description']) ? $material['description'] : '') ?></div>

            <?php if (!empty($payload['student_activity'])) : ?>
                <h2>Aktivitas Mahasiswa</h2>
                <div class="content"><?= html_escape($payload['student_activity']) ?></div>
            <?php endif; ?>

            <?php if (!empty($sources)) : ?>
                <h2>Sumber Belajar</h2>
                <ul>
                    <?php foreach ($sources as $source) : ?>
                        <li><?= html_escape(is_scalar($source) ? (string)$source : json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer">Portal E-Learning · Universitas Muhammadiyah Sidenreng Rappang</div>
</div>
</body>
</html>
