024 — SNEAT v1.0.0 FULL TEMPLATE CUTOVER

TUJUAN
Mengganti shell template aplikasi secara nyata dari Gentelella/Portal Blue menjadi Sneat v1.0.0.

SUMBER TEMPLATE
Sneat Bootstrap HTML Admin Template Free v1.0.0
Official ThemeSelection repository commit/tag: 40c50e6
License files disertakan di assets/sneat/license.
Attribution ThemeSelection dipertahankan di footer.

CARA KERJA
1. FinalNavigationHook digantikan SneatTemplateHook.
2. Output halaman lama dibaca server-side.
3. Shell Gentelella (left_col, top_nav, right_col, footer) tidak diteruskan ke browser.
4. Isi bisnis diambil dan ditempatkan di Sneat content wrapper.
5. Menu Super Admin dibangun sebagai Sneat menu canonical.
6. Role non-Super Admin mempertahankan menu lama dengan translator server-side ke markup Sneat.
7. Asset template lama tidak lagi dimuat.
8. Plugin bisnis seperti Select2/DataTables/SweetAlert tetap dipertahankan bila direferensikan.
9. Compatibility JS menjembatani Bootstrap 3 style data-toggle/modal API ke Bootstrap 5.

PENTING
Patch 024 belum menghapus file lama secara fisik untuk menjaga rollback.
Patch 025 melakukan dependency-safe purge setelah 024 lulus UAT.

PRASYARAT
- Patch 023 APPLIED.
- PHP 7.4.
- DOM extension direkomendasikan. Fallback tersedia jika DOMDocument tidak ada.
- ZipArchive hanya diperlukan Deployment Safety Center seperti biasa.

DEPLOY
Backup -> Upload 024 -> Preflight -> Dry-run -> Apply -> logout/login -> Ctrl+F5.

UJI PERTAMA
1. Login.
2. Command Center.
3. Sistem -> Deployment Safety Center.
4. Event/DPT.
5. Organisasi.
6. Saksi & Real Count.
7. Modal, dropdown, Select2, SweetAlert, DataTables.
8. Mobile.
9. Sneat Migration Center.
