-- --------------------------------------------
-- @version 0.0.0
--
-- @author: Tropotek <https://tropotek.com/>
-- --------------------------------------------

--
-- If no user entry then that user does not have login access
-- To enable an email to validate the account and create a password should be sent
-- There should be no where in the application to view/edit the password only use a recovery system
--
-- NOTE: This table assumes that user username's are unique.
--
-- Using this type of user table setup we can then add associated tables
--  such as: user_google, user_facebook, user_microsoft, user_gdrive, etc...
CREATE TABLE IF NOT EXISTS user_auth
(
  user_id INT UNSIGNED DEFAULT 0 NOT NULL,
  `username` VARCHAR(64) DEFAULT '' NOT NULL,
  `password` VARCHAR(128) DEFAULT '' NOT NULL,  -- Hashed password
  PRIMARY KEY (user_id, `username`),
  FOREIGN KEY (user_id) REFERENCES user (user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;



