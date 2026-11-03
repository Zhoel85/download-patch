<?php
defined('BASEPATH') or exit('No direct script access allowed');

$programName = isset($program['nama_prodi']) ? (string) $program['nama_prodi'] : 'Program Studi';
$missing = !empty($curriculum_gate)
    ? (isset($curriculum_missing) ? (array) $curriculum_missing : array())
    : (isset($foundation_status['missing']) ? (array) $foundation_status['missing'] : array());
$title = !empty($curriculum_gate)
    ? 'Context Kurikulum belum siap untuk RPS'
    : 'Fondasi Akademik Prodi belum lengkap';
$subtitle = !empty($curriculum_gate)
    ? 'RPS OBE baru dapat dilanjutkan setelah struktur Kurikulum memiliki CPL, BOK/Bahan Kajian, Mata Kuliah, I/R/M/A, CPMK, Sub-CPMK, Assessment, Signature Output, workload, dan referensi resmi yang lengkap.'
    : 'Kurikulum & RPS OBE untuk Prodi ini dikunci sampai Fondasi Akademik berstatus ACTIVE dan seluruh komponen wajibnya lengkap.';
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="<?= base_url('pengguna/bsi/Elearning_rencana') ?>" class="text-muted small text-decoration-none">
                <i class="bx bx-left-arrow-alt me-1"></i>Semua Program Studi
            </a>
            <h4 class="mb-1 mt-2">Kurikulum & RPS OBE · <?= html_escape($programName) ?></h4>
            <div class="text-muted small">Academic Foundation Gate · UMS Rappang</div>
        </div>
        <span class="badge bg-label-warning px-3 py-2">
            <i class="bx bx-lock-alt me-1"></i>TERKUNCI
        </span>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;border-left:4px solid #ffab00!important;">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-start gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-warning flex-shrink-0"
                              style="width:52px;height:52px;">
                            <i class="bx bx-lock-alt fs-3"></i>
                        </span>
                        <div>
                            <div class="text-warning fw-semibold small text-uppercase mb-1">Gate Akademik Wajib</div>
                            <h4 class="mb-2"><?= html_escape($title) ?></h4>
                            <p class="text-muted mb-0"><?= html_escape($subtitle) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="rounded-3 bg-label-secondary p-3">
                        <div class="small text-muted mb-1">Program Studi</div>
                        <div class="fw-semibold"><?= html_escape($programName) ?></div>
                        <?php if (!empty($program['kode_prodi'])): ?>
                            <div class="small text-muted mt-1">Kode: <?= html_escape($program['kode_prodi']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-header border-bottom">
                    <h6 class="mb-1">Yang harus diselesaikan</h6>
                    <div class="text-muted small">Portal tidak akan melewati gate ini hanya karena ada data Kurikulum lama.</div>
                </div>
                <div class="card-body">
                    <?php if (!$missing): ?>
                        <div class="alert alert-info mb-0">Status kelengkapan sedang dihitung ulang. Muat ulang halaman setelah data diperbarui.</div>
                    <?php else: ?>
                        <div class="d-grid gap-2">
                            <?php foreach ($missing as $item): ?>
                                <div class="d-flex align-items-start gap-2 rounded-3 border p-3">
                                    <i class="bx bx-error-circle text-warning fs-5 mt-1"></i>
                                    <div class="small"><?= html_escape($item) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-header border-bottom">
                    <h6 class="mb-0">Urutan yang benar</h6>
                </div>
                <div class="card-body">
                    <?php
                    $steps = array(
                        'Fondasi Akademik Prodi ACTIVE & lengkap',
                        'Kurikulum DRAFT dikunci ke Foundation',
                        'Lengkapi Kurikulum / AI Curriculum',
                        'Validasi Context RPS',
                        'Susun dan publish seluruh RPS OBE',
                        'Buku Kurikulum OBE siap diunduh'
                    );
                    foreach ($steps as $index => $step):
                    ?>
                        <div class="d-flex gap-2 mb-3">
                            <span class="badge bg-label-primary rounded-circle flex-shrink-0"
                                  style="width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;">
                                <?= $index + 1 ?>
                            </span>
                            <div class="small"><?= html_escape($step) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <div class="alert alert-warning small mb-3">
                        <i class="bx bx-info-circle me-1"></i>
                        Lengkapi Fondasi melalui menu <strong>Fondasi Akademik Prodi</strong> pada sidebar BSI.
                    </div>
                    <a href="<?= base_url('pengguna/bsi/Elearning_rencana') ?>" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bx bx-arrow-back me-1"></i>Kembali ke Semua Prodi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
