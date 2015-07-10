ALTER TABLE `users_checkin`
  DROP FOREIGN KEY `users_checkin_ibfk_1`;
ALTER TABLE `users_checkin`
  ADD CONSTRAINT `users_checkin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `users_payments`
  DROP FOREIGN KEY `users_payments_ibfk_1`;
ALTER TABLE `users_payments`
  ADD CONSTRAINT `users_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
