<?php
	// this file does update old bz-owl databases
	// to the version that this file ships with
	// NOTE: This does not update tables created by 3rd party add-ons!
	
	// current db version
	define('MAX_DB_VERSION', 5);
	
	
	
	if (!(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])))
	{
		header('Content-Type: text/plain');
		exit('File to be called on command line interface (cli) only!' . "\n");
	}
	
	// use the simple site.php as it's quite convenient to use
	
	// default to path within repository
	global $installationPath;
	$installationPath = dirname(dirname(__FILE__)) . '/web/';
	
	// use custom installation path info if available
	$installationPathFile = dirname(__FILE__) . '/installationPath.txt';
	if (file_exists($installationPathFile) && is_readable($installationPathFile))
	{
		// remove newlines from end of file, if there is one
		$installationPath = rtrim(file_get_contents($installationPathFile), "\n");
		
		// if relative path -> if path does not begin with /
		if (strcasecmp(substr($path, 0, 1), '/') !== 0)
		{
			$installationPath = dirname(__FILE__) . '/' . $installationPath;
		}
	}
	// load site.php
	require_once $installationPath . 'CMS/site.php';
	$site = new site();
	
	// do not run if website is in production mode
	if (!$config->getValue('debugSQL') && (!$config->getValue('maintenance.now') || !$config->getValue('maintenance.updateDB')))
	{
		exit('Can only update DB if live website is down for maintenance.' . "\n");
	}
	
	
	// assume if table CMS does not exist db version is oldest, newest otherwise
	$query = $db->prepare('SHOW COLUMNS FROM `misc_data` WHERE `Field`=?');
	$db->execute($query, 'db.version');
	$rows = $db->fetchAll($query);
	$db->free($query);
	
	if (count($rows) < 1)
	{
		// if CMS table exists, assume db is at newest version, 0 otherwise
		$query = $db->prepare('SHOW TABLES LIKE ?');
		$db->execute($query, 'CMS');
		$rows = $db->fetchAll($query);
		$db->free($query);
		
		$dbVersion = (count($rows) >= 1) ? MAX_DB_VERSION : 0;
	} else
	{
		// check version from looking at table entries
		$query = $db->SQL('SELECT `db.Version` FROM `misc_data`');
		$numRows = 0;
		$newestVersionFound = 0;
		while ($row = $db->fetchRow($query))
		{
			// if more than 1 row has been found, set $numRows to 2
			if ($numRows !== 2)
			{
				$numRows = ($numRows > 1) ? 2 : $numRows+1;
			}
			
			// update newest version if newer version found than in last row
			if ($row['db.Version'] > $newestVersionFound)
			{
				$newestVersionFound = $row['db.Version'];
			}
		}
		$db->free($query);
		
		$dbVersion = ($numRows < 1) ? MAX_DB_VERSION : $newestVersionFound;
		
		// if $dbVersion is 0, updateVersion0 will create the table in question instead
		if (($dbVersion > 0) && ($numRows < 1))
		{
			$db->SQL('INSERT INTO `misc_data` (`db.Version`) VALUES (' . $dbVersion . ')');
		} elseif (($dbVersion > 0) && $numRows > 1)
		{
			$db->SQL('DELETE FROM `misc_data`');
			$db->SQL('INSERT INTO `misc_data` (`db.Version`) VALUES (' . $dbVersion . ')');
		}
	}
	
	// call the updating code now that we know the current db version
	// use a while loop instead of iterating inside the update function
	// so we keep the used stack memory low
	while ($dbVersion < MAX_DB_VERSION)
	{
		updateDB($dbVersion);
	}
	echo ('The used database (' . $config->getValue('dbName')
		  . ') is up to date (version ' . $dbVersion . ').' . "\n");
	exit();
	
	
	function status($message='')
	{
		echo($message . "\n");
	}
	
	function updateDB(&$version)
	{
		echo('DB at version ' . $version . "\n");
		
		if ($version < MAX_DB_VERSION)
		{
			echo('Updating...' . "\n");
			
			$updateVersionFunction = 'updateVersion' . $version;
			$updateWorked = $updateVersionFunction();
			if (!$updateWorked)
			{
				exit('A non-recoverable error occured in ' . strval($updateVersionFunction) . '() -> update stopped!' . "\n");
			}
			tagDBVersion(intval($version)+1);
			
			// update counter
			$version++;
		}
	}
	
	function tagDBVersion($version)
	{
		global $db;
		
		status('Tagging DB with version ' . $version);
		status();
		
		$query = $db->SQL('SELECT count(*) AS `numRows` FROM `misc_data');
		$rows = $db->fetchRow($query);
		$db->free($query);
		if ($rows['numRows'] < 1)
		{
			$query = $db->prepare('INSERT INTO `misc_data` (`db.version`) VALUES (?)');
		} else
		{
			$query = $db->prepare('UPDATE `misc_data` SET `db.version` = ?');
		}
		$db->execute($query, $version);
	}
	
	function updateVersion0()
	{
		global $db;
		
		// updates v0 to v1
		
		// staticPage changes
		status('Changing helper table for staticPageEditor');
		$db->SQL('ALTER TABLE `static_pages` CHANGE `page_name` `page` varchar(1000) NOT NULL');
		status('Fixing home static page');
		$db->SQL("UPDATE `static_pages` SET `page`='/' WHERE `page`='_/'");
		
		
		// PM DB changes
		status('Renaming PM tables');
		$db->SQL('RENAME TABLE `messages_storage` TO `pmsystem.msg.storage`');
		$db->SQL('CREATE TABLE `pmsystem.msg.recipients.teams` (
				 `msgid` int(11) unsigned DEFAULT NULL,
				 `teamid` int(11) unsigned DEFAULT NULL,
				 KEY `msgid` (`msgid`),
				 KEY `teamid` (`teamid`)
				 ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
		$db->SQL('CREATE TABLE `pmsystem.msg.recipients.users` (
				 `msgid` int(11) unsigned DEFAULT NULL,
				 `userid` int(11) unsigned DEFAULT NULL,
				 KEY `msgid` (`msgid`),
				 KEY `userid` (`userid`)
				 ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
		
		// transform data to new format
		$convertRecipientTeam=$db->prepare('INSERT INTO `pmsystem.msg.recipients.teams` (`msgid`,`teamid`) VALUES(?,?)');
		$convertRecipientPlayer=$db->prepare('INSERT INTO `pmsystem.msg.recipients.users` (`msgid`,`userid`) VALUES(?,?)');
		
		status('Converting PM recipients to pmsystem.msg.recipients.teams and pmsystem.msg.recipients.users');
		echo('.');
		$time = time();
		$query = $db->SQL('SELECT * FROM `pmsystem.msg.storage`');
		while($row = $db->fetchRow($query))
		{
			// one progress dot every 5 seconds
			if (time() - $time > 5)
			{
				echo('.');
				$time = time();
			}
			
			if (isset($row['from_team']) && strcasecmp($row['from_team'], '1') === 0)
			{
				$db->execute($convertRecipientTeam, array($row['id'], $row['recipients']));
				$db->free($convertRecipientTeam);
			} else
			{
				$recipients = explode(' ', $row['recipients']);
				foreach($recipients AS $recipient)
				{
					$db->execute($convertRecipientPlayer, array($row['id'], $recipient));
					$db->free($convertRecipientPlayer);
				}
				unset($recipient);
			}
		}
		$db->free($query);
		echo("\n");
		
		echo('Removing recipients column from pmsystem.msg.storage' . "\n" . '...' . "\n");
		$db->SQL('ALTER TABLE `pmsystem.msg.storage` DROP `recipients`');
		echo('Removing from_team column from pmsystem.msg.storage' . "\n" . '...' . "\n");
		$db->SQL('ALTER TABLE `pmsystem.msg.storage` DROP `from_team`');
		
		
		status('Altering connecting tables between message and users');
		$db->SQL('ALTER TABLE `messages_users_connection` DROP FOREIGN KEY `messages_users_connection_ibfk_3`');
		$db->SQL('RENAME TABLE `messages_users_connection` TO `pmsystem.msg.users`');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` DROP `id`');
		
		status('Moving mailbox detection from seperate columns to one column of type set');
		$db->SQL("ALTER TABLE `pmsystem.msg.users` ADD `folder` set('inbox','outbox') NOT NULL DEFAULT 'inbox'  AFTER `playerid`");
		status('Adding messages to new inbox');
		$db->SQL("UPDATE `pmsystem.msg.users` SET `folder`='inbox' WHERE `in_inbox`='1'");
		status('Adding messages to new outbox');
		$db->SQL("UPDATE `pmsystem.msg.users` SET `folder`='outbox' WHERE `in_outbox`='1'");
		status('Deleting replied value from status set as we have `msg_replied_to_msgid`');
		$db->SQL("ALTER TABLE `pmsystem.msg.users` CHANGE `msg_status` `msg_status` set('new','read') NOT NULL DEFAULT 'new'");
		status('Deleting `msg_replied_team` because it is not used anywhere');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` DROP `msg_replied_team`');
		status('Deleting messages from old inbox');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` DROP `in_inbox`');
		status('Deleting messages from old outbox');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` DROP `in_outbox`');
		
		status('Renaming playerid column in pmsystem.msg.users to userid');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` DROP INDEX `playerid`');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` CHANGE `playerid` `userid` int(11) UNSIGNED NOT NULL');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` ADD INDEX  (`userid`)');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` ADD FOREIGN KEY (`userid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');

		$db->SQL('ALTER TABLE `pmsystem.msg.users` DROP INDEX `msg_status`');
		$db->SQL('ALTER TABLE `pmsystem.msg.users` ADD INDEX  (`msg_status`)');
		
		
		status('Converting news and bans tables to newsSystem add-on');
		$db->SQL('RENAME TABLE `news` TO `newssystem`');
		
		status('Inserting title and page column to newsSystem table and renaming all announcements to msgs');
		$db->SQL("ALTER TABLE `newssystem` ADD `title` varchar(256) NOT NULL DEFAULT 'News'  AFTER `id`");
		$db->SQL('ALTER TABLE `newssystem` CHANGE `announcement` `msg` text NULL DEFAULT NULL');
		$db->SQL('ALTER TABLE `newssystem` CHANGE `raw_announcement` `raw_msg` text NULL DEFAULT NULL');
		$db->SQL("ALTER TABLE `newssystem` ADD `page` varchar(1000) NOT NULL DEFAULT 'news'  AFTER `raw_msg`");
		
		status('Force update page column to news just in case DBMS does not do that automatically');
		$db->SQL("UPDATE `newssystem` SET `page`='News/'");
		
		status('Inserting ban entries to newsSystem');
		$query = $db->SQL('SELECT * FROM `bans`');
		$bansInsertQuery = $db->prepare('INSERT INTO `newssystem` (`title`, `timestamp`, `author`, `msg`, `raw_msg`, `page`)'
										. ' VALUES (?, ?, ?, ?, ?, ?)');
		while ($row = $db->fetchRow($query))
		{
			$db->execute($bansInsertQuery, array('Ban', $row['timestamp'], $row['author'],
												 $row['announcement'], $row['raw_announcement'], 'Bans/'));
		}
		$db->free($query);
		
		status('Removing old bans table');
		$db->SQL('DROP TABLE `bans`');
		
		
		status('Renaming playerid to userid in online_users table');
		$db->SQL('ALTER TABLE `online_users` DROP FOREIGN KEY `online_users_ibfk_1`');
		$db->SQL('ALTER TABLE `online_users` CHANGE `playerid` `userid` int(11) UNSIGNED NOT NULL');
		$db->SQL('ALTER TABLE `online_users` ADD INDEX  (`userid`)');
		// truncate table to have a low index number at end of foreign key
		$db->SQL('TRUNCATE `online_users`');
		$db->SQL('ALTER TABLE `online_users` ADD FOREIGN KEY (`userid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
		
		status('Renaming exteral_playerid to external_id in players table');
		$db->SQL('ALTER TABLE `players` CHANGE `external_playerid` `external_id` varchar(50) NOT NULL');
		
		status('Creating ERROR_LOG table');
		$db->SQL('DROP TABLE IF EXISTS `ERROR_LOG`');
		$db->SQL("CREATE TABLE `ERROR_LOG` (
				 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				 `msg` varchar(2000) DEFAULT 'Something went wrong. You should see an actual error message instead.',
				 PRIMARY KEY (`id`)
				 ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		
		
		status('Creating Content Management System (CMS) table');
		$db->SQL("CREATE TABLE `CMS` (
				 `id` int(11) NOT NULL AUTO_INCREMENT,
				 `requestPath` varchar(1000) NOT NULL DEFAULT '/',
				 `title` varchar(256) NOT NULL DEFAULT 'Untitled',
				 `addon` varchar(256) NOT NULL DEFAULT 'staticPageEditor',
				 PRIMARY KEY (`id`),
				 KEY `requestPath` (`requestPath`(255))
				 ) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8");
		
		status('Filling CMS table with default data');
		$db->SQL("INSERT INTO `CMS` (`id`,`requestPath`,`title`,`addon`)
				 VALUES
				 (1, '/', 'Home', 'staticPageEditor'),
				 (2, 'PM/', 'Mail overview', 'pmSystem'),
				 (3, 'News/', 'News', 'newsSystem'),
				 (4, 'Rules/', 'Rules', 'staticPageEditor'),
				 (5, 'FAQ/', 'FAQ', 'staticPageEditor'),
				 (6, 'Links/', 'Links', 'staticPageEditor'),
				 (7, 'Contact/', 'Contact', 'staticPageEditor'),
				 (8, 'Bans/', 'Bans', 'newsSystem'),
				 (9, 'Config/','Config','configSystem')");
		
		status('Updating last_maintenance column of misc_data to YYYY-MM-DD format');
		$db->SQL("ALTER TABLE `misc_data` CHANGE `last_maintenance` `last_maintenance` varchar(10) NULL DEFAULT '0000-00-00'");
		
		status('Adding DB version column (db.version) to misc_data');
		$db->SQL("ALTER TABLE `misc_data` ADD `db.version` int(11) NOT NULL DEFAULT '0'  AFTER `last_servertracker_query`");
		
		
		return true;
	}
	
	function updateVersion1()
	{
		global $db;
		
		status('Renaming pmSystem tables: Changing . to _ in table names and cleaning its table contents.');
		$db->SQL('RENAME TABLE `pmsystem.msg.storage` TO `pmsystem_msg_storage`');
		$db->SQL('RENAME TABLE `pmsystem.msg.recipients.teams` TO `pmsystem_msg_recipients_teams`');
		$db->SQL('RENAME TABLE `pmsystem.msg.recipients.users` TO `pmsystem_msg_recipients_users`');
		$db->SQL('RENAME TABLE `pmsystem.msg.users` TO `pmsystem_msg_users`');
		$db->SQL("ALTER TABLE `pmsystem_msg_storage` CHANGE `message` `message` varchar(4000) NULL DEFAULT ''");
		
		status('Changing timestamps from varchar(20) to varchar(19) at least');
		$db->SQL("ALTER TABLE `pmsystem_msg_storage` CHANGE `timestamp` `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$db->SQL("ALTER TABLE `matches` CHANGE `timestamp` `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$db->SQL("ALTER TABLE `matches_edit_stats` CHANGE `timestamp` `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$db->SQL("ALTER TABLE `newssystem` CHANGE `timestamp` `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$db->SQL("ALTER TABLE `players_profile` CHANGE `joined` `joined` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$db->SQL("ALTER TABLE `players_profile` CHANGE `last_login` `last_login` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00'");
		
		status('Registering new teamSystem add-on');
		$db->SQL("INSERT INTO `CMS` (`id`,`requestPath`,`title`,`addon`) VALUES (NULL,'Teams/','Teams','teamSystem')");
		
		status('Changing team tables to be used with new teamSystem add-on');
		$db->SQL('ALTER TABLE `teams_overview` DROP `id`');
		$db->SQL('ALTER TABLE `teams_overview` ADD PRIMARY KEY  (`teamid`)');
		$db->SQL('ALTER TABLE `teams_permissions` DROP `id`');
		$db->SQL('ALTER TABLE `teams_permissions` ADD PRIMARY KEY  (`teamid`)');
		$db->SQL('ALTER TABLE `teams_profile` DROP `id`');
		$db->SQL('ALTER TABLE `teams_profile` ADD PRIMARY KEY  (`teamid`)');
		$db->SQL("ALTER TABLE `teams_overview` CHANGE `activity` `activityNew` float NOT NULL DEFAULT '0'");
		$db->SQL("ALTER TABLE `teams_overview` ADD `activityOld` float NOT NULL DEFAULT '0'  AFTER `activityNew`");
		$db->SQL("ALTER TABLE `teams` CHANGE `leader_playerid` `leader_userid` int(11) NOT NULL DEFAULT '0'");
		
		
		return true;
	}
	
	function updateVersion2()
	{
		global $db;
		
		// find out if pageSystem add-on is added
		$query = $db->SQL("SELECT `requestPath` FROM `CMS` WHERE `addon`='pageSystem' LIMIT 1");
		if (count($db->fetchAll($query)) < 1)
		{
			// add pageSystem if pages path not already set
			$query = $db->SQL("SELECT `requestPath` FROM `CMS` WHERE `requestPath`='Pages/'");
			$pageSystemInsertionQuery = ("INSERT INTO `CMS` (`requestPath`, `title`, `addon`)
			VALUES
			('Pages/', 'Page assignments', 'pageSystem')");
			if (count($db->fetchAll($query)) < 1)
			{
				$db->SQL($pageSystemInsertionQuery);
			} else
			{
				// print out an error message and log the problem
				status('Adding pageSystem to CMS table failed because Pages/ entry was already set.');
				$db->logError('bz-owl-db-updater: ' . $pageSystemInsertionQuery . ' failed: Pages/ already set.');
				return false;
			}
		}
		
		
		return true;
	}
	
	function updateVersion3()
	{
		global $db;
		
		// rename playerid to userid in matches table
		$query = $db->SQL('ALTER TABLE `matches` DROP FOREIGN KEY `matches_ibfk_1`');
		if (!$query)
		{
			status('Could not change drop foreign key matches_ibfk_1 in matches table.');
			$db->logError('bz-owl-db-updater: Could not drop foreign key matches_ibfk_1 in matches table.');
			return false;
		}
		$query = $db->SQL('ALTER TABLE `matches` CHANGE `playerid` `userid` INT(11)  UNSIGNED  NOT NULL');
		if (!$query)
		{
			status('Could not change field playerid in matches table to userid.');
			$db->logError('bz-owl-db-updater: Could not change field playerid in matches table to userid.');
			return false;
		}
		$query = $db->SQL('ALTER TABLE `matches` ADD FOREIGN KEY (`userid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
		if (!$query)
		{
			status('Could not re-add foreign key matches_ibfk_1 in matches table.');
			$db->logError('bz-owl-db-updater: Could not re-add foreign key matches_ibfk_1 in matches table.');
			return false;
		}
		
		$query = $db->SQL('ALTER TABLE `matches_edit_stats` DROP FOREIGN KEY `matches_edit_stats_ibfk_1`');
		if (!$query)
		{
			status('Could not change drop foreign key matches_edit_stats_ibfk_1 in matches_edit_stats table.');
			$db->logError('bz-owl-db-updater: Could not drop foreign key matches_edit_stats_ibfk_1 in matches_edit_stats table.');
			return false;
		}
		$query = $db->SQL('ALTER TABLE `matches_edit_stats` CHANGE `playerid` `userid` INT(11)  UNSIGNED  NOT NULL');
		if (!$query)
		{
			status('Could not change field playerid in matches_edit_stats table to userid.');
			$db->logError('bz-owl-db-updater: Could not change field matches_edit_stats in matches table to userid.');
			return false;
		}
		$query = $db->SQL('ALTER TABLE `matches_edit_stats` ADD FOREIGN KEY (`userid`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
		if (!$query)
		{
			status('Could not re-add foreign key matches_ibfk_1 in matches table.');
			$db->logError('bz-owl-db-updater: Could not re-add foreign key matches_ibfk_1 in matches table.');
			return false;
		}
		
		// rename team1_teamid to team1ID and team2_teamid to team2ID in matches table
		$query = $db->SQL("ALTER TABLE `matches` CHANGE `team1_teamid` `team1ID` INT(11)  UNSIGNED  NOT NULL  DEFAULT '0'");
		if (!$query)
		{
			status('Could not change team1_teamid to team1ID in matches table.');
			$db->logError('bz-owl-db-updater: Could not change team1_teamid to team1ID in matches table.');
			return false;
		}
		$query = $db->SQL("ALTER TABLE `matches` CHANGE `team2_teamid` `team2ID` INT(11)  UNSIGNED  NOT NULL  DEFAULT '0'");
		if (!$query)
		{
			status('Could not change team2_teamid to team2ID in matches table.');
			$db->logError('bz-owl-db-updater: Could not change team2_teamid to team2ID in matches table.');
			return false;
		}
		
		$query = $db->SQL("ALTER TABLE `matches_edit_stats` CHANGE `team1_teamid` `team1ID` INT(11)  UNSIGNED  NOT NULL  DEFAULT '0'");
		if (!$query)
		{
			status('Could not change team1_teamid to team1ID in matches_edit_stats table.');
			$db->logError('bz-owl-db-updater: Could not change team1_teamid to team1ID in matches_edit_stats table.');
			return false;
		}
		$query = $db->SQL("ALTER TABLE `matches_edit_stats` CHANGE `team2_teamid` `team2ID` INT(11)  UNSIGNED  NOT NULL  DEFAULT '0'");
		if (!$query)
		{
			status('Could not change team2_teamid to team2ID in matches_edit_stats table.');
			$db->logError('bz-owl-db-updater: Could not change team2_teamid to team2ID in matches_edit_stats table.');
			return false;
		}
		
		// add duration column to matches
		$query = $db->SQL("ALTER TABLE `matches` ADD `duration` int(11) unsigned NOT NULL DEFAULT '30'");
		if (!$query)
		{
			status('Could not add duration to matches table.');
			$db->logError('bz-owl-db-updater: Could not add duration to matches table.');
			return false;
		}
		$query = $db->SQL("ALTER TABLE `matches_edit_stats` ADD `duration` int(11) unsigned NOT NULL DEFAULT '30'");
		if (!$query)
		{
			status('Could not add duration to matches_edit_stats table.');
			$db->logError('bz-owl-db-updater: Could not add duration to matches_edit_stats table.');
			return false;
		}
		
		// set all old durations to 30 minutes
		$db->SQL('UPDATE `matches` SET `duration`=30');
		$db->SQL('UPDATE `matches_edit_stats` SET `duration`=30');
		
		
		// add timestamp to ERROR_LOG table
		$query = $db->SQL("ALTER TABLE `ERROR_LOG` ADD `timestamp` varchar(19) NOT NULL DEFAULT '0000-00-00 00:00:00'  AFTER `msg`");
		if (!$query)
		{
			status('Could not add timestamp column to ERROR_LOG table.');
			$db->logError('bz-owl-db-updater: Could not add timestamp column to ERROR_LOG table.');
			return false;
		}
		
		
		return true;
	}
	
	function updateVersion4()
	{
		global $db;
				
		
		status('Replace player in table names with user ');
		$db->SQL('RENAME TABLE `players` TO `users`');
		$db->SQL('RENAME TABLE `players_passwords` TO `users_passwords`');
		$db->SQL('RENAME TABLE `players_profile` TO `users_profile`');
		
		
		status('Renaming several table fields to lower case');
		$db->SQL('ALTER TABLE `CMS` CHANGE `requestPath` `request_path` VARCHAR(1000)  NOT NULL  DEFAULT \'/\'');
		$db->SQL('ALTER TABLE `matches` CHANGE `team1ID` `team1_id` INT(11)  UNSIGNED  NOT NULL  DEFAULT \'0\'');
		$db->SQL('ALTER TABLE `matches` CHANGE `team2ID` `team2_id` INT(11)  UNSIGNED  NOT NULL  DEFAULT \'0\'');
		$db->SQL('ALTER TABLE `matches_edit_stats` CHANGE `team1ID` `team1_id` INT(11)  UNSIGNED  NOT NULL  DEFAULT \'0\'');
		$db->SQL('ALTER TABLE `matches_edit_stats` CHANGE `team2ID` `team2_id` INT(11)  UNSIGNED  NOT NULL  DEFAULT \'0\'');
		$db->SQL('ALTER TABLE `users_profile` CHANGE `UTC` `utc` TINYINT(2)  NOT NULL  DEFAULT \'0\'');
		
		status('Creating new cms bans and user rejected login tables');
		$db->SQL('CREATE TABLE `cms_bans` (
				 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				 `ip-address` varchar(100) NOT NULL DEFAULT \'0.0.0.0.0\',
				 `expiration_timestamp` varchar(19) NOT NULL DEFAULT \'0000-00-00 00:00:00\' COMMENT \'0000-00-00 00:00:00 means a ban won\'\'t expire\',
				 PRIMARY KEY (`id`)
				 ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8');
		
		$db->SQL('CREATE TABLE `users_rejected_logins` (
				 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				 `name` varchar(50) NOT NULL DEFAULT \'\',
				 `ip-address` varchar(100) NOT NULL DEFAULT \'0.0.0.0.0\',
				 `forwarded_for` varchar(200) DEFAULT NULL,
				 `host` varchar(100) DEFAULT NULL,
				 `timestamp` varchar(19) NOT NULL DEFAULT \'0000-00-00 00:00:00\',
				 `reason` enum(\'unknown\',\'fieldMissing\',\'emptyUserName\',\'emptyPassword\',\'tooLongPassword\',\'tooLongUserName\',\'passwordMismatch\',\'missconfiguration\') DEFAULT NULL,
				 PRIMARY KEY (`id`)
				 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT=\'Log failed logins with their reason\'');
		
		$db->SQL('ALTER TABLE `users_profile` DROP FOREIGN KEY `users_profile_ibfk_1`');
		$db->SQL('ALTER TABLE `users_profile` CHANGE `playerid` `userid` INT(11)  UNSIGNED  NOT NULL  DEFAULT \'0\'');
		$db->SQL('ALTER TABLE `users_profile` ADD FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
		$db->SQL('ALTER TABLE `users_passwords` DROP FOREIGN KEY `users_passwords_ibfk_1`');
		$db->SQL('ALTER TABLE `users_passwords` CHANGE `playerid` `userid` INT(11)  UNSIGNED  NOT NULL  DEFAULT \'0\'');
		$db->SQL('ALTER TABLE `users_passwords` ADD FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
		$db->SQL('ALTER TABLE `visits` DROP FOREIGN KEY `visits_ibfk_1`');
		$db->SQL('ALTER TABLE `visits` CHANGE `playerid` `userid` INT(11)  UNSIGNED  NOT NULL  DEFAULT \'0\'');
		$db->SQL('ALTER TABLE `visits` ADD FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
		
		status('');
		status('+----------------------------------------------------------------------------------------------------------------------+');
		status('| If you hardcoded logout path into webserver config you must remove that path now. That path is now set in CMS table. |');
		status('+----------------------------------------------------------------------------------------------------------------------+');
		status('');
		$db->SQL('INSERT INTO `CMS` (`id`, `request_path`, `title`, `addon`) VALUES (\'\', \'Logout/\', \'Logout\', \'logoutSystem\')');
		
		
		status('');
		status('+---------------------------------------------------------------------------------------------------------------------------+');
		status('| If you hardcoded Online User path into webserver config you must remove that path now. That path is now set in CMS table. |');
		status('+---------------------------------------------------------------------------------------------------------------------------+');
		status('');
		$db->SQL('INSERT INTO `CMS` (`id`, `request_path`, `title`, `addon`) VALUES (NULL, \'Online/\', \'Online users\', \'onlineUserSystem\')');
		
		
		status('');
		status('+-----------------------------------------------------------------------------------------------------------------------+');
		status('| If you hardcoded Matches path into webserver config you must remove that path now. That path is now set in CMS table. |');
		status('+-----------------------------------------------------------------------------------------------------------------------+');
		status('');
		$db->SQL('INSERT INTO `CMS` (`id`, `request_path`, `title`, `addon`) VALUES (NULL, \'Matches/\', \'Matches\', \'matchServices\')');
		
		status('Renaming CMS table to cms_paths');
		$db->SQL('RENAME TABLE `CMS` TO `cms_paths`)';
		
		
		// delete maintenance log file, new version uses database instead
		global $installationPath;
		if (file_exists($installationPath . 'CMS/maintenance/maintenance.txt'))
		{
			status('Resetting maintenance date');
			$db->SQL('Update `misc_data` SET `last_maintenance`=\'0000-00-00\'');
			
			if (!unlink($installationPath . 'CMS/maintenance/maintenance.txt'))
			{
				status('Could not delete file ' . $installationPath . 'CMS/maintenance/maintenance.txt');
				return false;
			}
			$maintDir = scandir($installationPath . 'CMS/maintenance/';
			if ($maintDir !== false && count(scandir($maintDir)) === 0 && rmdir($maintDir))
			{
				status('Deleted empty maintenance folder');
			} else
			{
				status('Could not delete maintenance folder')
				return false;
			}
		}
		
		
		$db->SQL('ALTER TABLE `teams_overview` CHANGE `any_teamless_player_can_join` `open` TINYINT(1)  NOT NULL  DEFAULT \'1\');

		return true;
	}
?>
