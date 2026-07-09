ALTER TABLE `display` ADD COLUMN IF NOT EXISTS `modified` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `email` ADD COLUMN IF NOT EXISTS `modified` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `alarm` ADD COLUMN IF NOT EXISTS `modified` int(10) unsigned NOT NULL DEFAULT 0;

ALTER TABLE `display` DROP FOREIGN KEY IF EXISTS `descriptionFK`;
ALTER TABLE `display` ADD CONSTRAINT `descriptionFK` FOREIGN KEY (`description_id`) REFERENCES `description` (`description_id`) ON DELETE RESTRICT;
ALTER TABLE `email` DROP FOREIGN KEY IF EXISTS `emailDescriptionFK`;
ALTER TABLE `email` ADD CONSTRAINT `emailDescriptionFK` FOREIGN KEY (`description_id`) REFERENCES `description` (`description_id`) ON DELETE RESTRICT;
ALTER TABLE `alarm` DROP FOREIGN KEY IF EXISTS `displayFK`;
ALTER TABLE `alarm` ADD CONSTRAINT `displayFK` FOREIGN KEY (`display_id`) REFERENCES `display` (`display_id`) ON DELETE RESTRICT;
ALTER TABLE `alarm` DROP FOREIGN KEY IF EXISTS `emailFK`;
ALTER TABLE `alarm` ADD CONSTRAINT `emailFK` FOREIGN KEY (`email_id`) REFERENCES `email` (`email_id`) ON DELETE RESTRICT;
ALTER TABLE `alarm` DROP FOREIGN KEY IF EXISTS `audioFK`;
ALTER TABLE `alarm` ADD CONSTRAINT `audioFK` FOREIGN KEY (`audio_id`) REFERENCES `audio` (`audio_id`) ON DELETE RESTRICT;

DROP TRIGGER IF EXISTS `description_after_update`;
DROP TRIGGER IF EXISTS `description_before_delete`;
DROP TRIGGER IF EXISTS `recipient_after_update`;
DROP TRIGGER IF EXISTS `recipient_before_delete`;
DROP TRIGGER IF EXISTS `emailRecipient_after_insert`;
DROP TRIGGER IF EXISTS `emailRecipient_after_delete`;
DROP TRIGGER IF EXISTS `display_after_update`;
DROP TRIGGER IF EXISTS `display_before_delete`;
DROP TRIGGER IF EXISTS `email_after_update`;
DROP TRIGGER IF EXISTS `email_before_delete`;
DROP TRIGGER IF EXISTS `audio_after_update`;
DROP TRIGGER IF EXISTS `audio_before_delete`;
DROP TRIGGER IF EXISTS `alarm_after_update`;
DROP TRIGGER IF EXISTS `alarm_before_delete`;
DROP TRIGGER IF EXISTS `calendarAlarm_after_insert`;
DROP TRIGGER IF EXISTS `calendarAlarm_after_delete`;
DROP TRIGGER IF EXISTS `eventAlarm_after_insert`;
DROP TRIGGER IF EXISTS `eventAlarm_after_delete`;

DELIMITER ;;

CREATE TRIGGER `description_after_update`
AFTER UPDATE ON `description`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `display` SET `modified` = @t WHERE `description_id` = NEW.`description_id` AND `modified` < @t;
	UPDATE `email` SET `modified` = @t WHERE `description_id` = NEW.`description_id` AND `modified` < @t;
END;;

CREATE TRIGGER `recipient_after_update`
AFTER UPDATE ON `recipient`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `email` e JOIN `emailRecipient` er ON er.email_id = e.email_id
		SET e.`modified` = @t
		WHERE er.recipient_id = NEW.`recipient_id` AND e.`modified` < @t;
END;;

CREATE TRIGGER `recipient_before_delete`
BEFORE DELETE ON `recipient`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `email` e JOIN `emailRecipient` er ON er.email_id = e.email_id
		SET e.`modified` = @t
		WHERE er.recipient_id = OLD.`recipient_id` AND e.`modified` < @t;
END;;

CREATE TRIGGER `emailRecipient_after_insert`
AFTER INSERT ON `emailRecipient`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `email` SET `modified` = @t WHERE `email_id` = NEW.`email_id` AND `modified` < @t;
END;;

CREATE TRIGGER `emailRecipient_after_delete`
AFTER DELETE ON `emailRecipient`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `email` SET `modified` = @t WHERE `email_id` = OLD.`email_id` AND `modified` < @t;
END;;

CREATE TRIGGER `display_after_update`
AFTER UPDATE ON `display`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `alarm` SET `modified` = @t WHERE `display_id` = NEW.`display_id` AND `modified` < @t;
END;;

CREATE TRIGGER `email_after_update`
AFTER UPDATE ON `email`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `alarm` SET `modified` = @t WHERE `email_id` = NEW.`email_id` AND `modified` < @t;
END;;

CREATE TRIGGER `audio_after_update`
AFTER UPDATE ON `audio`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `alarm` SET `modified` = @t WHERE `audio_id` = NEW.`audio_id` AND `modified` < @t;
END;;

CREATE TRIGGER `alarm_after_update`
AFTER UPDATE ON `alarm`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `calendar` c JOIN `calendarAlarm` ca ON ca.calendar_id = c.calendar_id
		SET c.`modified` = @t
		WHERE ca.alarm_id = NEW.`alarm_id` AND c.`modified` < @t;
	UPDATE `event` e JOIN `eventAlarm` ea ON ea.event_id = e.event_id
		SET e.`modified` = @t
		WHERE ea.alarm_id = NEW.`alarm_id` AND e.`modified` < @t;
END;;

CREATE TRIGGER `alarm_before_delete`
BEFORE DELETE ON `alarm`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `calendar` c JOIN `calendarAlarm` ca ON ca.calendar_id = c.calendar_id
		SET c.`modified` = @t
		WHERE ca.alarm_id = OLD.`alarm_id` AND c.`modified` < @t;
	UPDATE `event` e JOIN `eventAlarm` ea ON ea.event_id = e.event_id
		SET e.`modified` = @t
		WHERE ea.alarm_id = OLD.`alarm_id` AND e.`modified` < @t;
END;;

CREATE TRIGGER `calendarAlarm_after_insert`
AFTER INSERT ON `calendarAlarm`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `calendar` SET `modified` = @t WHERE `calendar_id` = NEW.`calendar_id` AND `modified` < @t;
END;;

CREATE TRIGGER `calendarAlarm_after_delete`
AFTER DELETE ON `calendarAlarm`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `calendar` SET `modified` = @t WHERE `calendar_id` = OLD.`calendar_id` AND `modified` < @t;
END;;

CREATE TRIGGER `eventAlarm_after_insert`
AFTER INSERT ON `eventAlarm`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `event` SET `modified` = @t WHERE `event_id` = NEW.`event_id` AND `modified` < @t;
END;;

CREATE TRIGGER `eventAlarm_after_delete`
AFTER DELETE ON `eventAlarm`
FOR EACH ROW
BEGIN
	SET @t = UNIX_TIMESTAMP();
	UPDATE `event` SET `modified` = @t WHERE `event_id` = OLD.`event_id` AND `modified` < @t;
END;;

DELIMITER ;
