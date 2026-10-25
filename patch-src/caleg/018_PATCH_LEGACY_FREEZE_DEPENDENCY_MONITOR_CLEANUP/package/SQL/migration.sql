-- 018 LEGACY FREEZE, DEPENDENCY MONITOR & SAFE CLEANUP
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS legacy_freeze_rules (
 resource_code VARCHAR(80) NOT NULL,
 mode VARCHAR(20) NOT NULL DEFAULT 'MONITOR',
 description VARCHAR(255) NOT NULL DEFAULT '',
 replacement_module VARCHAR(160) NOT NULL DEFAULT '',
 cleanup_status VARCHAR(30) NOT NULL DEFAULT 'MONITOR',
 updated_by VARCHAR(64) DEFAULT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 PRIMARY KEY(resource_code),
 KEY idx_legacy_mode(mode),
 KEY idx_legacy_cleanup(cleanup_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS legacy_dependency_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 resource_code VARCHAR(80) NOT NULL,
 access_type VARCHAR(10) NOT NULL,
 http_method VARCHAR(10) NOT NULL,
 request_uri VARCHAR(255) NOT NULL,
 actor_type VARCHAR(20) NOT NULL DEFAULT '',
 actor_id VARCHAR(64) NOT NULL DEFAULT '',
 ip_address VARCHAR(64) NOT NULL DEFAULT '',
 created_at DATETIME NOT NULL,
 PRIMARY KEY(id),
 KEY idx_legacy_resource_created(resource_code,created_at),
 KEY idx_legacy_access_created(access_type,created_at),
 KEY idx_legacy_actor_created(actor_type,actor_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS legacy_freeze_history (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 resource_code VARCHAR(80) NOT NULL,
 old_mode VARCHAR(20) NOT NULL,
 new_mode VARCHAR(20) NOT NULL,
 actor_id VARCHAR(64) NOT NULL DEFAULT '',
 created_at DATETIME NOT NULL,
 PRIMARY KEY(id),
 KEY idx_legacy_history_resource(resource_code,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO legacy_freeze_rules
(resource_code,mode,description,replacement_module,cleanup_status,created_at,updated_at)
VALUES
('LEGACY_REAL_COUNT','READ_ONLY','Quick Count/Real Count legacy','Real Count Engine','FREEZE_WRITE',NOW(),NOW()),
('LEGACY_SAKSI','READ_ONLY','CRUD dan alur saksi legacy','Saksi 360','FREEZE_WRITE',NOW(),NOW()),
('LEGACY_ADMIN_ROLE','MONITOR','CRUD role/admin legacy','User Role Permission + Organisasi','MONITOR',NOW(),NOW()),
('LEGACY_FIELD_ROLE','MONITOR','Korkab/Korcam/Kordes/Tim/Relawan legacy','Mobile Workspace + Organisasi','MONITOR',NOW(),NOW()),
('LEGACY_DATA_USER','MONITOR','Identitas admin legacy data_user','persons + accounts + role_assignments','MONITOR',NOW(),NOW()),
('LEGACY_DATA_ANGGOTA','MONITOR','Identitas anggota legacy data_anggota','persons + accounts + assignments','MONITOR',NOW(),NOW()),
('LEGACY_MASTER_KABUPATENS','MONITOR','Master kabupaten plural legacy','Canonical wilayah','MONITOR',NOW(),NOW());

INSERT INTO auth_permissions
(permission_code,module_code,action_code,permission_name,description,is_active,created_at)
VALUES
('SYSTEM.LEGACY_MONITOR','SYSTEM','LEGACY_MONITOR','Kelola Legacy Dependency Monitor','Melihat dependency dan safe freeze jalur legacy',1,NOW())
ON DUPLICATE KEY UPDATE permission_name=VALUES(permission_name),description=VALUES(description),is_active=1;

INSERT IGNORE INTO auth_role_permissions(role_id,permission_id,created_at)
SELECT r.id,p.id,NOW() FROM auth_roles r JOIN auth_permissions p
WHERE r.role_code='SUPER_ADMIN' AND p.permission_code='SYSTEM.LEGACY_MONITOR';
