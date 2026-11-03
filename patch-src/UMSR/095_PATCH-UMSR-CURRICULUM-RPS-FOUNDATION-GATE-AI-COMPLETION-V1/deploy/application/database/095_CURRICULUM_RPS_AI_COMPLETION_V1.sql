-- 095 Curriculum and RPS OBE AI completion audit schema

CREATE TABLE IF NOT EXISTS elearning_curriculum_ai_import (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  prodi_id INT NOT NULL,
  curriculum_id BIGINT UNSIGNED NOT NULL,
  schema_version VARCHAR(20) NOT NULL DEFAULT '1.0',
  package_hash CHAR(64) NOT NULL,
  foundation_hash CHAR(64) NOT NULL,
  curriculum_hash CHAR(64) NOT NULL,
  package_json LONGTEXT NOT NULL,
  preview_json LONGTEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'PREVIEW',
  created_by VARCHAR(100) NULL,
  created_at DATETIME NOT NULL,
  applied_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_curr_ai_scope (prodi_id, curriculum_id, status),
  KEY idx_curr_ai_hash (package_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
