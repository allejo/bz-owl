# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.1.72)
# Datenbank: bz-owl
# Erstellungsdauer: 2014-03-26 21:39:29 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Export von Tabelle cms_bans
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cms_bans`;

CREATE TABLE `cms_bans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip-address` varchar(100) NOT NULL DEFAULT '0.0.0.0.0',
  `expiration_timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '0000-00-00 00:00:00 means a ban won''t expire',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle cms_paths
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cms_paths`;

CREATE TABLE `cms_paths` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_path` varchar(1000) NOT NULL DEFAULT '/',
  `title` varchar(256) NOT NULL DEFAULT 'Untitled',
  `addon` varchar(256) NOT NULL DEFAULT 'staticPageEditor',
  PRIMARY KEY (`id`),
  KEY `requestPath` (`request_path`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `cms_paths` WRITE;
/*!40000 ALTER TABLE `cms_paths` DISABLE KEYS */;

INSERT INTO `cms_paths` (`id`, `request_path`, `title`, `addon`)
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
	(12,'Logout/','Logout','logoutSystem'),
	(13,'Online/','Online users','onlineUserSystem'),
	(14,'Matches/','Matches','matchServices');

/*!40000 ALTER TABLE `cms_paths` ENABLE KEYS */;
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
  `msg` varchar(2000) DEFAULT 'Something went wrong. You should see an actual error message instead.',
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle invitations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `invitations`;

CREATE TABLE `invitations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `expiration` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `invited_playerid` (`userid`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `invitations_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `invitations_ibfk_2` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle matches
# ------------------------------------------------------------

DROP TABLE IF EXISTS `matches`;

CREATE TABLE `matches` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1_id` int(11) unsigned NOT NULL DEFAULT '0',
  `team2_id` int(11) unsigned NOT NULL DEFAULT '0',
  `team1_points` int(11) NOT NULL DEFAULT '0',
  `team2_points` int(11) NOT NULL DEFAULT '0',
  `team1_new_score` int(11) NOT NULL DEFAULT '1200',
  `team2_new_score` int(11) NOT NULL DEFAULT '1200',
  `duration` int(11) unsigned NOT NULL DEFAULT '30',
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `playerid` (`userid`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The played matches in the league';



# Export von Tabelle matches_edit_stats
# ------------------------------------------------------------

DROP TABLE IF EXISTS `matches_edit_stats`;

CREATE TABLE `matches_edit_stats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(11) unsigned NOT NULL,
  `userid` int(11) unsigned NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1_id` int(11) unsigned NOT NULL DEFAULT '0',
  `team2_id` int(11) unsigned NOT NULL DEFAULT '0',
  `team1_points` int(11) NOT NULL DEFAULT '0',
  `team2_points` int(11) NOT NULL DEFAULT '0',
  `duration` int(11) unsigned NOT NULL DEFAULT '30',
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  KEY `playerid` (`userid`),
  KEY `timestamp` (`timestamp`),
  CONSTRAINT `matches_edit_stats_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The match editing history';



# Export von Tabelle misc_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `misc_data`;

CREATE TABLE `misc_data` (
  `last_maintenance` varchar(10) DEFAULT '0000-00-00',
  `last_servertracker_query` int(11) unsigned NOT NULL DEFAULT '0',
  `db.version` int(11) NOT NULL DEFAULT '0'
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
  `page` varchar(1000) NOT NULL DEFAULT 'news',
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
  KEY `playerid` (`userid`),
  KEY `userid` (`userid`),
  CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='list of online users';



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
  KEY `msgid` (`msgid`),
  KEY `userid` (`userid`),
  KEY `msg_status` (`msg_status`),
  CONSTRAINT `pmsystem_msg_users_ibfk_4` FOREIGN KEY (`msgid`) REFERENCES `pmsystem_msg_storage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pmsystem_msg_users_ibfk_5` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
  `last_modified` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
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
  `activityNew` float NOT NULL DEFAULT '0',
  `activityOld` float NOT NULL DEFAULT '0',
  `member_count` int(11) unsigned NOT NULL DEFAULT '1',
  `any_teamless_player_can_join` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`teamid`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_overview_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='deleted: 0 new; 1 active; 2 deleted; 3 re-activated; 4 inact';



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
  `num_matches_total` int(11) NOT NULL DEFAULT '0',
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



# Export von Tabelle users_profile
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users_profile`;

CREATE TABLE `users_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `location` int(11) NOT NULL DEFAULT '1',
  `utc` tinyint(2) NOT NULL DEFAULT '0',
  `user_comment` varchar(1500) NOT NULL DEFAULT '',
  `raw_user_comment` varchar(1500) NOT NULL DEFAULT '',
  `admin_comments` mediumtext NOT NULL,
  `raw_admin_comments` mediumtext NOT NULL,
  `joined` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_login` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `logo_url` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `playerid` (`userid`),
  CONSTRAINT `users_profile_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='the players profile data';



# Export von Tabelle users_rejected_logins
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users_rejected_logins`;

CREATE TABLE `users_rejected_logins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `ip-address` varchar(100) NOT NULL DEFAULT '0.0.0.0.0',
  `forwarded_for` varchar(200) DEFAULT NULL,
  `host` varchar(100) DEFAULT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `reason` enum('unknown','fieldMissing','emptyUserName','emptyPassword','tooLongPassword','tooLongUserName','passwordMismatch','missconfiguration') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Log failed logins with their reason';



# Export von Tabelle visits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `ip-address` varchar(100) NOT NULL DEFAULT '0.0.0.0.0',
  `host` varchar(100) DEFAULT NULL,
  `forwarded_for` varchar(200) DEFAULT NULL,
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `playerid` (`userid`),
  KEY `ip-address` (`ip-address`),
  KEY `host` (`host`),
  CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
