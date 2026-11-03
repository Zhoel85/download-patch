<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <a href="<?= $base095.'/prodi/'.$prodi_id ?>" class="small text-muted"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
      <h4 class="mt-2 mb-1">Upload JSON Kurikulum</h4>
      <div class="text-muted small">JSON hanya membuat preflight. Belum ada perubahan database sampai tombol Terapkan ke DRAFT ditekan.</div>
    </div>
    <span class="badge bg-label-primary">DRAFT #<?= (int)$curriculum_id ?></span>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" action="<?= $base095.'/preview/'.$prodi_id.'/'.$curriculum_id ?>">
            <?php if(!empty($csrf095['name'])): ?><input type="hidden" name="<?= html_escape($csrf095['name']) ?>" value="<?= html_escape($csrf095['hash']) ?>"><?php endif; ?>
            <label class="form-label fw-semibold">HASIL_KURIKULUM_*.json</label>
            <input class="form-control" type="file" name="json_file" accept=".json,application/json" required>
            <div class="form-text">Maksimal 5 MB. Scope dan Foundation lock harus sama dengan file perintah.</div>
            <div class="d-flex gap-2 mt-3">
              <button class="btn btn-sm btn-primary" type="submit"><i class="bx bx-shield-quarter me-1"></i>Preflight & Preview</button>
              <a class="btn btn-sm btn-outline-primary" href="<?= $base095.'/command/'.$prodi_id.'/'.$curriculum_id ?>"><i class="bx bx-download me-1"></i>Unduh Perintah Baru</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6>Aturan Import</h6>
          <div class="small d-grid gap-2">
            <div><i class="bx bx-check text-success me-1"></i>KEEP existing valid</div>
            <div><i class="bx bx-plus text-primary me-1"></i>ADD komponen kosong</div>
            <div><i class="bx bx-edit text-info me-1"></i>COMPLETE field kosong</div>
            <div><i class="bx bx-shield text-warning me-1"></i>CONFLICT tidak overwrite</div>
            <div><i class="bx bx-lock-alt text-secondary me-1"></i>PL/PEO tetap dari Foundation</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
