-- ------------------------------
-- 
--
-- @author Michael Mifsud <info@tropotek.com>
-- ------------------------------



-- Create the new fields
alter table user add type varchar(32) default '' not null after uid;

RENAME TABLE user_permission TO _user_role_permission;
RENAME TABLE user_role TO _user_role;

-- --------------------------------------------------------
-- The user permission table
-- Table structure for table `user_permission`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permission` (
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY `user_id_name` (`user_id`, `name`)
) ENGINE=InnoDB;

UPDATE user a, _user_role b SET a.type = b.type
    WHERE a.role_id = b.id;

UPDATE user SET type = 'member' WHERE type = 'user';

INSERT INTO user_permission (user_id, name)
  (
    SELECT a.id, c.name
    FROM user a, _user_role b, _user_role_permission c
    WHERE a.role_id = b.id AND b.id = c.role_id AND c.name NOT LIKE 'type.%'
  )
;


alter table user drop column role_id;
alter table user drop column name;

DROP TABLE _user_role;
DROP TABLE _user_role_permission;




-- NOTE: This has to be run manually before upgrading to ver 3.2
-- RENAME TABLE migration TO _migration;
-- RENAME TABLE data TO _data;
-- RENAME TABLE session TO _session;
-- RENAME TABLE plugin TO _plugin;
