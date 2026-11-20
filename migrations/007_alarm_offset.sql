ALTER TABLE `alarm` ADD COLUMN IF NOT EXISTS `offset` INT NOT NULL DEFAULT 0 AFTER `related`;

UPDATE `alarm` SET `offset` =
	IF(LEFT(`trigger`, 1) = '-', -1, 1)
		* CAST(REGEXP_REPLACE(`trigger`, '[^0-9]', '') AS SIGNED)
		* CASE RIGHT(`trigger`, 1) WHEN 'W' THEN 10080 WHEN 'D' THEN 1440 WHEN 'H' THEN 60 ELSE 1 END
	+ IF(`repeat` > 0 AND `duration` IS NOT NULL,
		CAST(`repeat` AS SIGNED)
			* CAST(REGEXP_REPLACE(`duration`, '[^0-9]', '') AS SIGNED)
			* CASE RIGHT(`duration`, 1) WHEN 'W' THEN 10080 WHEN 'D' THEN 1440 WHEN 'H' THEN 60 ELSE 1 END,
		0)
WHERE `trigger` REGEXP '^-?P([0-9]+[WD]|T[0-9]+[HM])$'
	AND (`repeat` = 0 OR `duration` IS NULL OR `duration` REGEXP '^P([0-9]+[WD]|T[0-9]+[HM])$');

SELECT `alarm_id`, `label`, `trigger`, `repeat`, `duration` AS `step`
FROM `alarm`
WHERE `trigger` NOT REGEXP '^-?P([0-9]+[WD]|T[0-9]+[HM])$'
	OR (`repeat` > 0 AND `duration` IS NOT NULL AND `duration` NOT REGEXP '^P([0-9]+[WD]|T[0-9]+[HM])$');
