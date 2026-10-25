<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-1">Sneat Template Cutover</h5>
          <small class="text-muted">Validasi asset Sneat v1.0.0 dan kandidat cleanup template lama.</small>
        </div>
        <span class="badge bg-label-primary">PATCH 024</span>
      </div>
      <div class="card-body">
        <div class="alert alert-primary mb-0">
          Patch 024 hanya melakukan cutover template. Penghapusan file legacy dilakukan terpisah melalui Patch 025 setelah dependency scan aman.
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <h5 class="card-header">Sneat Runtime</h5>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr><th>Komponen</th><th>Status</th><th>Ukuran</th></tr>
          </thead>
          <tbody>
          <?php foreach ($status as $row): ?>
            <tr>
              <td><?= html_escape($row['label']); ?></td>
              <td>
                <?php if ($row['exists']): ?>
                  <span class="badge bg-label-success">OK</span>
                <?php else: ?>
                  <span class="badge bg-label-danger">MISSING</span>
                <?php endif; ?>
              </td>
              <td><?= number_format((int) $row['size']); ?> B</td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <h5 class="card-header">Legacy Cleanup Candidates</h5>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr><th>File</th><th>Ada</th><th>Referensi</th></tr>
          </thead>
          <tbody>
          <?php foreach ($legacy as $row): ?>
            <tr>
              <td><?= html_escape($row['label']); ?></td>
              <td><?= $row['exists'] ? '<span class="badge bg-label-warning">ADA</span>' : '<span class="badge bg-label-secondary">TIDAK ADA</span>'; ?></td>
              <td><?= $row['referenced'] ? '<span class="badge bg-label-danger">MASIH DIPAKAI</span>' : '<span class="badge bg-label-success">AMAN</span>'; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
