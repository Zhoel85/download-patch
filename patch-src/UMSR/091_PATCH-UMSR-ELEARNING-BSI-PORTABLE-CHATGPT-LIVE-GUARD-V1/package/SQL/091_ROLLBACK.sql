-- 091 ROLLBACK
-- Jalankan HANYA bila patch 091 benar-benar akan dibatalkan.
-- Tabel di bawah hanya milik patch 091. Data operasional E-Learning lama tidak disentuh.
DROP TABLE IF EXISTS elearning_bsi_generated_content;
DROP TABLE IF EXISTS elearning_bsi_portable_batch;
