CREATE TABLE IF NOT EXISTS `backup_retention_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `deleted_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `freed_bytes` VARCHAR(32) NOT NULL DEFAULT '0',
  `policy_json` TEXT NULL,
  `detail_json` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_backup_retention_created` (`created_at`),
  KEY `idx_backup_retention_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
