<?php
	
	function addonToUse($path, &$title)
	{
		global $db;
		
		
		$query = $db->prepare('SELECT `addon`, `title` FROM `CMS` WHERE `requestPath`=? LIMIT 1');
		$db->execute($query, $path);
		
		$row = $db->fetchRow($query);
		$db->free($query);
		
		$addon = '';
		if (getFixedPageAddon($path, $title, $addon))
		{
			return $addon;
		}
		
		if (count($row) > 0)
		{
			$addon = $row['addon'];
			$title = $row['title'];
			
			return $addon;
		}
		
		return false;
	}
	
	
	function getFixedPageAddon($path, &$title, &$addon)
	{
		if (strcmp($path, 'Login/') === 0)
		{
			$title = 'Login';
			$addon = 'login';
			
			return true;
		}
		
		return false;
	}
	
	
	function loadAddon($addon, $title, $path)
	{
		global $site;
		global $tmpl;
		
		$file = dirname(__FILE__) . '/CMS/add-ons/' . $addon
				. '/' . $addon . '.php';
		if (file_exists($file))
		{
			// init the addon
			include($file);
			$addon = new $addon($title, $path);
		} else
		{
			// the path could not be found in database
			$tmpl->setTemplate('404');
			$tmpl->display();
		}
	}
	
	// if path specified use it, otherwise default to root
	$path = (isset($_GET['path'])) ? $_GET['path'] : '/';
	
	// init common classes
	require('CMS/site.php');
	$site = new site();
	
	$title = 'Untitled';
	
	if ($config->value('maintenance.now'))
	{
		header('Content-Type: text/plain');
		exit($config->value('maintenance.msg') ? $config->value('maintenance.msg') : 'This site has been shut down due to maintenance.' . "\n");
	}
	
	// load the add-on
	loadAddon(addonToUse($path, $title), $title, $path);
?>
