PATCH 097 — UMSR CURRICULUM/RPS 095 STABLE INTEGRATION V1

TUJUAN
Memperbaiki HTTP 500 pada pengguna/bsi/Elearning_rencana setelah PATCH 095
tanpa memakai _remap() dan tanpa mengambil alih routing seluruh controller.

KONDISI PEMASANGAN
- PATCH 095 tetap terpasang.
- PATCH 096 sudah di-rollback seperti keputusan pengujian.
- Pasang 097 langsung di atas kondisi tersebut.

AKAR MASALAH
095 mengganti controller pusat Elearning_rencana dan mengintersep seluruh route
melalui _remap(). Karena controller ini menjadi pintu semua Curriculum/RPS, satu
ketidakcocokan dispatcher dapat menjatuhkan index, master Prodi, RPS, PDF/JSON,
dan action legacy sekaligus.

PERBAIKAN 097
- Menghapus penggunaan _remap() dari integrasi 095.
- Route legacy kembali didispatch CI3 secara normal melalui public method parent.
- index() dan master($prodiId) saja yang dibungkus secara eksplisit.
- master Prodi tetap HARD GATE: Foundation ACTIVE dan lengkap wajib tersedia.
- AI Curriculum endpoints menjadi public method eksplisit:
  foundation_status
  foundation_bind
  ai_curriculum_command
  ai_curriculum_upload
  ai_curriculum_preview
  ai_curriculum_apply
  ai_curriculum_history
  ai_curriculum_revision
  curriculum_book
- Error fitur AI 095 dicatat ke log CodeIgniter.
- Tidak ada SQL.
- Tidak mengubah Foundation/Kurikulum/RPS existing.

BATAS DAMPAK
Hanya:
application/controllers/pengguna/bsi/Elearning_rencana.php

TIDAK MENYENTUH
- E-Learning Dosen
- KRS
- Jadwal
- Presensi
- Nilai
- Submission
- Mahasiswa
- controller core Elearning_plan_controller
- database

PENGUJIAN WAJIB
1. /pengguna/bsi/Elearning_rencana -> harus terbuka.
2. Prodi Foundation belum lengkap -> halaman gate, tidak masuk Curriculum Engine.
3. Prodi Foundation lengkap -> Curriculum Engine lama terbuka.
4. AI Curriculum Assistant muncul pada master.
5. RPS lama tetap dapat dibuka dari engine.
