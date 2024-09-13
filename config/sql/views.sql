-- ------------------------------------------------------
-- SQL views
--
-- Files views.sql, procedures.sql, events.sql, triggers.sql
--  will be executed if they exist after install, update and migration
--
-- They can be executed from the cli commands:
--  o `./bin/cmd migrate`
--  o `composer update`
--
-- ------------------------------------------------------


CREATE OR REPLACE VIEW v_user AS
SELECT
  u.*,
  MD5(CONCAT(u.user_id, 'User')) AS hash,
  CONCAT('/app/user/', u.user_id, '/data') AS data_path
FROM
  user u
;

