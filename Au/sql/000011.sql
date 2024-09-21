-- --------------------------------------------
-- @version 8.0.0
--
-- Migrate user updates
-- --------------------------------------------

RENAME TABLE user TO user_old;
RENAME TABLE v_user TO v_user_old;


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
  UNIQUE KEY (username),
  UNIQUE KEY (email),
  KEY (uid),
  KEY (fkey),
  KEY (fid)
);

INSERT INTO auth (auth_id, uid, fkey, fid, permissions, username, password, email, timezone, active, session_id, last_login, modified, created)
(
	SELECT user_id, uid, 'App\\Db\\User', user_id, permissions, username, password, email, timezone, active, session_id, last_login, modified, created
	FROM user_old
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
  KEY (type)
);

INSERT INTO user (user_id, type, title, given_name, family_name, modified, created)
(
	SELECT user_id, type, name_title, name_first, name_last, modified, created
	FROM user_old
);


CREATE TABLE IF NOT EXISTS auth_remember
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  selector VARCHAR(255) NOT NULL,
  hashed_validator VARCHAR(255) NOT NULL,
  browser_id VARCHAR(128) NOT NULL,
  auth_id INT UNSIGNED NOT NULL,
  expiry DATETIME NOT NULL,
  KEY (selector),
  KEY (browser_id),
  KEY (auth_id),
  CONSTRAINT fk_auth_remember__auth_id FOREIGN KEY (auth_id) REFERENCES auth (auth_id) ON DELETE CASCADE
);

INSERT INTO auth_remember (id, selector, hashed_validator, browser_id, auth_id, expiry)
(
	SELECT id, selector, hashed_validator, browser_id, user_id, expiry
	FROM user_remember
);



-- DROP TABLE user_old;
-- DROP TABLE v_user_old;
-- DROP TABLE user_remember;
