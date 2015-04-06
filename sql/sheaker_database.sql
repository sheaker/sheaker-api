#
# SQL Export
# Created by Querious (962)
# Created: April 6, 2015 at 8:56:12 AM CDT
# Encoding: Unicode (UTF-8)
#


DROP TABLE IF EXISTS `reserved_subdomains`;
DROP TABLE IF EXISTS `clients`;


CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `subdomain` varchar(255) NOT NULL DEFAULT '',
  `secret_key` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE TABLE `reserved_subdomains` (
  `subdomain` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




SET @PREVIOUS_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;


LOCK TABLES `clients` WRITE;
ALTER TABLE `clients` DISABLE KEYS;
INSERT INTO `clients` (`id`, `name`, `subdomain`, `secret_key`, `created_at`) VALUES 
	(1,'Force Gym','forcegym','MmSfT76E3cvJE3vPpxfv0TZ2flq679IT','2015-04-06 00:00:00');
ALTER TABLE `clients` ENABLE KEYS;
UNLOCK TABLES;


LOCK TABLES `reserved_subdomains` WRITE;
ALTER TABLE `reserved_subdomains` DISABLE KEYS;
INSERT INTO `reserved_subdomains` (`subdomain`) VALUES 
	('www'),
	('ftp'),
	('sheaker'),
	('api'),
	('dev');
ALTER TABLE `reserved_subdomains` ENABLE KEYS;
UNLOCK TABLES;




SET FOREIGN_KEY_CHECKS = @PREVIOUS_FOREIGN_KEY_CHECKS;


