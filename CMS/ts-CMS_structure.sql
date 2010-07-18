# Sequel Pro dump
# Version 2210
# http://code.google.com/p/sequel-pro
#
# Host: localhost (MySQL 5.1.48)
# Database: ts-CMS
# Generation Time: 2010-07-18 15:08:48 +0200
# ************************************************************

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table bans
# ------------------------------------------------------------

DROP TABLE IF EXISTS `bans`;

CREATE TABLE `bans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `author` varchar(255) DEFAULT NULL,
  `announcement` text,
  `raw_announcement` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;



# Dump of table countries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) DEFAULT NULL,
  `flagfile` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=814 DEFAULT CHARSET=utf8;



# Dump of table invitations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `invitations`;

CREATE TABLE `invitations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `invited_playerid` int(11) unsigned NOT NULL DEFAULT '0',
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `expiration` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
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
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
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
) ENGINE=InnoDB AUTO_INCREMENT=21867 DEFAULT CHARSET=utf8 COMMENT='The played matches in the league';



# Dump of table matches_edit_stats
# ------------------------------------------------------------

DROP TABLE IF EXISTS `matches_edit_stats`;

CREATE TABLE `matches_edit_stats` (
  `id` int(11) unsigned NOT NULL,
  `playerid` int(11) unsigned NOT NULL,
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `team2_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `team1_points` int(11) NOT NULL DEFAULT '0',
  `team2_points` int(11) NOT NULL DEFAULT '0',
  KEY `timestamp` (`timestamp`),
  KEY `playerid` (`playerid`),
  CONSTRAINT `matches_edit_stats_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The match editing history';



# Dump of table messages_storage
# ------------------------------------------------------------

DROP TABLE IF EXISTS `messages_storage`;

CREATE TABLE `messages_storage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(11) unsigned NOT NULL,
  `subject` varchar(50) NOT NULL,
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `message` varchar(2000) NOT NULL,
  `from_team` tinyint(1) unsigned NOT NULL,
  `recipients` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=379654 DEFAULT CHARSET=utf8 COMMENT='The message storage';



# Dump of table messages_users_connection
# ------------------------------------------------------------

DROP TABLE IF EXISTS `messages_users_connection`;

CREATE TABLE `messages_users_connection` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` int(11) unsigned NOT NULL,
  `playerid` int(11) unsigned NOT NULL,
  `in_inbox` tinyint(1) unsigned NOT NULL,
  `in_outbox` tinyint(1) unsigned NOT NULL,
  `msg_status` set('new','read','replied') NOT NULL DEFAULT 'new',
  `msg_replied_team` tinyint(1) unsigned DEFAULT '0',
  `msg_replied_to_msgid` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `msgid` (`msgid`),
  KEY `playerid` (`playerid`),
  KEY `msg_status` (`msg_status`),
  CONSTRAINT `messages_users_connection_ibfk_2` FOREIGN KEY (`msgid`) REFERENCES `messages_storage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `messages_users_connection_ibfk_3` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=353982 DEFAULT CHARSET=utf8 COMMENT='Connects messages to users';



# Dump of table misc_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `misc_data`;

CREATE TABLE `misc_data` (
  `last_maintenance` varchar(10) DEFAULT '00.00.0000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table news
# ------------------------------------------------------------

DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `author` varchar(255) DEFAULT NULL,
  `announcement` text,
  `raw_announcement` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=202 DEFAULT CHARSET=utf8;



# Dump of table online_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `online_users`;

CREATE TABLE `online_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playerid` int(11) unsigned NOT NULL,
  `username` varchar(50) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `playerid` (`playerid`),
  CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='list of online users';



# Dump of table players
# ------------------------------------------------------------

DROP TABLE IF EXISTS `players`;

CREATE TABLE `players` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `external_playerid` varchar(50) NOT NULL,
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL,
  `last_teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `suspended` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `teamid` (`teamid`)
) ENGINE=InnoDB AUTO_INCREMENT=3124 DEFAULT CHARSET=utf8 COMMENT='suspended: 0 active; 1 maint-deleted; 2 disabled; 3 banned';



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
) ENGINE=InnoDB AUTO_INCREMENT=7672 DEFAULT CHARSET=utf8;



# Dump of table players_profile
# ------------------------------------------------------------

DROP TABLE IF EXISTS `players_profile`;

CREATE TABLE `players_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playerid` int(11) unsigned NOT NULL DEFAULT '0',
  `location` int(11) NOT NULL DEFAULT '1',
  `user_comment` varchar(1000) NOT NULL DEFAULT '',
  `raw_user_comment` varchar(1000) NOT NULL DEFAULT '',
  `admin_comments` mediumtext NOT NULL,
  `raw_admin_comments` mediumtext NOT NULL,
  `joined` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_login` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `logo_url` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7672 DEFAULT CHARSET=utf8 COMMENT='the players profile data';



# Dump of table static_pages
# ------------------------------------------------------------

DROP TABLE IF EXISTS `static_pages`;

CREATE TABLE `static_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(255) DEFAULT NULL,
  `page_name` tinytext,
  `content` mediumtext NOT NULL,
  `last_modified` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
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
) ENGINE=InnoDB AUTO_INCREMENT=932 DEFAULT CHARSET=utf8;



# Dump of table teams_overview
# ------------------------------------------------------------

DROP TABLE IF EXISTS `teams_overview`;

CREATE TABLE `teams_overview` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `teamid` int(11) unsigned NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '1200',
  `activity` varchar(20) NOT NULL DEFAULT '0.00 (0.00)',
  `member_count` int(11) unsigned NOT NULL DEFAULT '1',
  `any_teamless_player_can_join` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `teamid` (`teamid`),
  CONSTRAINT `teams_overview_ibfk_1` FOREIGN KEY (`teamid`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1015 DEFAULT CHARSET=utf8 COMMENT='deleted: 0 new; 1 active; 2 deleted; 3 re-activated';



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
  `num_matches_played` int(11) NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB AUTO_INCREMENT=1015 DEFAULT CHARSET=utf8;



# Dump of table visits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playerid` int(11) unsigned NOT NULL DEFAULT '0',
  `ip-address` varchar(100) NOT NULL DEFAULT '0.0.0.0.0',
  `host` varchar(100) DEFAULT NULL,
  `forwarded_for` varchar(200) DEFAULT NULL,
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `playerid` (`playerid`),
  KEY `ip-address` (`ip-address`),
  KEY `host` (`host`),
  CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`playerid`) REFERENCES `players` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=771416 DEFAULT CHARSET=utf8;






/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
