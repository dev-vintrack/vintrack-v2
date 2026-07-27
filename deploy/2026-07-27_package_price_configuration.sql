ALTER TABLE `global_configuration`
    ADD COLUMN `min_price_package` DECIMAL(10,2) NOT NULL DEFAULT 100 AFTER `step_purchase_input`,
    ADD COLUMN `max_price_package` DECIMAL(10,2) NOT NULL DEFAULT 5000 AFTER `min_price_package`,
    ADD COLUMN `step_price_package` DECIMAL(10,2) NOT NULL DEFAULT 100 AFTER `max_price_package`;

UPDATE `global_configuration`
SET
    `min_price_package` = 100,
    `max_price_package` = 5000,
    `step_price_package` = 100,
    `updated_at` = NOW();
