<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="<?= base_url('pengguna/bsi/Elearning_rencana/master/' . (int)$prodi_id . '?master_id=' . (int)$master_id) ?>"
               class="text-muted small text-decoration-none">
                <i class="bx bx-left-arrow-alt me-1"></i>Curriculum Engine
            </a>
            <h4 class="mb-1 mt-2">Riwayat AI Curriculum</h4>
            <div class="text-muted small"><?= html_escape($program['nama_prodi'] ?? '') ?></div>
        </div>
        <a href="<?= base_url('pengguna/bsi/Elearning_rencana/ai_curriculum_upload/' . (int)$prodi_id . '?master_id=' . (int)$master_id) ?>"
           class="btn btn-sm btn-primary">
            <i class="bx bx-upload me-1"></i>Upload JSON
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;">
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
                <?php if (empty($history)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada riwayat AI Curriculum pada versi ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($history as $row):
                        $status = strtoupper((string)($row['status'] ?? ''));
                        $badge = $status === 'APPLIED' ? 'success' : ($status === 'REJECTED' ? 'danger' : 'info');
                    ?>
                        <tr>
                            <td class="fw-semibold">#<?= (int)$row['id'] ?></td>
                            <td><?= html_escape($row['created_at'] ?? '') ?></td>
                            <td><span class="badge bg-label-<?= $badge ?>"><?= html_escape($status) ?></span></td>
                            <td><?= html_escape($row['schema_version'] ?? '') ?></td>
                            <td><?= html_escape($row['created_by'] ?? '') ?></td>
                            <td><code><?= html_escape(substr((string)($row['package_hash'] ?? ''), 0, 16)) ?>…</code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
