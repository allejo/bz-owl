<?php
	class bzbb
	{
		private $xhtml = true;
		
		function __construct()
		{
			global $config;
			
			
			$this->xhtml = $config->value('useXhtml');
		}
		
		
		function showLoginText()
		{
			global $config;
			
			
			$text = ('<p class="first_p">Please log in using your account at <a href='
					 . '"http://my.bzflag.org/weblogin.php?action=weblogin&amp;url='
					 . urlencode($config->value('baseaddress') . 'Login2/'
							   . '?module=bzbb&action=login&auth=%TOKEN%,%USERNAME%')
					 . '">my.bzflag.org (BZBB)</a>.</p>' . "\n");
			
			return ($text);
		}
		
		
		function showForm()
		{
			// 3rd party bzflag weblogin shows the form
			// nothing to see here, move along
			return '';
		}
		
		
		function validateLogin(&$output)
		{
			global $config;
			global $user;
			
			// initialise permissions
			$user->removeAllPermissions();
			
			if (isset($_GET['auth']) === false)
			{
				// no auth data -> no login
				return false;
			}
			
			// load module specific auth helper
			require dirname(__FILE__) . '/checkToken.php';
			
			if (($groups = $config->value('login.bzbb.groups')) === false || is_array($groups) === false)
			{
				// no accepted groups in config -> no login
				$output = 'config error : no login group was specified.';
				return false;
			}
			
			// parameters supplied by 3rd party weblogin script
			// $params[0] is token, $params[1] is callsign
			$params = explode(',', urldecode($_GET['auth']));
			
			$groupNames = array();
			foreach ($groups as $group)
			{
				$groupNames[] = $group['name'];
			}
			unset($group);
			
			if (!$info = validate_token($params[0], $params[1], $groupNames, false))
			{
				// login did not work, removing permissions not necessary as additional permissions where never granted
				// after permissions were removed at the beginning of the file
				
				$output = ('<p class="first_p">Login failed: The returned values could not be validated!'
						   . ' You may check your username and password and <a href="./">try again</a>.</p>' . "\n");
				return false;
			}
			
			// there is no such thing in the reply like the VERIFIED group
			// just search for the VERIFIED group in $groups and apply the perms manually
			if (isset($groups['VERIFIED']))
			{
				$this->applyPermissions($groups['VERIFIED']);
			}
			
			foreach ($groups as $group)
			{
				foreach ($info['groups'] as $memberOfGroup)
				{
					// case insensitive comparison
					if (strcmp($memberOfGroup, $group['name']) === 0)
					{
						$this->applyPermissions($group);
						break;
					}
				}
				unset($memberOfGroup);
			}
			
			// code ran successfully
			return true;
		}
		
		private function applyPermissions($group)
		{
			global $user;
			
			
			echo '<p>Applying permissions of group ' . $group . '</p>';
			// iterate through the permissions specified in config
			// and apply them individually
			foreach ($group['permissions'] as $name => $value)
			{
				$user->setPermission($name, $value === true);
			}
		}
	}
?>
