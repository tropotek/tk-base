-- ------------------------------------------------------
-- SQL procedures and functions
--
-- Files views.sql, procedures.sql, events.sql, triggers.sql
--  will be executed if they exist after install, update and migration
--
-- They can be executed from the cli commands:
--  o `./bin/cmd migrate`
--  o `composer update`
--
-- ------------------------------------------------------

-- Util function because views can't refer to constant @@time_zone
DROP FUNCTION IF EXISTS session_timezone;
DELIMITER //
CREATE FUNCTION session_timezone() RETURNS VARCHAR(100) DETERMINISTIC
BEGIN
	RETURN @@time_zone;
END //
DELIMITER ;

-- Set all words first letter to uppercase
DROP FUNCTION IF EXISTS ucwords;
DELIMITER //
CREATE FUNCTION ucwords(input VARCHAR(255)) RETURNS VARCHAR(255)
BEGIN
	DECLARE len INT;
	DECLARE i INT;

	SET len   = CHAR_LENGTH(input);
	SET input = LOWER(input);
	SET i = 0;

	WHILE (i < len) DO
		IF (MID(input,i,1) = ' ' OR i = 0) THEN
			IF (i < len) THEN
				SET input = CONCAT(
					LEFT(input,i),
					UPPER(MID(input,i + 1,1)),
					RIGHT(input,len - i - 1)
				);
			END IF;
		END IF;
		SET i = i + 1;
	END WHILE;

	RETURN input;
END //
DELIMITER ;


-- compares two date ranges and checks for overlap (inclusive)
-- start dates must be before end date
# DROP FUNCTION IF EXISTS dates_overlap;
# CREATE FUNCTION dates_overlap(
# 	start1 DATE,
# 	end1 DATE,
# 	start2 DATE,
# 	end2 DATE
# ) RETURNS BOOLEAN DETERMINISTIC
# 	RETURN GREATEST(start1, start2) <= LEAST(end1, end2)
# ;

-- return extension given a filename
-- returns extension lower-cased, null if no extension found
# DROP FUNCTION IF EXISTS filename_ext;
# CREATE FUNCTION filename_ext(filename VARCHAR(400))
#   RETURNS VARCHAR(4) DETERMINISTIC
# BEGIN
#   SET @ext = SUBSTRING_INDEX(filename, '.', -1);
#   IF @ext = filename THEN
#     -- no . found
#     SET @ext = NULL;
#   END IF;
#   RETURN LOWER(@ext);
# END;



-- DB Search all tables/columns for a value (not for production use)
DROP PROCEDURE IF EXISTS findAll; -- TODO remove this after all site have migrated procedure
DROP PROCEDURE IF EXISTS dbSearchAll;
DELIMITER //
CREATE PROCEDURE dbSearchAll( IN `search` TEXT )
BEGIN
  SET SESSION group_concat_max_len := @@max_allowed_packet;

  SELECT GROUP_CONCAT(
    "SELECT '", c1.TABLE_NAME, "' AS `table`, '", c1.COLUMN_NAME, "' AS `column`, ",
    "CONCAT_WS(',', ",  (SELECT GROUP_CONCAT('`', c2.column_name, '`') FROM `information_schema`.`columns` c2 WHERE c1.TABLE_SCHEMA=c2.TABLE_SCHEMA AND c1.TABLE_NAME=c2.TABLE_NAME AND c2.COLUMN_KEY='PRI' LIMIT 1) ,") AS pri,",
    "`", c1.COLUMN_NAME, "` AS value FROM `", c1.TABLE_NAME, "`",
    " WHERE `",c1.COLUMN_NAME,"` LIKE '%", search, "%'" SEPARATOR "\nUNION\n") AS col
  INTO @sql
  FROM information_schema.columns c1
  WHERE c1.TABLE_SCHEMA = DATABASE();

  PREPARE stmt FROM @sql;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
END //
DELIMITER ;