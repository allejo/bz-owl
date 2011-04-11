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
	// TODO: need to be able to specify a path to the installation
	require_once (dirname(dirname(__FILE__)) . '/web/CMS/site.php');
	$site = new site();
	
	// do not run if website is in production mode
	if (!$config->value('maintenance.now') || !$config->value('maintenance.updateDB'))
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
	
	// call the update script now that we know the current db version
	// use a while loop instead of iterating inside the update function
	// so we keep the used stack memory small
	$error = false;
	while ($dbVersion < MAX_DB_VERSION && $error === false)
	{
		updateDB($dbVersion, $error);
	}
	echo ($error === false) ? ('The used database (' . $config->value('dbName')
							   . ') is up to date (version ' . $dbVersion . ').' . "\n") : $error;
	exit();
	
	
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
		
		echo('Tagging DB with version ' . $version . "\n");
		
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
		
		// PM DB changes
		echo('Renaming PM tables' . "\n");
		$db->SQL('RENAME TABLE `messages_storage` TO `pmSystem.Msg.Storage`');
		$db->SQL('CREATE TABLE `pmSystem.Msg.Recipients.Teams` (
				 `id` int(11) NOT NULL AUTO_INCREMENT,
				 `msgid` int(11) unsigned DEFAULT NULL,
				 `teamid` int(11) unsigned DEFAULT NULL,
				 PRIMARY KEY (`id`)
				 ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
		$db->SQL('CREATE TABLE `pmSystem.Msg.Recipients.Users` (
				 `id` int(11) NOT NULL AUTO_INCREMENT,
				 `msgid` int(11) unsigned DEFAULT NULL,
				 `userid` int(11) unsigned DEFAULT NULL,
				 PRIMARY KEY (`id`)
				 ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
		
		// transform data to new format
		$convertRecipientTeam=$db->prepare('INSERT INTO `pmSystem.Msg.Recipients.Teams` (`msgid`,`teamid`) VALUES(?,?)');
		$convertRecipientPlayer=$db->prepare('INSERT INTO `pmSystem.Msg.Recipients.Users` (`msgid`,`userid`) VALUES(?,?)');
		
		echo('Converting PM recipients to pmSystem.Msg.Recipients.Teams and pmSystem.Msg.Recipients.Users' . "\n"  . '.');
		$time = time();
		$query = $db->SQL('SELECT * FROM `pmSystem.Msg.Storage`');
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
		
		echo('Removing recipients column from pmSystem.Msg.Storage' . "\n" . '...' . "\n");
		$db->SQL('ALTER TABLE `pmSystem.Msg.Storage` DROP `recipients`');
		echo('Removing from_team column from pmSystem.Msg.Storage' . "\n" . '...' . "\n");
		$db->SQL('ALTER TABLE `pmSystem.Msg.Storage` DROP `from_team`');
		
		echo('Altering connecting tables between message and users' . "\n");
		$db->SQL('RENAME TABLE `messages_users_connection` TO `pmSystem.Msg.Users`');
		$db->SQL('ALTER TABLE `pmSystem.Msg.Users` DROP `id`');
		
		echo('Creating Content Management System (CMS) table' . "\n");
		$db->SQL("CREATE TABLE IF NOT EXISTS `CMS` (
				 `id` int(11) unsigned NOT NULL DEFAULT '0',
				 `requestPath` varchar(1000) NOT NULL DEFAULT '/',
				 `title` varchar(256) NOT NULL DEFAULT 'Untitled',
				 `addon` varchar(256) NOT NULL DEFAULT 'static',
				 PRIMARY KEY (`id`),
				 KEY `requestPath` (`requestPath`(255))
				 ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		
		echo('Filling CMS table with data' . "\n");
		$db->SQL("INSERT INTO `CMS` (`id`, `requestPath`, `title`, `addon`) VALUES
				 (0, '/', 'Home', 'staticPageEditor'),
				 (1, 'PM/', 'Private messages', 'pmSystem'),
				 (2, 'News/', 'News', 'newsSystem')");
		
		echo('Inserting title column to news table and renaming all announcements to msgs' . "\n");
		$db->SQL("ALTER TABLE `news` ADD `title` varchar(256) NOT NULL DEFAULT 'News'  AFTER `id`");
		$db->SQL('ALTER TABLE `news` CHANGE `announcement` `msg` text NULL DEFAULT NULL');
		$db->SQL('ALTER TABLE `news` CHANGE `raw_announcement` `raw_msg` text NULL DEFAULT NULL');
		
		echo('Adding DB version column (db.version) to misc_data' . "\n");
		$db->SQL("ALTER TABLE `misc_data` ADD `db.version` int(11) NOT NULL DEFAULT '0'  AFTER `last_servertracker_query`");
		tagDBVersion(1);
	}
?>
