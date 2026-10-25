031 — COMMAND CENTER WRAPPER DETECTION FIX

HASIL AUDIT TEKNIS
Source 029:
  /(^|\s)col-...-\d+/
  BENAR.

Source 030 yang terbit:
  /(^|\\s)...\\d+/
  SALAH untuk JavaScript regex literal.

Dampak:
wrapper legacy seperti col-md-8 tidak pernah dikenali oleh 030.
Karena itu card/filter/KPI tetap berhenti di sekitar 8/12 workspace dan
sisi kanan layar kosong.

PERBAIKAN 031
- Tidak lagi memakai regex class untuk common wrapper Command Center.
- commonAncestors() mencari node yang nyata-nyata membungkus:
  1. form filter,
  2. Coverage Organisasi,
  3. Tugas / Kegiatan Tertunda.
- Semua common ancestor di antara konten itu dan root Command Center diberi
  sar-command-common-full.
- CSS !important menetralkan width/flex/margin legacy hanya di Command Center.

SCOPE
Hanya Command Center.
Tidak mengubah halaman lain, controller, query, database, permission atau data.

DEPLOY
030 harus APPLIED.
Upload 031 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
