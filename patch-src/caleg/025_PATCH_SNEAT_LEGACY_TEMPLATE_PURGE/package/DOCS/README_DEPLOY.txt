025 — SNEAT LEGACY TEMPLATE DEPENDENCY-SAFE PURGE

TUJUAN
Membersihkan sisa template lama setelah Sneat 024 stabil, tanpa menghapus library bisnis secara membabi buta.

MEKANISME
1. Buka Sistem -> Sneat Migration Center.
2. Sistem menampilkan candidate legacy + status dependency.
3. Klik "Bersihkan Template Lama".
4. File yang masih direferensikan = KEEP.
5. File yang aman = COPY ke application/backups/sneat_template_cleanup_025/files/ lalu REMOVE dari public/runtime.
6. Manifest cleanup disimpan untuk restore.

KANDIDAT
- Portal Blue CSS/JS.
- FinalNavigationHook.
- Gentelella custom.css/custom.js.
- Bootstrap 3 template CSS/JS.
- jQuery template lama.
- FastClick.
- NProgress.
- iCheck.

TIDAK DIHAPUS OTOMATIS
- FontAwesome jika masih dipakai content.
- DataTables.
- Select2.
- SweetAlert.
- chart/map/plugin bisnis.
- Sidebar legacy PHP yang masih menjadi sumber metadata menu role non-Super Admin.
  Sidebar tersebut tidak dirender sebagai template setelah 024.

ROLLBACK
Sebelum rollback 024:
1. Buka Sneat Migration Center.
2. Klik Pulihkan Backup Legacy.
3. Pastikan file kembali.
4. Baru rollback 024 bila diperlukan.

CATATAN
Patch 025 sendiri tidak mengubah database.
