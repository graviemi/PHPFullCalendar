UPDATE `event`
	SET `duration` = 0 WHERE `duration` IS NULL;
ALTER TABLE `event`
	MODIFY COLUMN `duration` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'minutes';
