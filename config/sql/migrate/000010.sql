-- --------------------------------------------
-- @version 8.0.0
-- --------------------------------------------

-- Default lib authentication table
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

-- Remember me cookie tokens
CREATE TABLE IF NOT EXISTS auth_remember
(
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  selector VARCHAR(255) NOT NULL,
  hashed_validator VARCHAR(255) NOT NULL,
  browser_id VARCHAR(128) NOT NULL,
  auth_id INT UNSIGNED NOT NULL,
  ttl_mins INT NOT NULL DEFAULT 1440,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiry DATETIME GENERATED ALWAYS AS (created + INTERVAL ttl_mins MINUTE) VIRTUAL,
  KEY (selector),
  KEY (browser_id),
  KEY (auth_id),
  CONSTRAINT fk_auth_remember__auth_id FOREIGN KEY (auth_id) REFERENCES auth (auth_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS guest_token
(
  token VARCHAR(64) NOT NULL PRIMARY KEY,
  pages VARCHAR(500) NOT NULL DEFAULT '',
  payload VARCHAR(4000) NOT NULL DEFAULT '',
  ttl_mins INT NOT NULL DEFAULT 10,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiry DATETIME GENERATED ALWAYS AS (created + INTERVAL ttl_mins MINUTE) VIRTUAL
);
