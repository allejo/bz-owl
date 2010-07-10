<?php
	@require_once '../CMS/siteinfo.php';
	$site = new siteinfo();
	$connection = $site->connect_to_db();
	
	// choose database
	mysql_select_db($site->db_used_name(), $connection);
	
	$path = (pathinfo(realpath('./')));
	$display_page_title = $path['basename'];
	
	$output_buffer = '';
	ob_start();
	require '../CMS/index.php';
	
	// buffer can now be written
//	echo $output;
	
	function die_with_no_login($message='', &$logged_string='')
	{
		global $site;
		
		$_SESSION['user_logged_in'] = false;
		$_SESSION['viewerid'] = -1;
		
		require_once '../CMS/navi.inc';
		echo '<div class="static_page_box">' . "\n";
		$output_buffer .= ob_get_contents();
		ob_end_clean();
		// write output buffer
		echo $output_buffer;
		if (strlen($message) > 0)
		{
			echo '<p class="first_p">' . $message . '</p>' . "\n";
		}
		
		if (strcmp($message, $logged_string) === 0)
		{
			// message already shown
			$site->dieAndEndPage();
		} else
		{
			// message not shown yet
			$site->dieAndEndPage($logged_string);
		}
	}
	
	// set the date and time
	date_default_timezone_set('Europe/Berlin');
	
	// only perform the operation if user logs is and not on reload
	if ($auth_performed && (isset($_SESSION['user_logged_in'])))
	{
		// delete expired invitations
		$query = 'DELETE LOW_PRIORITY FROM `invitations` WHERE `expiration`<=';
		$query .= sqlSafeStringQuotes(date('Y-m-d H:i:s'));
		if (!$result = $site->execute_query($site->db_used_name(), 'invitations', $query, $connection))
		{
			$msg = 'Could not delete expired invitations.';
			die_with_no_login($msg, $msg);
		}
	}
	unset($auth_performed);
	
	// in no case an empty username is allowed
	if (isset($_SESSION['username']) && (strlen($_SESSION['username']) < 2))
	{
		die_with_no_login('<p>Any username is required to be at least 2 chars long</p>');
	}
	
	if ((isset($_SESSION['user_logged_in'])) && ($_SESSION['user_logged_in']))
	{
		// is the user already registered at this site?
		$query = 'SELECT `id`, ';
		// only need an external login id in case an external login was performed by the viewing player
		// but look it up to find out if user is global login enabled
		$query .= ' `external_playerid`, ';
		$query .= '`suspended` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($_SESSION['username']);
		// only one player tries to login so only fetch one entry, speeds up login a lot
		$query .= ' LIMIT 1';
		if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
		{
			$msg = ('Could not get account data for external_playerid ' . sqlSafeString($_SESSION['external_id']) . '.');
			die_with_no_login($msg, $msg);
		}
		
		$rows_num_accounts = (int) mysql_num_rows($result);
		$suspended_mode = 0;
		// find out if a username got locked before doing updates
		// e.g. inappropriate username got renamed by admins
		// and then someone else tries to join using this username, 
		// causing a reset of the other username to be reset to the inappropriate one
		$convert_to_external_login = false;
		while ($row = mysql_fetch_array($result))
		{
			$_SESSION['viewerid'] = (int) $row['id'];
			$suspended_mode = (int) $row['suspended'];
			if (strcmp(($row['external_playerid']), '') === 0)
			{
				$convert_to_external_login = $site->convert_users_to_external_login();
			}
		}
		mysql_free_result($result);
		
		if (isset($external_login_id) && $external_login_id && ($convert_to_external_login))
		{
			$msg = 'The account you tried to login to does not support ';
			if (isset($module['bzbb']) && ($module['bzbb']))
			{
				$msg .= 'the bzbb login';
			} else
			{
				$msg .= 'external logins';
			}
			$msg .= '. You may convert the account first by using your local login.</p>' . "\n";
			$msg .= '<p>In case someone other than you owns the local account then you need to contact an admin to solve the problem.' . "\n";
			$output_buffer2 = '<p>';
			ob_start();
			if (isset($module['bzbb']) && ($module['bzbb']))
			{
				$site->write_self_closing_tag('input type="submit" name="local_login_wanted" value="Convert legacy account to allow bzbb login"');
			} else
			{
				$site->write_self_closing_tag('input type="submit" name="local_login_wanted" value="Convert user to external login"');
			}
			$output_buffer2 .= ob_get_contents();
			ob_end_clean();
			// write output buffer
			$msg .= $output_buffer2 . '</p>' . "\n";
			die_with_no_login($msg);
		}
		
		if (isset($_SESSION['viewerid']) && ((int) $_SESSION['viewerid'] === (int) 0)
			&& ($suspended_mode > (int) 1))
		{
			$msg = 'There is a user that got banned/disabled by admins with the same username in the database already. Please choose a different username!';
			die_with_no_login($msg);
		}
		// dealing only with the current player from this point on
		
		// cache this variable to speed up further access to the value
		$user_id = getUserID();
		
		
		// suspended mode:
		// 0 describes an active account
		// 1 describes an account that got deleted during maintenance
		// 2 describes an account that got disabled by admins
		// 3 describes that the owner of that account should be banned from the entire site, if possible
		if ($suspended_mode === (int) 1)
		{
			$query = 'UPDATE `players` SET `suspended`=' . sqlSafeStringQuotes('0');
			$query .= ' WHERE `id`=' . sqlSafeStringQuotes($user_id);
			if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
			{
				$msg = 'Could not reactivate deleted account with id ' . sqlSafeString($user_id) . '.';
				die_with_no_login($msg, $msg);
			}
			$suspended_mode = (int) 0;
		}
		if ($suspended_mode > (int) 1)
		{
			$msg = '';
			if ($suspended_mode === (int) 2)
			{
				$msg .= 'Login for this account was disabled by admins.';
			}
			if ($suspended_mode === (int) 3)
			{
				// FIXME: BAN FOR REAL!!!!
				$msg .=  'Admins specified you should be banned from the entire site.';
			}
			// skip updates if the user has a disabled login or is banned (inappropriate callsign for instance)
			die_with_no_login($msg);
		}
		unset($suspended_mode);
		
		if (isset($_SESSION['external_login']) && ($_SESSION['external_login']))
		{
			if ($rows_num_accounts === 0)
			{
				echo '<p class="first_p">Adding user to database…</p>' . "\n";
				// example query: INSERT INTO `players` (`external_playerid`, `teamid`, `name`) VALUES('1194', '0', 'ts')
				$query = 'INSERT INTO `players` (`external_playerid`, `teamid`, `name`) VALUES(';
				$query .= sqlSafeStringQuotes($_SESSION['external_id']) . ', ' . "'" . '0' . "'" . ', ' . sqlSafeStringQuotes(htmlent($_SESSION['username'])) .')';
				if ($insert_result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection))
				{
					$query = 'SELECT `id` FROM `players` WHERE `external_playerid`=' . sqlSafeStringQuotes($_SESSION['external_id']);
					if ($id_result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection))
					{
						$rows = mysql_num_rows($id_result);
						while($row = mysql_fetch_array($id_result))
						{
							$_SESSION['viewerid'] = (int) $row['id'];
						}
						mysql_free_result($id_result);
						$user_id = getUserID();
						if ($rows === 1)
						{
							// user inserted without problems
							echo '<p>You have been added to the list of players at this site. Thanks for visiting our site.</p>';
							
							// write welcome mail!
							
							// lock messages_storage because of mysql_insert_id() usage
							$query = 'LOCK TABLES `messages_storage` WRITE';
							if (!($result = @$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection)))
							{
								// query was bad, error message was already given in $site->execute_query(...)
								$msg = 'Could not lock the messages_storage table.';
								die_with_no_login($msg);
							}
							
							// create the welcome message in database
							$query = 'INSERT INTO `messages_storage` (`author`, `author_id`, `subject`, `timestamp`, `message`, `from_team`, `recipients`) VALUES ';
							$query .= '(' . sqlSafeStringQuotes('league management system') . ', ' . sqlSafeStringQuotes ('0');
							$query .= ', ' . sqlSafeStringQuotes('Welcome!') . ', ' . sqlSafeStringQuotes(date('Y-m-d H:i:s'));
							$query .= ', ' . sqlSafeStringQuotes('Welcome and thanks for registering at this website!' . "\n"
																 . 'In the FAQ you can find the most important informations about organising and playin matches.'
																 . "\n\n" .
																 'See you on the battlefield.');
							$query .= ', ' . sqlSafeStringQuotes('0') . ', ' . sqlSafeStringQuotes($user_id) . ')';
							if (!($result = @$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection)))
							{
								// query was bad, error message was already given in $site->execute_query(...)
								$msg = 'Could not create the welcome mail.';
								die_with_no_login($msg);
							}
							
							// get the msgid generated from database
							$msgid = mysql_insert_id($connection);
							
							// unlock messages_storage because mysql_insert_id() was used and is no longer needed
							$query = 'UNLOCK TABLES';
							if (!($result = @$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection)))
							{
								// query was bad, error message was already given in $site->execute_query(...)
								$msg = 'Could not unlock the messages_storage table.';
								die_with_no_login($msg);
							}
							// send the invitation message to user
							$query = 'INSERT INTO `messages_users_connection` (`msgid`, `playerid`, `in_inbox`, `in_outbox`) VALUES ';
							$query .= '(' . sqlSafeStringQuotes($msgid) . ', ' . sqlSafeStringQuotes($user_id);
							$query .= ', ' . sqlSafeStringQuotes('1');
							$query .= ', ' . sqlSafeStringQuotes('0') . ')';
							if (!($result = @$site->execute_query($site->db_used_name(), 'messages_users_connection', $query, $connection)))
							{
								// query was bad, error message was already given in $site->execute_query(...)
								$msg = 'Could not send the welcome mail.';
								die_with_no_login($msg);
							}
							
							// message sent
						} else
						{
							$msg = ('Unfortunately there seems to be a database problem and thus a unique id can not be retrieved for your account. '
												 . ' Please try again later.</p>' . "\n"
												 . '<p>If the problem persists please tell an admin');
							die_with_no_login($msg, $msg);
						}
					}
				} else
				{
					// apologise, the user is new and we all like newbies
					$msg = ('Unfortunately there seems to be a database problem and thus you (id='
										 . htmlent($user_id)
										 . ') can not be added to the list of players at this site. '
										 . 'Please try again later.</p>' . "\n"
										 . '<p>If the problem persists please report it to an admin');
					die_with_no_login($msg, $msg);
				}
				
				// adding player profile entry
				$query = 'INSERT INTO `players_profile` (`playerid`, `joined`, `location`) VALUES (';
				$query .= sqlSafeStringQuotes($user_id) . ', ' . sqlSafeStringQuotes(date('Y-m-d H:i:s'));
				$query .= ', ' . sqlSafeStringQuotes('1') . ')';
				if (!(@$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
				{
					$msg = ('Unfortunately there seems to be a database problem and thus creating your profile page (id='
										 . htmlent($user_id)
										 . ') failed. Please report this to admins.');
					die_with_no_login($msg, $msg);
				}
			} else
			{
				// user is not new, update his callsign with new callsign supplied from external login
				$query = 'UPDATE `players` SET `name`=' . sqlSafeStringQuotes(htmlent($_SESSION['username']));
				$query .= ' WHERE `id`=' . sqlSafeStringQuotes($_SESSION['viewerid']);
				// each user has only one entry in the database
				$query .= ' LIMIT 1';
				if (!($update_result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
				{
					$msg = ('Unfortunately there seems to be a database problem which prevents the system from updating your callsign (id='
										. htmlent($user_id)
										. '). Please report this to an admin.</p>');
					die_with_no_login($msg, $msg);
				}
			}
		} else
		{
			// local login
			if (isset($internal_login_id))
			{
				$msg = '';
				if (isset($convert_to_external_login) && $convert_to_external_login)
				{
					// user is not new, update his callsign with new external playerid supplied from login
					
					// external_playerid was empty, set it to the external value obtained by bzidtools
					// create a new cURL resource
					$ch = curl_init();
					
					// set URL and other appropriate options
					curl_setopt($ch, CURLOPT_URL, 'http://my.bzflag.org/bzidtools2.php?action=id&value=' . sqlSafeString($_SESSION['username']));
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					
					// grab URL and pass it to the browser
					$output = curl_exec($ch);
					
					// close cURL resource, and free up system resources
					curl_close($ch);
					
					// update the entry with the result from the bzidtools2.php script
					if ((strlen($output) > 9) && (strcmp(substr($output, 0, 9), 'SUCCESS: ') === 0))
					{
						// example query: UPDATE `players` SET `external_playerid`='external_id' WHERE `id`='internal_id';
						$query = 'UPDATE `players` SET `external_playerid`=' . sqlSafeStringQuotes(htmlent(substr($output, 9)));
					}
					$query .= ' WHERE `id`=' . sqlSafeStringQuotes($internal_login_id);
					// each user has only one entry in the database
					$query .= ' LIMIT 1';
					if (!($update_result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
					{
						$msg = ('Unfortunately there seems to be a database problem'
											 . ' which prevents the system from setting your external playerid (id='
											 . htmlent($user_id)
											 . '). Please report this to an admin.');
						die_with_no_login($msg, $msg);
					}
					$msg .= 'Congratulations, you enabled ';
					if (isset($module['bzbb']) && ($module['bzbb']))
					{
						$msg .= 'the bzbb login';
					} else
					{
						$msg .= 'external logins';
					}
					$msg .= ' for this account.' . "\n";
				}
				
				// local login tried but external login forced in settings
				if (isset($internal_login_id) && $site->force_external_login_when_trying_local_login())
				{
					if (strlen($msg) > 0)
					{
						$msg .= '</p><p>';
					}
					$msg .= '<span class="unread_messages">The hoster of this website has disabled local logins. You should login using your <a href="./">';
					if (isset($module['bzbb']) && ($module['bzbb']))
					{
						$msg .= 'bzbb account';
					} else
					{
						$msg .= 'external login';
					}
					$msg .= '</a>.</span>' . "\n";
					die_with_no_login($msg);
				}
				echo $msg;
			}
		}
		
		// bzflag auth specific code, thus use bzid value directly
		if (isset($_SESSION['bzid']) && isset($_SESSION['username']))
		{
			// find out if someone else once used the same callsign
			// update the callsign from the other player in case he did
			// example query: SELECT `external_playerid` FROM `players` WHERE (`name`='ts') AND (`external_playerid` <> '1194')
			// AND (`external_playerid` <> '') AND (`suspended` < '2')
			// FIXME sql query should be case insensitive (SELECT COLLATION(VERSION()) returns utf8_general_ci)
			// FIXME: find out if this depends on platform
			$query = 'SELECT `external_playerid` FROM `players` WHERE (`name`=' . sqlSafeStringQuotes(htmlent($_SESSION['username'])) . ')';
			$query .= ' AND (`external_playerid` <> ' . sqlSafeStringQuotes($_SESSION['external_id']) . ')';
			// do not update users with local login
			$query .= ' AND (`external_playerid` <> ' . "'" . "'" . ')';
			// skip updates for banned or disabled accounts (inappropriate callsign for instance)
			$query .= ' AND (`suspended` < ' . sqlSafeStringQuotes('2') . ')';
			if ($result = $site->execute_query($site->db_used_name(), 'players', $query, $connection))
			{
				$errno = 0;
				$errstr = '';
				while($row = mysql_fetch_array($result))
				{
					// create a new cURL resource
					$ch = curl_init();
					
					// set URL and other appropriate options
					curl_setopt($ch, CURLOPT_URL, 'http://my.bzflag.org/bzidtools2.php?action=name&value=' . sqlSafeString((int) $row['external_playerid']));
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					
					// grab URL and pass it to the browser
					$output = curl_exec($ch);
					
					// close cURL resource, and free up system resources
					curl_close($ch);
					
					// update the entry with the result from the bzidtools2.php script
					if ((strlen($output) > 10) && (strcmp(substr($output, 0, 9), 'SUCCESS: ') === 0))
					{
						// example query: UPDATE `players` SET `name`='moep' WHERE `external_playerid`='external_id';
						$query = 'UPDATE `players` SET `name`=' . sqlSafeStringQuotes(htmlent(substr($output, 9)));
					} else
					{
						// example query: UPDATE `players` SET `name`='moep ERROR: SELECT username_clean FROM bzbb3_users WHERE user_id=uidhere' WHERE `external_playerid`='external_id';
						$query = 'UPDATE `players` SET `name`=' . sqlSafeStringQuotes(htmlent($_SESSION['username']) . ' ' . htmlent($output));
					}
					$query .= ' WHERE `external_playerid`=' . sqlSafeStringQuotes((int) $row['bzid']);

					if (!($update_result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
					{
						// trying to update the players old callsign failed
						$msg = ('Unfortunately there seems to be a database problem which prevents the system from updating the old callsign of another user.'
											 . ' However you curently own that callsign so now there will be two users with the callsign in the table and people will'
											 . 'have problems to distinguish you two!</p>'
											 . '<p>Please report this to an admin.');
						die_with_no_login($msg, $msg);
					}
				}
			} else
			{
				$msg = ('Finding other members who had the same name '
									 . sqlSafeStringQuotes(htmlent($_SESSION['username']))
									 . 'failed. This is a database problem. Please report this to an admin!');
				die_with_no_login($msg, $msg);
			}
		}
	}
	
	
	if (isset($_SESSION['user_logged_in']) && ($_SESSION['user_logged_in']))
	{
		// update last login entry
		$query = 'UPDATE `players_profile` SET `last_login`=' . sqlSafeStringQuotes(date('Y-m-d H:i:s'));
		if (isset($_SESSION['external_login']) && ($_SESSION['external_login']))
		{
			$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($user_id);
		} else
		{
			$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($internal_login_id);
		}
		// only one user account needs to be updated
		$query .= ' LIMIT 1';
		@$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection);		
	}
	
	
	if ((!(isset($_SESSION['user_in_online_list'])) || !($_SESSION['user_in_online_list'])) &&  ((isset($_SESSION['user_logged_in'])) && ($_SESSION['user_logged_in'])))
	{
		$_SESSION['user_in_online_list'] = true;
		$curDate = sqlSafeStringQuotes(date('Y-m-d H:i:s'));
		
		// find out if table exists
		$query = 'SHOW TABLES LIKE ' . "'" . 'online_users' . "'";
		$result = @mysql_query($query, $connection);
		$rows = @mysql_num_rows($result);
		// done
		mysql_free_result($result);
		
		$onlineUsers = false;
		if ($rows > 0)
		{
			// no need to create table in case it does not exist
			// any interested viewer looking at the online page will create it
			$onlineUsers = true;
		}
		
		// use the resulting data
		if ($onlineUsers)
		{
			$query = 'SELECT * FROM `online_users` WHERE `playerid`=' . sqlSafeStringQuotes($user_id);
			$result = mysql_query($query, $connection);
			$rows = mysql_num_rows($result);
			// done
			mysql_free_result($result);
			
			$onlineUsers = false;
			if ($rows > 0)
			{
				// already logged in
				$query = 'DELETE FROM `online_users` WHERE `playerid`=' . sqlSafeStringQuotes($user_id);
				// ignore result
				$result = mysql_query($query, $connection);
				if (!($result))
				{
					$msg = 'Could not remove already logged in user from online user table. Database broken?';
					die_with_no_login($msg, $msg);
				}
			}
			
			// insert logged in user into online_users table
			$query = 'INSERT INTO `online_users` (`playerid`, `username`, `last_activity`) Values';
			$query .= '(' . sqlSafeStringQuotes($user_id) . ', ' . sqlSafeStringQuotes(htmlent($_SESSION['username'])) . ', ' . $curDate . ')';	
			$site->execute_query($site->db_used_name(), 'online_users', $query, $connection);
			
			// do maintenance in case a user still belongs to a deleted team (database problem)
			$query = 'SELECT `teams_overview`.`deleted` FROM `teams_overview`, `players` WHERE `teams_overview`.`teamid`=`players`.`teamid` AND `players`.`id`=';
			$query .= sqlSafeStringQuotes($user_id);
			$query .= ' AND `teams_overview`.`deleted`>' . sqlSafeStringQuotes('2');
			// only the player that just wants to log-in is dealt with
			$query .= ' LIMIT 1';
			if ($result = $site->execute_query($site->db_used_name(), 'teams_overview, players', $query, $connection))
			{
				while($row = mysql_fetch_array($result))
				{
					if ((int) $row['deleted'] === 1)
					{
						// mark who was where, to easily restore an unwanted team deletion
						$query = 'UPDATE `players` SET `last_teamid`=`players`.`teamid`';
						$query .= ', `teamid`=' . sqlSafeStringQuotes('0');
						$query .= ' WHERE `id`=' . sqlSafeStringQuotes($user_id);
						$site->execute_query($site->db_used_name(), 'players', $query, $connection);
					}
				}
				mysql_free_result($result);
			}
			
			// insert to the visits log of the player
			$ip_address = getenv('REMOTE_ADDR');
			$host = gethostbyaddr($ip_address);
			$query = ('INSERT INTO `visits` (`playerid`,`ip-address`,`host`,`forwarded_for`,`timestamp`) VALUES ('
					  . sqlSafeStringQuotes($user_id)
					  . ', ' . sqlSafeStringQuotes(htmlent($ip_address))
					  . ', ' . sqlSafeStringQuotes(htmlent($host))
					  // try to detect original ip-address in case proxies are used
					  . ',' . sqlSafeStringQuotes(htmlent(getenv('HTTP_X_FORWARDED_FOR')))
					  . ', ' . $curDate
					  . ')');
			$site->execute_query($site->db_used_name(), 'visits', $query, $connection);
		}
	}
	
	// $user_id is not set in case no login/registration was performed
	if (getUserID() > 0)
	{
		require_once '../CMS/navi.inc';
		echo '<div class="static_page_box">' . "\n";
		echo '<p class="first_p">Login was successful!</p>' . "\n";
		echo '<p>Your profile page can be found <a href="../Players/?profile=' . $user_id . '">here</a>.</p>' . "\n";
	}
	
	$output_buffer .= ob_get_contents();
	ob_end_clean();
	// write output buffer
	echo $output_buffer;
?>
</div>
</div>
</body>
</html>