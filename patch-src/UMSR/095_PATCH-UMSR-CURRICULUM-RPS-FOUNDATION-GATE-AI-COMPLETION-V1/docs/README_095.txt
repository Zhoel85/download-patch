PATCH 095 — UMS RAPPANG
CURRICULUM & RPS OBE — FOUNDATION GATE + AI COMPLETION V1

TUJUAN
1. Menjadikan Fondasi Akademik Prodi sebagai gate wajib sebelum Kurikulum dan RPS OBE dapat dilanjutkan.
2. Mengunci Kurikulum DRAFT ke Foundation ACTIVE melalui id/revision/hash.
3. Menambahkan AI Curriculum Assistant berbasis TXT -> ChatGPT -> JSON -> Preflight/Diff -> Apply ke DRAFT.
4. Mempertahankan data existing yang valid dan menolak overwrite diam-diam.
5. Memblokir RPS baru sampai Context Kurikulum siap.
6. Mengaktifkan download Buku Kurikulum OBE hanya setelah Kurikulum dan seluruh RPS OBE lengkap.

PRINSIP
- PL dan PEO hanya berasal dari Foundation Prodi.
- Kurikulum ACTIVE/APPROVED/PUBLISHED bersifat read-only.
- Perubahan versi aktif dilakukan melalui clone ke DRAFT.
- AI hanya boleh ADD/COMPLETE. Perbedaan non-empty menjadi CONFLICT dan KEEP_EXISTING.
- Hash Foundation dan Kurikulum diperiksa saat upload dan kembali diperiksa saat apply.
- Workload UMS Rappang: SKS x 45 jam per semester; active student learning minimal 60%.
- Apply AI berjalan dalam transaksi.
- Tidak ada perubahan pada data mahasiswa, KRS, nilai, presensi, jadwal, dosen, submission, atau kelas live.

GATE FOUNDATION
Sebuah Prodi tidak dapat masuk ke workflow Kurikulum/RPS bila Foundation ACTIVE belum lengkap.
Pemeriksaan meliputi identitas Prodi, field Fondasi yang tersedia pada schema, Profil Lulusan, PEO, dan mapping PEO-PL bila tabel mapping tersedia.

GATE CONTEXT RPS
RPS baru hanya dapat dilanjutkan bila versi Kurikulum memiliki:
- Foundation lock current
- CPL
- BOK/Bahan Kajian
- Struktur Mata Kuliah
- pembebanan CPL per MK
- I/R/M/A
- CPMK
- Sub-CPMK
- Assessment Blueprint
- Signature Output
- workload valid
- official reference

FINAL BOOK GATE
Download Buku Kurikulum OBE final aktif setelah:
- seluruh gate Kurikulum lulus; dan
- seluruh mata kuliah memiliki RPS OBE PUBLISHED.

FILES
- controllers/pengguna/bsi/Elearning_rencana.php
- models/Elearning_curriculum_095_model.php
- views/elearning_plan/foundation_gate_095.php
- views/elearning_plan/curriculum_ai_upload_095.php
- views/elearning_plan/curriculum_ai_preview_095.php
- views/elearning_plan/curriculum_ai_history_095.php
- database/095_CURRICULUM_RPS_AI_COMPLETION_V1.sql

DEPENDENSI ARSITEKTUR
- Academic Foundation Prodi 089/090
- Curriculum Engine / RPS OBE existing
- dompdf_gen existing untuk PDF

CATATAN DEPLOYMENT
ZIP native hanya berisi application/.
Migration hanya membuat tabel audit AI Curriculum secara additive.
