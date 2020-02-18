-- --------------------------------------------------------
--
--
--
--
-- @author Michael Mifsud <info@tropotek.com>
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user`
(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uid` VARCHAR(128) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT 'public',
  `username` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `name_first` varchar(128) NOT NULL DEFAULT '',
  `name_last` varchar(128) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(32) NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL DEFAULT '',
  `notes` TEXT,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` TIMESTAMP,
  `session_id` varchar(128) NOT NULL DEFAULT '',
  `hash` varchar(64) NOT NULL DEFAULT '',
  `del` TINYINT(1) NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  KEY `uid` (`uid`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB;


-- --------------------------------------------------------
-- The user permission table
-- Table structure for table `user_permission`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permission` (
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY `user_id_name` (`user_id`, `name`)
) ENGINE=InnoDB;



-- -------------------------------
--
-- NOTE: If using this file then you must also populate the migrate table
--       manually so that existing migrations are ignored.
-- TODO: write an install script that will install the DB new and auto generate this....
--
--




