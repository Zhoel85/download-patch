<div class="col-md-12 col-sm-12">
<div class="x_panel">
<div class="x_title"><h2>Legacy / Compatibility <small>Dependency Monitor & Freeze</small></h2><div class="clearfix"></div></div>
<div class="x_content">
<?php if ($this->session->flashdata('success')) : ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')) : ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

<div class="alert alert-info">
<strong>Safety policy:</strong> belum ada tabel bisnis/controller legacy yang dihapus.
Saksi dan Real Count lama dibekukan untuk <strong>WRITE</strong>.
Jalur lain tetap <strong>MONITOR</strong> sampai dependency menunjukkan aman.
</div>

<h4>Dependency 7 Hari Terakhir</h4>
<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead><tr><th>Resource</th><th>Pengganti</th><th>Mode</th><th>READ</th><th>WRITE</th><th>Akses Terakhir</th><th>Cleanup</th><th>Kontrol</th></tr></thead>
<tbody>
<?php foreach ($stats7 as $row) : ?>
<?php
$mode = strtoupper((string)$row['mode']);
$modeClass = $mode === 'READ_ONLY' ? 'label-warning' : ($mode === 'DISABLED' ? 'label-danger' : 'label-info');
$safeToggle = in_array($row['resource_code'], array('LEGACY_REAL_COUNT','LEGACY_SAKSI'), TRUE);
?>
<tr>
<td><code><?= html_escape($row['resource_code']); ?></code></td>
<td><?= html_escape($row['replacement_module']); ?></td>
<td><span class="label <?= $modeClass; ?>"><?= html_escape($mode); ?></span></td>
<td><?= (int)$row['read_count']; ?></td>
<td><?= (int)$row['write_count']; ?></td>
<td><?= html_escape($row['last_access'] ?: '-'); ?></td>
<td><?= html_escape($row['cleanup_status']); ?></td>
<td>
<?php if ($safeToggle) : ?>
<?= form_open('admin/LegacyCompatibility/set_mode', array('style'=>'display:inline-block')); ?>
<input type="hidden" name="resource_code" value="<?= html_escape($row['resource_code']); ?>">
<input type="hidden" name="mode" value="<?= $mode === 'READ_ONLY' ? 'MONITOR' : 'READ_ONLY'; ?>">
<button type="submit" class="btn btn-xs <?= $mode === 'READ_ONLY' ? 'btn-default' : 'btn-warning'; ?>">
<?= $mode === 'READ_ONLY' ? 'Kembali Monitor' : 'Jadikan Read-only'; ?>
</button>
<?= form_close(); ?>
<?php else : ?><span class="text-muted">Monitor-only pada 018</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<hr><h4>Jalur Legacy Manual</h4>
<div class="table-responsive"><table class="table table-bordered">
<thead><tr><th>Legacy</th><th>Pengganti</th><th>Akses</th></tr></thead><tbody>
<?php foreach ($legacy_links as $row) : ?><tr>
<td><?= html_escape($row['label']); ?></td><td><?= html_escape($row['replacement']); ?></td>
<td><a class="btn btn-xs btn-default" href="<?= base_url($row['route']); ?>">Buka Legacy</a></td>
</tr><?php endforeach; ?>
</tbody></table></div>

<hr><h4>100 Aktivitas Legacy Terbaru</h4>
<div class="table-responsive"><table class="table table-condensed table-striped">
<thead><tr><th>Waktu</th><th>Resource</th><th>Akses</th><th>Method</th><th>Actor</th><th>Route</th></tr></thead><tbody>
<?php foreach ($recent as $row) : ?><tr>
<td><?= html_escape($row['created_at']); ?></td>
<td><?= html_escape($row['resource_code']); ?></td>
<td><?= html_escape($row['access_type']); ?></td>
<td><?= html_escape($row['http_method']); ?></td>
<td><?= html_escape($row['actor_type'].':'.$row['actor_id']); ?></td>
<td><code><?= html_escape($row['request_uri']); ?></code></td>
</tr><?php endforeach; ?>
</tbody></table></div>

<div class="alert alert-warning"><strong>Aturan penghapusan:</strong> baru hapus setelah WRITE=0 dan required READ=0 selama masa observasi, canonical UAT lulus, dan backup/restore teruji.</div>
</div></div></div>
