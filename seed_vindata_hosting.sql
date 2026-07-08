-- Seed VINDATA provider and services for hosting deployment
-- Run this against the hosting database after pulling the latest code

INSERT INTO providers (code, name, base_url, policies_json, enabled, created_at, updated_at)
VALUES (
    'VINDATA',
    'VINData',
    'https://api.vindata.com/v1',
    '{"debitTiming":"postAccept","creditCost":1,"resetPeriod":"none","carryOver":true,"expireAfterDays":null}',
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    base_url = VALUES(base_url),
    policies_json = VALUES(policies_json),
    enabled = VALUES(enabled),
    updated_at = NOW();

SET @vindata_id = LAST_INSERT_ID();
-- If the provider already existed, LAST_INSERT_ID() returns 0; fetch it.
SELECT @vindata_id := id FROM providers WHERE code = 'VINDATA';

INSERT INTO provider_services (provider_id, key, name, credit_cost, enabled, created_at, updated_at)
VALUES
    (@vindata_id, 'VHR', 'VIN History Report', 1, 1, NOW(), NOW()),
    (@vindata_id, 'NMVTISPlus', 'NMVTIS+', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    credit_cost = VALUES(credit_cost),
    enabled = VALUES(enabled),
    updated_at = NOW();
