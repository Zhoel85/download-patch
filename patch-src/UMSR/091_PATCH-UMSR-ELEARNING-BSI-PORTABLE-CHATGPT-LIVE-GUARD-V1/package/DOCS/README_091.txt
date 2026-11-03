PATCH 091 — UMS RAPPANG
E-LEARNING BSI PORTABLE CHATGPT + LIVE DATA GUARD V1

TUJUAN
Menambahkan workflow Portable ChatGPT langsung di dalam kelas BSI tanpa mengubah
tampilan dasar kelas bersama Dosen.

ALUR
1. BSI membuka kelas.
2. Klik Unduh Perintah.
3. File PERINTAH_CHATGPT_ELEARNING_{ID}_{PERIODE}.txt dikerjakan di ChatGPT.
4. ChatGPT mengembalikan satu JSON UMSR_ELB_PACKAGE v3.0.
5. BSI klik Upload JSON.
6. Portal menjalankan validasi SELURUH paket dan menampilkan Preview/Live Data Guard.
7. BSI menekan Terapkan Paket.
8. Hanya bagian aman yang diisi.

YANG DAPAT DIISI
- Tepat 16 pertemuan.
- Materi pembelajaran per slot reguler.
- 14 tugas reguler sebagai PREPARED bila belum ada deadline nyata.
- UTS/UAS minimal 50 PG.
- Soal dapat ditempel ke quiz existing hanya bila quiz belum memiliki soal/jawaban/nilai.
- Mapping Sub-CPMK/OBE tetap dibawa dalam paket/staging.
- Workload SKS x 45 jam; active student learning minimal 60%.

LIVE DATA GUARD
PATCH 091 tidak melakukan INSERT/UPDATE/DELETE terhadap:
- monitoring/presensi;
- elearning_pengumpulan;
- elearning_quiz_jawaban;
- elearning_quiz_nilai;
- KRS/mahasiswa;
- jadwal/dosen pengampu;
- nilai/feedback;
- dokumentasi pelaksanaan;
- tanggal real perkuliahan;
- waktu ujian atau deadline tugas.

ATURAN EXISTING DATA
- Pertemuan existing: KEEP_EXISTING.
- Materi existing: PROTECTED_EXISTING.
- Tugas existing: PROTECTED_EXISTING.
- Quiz existing yang sudah memiliki soal/jawaban/nilai: PROTECTED_EXISTING.
- Tugas baru tanpa deadline real: PREPARED, tidak dibuat deadline palsu.
- UTS/UAS belum mempunyai jadwal: PREPARED_NEEDS_SCHEDULE.

DEPENDENSI
086 -> 087 -> 089 -> 090 -> 091

INSTALL
1. Backup database dan application.
2. Scan patch melalui Deployment Safety Center.
3. Jalankan SQL/091_ELEARNING_BSI_PORTABLE_V3_INSTALL.sql bila SQL installer patch
   tidak dijalankan otomatis oleh mekanisme deployment Anda.
4. Pastikan SQL/091_VERIFY.sql menghasilkan PASS.
5. Login BSI role 17.
6. Buka satu kelas pilot dan lihat card Persiapan Konten BSI.

CATATAN UI
Patch tidak mengubah views/dosen/elearning/kelas.php dan tidak mengubah controller
dosen/Elearning.php. UI BSI ditambahkan melalui slot khusus:
views/pengguna/bsi/elearning/_bsi_feature_slot.php.

SECURITY
- Mutation apply hanya POST + CSRF CI3.
- Upload maksimal 5 MB dan wajib .json.
- Hash Foundation/Kurikulum/RPS/week divalidasi ulang sebelum apply.
- Paket kelas/periode lain ditolak.
- Materi generated dilayani oleh endpoint token dan tetap memeriksa role/KRS/pengampu.

ROLLBACK
SQL/091_ROLLBACK.sql hanya menghapus dua tabel milik patch 091.
Jangan rollback setelah paket telah dipakai tanpa backup/audit terlebih dahulu.
ROLLBACK_FILES mengembalikan slot BSI menjadi kosong seperti baseline 086.
