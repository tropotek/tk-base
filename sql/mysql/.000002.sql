



-- --------------------------------------------------------
--
-- Table structure for table `menu`
--
CREATE TABLE IF NOT EXISTS `menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(64) NOT NULL DEFAULT '',                 -- Text name of the menu
  `type` varchar(64) NOT NULL DEFAULT '',                 -- [admin|client|staff|student] The system role type
  `var` varchar(64) NOT NULL DEFAULT '',                  -- the var element we will be replacing
  `params` TEXT,
  `description` TEXT,
  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `menu_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `menu_id` int(10) unsigned NOT NULL DEFAULT 0,
  `parent_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(64) NOT NULL DEFAULT '',                 -- Text of the menu item
  `url` varchar(164) NOT NULL DEFAULT '',
  `icon` varchar(164) NOT NULL DEFAULT '',
  `params` TEXT,

  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  KEY `menu_id` (`menu_id`),
  KEY `parent_id` (`parent_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB;



