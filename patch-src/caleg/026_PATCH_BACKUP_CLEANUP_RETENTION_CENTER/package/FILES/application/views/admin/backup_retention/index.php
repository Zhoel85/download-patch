<?php
$fmt = function ($bytes) {
    $bytes = max(0, (float) $bytes);
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return number_format($bytes, $i === 0 ? 0 : 2) . ' ' . $units[$i];
};

$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
?>

<?php if ($this->session->flashdata('backup_retention_success')): ?>
<div class="alert alert-success alert-dismissible" role="alert">
  <?= html_escape($this->session->flashdata('backup_retention_success')); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($this->session->flashdata('backup_retention_error')): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
  <?= html_escape($this->session->flashdata('backup_retention_error')); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-muted small">Total Backup</span>
        <h4 class="mb-0 mt-2"><?= html_escape($fmt($report['total_bytes'])); ?></h4>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-muted small">Kandidat Cleanup</span>
        <h4 class="mb-0 mt-2"><?= (int) $report['candidate_count']; ?> item</h4>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-muted small">Estimasi Ruang</span>
        <h4 class="mb-0 mt-2 text-success"><?= html_escape($fmt($report['candidate_bytes'])); ?></h4>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-muted small">Dilindungi</span>
        <h4 class="mb-0 mt-2"><?= (int) $report['protected_count']; ?> item</h4>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-1">Retention Policy</h5>
    <small class="text-muted">Preview tidak menghapus file. Apply selalu menghitung ulang kandidat di server.</small>
  </div>
  <div class="card-body">
    <form method="post" action="<?= site_url('admin/BackupRetentionCenter/preview'); ?>" class="row g-3">
      <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">

      <div class="col-md-2">
        <label class="form-label">Retention Hari</label>
        <input type="number" min="1" max="3650" class="form-control" name="keep_days" value="<?= (int) $policy['keep_days']; ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Source Terbaru</label>
        <input type="number" min="1" max="50" class="form-control" name="keep_source" value="<?= (int) $policy['keep_source']; ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Database Terbaru</label>
        <input type="number" min="1" max="50" class="form-control" name="keep_database" value="<?= (int) $policy['keep_database']; ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Deployment Terbaru</label>
        <input type="number" min="1" max="100" class="form-control" name="keep_deployment" value="<?= (int) $policy['keep_deployment']; ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Baseline Terbaru</label>
        <input type="number" min="1" max="20" class="form-control" name="keep_baseline" value="<?= (int) $policy['keep_baseline']; ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Sneat Backup</label>
        <input type="number" min="1" max="10" class="form-control" name="keep_sneat" value="<?= (int) $policy['keep_sneat']; ?>">
      </div>

      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bx bx-search-alt me-1"></i> Preview Cleanup
        </button>
        <a href="<?= site_url('admin/DeploymentSafetyCenter'); ?>" class="btn btn-outline-secondary">
          <i class="bx bx-arrow-back me-1"></i> Deployment Safety Center
        </a>
      </div>
    </form>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
    <div>
      <h5 class="mb-1">Inventaris Backup</h5>
      <small class="text-muted">Backup dengan label DILINDUNGI tidak akan ikut cleanup.</small>
    </div>
    <?php if ($preview && $report['candidate_count'] > 0): ?>
      <span class="badge bg-label-warning align-self-md-center">
        Preview: <?= (int) $report['candidate_count']; ?> item / <?= html_escape($fmt($report['candidate_bytes'])); ?>
      </span>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>Backup</th>
          <th>Kategori</th>
          <th>Ukuran</th>
          <th>Umur</th>
          <th>Terakhir Diubah</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($report['items'])): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4">Belum ada backup pada root yang dipantau.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($report['items'] as $item): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= html_escape($item['name']); ?></div>
              <small class="text-muted"><?= html_escape($item['relative_path']); ?></small>
            </td>
            <td><span class="badge bg-label-info"><?= html_escape(strtoupper($item['category'])); ?></span></td>
            <td><?= html_escape($fmt($item['size_bytes'])); ?></td>
            <td><?= (int) $item['age_days']; ?> hari</td>
            <td><?= $item['mtime'] ? html_escape(date('d-m-Y H:i', $item['mtime'])) : '-'; ?></td>
            <td style="min-width:220px">
              <?php if ($item['recommended_delete']): ?>
                <span class="badge bg-label-warning">KANDIDAT HAPUS</span>
                <div class="small text-muted mt-1">Di luar retention policy.</div>
              <?php else: ?>
                <span class="badge bg-label-success">DILINDUNGI</span>
                <div class="small text-muted mt-1"><?= html_escape($item['protected_reason']); ?></div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($preview && $report['candidate_count'] > 0): ?>
