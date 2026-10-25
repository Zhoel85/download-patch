<?php
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();

$movedCount = !empty($manifest['moved']) ? count($manifest['moved']) : 0;
$keptCount = !empty($manifest['kept']) ? count($manifest['kept']) : 0;
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h5 class="mb-1">Sneat Migration & Legacy Cleanup</h5>
          <small class="text-muted">Dependency-safe purge template lama dengan backup terlindungi.</small>
        </div>
        <div class="d-flex gap-2">
          <span class="badge bg-label-primary">SNEAT v1.0.0</span>
          <span class="badge bg-label-warning">PATCH 025</span>
        </div>
      </div>
      <div class="card-body">
        <div class="alert alert-warning">
          Cleanup tidak menghapus file yang masih direferensikan oleh controller/view bisnis. File aman dipindahkan ke
          <code>application/backups/sneat_template_cleanup_025/</code> agar dapat dipulihkan.
        </div>

        <div class="d-flex flex-wrap gap-2">
          <form method="post" action="<?= site_url('admin/SneatMigrationCenter/finalize_cleanup'); ?>" class="m-0" id="form-cleanup-legacy">
            <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
            <button type="submit" class="btn btn-danger">
              <i class="bx bx-trash me-1"></i> Bersihkan Template Lama
            </button>
          </form>

          <?php if ($movedCount > 0): ?>
          <form method="post" action="<?= site_url('admin/SneatMigrationCenter/restore_legacy'); ?>" class="m-0" id="form-restore-legacy">
            <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
            <button type="submit" class="btn btn-outline-secondary">
              <i class="bx bx-undo me-1"></i> Pulihkan Backup Legacy
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5 mb-4">
    <div class="card h-100">
      <h5 class="card-header">Sneat Runtime</h5>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead><tr><th>Komponen</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($status as $row): ?>
            <tr>
              <td><?= html_escape($row['label']); ?></td>
              <td>
                <?= $row['exists']
                  ? '<span class="badge bg-label-success">OK</span>'
                  : '<span class="badge bg-label-danger">MISSING</span>'; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-7 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Dependency Scan Template Lama</h5>
        <?php if ($movedCount || $keptCount): ?>
          <small class="text-muted">Moved <?= (int) $movedCount; ?> · Kept <?= (int) $keptCount; ?></small>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Komponen</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
          <?php foreach ($legacy as $row): ?>
            <tr>
              <td>
                <strong><?= html_escape($row['label']); ?></strong><br>
                <small class="text-muted"><?= html_escape($row['relative']); ?></small>
              </td>
              <td>
                <?php if (!$row['exists']): ?>
                  <span class="badge bg-label-secondary">TIDAK ADA</span>
                <?php elseif ($row['referenced']): ?>
                  <span class="badge bg-label-warning">MASIH DIREFERENSIKAN</span>
                <?php else: ?>
                  <span class="badge bg-label-success">AMAN</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($row['action'] === 'BACKUP_AND_REMOVE'): ?>
                  <span class="text-danger fw-semibold">Backup + Remove</span>
                <?php elseif ($row['action'] === 'KEEP'): ?>
                  <span class="text-warning fw-semibold">Pertahankan</span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  function confirmForm(id, title, text) {
    var form = document.getElementById(id);
    if (!form) return;

    form.addEventListener('submit', function (event) {
      if (!window.Swal) {
        if (!window.confirm(text)) event.preventDefault();
        return;
      }

      event.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: title,
        text: text,
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal'
      }).then(function (result) {
        if (result.isConfirmed) form.submit();
      });
    });
  }

  confirmForm(
    'form-cleanup-legacy',
    'Bersihkan template lama?',
    'Hanya file yang lolos dependency scan yang akan dipindahkan ke backup terlindungi.'
  );

  confirmForm(
    'form-restore-legacy',
    'Pulihkan template legacy?',
    'File yang pernah dipindahkan oleh Patch 025 akan dikembalikan ke lokasi asal.'
  );
})();
</script>
