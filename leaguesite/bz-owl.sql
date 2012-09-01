# ************************************************************
# Sequel Pro SQL dump
# Version 3408
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.1.63)
# Datenbank: scherenschnitte
# Erstellungsdauer: 2012-07-25 13:11:59 +0200
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Export von Tabelle CMS
# ------------------------------------------------------------

DROP TABLE IF EXISTS `CMS`;

CREATE TABLE `CMS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestPath` varchar(1000) NOT NULL DEFAULT '/',
  `title` varchar(256) NOT NULL DEFAULT 'Untitled',
  `addon` varchar(256) NOT NULL DEFAULT 'staticPageEditor',
  PRIMARY KEY (`id`),
  KEY `requestPath` (`requestPath`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `CMS` WRITE;
/*!40000 ALTER TABLE `CMS` DISABLE KEYS */;

INSERT INTO `CMS` (`id`, `requestPath`, `title`, `addon`)
VALUES
	(1,'/','Home','staticPageEditor'),
	(2,'PM/','Mail overview','pmSystem'),
	(3,'News/','News','newsSystem'),
	(4,'Rules/','Rules','staticPageEditor'),
	(5,'FAQ/','FAQ','staticPageEditor'),
	(6,'Links/','Links','staticPageEditor'),
	(7,'Contact/','Contact','staticPageEditor'),
	(8,'Bans/','Bans','newsSystem'),
	(9,'Config/','Config','configSystem'),
	(10,'Teams/','Teams','teamSystem'),
	(11,'Pages/','Page assignments','pageSystem'),
	(12,'index.php','Coda 2 bug','staticPageEditor');

/*!40000 ALTER TABLE `CMS` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle countries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) DEFAULT NULL,
  `flagfile` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle ERROR_LOG
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ERROR_LOG`;

CREATE TABLE `ERROR_LOG` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `msg` varchar(2000) DEFAULT 'Something went wrong. You should see an actual error message instead.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `ERROR_LOG` WRITE;
/*!40000 ALTER TABLE `ERROR_LOG` DISABLE KEYS */;

INSERT INTO `ERROR_LOG` (`id`, `timestamp`, `msg`)
VALUES
	(1,'2012-07-25 18:12:45','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset'),
	(2,'2012-07-25 18:14:41','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset'),
	(3,'2012-07-25 18:14:43','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset'),
	(4,'2012-07-25 18:14:45','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset'),
	(5,'2012-07-25 18:15:00','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset'),
	(6,'2012-07-25 18:23:46','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset'),
	(7,'2012-07-25 18:25:20','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset'),
	(8,'2012-07-25 18:37:10','SQLSTATE error code: 42S02, driver error code: 1146\ndriver error message: Table \'scherenschnitte.players\' doesn\'t exist\n\nexecuting prepared statement failed,  query was:\nSELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`, IF(`pmsystem_msg_storage`.`author_id`<>0, (SELECT `name` FROM `players` WHERE `id`=`author_id`),:author) AS `author` FROM `pmsystem_msg_storage`, `pmsystem_msg_users` WHERE `pmsystem_msg_users`.`userid`=:userid AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid` AND `folder`=:folder ORDER BY `pmsystem_msg_storage`.`id` DESC LIMIT :limit OFFSET :offset');

/*!40000 ALTER TABLE `ERROR_LOG` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle invitations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `invitations`;

CREATE TABLE `invitations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `invited_playerid` int(11) unsigned NOT NULL DEFAULT '0',
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `expiration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `invited_playerid` (`invited_playerid`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `invitations_ibfk_1` FOREIGN KEY (`invited_playerid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `invitations_ibfk_2` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle matches
# ------------------------------------------------------------

DROP TABLE IF EXISTS `matches`;

CREATE TABLE `matches` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1ID` int(11) unsigned NOT NULL DEFAULT '0',
  `team2ID` int(11) unsigned NOT NULL DEFAULT '0',
  `team1_points` int(11) NOT NULL DEFAULT '0',
  `team2_points` int(11) NOT NULL DEFAULT '0',
  `team1_new_score` int(11) NOT NULL DEFAULT '1200',
  `team2_new_score` int(11) NOT NULL DEFAULT '1200',
  `duration` int(11) NOT NULL DEFAULT '30',
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `playerid` (`userid`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The played matches in the league';



# Export von Tabelle matches_edit_stats
# ------------------------------------------------------------

DROP TABLE IF EXISTS `matches_edit_stats`;

CREATE TABLE `matches_edit_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(11) unsigned NOT NULL,
  `playerid` int(11) unsigned NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `team2_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `team1_points` int(11) NOT NULL DEFAULT '0',
  `team2_points` int(11) NOT NULL DEFAULT '0',
  `duration` int(11) NOT NULL DEFAULT '30',
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  KEY `playerid` (`playerid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The match editing history';



# Export von Tabelle misc_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `misc_data`;

CREATE TABLE `misc_data` (
  `last_maintenance` varchar(10) DEFAULT '0000-00-00',
  `last_servertracker_query` int(11) unsigned NOT NULL DEFAULT '0',
  `db.version` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle newssystem
# ------------------------------------------------------------

DROP TABLE IF EXISTS `newssystem`;

CREATE TABLE `newssystem` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL DEFAULT 'News',
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `author` varchar(255) DEFAULT NULL,
  `msg` text,
  `raw_msg` text,
  `page` varchar(1000) NOT NULL DEFAULT 'News/',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle online_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `online_users`;

CREATE TABLE `online_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL,
  `username` varchar(50) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='list of online users';

LOCK TABLES `online_users` WRITE;
/*!40000 ALTER TABLE `online_users` DISABLE KEYS */;

INSERT INTO `online_users` (`id`, `userid`, `username`, `last_activity`)
VALUES
	(29,1,'admin','2012-07-25 19:14:14');

/*!40000 ALTER TABLE `online_users` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle pmsystem_msg_recipients_teams
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem_msg_recipients_teams`;

CREATE TABLE `pmsystem_msg_recipients_teams` (
  `msgid` int(11) unsigned DEFAULT NULL,
  `teamid` int(11) unsigned DEFAULT NULL,
  KEY `msgid` (`msgid`),
  KEY `teamid` (`teamid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle pmsystem_msg_recipients_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem_msg_recipients_users`;

CREATE TABLE `pmsystem_msg_recipients_users` (
  `msgid` int(11) unsigned DEFAULT NULL,
  `userid` int(11) unsigned DEFAULT NULL,
  KEY `msgid` (`msgid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle pmsystem_msg_storage
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem_msg_storage`;

CREATE TABLE `pmsystem_msg_storage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(11) unsigned NOT NULL,
  `subject` varchar(50) NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `message` varchar(4000) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The message storage';



# Export von Tabelle pmsystem_msg_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem_msg_users`;

CREATE TABLE `pmsystem_msg_users` (
  `msgid` int(11) unsigned NOT NULL,
  `userid` int(11) unsigned NOT NULL,
  `folder` set('inbox','outbox') NOT NULL DEFAULT 'inbox',
  `msg_status` set('new','read') NOT NULL DEFAULT 'new',
  `msg_replied_to_msgid` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`msgid`),
  KEY `msgid` (`msgid`),
  KEY `userid` (`userid`),
  KEY `msg_status` (`msg_status`),
  CONSTRAINT `pmsystem_msg_users_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `pmsystem_msg_storage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pmsystem_msg_users_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Connects messages to users';



# Export von Tabelle servertracker
# ------------------------------------------------------------

DROP TABLE IF EXISTS `servertracker`;

CREATE TABLE `servertracker` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `servername` tinytext,
  `serveraddress` tinytext NOT NULL,
  `owner` tinytext NOT NULL,
  `cur_players_total` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle static_pages
# ------------------------------------------------------------

DROP TABLE IF EXISTS `static_pages`;

CREATE TABLE `static_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` int(11) NOT NULL,
  `page` varchar(1000) NOT NULL,
  `content` mediumtext NOT NULL,
  `raw_content` mediumtext NOT NULL,
  `last_modified` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle teams
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams`;

CREATE TABLE `teams` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT 'think of a good name',
  `leader_userid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle teams_overview
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams_overview`;

CREATE TABLE `teams_overview` (
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '1200',
  `num_matches_played` int(11) unsigned NOT NULL DEFAULT '0',
  `activityNew` float NOT NULL DEFAULT '0',
  `activityOld` float NOT NULL DEFAULT '0',
  `member_count` int(11) unsigned NOT NULL DEFAULT '1',
  `any_teamless_player_can_join` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`teamid`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_overview_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='deleted: 0 new; 1 active; 2 deleted; 3 re-activated';



# Export von Tabelle teams_permissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams_permissions`;

CREATE TABLE `teams_permissions` (
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `locked_by_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`teamid`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_permissions_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle teams_profile
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams_profile`;

CREATE TABLE `teams_profile` (
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `num_matches_won` int(11) NOT NULL DEFAULT '0',
  `num_matches_draw` int(11) NOT NULL DEFAULT '0',
  `num_matches_lost` int(11) NOT NULL DEFAULT '0',
  `description` mediumtext NOT NULL,
  `raw_description` mediumtext NOT NULL,
  `logo_url` varchar(200) DEFAULT NULL,
  `created` varchar(10) NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`teamid`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_profile_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(50) NOT NULL,
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL,
  `last_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `status` set('active','deleted','login disabled','banned') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `teamid` (`teamid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The players'' main data';

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` (`id`, `external_id`, `teamid`, `name`, `last_teamid`, `status`)
VALUES
	(1,'',0,'admin',0,'active');

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle users_passwords
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users_passwords`;

CREATE TABLE `users_passwords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `password` varchar(255) NOT NULL DEFAULT '',
  `cipher` set('md5','blowfish') NOT NULL DEFAULT 'blowfish',
  PRIMARY KEY (`id`),
  KEY `playerid` (`userid`),
  CONSTRAINT `users_passwords_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `users_passwords` WRITE;
/*!40000 ALTER TABLE `users_passwords` DISABLE KEYS */;

INSERT INTO `users_passwords` (`id`, `userid`, `password`, `cipher`)
VALUES
	(1,1,'$2a$09$th1s1sSp4rt4.O.RlySureLMP23INbDfy7SFSM0yA4fa52plSb31C','blowfish');

/*!40000 ALTER TABLE `users_passwords` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle users_permissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users_permissions`;

CREATE TABLE `users_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL,
  `permissions` varchar(1023) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  CONSTRAINT `users_permissions_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Applies to local login only at the moment';

LOCK TABLES `users_permissions` WRITE;
/*!40000 ALTER TABLE `users_permissions` DISABLE KEYS */;

INSERT INTO `users_permissions` (`id`, `userid`, `permissions`)
VALUES
	(1,1,'a:1:{s:18:\"allow_add_messages\";b:0;}');

/*!40000 ALTER TABLE `users_permissions` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle users_profile
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users_profile`;

CREATE TABLE `users_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playerid` int(11) unsigned NOT NULL DEFAULT '0',
  `location` int(11) NOT NULL DEFAULT '1',
  `UTC` tinyint(2) NOT NULL DEFAULT '0',
  `user_comment` varchar(1500) NOT NULL DEFAULT '',
  `raw_user_comment` varchar(1500) NOT NULL DEFAULT '',
  `admin_comments` mediumtext NOT NULL,
  `raw_admin_comments` mediumtext NOT NULL,
  `joined` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_login` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `logo_url` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `playerid` (`playerid`),
  CONSTRAINT `users_profile_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='the players profile data';



# Export von Tabelle visits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playerid` int(11) unsigned NOT NULL DEFAULT '0',
  `ip-address` varchar(100) NOT NULL DEFAULT '0.0.0.0.0',
  `host` varchar(100) DEFAULT NULL,
  `forwarded_for` varchar(200) DEFAULT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `playerid` (`playerid`),
  KEY `ip-address` (`ip-address`),
  KEY `host` (`host`),
  CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;

INSERT INTO `visits` (`id`, `playerid`, `ip-address`, `host`, `forwarded_for`, `timestamp`)
VALUES
	(1,1,'192.168.1.10','192.168.1.10','','2012-07-23 19:29:40'),
	(2,1,'192.168.1.10','192.168.1.10','','2012-07-23 19:29:57'),
	(3,1,'192.168.1.10','192.168.1.10','','2012-07-23 19:33:11'),
	(4,1,'192.168.1.10','192.168.1.10','','2012-07-23 19:37:11'),
	(5,1,'192.168.1.10','192.168.1.10','','2012-07-23 19:43:21'),
	(6,1,'192.168.1.10','192.168.1.10','','2012-07-23 19:53:47'),
	(7,1,'192.168.1.10','192.168.1.10','','2012-07-23 19:56:20'),
	(8,1,'192.168.1.10','192.168.1.10','','2012-07-23 20:05:42'),
	(9,1,'192.168.1.10','192.168.1.10','','2012-07-23 20:06:48'),
	(10,1,'192.168.1.10','192.168.1.10','','2012-07-23 20:07:51'),
	(11,1,'192.168.1.10','192.168.1.10','','2012-07-23 20:08:24'),
	(12,1,'192.168.1.10','192.168.1.10','','2012-07-23 20:09:08'),
	(13,1,'192.168.1.10','192.168.1.10','','2012-07-23 20:09:56'),
	(14,1,'192.168.1.10','192.168.1.10','','2012-07-23 20:23:32'),
	(15,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:03:38'),
	(16,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:04:03'),
	(17,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:05:53'),
	(18,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:06:33'),
	(19,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:08:34'),
	(20,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:08:59'),
	(21,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:10:38'),
	(22,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:12:22'),
	(23,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:12:35'),
	(24,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:23:42'),
	(25,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:25:17'),
	(26,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:26:10'),
	(27,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:36:02'),
	(28,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:37:04'),
	(29,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:44:07'),
	(30,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:44:29'),
	(31,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:45:59'),
	(32,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:46:14'),
	(33,1,'192.168.1.10','192.168.1.10','','2012-07-25 18:47:17'),
	(34,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:01:05'),
	(35,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:01:59'),
	(36,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:02:41'),
	(37,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:03:03'),
	(38,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:07:40'),
	(39,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:09:40'),
	(40,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:12:46'),
	(41,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:13:30'),
	(42,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:13:50'),
	(43,1,'192.168.1.10','192.168.1.10','','2012-07-25 19:14:14');

/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
