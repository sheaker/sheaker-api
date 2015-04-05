#
# SQL Export
# Created by Querious (962)
# Created: April 3, 2015 at 9:34:49 AM CST
# Encoding: Unicode (UTF-8)
#


SET @PREVIOUS_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;


LOCK TABLES `users` WRITE;
ALTER TABLE `users` DISABLE KEYS;
INSERT INTO `users` (`id`, `custom_id`, `first_name`, `last_name`, `password`, `phone`, `mail`, `birthdate`, `address_street_1`, `address_street_2`, `city`, `zip`, `gender`, `photo`, `sponsor_id`, `comment`, `failed_logins`, `last_seen`, `last_ip`, `created_at`) VALUES 
	(1,0,'admin','admin','$1$OhuHu9HV$MGnsyEEGXt6Y6QShwL7ZM/','523312345678','admin@sheaker.com','0000-00-00','','','','0',0,'',0,'',0,'2015-03-19 16:27:51','127.0.0.1','2015-01-01 00:00:00');
ALTER TABLE `users` ENABLE KEYS;
UNLOCK TABLES;


LOCK TABLES `users_access` WRITE;
ALTER TABLE `users_access` DISABLE KEYS;
INSERT INTO `users_access` (`user_id`, `user_level`) VALUES 
	(1,2);
ALTER TABLE `users_access` ENABLE KEYS;
UNLOCK TABLES;


LOCK TABLES `users_payments` WRITE;
ALTER TABLE `users_payments` DISABLE KEYS;
ALTER TABLE `users_payments` ENABLE KEYS;
UNLOCK TABLES;




SET FOREIGN_KEY_CHECKS = @PREVIOUS_FOREIGN_KEY_CHECKS;


