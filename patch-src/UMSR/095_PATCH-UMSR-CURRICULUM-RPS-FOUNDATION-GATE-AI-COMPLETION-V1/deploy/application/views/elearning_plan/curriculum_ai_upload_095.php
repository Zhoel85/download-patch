<?php
defined('BASEPATH') or exit('No direct script access allowed');

$programName = isset($program['nama_prodi']) ? (string) $program['nama_prodi'] : 'Program Studi';
$state = strtoupper((string) ($curriculum['state'] ?? $curriculum['status'] ?? 'DRAFT'));
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="<?= base_url('pengguna/bsi/Elearning_rencana/master/' . (int)$prodi_id . '?master_id=' . (int)$master_id) ?>"
               class="text-muted small text-decoration-none">
                <i class="bx bx-left-arrow-alt me-1"></i>Kembali ke Curriculum Engine
            </a>
            <h4 class="mb-1 mt-2">Upload JSON AI · <?= html_escape($programName) ?></h4>
            <div class="text-muted small">AI Curriculum Assistant · Safe Merge</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-label-success px-3 py-2">FOUNDATION LOCKED</span>
            <span class="badge bg-label-primary px-3 py-2"><?= html_escape($state) ?></span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-header border-bottom">
                    <h6 class="mb-1">Hasil ChatGPT</h6>
                    <div class="text-muted small">Upload satu JSON dari perintah Kurikulum versi ini. Upload hanya membuat preflight; belum menulis Kurikulum.</div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-2 mb-4">
                        <div class="col-sm-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">Kelengkapan saat ini</div>
                                <div class="fs-4 fw-semibold text-primary"><?= (int)$completion['percent'] ?>%</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">Foundation Rev.</div>
                                <div class="fs-4 fw-semibold"><?= html_escape($foundation_status['revision'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">RPS Ready</div>
                                <div class="fs-4 fw-semibold"><?= (int)$completion['rps']['ready'] ?>/<?= (int)$completion['rps']['total'] ?></div>
                            </div>
                        </div>
                    </div>

                    <form method="post"
                          enctype="multipart/form-data"
                          action="<?= base_url('pengguna/bsi/Elearning_rencana/ai_curriculum_preview/' . (int)$prodi_id) ?>">
                        <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>"
                               value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                        <input type="hidden" name="master_id" value="<?= (int)$master_id ?>">

                        <label class="form-label fw-semibold">File JSON Kurikulum</label>
                        <input type="file" name="json_file" class="form-control"
                               accept=".json,application/json" required>
                        <div class="form-text">Maksimal 5 MB · schema UMSR_CURRICULUM_PACKAGE v1.0.</div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bx bx-shield-quarter me-1"></i>Preflight & Lihat Perubahan
                            </button>
                            <a href="<?= base_url('pengguna/bsi/Elearning_rencana/ai_curriculum_command/' . (int)$prodi_id . '?master_id=' . (int)$master_id) ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-download me-1"></i>Unduh Perintah Baru
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
                <div class="card-header border-bottom"><h6 class="mb-0">Safe Merge</h6></div>
                <div class="card-body">
                    <?php
                    $rules = array(
                        array('KEEP', 'Data existing valid tetap dipertahankan.', 'success'),
                        array('ADD', 'Komponen yang belum ada dapat ditambahkan.', 'primary'),
                        array('COMPLETE', 'Field kosong dapat dilengkapi.', 'info'),
                        array('CONFLICT', 'Perbedaan tidak menimpa existing; wajib review.', 'warning')
                    );
                    foreach ($rules as $rule):
                    ?>
                        <div class="d-flex gap-2 align-items-start mb-3">
                            <span class="badge bg-label-<?= $rule[2] ?>"><?= $rule[0] ?></span>
                            <div class="small"><?= html_escape($rule[1]) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="alert alert-warning small">
                <strong>PL & PEO tidak dikelola di sini.</strong><br>
                Keduanya selalu dibaca dari Fondasi Akademik Prodi yang terkunci.
            </div>
        </div>
    </div>
</div>
