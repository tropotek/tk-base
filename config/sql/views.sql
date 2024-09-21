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

CREATE OR REPLACE VIEW v_auth AS
SELECT
  a.*,
  MD5(CONCAT(a.auth_id, 'Auth')) AS hash
FROM auth a
;

