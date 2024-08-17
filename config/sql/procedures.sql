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

-- necessary because views can't refer to @@time_zone
DROP FUNCTION IF EXISTS session_timezone;
DELIMITER //
CREATE FUNCTION session_timezone() RETURNS VARCHAR(100) DETERMINISTIC
BEGIN
	RETURN @@time_zone;
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

-- Set all words first letter to uppercase (mysql only)
# DROP FUNCTION IF EXISTS ucwords;
# CREATE FUNCTION ucwords(s VARCHAR(255)) RETURNS VARCHAR(255)
# BEGIN
#   declare c int;
#   declare x VARCHAR(255);
#   declare y VARCHAR(255);
#   declare z VARCHAR(255);
#
#   set x = UPPER( SUBSTRING( s, 1, 1));
#   set y = SUBSTR( s, 2);
#   set c = instr( y, ' ');
#
#   while c > 0
#     do
#       set z = SUBSTR( y, 1, c);
#       set x = CONCAT( x, z);
#       set z = UPPER( SUBSTR( y, c+1, 1));
#       set x = CONCAT( x, z);
#       set y = SUBSTR( y, c+2);
#       set c = INSTR( y, ' ');
#   end while;
#   set x = CONCAT(x, y);
#   return x;
# END;