-- 091 UMSR E-Learning BSI Portable ChatGPT + Live Data Guard
-- Deployment Safety Center compatible additive schema.

CREATE TABLE IF NOT EXISTS elearning_bsi_portable_batch (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_jadwal INT NOT NULL,
  periode VARCHAR(20) NOT NULL,
  schema_version VARCHAR(20) NOT NULL DEFAULT '3.0',
  package_hash CHAR(64) NOT NULL,
  lock_json LONGTEXT NOT NULL,
  payload_json LONGTEXT NOT NULL,
  preview_json LONGTEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'PREVIEW',
  created_by VARCHAR(100) NULL,
  created_at DATETIME NOT NULL,
  applied_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_elb_portable_batch_class (id_jadwal, periode, status),
  KEY idx_elb_portable_batch_hash (package_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS elearning_bsi_generated_content (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_jadwal INT NOT NULL,
  id_pertemuan INT NULL,
  slot TINYINT UNSIGNED NULL,
  content_type VARCHAR(20) NOT NULL,
  title VARCHAR(255) NOT NULL,
  content_json LONGTEXT NOT NULL,
  public_token VARCHAR(64) NOT NULL,
  state VARCHAR(30) NOT NULL DEFAULT 'PREPARED',
  source_batch_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_elb_generated_token (public_token),
  KEY idx_elb_generated_class (id_jadwal, content_type, slot),
  KEY idx_elb_generated_batch (source_batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
