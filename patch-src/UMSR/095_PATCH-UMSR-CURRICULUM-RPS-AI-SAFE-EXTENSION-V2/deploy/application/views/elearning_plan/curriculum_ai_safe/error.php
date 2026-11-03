<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card border-danger shadow-sm mx-auto" style="max-width:850px;">
    <div class="card-body p-4">
      <div class="d-flex gap-3 align-items-start">
        <span class="d-inline-flex align-items-center justify-content-center bg-label-danger rounded-circle flex-shrink-0" style="width:48px;height:48px;"><i class="bx bx-error-circle fs-3"></i></span>
        <div>
          <h5 class="mb-1"><?= html_escape($error_title ?? 'Kurikulum & RPS OBE') ?></h5>
          <p class="text-muted mb-3"><?= html_escape($error_message ?? 'Terjadi kesalahan.') ?></p>
          <div class="d-flex gap-2">
            <?php if(!empty($prodi_id)): ?><a class="btn btn-sm btn-primary" href="<?= $base095.'/prodi/'.(int)$prodi_id ?>">Kembali ke Prodi</a><?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= $base095 ?>">Daftar Prodi</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('pengguna/bsi/Elearning_rencana') ?>">Engine Existing</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
