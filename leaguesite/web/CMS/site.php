<?php
	// this file is supposed to load and init all common classes
	class site
	{
		function __construct()
		{
			global $tmplHelper;
			global $config;
			global $db;
			global $user;
			global $tmpl;
			
			
			// site config information
			include dirname(__FILE__) . '/classes/config.php';
			$config = new config();
			
			// setup session
			ini_set('session.use_trans_sid', 0);
			ini_set('session.name', 'SID');
			ini_set('session.gc_maxlifetime', '7200');
			ini_set('session.cookie_path', $config->getValue('basepath'));
			session_start();
			
			// set the date and time
			// suppress warning on invalid value to keep output well-formed
			if (@date_default_timezone_set($config->getValue('timezone')) === false)
			{
				// fallback to UTC if supplied config value is invalid
				date_default_timezone_set('UTC');
			}
			
			// database connectivity
			include dirname(__FILE__) . '/classes/db.php';
			$db = new database();
			
			// user information
			require dirname(__FILE__) . '/classes/user.php';
			$user = new user();
			
			// template builder
			require dirname(__FILE__) . '/classes/tmpl.php';
			$tmpl = new tmpl();
			
			
			
			// session fixation protection
			if (!isset($_SESSION['creationTime']))
			{
				$_SESSION['creationTime'] = time();
			} else
			{
				// invalidate old session
				// default: 15 minutes (60*15)
				$sessionRegenTime = ($config->getValue('sessionRegenTime')) ?
									 $config->getValue('sessionRegenTime') : (60*15);
				if (time() - $_SESSION['creationTime'] > (60*15))
				{
					// session creationTime older than $sessionRegenTime
					
					// force regenerate SID, invalidate old id
					session_regenerate_id(true);
					// update timestamp
					$_SESSION['creationTime'] = time();
				}
			}
			
			// logout inactive users
			// default: 2 hours (60*60*2)
			$sessionExpiryTime = ($config->getValue('logoutUserAfterXSecondsInactive')) ?
								  $config->getValue('logoutUserAfterXSecondsInactive') : (60*60*2);
			if (isset($_SESSION['lastActivity']) && (time() - $_SESSION['lastActivity']) > $sessionExpiryTime)
			{
				// last access older than $sessionExpiryTime
				$user->logout();
			}
			$_SESSION['lastActivity'] = time();
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
		
		function setKey($randomkey_name)
		{
			// this should be good enough as all we need is something that can not be guessed without many tries
			return $_SESSION[$randomkey_name] = rand(0, getrandmax());
		}
		
		function validateKey($key, $value)
		{
			if (isset($_SESSION[$key]))
			{
				$randomkeysmatch = (strcmp(html_entity_decode(urldecode($_SESSION[$key])), $value) === 0);
				
				// invalidate key & value to prevent allowing sending stuff more than once
				if (!(strcmp($value, '') === 0))
				{
					unset ($_SESSION[$key]);
					unset ($_SESSION[$value]);
				} else
				{
					// never return true on empty key & value combination
					return false;
				}
				
				return $randomkeysmatch;
			}
			
			return false;
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
?>
