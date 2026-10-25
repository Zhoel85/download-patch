026 — BACKUP CLEANUP & RETENTION CENTER

TUJUAN
Mengurangi ukuran source aplikasi dengan membersihkan backup lama secara aman.

LOKASI YANG DIPANTAU
- application/backups/deployment/
- application/backups/
- application/backups_private/

PROTEKSI DEFAULT
- Retention window: 30 hari
- 3 source backup terbaru
- 3 database backup terbaru
- 5 deployment backup terbaru
- 1 baseline terbaru
- 1 backup Sneat terbaru
- symlink tidak pernah dihapus
- item bertanda .keep atau critical tidak dihapus otomatis

ALUR
Sistem -> Backup & Retention
-> atur policy
-> Preview Cleanup
-> periksa kandidat
-> ketik HAPUS BACKUP LAMA
-> Bersihkan Backup Lama

PENGAMAN
- hanya Super Admin
- POST + CSRF
- preview tidak mengubah file
- Apply menghitung ulang kandidat
- path divalidasi harus berada di root backup allowlist
- root backup tidak pernah dapat dihapus
- symlink diblokir
- histori cleanup disimpan ke database

CATATAN PRODUKSI
Backup penting idealnya dipindahkan keluar public_html/source aplikasi setelah proses operasional stabil.
