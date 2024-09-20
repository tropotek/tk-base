-- --------------------------------------------
-- @version 8.0.0
-- --------------------------------------------


-- install the default tk lib user table
CREATE TABLE IF NOT EXISTS auth
(
  auth_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  fkey VARCHAR(128) NOT NULL DEFAULT '',
  fid INT UNSIGNED NOT NULL DEFAULT 0,
  permissions BIGINT NOT NULL DEFAULT 0,
  username VARCHAR(255) NOT NULL DEFAULT '',
  password VARCHAR(128) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
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

CREATE TABLE IF NOT EXISTS user
(
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(128) NOT NULL DEFAULT '',
  title VARCHAR(20) NOT NULL DEFAULT '',
  given_name VARCHAR(128) NOT NULL DEFAULT '',
  family_name VARCHAR(128) NOT NULL DEFAULT '',
  phone VARCHAR(20) NOT NULL DEFAULT '',
  address VARCHAR(1000) NOT NULL DEFAULT '',
  city VARCHAR(128) NOT NULL DEFAULT '',
  state VARCHAR(128) NOT NULL DEFAULT '',
  postcode VARCHAR(128) NOT NULL DEFAULT '',
  country VARCHAR(128) NOT NULL DEFAULT '',
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user__auth_id FOREIGN KEY (auth_id) REFERENCES auth (auth_id) ON DELETE CASCADE ON UPDATE CASCADE
);



-- User tokens to enable the 'Remember Me' functionality
CREATE TABLE IF NOT EXISTS auth_remember
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  selector VARCHAR(255) NOT NULL,
  hashed_validator VARCHAR(255) NOT NULL,
  browser_id VARCHAR(128) NOT NULL,
  auth_id INT UNSIGNED NOT NULL,
  expiry DATETIME NOT NULL,
  CONSTRAINT fk_auth_remember__auth_id FOREIGN KEY (auth_id) REFERENCES auth (auth_id) ON DELETE CASCADE
);

