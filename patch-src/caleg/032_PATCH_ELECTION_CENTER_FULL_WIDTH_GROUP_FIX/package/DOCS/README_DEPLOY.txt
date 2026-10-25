032 — ELECTION CENTER FULL WIDTH GROUP FIX

SCOPE
- Event
- Kandidat & Paslon
- Wilayah & Dapil
- DPT / Pemilih

ROUTE
admin/ElectionCenter/*

PERBAIKAN
- Route-specific, tidak global.
- Direct legacy column di root dibuat full width.
- Row dengan satu kolom terlihat dibuat 12/12.
- Nested wrapper chain dengan satu child konten diperluas berulang.
- Begitu parent mempunyai dua atau lebih child terlihat, normalisasi berhenti.
- Card, filter, table, DataTables dan tabs memakai penuh grid cell.

TIDAK DIUBAH
Controller, model, query, database, route, permission, data bisnis.

DEPLOY
031 harus APPLIED.
Upload 032 -> Preflight -> Dry-run -> Apply -> Ctrl+F5.
