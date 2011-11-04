<?php
	class bzbb
	{
		private $xhtml = true;
		private $groups = array();
		private $info = array();
		private $bzid = 0;
		
		public function __construct()
		{
			global $config;
			
			
			$this->xhtml = $config->getValue('useXhtml');
		}
		
		
		public function showLoginText()
		{
			global $config;
			
			
			$text = ('<p class="first_p">Please login using your account at <a href='
					 . '"http://my.bzflag.org/weblogin.php?action=weblogin&amp;url='
					 . urlencode($config->getValue('baseaddress') . 'Login/'
							   . '?module=bzbb&action=login&auth=%TOKEN%,%USERNAME%')
					 . '">my.bzflag.org (BZBB)</a>.</p>' . "\n");
			
			return ($text);
		}
		
		
		public function showForm()
		{
			// 3rd party bzflag weblogin shows the form
			// nothing to see here, move along
			return '';
		}
		
		
		public function validateLogin(&$output)
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
			
			if (($this->groups = $config->getValue('login.modules.bzbb.groups')) === false
				|| is_array($this->groups) === false)
			{
				// no accepted groups in config -> no login
				$output = 'config error : no login group was specified.';
				return false;
			}
			
			// parameters supplied by 3rd party weblogin script
			// $params[0] is token, $params[1] is callsign
			$params = explode(',', urldecode($_GET['auth']));
			
			$groupNames = array();
			foreach ($this->groups as $group)
			{
				$groupNames[] = $group['name'];
			}
			unset($group);
			
			// set external login data
			if (!$this->info = validate_token($params[0], $params[1], $groupNames,
											  !$config->getValue('login.modules.bzbb.disableCheckIP')))
			{
				// login did not work
				$output = ('Login failed: The returned values could not be validated! '
						   . 'You may check your username and password and <a href="./">try again</a>.');
				return false;
			}
			
			
			// code ran successfully
			return true;
		}
		
		
		public function getID()
		{
			return $this->info['bzid'];
		}
		
		public function getName()
		{
			return htmlent($this->info['username']);
		}
		
		public function givePermissions()
		{
			// there is no such thing in the reply like the VERIFIED group
			// just search for the VERIFIED group in $groups and apply the perms manually
			if (isset($this->groups['VERIFIED']))
			{
				$this->applyPermissions($this->groups['VERIFIED']);
//				echo '<p>Applying permissions of group VERIFIED</p>';
			}
			
			foreach ($this->groups as $group)
			{
				foreach ($this->info['groups'] as $memberOfGroup)
				{
					// case insensitive comparison of group names
					if (strcmp($memberOfGroup, $group['name']) === 0)
					{
//						echo '<p>Applying permissions of group ' . $group['name'] . '</p>';
						$this->applyPermissions($group);
						
						// TODO: consider removing matched entry from array using array_splice
						break;
					}
				}
				unset($memberOfGroup);
			}
		}
		
		private function applyPermissions($group)
		{
			global $user;
			
			
			// iterate through the permissions specified in config
			// and apply them individually
			foreach ($group['permissions'] as $name => $value)
			{
				$user->setPermission($name, $value === true);
			}
		}
		
		static public function updateUserName($bzid, &$callsign)
		{
			// checks bzid with BZFlag's bzidtools2 API and returns the new callsign
			
			$ch = curl_init();
			
			// set URL and other appropriate options
			curl_setopt($ch, CURLOPT_URL, 'http://my.bzflag.org/bzidtools2.php?action=name&value=' . strval($bzid));
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			// grab URL and pass it to the browser
			$output = curl_exec($ch);
			
			// close cURL resource, and free up system resources
			curl_close($ch);
			
			// update the entry with the result from the bzidtools2.php script
			if ((strlen($output) > 10) && (strcmp(substr($output, 0, 9), 'SUCCESS: ') === 0))
			{
				// API call was successful
				$callsign = substr($output, 9);
			} else
			{
				// API call was not successful
				
				// set callsign to the error message provided by interface
				$callsign = $output;
				// abort function because of service failure
				return false;
			}
			
			return true;
		}
		
		static public function convertAccount($userid, $loginname, &$output)
		{
			global $config;
			global $db;
			
			
			// user is not new, update his callsign with new external playerid supplied from login
			
			// external_id was empty, set it to the external value obtained by bzidtools
			// create a new cURL resource
			$ch = curl_init();
			
			// set URL and other appropriate options
			$url = ('http://my.bzflag.org/bzidtools2.php?action=id&value='
					. urlencode(strtolower($loginname)));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			// grab URL and pass it to the browser
			$output = curl_exec($ch);
			
			// close cURL resource, and free up system resources
			curl_close($ch);
			
			// update the entry with the result from the bzidtools2.php script
			if ((strlen($output) > 9) && (strcmp(substr($output, 0, 9), 'SUCCESS: ') === 0))
			{
				// the external id received from API
				$externalID = substr($output, 9);
				
				// check if external id is already used in our db
				$query = $db->prepare('SELECT `id` FROM `players` WHERE `external_id`=? LIMIT 1');
				$db->execute($query, $externalID);
				
				// error if id already used
				if ($db->fetchRow($query))
				{
					$output = ('This bzbb id (' . $externalID . ')is already tied to another account. '
							   . 'If you think this error message is not justified '
							   . 'please contact one of the admins');
					return false;
				}
				
				$query = $db->prepare('UPDATE `players` SET `external_id`=?'
									  // each user has only one entry in the database
									  . ' WHERE `id`=? LIMIT 1');
				if (!$db->execute($query, array(htmlent(substr($output, 9)), $userid)))
				{
					$output = ('Unfortunately there seems to be a database problem'
							   . ' which prevents the system from setting your external playerid (id='
							   . htmlent($userid)
							   . '). Please report this to an admin.');
					return false;
				}
				$output = ('Congratulations, you enabled the <a href="'
						   .  htmlspecialchars('http://my.bzflag.org/weblogin.php?action=weblogin&url=')
						   . urlencode($config->getValue('baseaddress')
									   . 'Login/?module=bzbb&action=login&auth=%TOKEN%,%USERNAME%')
						   . '">my.bzflag.org/bb/ (global) login</a> for this account.' . "\n");
			} else
			{
				$output = ('Unfortunately the bzidtools2.php script failed'
						   . ' which prevents the system from setting your external playerid (id='
						   . htmlent($userid)
						   . '). The bzidtool2.php call was '
						   . htmlent($url)
						   . '. Please report this to an admin.');
				// log the problem
				$db->logError($db->quote($output));
				
				return false;
			}
			
			// converting account to use bzbb login was successful :)
			return true;
		}
	}
?>
