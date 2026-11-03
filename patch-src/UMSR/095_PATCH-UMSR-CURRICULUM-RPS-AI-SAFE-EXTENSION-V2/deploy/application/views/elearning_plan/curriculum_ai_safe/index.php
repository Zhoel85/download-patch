<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
      <div class="text-primary small fw-semibold mb-1"><i class="bx bx-shield-quarter me-1"></i>BSI · SAFE EXTENSION V2</div>
      <h4 class="mb-1">Kurikulum & RPS OBE</h4>
      <div class="text-muted">Fondasi Akademik Prodi menjadi pintu wajib sebelum masuk ke Curriculum Engine.</div>
    </div>
    <span class="badge bg-label-success">Legacy Engine tidak diubah</span>
  </div>

  <?php if (empty($programs)): ?>
    <div class="alert alert-warning">Master Program Studi tidak ditemukan.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($programs as $item):
        $p=$item['program']; $f=$item['foundation']; $ok=!empty($f['complete']);
      ?>
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
          <div class="card-body">
            <div class="d-flex justify-content-between gap-2 align-items-start">
              <div>
                <div class="small text-muted"><?= html_escape($p['kode_prodi'] ?? '') ?></div>
                <h6 class="mb-1"><?= html_escape($p['nama_prodi'] ?? 'Program Studi') ?></h6>
                <div class="small text-muted"><?= html_escape($p['status'] ?? '') ?></div>
              </div>
              <span class="badge <?= $ok ? 'bg-label-success' : 'bg-label-warning' ?>">
                <?= $ok ? 'FOUNDATION SIAP' : 'TERKUNCI' ?>
              </span>
            </div>

            <div class="mt-3 small">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Foundation</span>
                <strong><?= $ok ? 'Rev. '.html_escape($f['revision'] ?? '-') : 'Belum lengkap' ?></strong>
              </div>
              <div class="d-flex justify-content-between mt-1">
                <span class="text-muted">Versi Kurikulum</span>
                <strong><?= (int)$item['curriculum_count'] ?></strong>
              </div>
            </div>

            <?php if (!$ok && !empty($f['missing'])): ?>
              <div class="mt-3 p-2 rounded bg-label-warning small">
                <?= html_escape($f['missing'][0]) ?>
                <?php if (count($f['missing'])>1): ?>
                  <span class="text-muted"> +<?= count($f['missing'])-1 ?> lainnya</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="mt-3">
              <a class="btn btn-sm <?= $ok ? 'btn-primary' : 'btn-outline-secondary' ?>"
                 href="<?= $ok ? $base095.'/prodi/'.(int)$p['id'] : 'javascript:void(0)' ?>"
                 <?= $ok ? '' : 'aria-disabled="true" style="pointer-events:none;opacity:.55"' ?>>
                <i class="bx <?= $ok ? 'bx-right-arrow-alt' : 'bx-lock-alt' ?> me-1"></i>
                <?= $ok ? 'Kelola Kurikulum' : 'Lengkapi Fondasi dahulu' ?>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
