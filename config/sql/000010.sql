-- --------------------------------------------
-- @version 0.0.0
-- --------------------------------------------

-- install the default tk lib user table
CREATE TABLE IF NOT EXISTS user
(
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  type VARCHAR(32) NOT NULL DEFAULT '',
  permissions BIGINT NOT NULL DEFAULT 0,
  username VARCHAR(255) NOT NULL DEFAULT '',
  password VARCHAR(128) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  name VARCHAR(128) NOT NULL DEFAULT '',
  notes TEXT DEFAULT '',
  timezone VARCHAR(64) NULL,
  active BOOL NOT NULL DEFAULT TRUE,
  hash VARCHAR(64) NOT NULL DEFAULT '',
  session_id VARCHAR(128) NOT NULL DEFAULT '',
  last_login TIMESTAMP NULL,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_username (username),
  UNIQUE KEY uk_email (email),
  KEY k_uid (uid),
  KEY k_type (type),
  KEY k_email (email)
);

-- User tokens to enable the 'Remember Me' functionality
CREATE TABLE IF NOT EXISTS user_tokens
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  selector VARCHAR(255) NOT NULL,
  hashed_validator VARCHAR(255) NOT NULL,
  browser_id VARCHAR(128) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  expiry DATETIME NOT NULL,
  CONSTRAINT fk_user_tokens__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE
);


# TODO: you need to instert data in the App migration scripts
# SET FOREIGN_KEY_CHECKS = 0;
# SET SQL_SAFE_UPDATES = 0;
#
# TRUNCATE TABLE user;
# TRUNCATE TABLE user_tokens;
#
# INSERT INTO user (type, username, email, name, timezone, permissions) VALUES
#   ('staff', 'admin', 'admin@example.com', 'Admin', NULL, 1)
# ;
#
# UPDATE `user` SET `hash` = MD5(CONCAT(username, id)) WHERE 1;
#
# SET SQL_SAFE_UPDATES = 1;
# SET FOREIGN_KEY_CHECKS = 1;


-- TODO Add the following event to your sites event.sql
-- Delete expired user 'remember me' login tokens
# DROP EVENT IF EXISTS evt_delete_expired_user_tokens;
# DELIMITER //
# CREATE EVENT evt_delete_expired_user_tokens
#   ON SCHEDULE EVERY 1 DAY
#   COMMENT 'Delete expired user remember me login tokens'
#   DO
#   BEGIN
#     DELETE FROM user_tokens WHERE expiry < NOW();
#   END
# //
# DELIMITER ;



