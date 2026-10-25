DELETE rp FROM auth_role_permissions rp JOIN auth_permissions p ON p.id=rp.permission_id WHERE p.permission_code='SYSTEM.LEGACY_MONITOR';
DELETE FROM auth_permissions WHERE permission_code='SYSTEM.LEGACY_MONITOR';
DROP TABLE IF EXISTS legacy_freeze_history;
DROP TABLE IF EXISTS legacy_dependency_events;
DROP TABLE IF EXISTS legacy_freeze_rules;
