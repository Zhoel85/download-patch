022 — PORTAL BLUE LAYOUT GEOMETRY HOTFIX

TUJUAN
Memperbaiki ruang kosong besar antara sidebar dan konten setelah 021.

AKAR MASALAH
Lebar/margin bawaan Gentelella dan override Portal Blue masih saling bertumpuk.
Topbar dan right_col tidak dipaksa menggunakan sisa viewport secara eksplisit.

PERBAIKAN
- nav-md: sidebar 230px; topbar + right_col = calc(100% - 230px)
- nav-sm: sidebar 70px; topbar + right_col = calc(100% - 70px)
- float/left/right legacy dinetralkan pada topbar/right_col
- hero dan module tabs dipaksa 100% dari content area
- row Bootstrap dinormalisasi agar tidak membuat offset tambahan
- mobile <=991px kembali 100% width

TIDAK DIUBAH
Dropdown/menu 021, route, controller, database, query, permission, Real Count, Deployment Safety Center logic.

DEPLOY
021 harus sudah APPLIED.
Upload -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
