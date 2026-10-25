-- 017 FINAL MENU, ROLE EXPOSURE & UX CLEANUP
SET NAMES utf8mb4;

INSERT INTO auth_permissions
(permission_code,module_code,action_code,permission_name,description,is_active,created_at)
VALUES
('SYSTEM.LEGACY_VIEW','SYSTEM','LEGACY_VIEW','Lihat Legacy Compatibility','Akses pusat kompatibilitas legacy untuk masa transisi',1,NOW())
ON DUPLICATE KEY UPDATE
permission_name=VALUES(permission_name),
description=VALUES(description),
is_active=1;

INSERT IGNORE INTO auth_role_permissions (role_id,permission_id,created_at)
SELECT r.id,p.id,NOW()
FROM auth_roles r
JOIN auth_permissions p
WHERE r.role_code='SUPER_ADMIN'
  AND p.permission_code='SYSTEM.LEGACY_VIEW';
