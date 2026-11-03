<?php
defined('BASEPATH') or exit('No direct script access allowed');

$class = !empty($context['class']) ? $context['class'] : array();
$ready = !empty($context['ready']);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h5 class="mb-1">Upload JSON E-Learning BSI</h5>
            <div class="text-muted small">
                <?= html_escape(isset($class['nama_matakuliah']) ? $class['nama_matakuliah'] : 'Kelas') ?>
                <?php if (!empty($class['nama_kelas'])) : ?>
                    · <?= html_escape($class['nama_kelas']) ?>
                <?php endif; ?>
                <?php if (!empty($class['periode'])) : ?>
                    · <?= html_escape($class['periode']) ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?= base_url('pengguna/bsi/Elearning/kelas/' . (int)$id_jadwal) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i>Kembali ke Kelas
        </a>
    </div>

    <?php if (!empty($errors)) : ?>
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-2"><i class="bx bx-error-circle me-1"></i>Preflight belum lolos</div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error) : ?>
                    <li><?= html_escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h6 class="mb-1">File hasil ChatGPT</h6>
                    <div class="text-muted small">Upload satu file JSON dari perintah kelas ini. Portal hanya membuat preview; belum ada data kelas yang ditulis.</div>
                </div>
                <div class="card-body">
                    <?php if (!$ready) : ?>
                        <div class="alert alert-warning mb-0">
                            <div class="fw-semibold">Context akademik belum siap.</div>
                            <div class="small mt-1">
                                <?= html_escape(implode(' ', isset($context['errors']) ? $context['errors'] : array('Context belum siap.'))) ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <form action="<?= base_url('pengguna/bsi/Elearning_portable/preview/' . (int)$id_jadwal) ?>"
                              method="post" enctype="multipart/form-data">
                            <input type="hidden"
                                   name="<?= $this->security->get_csrf_token_name() ?>"
                                   value="<?= $this->security->get_csrf_hash() ?>">

                            <label class="form-label fw-semibold">JSON E-Learning</label>
                            <input type="file" name="json_file" class="form-control" accept=".json,application/json" required>
                            <div class="form-text">Maksimal 5 MB. Schema wajib UMSR_ELB_PACKAGE v3.0.</div>

                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bx bx-shield-quarter me-1"></i>Preflight & Preview
                                </button>
                                <a href="<?= base_url('pengguna/bsi/Elearning_portable/download_instruction/' . (int)$id_jadwal) ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-download me-1"></i>Unduh Perintah Baru
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom">
                    <h6 class="mb-0">Live Data Guard</h6>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-3">Import 091 tidak akan mengubah:</div>
                    <div class="d-grid gap-2 small">
                        <?php foreach (array(
                            'Presensi mahasiswa',
                            'Submission & file jawaban',
                            'Nilai & feedback dosen',
                            'KRS dan data mahasiswa',
                            'Jadwal & dosen pengampu',
                            'Tanggal pelaksanaan nyata',
                            'Waktu UTS/UAS & deadline tugas',
                            'Dokumentasi pelaksanaan'
                        ) as $guard) : ?>
                            <div><i class="bx bx-lock-alt text-success me-2"></i><?= html_escape($guard) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
