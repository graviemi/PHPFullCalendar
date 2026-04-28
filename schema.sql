SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `calendar` (
  `calendar_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `calendarACL` (
  `source` varchar(255) CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `type` enum('user','group','special') NOT NULL DEFAULT 'user',
  `authorization` tinyint(3) UNSIGNED NOT NULL,
  `calendar_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `event` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `calendar_id` int(10) UNSIGNED NOT NULL,
  `start` int(10) UNSIGNED NOT NULL COMMENT 'UNIX timestamp / 60 (minutes)',
  `duration` int(10) UNSIGNED DEFAULT NULL COMMENT 'minutes',
  `until` int(10) UNSIGNED DEFAULT NULL COMMENT 'UNIX timestamp / 60 (minutes)',
  `frequency` enum('dayly','weekly','monthly','yearly') DEFAULT NULL COMMENT 'RRule',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `globalACL` (
  `source` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `type` enum('user','group','special') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'user',
  `authorization` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `resource` (
  `resource_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `calendar_id` int(10) UNSIGNED NOT NULL,
  `resourceType_id` int(10) UNSIGNED NOT NULL,
  `data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `resourceAttribut` (
  `resourceAttribut_id` int(10) UNSIGNED NOT NULL,
  `resourceType_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `resourceType` (
  `resourceType_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ressourceACL` (
  `identifier` varchar(255) NOT NULL,
  `type` enum('user','group','special') NOT NULL DEFAULT 'user',
  `authorization` tinyint(3) UNSIGNED NOT NULL,
  `ressource_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `calendar`
  ADD PRIMARY KEY (`calendar_id`);

ALTER TABLE `calendarACL`
  ADD PRIMARY KEY (`source`,`identifier`,`type`,`calendar_id`) USING BTREE,
  ADD KEY `what` (`calendar_id`) USING BTREE,
  ADD KEY `who` (`source`,`identifier`,`type`) USING BTREE;

ALTER TABLE `event`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `calendar_id` (`calendar_id`),
  ADD KEY `title` (`title`);

ALTER TABLE `globalACL`
  ADD PRIMARY KEY (`source`,`identifier`,`type`) USING BTREE;

ALTER TABLE `resource`
  ADD PRIMARY KEY (`resource_id`),
  ADD UNIQUE KEY `calendar_id` (`calendar_id`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `resourceAttribut`
  ADD PRIMARY KEY (`resourceAttribut_id`),
  ADD UNIQUE KEY `resourceType_id` (`resourceType_id`,`name`);

ALTER TABLE `resourceType`
  ADD PRIMARY KEY (`resourceType_id`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `ressourceACL`
  ADD UNIQUE KEY `right` (`identifier`,`type`,`ressource_id`) USING BTREE,
  ADD KEY `who` (`identifier`,`type`) USING BTREE,
  ADD KEY `what` (`ressource_id`);

ALTER TABLE `calendar`
  MODIFY `calendar_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `event`
  MODIFY `event_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `resource`
  MODIFY `resource_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `resourceAttribut`
  MODIFY `resourceAttribut_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `resourceType`
  MODIFY `resourceType_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `calendarACL`
  ADD CONSTRAINT `calendarACL_ibfk_1` FOREIGN KEY (`calendar_id`) REFERENCES `calendar` (`calendar_id`) ON DELETE CASCADE;

ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`calendar_id`) REFERENCES `calendar` (`calendar_id`) ON DELETE CASCADE;

ALTER TABLE `ressourceACL`
  ADD CONSTRAINT `ressourceACL_ibfk_1` FOREIGN KEY (`ressource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;
COMMIT;
