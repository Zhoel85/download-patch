033 — ELECTION CENTER OUTER WRAPPER FULL WIDTH FIX

HASIL AUDIT SCREENSHOT EVENT
- Tab modul, KPI, Tambah Event dan Daftar Event berhenti pada lebar yang sama.
- Ini menunjukkan pembatas berada pada outer wrapper yang membungkus seluruh
  isi ElectionCenter, bukan pada card individual.
- 032 hanya menormalisasi wrapper single-column berdasarkan struktur row/col,
  sehingga outer wrapper tertentu masih lolos.

PERBAIKAN 033
- Hanya aktif di admin/ElectionCenter/*.
- Cari module navigation berdasarkan link:
  Event / Kandidat / Wilayah-Dapil / DPT.
- Ambil content block pertama dan terakhir.
- Hitung common ancestor dari:
  module nav + content pertama + content terakhir.
- Seluruh ancestor bersama sampai root diberi full-width.
- Naikkan chain lagi bila parent hanya punya satu child konten.
- Internal layout seperti Tambah Event | Daftar Event tetap dua kolom.

TIDAK DIUBAH
Controller, model, query, database, route, permission, data.

DEPLOY
032 harus APPLIED.
Upload 033 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
