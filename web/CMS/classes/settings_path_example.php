<?php
	// this is only a settings_path example file
	
	// copy it to the path specified within settings.php and
	// edit the return values appropriately to get the site running
	// do the same for the settings example
	
	// you should not have your settings.php in your webserver dir because of security concerns.
	
	// __FILE__ is a PHP 5 constant that points to the current file (in this case settings.php)
	// dirname(__FILE__) gets the directory name where this file resides (CMS/)
	// the other dirname's work like ../ in the shell.
	// this example points to ../../settings.php
	// you could also use an absolute path
	// 	require_once ('/absolute/path/to/settings.php');
	require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/settings.php';
?>