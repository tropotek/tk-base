-- --------------------------------------------
-- @version 8.0.0
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
  name_title VARCHAR(16) NOT NULL DEFAULT '',
  name_first VARCHAR(128) NOT NULL DEFAULT '',
  name_last VARCHAR(128) NOT NULL DEFAULT '',
  name_display VARCHAR(128) NOT NULL DEFAULT '',
  notes TEXT DEFAULT '',
  timezone VARCHAR(64) NOT NULL DEFAULT '',
  active BOOL NOT NULL DEFAULT TRUE,
  session_id VARCHAR(128) NOT NULL DEFAULT '',
  last_login TIMESTAMP NULL,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_username (username),
  UNIQUE KEY uk_email (email),
  KEY k_uid (uid),
  KEY k_type (type),
  KEY k_email (email)
);


-- User tokens to enable the 'Remember Me' functionality
CREATE TABLE IF NOT EXISTS user_remember
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  selector VARCHAR(255) NOT NULL,
  hashed_validator VARCHAR(255) NOT NULL,
  browser_id VARCHAR(128) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  expiry DATETIME NOT NULL,
  CONSTRAINT fk_user_remember__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE
);

