<?php
defined('BASEPATH') or exit('No direct script access allowed');

$class = isset($context['class']) ? $context['class'] : array();
$counts = isset($validation['counts']) ? $validation['counts'] : array();
$summary = isset($preview['summary']) ? $preview['summary'] : array();
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0">Preflight Paket E-Learning</h5>
                <span class="badge bg-label-success">VALID</span>
            </div>
            <div class="text-muted small mt-1">
                <?= html_escape(isset($class['nama_matakuliah']) ? $class['nama_matakuliah'] : '') ?>
                · Batch #<?= (int)$batch_id ?>
            </div>
        </div>
        <a href="<?= base_url('pengguna/bsi/Elearning_portable/upload/' . (int)$id_jadwal) ?>"
           class="btn btn-sm btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i>Ganti JSON
        </a>
    </div>

    <div class="row g-2 mb-3">
        <?php
        $cards = array(
            array('Pertemuan', isset($counts['meetings']) ? $counts['meetings'] . '/16' : '-', 'bx-calendar-check'),
            array('Tugas', isset($counts['tasks']) ? $counts['tasks'] . '/14' : '-', 'bx-task'),
            array('UTS PG', isset($counts['uts_questions']) ? $counts['uts_questions'] : '-', 'bx-list-check'),
            array('UAS PG', isset($counts['uas_questions']) ? $counts['uas_questions'] : '-', 'bx-list-check'),
            array('Workload', isset($counts['total_hours']) ? $counts['total_hours'] . ' jam' : '-', 'bx-time-five'),
            array('Active', isset($counts['active_hours']) ? $counts['active_hours'] . ' jam' : '-', 'bx-run')
        );
        foreach ($cards as $card) :
        ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <i class="bx <?= $card[2] ?> text-primary"></i>
                        <div class="text-muted small mt-2"><?= html_escape($card[0]) ?></div>
                        <div class="fw-semibold"><?= html_escape((string)$card[1]) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-1">Preview Live Data Guard</h6>
                <div class="text-muted small">Existing content dipertahankan. Hanya ruang aman yang akan diisi.</div>
            </div>
            <span class="badge bg-label-info">Belum diterapkan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:80px;">Slot</th>
                    <th>Pertemuan</th>
                    <th>Pertemuan</th>
                    <th>Materi</th>
                    <th>Tugas</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($preview['rows'] as $row) : ?>
                    <tr>
                        <td><span class="badge bg-label-primary"><?= (int)$row['slot'] ?></span></td>
                        <td class="text-wrap" style="min-width:250px;"><?= html_escape($row['title']) ?></td>
                        <?php foreach (array('meeting', 'material', 'task') as $key) :
                            $value = (string)$row[$key];
                            $classBadge = strpos($value, 'PROTECTED') !== false ? 'warning'
                                : (strpos($value, 'ADD') !== false || strpos($value, 'ATTACH') !== false ? 'success'
                                : (strpos($value, 'PREPARED') !== false ? 'info' : 'secondary'));
                        ?>
                            <td><span class="badge bg-label-<?= $classBadge ?>"><?= html_escape($value) ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <?php foreach (array('UTS', 'UAS') as $exam) :
            $examData = $preview['exams'][$exam];
            $action = $examData['action'];
            $badge = strpos($action, 'PROTECTED') !== false ? 'warning'
                : (strpos($action, 'ATTACH') !== false ? 'success' : 'info');
        ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold"><?= $exam ?></div>
                                <div class="text-muted small"><?= (int)$examData['questions_in_json'] ?> PG di JSON</div>
                            </div>
                            <span class="badge bg-label-<?= $badge ?>"><?= html_escape($action) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-primary border-0 shadow-sm d-flex align-items-start gap-2">
        <i class="bx bx-shield-quarter fs-4 mt-1"></i>
        <div>
            <div class="fw-semibold">Tidak ada overwrite diam-diam.</div>
            <div class="small"><?= html_escape($preview['guard_note']) ?></div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2">
        <a href="<?= base_url('pengguna/bsi/Elearning/kelas/' . (int)$id_jadwal) ?>"
           class="btn btn-sm btn-outline-secondary">Batal</a>
        <form action="<?= base_url('pengguna/bsi/Elearning_portable/apply/' . (int)$id_jadwal . '/' . (int)$batch_id) ?>"
              method="post" class="d-inline">
            <input type="hidden"
                   name="<?= $this->security->get_csrf_token_name() ?>"
                   value="<?= $this->security->get_csrf_hash() ?>">
            <button type="submit" class="btn btn-sm btn-primary"
                    onclick="return confirm('Terapkan paket yang sudah lolos preflight? Existing live data tetap dilindungi.');">
                <i class="bx bx-check-shield me-1"></i>Terapkan Paket
            </button>
        </form>
    </div>
</div>
