019 — FINAL HARDENING, PERFORMANCE, CONCURRENCY & UAT GATE

PREREQUISITE
018_PATCH_LEGACY_FREEZE_DEPENDENCY_MONITOR_CLEANUP harus APPLIED dan UAT 018 selesai.

PERUBAHAN KRITIS
1. Election Day menghitung TPS berdasarkan election_event_tps bila mapping tersedia.
2. Real Count recap hanya menghitung baris milik header VERIFIED.
3. Total TPS Big Screen/Real Count mengikuti scope event.
4. Finalisasi Saksi memperoleh lock unik Event+TPS melalui real_count_tps_registry.
5. Rate limiting untuk endpoint finalisasi, incident, verifikasi, import apply/rollback dan export.
6. Security headers + no-store untuk sesi authenticated.
7. Index tambahan untuk query Hari H, DPT, Organisasi dan Live Activity.
8. Protected upload directories.
9. Production Readiness GO / CONDITIONAL / NO-GO.

ATURAN GO-LIVE
- NO-GO: produksi DILARANG dibuka.
- CONDITIONAL: blocker 0, warning wajib direview dan dicatat.
- GO: gate otomatis lulus, tetapi UAT manual tetap wajib.

DEPLOY
Deployment Safety Center -> Backup Source -> Backup DB -> Upload 019 -> Dry-run -> Apply.
Setelah Apply, buka Sistem -> Production Readiness.
