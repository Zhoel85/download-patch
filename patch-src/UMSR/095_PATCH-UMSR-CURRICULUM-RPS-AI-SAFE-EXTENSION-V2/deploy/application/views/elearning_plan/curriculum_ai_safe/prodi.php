<?php
defined('BASEPATH') or exit('No direct script access allowed');
$p=$foundation['program'] ?? array();
$ready=!empty($foundation['complete']);
?>
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <a href="<?= $base095 ?>" class="small text-muted"><i class="bx bx-arrow-back me-1"></i>Semua Program Studi</a>
      <h4 class="mb-1 mt-2">Curriculum AI · <?= html_escape($p['nama_prodi'] ?? '') ?></h4>
      <div class="text-muted small">Foundation → Kurikulum → Context RPS → RPS OBE → Buku Kurikulum</div>
    </div>
    <span class="badge <?= $ready ? 'bg-label-success' : 'bg-label-warning' ?>">
      <?= $ready ? 'FOUNDATION ACTIVE · Rev. '.html_escape($foundation['revision'] ?? '-') : 'FOUNDATION TERKUNCI' ?>
    </span>
  </div>

  <?php if (!$ready): ?>
    <div class="card border-warning shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex gap-3">
          <span class="d-inline-flex align-items-center justify-content-center bg-label-warning rounded-circle flex-shrink-0" style="width:44px;height:44px;">
            <i class="bx bx-lock-alt fs-4"></i>
          </span>
          <div>
            <h6 class="mb-1">Fondasi Akademik Prodi belum lengkap</h6>
            <div class="text-muted small mb-2">Prodi ini tidak dapat dilanjutkan melalui pintu AI Curriculum sampai Foundation ACTIVE lengkap.</div>
            <ul class="small mb-0 ps-3">
              <?php foreach (($foundation['missing'] ?? array()) as $m): ?><li><?= html_escape($m) ?></li><?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
      <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <div class="fw-semibold">Fondasi Akademik terkunci sebagai sumber kebenaran</div>
            <div class="small text-muted">PL dan PEO tidak dibuat ulang di Kurikulum. Hash Foundation: <?= html_escape(substr($foundation['hash'] ?? '',0,12)) ?>…</div>
          </div>
          <a class="btn btn-sm btn-outline-primary" href="<?= base_url('pengguna/bsi/Elearning_rencana/master/'.$prodi_id) ?>">
            <i class="bx bx-layout me-1"></i>Buka Curriculum Engine Existing
          </a>
        </div>
      </div>
    </div>

    <?php if (empty($curricula)): ?>
      <div class="alert alert-info">
        Belum ada versi Kurikulum yang terbaca. Buat versi melalui Curriculum Engine existing, lalu kembali ke halaman ini.
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($curricula as $item):
          $row=$item['row']; $cur=$item['curriculum']; $cmp=$item['completion']; $bind=$item['binding'];
          $id=(int)($row['id'] ?? 0);
          $stateText=$cur ? strtoupper((string)($cur['state'] ?? $cur['status'] ?? 'DRAFT')) : 'DRAFT';
          $immutable=!empty($item['immutable']);
          $current=!empty($bind['current']);
          $pct=(int)($cmp['percent'] ?? 0);
        ?>
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
                <div class="flex-grow-1">
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <h6 class="mb-0"><?= html_escape($row['title'] ?? $row['code'] ?? ('Kurikulum #'.$id)) ?></h6>
                    <span class="badge <?= $immutable ? 'bg-label-success' : 'bg-label-info' ?>"><?= html_escape($stateText) ?></span>
                    <span class="badge <?= $current ? 'bg-label-primary' : 'bg-label-warning' ?>">
                      <?= $current ? 'FOUNDATION LOCK OK' : 'BELUM TERIKAT' ?>
                    </span>
                    <span class="badge <?= $pct>=100 ? 'bg-label-success' : 'bg-label-warning' ?>"><?= $pct ?>%</span>
                  </div>

                  <div class="row g-2 mt-2 small">
                    <div class="col-md-4">
                      <div class="p-2 rounded bg-label-secondary">
                        <div class="text-muted">Context Kurikulum</div>
                        <strong><?= !empty($cmp['curriculum_complete']) ? 'READY' : 'PERLU DILENGKAPI' ?></strong>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="p-2 rounded bg-label-secondary">
                        <div class="text-muted">RPS OBE</div>
                        <strong><?= (int)($cmp['rps']['ready'] ?? 0) ?>/<?= (int)($cmp['rps']['total'] ?? 0) ?> PUBLISHED</strong>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="p-2 rounded bg-label-secondary">
                        <div class="text-muted">Buku Kurikulum</div>
                        <strong><?= !empty($cmp['complete']) ? 'SIAP TERBIT' : 'TERKUNCI' ?></strong>
                      </div>
                    </div>
                  </div>

                  <?php if (!empty($cmp['missing'])): ?>
                    <div class="small text-warning mt-2">
                      <i class="bx bx-info-circle me-1"></i>
                      <?= html_escape(implode(' · ', array_slice($cmp['missing'],0,3))) ?>
                      <?php if(count($cmp['missing'])>3): ?> · +<?= count($cmp['missing'])-3 ?> lainnya<?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap align-content-start gap-2" style="max-width:520px;">
                  <a class="btn btn-sm btn-outline-secondary"
                     href="<?= base_url('pengguna/bsi/Elearning_rencana/master/'.$prodi_id.'?master_id='.$id) ?>">
                    <i class="bx bx-show me-1"></i>Lihat Existing
                  </a>

                  <?php if ($immutable): ?>
                    <form method="post" action="<?= $base095.'/revision/'.$prodi_id.'/'.$id ?>" class="d-inline">
                      <?php if(!empty($csrf095['name'])): ?><input type="hidden" name="<?= html_escape($csrf095['name']) ?>" value="<?= html_escape($csrf095['hash']) ?>"><?php endif; ?>
                      <button class="btn btn-sm btn-primary" type="submit">
                        <i class="bx bx-git-branch me-1"></i>Buat Revisi & Lengkapi AI
                      </button>
                    </form>
                  <?php elseif (!$current): ?>
                    <form method="post" action="<?= $base095.'/bind/'.$prodi_id.'/'.$id ?>" class="d-inline">
                      <?php if(!empty($csrf095['name'])): ?><input type="hidden" name="<?= html_escape($csrf095['name']) ?>" value="<?= html_escape($csrf095['hash']) ?>"><?php endif; ?>
                      <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bx bx-link me-1"></i>Ikat Foundation ACTIVE</button>
                    </form>
                  <?php else: ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= $base095.'/command/'.$prodi_id.'/'.$id ?>">
                      <i class="bx bx-download me-1"></i>Unduh Perintah
                    </a>
                    <a class="btn btn-sm btn-primary" href="<?= $base095.'/upload/'.$prodi_id.'/'.$id ?>">
                      <i class="bx bx-upload me-1"></i>Upload JSON
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= $base095.'/history/'.$prodi_id.'/'.$id ?>">
                      <i class="bx bx-history me-1"></i>Riwayat
                    </a>
                  <?php endif; ?>

                  <?php if (!empty($cmp['complete'])): ?>
                    <a class="btn btn-sm btn-success" href="<?= $base095.'/book/'.$prodi_id.'/'.$id ?>">
                      <i class="bx bxs-file-pdf me-1"></i>Buku Kurikulum OBE
                    </a>
                  <?php else: ?>
                    <span class="btn btn-sm btn-outline-success disabled">
                      <i class="bx bxs-file-pdf me-1"></i>Buku Kurikulum terkunci
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
