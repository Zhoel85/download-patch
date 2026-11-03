<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <a href="<?= $base095.'/prodi/'.$prodi_id ?>" class="small text-muted"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
      <h4 class="mt-2 mb-1">Riwayat AI Curriculum</h4>
      <div class="text-muted small">Audit preview/apply per Prodi dan versi Kurikulum.</div>
    </div>
  </div>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Batch</th><th>Curriculum</th><th>Status</th><th>Schema</th><th>Operator</th><th>Dibuat</th><th>Applied</th></tr></thead>
        <tbody>
        <?php if(empty($history)): ?><tr><td colspan="7" class="text-center text-muted py-5">Belum ada riwayat.</td></tr>
        <?php else: foreach($history as $row):
          $st=strtoupper((string)($row['status']??''));
        ?>
          <tr>
            <td>#<?= (int)($row['id']??0) ?></td>
            <td>#<?= (int)($row['curriculum_id']??0) ?></td>
            <td><span class="badge <?= $st==='APPLIED'?'bg-label-success':'bg-label-info' ?>"><?= html_escape($st) ?></span></td>
            <td><?= html_escape($row['schema_version']??'') ?></td>
            <td><?= html_escape($row['created_by']??'') ?></td>
            <td><?= html_escape($row['created_at']??'') ?></td>
            <td><?= html_escape($row['applied_at']??'-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
