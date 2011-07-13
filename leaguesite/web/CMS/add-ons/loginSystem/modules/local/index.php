<?php
	class local
	{
		private $xhtml = true;
		private $info = array();
		
		function __construct()
		{
			global $config;
			
			
			$this->xhtml = $config->getValue('useXhtml');
		}
		
		
		function showLoginText()
		{
			global $config;
			global $db;
			
			
			$oldWebsiteName = $config->getValue('login.local.oldWebsiteName');
			if ($oldWebsiteName === false)
			{
				$db->logError('CONFIG ERROR: Variable login.local.oldWebsiteName is not set. '
							  . 'Generator: ' . __FILE__);
				return 'ERROR: Variable login.local.oldWebsiteName is not set in config.';
			}
			
			
			// show login text only if configured to do so
			if ($config->getValue('login.local.showLoginText'))
			{
				$msg = ('<form action="./?module=local&action=form" method="post">' . "\n");
				$msg .= '<p class="first_p">' . "\n";
				if ($config->getValue('forceExternalLoginOnly'))
				{
					$msg .= ('<input type="submit" name="local_login_wanted"'
							 . 'value="Update old account from '
							 . $oldWebsiteName . '"');
					$msg .= $this->xhtml ? ' />' : '>';
				} else
				{
					$msg .= ('<input type="submit" name="local_login_wanted"'
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
			$oldWebsiteName = $config->getValue('login.local.oldWebsiteName');
			if ($oldWebsiteName === false)
			{
				$db->logError('CONFIG ERROR: Variable login.local.oldWebsiteName is not set. '
							  . 'Generator: ' . __FILE__);
				return 'ERROR: Variable login.local.oldWebsiteName is not set in config.';
			}
			
			if ($config->getValue('login.local.convertUsersToExternalLogin'))
			{
				$modules = loginSystem::getModules();
				if (array_search('bzbb', $modules) !== false)
				{
					$msg .= ('<strong><span class="unread_messages">'
							 . 'Before you continue make absolutely sure your account here '
							 . 'and the my.bzflag.org/bb/ (forum) account have exactly the '
							 . 'same username or you will give someone else access to your account '
							 . 'and that access can never be revoked.</span></strong></p>');
				}
				unset($modules);
			}
			
			$msg .= 'Enter login data from <strong>' . $oldWebsiteName . '</strong> here!</p>';
			$msg .= "\n";
			
			// load form
			$msg .= '<form action="./?module=local&amp;action=login'. '" method="post">' . "\n";
			$msg .= '<div class="p">Name:</div>' . "\n";
			$msg .= '<p class="first_p">' . "\n";
			$msg .= '<input type="text" class="small_input_field" name="loginname" value="" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</p>' . "\n";
			
			$msg .= '<div class="p">Password:</div>' . "\n";
			$msg .= '<p class="first_p">' . "\n";
			$msg .= '<input type="password" name="pw" value="" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</p>' . "\n";
			
			$msg .= '<div>' . "\n";
			$msg .= '<input type="hidden" name="module" value="local" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</div>' . "\n";
			
			$msg .= '<div>' . "\n";
			if ($config->getValue('forceExternalLoginOnly'))
			{
				$msg .= '<input type="submit" value="Update"';
			} else
			{
				$msg .= '<input type="submit" value="Login"';
			}
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</div>' . "\n";
			$msg .= '</form>' . "\n";
			
			$msg .= '<p>Note: Only global login has the ability to allow more than standard permissions at the moment.</p>' . "\n";
			
			return $msg;
		}
		
		
		public function validateLogin(&$output)
		{
			global $user;
			
			// initialise permissions
			$user->removeAllPermissions();
			
			// set password based on POST parameter
			// no password -> login failed
			if (!isset($_POST['pw']))
			{
				return false;
			}
			$pw = $_POST['pw'];
			
			// set loginname based on POST parameter
			// no loginname -> login failed
			if (!isset($_POST['loginname']))
			{
				$output = ('Error: You must specify a name. '
						   . 'You may <a href="./?module=local&amp;action=form">try logging in again</a>.');
				return false;
			}
			
			// escape username before storing it in db
			// that way encoding when displaying (on other pages)
			// using (X)HTML is not needed
			$loginname = htmlent($_POST['loginname']);
			
			// initialise match variables for password and user
			$correctUser = false;
			$correctPw = false;
			
			
			$lenLogin = strlen($loginname);
			if (($lenLogin > 50) || ($lenLogin < 1))
			{
				$output .= ('User names must be using less than 50 '
							. 'but more than 0 <abbr title="characters">chars</abbr>.' . "\n");
				return false;
			}
			
			// get player id
			$query = 'SELECT `id`';
			if ($config->getValue('forceExternalLoginOnly'))
			{
				$query .= ', `external_id` ';
			}
			// only one player tries to login so only fetch one entry, speeds up login a lot
			$query .= ' FROM `players` WHERE `name`=? LIMIT 1';
			
			$query = $db->prepare($query);
			$db->execute($query, $loginname);
			
			
			// initialise with reserved player id 0 (no player)
			$userid = (int) 0;
			$convert_to_external_login = true;
			while($row = $db->fetchRow($query))
			{
				$userid = $row['id'];
				if (!(strcmp(($row['external_id']), '') === 0))
				{
					$convert_to_external_login = false;
				}
			}
			
			// local login tried but external login forced in settings
			if (!$convert_to_external_login && $config->getValue('forceExternalLoginOnly'))
			{
				$msg = '<span class="unread_messages">You already enabled ';
				$modules = loginSystem::getModules();
				if (array_search('bzbb', $modules) !== false)
				{
					$url = urlencode($config->getValue('baseaddress') . 'Login/' . '?bzbbauth=%TOKEN%,%USERNAME%');
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
							$output[] = ('Module bzbb has returned the following error on convertAccount: '
										 . $moduleConvertMsg);
						}
						return false;
					}
					if (strlen($moduleConvertMsg) > 0)
					{
						$output[] = ('Module bzbb has returned the success message on convertAccount: '
									 . $moduleConvertMsg);
					}
					unset($moduleConvertMsg);
				}
			}
			
			
			if (intval($userid) === 0)
			{
				$user->logout();
				
				$output .= ('The specified user is not registered. '
							. 'You may want to <a href="./">try logging in again</a>.');
				return false;
			}
			
			// get password from database in order to compare it with the user entered password
			$query = ('SELECT `password`, `password_encoding` FROM `players_passwords` WHERE `playerid`=?'
					  // only one player tries to login so only fetch one entry, speeds up login a lot
					  . ' LIMIT 1');
			
			// execute query
			$query = $db->prepare($query);
			if (!$db->execute($query, $userid))
			{
				// query failed
				$output .= 'Could not retrieve password for you in database.';
				return false;
			}
			
			// initialise without md5 (hash functions could cause collisions despite passwords will not match)
			$password_md5_encoded = false;
			// no password is default and will not match any password set
			$password_database = '';
			while($row = $db->fetchRow($query))
			{
				if (strcmp($row['password_encoding'],'md5') === 0)
				{
					$password_md5_encoded = true;
				}
				$password_database = $row['password'];
			}
			
			$lenPw = strlen($pw);
			
			// webleague imported passwords have unknown length limitations 
			if (!$password_md5_encoded)
			{
				if (($lenPw < 10) || ($lenPw > 32) && (strlen($password_database) !== 0))
				{
					$output .= ('Passwords must be using less than 32 but more than 9 '
								. '<abbr title="characters">chars</abbr>.'
								. ' You may want to <a href="./">try logging in again</a>.</p>' . "\n");
					return false;
				}
			} else
			{
				// generate md5 hash of user entered password
				$pw = md5($pw);
			}
			
			if (!(strcmp($password_database, $pw) === 0) && strlen($password_database) > 0)
			{
				// TODO: automatically log these cases and lock account for some hours after several unsuccessful tries
				$output .= ('Your password does not match the stored password.'
							. ' You may want to <a href="./">try logging in again</a>.' . "\n");
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
			
			
			// standard permissions for user
			$_SESSION['username'] = $this->info['username'];
			$_SESSION['user_logged_in'] = true;
			$internal_login_id = $userid;
			
			// permissions for private messages
			$user->setPermission('allow_add_messages');
			$user->setPermission('allow_delete_messages');
		}
	}
?>
