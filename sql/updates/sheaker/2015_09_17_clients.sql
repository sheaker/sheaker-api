ALTER TABLE `clients` ADD COLUMN `flags` INT(10) UNSIGNED NULL DEFAULT 0 AFTER `secret_key`;

-- Reindex clients
UPDATE `clients` SET `flags` = 0x01;
