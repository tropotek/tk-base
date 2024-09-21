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

CREATE OR REPLACE VIEW v_user AS
SELECT
  u.*,
  TRIM(CONCAT_WS(' ', u.given_name, u.family_name)) AS name_short,
  TRIM(CONCAT_WS(' ', u.title , u.given_name, u.family_name)) AS name_long,
  IFNULL(a.uid, '') AS uid,
  IFNULL(a.permissions, 0) AS permissions,
  IFNULL(a.username, '') AS username,
  IFNULL(a.email, '') AS email,
  IFNULL(a.timezone, '') AS timezone,
  IFNULL(a.active, FALSE) AS active,
  IFNULL(a.session_id, '') AS session_id,
  a.last_login,
  MD5(CONCAT(a.auth_id, 'Auth')) AS hash,
  CONCAT('/app/', u.type, '/' , u.user_id, '/data') AS data_path
FROM user u
LEFT JOIN auth a ON (a.fkey = 'App\\Db\\User' AND a.fid = (u.user_id))
;


