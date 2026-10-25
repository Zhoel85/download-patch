027 — BACKUP RETENTION SNEAT RENDER FIX

GEJALA
Backup & Retention tampil sebagai HTML polos tanpa sidebar/navbar/Sneat.

AKAR MASALAH
Controller 026 hanya memanggil:
  admin/backup_retention/index

SneatTemplateHook hanya melakukan cutover jika output adalah dokumen HTML lengkap
yang memiliki <html> dan <body>. Karena output 026 hanya fragment, hook tidak jalan.

PERBAIKAN
Render sekarang mengikuti pola controller admin aplikasi:
  templates/header
  templates/sidebar
  admin/backup_retention/index
  templates/footer

SneatTemplateHook kemudian membungkus output penuh menjadi layout Sneat.

TIDAK DIUBAH
- algoritme retention;
- inventory backup;
- ukuran file/folder;
- candidate delete;
- protected backup;
- cleanup;
- audit log;
- database;
- route.

DEPLOY
Pastikan 026 APPLIED.
Upload 027 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
