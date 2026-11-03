<?php
defined('BASEPATH') or exit('No direct script access allowed');
$s=$preview['stats'] ?? array();
$b=$preview['before'] ?? array();
?>
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h4 class="mb-1">Preflight Kurikulum</h4>
      <div class="text-muted small">Batch #<?= (int)$batch_id ?> · belum diterapkan</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= $base095.'/upload/'.$prodi_id.'/'.$curriculum_id ?>"><i class="bx bx-arrow-back me-1"></i>Ganti JSON</a>
  </div>

  <div class="row g-2 mb-3">
    <?php foreach(array(
      array('KEEP',(int)($s['keep']??0),'success'),
      array('ADD',(int)($s['add']??0),'primary'),
      array('COMPLETE',(int)($s['complete']??0),'info'),
      array('CONFLICT',(int)($s['conflict']??0),'warning')
    ) as $card): ?>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body py-3">
        <div class="small text-muted"><?= $card[0] ?></div>
        <div class="fs-4 fw-semibold"><?= $card[1] ?></div>
      </div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <h6 class="mb-2">Kondisi sebelum Apply</h6>
      <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-label-secondary">Kelengkapan <?= (int)($b['percent']??0) ?>%</span>
        <span class="badge <?= !empty($b['curriculum_complete'])?'bg-label-success':'bg-label-warning' ?>">Context RPS <?= !empty($b['curriculum_complete'])?'READY':'BELUM READY' ?></span>
        <span class="badge bg-label-info">RPS <?= (int)($b['rps']['ready']??0) ?>/<?= (int)($b['rps']['total']??0) ?></span>
      </div>
    </div>
  </div>

  <?php if(!empty($preview['conflicts'])): ?>
  <div class="card border-warning shadow-sm mb-3">
    <div class="card-header"><h6 class="mb-0">Conflict — existing akan dipertahankan</h6></div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>Path</th><th>Existing</th><th>Usulan AI</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach(array_slice($preview['conflicts'],0,100) as $x): ?>
          <tr>
            <td><code><?= html_escape($x['path']??'') ?></code></td>
            <td class="text-wrap"><?= html_escape(is_scalar($x['existing']??'')?(string)$x['existing']:json_encode($x['existing'],JSON_UNESCAPED_UNICODE)) ?></td>
            <td class="text-wrap"><?= html_escape(is_scalar($x['proposed']??'')?(string)$x['proposed']:json_encode($x['proposed'],JSON_UNESCAPED_UNICODE)) ?></td>
            <td><span class="badge bg-label-warning">KEEP_EXISTING</span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="alert alert-primary"><i class="bx bx-info-circle me-1"></i>Apply akan memvalidasi ulang hash Foundation dan Kurikulum. Jika berubah setelah preview, paket ditolak.</div>

  <div class="d-flex justify-content-end gap-2">
    <a class="btn btn-sm btn-outline-secondary" href="<?= $base095.'/prodi/'.$prodi_id ?>">Batal</a>
    <form method="post" action="<?= $base095.'/apply/'.$prodi_id.'/'.$batch_id ?>">
      <?php if(!empty($csrf095['name'])): ?><input type="hidden" name="<?= html_escape($csrf095['name']) ?>" value="<?= html_escape($csrf095['hash']) ?>"><?php endif; ?>
      <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Terapkan hasil preflight ke Kurikulum DRAFT? Existing valid dan conflict tetap dipertahankan.');">
        <i class="bx bx-check-shield me-1"></i>Terapkan ke DRAFT
      </button>
    </form>
  </div>
</div>
