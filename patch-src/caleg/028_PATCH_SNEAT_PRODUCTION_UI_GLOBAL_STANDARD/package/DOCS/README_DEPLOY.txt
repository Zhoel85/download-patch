028 — SNEAT PRODUCTION UI GLOBAL STANDARD

TUJUAN
Menyatukan seluruh tampilan aplikasi ke satu standar produksi Sneat.

CAKUPAN GLOBAL
- navbar;
- sidebar/menu;
- icon parent dan child menu;
- page heading;
- cards/panels;
- KPI/stat cards;
- filter forms;
- form controls;
- buttons dan action icons;
- tables;
- status badges;
- modals;
- Select2;
- DataTables compatibility;
- empty states;
- footer;
- login;
- responsive desktop/tablet/mobile.

SWEETALERT
- SweetAlert2 dipin ke v11.26.25.
- Flashdata pesan/gagal/success/error/warning/info ditampilkan secara terpusat.
- Aksi link/form Hapus, Rollback/Pulihkan, dan Finalisasi mendapat konfirmasi SweetAlert.
- Wrapper global tersedia:
  window.SARAlert
  window.UMSRAlert
- Jika CDN SweetAlert gagal, sistem fallback ke alert/confirm browser agar alur tidak terblokir.

PRINSIP KEAMANAN
- Tidak mengganti controller bisnis.
- Tidak mengganti query.
- Tidak mengganti route.
- Tidak mengganti permission.
- Tidak mengubah database.
- Markup lama dipertahankan lalu dinormalisasi secara non-destruktif.

DEPLOY
027 harus APPLIED.
Backup -> Upload 028 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
