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
		
		$dbVersion = (count($rows) < 1) ? MAX_DB_VERSION : 0;
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
	while ($dbVersion < MAX_DB_VERSION)
	{
		updateDB($dbVersion);
	}
	echo('The used database (' . $config->value('dbName') . ') is up to date (version ' . $dbVersion . ').' . "\n");
	
	
	
	function updateDB(&$version, &$error)
	{
		echo('DB at version ' . $version . "\n");
		
		if ($version < MAX_DB_VERSION)
		{
			echo('UpdatingÉ');
			
			// TODO: update code here
			// TODO: if non-recoverable error occurs, write it into $error
			
			// update counter
			$version++;
		}
	}
?>
