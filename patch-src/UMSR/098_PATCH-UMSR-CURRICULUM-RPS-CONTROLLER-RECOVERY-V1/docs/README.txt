PATCH 098 — UMSR CURRICULUM/RPS CONTROLLER RECOVERY V1

TUJUAN
Memulihkan /pengguna/bsi/Elearning_rencana setelah HTTP 500 yang muncul sejak
PATCH 095 dan tetap terjadi setelah hotfix routing 096/097.

HASIL AUDIT
1. Elearning_plan_controller baseline memang memiliki public _remap().
2. Karena itu diagnosis 096/097 mengenai ketiadaan parent _remap adalah salah.
3. Seluruh PHP 095 sudah diuji ulang dengan PHP 7.4 dan syntax PASS.
4. Titik risiko tersisa adalah controller 095 sendiri: wrapper lama diganti menjadi
   controller besar yang sejak constructor memuat model 095 dan mengintersep alur core.
5. Core lama sudah memiliki index/master/RPS/PDF/JSON/business validation sendiri dan
   seharusnya tetap menjadi execution engine.

PERBAIKAN
- Elearning_rencana.php dikembalikan menjadi thin wrapper.
- Tidak ada _remap baru.
- Tidak ada constructor baru.
- Tidak memuat Elearning_curriculum_095_model.
- Tidak ada script injection.
- Tidak ada SQL.
- Core Elearning_plan_controller kembali menangani semua route lama secara langsung.

STATUS FITUR 095
Model/view/tabel tambahan 095 boleh tetap ada, tetapi sementara TIDAK AKTIF dari
Elearning_rencana. Ini disengaja untuk memulihkan layanan dahulu.
Fondasi/AI Curriculum akan diintegrasikan kembali pada patch berikutnya melalui
extension point yang tidak mengganti controller utama.

KONDISI PEMASANGAN
- 095 sudah terpasang.
- 096 sudah di-rollback.
- 097 boleh sudah terpasang; 098 menimpa controller yang sama sehingga tidak perlu
  rollback 097 terlebih dahulu.

UJI SETELAH APPLY
1. Buka /pengguna/bsi/Elearning_rencana
2. Buka satu master Curriculum Prodi
3. Buka satu RPS
4. Coba PDF/JSON RPS
5. Jangan lanjut pengembangan AI Curriculum sebelum semua legacy flow PASS.
