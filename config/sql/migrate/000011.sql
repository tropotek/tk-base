-- --------------------------------------------
-- @version 8.0.0
-- Failed login attempt tracking (brute-force defence)
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS auth_login_attempt
(
  attempt_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL DEFAULT '',
  ip VARCHAR(64) NOT NULL DEFAULT '',
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempt_lookup (username, ip, created)
);
