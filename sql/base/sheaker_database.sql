#
# SQL Export
# Created by Querious (971)
# Created: April 21, 2015 at 8:28:37 AM CDT
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `reserved_subdomains` (
  `subdomain` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




SET @PREVIOUS_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;


LOCK TABLES `reserved_subdomains` WRITE;
ALTER TABLE `reserved_subdomains` DISABLE KEYS;
INSERT INTO `reserved_subdomains` (`subdomain`) VALUES 
	('www'),
	('ftp'),
	('sheaker'),
	('api'),
	('dev'),
	('test');
ALTER TABLE `reserved_subdomains` ENABLE KEYS;
UNLOCK TABLES;




SET FOREIGN_KEY_CHECKS = @PREVIOUS_FOREIGN_KEY_CHECKS;


