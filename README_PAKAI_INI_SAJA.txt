004 SDGs STORAGE FILES - WEB UTAMA / STAGING

Tujuan:
Memperbaiki UAT Phase 09:
- Storage SDGs tersedia & writable
- Direct access storage diblokir

Isi:
asset/files_sdgs/.htaccess
asset/files_sdgs/index.html

Pemasangan:
1. Download ZIP branch ini.
2. Extract.
3. Upload folder asset/files_sdgs/ ke root web staging, sejajar application/, system/, index.php.
4. Pastikan .htaccess ikut ter-upload (hidden file).
5. Permission folder mulai dari 755; gunakan 775 hanya bila PHP belum dapat menulis.
6. Refresh SDGs -> UAT & Go-Live.

Ini adalah STORAGE FIX PACKAGE dari Phase 04, bukan installer ulang seluruh Phase 04.
