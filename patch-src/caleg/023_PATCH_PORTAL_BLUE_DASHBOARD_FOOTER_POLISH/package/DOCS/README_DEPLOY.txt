023 — PORTAL BLUE DASHBOARD & FOOTER PRODUCTION POLISH

HASIL AUDIT SCREENSHOT
1. Command Center shell sudah mengikuti sisa viewport, tetapi filter/stat cards masih terdorong ke tengah akibat Bootstrap offset legacy.
2. URL root Operations360 belum dikenali metadata UI sehingga hero memakai document title "Halaman Command Center Dashboard".
3. Sidebar belum dipaksa full-height dengan scroll internal yang konsisten.
4. Footer masih memakai visual/geometri template lama dan terlihat terpisah dari Portal Blue.

PERBAIKAN
- Root /admin/Operations360 dan /index dikenali sebagai Command Center.
- Wrapper Bootstrap yang secara visual masih offset pada Command Center dideteksi saat runtime dan dibuat full-width.
- Perubahan full-width hanya berlaku pada Command Center, tidak global.
- Sidebar desktop fixed full-height, scroll terjadi di dalam sidebar.
- Footer mengikuti nav-md 230px / nav-sm 70px.
- Footer diberi border, warna, spacing dan shadow ringan yang konsisten.
- Mobile footer 100% dan text center.
- Cache bust CSS/JS menjadi v=023.

TIDAK DIUBAH
Route bisnis, controller, query, database, permission, dropdown 021, layout geometry 022, Real Count, Deployment Safety Center.

DEPLOY
Pastikan 022 APPLIED.
Backup -> Upload 023 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
