-- 019 FINAL HARDENING, PERFORMANCE & UAT GATE
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS real_count_tps_registry (
    event_id BIGINT UNSIGNED NOT NULL,
    id_tps VARCHAR(32) NOT NULL,
    witness_id BIGINT UNSIGNED NOT NULL,
    header_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    state VARCHAR(20) NOT NULL DEFAULT 'CLAIMED',
    claimed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (event_id,id_tps),
    KEY idx_rc_registry_witness (witness_id),
    KEY idx_rc_registry_header (header_id),
    KEY idx_rc_registry_state (state,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS security_runtime_rate_limits (
    endpoint_key VARCHAR(80) NOT NULL,
    actor_key CHAR(64) NOT NULL,
    window_start DATETIME NOT NULL,
    hit_count INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (endpoint_key,actor_key,window_start),
    KEY idx_rate_window (window_start),
    KEY idx_rate_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed registry ONLY for TPS that already have exactly one authoritative header.
INSERT IGNORE INTO real_count_tps_registry
(event_id,id_tps,witness_id,header_id,state,claimed_at,created_at,updated_at)
SELECT
    h.event_id,
    h.id_tps,
    MAX(h.witness_id),
    MAX(h.id),
    MAX(h.status),
    COALESCE(MAX(h.finalized_at),NOW()),
    NOW(),
    NOW()
FROM witness_result_headers h
WHERE h.status IN ('FINALIZED','VALIDATED','VERIFIED')
GROUP BY h.event_id,h.id_tps
HAVING COUNT(*)=1;

ALTER TABLE witness_result_headers
    ADD INDEX IF NOT EXISTS idx_rc_event_tps_status (event_id,id_tps,status),
    ADD INDEX IF NOT EXISTS idx_rc_event_status_updated (event_id,status,updated_at);

ALTER TABLE campaign_incidents
    ADD INDEX IF NOT EXISTS idx_incident_event_tps_status (event_id,id_tps,status,priority);

ALTER TABLE campaign_witnesses
    ADD INDEX IF NOT EXISTS idx_witness_event_tps_active (event_id,id_tps,is_active);

ALTER TABLE campaign_witness_day
    ADD INDEX IF NOT EXISTS idx_witness_day_date (day_date,witness_id,checkin_at);

ALTER TABLE campaign_live_activity
    ADD INDEX IF NOT EXISTS idx_live_event_created (event_id,created_at);

ALTER TABLE dpt_records
    ADD INDEX IF NOT EXISTS idx_dpt_event_area (event_id,version_id,id_kecamatan,id_desa,id_tps);

ALTER TABLE campaign_org_assignments
    ADD INDEX IF NOT EXISTS idx_org_event_role_area (event_id,role_code,status,id_kecamatan,id_desa,id_tps);

INSERT INTO auth_permissions
(permission_code,module_code,action_code,permission_name,description,is_active,created_at)
VALUES
('SYSTEM.PRODUCTION_READINESS','SYSTEM','PRODUCTION_READINESS','Production Readiness','Final automated production GO/NO-GO gate',1,NOW())
ON DUPLICATE KEY UPDATE
permission_name=VALUES(permission_name),
description=VALUES(description),
is_active=1;

INSERT IGNORE INTO auth_role_permissions(role_id,permission_id,created_at)
SELECT r.id,p.id,NOW()
FROM auth_roles r
JOIN auth_permissions p
WHERE r.role_code='SUPER_ADMIN'
  AND p.permission_code='SYSTEM.PRODUCTION_READINESS';

-- Rate-limit rows are ephemeral; prune old windows at deploy time.
DELETE FROM security_runtime_rate_limits WHERE window_start < DATE_SUB(NOW(),INTERVAL 1 DAY);
