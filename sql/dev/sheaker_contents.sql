#
# SQL Export
# Created by Querious (971)
# Created: April 21, 2015 at 8:29:33 AM CDT
# Encoding: Unicode (UTF-8)
#


SET @PREVIOUS_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;


LOCK TABLES `clients` WRITE;
ALTER TABLE `clients` DISABLE KEYS;
INSERT INTO `clients` (`id`, `name`, `subdomain`, `secret_key`, `created_at`) VALUES 
	(1,'My Gym','mygym','MmSfT76E3cvJE3vPpxfv0TZ2flq679IT','2015-04-06 00:00:00');
ALTER TABLE `clients` ENABLE KEYS;
UNLOCK TABLES;




SET FOREIGN_KEY_CHECKS = @PREVIOUS_FOREIGN_KEY_CHECKS;


