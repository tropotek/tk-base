-- ------------------------------------------------------
-- SQL events
--
-- Files views.sql, procedures.sql, events.sql, triggers.sql
--  will be executed if they exist after install, update and migration
--
-- Note: update your mysql server to `event_scheduler=ON` to enable mysql events
-- ------------------------------------------------------

-- Delete expired user 'remember me' login tokens
DROP EVENT IF EXISTS evt_delete_expired_auth_remember;
DELIMITER //
CREATE EVENT evt_delete_expired_auth_remember
  ON SCHEDULE EVERY 1 DAY
  COMMENT 'Delete expired auth remember me login tokens'
  DO
  BEGIN
    DELETE FROM auth_remember
    WHERE expiry < NOW();
  END
//
DELIMITER ;

-- remove expired guest login tokens
DROP EVENT IF EXISTS evt_remove_expired_guest_tokens;
DELIMITER //
CREATE EVENT evt_remove_expired_guest_tokens
  ON SCHEDULE EVERY 1 MINUTE
  COMMENT 'Remove expired guest login tokens'
  DO
  BEGIN
    DELETE FROM guest_token
    WHERE expiry < NOW();
  END
//
DELIMITER ;

