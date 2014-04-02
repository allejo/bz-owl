<?php
	// init common classes and permissions
	require(dirname(__FILE__) . '/CMS/site.php');
	$site = new site();
	
	// handle the request
	require('CMS/add-ons/pathLoaderSystem/pathLoaderSystem.php');
	new pathLoaderSystem();
?>
