<?php
	// this file does update old bz-owl databases
	// to the version that this file ships with
	// NOTE: This does not update tables by add-ons!
	
	define('MAX_DB_VERSION', 1);
	
	if (!(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])))
	{
		header('Content-Type: text/plain');
		exit('File to be called on command line interface (cli) only!' . "\n");
	}
	
	// use the simple site.php as it's quite convenient to use
	
	// default to path within repository
	$path = dirname(dirname(__FILE__)) . '/web/';
	
	// use custom installation path info if available
	$installationPathFile = dirname(__FILE__) . '/installationPath.txt';
	if (file_exists($installationPathFile) && is_readable($installationPathFile))
	{
		// remove newlines from end of file, if there is one
		$path = rtrim(file_get_contents($installationPathFile), "\n");
		
		// if relative path -> if path does not begin with /
		if (strcasecmp(substr($path, 0, 1), '/') !== 0)
		{
			$path = dirname(__FILE__) . '/' . $path;
		}
	}
	// load site.php
	require_once $path . 'CMS/site.php';
	$site = new site();
	
	// do not run if website is in production mode
	if (!$config->value('debugSQL') && (!$config->value('maintenance.now') || !$config->value('maintenance.updateDB')))
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
		$query = $db->prepare('SHOW TABLES LIKE ?');
		$db->execute($query, 'CMS');
		$rows = $db->fetchAll($query);
		$db->free($query);
		
		$dbVersion = (count($rows) >= 1) ? MAX_DB_VERSION : 0;
	} else
	{
		$query = $db->SQL('SELECT `db.Version` FROM `misc_data` LIMIT 1');
		$rows = $db->fetchAll($query);
		$db->free($query);
		
		$dbVersion = (count($rows) < 1) ? MAX_DB_VERSION : $rows[0]['db.Version'];
		
		if ($dbVersion > 0)
		{
			$db->SQL('INSERT INTO `misc_data` (`db.Version`) VALUES (' . MAX_DB_VERSION . ')');
		}
	}
	
	// call the updating code now that we know the current db version
	// use a while loop instead of iterating inside the update function
	// so we keep the used stack memory low
	$error = false;
	while ($dbVersion < MAX_DB_VERSION && $error === false)
	{
		updateDB($dbVersion, $error);
	}
	echo ($error === false) ? ('The used database (' . $config->value('dbName')
							   . ') is up to date (version ' . $dbVersion . ').' . "\n") : $error;
	exit();
	
	function status($message='')
	{
		echo($message . "\n");
	}
	
	function updateDB(&$version, &$error)
	{
		echo('DB at version ' . $version . "\n");
		
		if ($version < MAX_DB_VERSION)
		{
			echo('Updating...' . "\n");
			
			$updateVersionFunction = 'updateVersion' . $version;
			$updateVersionFunction();
			// TODO: update code here
			// TODO: if non-recoverable error occurs, write it into $error
			
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
		tagDBVersion(1);
	}
?>
