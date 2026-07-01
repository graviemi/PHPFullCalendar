CREATE TABLE `description` (
	`description_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`label` varchar(255) NOT NULL,
	`contents` text NOT NULL,
	PRIMARY KEY (`description_id`),
	UNIQUE KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `recipient` (
	`recipient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`address` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
	`name` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`recipient_id`),
	UNIQUE KEY `address` (`address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `audio` (
	`audio_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`label` varchar(255) NOT NULL COMMENT 'meaningfull for you',
	`mimetype` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
	`sound` blob NOT NULL,
	PRIMARY KEY (`audio_id`),
	UNIQUE KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `display` (
	`display_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`label` varchar(255) NOT NULL,
	`description_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`display_id`),
	UNIQUE KEY `label` (`label`),
	KEY `descriptionFK` (`description_id`),
	CONSTRAINT `descriptionFK` FOREIGN KEY (`description_id`) REFERENCES `description` (`description_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email` (
	`email_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`label` varchar(255) NOT NULL,
	`summary` varchar(255) NOT NULL,
	`description_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`email_id`),
	UNIQUE KEY `label` (`label`),
	KEY `emailDescriptionFK` (`description_id`),
	CONSTRAINT `emailDescriptionFK` FOREIGN KEY (`description_id`) REFERENCES `description` (`description_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `emailRecipient` (
	`email_id` int(10) unsigned NOT NULL,
	`recipient_id` int(10) unsigned NOT NULL,
	UNIQUE KEY `emailRecipient` (`email_id`,`recipient_id`),
	KEY `recipient_id` (`recipient_id`),
	CONSTRAINT `emailRecipient_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `email` (`email_id`) ON DELETE CASCADE,
	CONSTRAINT `emailRecipient_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `recipient` (`recipient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `alarm` (
	`alarm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`label` varchar(255) DEFAULT NULL,
	`audio_id` int(10) unsigned DEFAULT NULL,
	`display_id` int(10) unsigned DEFAULT NULL,
	`email_id` int(10) unsigned DEFAULT NULL,
	`trigger` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
	`repeat` int(10) unsigned NOT NULL DEFAULT 0,
	`duration` varchar(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
	`related` enum('start','end') NOT NULL DEFAULT 'start',
	PRIMARY KEY (`alarm_id`),
	UNIQUE KEY `label` (`label`),
	KEY `audioFK` (`audio_id`),
	KEY `displayFK` (`display_id`),
	KEY `emailFK` (`email_id`),
	CONSTRAINT `audioFK` FOREIGN KEY (`audio_id`) REFERENCES `audio` (`audio_id`) ON DELETE SET NULL,
	CONSTRAINT `displayFK` FOREIGN KEY (`display_id`) REFERENCES `display` (`display_id`) ON DELETE SET NULL,
	CONSTRAINT `emailFK` FOREIGN KEY (`email_id`) REFERENCES `email` (`email_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `calendarAlarm` (
	`calendar_id` int(10) unsigned NOT NULL,
	`alarm_id` int(10) unsigned NOT NULL,
	UNIQUE KEY `calendarAlarm` (`calendar_id`,`alarm_id`),
	KEY `alarmFK` (`alarm_id`),
	CONSTRAINT `alarmFK` FOREIGN KEY (`alarm_id`) REFERENCES `alarm` (`alarm_id`) ON DELETE CASCADE,
	CONSTRAINT `calendarFK` FOREIGN KEY (`calendar_id`) REFERENCES `calendar` (`calendar_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `eventAlarm` (
	`event_id` int(10) unsigned NOT NULL,
	`alarm_id` int(10) unsigned NOT NULL,
	UNIQUE KEY `eventAlarm` (`event_id`,`alarm_id`),
	KEY `alarm` (`alarm_id`),
	CONSTRAINT `alarm` FOREIGN KEY (`alarm_id`) REFERENCES `alarm` (`alarm_id`) ON DELETE CASCADE,
	CONSTRAINT `eventAlarm_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
