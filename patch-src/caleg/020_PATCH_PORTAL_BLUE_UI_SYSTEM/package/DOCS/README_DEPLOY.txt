020 — PORTAL BLUE UI SYSTEM & PRODUCTION PAGE LAYOUT

TUJUAN
Mengubah tampilan aplikasi menjadi satu bahasa visual modern seperti referensi Portal Akademik:
- sidebar putih;
- kartu role;
- topbar ringan;
- hero/header halaman;
- tab modul;
- card, filter, table, badge, button, modal;
- mobile responsive.

SCOPE
- Tidak mengubah controller bisnis.
- Tidak mengubah query.
- Tidak mengubah permission.
- Tidak mengubah route.
- Tidak menghapus data.
- Tidak mengubah tabel database.

FILE RUNTIME
1. application/hooks/FinalNavigationHook.php
2. assets/css/campaign-portal-v2.css
3. assets/js/campaign-portal-v2.js

CARA DEPLOY
1. Pastikan patch 019 APPLIED.
2. Backup source.
3. Upload 020 ke Deployment Safety Center.
4. Preflight.
5. Dry-run.
6. Apply.
7. Clear browser cache / hard refresh.
8. UAT desktop dan mobile.

CATATAN
Rollback mengembalikan FinalNavigationHook. File CSS/JS tambahan boleh tetap berada di server karena tidak lagi dipanggil setelah rollback.
