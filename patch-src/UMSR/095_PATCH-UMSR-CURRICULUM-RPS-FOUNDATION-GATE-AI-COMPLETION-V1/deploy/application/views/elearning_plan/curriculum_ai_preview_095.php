<?php
defined('BASEPATH') or exit('No direct script access allowed');

$stats = isset($preview['stats']) ? $preview['stats'] : array();
$conflicts = isset($preview['conflicts']) ? $preview['conflicts'] : array();
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="<?= base_url('pengguna/bsi/Elearning_rencana/ai_curriculum_upload/' . (int)$prodi_id . '?master_id=' . (int)$master_id) ?>"
               class="text-muted small text-decoration-none">
                <i class="bx bx-left-arrow-alt me-1"></i>Ganti JSON
            </a>
            <h4 class="mb-1 mt-2">Preflight AI Curriculum</h4>
            <div class="text-muted small"><?= html_escape($program['nama_prodi'] ?? '') ?> · Batch #<?= (int)$batch_id ?></div>
        </div>
        <span class="badge bg-label-info px-3 py-2">BELUM DITERAPKAN</span>
    </div>

    <div class="row g-2 mb-3">
        <?php
        $cards = array(
            array('KEEP', (int)($stats['keep'] ?? 0), 'success', 'bx-check'),
            array('ADD', (int)($stats['add'] ?? 0), 'primary', 'bx-plus'),
            array('COMPLETE', (int)($stats['complete'] ?? 0), 'info', 'bx-edit'),
            array('CONFLICT', (int)($stats['conflict'] ?? 0), 'warning', 'bx-error')
        );
        foreach ($cards as $card):
        ?>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small"><?= $card[0] ?></div>
                                <div class="fs-4 fw-semibold"><?= $card[1] ?></div>
                            </div>
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-<?= $card[2] ?>"
                                  style="width:38px;height:38px;">
                                <i class="bx <?= $card[3] ?> fs-5"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
        <div class="card-header border-bottom">
            <h6 class="mb-1">Prinsip Apply</h6>
            <div class="text-muted small">Existing non-empty selalu menang. Conflict dicatat, bukan ditimpa.</div>
        </div>
        <div class="card-body">
            <div class="alert alert-primary mb-0">
                <i class="bx bx-shield-quarter me-1"></i>
                APPLY hanya memperbarui payload versi <strong>DRAFT</strong>, melakukan validasi hash ulang, dan dijalankan dalam transaksi database.
            </div>
        </div>
    </div>

    <?php if ($conflicts): ?>
        <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
            <div class="card-header border-bottom">
                <h6 class="mb-1">Conflict untuk Review</h6>
                <div class="text-muted small"><?= count($conflicts) ?> perbedaan dipertahankan sebagai existing.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Path</th><th>Existing</th><th>Usulan AI</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($conflicts, 0, 100) as $row): ?>
                        <tr>
                            <td><code><?= html_escape($row['path'] ?? '') ?></code></td>
                            <td class="text-wrap" style="max-width:300px;"><?= html_escape(is_scalar($row['existing'] ?? '') ? (string)$row['existing'] : json_encode($row['existing'], JSON_UNESCAPED_UNICODE)) ?></td>
                            <td class="text-wrap" style="max-width:300px;"><?= html_escape(is_scalar($row['proposed'] ?? '') ? (string)$row['proposed'] : json_encode($row['proposed'], JSON_UNESCAPED_UNICODE)) ?></td>
                            <td><span class="badge bg-label-warning">KEEP EXISTING</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-end gap-2">
        <a href="<?= base_url('pengguna/bsi/Elearning_rencana/master/' . (int)$prodi_id . '?master_id=' . (int)$master_id) ?>"
           class="btn btn-sm btn-outline-secondary">Batal</a>
        <form method="post" action="<?= base_url('pengguna/bsi/Elearning_rencana/ai_curriculum_apply/' . (int)$prodi_id) ?>">
            <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>"
                   value="<?= html_escape($this->security->get_csrf_hash()) ?>">
            <input type="hidden" name="master_id" value="<?= (int)$master_id ?>">
            <input type="hidden" name="batch_id" value="<?= (int)$batch_id ?>">
            <button type="submit" class="btn btn-sm btn-primary"
                    onclick="return confirm('Terapkan hasil preflight ke versi DRAFT? Data existing valid dan conflict tidak akan ditimpa.');">
                <i class="bx bx-check-shield me-1"></i>Terapkan ke DRAFT
            </button>
        </form>
    </div>
</div>
