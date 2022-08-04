-- --------------------------------------------
-- @version 3.4.26
--
-- @author: Michael Mifsud <info@tropotek.com>
-- --------------------------------------------

SELECT count(*) into @colCnt FROM information_schema.columns WHERE table_name = 'file' AND column_name = 'selected' and table_schema = DATABASE();
IF @colCnt = 0 THEN
    ALTER TABLE `file` ADD COLUMN `selected` BOOL NOT NULL DEFAULT FALSE AFTER `notes`;
END IF;
