/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `alarm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audio` (
  `audio_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL COMMENT 'meaningfull for you',
  `mimetype` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sound` blob NOT NULL,
  PRIMARY KEY (`audio_id`),
  UNIQUE KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar` (
  `calendar_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `modified` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`calendar_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendarACL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendarACL` (
  `source` varchar(255) CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `type` enum('user','group','special') NOT NULL DEFAULT 'user',
  `authorization` tinyint(3) unsigned NOT NULL,
  `calendar_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`source`,`identifier`,`type`,`calendar_id`) USING BTREE,
  KEY `what` (`calendar_id`) USING BTREE,
  KEY `who` (`source`,`identifier`,`type`) USING BTREE,
  CONSTRAINT `calendarACL_ibfk_1` FOREIGN KEY (`calendar_id`) REFERENCES `calendar` (`calendar_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendarAlarm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendarAlarm` (
  `calendar_id` int(10) unsigned NOT NULL,
  `alarm_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `calendarAlarm` (`calendar_id`,`alarm_id`),
  KEY `alarmFK` (`alarm_id`),
  CONSTRAINT `alarmFK` FOREIGN KEY (`alarm_id`) REFERENCES `alarm` (`alarm_id`) ON DELETE CASCADE,
  CONSTRAINT `calendarFK` FOREIGN KEY (`calendar_id`) REFERENCES `calendar` (`calendar_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `description` (
  `description_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `contents` text NOT NULL,
  PRIMARY KEY (`description_id`),
  UNIQUE KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `display`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `display` (
  `display_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `description_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`display_id`),
  UNIQUE KEY `label` (`label`),
  KEY `descriptionFK` (`description_id`),
  CONSTRAINT `descriptionFK` FOREIGN KEY (`description_id`) REFERENCES `description` (`description_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emailRecipient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `emailRecipient` (
  `email_id` int(10) unsigned NOT NULL,
  `recipient_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `emailRecipient` (`email_id`,`recipient_id`),
  KEY `recipient_id` (`recipient_id`),
  CONSTRAINT `emailRecipient_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `email` (`email_id`) ON DELETE CASCADE,
  CONSTRAINT `emailRecipient_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `recipient` (`recipient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_id` int(10) unsigned NOT NULL,
  `start` int(10) unsigned NOT NULL COMMENT 'UNIX timestamp / 60 (minutes)',
  `duration` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'minutes',
  `until` int(10) unsigned DEFAULT NULL COMMENT 'UNIX timestamp / 60 (minutes)',
  `rrule` text DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` text DEFAULT NULL,
  `position` varchar(32) DEFAULT NULL,
  `color` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `modified` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `calendar_id` (`calendar_id`),
  KEY `title` (`title`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`calendar_id`) REFERENCES `calendar` (`calendar_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `event_before_insert`
BEFORE INSERT ON `event`
FOR EACH ROW
	SET NEW.`modified` = UNIX_TIMESTAMP() */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `event_after_insert`
AFTER INSERT ON `event`
FOR EACH ROW
	UPDATE `calendar` SET `modified` = UNIX_TIMESTAMP() WHERE `calendar_id` = NEW.`calendar_id` */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `event_before_update`
BEFORE UPDATE ON `event`
FOR EACH ROW
	SET NEW.`modified` = UNIX_TIMESTAMP() */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `event_after_update`
AFTER UPDATE ON `event`
FOR EACH ROW
	UPDATE `calendar` SET `modified` = UNIX_TIMESTAMP() WHERE `calendar_id` = NEW.`calendar_id` */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `event_after_delete`
AFTER DELETE ON `event`
FOR EACH ROW
	UPDATE `calendar` SET `modified` = UNIX_TIMESTAMP() WHERE `calendar_id` = OLD.`calendar_id` */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `eventAlarm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventAlarm` (
  `event_id` int(10) unsigned NOT NULL,
  `alarm_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `eventAlarm` (`event_id`,`alarm_id`),
  KEY `alarm` (`alarm_id`),
  CONSTRAINT `alarm` FOREIGN KEY (`alarm_id`) REFERENCES `alarm` (`alarm_id`) ON DELETE CASCADE,
  CONSTRAINT `eventAlarm_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `globalACL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `globalACL` (
  `source` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `type` enum('user','group','special') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'user',
  `authorization` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`source`,`identifier`,`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recipient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipient` (
  `recipient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`recipient_id`),
  UNIQUE KEY `address` (`address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource` (
  `resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `calendar_id` int(10) unsigned NOT NULL,
  `resourceType_id` int(10) unsigned NOT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  UNIQUE KEY `calendar_id` (`calendar_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resourceAttribut`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resourceAttribut` (
  `resourceAttribut_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resourceType_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`resourceAttribut_id`),
  UNIQUE KEY `resourceType_id` (`resourceType_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resourceType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resourceType` (
  `resourceType_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`resourceType_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ressourceACL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ressourceACL` (
  `identifier` varchar(255) NOT NULL,
  `type` enum('user','group','special') NOT NULL DEFAULT 'user',
  `authorization` tinyint(3) unsigned NOT NULL,
  `ressource_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `right` (`identifier`,`type`,`ressource_id`) USING BTREE,
  KEY `who` (`identifier`,`type`) USING BTREE,
  KEY `what` (`ressource_id`),
  CONSTRAINT `ressourceACL_ibfk_1` FOREIGN KEY (`ressource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

