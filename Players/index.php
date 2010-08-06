<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require realpath('../CMS/navi.inc');
	
	function show_overview_and_profile_button()
	{
		global $profile;
		
		echo '<a class="button" href="./">overview</a>' . "\n";
		if (!isset($_GET['profile']))
		{
			echo '<a class="button" href="./?profile=' . strval($profile) . '">back to user profile</a>' . "\n";
		}
	}
	
	if (!isset($site))
	{
		$site = new siteinfo();
	}
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_user';
	$viewerid = (int) getUserID();
	
	$allow_edit_any_user_profile = false;
	if (isset($_GET['profile']) || isset($_GET['edit']))
	{
		if (isset($_SESSION['allow_edit_any_user_profile']))
		{
			if (($_SESSION['allow_edit_any_user_profile']) === true)
			{
				$allow_edit_any_user_profile = true;
			}
		}
	}
	$allow_add_admin_comments_to_user_profile = false;
	if (isset($_GET['profile']) || isset($_GET['edit']))
	{
		if (isset($_SESSION['allow_add_admin_comments_to_user_profile']))
		{
			if (($_SESSION['allow_add_admin_comments_to_user_profile']) === true)
			{
				$allow_add_admin_comments_to_user_profile = true;
			}
		}
	}
	
	if ((isset($_GET['edit'])) || (isset($_GET['invite'])))
	{
		if ($viewerid < 1)
		{
			$profile = (int) $_GET['edit'];
			show_overview_and_profile_button();
			echo '<p>You must login to change any player data.</p>' . "\n";
			$site->dieAndEndPage();
		}
	}
	
		
	// abort if user does not exist
	if ((isset($_GET['profile']) || (isset($_GET['edit'])) || isset($_GET['ban'])))
	{
		// display profile page
		if (isset($_GET['profile']))
		{
			$profile = (int) urldecode($_GET['profile']);
		}
		if (isset($_GET['edit']))
		{
			$profile = (int) urldecode($_GET['edit']);
		}
		if (isset($_GET['ban']))
		{
			$profile = (int) urldecode($_GET['ban']);
		}
		
		if ($profile === 0)
		{
			echo '<a class="button" href="./">overview</a>' . "\n";
			echo '<p>The user id 0 is reserved for not logged in players and thus no user with that id could ever exist.</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		// is player banned and does he exist?
		$query = 'SELECT `status` FROM `players` WHERE `id`=' . sqlSafeStringQuotes($profile) . ' LIMIT 1';
		if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
		{
			show_overview_and_profile_button();
			$site->dieAndEndPage('It seems like the player profile can not be accessed for an unknown reason.');
		}
		
		$suspended_status = 'deleted';
		$rows = (int) mysql_num_rows($result);
		if ($rows === 1)
		{
			while ($row = mysql_fetch_array($result))
			{
				$suspended_status = $row['status'];
			}
		}
		mysql_free_result($result);
		
		if ($rows === 0)
		{
			// someone tried to view the profile of a non existing user
			echo '<a class="button" href="./">overview</a>' . "\n";
			echo '<p>This user does not exist.</p>';
			$site->dieAndEndPage('');
		}
		unset($rows);
	}
	
	if (isset($_GET['invite']))
	{
		$profile = (int) urldecode($_GET['invite']);
		
		// was the player deleted during maintenance
		$query = 'SELECT `status` FROM `players` WHERE `id`=' . "'" . (urlencode($profile)) ."'";
		// 1 means maintenance-deleted
		$query .= ' AND `status`<>' . sqlSafeStringQuotes('deleted');
		// only information about one player needed
		$query .= ' LIMIT 1';
		if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		$suspended_status = 'deleted';
		while ($row = mysql_fetch_array($result))
		{
			$suspended_status = $row['status'];
		}
		mysql_free_result($result);
		
		show_overview_and_profile_button();
		
		if (!strcmp($suspended_status, 'active') === 0)
		{
			echo '<p>You may not invite deleted, disabled or banned users</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		$allow_invite_in_any_team = false;
		if (isset($_SESSION['allow_invite_in_any_team']))
		{
			if (($_SESSION['allow_invite_in_any_team']) === true)
			{
				$allow_invite_in_any_team = true;
			}
		}
		
		// 0 is a reserved value and stands for no team
		$leader_of_team_with_id = 0;
		if (!($allow_invite_in_any_team))
		{
			// users are not supposed to invite themselves
			if ($viewerid === $profile)
			{
				echo '<p>You are not allowed to invite yourself.</p>' . "\n";
				$site->dieAndEndPage('');
			}
			
			$query = 'SELECT `id` FROM `teams` WHERE `leader_playerid`=' . "'" . sqlSafeString($viewerid) . "'" . ' LIMIT 1';
			if (!($result = @$site->execute_query($site->db_used_name(), 'teams', $query, $connection)))
			{
				$site->dieAndEndPage('A database related problem prevented to find out if the viewer of this site is the leader of a team.');
			}
			
			$rows = (int) mysql_num_rows($result);
			// only one team must exist for each id
			// if more than one team exists for a given id then it is no user error but a database problem
			if ($rows > (int) 1)
			{
				show_overview_and_profile_button();
				$site->dieAndEndPage('There is more than one team with the id ' . sqlSafeString($teamid) . ' in the database! This is a database problem, please report it to admins!');
			}
			
			while($row = mysql_fetch_array($result))
			{
				$leader_of_team_with_id = $row['id'];
			}
			mysql_free_result($result);
		}
		
		// check invite permission
		if ((!($profile > 0) || !($leader_of_team_with_id < 1)) && !($allow_edit_any_user_profile))
		{
			$site->dieAndEndPage('You (id='. sqlSafeString($viewerid) . ') are not allowed to invite the user with id ' . sqlSafeString($profile) . '.');
		}		
		
		$confirmed = 0;
		if (isset($_POST['confirmed']))
		{
			$confirmed = (int) $_POST['confirmed'];
		}
		
		if (isset($_POST['confirmed']))
		{
			// someone is trying to break the form
			// TODO: implement preview
			if (($confirmed < 1) || ($confirmed > 2))
			{
				$site->dieAndEndPage('Your (id='. $viewerid. ') attempt to insert wrong data into the form was detected.');
			}
						
			$new_randomkey_name = '';
			if (isset($_POST['key_name']))
			{
				$new_randomkey_name = html_entity_decode($_POST['key_name']);
			}
			$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
			
			if (!($randomkeysmatch))
			{
				echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
				$site->dieAndEndPage('');
			}
			
			$invited_to_team = $leader_of_team_with_id;
			if ($allow_invite_in_any_team)
			{
				$invited_to_team = urldecode($_POST['invite_to_team_id']);
				
				// does the specified team exist and is not deleted at all?
				$query = 'SELECT `teams`.`id` FROM `teams`,`teams_overview`';
				$query .= ' WHERE (`teams_overview`.`deleted`=' . "'" . sqlSafeString('0') . "'";
				$query .= ' OR `teams_overview`.`deleted`=' . "'" . sqlSafeString('1') . "'";
				$query .= ' OR `teams_overview`.`deleted`=' . "'" . sqlSafeString('3') . "'";
				$query .= ') AND `teams`.`id`=`teams_overview`.`teamid`';				
				$query .= ' AND `teams`.`id`=`teams_overview`.`teamid` AND `teams`.`id`=' . "'" . sqlSafeString($invited_to_team) . "'";
				$query .= ' LIMIT 1';
				if (!($result = @$site->execute_query($site->db_used_name(), 'teams', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('');
				}
				$rows = (int) mysql_num_rows($result);
				mysql_free_result($result);
				
				if ($rows < 1)
				{
					echo '<p>The specified team does not exist and thus the invitation was cancelled.</p>' . "\n";
					$site->dieAndEndPage('');
				}
			}
			
			if ($invited_to_team < 1)
			{
				$site->dieAndEndPage('You do not have permission to invite players to a team!');
			}
			
			// invitate the player to team
			$query = 'INSERT INTO `invitations` (`invited_playerid`, `teamid`, `expiration`) VALUES ';
			$query .= '(' . "'" . sqlSafeString($profile) . "'" . ', ' . "'" . sqlSafeString ($invited_to_team). "'";
			$sevendayslater = strtotime('+7 days');
			$sevendayslater = strftime('%Y-%m-%d %H:%M:%S', $sevendayslater);
			$query .= ', ' . "'" . sqlSafeString($sevendayslater) . "'" . ')';
			if (!($result = @$site->execute_query($site->db_used_name(), 'invitations', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			// get team name
			$team_name = '(no team name)';
			$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($invited_to_team) . ' LIMIT 1';
			if (!($result = @$site->execute_query($site->db_used_name(), 'teams', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			while($row = mysql_fetch_array($result))
			{
				$team_name = $row['name'];
			}
			mysql_free_result($result);
			
			// get player name
			$player_name = '(no player name)';
			$query = 'SELECT `name` FROM `players` WHERE `id`=' . "'" . sqlSafeString($viewerid) . "'" . ' LIMIT 1';
			if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			while($row = mysql_fetch_array($result))
			{
				$player_name = $row['name'];
			}
			mysql_free_result($result);
			
			// lock messages_storage because of mysql_insert_id() usage
			$query = 'LOCK TABLES `messages_storage` WRITE';
			if (!($result = @$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			
			// create invitation message in database
			$query = 'INSERT INTO `messages_storage` (`author_id`, `subject`, `timestamp`, `message`, `from_team`, `recipients`) VALUES ';
			$query .= '(' . sqlSafeStringQuotes ('0');
			$query .= ', ' . sqlSafeStringQuotes(('Invitation to team ' . $team_name)) . ', ' . sqlSafeStringQuotes(date('Y-m-d H:i:s'));
			$query .= ', ' . sqlSafeStringQuotes(('Congratulations, you were invited by ' . $player_name . ' to the team ' . $team_name . '!' . "\n" . 'The invitation will expire in 7 days.'));
			$query .= ', ' . sqlSafeStringQuotes('0') . ', ' . sqlSafeStringQuotes($profile) . ')';
			if (!($result = @$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			// get the msgid generated from database
			$msgid = mysql_insert_id($connection);
			
			// unlock messages_storage because mysql_insert_id() was used and is no longer needed
			$query = 'UNLOCK TABLES';
			if (!($result = @$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			// send the invitation message to user
			$query = 'INSERT INTO `messages_users_connection` (`msgid`, `playerid`, `in_inbox`, `in_outbox`) VALUES ';
			$query .= '(' . sqlSafeStringQuotes($msgid) . ', ' . sqlSafeStringQuotes($profile);
			$query .= ', ' . sqlSafeStringQuotes('1');
			$query .= ', ' . sqlSafeStringQuotes('0') . ')';
			if (!($result = @$site->execute_query($site->db_used_name(), 'messages_users_connection', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			echo '<p>The player was invited successfully.</p>' . "\n";
			
			// invitation and notification was sent
			$site->dieAndEndPage('');
		}
		
		if ($allow_invite_in_any_team || ($leader_of_team_with_id > 0))
		{
			echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?invite=' . htmlentities(urlencode($profile)) . '">' . "\n";
			echo '<div><input type="hidden" name="confirmed" value="1"></div>' . "\n";
			
			// display team picker in case the user can invite a player to any team
			if ($allow_invite_in_any_team)
			{
				// get a full list of teams, excluding deleted teams
				// teams_overview.deleted: 0 new; 1 active; 2 deleted; 3 revived
				$query = 'SELECT `teams`.`id`,`teams`.`name` FROM `teams`,`teams_overview`';
				$query .= ' WHERE (`teams_overview`.`deleted`=' . "'" . sqlSafeString('0') . "'";
				$query .= ' OR `teams_overview`.`deleted`=' . "'" . sqlSafeString('1') . "'";
				$query .= ' OR `teams_overview`.`deleted`=' . "'" . sqlSafeString('3') . "'";
				$query .= ') AND `teams`.`id`=`teams_overview`.`teamid`';
				if (!($result = @$site->execute_query($site->db_used_name(), 'teams', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('');
				}
				
				$team_name_list = Array();
				$team_id_list = Array();
				while($row = mysql_fetch_array($result))
				{
					$team_name_list[] = $row['name'];
					$team_id_list[] = $row['id'];
				}
				mysql_free_result($result);
				
				$list_team_id_and_name = Array();
				
				$list_team_id_and_name[] = $team_id_list;
				$list_team_id_and_name[] = $team_name_list;
				
				$n = ((int) count($team_id_list)) - 1;
				
				echo '<p><label for="invite_to_team">Select the team the player will be invited to: </label>' . "\n";
				echo '<span><select id="invite_to_team" name="invite_to_team_id';
				if (isset($_GET['delete']))
				{
					echo '" disabled="disabled';
				}
				echo '">' . "\n";
				
				$n = ((int) count($team_id_list)) - 1;
				for ($i = 0; $i <= $n; $i++)
				{
					echo '<option value="';
					// no htmlentities because team id 0 is reserved
					echo $list_team_id_and_name[0][$i];
					if (isset($leader_of_team_with_id) && ((int) $list_team_id_and_name[0][$i] === $leader_of_team_with_id))
					{
						echo '" selected="selected';
					}
					echo '">' . $list_team_id_and_name[1][$i];
					echo '</option>' . "\n";
				}
				
				echo '</select></span>' . "\n";			
			}
			
			$new_randomkey_name = $randomkey_name . microtime();
			$new_randomkey = $site->set_key($new_randomkey_name);
			echo '<div><input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"></div>' . "\n";
			echo '<div><input type="hidden" name="' . htmlentities($randomkey_name) . '" value="';
			echo urlencode(($_SESSION[$new_randomkey_name])) . '"></div>' . "\n";
			
			// find out the player's name
			$query = 'SELECT `name` FROM `players` WHERE `id`=' . "'" . sqlSafeString($profile) . "'" . ' LIMIT 1';
			if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
			{
				echo '</form>' . "\n";
				$site->dieAndEndPage('A database related problem prevented to find out the name of the player.');
			}
			
			// initialise value
			$player_name = '(name could not be found)';
			while($row = mysql_fetch_array($result))
			{
				$player_name = $row['name'];
			}
			
			echo '<p style="display:inline">Do you really want to invite ' . $player_name . '?</p>' . "\n";
			echo '<div style="display:inline"><input type="submit" name="invite_player" value="Invite the player" id="send"></div>' . "\n";
			echo '</form>' . "\n";
			$site->dieAndEndPage('');
		}
		
		$site->dieAndEndPage('');
	}
	
	if (isset($_GET['edit']))
	{
		// edit profile page
		$profile = (int) $_GET['edit'];
		
		show_overview_and_profile_button();
		
		if ($profile === 0)
		{
			echo '<p>The user id 0 is reserved for not logged in players and thus no user with that id could ever exist.</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		if (!(($profile === $viewerid) || $allow_edit_any_user_profile))
		{
			$site->dieAndEndPage('You (id='. $viewerid. ') are not allowed to edit the profile of the user with id ' . sqlSafeString($profile));
		}
		
		$suspended_status = 'deleted';
		if (!(isset($_POST['user_suspended_status_id'])))
		{
			// get entire suspended status, including maintenance-deleted
			$query = 'SELECT `status`';
			if (isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user'])
			{
				// the ones who can ban, can also change a user's callsign
				$query .= ',`name`';
			}
			$query .= ' FROM `players` WHERE `id`=' . "'" . (urlencode($profile)) ."'";
			// only information about one player needed
			$query .= ' LIMIT 1';
			if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			// inisialise name with error message, it will get overwritten if a name can be found
			$callsign = 'ERROR: unknown callsign';
			while($row = mysql_fetch_array($result))
			{
				$suspended_status = $row['status'];
				$callsign = htmlent($row['name']);
			}
			mysql_free_result($result);
			
			if (strcmp($suspended_status, 'deleted') === 0)
			{
				echo '<p>You may not edit this user as the user was deleted during maintenance.</p>';
				$site->dieAndEndPage('');
			}
		}		
		
		if (isset($_POST['confirmed']))
		{
			// someone is trying to break the form
			// TODO: implement preview
			if (($_POST['confirmed'] < 1) || ($_POST['confirmed'] > 2))
			{
				$site->dieAndEndPage('Your (id='. $viewerid. ') attempt to insert wrong data into the form was detected.');
			}
						
			$new_randomkey_name = '';
			if (isset($_POST['key_name']))
			{
				$new_randomkey_name = html_entity_decode($_POST['key_name']);
			}
			$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
			
			if (!($randomkeysmatch))
			{
				echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
				$site->dieAndEndPage('');
			}
			
			// could callsign change be requested?
			if (isset($_POST['callsign']))
			{
				// only admins can edit their comments
				if (isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user'])
				{
					// is the player name already used?
					$query = 'SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes(htmlent($_POST['callsign'])) . ' LIMIT 1';
					if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('');
					}
					
					if ((int) mysql_num_rows($result) > 0)
					{
						$tmp_name_change_requested = true;
						if (!((int) mysql_num_rows($result) > 1))
						{
							while ($row = mysql_fetch_array($result))
							{
								if (((int) $row['id']) === ((int) $profile))
								{
									$tmp_name_change_requested = false;
								}
							}
						}
						mysql_free_result($result);
						
						// was callsign change requested?
						if ($tmp_name_change_requested)
						{
							// callsign change was requested!
							// player name already used -> do not change to player name to it
							echo '<p>The player name was not changed because there is already a player with that name in the database.</p>' . "\n";
						}
						unset($tmp_name_change_requested);
					} else
					{
						mysql_free_result($result);
						$query = 'UPDATE `players` SET `name`=' . sqlSafeStringQuotes(htmlent(urldecode($_POST['callsign'])));
						$query .= ' WHERE `id`=' . sqlSafeStringQuotes($profile);
						if (!($result = @$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
						{
							// query was bad, error message was already given in $site->execute_query(...)
							$site->dieAndEndPage('');
						}
					}
				}
			}
			
			if (isset($_POST['location']))
			{
				$query = 'SELECT `location` FROM `players_profile` WHERE `playerid`=' . sqlSafeStringQuotes($profile) . ' LIMIT 1';
				if (!($result = @$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
				{
					$site->dieAndEndPageNoBox('Could not confirm value ' . sqlSafeStringQuotes($_POST['location']) . ' as new location.');
				}
				$update_location = false;
				while ($row = mysql_fetch_array($result))
				{
					if (!(((int) $_POST['location']) === ((int) $row['location'])))
					{
						$update_location = true;
					}
				}
				mysql_free_result($result);
				if ($update_location)
				{
					$query = 'UPDATE `players_profile` SET `location`=' . sqlSafeStringQuotes((int) $_POST['location']);
					$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
					if (!($result = @$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPageNoBox('Could not update value ' . sqlSafeStringQuotes($_POST['location']).' as new location for player '.sqlSafeStringQuotes($profile) . '.');
					}
				}
			}
			
			if (isset($_POST['timezone']))
			{
				$query = 'UPDATE `players_profile` SET `UTC`=' . sqlSafeStringQuotes(intval($_POST['timezone']));
				$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
				if (!($result = @$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('');
				}
			}
			
			// is there a user comment?
			if (isset($_POST['user_comment']))
			{
				if (!(strcmp($_POST['user_comment'], 'No profile text has yet been set up') === 0))
				{
					// yes there is a comment, save it!
					$query = 'UPDATE `players_profile` SET `user_comment`=' . sqlSafeStringQuotes($site->bbcode($_POST['user_comment']));
					$query .= ', `raw_user_comment`=' . sqlSafeStringQuotes($_POST['user_comment']);
					$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
					if (!($result = @$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('');
					}
				}
			}
			
			if (isset($_POST['logo_url']))
			{
				$allowedExtensions = array('.png', '.bmp', '.jpg', '.gif', 'jpeg');
				$logo_url = sqlSafeString($_POST['logo_url']);
				if ((in_array(substr($logo_url, -4), $allowedExtensions)) && (substr($logo_url, 0, 7) == 'http://'))
				{
					// image url exists and has a valid file extension
					$query = "UPDATE `players_profile` SET `logo_url` = '$logo_url'";
					$query .= " WHERE `playerid` = $profile";
					if (!($result = $site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('');
					}
				} else
				{
					if (!(strcmp(($_POST['logo_url']), '') === 0))
					{
						echo '<p>Error: Skipping logo setting: Not allowed URL or extension.</p>';
					}
				}
			}
			
			if (isset($_POST['admin_comments']))
			{
				// only admins can edit their comments
				if ($allow_add_admin_comments_to_user_profile)
				{
					$query = 'UPDATE `players_profile` SET `admin_comments`=' . sqlSafeStringQuotes($site->bbcode($_POST['admin_comments']));
					$query .= ', `raw_admin_comments`=' . sqlSafeStringQuotes($_POST['admin_comments']);
					$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
					if (!($result = @$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('');
					}
				}
			}
			
			echo '<p>The player profile has been updated successfully.</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		// display editing form
		echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?edit=' . $profile . '">' . "\n";
		echo '<div><input type="hidden" name="confirmed" value="1"></div>' . "\n";
		$new_randomkey_name = $randomkey_name . microtime();
		$new_randomkey = $site->set_key($new_randomkey_name);
		echo '<div><input type="hidden" name="key_name" value="' . htmlspecialchars($new_randomkey_name) . '"></div>' . "\n";
		echo '<div><input type="hidden" name="' . htmlspecialchars($randomkey_name) . '" value="';
		echo urlencode(($_SESSION[$new_randomkey_name])) . '"></div>' . "\n";
		
		$query = 'SELECT `location`, `UTC`';
		$query .= ', `raw_user_comment`, `raw_admin_comments`';
		$query .= ', `logo_url` FROM `players_profile` WHERE `playerid`=' . "'" . sqlSafeString($profile) . "'";
		$query .= ' LIMIT 1';
		if (!($result = @$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		$location = 0;
		$timezone = 0;
		$user_comment = '';
		$admin_comments = '';
		while ($row = mysql_fetch_array($result))
		{
			$location = (int) $row['location'];
			$timezone = (int) $row['UTC'];
			$user_comment = $row['raw_user_comment'];
			$admin_comments = $row['raw_admin_comments'];
			$logo_url = $row['logo_url'];
		}
		mysql_free_result($result);
		
		// show some sort of comment because one would expect some profile text
		// admin comments in contrary should not be set often and thus just ignore the default to make sure it does not get set by accident
		if (strcmp($user_comment, '') === 0)
		{
			$user_comment = 'No profile text has yet been set up';
		}
		
		// admins may change user names
		if (isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user'])
		{
			echo '<p><label class="player_edit" for="edit_player_name">Change callsign:</label> ';
			$site->write_self_closing_tag('input id="edit_player_name" type="text" name="callsign" maxlength="50" size="60" value="'
										  . htmlent_decode($callsign) . '"');
			echo '</p>';
		}
		
		// location
		$query = 'SELECT `id`,`name` FROM `countries` ORDER BY `name`';
		if (!($result = @$site->execute_query($site->db_used_name(), 'countries', $query, $connection)))
		{
			$site->dieAndEndPage('Could not retrieve list of countries from database.');
		}
		echo '<p><label class="player_edit" for="edit_player_location">Change country:</label> ';
		echo '<select id="edit_player_location" name="location">';
		while ($row = mysql_fetch_array($result))
		{
			echo '<option value="';
			echo htmlspecialchars($row['id']);
			if ($location === ((int) $row['id']))
			{
				echo '" selected="selected';
			}
			echo '">';
			echo htmlent($row['name']);
			echo '</option>' . "\n";
		}
		mysql_free_result($result);
		echo '</select>';
		echo '</p>' . "\n\n";
		
		// timezone
		echo '<p><label class="player_edit" for="edit_player_location">Change timezone:</label> ';
		echo '<select id="edit_player_timezone" name="timezone">';
		for ($i = -12; $i <= 12; $i++)
		{
			echo '<option value="';
			echo htmlspecialchars($i);
			if ($timezone === $i)
			{
				echo '" selected="selected';
			}
			echo '">';
			if ($i >= 0)
			{
				$time_format = '+' . strval($i);
			} else
			{
				$time_format = strval($i);
			}
			echo htmlent('UTC ' . $time_format);
			echo '</option>' . "\n";
		}
		unset($time_format);
		echo '</select>';
		echo '</p>' . "\n\n";
		
		// user comment
		echo '<p><label class="player_edit" for="edit_user_comment">User comment: </label>' . "\n";
		echo '<span><textarea class="player_edit" id="edit_user_comment" rows="10" cols="50" name="user_comment">';
		echo $user_comment;
		echo '</textarea></span></p>';

		// logo/avatar url
		echo '<p><label class="player_edit" for="edit_avatar_url">Avatar URL: </label>';
		$site->write_self_closing_tag('input id="edit_avatar_url" type="text" name="logo_url" maxlength="200" size="60" value="'.$logo_url.'"');
		echo '</p>';
		
		// admin comments, these should only be set by an admin
		if ($allow_add_admin_comments_to_user_profile === true)
		{
			echo '<p><label class="player_edit" for="edit_admin_comments">Edit admin comments: </label>';
			echo '<span><textarea class="player_edit" id="edit_admin_comments" rows="10" cols="50" name="admin_comments">';
			echo $admin_comments;
			echo '</textarea></span></p>' . "\n";
		}
		
		echo '<div><input type="submit" name="edit_user_profile_data" value="Change user profile" id="send"></div>' . "\n";
		echo '</form>' . "\n";
		
		$site->dieAndEndPageNoBox('');
	}
	
	// banning user section
	if (isset($_GET['ban']))
	{
		// show possiblity to go fast back to registered user overview
		show_overview_and_profile_button();
		
		echo '<div class="static_page_box">' . "\n";
		if (!(isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user']))
		{
			echo '<p class="first_p">You have no permissions to perform that action.</p>';
			$site->dieAndEndPage('');
		}
		
		if (strcmp($suspended_status, 'deleted') === 0)
		{
			echo '<p>You may not set a status for this user as the user was deleted during maintenance.</p>';
			$site->dieAndEndPage('');
		}
		
		// need name for player for better end user experience
		$query = 'SELECT `name` FROM `players` WHERE `id`=' . "'" . (urlencode($profile)) ."'";
		$query .= ' LIMIT 1';
		// perform the query
		if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		if (isset($_POST['confirmed']) && ((int) $_POST['confirmed'] === (int) 1))
		{
			// validate random key
			$new_randomkey_name = '';
			if (isset($_POST['key_name']))
			{
				$new_randomkey_name = html_entity_decode($_POST['key_name']);
			}
			$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
			
			if (!($randomkeysmatch))
			{
				echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
				$site->dieAndEndPage('');
			}
			
			$suspended_status = (int) $_POST['user_suspended_status_id'];
			if ($suspended_status === 1)
			{
				$suspended_status = 'active';
			} elseif ($suspended_status === 2)
			{
				$suspended_status = 'login disabled';
			} else
			{
				$suspended_status = 'banned';
			}
			$query = 'UPDATE `players` SET `status`=' .sqlSafeStringQuotes(htmlentities($suspended_status));
			$query .= ' WHERE `id`=' . sqlSafeStringQuotes($profile);
			if (!($result_suspended = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
			{
				echo '<p>The new suspended status for';
				while($row = mysql_fetch_array($result_suspended))
				{
					echo htmlent($row['name']);
				}
				mysql_free_result($result_suspended);
				echo 'could not be set due to a SQL/db connectivity problem.</p>';
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}			
			
			echo '<p>The new status of user ';
			while($row = mysql_fetch_array($result))
			{
				echo htmlent($row['name']);
			}
			mysql_free_result($result);
			echo ' has been set to ';
			echo htmlent($suspended_status);			
			echo '.</p>';
			
			// done with setting account status
			$site->dieAndEndPage('');
		}
		
		echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?ban=' . htmlentities(urlencode($profile)) . '">' . "\n";
		echo '<div>';
		$site->write_self_closing_tag('input type="hidden" name="confirmed" value="1"');
		echo '</div>' . "\n";
		
		echo '<p id="edit_user_suspended_status_description">Select new status for user ';
		
		while($row = mysql_fetch_array($result))
		{
			echo htmlent($row['name']);
		}
		mysql_free_result($result);
		echo ':' . "\n";
		
		// now display form
		echo '<span><select id="user_suspended_status" name="user_suspended_status_id';
		echo '">' . "\n";
		
		echo '<option value="1';
		if (strcmp($suspended_status, 'active') === 0)
		{
			echo '" selected="selected';
		}
		echo '">active';
		echo '</option>' . "\n";
		echo '<option value="2';
		if (strcmp($suspended_status, 'login disabled') === 0)
		{
			echo '" selected="selected';
		}
		echo '">disabled';
		echo '</option>' . "\n";
		echo '<option value="3';
		if (strcmp($suspended_status, 'banned') === 0)
		{
			echo '" selected="selected';
		}
		echo '">banned';
		echo '</option>' . "\n";
		
		echo '</select></span></p>' . "\n";			
		
		// send button
		echo '<div class="edit_user_suspended_status_send">';
		$site->write_self_closing_tag('input type="submit" name="edit_user_suspended_status" value="Set new user suspended status" id="send"');
		echo '</div>' . "\n";
		
		// random key fun to prevent automated sending by visiting a page
		$new_randomkey_name = $randomkey_name . microtime();
		$new_randomkey = $site->set_key($new_randomkey_name);
		echo '<div>';
		$site->write_self_closing_tag('input type="hidden" name="key_name" value="' . htmlspecialchars($new_randomkey_name) . '"');
		echo '</div>' . "\n";
		echo '<div>';
		$site->write_self_closing_tag('input type="hidden" name="' . htmlspecialchars($randomkey_name) . '" value="'
									  . urlencode(($_SESSION[$new_randomkey_name])) . '"');
		echo '</div>' . "\n";
		
		// form finished
		echo '</form>' . "\n";
		
		// done with banning section
		$site->dieAndEndPage('');
	}
	
	// user profile
	if (isset($_GET['profile']))
	{
		show_overview_and_profile_button();
		
		if (isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user'])
		{
			// a user should not be able to ban the own account
			// allowing to unban is also not wished as there could always be a (theoretical) problem
			// in the login code that lets a banned player login to the site
			if (!($viewerid === $profile))
			{
				if (strcmp($suspended_status, 'active') === 0)
				{
					echo '<a class="button" href="./?ban=' . (urlencode($profile)) . '">ban</a>' . "\n";
				} elseif (strcmp($suspended_status, 'deleted') !== 0)
				{
					echo '<a class="button" href="./?ban=' . (urlencode($profile)) . '">unban</a>' . "\n";
				}
			}
		}
		
		if ((($profile > 0) && $viewerid === $profile || $allow_edit_any_user_profile) && (strcmp($suspended_status, 'deleted') !== 0))
		{
			echo '<a class="button" href="./?edit=' . (urlencode($profile)) . '">edit</a>' . "\n";
		}
		// need an element displayed with display: block before the team area
		echo '<div class="p"></div>' . "\n";
		
		// the data we want
		$query = 'SELECT `players`.`name`,`countries`.`name` AS `country_name`,`countries`.`flagfile`, `players_profile`.`UTC`';
		$query .= ', `players_profile`.`last_login`,`players_profile`.`joined`, `players_profile`.`user_comment`';
		// optimise query by finding out whether the admin comments are needed at all (no permission to view = unnecessary)
		if ((isset($_SESSION['allow_view_user_visits'])) && ($_SESSION['allow_view_user_visits'] === true))
		{
			$query .= ', `players_profile`.`admin_comments`';
		}
		$query .= ', `players_profile`.`logo_url`';
		// if the player is a member of team get the corresponding team name
		$query .= ',`players`.`teamid`,IF (`players`.`teamid`<>' . sqlSafeStringQuotes('0') . ',';
		$query .= '(SELECT `teams`.`name` FROM `teams` WHERE `teams`.`id`=`players`.`teamid`),' . "''" . ') AS `team_name`';
		// join the tables `teams`, `teams_overview` and `teams_profile` using the team's id
		$query .= ' FROM `players`, `players_profile`,`countries` WHERE `players`.`id` = `players_profile`.`playerid`';
		$query .= ' AND `players_profile`.`location`=`countries`.`id`';
		$query .= ' AND `players`.`id`=';
		$query .= "'" . sqlSafeString($profile) . "'" . ' LIMIT 1';
		if (!($result = @$site->execute_query($site->db_used_name(), 'players, players_profile, countries', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		if ((int) mysql_num_rows($result) < 1)
		{
			echo 'It seems like the flag specified by this user does not exist.';
			// the data we want
			$query = 'SELECT `players`.`name`,' . sqlSafeStringQuotes('') . ' AS `country_name`,' . sqlSafeStringQuotes('') . ' AS `flagfile`';
			$query .= ', `players_profile`.`UTC`, `players_profile`.`last_login`, `players_profile`.`joined`, `players_profile`.`user_comment`';
			// optimise query by finding out whether the admin comments are needed at all (no permission to view = unnecessary)
			if ((isset($_SESSION['allow_view_user_visits'])) && ($_SESSION['allow_view_user_visits'] === true))
			{
				$query .= ', `players_profile`.`admin_comments`';
			}
			$query .= ', `players_profile`.`logo_url`';
			// if the player is a member of team get the corresponding team name
			$query .= ',`players`.`teamid`,IF (`players`.`teamid`<>' . sqlSafeStringQuotes('0') . ',';
			$query .= '(SELECT `teams`.`name` FROM `teams` WHERE `teams`.`id`=`players`.`teamid`),' . "''" . ') AS `team_name`';
			// join the tables `teams`, `teams_overview` and `teams_profile` using the team's id
			$query .= ' FROM `players`, `players_profile` WHERE `players`.`id` = `players_profile`.`playerid`';
			$query .= ' AND `players`.`id`=';
			$query .= "'" . sqlSafeString($profile) . "'" . ' LIMIT 1';
			if (!($result = @$site->execute_query($site->db_used_name(), 'players, players_profile', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
		}
		
		if ((int) mysql_num_rows($result) > 1)
		{
			// more than one team with the same id!
			// this should never happen
			$site->dieAndEndPage('There was more than one user with that id ('
								 . sqlSafeString($profile) . '). This is a database error, please report it to admins.');
		}
		
		$player_name = '';
		while($row = mysql_fetch_array($result))
		{
			$player_name = $row['name'];
			
			echo '<div class="user_areas_container">' . "\n";
			echo '<div class="user_area">' . "\n";
			echo '	<div class="user_header">' . "\n";
			echo '		<div class="user_general_info_header">Player Profile</div>' . "\n";
			echo '	</div>' . "\n";
			echo '	<div class="user_description">' . "\n";
			if (!(strcmp(($row['logo_url']), '') === 0))
			{
				// user entered a logo
				$site->write_self_closing_tag('img class="player_logo" src="'
											  . htmlentities($row['logo_url'])
											  . '" style="max-width:200px; max-height:150px" alt="player logo"');
			}
			echo '		<span class="user_profile_name">' . $player_name . '</span> ';
			if (strcmp($suspended_status, 'deleted') === 0)
			{
				echo '<span class="user_description_deleted">(deleted)</span>' . "\n";
			}
			if ((strcmp($suspended_status, 'login disabled') === 0) || (strcmp($suspended_status, 'banned') === 0))
			{
				echo '<span class="user_description_banned">(banned)</span>' . "\n";
			}
			if ((int) $row['teamid'] !== 0)
			{
				echo '<div class="user_profile_team_name">';
				echo 'Team: <a href="../Teams?profile=' . $row['teamid'] . '">' . $row['team_name'] . '</a>';
				echo '</div>' . "\n";
			}
			
			echo '		<div class="user_profile_location_description_row"><span class="user_profile_location_description">location: </span>';
			if (!(strcmp($row['flagfile'], '') === 0))
			{
				$site->write_self_closing_tag('img alt="country flag" class="country_flag" src="../Flags/' . $row['flagfile'] . '"');
			}
			echo '<span class="user_profile_location">' . htmlent($row['country_name']) . '</span></div>' . "\n";
			if (intval($row['UTC']) >= 0)
			{
				$time_format = '+' . strval($row['UTC']);
			} else
			{
				$time_format = strval($row['UTC']);
			}
			echo '		<div class="user_profile_location_timezone_row"><span class="user_profile_location_timezone_description">timezone: </span> <span class="user_profile_location_timezone">' . htmlent('UTC ' . $time_format) . '</span></div>' . "\n";
			unset($time_format);
			echo '		<div class="user_profile_joined_description_row"><span class="user_profile_joined_description">joined:</span> <span class="user_profile_joined">' . htmlent($row['joined']) . '</span></div>' . "\n";
			echo '		<div class="user_profile_last_login_description_row"><span class="user_profile_last_login_description">last login:</span> <span class="user_profile_last_login">' . htmlent($row['last_login']) . '</span></div>' . "\n";
			echo '	</div>' . "\n";
			echo '</div>' . "\n";
			
			echo '<div class="user_area">' . "\n";
			echo '	<div class="user_general_info_header">Profile Text</div>' . "\n";
			echo '	<span class="user_comment">';
			
			if (strcmp ($row['user_comment'], '') === 0)
			{
				echo '<span class="no_user_comment">The user has not set up any profile text yet.</span>';
			} else
			{
				echo $row['user_comment'];
			}
			echo '</span>' . "\n";
			echo '</div>' . "\n";
			
			// only admins can see their comments, as users may be upset about admin comments on their profile page
			if ($allow_add_admin_comments_to_user_profile === true)
			{
				$admin_comments = $row['admin_comments'];
				if (strcmp ($admin_comments, '') === 0)
				{
					echo '<p>There are no admin comments on this user page.</p>';
				} else
				{
					echo '	<div class="user_admin_comments_area">' . "\n";
					echo '		<div class="user_admin_comments_header_text">admin comments</div>' . "\n";
					echo '		<div class="user_admin_comments">' . $admin_comments . '</div>' . "\n";
					echo '	</div>' . "\n";
				}
			}
		}
		echo '</div>' . "\n";
		// query result no longer needed
		mysql_free_result($result);
		
		// user needs to be logged in to see some links
		if ($viewerid > 0)
		{
			
			if ($site->use_xtml())
			{
				echo '<br />' . "\n";
			} else
			{
				echo '<br>' . "\n";
			}
			echo '<a class="button" href="../Messages/?add&amp;playerid=' . htmlspecialchars(urlencode($player_name)) . '">Write bzmail to player</a>' . "\n";
			
			$allow_invite_in_any_team = false;
			if (isset($_SESSION['allow_invite_in_any_team']))
			{
				if (($_SESSION['allow_invite_in_any_team']) === true)
				{
					$allow_invite_in_any_team = true;
				}
			}
			
			// 0 is a reserved value and stands for no team
			$leader_of_team_with_id = 0;
			if (!($allow_invite_in_any_team))
			{
				$query = 'SELECT `id` FROM `teams` WHERE `leader_playerid`=' . "'" . sqlSafeString($viewerid) . "'" . ' LIMIT 1';
				if (!($result = @$site->execute_query($site->db_used_name(), 'teams', $query, $connection)))
				{
					$site->dieAndEndPage('A database related problem prevented to find out if the viewer of this site is the leader of a team.');
				}
				
				// if the viewer is leader of a team, a value other than 0 will be the result of the query
				// and that value will be the id of the team the viewer is leader
				while($row = mysql_fetch_array($result))
				{
					$leader_of_team_with_id = $row['id'];
				}
			}
			
			// users are not supposed to invite themselves
			if (($allow_invite_in_any_team || (($leader_of_team_with_id > 0) && ($viewerid !== $profile))) && (strcmp($suspended_status, 'deleted') !== 1))
			{
				echo '<a class="button" href="?invite=' . htmlspecialchars(urlencode($profile)) . '">Invite player to team</a>' . "\n";
			}
			
			
			if (((isset($_SESSION['allow_view_user_visits'])) && ($_SESSION['allow_view_user_visits'] === true)) && (strcmp($suspended_status, 'deleted') !== 1))
			{
				echo '<a class="button" href="../Visits/?profile=' . htmlspecialchars($profile) . '">View visits log</a>' . "\n";
			}
		}
		$site->dieAndEndPageNoBox();
	}
	
	// display overview
	
	// form letting search for team name or time
	// this form is considered not to be dangerous, thus no key checking at all and also using the get method
	echo "\n" . '<form enctype="application/x-www-form-urlencoded" method="get" action="./">' . "\n";
	
	// input string
	echo '<div style="display:inline"><label for="player_search_string">Search for:</label> ' . "\n";
	echo '<span>';
	if (isset($_GET['search']))
	{
		$site->write_self_closing_tag('input type="text" id="player_search_string" name="search_string" value="' . $_GET['search_string'] . '"');
	} else
	{
		$site->write_self_closing_tag('input type="text" id="player_search_string" name="search_string"');
	}
	echo '</span></div> ' . "\n";
	
	// looking for either team name or time
	echo '<div style="display:inline"><label for="player_search_type">sort by:</label> ' . "\n";
	echo '<span><select id="player_search_type" name="search_type">';
	
	$search_string = '';
	if (isset($_GET['search_string']))
	{
		$search_string = $_GET['search_string'];
		$search_string = str_replace('*', '%', $search_string);
		if (!(strcmp(substr($search_string, -1), '%') === 0))
		{
			$search_string .= '%';
		}
	}
	
	// avoid to let the user enter a custom table column at all costs
	// only let them switch between team name and time search
	
	// search for team name by default
	$search_sort = '';
	$search_team_sort = false;
	$search_joined_sort = false;
	$search_player_sort = false;
	if (isset($_GET['search_type']))
	{
		switch ($_GET['search_type'])
		{
			case 'player': $search_player_sort = true; break;
			case 'team': $search_team_sort = true; break;
			case 'joined': $search_joined_sort = true; break;
			default: $search_team_sort = true;
		}
	}
	
	echo '<option';
	if ($search_player_sort)
	{
		$search_sort = 'player';
		echo ' selected="selected"';
	}
	echo ' value="player"';
	echo '>player name</option>';
	
	echo '<option';
	if ($search_team_sort)
	{
		$search_sort = 'team';
		echo ' selected="selected"';
	}
	echo ' value="team"';
	echo '>team name</option>';
	
	echo '<option';
	if ($search_joined_sort)
	{
		$search_sort = 'time';
		echo ' selected="selected"';
	}
	echo '>joined</option>';
	
	echo '</select></span>';
	
	echo ' <label for="player_search_result_included">Entries:</label> ';
	echo '<span><select id="player_search_result_included" name="search_result_included">';
	
	// search for all players by default
	$search_type = '';
	$search_all = false;
	$search_team = false;
	$search_teamless = false;
	if (isset($_GET['search_result_included']))
	{
		switch ($_GET['search_result_included'])
		{
			case 'player': $search_player = true; break;
			case 'team': $search_team = true; break;
			case 'teamless': $search_teamless = true; break;
			default: $search_all = true;
		}
	}
	
	echo '<option';
	if ($search_all)
	{
		$search_type = 'all';
		echo ' selected="selected"';
	}
	echo '>all</option>';
	
	echo '<option';
	if ($search_team)
	{
		$search_type = 'team';
		echo ' selected="selected"';
	}
	echo ' value="team"';
	echo '>team members</option>';
	
	echo '<option';
	if ($search_teamless)
	{
		$search_type = 'teamless';
		echo ' selected="selected"';
	}
	echo '>teamless</option>';
	
	echo '</select></span>';
	echo '</div> ' . "\n";
	
	echo '<div style="display:inline">';
	$site->write_self_closing_tag('input type="submit" name="search" value="Search" id="send"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	// end display toolbar
	
	// get all data at once instead of many small queries -> a lot more efficient
	// example query:
	// SELECT `players`.`id`,`players`.`teamid`,`players`.`name` AS `player_name`,
	// IF (`players`.`teamid`<>'0',(SELECT `teams`.`name` FROM `teams`
	// WHERE `teams`.`id`=`players`.`teamid` LIMIT 1),'(teamless)') AS `team_name`,
	// `players_profile`.`joined` FROM `players`,`players_profile`
	// WHERE `players`.`status`<>'deleted' AND `players_profile`.`playerid`=`players`.`id`
	// ORDER BY `players`.`teamid`, `players`.`name`;
	$query = 'SELECT ';
	// the needed values
	$query .= ' `players`.`id`,`players`.`teamid`,`players`.`name` AS `player_name`,';
	// team name only available if player belongs to a team
	if ($search_teamless)
	{
		$query .= sqlSafeStringQuotes('(teamless)') . ' AS `team_name`';
	} elseif ($search_team)
	{
		$query .= '(SELECT `teams`.`name` FROM `teams` WHERE `teams`.`id`=`players`.`teamid` LIMIT 1) AS `team_name`';
	} else
	{
		$query .= 'IF (`players`.`teamid`<>' . sqlSafeStringQuotes('0') . ',(SELECT `teams`.`name` FROM `teams` WHERE `teams`.`id`=`players`.`teamid` LIMIT 1),';
		$query .= sqlSafeStringQuotes('(teamless)') . ') AS `team_name`';
	}
	// player first joined date
	$query .= ',`players_profile`.`joined`';
	// tables involved
	$query .= ' FROM `players`,`players_profile`';
	// do not display deleted players during maintenance
	$query .= ' WHERE `players`.`status`<>' . sqlSafeStringQuotes('deleted');
	if ($search_teamless)
	{
		$query .= ' AND `players`.`teamid`=' . sqlSafeStringQuotes('0');
	} elseif ($search_team)
	{
		$query .= ' AND `players`.`teamid`<>' . sqlSafeStringQuotes('0');
	}
	if (isset($_GET['search_string']) && !(strcmp($search_string, '') === 0))
	{
		$query .= ' AND `players`.`name` LIKE ' . sqlSafeStringQuotes($search_string);
	}
	// the profile id of the player must match the actual player id (profile must belong to the same player)
	$query .= ' AND `players_profile`.`playerid`=`players`.`id`';
	// sort the result
	if ($search_player_sort)
	{
		$query .= ' ORDER BY `players`.`name`, `team_name`';
	} elseif ($search_joined_sort)
	{
		$query .= ' ORDER BY `players_profile`.`joined`, `team_name`, `players`.`name`';
	} else
	{
		$query .= ' ORDER BY `team_name`, `players`.`name`';
	}
	if ($result = @$site->execute_query($site->db_used_name(), 'players, teams', $query, $connection))
	{
		$rows = (int) mysql_num_rows($result);
		if ($rows === 0)
		{
			echo '<p>There are no ';
			if (isset($_GET['search']))
			{
				echo 'such ';
			}
			echo 'players in the database.</p>';
			$site->dieAndEndPageNoBox();
		}
		
		// unfortunately as the list is sorted by teamid and name we need to keep track of teamid changes
		$teamid = (int) -1;
		echo '<table id="table_players_overview" class="big">' . "\n";
		echo '<caption>Active Players</caption>' . "\n";
		echo '<tr>' . "\n";
		echo '	<th>Name</th>' . "\n";
		echo '	<th>Team</th>' . "\n";
		echo '	<th>Joined</th>' . "\n";
		echo '</tr>' . "\n\n";
		
		while($row = mysql_fetch_array($result))
		{
			echo '<tr class="players_overview">' . "\n";
			echo '	<td class="players_overview_name">';
			echo '<a href="./?profile=' . htmlentities($row['id']) . '">';
			echo $row['player_name'];
			echo '</a></td>' . "\n";
			echo '	<td class="players_overview_team">';
			if ((int) $row['teamid'] === 0)
			{
				echo '(teamless)';
			} else
			{
				echo '<a href="../Teams/?profile=' . $row['teamid'] . '">';
				echo $row['team_name'];
				echo '</a>';
			}
			echo '</td>'. "\n";
			echo '	<td class="players_overview_joined">';
			echo htmlent($row['joined']);
			echo '</td>' . "\n";
			echo '</tr>' . "\n";
		}
		mysql_free_result($result);
		
		// no more players left to display
		echo '</table>' . "\n";
	}
?>
</div>
</body>
</html>
