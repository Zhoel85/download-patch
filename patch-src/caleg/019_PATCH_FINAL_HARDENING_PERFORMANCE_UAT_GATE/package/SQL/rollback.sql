-- 019 rollback
DELETE rp
FROM auth_role_permissions rp
JOIN auth_permissions p ON p.id=rp.permission_id
WHERE p.permission_code='SYSTEM.PRODUCTION_READINESS';

DELETE FROM auth_permissions
WHERE permission_code='SYSTEM.PRODUCTION_READINESS';

DROP INDEX IF EXISTS idx_rc_event_tps_status ON witness_result_headers;
DROP INDEX IF EXISTS idx_rc_event_status_updated ON witness_result_headers;
DROP INDEX IF EXISTS idx_incident_event_tps_status ON campaign_incidents;
DROP INDEX IF EXISTS idx_witness_event_tps_active ON campaign_witnesses;
DROP INDEX IF EXISTS idx_witness_day_date ON campaign_witness_day;
DROP INDEX IF EXISTS idx_live_event_created ON campaign_live_activity;
DROP INDEX IF EXISTS idx_dpt_event_area ON dpt_records;
DROP INDEX IF EXISTS idx_org_event_role_area ON campaign_org_assignments;

DROP TABLE IF EXISTS security_runtime_rate_limits;
DROP TABLE IF EXISTS real_count_tps_registry;
