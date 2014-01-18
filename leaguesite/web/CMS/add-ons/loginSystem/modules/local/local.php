<?php
	class local
	{
		private $xhtml = true;
		private $info = array();
		private $loginAllowed = true;
		
		
		function __construct()
		{
			global $config;
			global $db;
			
			
			// check if all free login attempts per day from ip have been already used
			// if that's the case show a no perm page and accept no login
			$query = $db->prepare('SELECT * FROM `users_rejected_logins` WHERE `ip-address`=:ip AND `timestamp`>=:timestamp');
			
			// create current timestamp
			$targetTimestamp=time();
			
			// we count login attempts per day, so subtract 1 day
			// as UTC/GMT have no daylight saving one could also use -24 hours
			// but one might change time in db to be the system time in the future
			$targetTimestamp = strtotime('-1 day', $targetTimestamp);
			
			// now format it as YYYY:MM:DD HH:MM:SS and transform to GMT
			$targetTimestamp = gmstrftime('%Y:%m:%d %H:%M:%S', $targetTimestamp);
			
			// read out client's ip-address
			$ip_address = getenv('REMOTE_ADDR');
			
			// alright, we have enough info to build the query
			if (!$db->execute($query, array(':ip'=>array($targetTimestamp, PDO::PARAM_STR),
											':timestamp'=>array($ip_address, PDO::PARAM_STR))))
			{
				$db->logError('FATAL ERROR: Could not check user\'s login attempts');
				$tmpl->assign('errorMsg', 'Database error encountered: Could not check user\s login attempts.');
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			// get numer of row results, one by one to avoid a buffer overflow
			$numRows=0;
			while($db->fetchRow($query))
			{
				$numRows++;
			}
			$db->free($query);
			
			// more than 5 failed login attempts during last day
			// -> goodbye ;)
			if ($numRows > 5)
			{
				$tmpl->setTemplate('NoPerm');
				$this->loginAllowed = false;
				return;
			}
			
			
			$this->xhtml = $config->getValue('useXhtml');
			
			// include all loaded modules, choke if external login forced but only local login possible
			// local login is always loaded -> must be at least 2 modules for external login
			$modules = loginSystem::getModules();
			if (count($modules) < 2)
			{
				if ($config->getValue('login.modules.forceExternalLoginOnly'))
				{
					$db->logError('CONFIG OR INSTALLATION ERROR: Variable login.modules.forceExternalLoginOnly '
								  . 'is set but only local login module found. Generator: ' . __FILE__);
				}
			} else
			{
				// load other login modules
				foreach ($modules as &$module)
				{
					if (strcmp($module, 'local') !== 0)
					{
						require_once(dirname(dirname(__FILE__)) . '/' . $module . '/' . $module . '.php');
					}
				}
			}
		}
		
		private function logFailedLoginAttempt($name,$reason='unknown')
		{
			global $loginSystem;
			
			if (isset($loginSystem))
			{
				$this->$loginSystemClass->logFailedLoginAttempt($name, pathinfo(dirname(__FILE__), PATHINFO_FILENAME), $reason);
			}
			return;
		}
		
		
		function showLoginText()
		{
			global $config;
			global $db;
			
			
			$oldWebsiteName = $config->getValue('login.modules.local.oldWebsiteName');
			if ($oldWebsiteName === false)
			{
				$db->logError('CONFIG ERROR: Variable login.modules.local.oldWebsiteName is not set. '
							  . 'Generator: ' . __FILE__);
				return 'ERROR: Variable login.modules.local.oldWebsiteName is not set in config.';
			}
			
			
			// show login text only if configured to do so, else show form button
			if ($config->getValue('login.modules.local.showLoginText'))
			{
				return $this->showForm();
			} else
			{
				$msg = ('<form action="./?module=local&amp;action=form" method="post">' . "\n");
				$msg .= '<p class="first_p">' . "\n";
				if ($config->getValue('login.modules.forceExternalLoginOnly'))
				{
					$msg .= ('<input type="submit" name="local_login_wanted"'
							 . 'value="Update old account from '
							 . $oldWebsiteName . '"');
					$msg .= $this->xhtml ? ' />' : '>';
				} else
				{
					$msg .= ('<input type="submit" name="local_login_wanted" '
							. 'value="Local login"');
					$msg .= $this->xhtml ? ' />' : '>';
				}
				$msg .= '</p>' . "\n";
				$msg .= '</form>' . "\n";
				return ($msg);
			}
		}
		
		
		function showForm()
		{
			global $config;
			
			
			$msg = '';
			$oldWebsiteName = $config->getValue('login.modules.local.oldWebsiteName');
			if ($oldWebsiteName === false)
			{
				$db->logError('CONFIG ERROR: Variable login.modules.local.oldWebsiteName is not set. '
							  . 'Generator: ' . __FILE__);
				return 'ERROR: Variable login.modules.local.oldWebsiteName is not set in config.';
			}
			
			if ($config->getValue('login.modules.local.convertUsersToExternalLogin'))
			{
				$modules = loginSystem::getModules();
				if (array_search('bzbb', $modules) !== false)
				{
					$msg .= ('<strong><span class="unread_messages">'
							 . 'Before you continue make absolutely sure your account here '
							 . 'and the my.bzflag.org/bb/ (forum) account have exactly the '
							 . 'same username or you will give someone else access to your account '
							 . 'and that access can never be revoked.</span></strong></p><p>');
				}
				unset($modules);
			}
			
			// refer to website name, if wished on config
			if ($config->getValue('login.modules.local.mentionOldWebsiteName'))
			{
				$msg .= 'Enter login data from <strong>' . $oldWebsiteName . '</strong> here!</p>';
				$msg .= "\n" . '<p>';
			}
			
			// load form
			$msg .= '<form action="./?module=local&amp;action=login" method="post">' . "\n";
			$msg .= '<div class="p">Name:</div>' . "\n";
			$msg .= '<p class="first_p">';
			$msg .= '<input type="text" class="small_input_field" name="loginname" value="" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</p>' . "\n";
			
			$msg .= '<div class="p">Password:</div>' . "\n";
			$msg .= '<p class="first_p">';
			$msg .= '<input type="password" name="pw" value="" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</p>' . "\n";
			
			$msg .= '<div>';
			$msg .= '<input type="hidden" name="module" value="local"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</div>' . "\n";
			
			$msg .= '<div>';
			if ($config->getValue('login.modules.forceExternalLoginOnly'))
			{
				$msg .= '<input type="submit" value="Update"';
			} else
			{
				$msg .= '<input type="submit" value="Login"';
			}
			$msg .= $this->xhtml ? ' />' : '>';
			
/*
			if ($config->getValue('login.modules.local.allowRegister'))
			{
				$msg .= '<input type="submit" value="Register"' . ($this->xhtml ? ' />' : '>');
			}
*/
			
			$msg .= '</div>' . "\n";
			$msg .= '</form>' . "\n";
			
			return $msg;
		}
		
		
		public function validateLogin(&$output)
		{
			global $config;
			global $user;
			global $db;
			
			
			// initialise permissions
			$user->removeAllPermissions();
			
			// too many login attempts
			if (!$this->loginAllowed)
			{
				// print out the same error message an incorrect password would trigger
				$output .= ('Your password does not match the stored password.'
							. ' You may want to <a href="./">try logging in again</a>.' . "\n");
				return false;
			}
			
			// set loginname based on POST parameter
			// no loginname -> login failed
			if (!isset($_POST['loginname']))
			{
				$output = ('Error: You must specify a name. '
						   . 'You may <a href="./?module=local&amp;action=form">try logging in again</a>.');
				$this->logFailedLoginAttempt('', 'fieldMissing');
				return false;
			}
			
			
			// escape username before storing it in db
			// that way encoding when displaying (on other pages)
			// using (X)HTML is not needed
			$loginname = htmlent($_POST['loginname']);
			$lenLogin = strlen($loginname);
			
			// now take care of empty loginname
			// also present error message to user if too long or too short loginname
			if (($lenLogin > 50) || ($lenLogin < 1))
			{
				$output .= ('User names must be using less than 50 '
							. 'but more than 0 <abbr title="characters">chars</abbr>.' . "\n");
				$this->logFailedLoginAttempt(substr($_POST['loginname'],0,50), $lenLogin > 50 ? 'tooLongUserName' : 'emptyPassword');
				return false;
			}
			
			
			// set password based on POST parameter
			// no password -> login failed
			if (!isset($_POST['pw']))
			{
				$this->logFailedLoginAttempt($loginname, 'fieldMissing');
				return false;
			}
			
			
			// check length of password
			$pw_supplied = $_POST['pw'];
			$lenPw = strlen($pw_supplied);
			if (($lenPw < 9) || ($lenPw > 32))
			{
				$output .= ('Passwords must be using less than 32 but more than 9 '
							. '<abbr title="characters">chars</abbr>.'
							. ' You may want to <a href="./">try logging in again</a>.</p>' . "\n");
				$this->logFailedLoginAttempt($loginname, $lenPw > 32 ? 'tooLongPassword' : 'emptyPassword');
				return false;
			}
			
			// initialise match variables for password and user
			$correctUser = false;
			$correctPw = false;
			
			
			
			// get user id
			$query = 'SELECT `id`';
			if ($config->getValue('login.modules.forceExternalLoginOnly'))
			{
				$query .= ', `external_id` ';
			}
			// only one user tries to login so only fetch one entry, speeds up login a lot
			$query .= ' FROM `users` WHERE `name`=? LIMIT 1';
			
			$query = $db->prepare($query);
			$db->execute($query, $loginname);
			
			
			// initialise with reserved user id 0 (no user)
			$userid = (int) 0;
			$convert_to_external_login = true;
			while($row = $db->fetchRow($query))
			{
				$userid = $row['id'];
				// external_id might contain NULL values
				// so it is more practical to use isset
				// instead of checking $convert_to_external_login
				// and then investigate a possible NULL value
				if (!isset($row['external_id']) || !(strcmp(($row['external_id']), '') === 0))
				{
					$convert_to_external_login = false;
				}
			}
			
			// local login tried but external login forced in settings
			if (!$convert_to_external_login && $config->getValue('login.modules.forceExternalLoginOnly'))
			{
				$msg = '<span class="unread_messages">You already enabled ';
				$modules = loginSystem::getModules();
				if (array_search('bzbb', $modules) !== false)
				{
					$url = urlencode($config->getValue('baseaddress')
									 . 'Login/?module=bzbb&action=login&auth=%TOKEN%,%USERNAME%');
					$msg .= '<a href="' . htmlspecialchars('http://my.bzflag.org/weblogin.php?action=weblogin&url=') . $url;
					$msg .= '">global (my.bzflag.org/bb/) login</a>';
				} else
				{
					$msg .= 'external logins';
				}
				$msg .= ' for this account.</span>' . "\n";
				$output .= $msg;
				unset($modules);
				
				return false;
			} elseif ($convert_to_external_login)
			{
				// convert user account to external login implicitly
				
				// find out which login modules are installed
				$modules = loginSystem::getModules();
				
				// convert to use bzbb external login
				if (array_search('bzbb', $modules) !== false)
				{
					// try to convert the account using the module's inbuilt function
					$moduleConvertMsg = '';
					if (!bzbb::convertAccount($userid, $loginname, $moduleConvertMsg))
					{
						if (strlen($moduleConvertMsg) > 0)
						{
							$output .= ('Module bzbb has returned the following error on convertAccount: '
										 . $moduleConvertMsg);
						}
						return false;
					}
					if (strlen($moduleConvertMsg) > 0)
					{
						// Module bzbb has returned the following success message on convertAccount
						$output .= $moduleConvertMsg . ' ';
					}
					unset($moduleConvertMsg);
					
					// stop here if local login not allowed
					if ($config->getValue('login.modules.forceExternalLoginOnly'))
					{
						$output .= ('Local login is disabled by the site admin, though. '
									. 'You must use the bzbb login instead.');
						return false;
					}
				}
			}
			
			
			if (intval($userid) === 0)
			{
				$user->logout();
				
				$output .= ('The specified user is not registered. '
							. 'You may want to <a href="./">try logging in again</a>.');
				$this->logFailedLoginAttempt($loginname);
				return false;
			}
			
			// get password from database in order to compare it with the user entered password
			// only one user tries to login so only fetch one entry, speeds up login a lot
			$query = ('SELECT `password`, `cipher` FROM `users_passwords` WHERE `userid`=? LIMIT 1');
			
			// execute query
			$query = $db->prepare($query);
			if (!$db->execute($query, $userid))
			{
				// query failed
				$output .= 'Could not retrieve password for you in database.';
				$this->logFailedLoginAttempt($loginname, 'missconfiguration');
				return false;
			}
			
			// find out what database tells about password algorithm
			while($row = $db->fetchRow($query))
			{
				if (!isset($row['cipher']))
				{
					$output .= ('FATAL ERROR: Cipher not set.'
								. ' You may want to <a href="./">try logging in again</a>.' . "\n");
					$db->logError('FATAL ERROR: Local login module: Cipher not set.');
					$this->logFailedLoginAttempt($loginname, 'missconfiguration');
					return false;
				}
				// the algorithm
				$cipher=$row['cipher'];
				// the password in db
				$pw_db = $row['password'];
			}
			
			// little article about password security
			// http://www.php.net/manual/en/faq.passwords.php
			
			
			// cryptographic salt to use
			// see http://www.php.net/manual/en/function.crypt.php
			// TODO: allow overriding user configurable salt part using config
			$salt='';
			switch($cipher)
			{
				case 'md5':
				{
					// is md5 cipher supported on this system?
					if (CRYPT_MD5 != 1)
					{
						$salt=false;
						break;
					}
					// 12 char salt, beginning with $1$
					$salt='$1$';
					$userSalt='thisi$sp$';
					break;
				}
				case 'blowfish':
				{
					// is blowfish cipher supported on this system?
					if (CRYPT_BLOWFISH != 1)
					{
						$salt=false;
						break;
					}
					// 33 char salt,
					// beginning with $2a$, followed by cost between 04 and 31, a % and 22 chars in ./0-9A-Za-z
					$salt='$2a$';
					$userSalt='09$th1s1sSp4rt4.O.RlySure4b0$';
					//$userSalt='07$usesomesillystringforsalt$';
					break;
				}
				default:
				{
					// an undefined case, very bad
					// but not caused by end user so do not subtract free login attempts
					$output .= ('FATAL ERROR: Action for stored cipher in db not set.'
								. ' This case means you need admin support to resolve the technical issue.'
								. ' You may want to <a href="./">try logging in again</a>.' . "\n");
					$db->logError('FATAL ERROR: Local login module: No action for cipher (' . $cipher . ') set.');
					$this->logFailedLoginAttempt($loginname, 'missconfiguration');
					return false;
				}
			}
			
			// mention the error to user but don't tell about cipher information
			// as the latter would likely only help exploiting the password check
			// again not caused by end user
			if ($salt === false)
			{
				$output .= ('FATAL ERROR: Cipher set and valid but library error on server detected.'
							. ' This case means you need admin support to resolve the technical issue.'
							. ' You may want to <a href="./">try logging in again</a>.' . "\n");
				$db->logError('FATAL ERROR: Local login module: Cipher (' . $cipher . ') set and valid'
							. ' but no proper encoding lib available.');
				$this->logFailedLoginAttempt($loginname, 'missconfiguration');
				return false;
			}
			
			// build the final salt
			$salt .= $userSalt;
			
			// compute the password from user input
			$pw_gen = crypt($pw_supplied,$salt);
			
			
			// do the actual comparison
			if (!(strcmp($pw_db, $pw_gen) === 0) && strlen($pw_db) > 0)
			{
				// TODO: automatically log these cases and lock account for some hours after several unsuccessful tries
				$output .= ('Your password does not match the stored password.'
							. ' You may want to <a href="./">try logging in again</a>.' . "\n");
				$this->logFailedLoginAttempt($loginname, 'passwordMismatch');
				return false;
			}
			
			// put information into class variable for usage outside of this function
			$this->info['id'] = $userid;
			$this->info['username'] = $loginname;
			
			// sanity checks passed -> login successful
			return true;
			
			// username and password did match but there might be circumstances
			// where the caller script decides the login was not successful, though
		}
		
		
		public function getID()
		{
			return $this->info['id'];
		}
		
		public function getName()
		{
			return htmlent($this->info['username']);
		}
		
		static public function convertAccount($userid, $loginname, &$output)
		{
			// can not convert, failsave
			return false;
		}
		
		public function givePermissions()
		{
			global $user;
			global $db;
			
			// mark user as logged in
			$_SESSION['username'] = $this->info['username'];
			$_SESSION['user_logged_in'] = true;
			$internal_login_id = $this->info['id'];
			
			
			// try to get permission info from database
			$standardPerms = true;
			$query = $db->prepare('SELECT `permissions` FROM `users_permissions` WHERE `userid`=:id');
			if ($db->execute($query, array(':id' => array($this->info['id'], PDO::PARAM_INT))))
			{
				// put permission data into userPerms array
				// this is possible because data stored in db is a serialised array
				$userPerms = $db->fetchRow($query);
				//$userPerms = unserialize($userPerms[0]);
				$userPerms = explode(';',$userPerms[0]);
				foreach($userPerms AS $key => $value)
				{
					// compute new key=>value combination
					$value = explode(':', $value);
					// remove old key as there is no possibility to change key afterwards
					unset($userPerms[$key]);
					// create new key=>value entry in array
					// will create a warning if permission list ends with ;
					$userPerms[$value[0]] = $value[1];
					
				}
				
				if ($userPerms)
				{
					foreach($userPerms as $name => $value)
					{
						$user->setPermission($name, $value);
					}
					// special permissions have been set
					$standardPerms = false;
				}
			}
			
			// standard permissions for user if no special permissions set
			if ($standardPerms)
			{
				// permissions for private messages
				$user->setDefaultPermissions();
				$user->setPermission('allow_add_messages');
				$user->setPermission('allow_delete_messages');
			}
			
			// example of how to build a permission setting to be stored in db
			// NOTE: A bitfield based permission saving would be more efficient
			// NOTE: but could only store boolean values for the fields
/*
			$perms = array('allow_add_messages'=>false, 'allow_add_gallery'=>true, 'allow_edit_gallery'=>true,'allow_delete_gallery'=>true);
			foreach($perms AS $key => &$value)
			{
				if ($value === false)
				{
					// force boolean false output to be 0
					$value = $key . ':'. strval(0);
				} else
				{
					$value = $key . ':'. strval($value);
				}
			}
			$perms = implode(';', $perms);
			echo $perms;
*/
		}
	}
?>