<div class="card border-warning mb-4">
  <div class="card-header">
    <h5 class="mb-1 text-warning">Apply Cleanup</h5>
    <small class="text-muted">Operasi ini permanen untuk kandidat yang dihitung ulang saat tombol ditekan.</small>
  </div>
  <div class="card-body">
    <form id="backup-cleanup-form" method="post" action="<?= site_url('admin/BackupRetentionCenter/cleanup'); ?>">
      <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
      <input type="hidden" name="keep_days" value="<?= (int) $policy['keep_days']; ?>">
      <input type="hidden" name="keep_source" value="<?= (int) $policy['keep_source']; ?>">
      <input type="hidden" name="keep_database" value="<?= (int) $policy['keep_database']; ?>">
      <input type="hidden" name="keep_deployment" value="<?= (int) $policy['keep_deployment']; ?>">
      <input type="hidden" name="keep_baseline" value="<?= (int) $policy['keep_baseline']; ?>">
      <input type="hidden" name="keep_sneat" value="<?= (int) $policy['keep_sneat']; ?>">

      <label class="form-label">Ketik untuk konfirmasi</label>
      <div class="input-group" style="max-width:520px">
        <input type="text" class="form-control" name="confirm_phrase" placeholder="HAPUS BACKUP LAMA" autocomplete="off" required>
        <button type="submit" class="btn btn-danger">
          <i class="bx bx-trash me-1"></i> Bersihkan Backup Lama
        </button>
      </div>
      <div class="form-text">Baseline/backup terbaru, backup dalam retention window, symlink, dan backup kritis tetap dilindungi.</div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <h5 class="card-header">Root Backup</h5>
      <div class="card-body">
        <?php foreach ($report['roots'] as $root): ?>
          <div class="d-flex justify-content-between align-items-start border-bottom py-2">
            <div>
              <div class="fw-semibold"><?= html_escape($root['label']); ?></div>
              <small class="text-muted"><?= html_escape($root['path']); ?></small>
            </div>
            <div class="text-end">
              <?php if (!$root['exists']): ?>
                <span class="badge bg-label-secondary">TIDAK ADA</span>
              <?php elseif ($root['writable']): ?>
                <span class="badge bg-label-success">WRITABLE</span>
              <?php else: ?>
                <span class="badge bg-label-danger">READ ONLY</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="alert alert-warning mt-3 mb-0">
          Untuk production, backup kritis idealnya dipindahkan keluar public_html/source aplikasi.
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card h-100">
      <h5 class="card-header">Histori Cleanup</h5>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Waktu</th><th>Dihapus</th><th>Gagal</th><th>Ruang</th><th>User</th></tr>
          </thead>
          <tbody>
          <?php if (empty($history)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada histori cleanup.</td></tr>
          <?php else: ?>
            <?php foreach ($history as $row): ?>
              <tr>
                <td><?= html_escape($row['created_at']); ?></td>
                <td><?= (int) $row['deleted_count']; ?></td>
                <td><?= (int) $row['failed_count']; ?></td>
                <td><?= html_escape($fmt((float) $row['freed_bytes'])); ?></td>
                <td>#<?= (int) $row['user_id']; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var form = document.getElementById('backup-cleanup-form');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    var input = form.querySelector('[name="confirm_phrase"]');
    var ok = input && input.value.trim() === 'HAPUS BACKUP LAMA';

    if (!ok) {
      event.preventDefault();
      if (window.Swal) {
        Swal.fire({
          icon: 'warning',
          title: 'Konfirmasi belum sesuai',
          text: 'Ketik tepat: HAPUS BACKUP LAMA'
        });
      } else {
        alert('Ketik tepat: HAPUS BACKUP LAMA');
      }
      return;
    }

    if (!window.Swal && !window.confirm('Hapus backup lama sesuai preview retention?')) {
      event.preventDefault();
    }
  });
})();
</script>
