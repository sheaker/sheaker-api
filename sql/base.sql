#
# SQL Export
# Created by Querious (962)
# Created: April 3, 2015 at 9:32:29 AM CST
# Encoding: Unicode (UTF-8)
#


DROP TABLE IF EXISTS `users_payments`;
DROP TABLE IF EXISTS `users_access`;
DROP TABLE IF EXISTS `users`;


CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `custom_id` int(11) unsigned DEFAULT '0',
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL,
  `phone` varchar(128) DEFAULT '',
  `mail` varchar(255) DEFAULT '',
  `birthdate` date DEFAULT '0000-00-00',
  `address_street_1` varchar(255) DEFAULT '',
  `address_street_2` varchar(255) DEFAULT '',
  `city` varchar(255) DEFAULT '',
  `zip` varchar(10) DEFAULT '',
  `gender` tinyint(1) DEFAULT '-1',
  `photo` varchar(255) DEFAULT '',
  `sponsor_id` int(11) unsigned DEFAULT '0',
  `comment` text,
  `failed_logins` int(11) unsigned NOT NULL DEFAULT '0',
  `last_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_ip` varchar(255) NOT NULL DEFAULT '0.0.0.0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `users_access` (
  `user_id` int(11) unsigned NOT NULL,
  `user_level` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_user_id` (`user_id`) USING BTREE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `users_payments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT '0',
  `days` smallint(5) NOT NULL DEFAULT '-1',
  `start_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `price` smallint(5) NOT NULL DEFAULT '-1',
  `method` tinyint(3) NOT NULL DEFAULT '-1',
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`) USING BTREE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




