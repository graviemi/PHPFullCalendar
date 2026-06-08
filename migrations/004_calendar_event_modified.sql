ALTER TABLE `calendar`
	ADD COLUMN `modified` INT UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP());

CREATE OR REPLACE TRIGGER `event_after_insert`
AFTER INSERT ON `event`
FOR EACH ROW
	UPDATE `calendar` SET `modified` = UNIX_TIMESTAMP() WHERE `calendar_id` = NEW.`calendar_id`;

CREATE OR REPLACE TRIGGER `event_after_update`
AFTER UPDATE ON `event`
FOR EACH ROW
	UPDATE `calendar` SET `modified` = UNIX_TIMESTAMP() WHERE `calendar_id` = NEW.`calendar_id`;

CREATE OR REPLACE TRIGGER `event_after_delete`
AFTER DELETE ON `event`
FOR EACH ROW
	UPDATE `calendar` SET `modified` = UNIX_TIMESTAMP() WHERE `calendar_id` = OLD.`calendar_id`;

ALTER TABLE `event`
	ADD COLUMN `modified` INT UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP());

CREATE OR REPLACE TRIGGER `event_before_insert`
BEFORE INSERT ON `event`
FOR EACH ROW
	SET NEW.`modified` = UNIX_TIMESTAMP();

CREATE OR REPLACE TRIGGER `event_before_update`
BEFORE UPDATE ON `event`
FOR EACH ROW
	SET NEW.`modified` = UNIX_TIMESTAMP();
