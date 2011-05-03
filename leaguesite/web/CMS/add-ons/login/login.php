<?php
	class login
	{
		// this variable will disappear after cleanup of login code
		public $helper;
		
		function __construct()
		{
			global $site;
			global $tmpl;
			global $user;
			
			global $module;
			
			// init site class if not already done
			if (!isset($site))
			{
				require_once dirname(dirname(dirname(__FILE__))) . '/site.php';
				$site = new site();
			}
			
			$this->helper = new tmpl_helper();
			
			require_once 'modules/login_module_list.php';
			$module = active_login_modules();
			
			// magic quote band-aid for POST variables
			if ($site->magic_quotes_on())
			{
				stripslashes($_POST);
			}
			
			
			if (!$tmpl->setTemplate('Login'))
			{
				$tmpl->display('404');
			}
			
			// no need to do anything
			if ($user->loggedIn())
			{
				$this->helper->done('Login was already successful.');
			}
			
			if (!$this->loginTried())
			{
				$this->displayLoginOptions();
			} else
			{
				// process login attempt
				$this->loadLoginModule();
				
				// verify login module data
				if ($this->loginSuccessful())
				{
					$this->helper->addMsg('<p class="first_p">Login was successful!</p>'
								  . '<p>Your profile page can be found <a href="../Players/?profile=' . $user->getID() . '">here</a>.</p>');
				} else
				{
					// module itself should create an error message in case of failure
					// however if module error reporting would fail, handle this generic case here
					$this->helper->addMsg('<p class="first_p">Login failed in a login module for unknown reason!</p>');
				}
			}
			
			// perform a logout just in case anything went wrong.
			
			
			$tmpl->assign('msg', $this->helper->buildMsg());
			
			// done, render page
			$tmpl->display();
			
			die();
		}
		
		
		function logoutAndAbort($msg='')
		{
			global $user;
			global $tmpl;
			
			$user->logout();
			$this->helper->done($msg);
			die();
		}
		
		function loadLoginModule()
		{
			global $internal_login_id;
			global $module;
			global $config;
			global $user;
			global $tmpl;
			global $db;
			
			// FIXME: quick hack to allow access to helper in login module scripts
			$helper = $this->helper;
			
			// load modules to check input and buffer output
			// the buffer is neccessary because the modules might need to set cookies for instance
			if (isset($module['bzbb']) && ($module['bzbb']))
			{
				include_once 'modules/bzbb/index.php';
			}
			
			if (isset($module['local']) && ($module['local']))
			{
				include_once 'modules/local/index.php';
			}
		}
		
		function loginTried()
		{
			if (isset($_POST['loginname']) || isset($_GET['bzbbauth']))
			{
				return true;
			}
			return false;
		}
		
		function loginSuccessful()
		{
			global $user;
			
			$auth_performed = false;
			
			if ($user->loggedIn())
			{
				$auth_performed = true;
				
				
				// do all remaining stuff in one flat function
				// TODO: split it up into seperate functions
				$this->doAllTheRest();
				
				
				// if account can not be identified, it willl logout the user
				if ($user->getID() === 0)
				{
					$user->logout();
				}
			}
			
			
			
			if ($auth_performed && $user->loggedIn())
			{
				return true;
			}
			
			return false;
		}
		
		function displayLoginOptions()
		{
			global $site;
			global $config;
			global $tmpl;
			global $module;
			
			
			if (!(isset($_SESSION['user_logged_in'])) || !($_SESSION['user_logged_in']))
			{
				// user explicitly does not want an external login and confirmed it already
				if (!(isset($_POST['local_wanted']) && $_POST['local_wanted']))
				{
					if (isset($module['bzbb']) && ($module['bzbb']))
					{
						include_once('modules/bzbb/login_text.php');
					}
				}
				
				if (!(isset($_GET['bzbbauth'])) ||  !($_GET['bzbbauth']))
				{
					if (!(isset($_POST['local_wanted']) && $_POST['local_wanted']) && isset($module['local']) && ($module['local']))
					{
						$this->helper->addMsg('<strong>or</strong>');
						$this->helper->addMsg($this->helper->return_self_closing_tag('br'));
						$this->helper->addMsg($this->helper->return_self_closing_tag('br'));
					}
					
					if (isset($module['local']) && ($module['local']))
					{
						include_once(dirname(__FILE__) . '/modules/local/login_text.php');
					}
				}
			}
		}
		
		function doAllTheRest()
		{
			global $internal_login_id;
			global $module;
			
			global $config;
			global $site;
			global $tmpl;
			global $user;
			global $db;
			
			// init message
			$msg = '';
			
			// set the date and time
			date_default_timezone_set($config->value('timezone'));
			
			// only perform the operation if user logs in and not on reload
			if ($user->loggedIn())
			{
				// delete expired invitations
				$query = $db->prepare('DELETE LOW_PRIORITY FROM `invitations` WHERE `expiration`<=?');
				if (!$db->execute($query, date('Y-m-d H:i:s')))
				{
					$msg .= 'Could not delete expired invitations.';
					$this->logoutAndAbort($msg);
				}
			}
			
			// in no case an empty username is allowed
			if (isset($_SESSION['username']) && (strlen($_SESSION['username']) < 2))
			{
				$this->logoutAndAbort('<p>Any username is required to be at least 2 chars long</p>');
			}
			
			if ((isset($_SESSION['user_logged_in'])) && ($_SESSION['user_logged_in']))
			{
				// is the user already registered at this site?
				$query = $db->prepare('SELECT `id`, '
									  // only need an external login id in case an external login was performed by the viewing player
									  // but look it up to find out if user is global login enabled
									  // NOTE: this is only done as fallback in case the login module does not already handle it
									  . ' `external_id`, '
									  . '`status` FROM `players` WHERE `name`=?'
									  // only one player tries to login so only fetch one entry, speeds up login a lot
									  . ' LIMIT 1');
				if (!$db->execute($query, $_SESSION['username']))
				{
					$msg = ('Could not get account data for external_id ' . $db->quote($_SESSION['external_id']) . '.');
					$this->logoutAndAbort($msg);
				}
				
				$rows = $db->fetchAll($query);
				$db->free($query);
				$rows_num_accounts = count($rows);
				$suspended_mode = '';
				$convert_to_external_login = false;
				if ($rows_num_accounts > 0)
				{
					// find out if a username got locked before doing updates
					// e.g. inappropriate username got renamed by admins
					// and then someone else tries to join using this username, 
					// causing a reset of the other username to be reset to the inappropriate one
					foreach ($rows as $row)
					{
						$_SESSION['viewerid'] = (int) $row['id'];
						$suspended_mode = $row['status'];
						if (strcmp(($row['external_id']), '') === 0)
						{
							$convert_to_external_login = $config->value('convertUsersToExternalLogin');
						}
					}
				} elseif (isset($_SESSION['external_id']) && $_SESSION['external_id'])
				{
					// name is not known, check if id is already in the database
					// $rows_num_accounts === 0
					$query = 'SELECT `id`, `status` FROM `players` WHERE `external_id`=? LIMIT 1';
					$query = $db->prepare($query);
					if (!$db->execute($query, $_SESSION['external_id']))
					{
						$msg .= ('Could not find out if player already has an account with id '
								 . $db->quote($_SESSION['external_id']) . ' (renamed user?).');
						$this->logoutAndAbort($msg);
					}
					
					$rows_num_accounts = 0;
					while ($row = $db->fetchRow($query))
					{
						$rows_num_accounts += 1;
						$_SESSION['viewerid'] = (int) $row['id'];
						$suspended_mode = $row['status'];
						// reset back to default as there is nothing to do in that regard
						$convert_to_external_login = false;
					}
					$db->free($query);
				}
				
				// check if it is a false positive (no password stored in database)
				// this can happen in case the user got imported from another database
				if ($convert_to_external_login)
				{
					$query = $db->prepare('SELECT `password` FROM `players_passwords`'
										  . ' WHERE `players_passwords`.`playerid`=? LIMIT 1');
					if (!$db->execute($query, $_SESSION['viewerid']))
					{
						$msg = ('Could not find out if password is set for local account with id '
								. $db->quote($_SESSION['external_id']) . '.');
						$this->logoutAndAbort($msg);
					}
					
					$rows_num_accounts = 0;
					while ($row = $db->fetchRow($query))
					{
						$rows_num_accounts += 1;
						if (strcmp(($row['password']), '') === 0)
						{
							// yes, it was indeed a false positive
							$convert_to_external_login = false;
						}
					}
					$db->free($query);
				}
				
				if (isset($_SESSION['external_id']) && $_SESSION['external_id'] && ($convert_to_external_login))
				{
					$msg .= '<form action="' . $config->value('baseaddress') . 'Login/'. '" method="post">' . "\n";
					$msg .= 'The account you tried to login to does not support ';
					if (isset($module['bzbb']) && ($module['bzbb']))
					{
						$msg .= 'the my.bzflag.org/bb/ (global) login';
					} else
					{
						$msg .= 'external logins';
					}
					$msg .= '. You may update the account first by using your local login.</p>' . "\n";
					$msg .= '<p>In case someone other than you owns the local account then you need to contact an admin to solve the problem.' . "\n";
					$account_needs_to_be_converted = true;
					$msg .= '<form action="' . $config->value('baseaddress') . 'Login/'. '" method="post">' . "\n";
					$msg .= '<p class="first_p">' . "\n";
					if ($config->value('forceExternalLoginOnly'))
					{
						$msg .= $tmpl->return_self_closing_tag('input type="submit" name="local_wanted" value="Update old account from '
															   . htmlent($config->value('oldWebsiteName')) . '"');
					} else
					{
						$msg .= $tmpl->return_self_closing_tag('input type="submit" name="local_wanted" value="Local login"');
					}
					$msg .= '</p>' . "\n";
					$msg .= '</form>' . "\n";
					//				include_once(dirname(__FILE__) . '/local/login_text.php');
					
					$this->logoutAndAbort($msg);
				}
				
				if (isset($_SESSION['viewerid']) && ((int) $_SESSION['viewerid'] === (int) 0)
					&& ((strcmp($suspended_mode, 'login disabled') === 0) || strcmp($suspended_mode, 'banned') === 0))
				{
					$msg .= ('There is a user that got banned/disabled by admins with the same username'
							 . ' in the database already. Please choose a different username!');
					$this->logoutAndAbort($msg);
				}
				// dealing only with the current player from this point on
				
				// cache this variable to speed up further access to the value
				$user_id = $user->getID();
				
				
				// suspended mode: active; deleted; login disable; banned
				if (strcmp($suspended_mode, 'deleted') === 0)
				{
					$suspended_mode = 'active';
					$query = $db->prepare('UPDATE `players` SET `status`=?'
										  . ' WHERE `id`=? LIMIT 1');
					if (!$db->execute($query, array($suspended_mode, $user_id)))
					{
						$msg .= 'Could not reactivate deleted account with id ' . $db->quote($user_id) . '.';
						$this->logoutAndAbort($msg);
					}
				}
				if (strcmp($suspended_mode, 'login disabled') === 0)
				{
					$msg .= 'Login for this account was disabled by admins.';
					// skip updates if the user has a disabled login (inappropriate callsign for instance)
					$this->logoutAndAbort($msg);
				}
				if (strcmp($suspended_mode, 'banned') === 0)
				{
					$msg .=  'Admins specified you should be banned from the entire site.';
					// FIXME: BAN FOR REAL!!!!
					// skip updates if the user is banned (inappropriate callsign for instance)
					$this->logoutAndAbort($msg);
				}
				unset($suspended_mode);
				
				if (isset($_SESSION['external_login']) && ($_SESSION['external_login']))
				{
					if ($rows_num_accounts === 0)
					{
						$msg .= '<p class="first_p">Adding user to database…</p>' . "\n";
						// example query: INSERT INTO `players` (`external_id`, `teamid`, `name`) VALUES ('1194', '0', 'ts')
						$query = $db->prepare('INSERT INTO `players` (`external_id`, `teamid`, `name`)'
											  . ' VALUES (?, ?, ?)');
						if ($db->execute($query, array($_SESSION['external_id'], 0, htmlent($_SESSION['username']))))
						{
							$query = $db->prepare('SELECT `id` FROM `players` WHERE `external_id`=?');
							if ($db->execute($query, $_SESSION['external_id']))
							{
								$rows = intval($db->rowCount($query));
								while($row = $db->fetchRow($query))
								{
									$_SESSION['viewerid'] = (int) $row['id'];
								}
								$db->free($query);
								$user_id = $user->getID();
								if ($rows === 1)
								{
									// user inserted without problems
									$msg .= '<p>You have been added to the list of players at this site. Thanks for visiting our site.</p>';
									
									// send the welcome message
									include(dirname(dirname(__FILE__)) . '/pmSystem/classes/PMComposer.php');
									$pmComposer = new pmComposer();
									$pmComposer->setSubject('Welcome');
									$pmComposer->setContent('Welcome and thanks for registering at this website!' . "\n"
														   . 'In the FAQ you can find the most important informations'
														   . ' about organising and playing matches.' . "\n\n"
														   . 'See you on the battlefield.');
									$pmComposer->setTimestamp(date('Y-m-d H:i:s'));
									$pmComposer->addUserID($_SESSION['viewerid']);
									
									$pmComposer->send();
								} else
								{
									$msg .= ('Unfortunately there seems to be a database problem and thus a unique id can not be retrieved for your account. '
											 . ' Please try again later.</p>' . "\n"
											 . '<p>If the problem persists please tell an admin');
									$this->logoutAndAbort($msg);
								}
							}
						} else
						{
							// apologise, the user is new and we all like newbies
							$msg .= ('Unfortunately there seems to be a database problem and thus you (id='
									 . htmlent($user_id)
									 . ') can not be added to the list of players at this site. '
									 . 'Please try again later.</p>' . "\n"
									 . '<p>If the problem persists please report it to an admin');
							$this->logoutAndAbort($msg);
						}
						
						// adding player profile entry
						$query = $db->prepare('INSERT INTO `players_profile` (`playerid`, `joined`, `location`)'
											  . ' VALUES (?, ?, ?)');
						if (!$db->execute($query, array($_SESSION['viewerid'], date('Y-m-d H:i:s'), 1)))
						{
							$msg .= ('Unfortunately there seems to be a database problem and thus creating your profile page (id='
									 . htmlent($user_id)
									 . ') failed. Please report this to admins.');
							$this->logoutAndAbort($msg);
						}
					} else
					{
						// user is not new, update his callsign with new callsign supplied from external login
						
						// check for collisions with all local accounts
						$query = $db->prepare('SELECT `id`, `external_id` FROM `players` WHERE `name`=?');
						if (!$db->execute($query, htmlent($_SESSION['username'])))
						{
							$msg = ('Could not find out if external_id is set for all accounts having name '
									. $db->quote(htmlent($_SESSION['username'])) . '.');
							$this->logoutAndAbort($msg);
						}
						
						$local_name_collisions = false;
						while ($row = $db->fetchRow($query))
						{
							if (strcmp(($row['external_id']), '') === 0)
							{
								// yes, it was indeed a false positive
								$local_name_collisions = true;
							}
							$collisionID = intval($row['id']);
						}
						$db->free($query);
						
						if (isset($_SESSION['external_login']) && $_SESSION['external_login'])
						{
							// see if error can be recovered (empty password set)
							$query = $db->prepare('SELECT `password` FROM `players_passwords` WHERE `playerid`=? LIMIT 1');
							$db->execute($query, $collisionID);
							if ($collisionID > 0 && $row = $db->fetchRow($query))
							{
								// do the error recovery
								$updateAccountQuery = $db->prepare('UPDATE `players` SET `external_id`=? WHERE `id`=? LIMIT 1');
								$db->execute($updateAccountQuery, array($_SESSION['external_id'], $collisionID));
								$db->free($updateAccountQuery);
								
								// fix the problem so next login will not run into the same error recovery code
								$deleteEmptyPWQuery = $db->prepare('DELETE FROM `players_passwords` WHERE `playerid`=? LIMIT 1');
								$db->execute($deleteEmptyPWQuery, $collisionID);
								$db->free($deleteEmptyPWQuery);
								
								// give a message to user who might wonder but it could be helpful for troubleshooting
								$msg .= ('<p>Your account has been updated (empty password case for id '
										 . htmlent($collisionID) . ' detected).</p>');
								
								// recovered from collision
								$local_name_collisions = false;
							}
						}
						
						
						if ($local_name_collisions)
						{
							// non-resolvable collisions found, reset username of current user
							$query = $db->prepare('SELECT `name` FROM `players` WHERE `external_id`=? LIMIT 1');
							if (!$db->execute($query, $_SESSION['external_id']))
							{
								$msg = ('Could not find out if external_id is set for all accounts having name '
										. $db->quote(htmlent($_SESSION['username'])) . '.');
								$this->logoutAndAbort($msg);
							}
							while ($row = $db->fetchRow($query))
							{
								$_SESSION['username'] = htmlent_decode($row['name']);
							}
							$db->free($query);
							// print out a warning to the user, mentioning the non-updated callsign
							$msg .= '<p>Your callsign was not updated because there is already another local account in the database with the same callsign.</p>';
							$this->logoutAndAbort($msg);
						} else
						{
							// update name in case there is no collision
							$query = 'UPDATE `players` SET `name`=?';
							$args = array(htmlent($_SESSION['username']));
							if (isset($_SESSION['external_login']) && ($_SESSION['external_login']))
							{
								$query .= ' WHERE `external_id`=?';
								$args[] = $_SESSION['external_id'];
							} else
							{
								$query .= ' WHERE `id`=?';
								$args[] = $_SESSION['viewerid'];
							}
							// each user has only one entry in the database
							$query .= ' LIMIT 1';
							if (!$db->execute($db->prepare($query), $args))
							{
								$msg .= ('Unfortunately there seems to be a database problem which prevents the system from updating your callsign (id='
										 . htmlent($user_id)
										 . '). Please report this to an admin.</p>');
								$this->logoutAndAbort($msg);
							}
						}
						unset($local_name_collisions);
					}
				} else
				{
					// local login
					if (isset($internal_login_id))
					{
						if (isset($convert_to_external_login) && $convert_to_external_login)
						{
							// user is not new, update his callsign with new external playerid supplied from login
							
							// external_id was empty, set it to the external value obtained by bzidtools
							// create a new cURL resource
							$ch = curl_init();
							
							// set URL and other appropriate options
							$url = ('http://my.bzflag.org/bzidtools2.php?action=id&value=' . urlencode(strtolower($_SESSION['username'])));
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
								// example query: UPDATE `players` SET `external_id`='$external_id' WHERE `id`='$internal_id' LIMIT 1;
								$query = $db->prepare('UPDATE `players` SET `external_id`=?'
													  // each user has only one entry in the database
													  . ' WHERE `id`=? LIMIT 1');
								if (!$db->execute($query, array(htmlent(substr($output, 9)), $internal_login_id)))
								{
									$msg = ('Unfortunately there seems to be a database problem'
											. ' which prevents the system from setting your external playerid (id='
											. htmlent($user_id)
											. '). Please report this to an admin.');
									$this->logoutAndAbort($msg);
								}
								$msg = 'Congratulations, you enabled ';
								if (isset($module['bzbb']) && ($module['bzbb']))
								{
									$msg .= ('the <a href="' . $module['bzbb']
											 . '">my.bzflag.org/bb/ (global) login</a>');
								} else
								{
									$msg .= 'external logins';
								}
								$msg .= ' for this account.' . "\n";
							} else
							{
								$msg = ('Unfortunately the bzidtools2.php script failed'
										. ' which prevents the system from setting your external playerid (id='
										. htmlent($user_id)
										. '). The bzidtool2.php call was '
										. htmlent($url)
										. '. Please report this to an admin.');
								// log the problem
								$db->logError($db->quote($msg));
								
								$this->logoutAndAbort($msg);
							}
						}
						
						$this->helper->addMsg($msg);
						if ($config->value('forceExternalLoginOnly'))
						{
							$this->logoutAndAbort('');
						}
					}
				}
				
				// bzflag auth specific code, thus use bzid value directly
				if (isset($_SESSION['bzid']) && isset($_SESSION['username']))
				{
					// find out if someone else once used the same callsign
					// update the callsign from the other player in case he did
					// example query: SELECT `external_id` FROM `players` WHERE (`name`='ts') AND (`external_id` <> '1194')
					// AND (`external_id` <> '') AND (`status`='active' OR `status`='deleted')
					// FIXME: sql query should be case insensitive (SELECT COLLATION(VERSION()) returns utf8_general_ci)
					$query = ('SELECT `external_id` FROM `players` WHERE (`name`=?)'
							  . ' AND (`external_id` <> ?)'
							  // do not update users with local login
							  . ' AND (`external_id` <> ' . "''" . ')'
							  // skip updates for banned or disabled accounts (inappropriate callsign for instance)
							  . ' AND (`status`=? OR `status`=?)');
					$query = $db->prepare($query);
					$args = array(htmlent($_SESSION['username']), $_SESSION['external_id'], 'active', 'deleted');
					if (!$db->execute($query, $args))
					{
						$msg = ('Finding other members who had the same name '
								. $db->quote(htmlent($_SESSION['username']))
								. 'failed. This is a database problem. Please report this to an admin!');
						$this->logoutAndAbort($msg);
					}
					
					$errno = 0;
					$errstr = '';
					$rows = $db->fetchAll($query);
					$db->free($query);
					$n = count($rows);
					for ($i = 0; $i < $n; $i++)
					{
						// create a new cURL resource
						$ch = curl_init();
						
						// set URL and other appropriate options
						curl_setopt($ch, CURLOPT_URL, 'http://my.bzflag.org/bzidtools2.php?action=name&value='
									. "'" . (intval($row['external_id'])) . "'");
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						
						// grab URL and pass it to the browser
						$output = curl_exec($ch);
						
						// close cURL resource, and free up system resources
						curl_close($ch);
						
						// update the entry with the result from the bzidtools2.php script
						$query = $db->prepare('UPDATE `players` SET `name`=? WHERE `external_id`=?');
						if ((strlen($output) > 10) && (strcmp(substr($output, 0, 9), 'SUCCESS: ') === 0))
						{
							// example query: UPDATE `players` SET `name`='moep' WHERE `external_id`='external_id';
							$args = array(htmlent(substr($output, 9)), intval($rows[$i]['bzid']));
						} else
						{
							// example query: UPDATE `players` SET `name`=
							// 'moep ERROR: SELECT username_clean FROM bzbb3_users WHERE user_id=uidhere'
							// WHERE `external_id`='external_id';
							$args = array(htmlent($_SESSION['username']) . ' ' . htmlent($output), intval($rows[$i]['bzid']));
						}
						
						if (!$db->execute($query, $args))
						{
							// trying to update the players old callsign failed
							$msg = ('Unfortunately there seems to be a database problem which prevents'
									. ' the system from updating the old callsign of another user.'
									. ' However you currently own that callsign so now there will be two'
									. ' users with the callsign in the table and people will'
									. 'have problems to distinguish you two!</p>'
									. '<p>Please report this to an admin.');
							$this->logoutAndAbort($msg);
						}
					}
				}
			}
			
			
			if (isset($_SESSION['user_logged_in']) && ($_SESSION['user_logged_in']))
			{
				// update last login entry
				$query = $db->prepare('UPDATE `players_profile` SET `last_login`=? WHERE `playerid`=? LIMIT 1');
				
				if (isset($_SESSION['external_login']) && ($_SESSION['external_login']))
				{
					$args = array(date('Y-m-d H:i:s'), $user_id);
				} else
				{
					$args = array(date('Y-m-d H:i:s'), $internal_login_id);
				}
				
				$db->execute($query, $args);
			}
			
			
			if ((!(isset($_SESSION['user_in_online_list'])) || !($_SESSION['user_in_online_list'])) &&  ((isset($_SESSION['user_logged_in'])) && ($_SESSION['user_logged_in'])))
			{
				$_SESSION['user_in_online_list'] = true;
				$curDate = (date('Y-m-d H:i:s'));
				
				// find out if table exists
				$query = $db->SQL('SHOW TABLES LIKE ' . "'" . 'online_users' . "'");
				$numRows = count($db->fetchAll($query));
				$db->free($query);
				
				$onlineUsers = false;
				if ($numRows > 0)
				{
					// no need to create table in case it does not exist
					// any interested viewer looking at the online page will create it
					$onlineUsers = true;
				}
				
				// use the resulting data
				if ($onlineUsers)
				{
					$query = $db->prepare('SELECT * FROM `online_users` WHERE `userid`=?');
					$db->execute($query, $user_id);
					$rows = $db->rowCount($query);
					// done
					$db->free($query);
					
					$onlineUsers = false;
					if ($rows > 0)
					{
						// already logged in
						// so log him out
						$query = $db->prepare('DELETE FROM `online_users` WHERE `userid`=?');
						if (!$db->execute($query, $user_id))
						{
							$msg .= 'Could not remove already logged in user from online user table. Database broken?';
							$this->logoutAndAbort($msg);
						}
					}
					
					// insert logged in user into online_users table
					$query = $db->prepare('INSERT INTO `online_users` (`userid`, `username`, `last_activity`) Values (?, ?, ?)');
					$args = array($user_id, htmlent($_SESSION['username']), $curDate);
					$db->execute($query, $args);
					
					// do maintenance in case a user still belongs to a deleted team (database problem)
					$query = ('SELECT `teams_overview`.`deleted` FROM `teams_overview`, `players`'
							  . ' WHERE `teams_overview`.`teamid`=`players`.`teamid` AND `players`.`id`=?'
							  . ' AND `teams_overview`.`deleted`>?'
							  // deal only with the player that wants to login
							  . ' LIMIT 1');
					$query = $db->prepare($query);
					if ($db->execute($query, array($user_id, 2)))
					{
						$rows = $db->fetchAll($query);
						$db->free($query);
						$n = count($rows);
						// mark who was where, to easily restore an unwanted team deletion
						$query = $db->prepare('UPDATE `players` SET `last_teamid`=`players`.`teamid`'
											  . ', `teamid`=? WHERE `id`=?');
						for ($i = 0; $i < $n; $i++)
						{
							if ((int) $rows[$i]['deleted'] === 1)
							{
								
								$db->execute($query, array(0, $user_id));
							}
						}
						$db->free($query);
					}
					
					// insert to the visits log of the player
					$ip_address = getenv('REMOTE_ADDR');
					$host = gethostbyaddr($ip_address);
					$query = ('INSERT INTO `visits` (`playerid`,`ip-address`,`host`,`forwarded_for`,`timestamp`) VALUES'
							  . ' (?, ?, ?, ?, ?)');
					$query = $db->prepare($query);
					$args = array($user_id, htmlent($ip_address), htmlent($host)
								  // try to detect original ip-address in case proxies are used
								  , htmlent(getenv('HTTP_X_FORWARDED_FOR')), $curDate);
					$db->execute($query, $args);
				}
			}
			
			$this->helper->addMsg($msg);
		}
	}
	
	class tmpl_helper
	{
		private $msg = array();
		
		function addMsg($messageToAdd)
		{
			$this->msg[] = $messageToAdd;
		}
		
		function buildMsg()
		{
			$entireMsg = '';
			
			if (count($this->msg) > 0)
			{
				foreach ($this->msg as $curMSG)
				{
					$entireMsg .= $curMSG;
				}
			}
			
			return $entireMsg;
		}
		
		// quick way to stop script and to output a message
		function done($msg)
		{
			global $tmpl;
			
			$this->addMsg($msg);
			$tmpl->assign('msg', $this->buildMsg());
			$tmpl->display();
			die();
		}
		
		function return_self_closing_tag($tag)
		{
			global $config;
			
			$result = '<';
			$result .= $tag;
			// do we use xhtml (->true) or html (->false)
			if ($config->value('useXhtml'))
			{
				$result .= ' /';
			}
			$result .= '>';
			$result .= "\n";
			
			return $result;
		}
	}
?>