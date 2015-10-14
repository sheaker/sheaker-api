#
# SQL Export
# Created by Querious (999)
# Created: October 2, 2015 at 5:36:26 PM CDT
# Encoding: Unicode (UTF-8)
#


DROP TABLE IF EXISTS `users_payments`;
DROP TABLE IF EXISTS `users_checkin`;
DROP TABLE IF EXISTS `users_access`;
DROP TABLE IF EXISTS `users`;


CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL,
  `phone` varchar(128) DEFAULT '',
  `mail` varchar(255) DEFAULT '',
  `birthdate` date DEFAULT NULL,
  `address_street_1` varchar(255) DEFAULT '',
  `address_street_2` varchar(255) DEFAULT '',
  `city` varchar(255) DEFAULT '',
  `zip` varchar(10) DEFAULT '',
  `gender` char(1) DEFAULT '',
  `photo` varchar(255) DEFAULT '',
  `sponsor_id` varchar(20) DEFAULT '',
  `comment` text,
  `failed_logins` int(11) unsigned NOT NULL DEFAULT '0',
  `last_seen` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `users_access` (
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `user_level` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_user_id` (`user_id`) USING BTREE,
  CONSTRAINT `users_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `users_checkin` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`) USING BTREE,
  CONSTRAINT `users_checkin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `users_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT '0',
  `days` smallint(5) NOT NULL DEFAULT '-1',
  `start_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `price` smallint(5) NOT NULL DEFAULT '-1',
  `method` tinyint(3) NOT NULL DEFAULT '-1',
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`) USING BTREE,
  CONSTRAINT `users_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
