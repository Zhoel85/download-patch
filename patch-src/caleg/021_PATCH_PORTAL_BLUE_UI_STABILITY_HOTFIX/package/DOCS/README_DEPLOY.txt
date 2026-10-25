021 — PORTAL BLUE UI STABILITY HOTFIX

MASALAH YANG DIPERBAIKI
1. Dropdown sidebar Super Admin tidak merespons setelah patch 020.
2. Hamburger/nav-sm tidak sinkron karena margin topbar dipaksa 230px.
3. Judul legacy tampil lagi di bawah hero sehingga terlihat ganda.
4. Lebar content/sidebar menimbulkan overflow horizontal pada beberapa halaman.
5. Table/panel dapat mendorong lebar halaman.

AKAR MASALAH
Patch 020 mengganti HTML menu setelah event handler Gentelella terpasang.
Node menu baru tidak memiliki handler dropdown lama.

PERBAIKAN
- Dropdown memakai handler mandiri dan tidak bergantung pada binding Gentelella lama.
- Accordion state memakai class .open + aria-expanded.
- nav-md = 230px dan nav-sm = 70px disinkronkan untuk sidebar, topbar, dan content.
- Page title legacy disembunyikan karena sudah digantikan hero.
- Table tetap scroll di dalam .table-responsive, bukan membuat body overflow.
- Cache bust dinaikkan ke v=021.

TIDAK DIUBAH
Database, controller bisnis, route, permission, query, Deployment Safety Center logic, Real Count logic.

DEPLOY
Backup -> Upload 021 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
