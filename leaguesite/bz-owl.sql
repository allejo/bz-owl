# Sequel Pro dump
# Version 2492
# http://code.google.com/p/sequel-pro
#
# Host: localhost (MySQL 5.1.57)
# Database: bz-owl
# Generation Time: 2011-05-17 18:36:36 +0200
# ************************************************************

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table CMS
# ------------------------------------------------------------

DROP TABLE IF EXISTS `CMS`;

CREATE TABLE `CMS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestPath` varchar(1000) NOT NULL DEFAULT '/',
  `title` varchar(256) NOT NULL DEFAULT 'Untitled',
  `addon` varchar(256) NOT NULL DEFAULT 'staticPageEditor',
  PRIMARY KEY (`id`),
  KEY `requestPath` (`requestPath`(255))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

LOCK TABLES `CMS` WRITE;
/*!40000 ALTER TABLE `CMS` DISABLE KEYS */;
INSERT INTO `CMS` (`id`,`requestPath`,`title`,`addon`)
VALUES
	(1,'/','Home','staticPageEditor'),
	(2,'PM/','Mail overview','pmSystem'),
	(3,'News/','News','newsSystem'),
	(4,'Rules/','Rules','staticPageEditor'),
	(5,'FAQ/','FAQ','staticPageEditor'),
	(6,'Links/','Links','staticPageEditor'),
	(7,'Contact/','Contact','staticPageEditor'),
	(8,'Bans/','Bans','newsSystem'),
	(9,'Config/','Config','configSystem');

/*!40000 ALTER TABLE `CMS` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table countries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) DEFAULT NULL,
  `flagfile` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table ERROR_LOG
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ERROR_LOG`;

CREATE TABLE `ERROR_LOG` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `msg` varchar(2000) DEFAULT 'Something went wrong. You should see an actual error message instead.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table invitations
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
  CONSTRAINT `invitations_ibfk_1` FOREIGN KEY (`invited_playerid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `invitations_ibfk_2` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table matches
# ------------------------------------------------------------

DROP TABLE IF EXISTS `matches`;

CREATE TABLE `matches` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playerid` int(11) unsigned NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `team2_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `team1_points` int(11) NOT NULL DEFAULT '0',
  `team2_points` int(11) NOT NULL DEFAULT '0',
  `team1_new_score` int(11) NOT NULL DEFAULT '1200',
  `team2_new_score` int(11) NOT NULL DEFAULT '1200',
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `playerid` (`playerid`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The played matches in the league';



# Dump of table matches_edit_stats
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
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  KEY `playerid` (`playerid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The match editing history';



# Dump of table misc_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `misc_data`;

CREATE TABLE `misc_data` (
  `last_maintenance` varchar(10) DEFAULT '0000-00-00',
  `last_servertracker_query` int(11) unsigned NOT NULL DEFAULT '0',
  `db.version` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table newssystem
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



# Dump of table online_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `online_users`;

CREATE TABLE `online_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL,
  `username` varchar(50) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='list of online users';



# Dump of table players
# ------------------------------------------------------------

DROP TABLE IF EXISTS `players`;

CREATE TABLE `players` (
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



# Dump of table players_passwords
# ------------------------------------------------------------

DROP TABLE IF EXISTS `players_passwords`;

CREATE TABLE `players_passwords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playerid` int(11) unsigned NOT NULL DEFAULT '0',
  `password` varchar(32) NOT NULL DEFAULT '',
  `password_encoding` set('md5') NOT NULL DEFAULT 'md5',
  PRIMARY KEY (`id`),
  KEY `playerid` (`playerid`),
  CONSTRAINT `players_passwords_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table players_profile
# ------------------------------------------------------------

DROP TABLE IF EXISTS `players_profile`;

CREATE TABLE `players_profile` (
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
  CONSTRAINT `players_profile_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='the players profile data';



# Dump of table pmsystem.msg.recipients.teams
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem.msg.recipients.teams`;

CREATE TABLE `pmsystem.msg.recipients.teams` (
  `msgid` int(11) unsigned DEFAULT NULL,
  `teamid` int(11) unsigned DEFAULT NULL,
  KEY `msgid` (`msgid`),
  KEY `teamid` (`teamid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table pmsystem.msg.recipients.users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem.msg.recipients.users`;

CREATE TABLE `pmsystem.msg.recipients.users` (
  `msgid` int(11) unsigned DEFAULT NULL,
  `userid` int(11) unsigned DEFAULT NULL,
  KEY `msgid` (`msgid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table pmsystem.msg.storage
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem.msg.storage`;

CREATE TABLE `pmsystem.msg.storage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(11) unsigned NOT NULL,
  `subject` varchar(50) NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The message storage';



# Dump of table pmsystem.msg.users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pmsystem.msg.users`;

CREATE TABLE `pmsystem.msg.users` (
  `msgid` int(11) unsigned NOT NULL,
  `userid` int(11) unsigned NOT NULL,
  `folder` set('inbox','outbox') NOT NULL DEFAULT 'inbox',
  `msg_status` set('new','read') NOT NULL DEFAULT 'new',
  `msg_replied_to_msgid` int(11) unsigned DEFAULT NULL,
  KEY `msgid` (`msgid`),
  KEY `userid` (`userid`),
  KEY `msg_status` (`msg_status`),
  CONSTRAINT `pmsystem@002emsg@002eusers_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `pmsystem.msg.storage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pmsystem@002emsg@002eusers_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Connects messages to users';



# Dump of table servertracker
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



# Dump of table static_pages
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



# Dump of table teams
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams`;

CREATE TABLE `teams` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT 'think of a good name',
  `leader_playerid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table teams_overview
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams_overview`;

CREATE TABLE `teams_overview` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '1200',
  `num_matches_played` int(11) unsigned NOT NULL DEFAULT '0',
  `activity` varchar(20) NOT NULL DEFAULT '0.00 (0.00)',
  `member_count` int(11) unsigned NOT NULL DEFAULT '1',
  `any_teamless_player_can_join` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_overview_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='deleted: 0 new; 1 active; 2 deleted; 3 re-activated';



# Dump of table teams_permissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams_permissions`;

CREATE TABLE `teams_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `locked_by_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_permissions_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table teams_profile
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams_profile`;

CREATE TABLE `teams_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `num_matches_won` int(11) NOT NULL DEFAULT '0',
  `num_matches_draw` int(11) NOT NULL DEFAULT '0',
  `num_matches_lost` int(11) NOT NULL DEFAULT '0',
  `description` mediumtext NOT NULL,
  `raw_description` mediumtext NOT NULL,
  `logo_url` varchar(200) DEFAULT NULL,
  `created` varchar(10) NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`id`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_profile_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table visits
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
  CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;






/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
