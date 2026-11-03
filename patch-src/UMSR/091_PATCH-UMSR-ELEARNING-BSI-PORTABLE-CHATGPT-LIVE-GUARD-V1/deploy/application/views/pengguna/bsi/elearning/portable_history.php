<?php
defined('BASEPATH') or exit('No direct script access allowed');

$class = isset($context['class']) ? $context['class'] : array();
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h5 class="mb-1">Riwayat Import E-Learning BSI</h5>
            <div class="text-muted small">
                <?= html_escape(isset($class['nama_matakuliah']) ? $class['nama_matakuliah'] : 'Kelas') ?>
                <?php if (!empty($class['nama_kelas'])) : ?> · <?= html_escape($class['nama_kelas']) ?><?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('pengguna/bsi/Elearning_portable/upload/' . (int)$id_jadwal) ?>"
               class="btn btn-sm btn-primary"><i class="bx bx-upload me-1"></i>Upload JSON</a>
            <a href="<?= base_url('pengguna/bsi/Elearning/kelas/' . (int)$id_jadwal) ?>"
               class="btn btn-sm btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Kelas</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Batch</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th>Schema</th>
                    <th>Operator</th>
                    <th>Package Hash</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($history)) : ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada riwayat import untuk kelas ini.</td></tr>
                <?php else : ?>
                    <?php foreach ($history as $row) :
                        $status = strtoupper((string)$row['status']);
                        $badge = $status === 'APPLIED' ? 'success' : ($status === 'REJECTED' ? 'danger' : 'info');
                    ?>
                        <tr>
                            <td class="fw-semibold">#<?= (int)$row['id'] ?></td>
                            <td><?= html_escape($row['created_at']) ?></td>
                            <td><span class="badge bg-label-<?= $badge ?>"><?= html_escape($status) ?></span></td>
                            <td><?= html_escape($row['schema_version']) ?></td>
                            <td><?= html_escape((string)$row['created_by']) ?></td>
                            <td><code><?= html_escape(substr($row['package_hash'], 0, 16)) ?>…</code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
