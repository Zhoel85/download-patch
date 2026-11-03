PATCH 095 V2 — CURRICULUM & RPS AI SAFE EXTENSION
=====================================================

PRINSIP UTAMA
Patch ini dibuat ulang setelah rollback 095 lama.
Patch ini TIDAK mengganti controller Kurikulum/RPS existing.

FILE EXISTING YANG DIUBAH
0 file.

FILE LEGACY YANG TIDAK DISENTUH
- application/controllers/pengguna/bsi/Elearning_rencana.php
- application/core/Elearning_plan_controller.php
- application/config/routes.php
- menu global
- controller/model/view RPS existing

FILE BARU
- application/controllers/pengguna/bsi/Curriculum_ai.php
- application/models/Curriculum_ai_095_model.php
- application/views/elearning_plan/curriculum_ai_safe/*
- application/database/095_CURRICULUM_AI_SAFE_EXTENSION_V2.sql

ENTRY UAT
/pengguna/bsi/Curriculum_ai

HEALTH CHECK
/pengguna/bsi/Curriculum_ai/health

TUJUAN
1. Foundation ACTIVE + lengkap menjadi gate pada modul baru ini.
2. PL/PEO hanya dibaca dari Foundation.
3. Kurikulum ACTIVE tidak diedit AI; dibuat revisi DRAFT.
4. DRAFT dapat dikunci ke Foundation ACTIVE.
5. Download perintah TXT -> ChatGPT -> JSON.
6. Upload JSON -> Preflight -> KEEP/ADD/COMPLETE/CONFLICT.
7. Apply hanya ke DRAFT dan menggunakan transaksi.
8. Foundation hash + Curriculum hash divalidasi ulang saat Apply.
9. Context RPS diperiksa dari CPL/BOK/MK/I-R-M/A/CPMK/Sub-CPMK/Assessment/
   Signature Output/Workload/Referensi.
10. Buku Kurikulum OBE baru terbuka jika Kurikulum dan RPS PUBLISHED lengkap.

BATAS KEAMANAN
- Modul lama /pengguna/bsi/Elearning_rencana harus tetap bisa dibuka walaupun
  modul baru ini gagal.
- Tidak ada _remap baru.
- Tidak ada override Elearning_plan_controller.
- Tidak ada route global baru.
- Tidak ada UPDATE massal.
- Tidak ada DELETE/DROP.
- Tidak menyentuh KRS, jadwal, presensi, nilai, submission atau E-Learning Dosen.

CATATAN HARD GATE
Gate bersifat backend penuh untuk seluruh action AI Curriculum V2.
Pada halaman daftar Prodi V2, Prodi Foundation belum lengkap tidak mempunyai
tombol untuk lanjut ke Curriculum Engine.

Karena patch ini sengaja tidak memodifikasi router/controller legacy, URL legacy
yang diketik manual tetap merupakan tanggung jawab engine existing. Setelah UAT
V2 stabil, integrasi menu/legacy hard-gate dapat dibuat sebagai patch kecil
terpisah terhadap source live yang sudah diverifikasi byte-for-byte.

URUTAN UAT
1. Pastikan /pengguna/bsi/Elearning_rencana masih normal SEBELUM membuka modul V2.
2. Buka /pengguna/bsi/Curriculum_ai/health.
3. Pastikan legacy_controller_modified=false dan checks penting true.
4. Buka /pengguna/bsi/Curriculum_ai.
5. Prodi Foundation belum lengkap harus TERKUNCI.
6. Prodi lengkap dapat masuk halaman Prodi.
7. ACTIVE -> Buat Revisi & Lengkapi AI.
8. DRAFT -> Ikat Foundation ACTIVE.
9. Unduh Perintah.
10. Upload JSON -> Preview.
11. Pastikan conflict KEEP_EXISTING.
12. Apply dan verifikasi DRAFT.
13. Pastikan /pengguna/bsi/Elearning_rencana tetap normal setelah seluruh UAT.
