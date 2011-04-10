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
	
	// do not run if site is in production mode
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
		
		$dbVersion = (count($rows) > 1) ? MAX_DB_VERSION : 0;
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
	
	function updateVersion0()
	{
		global $db;
		
		// PM DB changes
		echo('renaming PM tables' . "\n");
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
		
		echo('converting PM recipients to pmSystem.Msg.Recipients.Teams and pmSystem.Msg.Recipients.Users' . "\n"  . '.');
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
		echo("\n" . 'done converting PM recipients to v1' . "\n");
		$db->free($query);
		
		echo('altering connecting tables between message and users' . "\n");
		$db->SQL('RENAME TABLE `messages_users_connection` TO `pmSystem.Msg.Users`');
		$db->SQL('ALTER TABLE `pmSystem.Msg.Users` DROP `id`');
	}
?>
