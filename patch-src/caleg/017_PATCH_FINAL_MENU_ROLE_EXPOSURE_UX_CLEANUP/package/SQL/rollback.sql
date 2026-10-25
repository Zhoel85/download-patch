-- 017 rollback
DELETE rp
FROM auth_role_permissions rp
JOIN auth_permissions p ON p.id=rp.permission_id
WHERE p.permission_code='SYSTEM.LEGACY_VIEW';

DELETE FROM auth_permissions
WHERE permission_code='SYSTEM.LEGACY_VIEW';
