ALTER TABLE `user_packages`
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(16) NOT NULL DEFAULT 'active' AFTER `expires_at`;

ALTER TABLE `user_provider_wallets`
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(16) NOT NULL DEFAULT 'active' AFTER `validity_end`;

UPDATE `user_packages`
SET `status` = 'expired'
WHERE `expires_at` IS NOT NULL
  AND `expires_at` <= NOW();

UPDATE `user_provider_wallets`
SET `status` = 'expired'
WHERE `validity_end` IS NOT NULL
  AND `validity_end` <= NOW();
