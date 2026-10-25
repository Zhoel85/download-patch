<div class="col-md-12 col-sm-12">
<div class="x_panel">
<div class="x_title"><h2>Production Readiness <small>Final GO / NO-GO Gate</small></h2><div class="clearfix"></div></div>
<div class="x_content">
<?php
$status = $audit['status'];
$cls = $status === 'GO' ? 'alert-success' : ($status === 'NO-GO' ? 'alert-danger' : 'alert-warning');
?>
<div class="alert <?= $cls; ?>">
    <h3 style="margin-top:0;"><?= html_escape($status); ?></h3>
    <strong>Block:</strong> <?= (int)$audit['block_count']; ?>
    &nbsp; | &nbsp;
    <strong>Warning:</strong> <?= (int)$audit['warn_count']; ?>
    &nbsp; | &nbsp;
    <strong>Generated:</strong> <?= html_escape($audit['generated_at']); ?>
</div>

<?php if ($status === 'NO-GO') : ?>
<div class="alert alert-danger">
    Produksi belum boleh dibuka. Selesaikan seluruh pemeriksaan berstatus <strong>BLOCK</strong>,
    lalu muat ulang halaman ini.
</div>
<?php elseif ($status === 'CONDITIONAL') : ?>
<div class="alert alert-warning">
    Tidak ada blocker teknis, tetapi warning masih perlu ditinjau dan dicatat pada checklist produksi.
</div>
<?php else : ?>
<div class="alert alert-success">
    Gate otomatis lulus. Tetap wajib menyelesaikan UAT manual, backup/restore test, rollback test,
    mobile UAT dan Big Screen UAT pada dokumen patch 019.
</div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead><tr><th style="width:90px;">Status</th><th>Pemeriksaan</th><th>Detail</th></tr></thead>
<tbody>
<?php foreach ($audit['checks'] as $row) : ?>
<?php
$label = $row['level'] === 'PASS' ? 'label-success' : ($row['level'] === 'BLOCK' ? 'label-danger' : 'label-warning');
?>
<tr>
<td><span class="label <?= $label; ?>"><?= html_escape($row['level']); ?></span></td>
<td><strong><?= html_escape($row['name']); ?></strong></td>
<td><?= html_escape($row['detail']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="alert alert-info">
<strong>Catatan:</strong> status GO pada halaman ini adalah gate otomatis.
Go-live final tetap membutuhkan UAT per role, concurrency test, mobile/PWA, Big Screen,
backup/restore, dan rollback test sesuai DOCS patch 019.
</div>
</div></div></div>
