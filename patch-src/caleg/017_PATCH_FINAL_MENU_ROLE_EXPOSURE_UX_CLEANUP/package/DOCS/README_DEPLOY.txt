017 — FINAL MENU, ROLE EXPOSURE & UX CLEANUP

PREREQUISITE
016_PATCH_PHASE022_MOBILE_UX_PWA harus APPLIED.

TUJUAN
- Merapikan navigasi Super Admin berdasarkan alur kerja, bukan nama controller.
- Menampilkan context Event Aktif pada sidebar.
- Menurunkan menu legacy dari menu utama tanpa menghapus source/route.
- Menyediakan Legacy / Compatibility Center sebagai jalur transisi.
- Tidak mengubah data bisnis.

MENU PRODUKSI
1. Command Center
2. Pemilihan & Data
   - Event
   - Kandidat & Paslon
   - Wilayah & Dapil
   - DPT / Pemilih
3. Operasional
   - Organisasi
   - Anggota & Relawan
   - Kegiatan & Timeline
   - Peta Operasional
4. Hari H
   - Saksi 360
   - Election Day
   - Incident Center
   - Real Count
   - Live Activity
   - Big Screen
5. Laporan & Analytics
6. Sistem
   - User, Role & Permission
   - Import / Export
   - Audit & Privasi
   - Deployment Safety Center
   - Database Normalization
   - Legacy / Compatibility

CATATAN KEAMANAN
Patch 017 TIDAK menghapus controller, tabel, atau data legacy.
Navigasi ringkas diterapkan melalui display hook hanya untuk Super Admin legacy hak_akses=1.
Role lain tetap memakai menu masing-masing sampai UAT role selesai.

DEPLOY
Deployment Safety Center -> Upload -> Dry-run -> Apply.
