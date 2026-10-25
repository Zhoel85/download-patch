030 — COMMAND CENTER FULL WIDTH FIX

MASALAH
Setelah 029, Command Center masih hanya menggunakan sekitar 60-70% area content.
Penyebabnya adalah wrapper parent legacy col-md/offset yang membungkus hampir seluruh isi
Command Center, tetapi berada lebih dalam dari normalizer global 029.

PERBAIKAN
- Hanya aktif jika halaman berisi:
  Coverage Organisasi
  + Tugas / Kegiatan Tertunda
  + form filter.
- Sistem menemukan common ancestor dari ketiga bagian tersebut.
- Hanya common wrapper yang diberi full width 12/12.
- Internal layout dipertahankan:
  KPI = 4 card per row desktop.
  Coverage + Tugas = tetap dua kolom.
- Halaman lain tidak ikut diperbaiki oleh patch ini.

TIDAK DIUBAH
Controller, model, query, route, database, permission, data dashboard.

DEPLOY
029 harus APPLIED.
Upload 030 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
