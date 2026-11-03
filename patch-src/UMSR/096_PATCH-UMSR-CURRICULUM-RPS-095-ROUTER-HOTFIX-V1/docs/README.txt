PATCH 096 — UMSR CURRICULUM/RPS 095 ROUTER HOTFIX V1

SCOPE
Hotfix khusus HTTP 500 setelah PATCH 095 pada:
pengguna/bsi/Elearning_rencana

ROOT CAUSE
PATCH 095 memasang _remap() pada controller wrapper lalu memanggil parent::_remap()
secara langsung. Baseline Elearning_plan_controller dapat menggunakan method public
biasa tanpa _remap. Pada baseline tersebut, delegasi menghasilkan fatal runtime dan
seluruh route Elearning_rencana dapat gagal.

FIX
- Tidak mengubah database.
- Tidak mengubah model Curriculum/RPS.
- Tidak mengubah Foundation, Kurikulum, RPS, kelas, jadwal, KRS, nilai atau presensi.
- Legacy route dispatcher kini:
  1) memakai parent _remap bila benar-benar tersedia;
  2) jika tidak, memanggil inherited public method target secara aman;
  3) method private/protected/underscore ditolak;
  4) error workflow 095 dicatat ke log CI untuk audit.

IMPACT CHECK
Route yang sebelumnya berpotensi terkena:
- /pengguna/bsi/Elearning_rencana
- /master/{prodi}
- route RPS legacy
- PDF/JSON RPS
- action legacy lain yang melewati controller Elearning_rencana

Route di luar Elearning_rencana tidak disentuh patch ini.

INSTALL
Pasang langsung di atas PATCH 095. Tidak perlu rollback 095.
