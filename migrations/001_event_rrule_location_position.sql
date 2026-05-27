ALTER TABLE `event`
    ADD COLUMN `rrule`    text         NULL AFTER `frequency`,
    ADD COLUMN `location` text         NULL AFTER `description`,
    ADD COLUMN `position` varchar(32)  NULL AFTER `location`;
