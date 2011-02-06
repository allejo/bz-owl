<?php
	// this file is supposed to load and init all common classes
	class site
	{
		
		function __construct()
		{
			global $config;
			global $db;
			global $user;
			global $tmpl;
			
			// database connectivity
			include dirname(__FILE__) . '/classes/config.php';
			$config = new config();
			
			// database connectivity
			include dirname(__FILE__) . '/classes/db.php';
			$db = new database();
			
			// user information
			require dirname(__FILE__) . '/classes/user.php';
			$user = new user();
			
			// template builder
			// do not init it, as db information is needed
			// to find out what template should be used
			require dirname(__FILE__) . '/classes/tmpl.php';
		}
		
		function magic_quotes_on()
		{
			if (function_exists('get_magic_quotes_gpc') && (get_magic_quotes_gpc() === 1))
			{
				return true;
			}
			
			return false;
		}
		
		function basename()
		{
			$path = (pathinfo(realpath('./')));
			$name = $path['basename'];
			return $name;
		}
	}
	
	// add a few functions to global namespace
	// only add very frequently used functions
	
	// shortcut for utf-8 aware htmlentities
	function htmlent($string)
	{
		return htmlentities($string, ENT_COMPAT, 'UTF-8');
	}
	
	function htmlent_decode($string)
	{
		return html_entity_decode($string, ENT_COMPAT, 'UTF-8');
	}
	
	// escaping shortcut
	function sqlSafeString($param)
	{
		// use MySQL function mysql_real_escape_string, alternative could be prepared statements
		return (NULL === $param ? "NULL" : mysql_real_escape_string ($param));
	}
	
	function sqlSafeStringQuotes($param)
	{
		// use sqlSafeString and append quotes before and after the result
		return ("'" . sqlSafeString($param) . "'");
	}
	
?>