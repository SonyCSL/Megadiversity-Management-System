# Copyright 2018 Sony Computer Science Laboratories, Inc.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table album
# ------------------------------------------------------------

DROP TABLE IF EXISTS `album`;

CREATE TABLE `album` (
  `album_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `create_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `owner_id` int(11) unsigned NOT NULL DEFAULT 1,
  `title` varchar(256) NOT NULL DEFAULT 'untitled album',
  `description` varchar(512) DEFAULT '',
  `permission_owner` tinyint(3) unsigned NOT NULL DEFAULT 7,
  `permission_members` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `permission_others` tinyint(3) unsigned NOT NULL DEFAULT 4,
  PRIMARY KEY (`album_id`),
  KEY `album_user` (`owner_id`),
  CONSTRAINT `album_user` FOREIGN KEY (`owner_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table album_shared_members
# ------------------------------------------------------------

DROP TABLE IF EXISTS `album_shared_members`;

CREATE TABLE `album_shared_members` (
  `album_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`album_id`,`user_id`),
  KEY `shared_user` (`user_id`),
  CONSTRAINT `shared_album` FOREIGN KEY (`album_id`) REFERENCES `album` (`album_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `shared_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table album_tagmap
# ------------------------------------------------------------

DROP TABLE IF EXISTS `album_tagmap`;

CREATE TABLE `album_tagmap` (
  `album_id` int(11) unsigned NOT NULL,
  `tag_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`album_id`,`tag_id`),
  KEY `tagmap_tag` (`tag_id`),
  CONSTRAINT `tagmap_album` FOREIGN KEY (`album_id`) REFERENCES `album` (`album_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tagmap_tag` FOREIGN KEY (`tag_id`) REFERENCES `album_tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table album_tags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `album_tags`;

CREATE TABLE `album_tags` (
  `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tagname` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tagname` (`tagname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table device
# ------------------------------------------------------------

DROP TABLE IF EXISTS `device`;

CREATE TABLE `device` (
  `device_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) unsigned NOT NULL,
  `devicename` tinytext CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `timezone` float NOT NULL DEFAULT 0,
  `location` geometry NOT NULL,
  `is_fixed` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `altitude` float DEFAULT NULL,
  `attached_height` int(5) DEFAULT NULL,
  `place` tinytext DEFAULT NULL,
  `comment` varchar(512) DEFAULT '',
  `is_disabled` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`device_id`),
  SPATIAL KEY `location` (`location`),
  KEY `user_id` (`owner_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table device_manage
# ------------------------------------------------------------

DROP TABLE IF EXISTS `device_manage`;

CREATE TABLE `device_manage` (
  `managekey` varchar(64) NOT NULL DEFAULT '',
  `device_id` int(11) unsigned NOT NULL,
  `config_string` varchar(1024) DEFAULT NULL,
  `report_string` varchar(1024) DEFAULT NULL,
  `report_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`managekey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table information
# ------------------------------------------------------------

DROP TABLE IF EXISTS `information`;

CREATE TABLE `information` (
  `article_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `title` varchar(255) DEFAULT '',
  `body` text DEFAULT NULL,
  PRIMARY KEY (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table language
# ------------------------------------------------------------

DROP TABLE IF EXISTS `language`;

CREATE TABLE `language` (
  `language_id` tinyint(8) unsigned NOT NULL AUTO_INCREMENT,
  `language_code` varchar(5) NOT NULL DEFAULT '',
  `language_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `language` WRITE;
/*!40000 ALTER TABLE `language` DISABLE KEYS */;

INSERT INTO `language` (`language_id`, `language_code`, `language_name`)
VALUES
	(1,'en','English'),
	(2,'ja','日本語'),
	(3,'de','Deutsch'),
	(4,'es','Español'),
	(5,'fr','Français'),
	(6,'it','Italiano'),
	(7,'ko','한국어'),
	(8,'la','Latin'),
	(9,'nl','Nederlands'),
	(10,'zh-ji','中文(简体)'),
	(11,'zh-fa','中文(繁體)'),
	(12,'jaA','日本語(アイヌ)'),
	(13,'jaR','日本語(琉球方言)'),
	(14,'jaH','日本語(八丈方言)');

/*!40000 ALTER TABLE `language` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table system_mimetype
# ------------------------------------------------------------

DROP TABLE IF EXISTS `system_mimetype`;

CREATE TABLE `system_mimetype` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table system_unit
# ------------------------------------------------------------

DROP TABLE IF EXISTS `system_unit`;

CREATE TABLE `system_unit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ui_strings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ui_strings`;

CREATE TABLE `ui_strings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ui_translation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ui_translation`;

CREATE TABLE `ui_translation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table upload_identifier
# ------------------------------------------------------------

DROP TABLE IF EXISTS `upload_identifier`;

CREATE TABLE `upload_identifier` (
  `accesskey` varchar(64) NOT NULL DEFAULT '',
  `device_id` int(11) unsigned NOT NULL,
  `album_id` int(11) unsigned NOT NULL,
  `memo` varchar(512) DEFAULT '',
  PRIMARY KEY (`accesskey`),
  KEY `identifier_album` (`album_id`),
  CONSTRAINT `identifier_album` FOREIGN KEY (`album_id`) REFERENCES `album` (`album_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` char(64) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `group_id` tinyint(8) unsigned NOT NULL DEFAULT 2,
  `language_id` tinyint(8) unsigned NOT NULL DEFAULT 1,
  `message` tinytext DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `user_language` (`language_id`),
  KEY `user_group` (`group_id`),
  CONSTRAINT `user_group` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;

INSERT INTO `user` (`user_id`, `username`, `password`, `email`, `group_id`, `language_id`, `message`)
VALUES
	(1,'admin','8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918','admin@localhost',1,1,'admin');

/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table user_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_groups`;

CREATE TABLE `user_groups` (
  `group_id` tinyint(8) unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(255) NOT NULL DEFAULT '',
  `edit_all` tinyint(1) unsigned NOT NULL,
  `view_all` tinyint(1) unsigned NOT NULL,
  `upload_all` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `user_groups` WRITE;
/*!40000 ALTER TABLE `user_groups` DISABLE KEYS */;

INSERT INTO `user_groups` (`group_id`, `groupname`, `edit_all`, `view_all`, `upload_all`)
VALUES
	(1,'Administrator',1,1,1),
	(2,'General user',0,0,0),
	(3,'Adviser',1,0,0),
	(4,'Viewer',0,1,0),
	(5,'Uploader',0,0,1);

/*!40000 ALTER TABLE `user_groups` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
