018 — LEGACY FREEZE, DEPENDENCY MONITOR & SAFE CLEANUP

PREREQUISITE: 017 harus APPLIED.

PRINSIP:
HIDE -> READ ONLY -> MIGRATE -> MONITOR -> 0 WRITE -> 0 REQUIRED READ -> BACKUP -> DELETE/ARCHIVE.

018 TIDAK DROP data_user, data_anggota, real_count, saksi, controller legacy, atau data bisnis.
Legacy Real Count dan Saksi: READ_ONLY.
Admin/Role dan field-role legacy: MONITOR.
Dependency Monitor tidak merekam password/NIK/payload POST.

Deploy: Deployment Safety Center -> Upload -> Dry-run -> Apply.
