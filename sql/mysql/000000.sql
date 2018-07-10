


CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `role` varchar(255) NOT NULL DEFAULT '',
  `notes` TEXT,
  `last_login` TIMESTAMP,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `hash` varchar(255) NOT NULL DEFAULT '',
  `del` TINYINT(1) NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


-- TODO: this should b added to the site sql only
# INSERT INTO `user` (`id`, `name`, `email`, `username`, `password`, `role`, `active`, `hash`, `modified`, `created`)
# VALUES
#   (NULL, 'Administrator', 'admin@example.com', 'admin', MD5('password'), 'admin', 1, MD5('1admin'), NOW() , NOW()),
#   (NULL, 'User 1', 'user@example.com', 'user1', MD5('password'), 'user', 1, MD5('2user1'), NOW() , NOW())
# ;






