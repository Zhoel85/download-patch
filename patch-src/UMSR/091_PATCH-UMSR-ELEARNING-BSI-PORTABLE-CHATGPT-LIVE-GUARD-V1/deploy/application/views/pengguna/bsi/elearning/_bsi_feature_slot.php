<?php
defined('BASEPATH') or exit('No direct script access allowed');

if ((string)$this->session->userdata('hak_akses') !== '17') {
    return;
}

$bsi_id_jadwal = 0;
if (isset($jadwal) && is_array($jadwal)) {
    if (!empty($jadwal['id'])) {
        $bsi_id_jadwal = (int)$jadwal['id'];
    } elseif (!empty($jadwal['id_jadwal'])) {
        $bsi_id_jadwal = (int)$jadwal['id_jadwal'];
    }
}
if ($bsi_id_jadwal <= 0 && isset($id_jadwal)) {
    $bsi_id_jadwal = (int)$id_jadwal;
}
if ($bsi_id_jadwal <= 0) {
    $bsi_id_jadwal = (int)$this->uri->segment(5);
}
if ($bsi_id_jadwal <= 0) {
    return;
}

$portable_base = base_url('pengguna/bsi/Elearning_portable');
?>
<div class="card border-0 shadow-sm mb-3" id="bsiPortableTools" style="border-radius:14px;">
    <div class="card-body py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-primary flex-shrink-0"
                      style="width:42px;height:42px;">
                    <i class="bx bx-bot fs-4"></i>
                </span>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h6 class="mb-0 fw-semibold">Persiapan Konten BSI</h6>
                        <span class="badge bg-label-info">Portable ChatGPT</span>
                        <span class="badge bg-label-success">Live Data Guard</span>
                    </div>
                    <div class="text-muted small mt-1">
                        Isi konten akademik kelas melalui JSON tanpa mengubah presensi, submission, nilai, jadwal, atau data pelaksanaan.
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="badge bg-label-secondary fw-normal">1. Unduh</span>
                        <span class="text-muted small align-self-center">→</span>
                        <span class="badge bg-label-secondary fw-normal">2. ChatGPT</span>
                        <span class="text-muted small align-self-center">→</span>
                        <span class="badge bg-label-secondary fw-normal">3. Upload JSON</span>
                        <span class="text-muted small align-self-center">→</span>
                        <span class="badge bg-label-secondary fw-normal">4. Preview & Terapkan</span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="<?= $portable_base . '/download_instruction/' . $bsi_id_jadwal ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-download me-1"></i>Unduh Perintah
                </a>
                <a href="<?= $portable_base . '/upload/' . $bsi_id_jadwal ?>"
                   class="btn btn-sm btn-primary">
                    <i class="bx bx-upload me-1"></i>Upload JSON
                </a>
                <a href="<?= $portable_base . '/history/' . $bsi_id_jadwal ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-history me-1"></i>Riwayat
                </a>
            </div>
        </div>
    </div>
</div>
