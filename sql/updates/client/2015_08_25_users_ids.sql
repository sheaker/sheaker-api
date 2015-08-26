-- First delete deplicate
DELETE FROM `users` WHERE `id` IN (1011, 1009, 1008, 1007, 1006, 1005, 1004, 1003, 998, 994, 993, 992, 991, 990, 989, 988);

ALTER TABLE `users_access` DROP FOREIGN KEY `users_access_ibfk_1`;
ALTER TABLE `users_checkin` DROP FOREIGN KEY `users_checkin_ibfk_1`;
ALTER TABLE `users_payments` DROP FOREIGN KEY `users_payments_ibfk_1`;

ALTER TABLE `users` CHANGE COLUMN `id` `id` BIGINT UNSIGNED NOT NULL auto_increment COMMENT '' FIRST;
ALTER TABLE `users` CHANGE COLUMN `birthdate` `birthdate` DATE NULL DEFAULT NULL  COMMENT '' AFTER `mail`;
ALTER TABLE `users` CHANGE COLUMN `gender` `gender` CHAR(1) DEFAULT ''  COMMENT '' AFTER `zip`;
ALTER TABLE `users` CHANGE COLUMN `sponsor_id` `sponsor_id` VARCHAR(20) DEFAULT ''  COMMENT '' AFTER `photo`;
ALTER TABLE `users` CHANGE COLUMN `last_seen` `last_seen` TIMESTAMP NULL DEFAULT NULL  COMMENT '' AFTER `failed_logins`;
ALTER TABLE `users` CHANGE COLUMN `last_ip` `last_ip` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT ''  COMMENT '' AFTER `last_seen`;

ALTER TABLE `users` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`;

-- Change ids type to bigint to allow us to go 18446744073709551615 (just in case)
ALTER TABLE `users_access` CHANGE COLUMN `user_id` `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0  COMMENT '' FIRST;
ALTER TABLE `users_checkin` CHANGE COLUMN `id` `id` BIGINT UNSIGNED NOT NULL auto_increment COMMENT '' FIRST;
ALTER TABLE `users_checkin` CHANGE COLUMN `user_id` `user_id` BIGINT UNSIGNED NULL DEFAULT 0  COMMENT '' AFTER `id`;
ALTER TABLE `users_payments` CHANGE COLUMN `id` `id` BIGINT UNSIGNED NOT NULL auto_increment COMMENT '' FIRST;
ALTER TABLE `users_payments` CHANGE COLUMN `user_id` `user_id` BIGINT UNSIGNED NULL DEFAULT 0  COMMENT '' AFTER `id`;

-- Restore temp FK with on cascade
ALTER TABLE `users_access` ADD CONSTRAINT `users_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE `users_checkin` ADD CONSTRAINT `users_checkin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE `users_payments` ADD CONSTRAINT `users_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;

SET @TEMP_LAST_ID = 10000;

UPDATE `users`
SET `id` = (@TEMP_LAST_ID := (SELECT SUM(@TEMP_LAST_ID + 1))), `custom_id` = 0
WHERE `id` IN (
  SELECT `id` FROM (
    SELECT dup.id
    FROM `users`
    INNER JOIN (SELECT `id` FROM `users` WHERE `custom_id` = 0) dup ON users.custom_id = dup.id
  ) AS ids_to_change
);

UPDATE `users`
SET `id` = (@TEMP_LAST_ID := (SELECT SUM(@TEMP_LAST_ID + 1))), `custom_id` = 0
WHERE `custom_id` IN (
  SELECT `custom_id` FROM (
    SELECT dup.custom_id
    FROM `users`
    INNER JOIN (SELECT `custom_id` FROM `users` WHERE `custom_id` != 0) dup ON users.id = dup.custom_id
  ) AS ids_to_change
);

-- Put customs ids in id column if any...
UPDATE `users` SET `id` = `custom_id` WHERE `custom_id` != 0;

-- ...and remove column
ALTER TABLE `users` DROP COLUMN `custom_id` ;

-- Set correct new id for tempory users
SET @TEMP_LAST_ID = 10000;
SELECT @CURRENT_LAST_ID := MAX(`id`) FROM `users` WHERE `id` < @TEMP_LAST_ID;

UPDATE `users`
SET `id` = (@CURRENT_LAST_ID := (SELECT SUM(@CURRENT_LAST_ID + 1)))
WHERE `id` IN (
  SELECT `id` FROM (
    SELECT `id` FROM `users` WHERE `id` >= @TEMP_LAST_ID
  ) AS ids_to_change
);

-- Small cleanups
UPDATE `users` SET `phone` = '' WHERE LENGTH(`phone`) < 8;
UPDATE `users` SET `phone` = CONCAT('33', `phone`) WHERE LENGTH(`phone`) = 8;
UPDATE `users` SET `phone` = SUBSTRING(`phone`, 3) WHERE LENGTH(`phone`) = 12;
UPDATE `users` SET `mail` = '' WHERE LENGTH(`mail`) < 5;
UPDATE `users` SET `birthdate` = NULL WHERE `birthdate` = '0000-00-00' OR `birthdate` = '1970-01-01';
UPDATE `users` SET `sponsor_id` = NULL WHERE `sponsor_id` = 0;
UPDATE `users` SET `last_seen` = NULL WHERE `last_seen` = '0000-00-00 00:00:00';
UPDATE `users` SET `last_ip` = '' WHERE `last_ip` = '0.0.0.0';

-- Reset auto increment
ALTER TABLE `users` AUTO_INCREMENT = 0;

-- Correctly reset fk
ALTER TABLE `users_access` DROP FOREIGN KEY `users_access_ibfk_1`;
ALTER TABLE `users_checkin` DROP FOREIGN KEY `users_checkin_ibfk_1`;
ALTER TABLE `users_payments` DROP FOREIGN KEY `users_payments_ibfk_1`;

ALTER TABLE `users_access` ADD CONSTRAINT `users_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;
ALTER TABLE `users_checkin` ADD CONSTRAINT `users_checkin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;
ALTER TABLE `users_payments` ADD CONSTRAINT `users_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;
