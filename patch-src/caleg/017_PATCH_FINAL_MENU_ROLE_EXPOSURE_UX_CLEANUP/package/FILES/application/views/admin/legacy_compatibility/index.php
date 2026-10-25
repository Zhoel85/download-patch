<div class="col-md-12 col-sm-12">
    <div class="x_panel">
        <div class="x_title">
            <h2>Legacy / Compatibility</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <div class="alert alert-warning">
                <strong>Mode transisi.</strong>
                Menu di bawah dipertahankan hanya untuk kompatibilitas. Gunakan modul canonical di kolom
                <em>Pengganti Produksi</em> untuk pekerjaan baru. Patch ini belum menghapus controller atau tabel legacy.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Fungsi Legacy</th>
                            <th>Pengganti Produksi</th>
                            <th>Status</th>
                            <th>Akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($legacy_links as $row) : ?>
                            <tr>
                                <td><?= html_escape($row['label']); ?></td>
                                <td><?= html_escape($row['replacement']); ?></td>
                                <td><span class="label label-warning">Compatibility</span></td>
                                <td>
                                    <a class="btn btn-xs btn-default" href="<?= base_url($row['route']); ?>">
                                        Buka Legacy
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="text-muted">
                Penghapusan fisik hanya dilakukan setelah Dependency Monitor pada patch berikutnya membuktikan
                tidak ada akses baca/tulis yang masih dibutuhkan.
            </p>
        </div>
    </div>
</div>
