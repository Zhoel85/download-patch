029 — SNEAT GLOBAL CONTENT LAYOUT & SPACING STANDARD

HASIL AUDIT
- container-xxl Sneat membatasi workspace pada layar lebar;
- beberapa view legacy masih mempunyai wrapper col-md-* / offset lama;
- filter/select/tombol terlalu rapat;
- card/KPI hanya memakai sebagian workspace;
- DataTables dan action button belum mempunyai ritme spacing yang sama.

PERBAIKAN GLOBAL
1. Main navbar/content/footer menggunakan seluruh sisa workspace.
2. Row legacy yang hanya memiliki satu kolom otomatis menjadi full-width.
3. Row dua/lebih kolom tetap mempertahankan pembagian kolom.
4. Card/panel/well memakai penuh grid cell.
5. Filter compact memakai gap profesional dan alignment bottom.
6. Select/input menjadi tinggi konsisten 42px.
7. Select2 single/multiple diselaraskan.
8. Button spacing dan table action dirapikan.
9. KPI grid memenuhi lebar content.
10. DataTables search/length/pagination dirapikan.
11. Modal footer/action spacing dirapikan.
12. Mobile menumpuk dengan aman.

PRINSIP
Tidak mengubah controller bisnis, route, query, database atau permission.

DEPLOY
028 harus APPLIED.
Backup -> Upload 029 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
