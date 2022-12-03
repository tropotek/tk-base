-- --------------------------------------------
-- @version 3.4.68
--
-- @author: Michael Mifsud <http://www.tropotek.com/>
-- --------------------------------------------

-- --------------------------------------------------------
-- This is used when a users wants to recover an account
-- Create a cron script to remove old entries,
--   they should also be removed once account is recovered
--
CREATE TABLE IF NOT EXISTS `user_recover` (
  user_id int(10) unsigned NOT NULL,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY `user_id_name` (`user_id`, `created`)
) ENGINE=InnoDB;


