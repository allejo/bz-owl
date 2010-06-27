<?php
	// set the date and time
	date_default_timezone_set('Europe/Berlin');
	
	// find out if maintenance is needed (compare old date in plain text file)
	$today = date('d.m.Y');
	$file = (dirname(__FILE__)) . '/maintenance.txt';
	
	// siteinfo class used all the time
	require_once ((dirname(dirname(__FILE__)) . '/siteinfo.php'));
	$site = new siteinfo();
	
	if (!(isset($connection)))
	{
		// database connection is used during maintenance
		$connection = $site->connect_to_db();
	}
	
	
	// find out when last maintenance happened
	$last_maintenance = '00.00.0000';
	$query = 'SELECT `last_maintenance` FROM `misc_data` LIMIT 1';
	// execute query
	if (!($result = @$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('MAINTENANCE ERROR: Can not get last maintenance data from database.');
	}
	
	if ((int) mysql_num_rows($result) === 0)
	{
		mysql_free_result($result);
		// first maintenance run in history
		$query = 'INSERT INTO `misc_data` (`last_maintenance`) VALUES (' . sqlSafeStringQuotes($today) . ')';
		// execute query
		if (!($result = @$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('MAINTENANCE ERROR: Can not get last maintenance data from database.');
		}
	} else
	{
		// read out the date of last maintenance
		while ($row = mysql_fetch_array($result))
		{
			$last_maintenance = $row['last_maintenance'];
		}
		mysql_free_result($result);
	}
	
	// was maintenance done today?
	if (strcasecmp($last_maintenance, $today) == 0)
	{
		// nothing to do
		// stop silently
		die();
	}
	
	// do the maintenance
	$maint = new maintenance();
	$maint->do_maintenance($site, $connection);
	
	// set up a class to have a unique namespace
	class maintenance
	{
		function cleanup_teams($site, $connection, $two_months_in_past)
		{
			// teams cleanup
			
			$query = 'SELECT `teamid`, `member_count`, `deleted` FROM `teams_overview`';
			$query .= ' WHERE `deleted`<>' . "'" . sqlSafeString('2') . "'";
			// execute query
			if (!($result = @$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('MAINTENANCE ERROR: getting list of teams with deleted not equal 2 (2 means deleted team) failed.');
			}
			
			// 6 months long inactive teams will be deleted during maintenance
			// inactive is defined as the team did not match 6 months
			$six_months_in_past = strtotime('-6 months');
			$six_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $six_months_in_past);			
			
			// walk through results
			while($row = mysql_fetch_array($result))
			{
				// the id of current team investigated
				$curTeam = $row['teamid'];
				
				// is the team new?
				$curTeamNew = ((int) $row['deleted'] === 0);
				
				$query = 'SELECT `timestamp` FROM `matches`';
				if ((int) $row['deleted'] === 3)
				{
					// re-activated team from admins will only last 2 months without matching
					$query .= ' WHERE `timestamp`>' . "'" . sqlSafeString($two_months_in_past) . "'";
				} else
				{
					// team marked as active (deleted === 1) has 6 months to match before being deleted
					$query .= ' WHERE `timestamp`>' . "'" . sqlSafeString($six_months_in_past) . "'";
				}
				$query .= ' AND (`team1_teamid`=' . "'" . sqlSafeString($curTeam) . "'";
				$query .= ' OR `team2_teamid`=' . "'" . sqlSafeString($curTeam) . "'" . ')';
				// only one match is sufficient to be considered active
				$query .= ' LIMIT 1';
				// execute query
				if (!($result_matches = @$site->execute_query($site->db_used_name(), 'matches', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('MAINTENANCE ERROR: getting list of recent matches from teams failed.');
				}
				
				// mark the team as inactive by default
				$cur_team_active = false;
				// walk through results
				while($row_matches = mysql_fetch_array($result_matches))
				{
					// now we know the current team is active
					$cur_team_active = true;
				}
				mysql_free_result($result_matches);
				
				if (((int) $row['member_count']) === 0)
				{
					// no members in team implies team inactive
					$cur_team_active = false;
				}
				
				// if team not active and is not new, delete it for real (do not mark as deleted but actually do it!)
				if (!$cur_team_active && $curTeamNew)
				{
					// delete (for real) the new team
					$query = 'DELETE FROM `teams` WHERE `id`=' . "'" . ($teamid) . "'";
					// execute query, ignore result
					@$site->execute_query($site->db_used_name(), 'teams', $query, $connection);
					$query = 'DELETE FROM `teams_overview` WHERE `teamid`=' . "'" . ($teamid) . "'";
					// execute query, ignore result
					@$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection);
					$query = 'DELETE FROM `teams_permissions` WHERE `teamid`=' . "'" . ($teamid) . "'";
					// execute query, ignore result
					@$site->execute_query($site->db_used_name(), 'teams_permissions', $query, $connection);
					$query = 'DELETE FROM `teams_profile` WHERE `teamid`=' . "'" . ($teamid) . "'";
					// execute query, ignore result
					@$site->execute_query($site->db_used_name(), 'teams_profile', $query, $connection);						
				}
				
				// if team not active but is not new, mark it as deleted
				if (!$cur_team_active && !$curTeamNew)
				{
					// delete team data:
					
					// delete description
					$query = 'UPDATE `teams_profile` SET description=' . "'" . "'";
					$query .= ', logo_url=' . "'" . "'";
					$query .= ' WHERE `teamid`=' . "'" . sqlSafeString($curTeam) . "'";
					// only one player needs to be updated
					$query .= ' LIMIT 1';
					// execute query, ignore result
					@$site->execute_query($site->db_used_name(), 'teams_profile', $query, $connection);	
					
					// mark the team as deleted
					$query = 'UPDATE `teams_overview` SET deleted=' . "'" . sqlSafeString('2') . "'";
					$query .= ', member_count=' . "'" . sqlSafeString('0') . "'";
					$query .= ' WHERE `teamid`=' . "'" . sqlSafeString($curTeam) . "'";
					// only one team with that id in database (id is unique identifier)
					$query .= ' LIMIT 1';
					// execute query, ignoring result
					@$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection);
					
					// mark who was where, to easily restore an unwanted team deletion
					$query = 'UPDATE `players` SET `last_teamid`=' . "'" . sqlSafeString($curTeam) . "'";
					$query .= ', `teamid`=' . "'" . sqlSafeString('0') . "'";
					$query .= ' WHERE `teamid`=' . "'" . sqlSafeString($curTeam) . "'";
					if (!($result_update = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('');
					}
				}
			}
		}
		
		function do_maintenance($site, $connection)
		{
			global $today;
			echo '<p>Performing maintenance...</p>';
			
			// flag stuff
			$query = 'SELECT `id` FROM `countries` WHERE `id`=' . sqlSafeStringQuotes('1');
			if (!($result = @$site->execute_query($site->db_used_name(), 'countries', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPageNoBox('Could not if country with id 0 does exist in database');
			}
			$insert_entry = false;
			if (!(mysql_num_rows($result) > 0))
			{
				$insert_entry = true;
			}
			mysql_free_result($result);
			
			if ($insert_entry)
			{
				$query = 'INSERT INTO `countries` (`id`,`name`, `flagfile`) VALUES (';
				$query .= sqlSafeStringQuotes('1') . ',';
				$query .= sqlSafeStringQuotes('here be dragons') . ',';
				$query .= sqlSafeStringQuotes('') . ')';
				if (!($result = @$site->execute_query($site->db_used_name(), 'countries', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPageNoBox('Could not insert reserved country with name ' . sqlSafeStringQuotes('here be dragons') . ' into database');
				}
			}
			
			$dir = dirname(dirname(dirname(__FILE__))) . '/Flags';
			$countries = array();
			if ($handle = opendir($dir))
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file != '.' && $file != '..' $file != '.svn' && $file != '.DS_Store')
					{
						$countries[] = $file;
					}
				}
				closedir($handle);
			}
			foreach($countries as &$one_country)
			{
				$flag_name_stripped = str_replace('Flag_of_', '', $one_country);
				$flag_name_stripped = str_replace('.png', '', $flag_name_stripped);
				$flag_name_stripped = str_replace('_', '', $one_country);
				$query = 'SELECT `flagfile` FROM `countries` WHERE `name`=' . sqlSafeStringQuotes($flag_name_stripped);
				if (!($result = @$site->execute_query($site->db_used_name(), 'countries', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPageNoBox('Could not check if flag ' . sqlSafeStringQuotes($one_country) . ' does exist in database');
				}
				$update_country = false;
				$insert_entry = false;
				if (!(mysql_num_rows($result) > 0))
				{
					$update_country = true;
					$insert_entry = true;
				}
				if (!$update_country)
				{
					while ($row = mysql_fetch_array($result))
					{
						if (!(strcmp($row['flagfile'], $one_country) === 0))
						{
							$update_country = true;
						}
					}
				}
				mysql_free_result($result);
				
				if ($update_country)
				{
					if ($insert_entry)
					{
						$query = 'INSERT INTO `countries` (`name`, `flagfile`) VALUES (';
						$query .= sqlSafeStringQuotes($flag_name_stripped) . ',';
						$query .= sqlSafeStringQuotes($one_country) . ')';
					} else
					{
						$query = 'UPDATE `countries` SET `flagfile`=' . sqlSafeStringQuotes($one_country);
						$query .= 'WHERE `name`=' . sqlSafeStringQuotes($flag_name_stripped);
					}
					
					// do the changes
					if (!($result = @$site->execute_query($site->db_used_name(), 'countries', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPageNoBox('Could update or insert country entry for ' . sqlSafeStringQuotes($one_country) . ' in database.');
					}
				}
			}
			
			
			// date of 2 months in past will help during maintenance
			$two_months_in_past = strtotime('-2 months');
			$two_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $two_months_in_past);
			
			// clean teams first
			// if team gets deleted players will be maintained later
			$this->cleanup_teams($site, $connection, $two_months_in_past);
			
			
			// maintain players now
			
			// get player id of teamless players that have not been logged-in in the last 2 months
			$query = 'SELECT `playerid` FROM `players`, `players_profile`';
			$query .= ' WHERE `players`.`teamid`=' . sqlSafeStringQuotes('0');
			$query .= 'AND `players`.`suspended`=' . sqlSafeStringQuotes('0');
			$query .= ' AND `players_profile`.`playerid`=`players`.`id`';
			$query .= 'AND `players_profile`.`last_visit`<' . sqlSafeStringQuotes($two_months_in_past);
			
			// execute query
			if (!($result = @$site->execute_query($site->db_used_name(), 'players, players_profile', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('MAINTENANCE ERROR: getting list of 2 months long inactive players failed.');
			}
			
			// store inactive players in an array
			$inactive_players = Array();
			while($row = mysql_fetch_array($result))
			{
				$inactive_players[] = $row['playerid'];
			}
			mysql_free_result($result);
			
			// handle each inactive player seperately
			foreach ($inactive_players as $one_inactive_player)
			{
				// delete account data:
				
				// user entered comments
				$query = 'UPDATE `players_profile` SET user_comment=' . "'" . "'";
				$query .= ', logo_url=' . "'" . "'";
				$query .= ' WHERE `playerid`=' . "'" . sqlSafeString($one_inactive_player) . "'";
				// only one player needs to be updated
				$query .= ' LIMIT 1';
				// execute query, ignore result
				@$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection);
				
				// visits log (ip-addresses and host data)
				$query = 'DELETE FROM `visits` WHERE `playerid`=' . "'" . sqlSafeString($one_inactive_player) . "'";
				@$site->execute_query($site->db_used_name(), 'visits', $query, $connection);
				
				// private messages connection
				
				// get msgid first!
				$query = 'SELECT `msgid` FROM `messages_users_connection` WHERE `playerid`=' . "'" . sqlSafeString($one_inactive_player) . "'";
				// execute query
				if (!($result = @$site->execute_query($site->db_used_name(), 'players, players_profile', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('MAINTENANCE ERROR: getting private msgid list of inactive players failed.');
				}
				
				$msg_list = Array();
				while($row = mysql_fetch_array($result))
				{
					$msg_list[] = $row['msgid'];
				}
				mysql_free_result($result);				
				
				// now delete the connection to mailbox
				$query = 'DELETE FROM `messages_users_connection` WHERE `playerid`=' . "'" . sqlSafeString($one_inactive_player) . "'";
				@$site->execute_query($site->db_used_name(), 'messages_users_connection', $query, $connection);				
				
				// delete private messages itself, in case no one else has the message in mailbox
				foreach ($msg_list as $msgid)
				{
					$query = 'SELECT `msgid` FROM `messages_users_connection` WHERE `msgid`=' . "'" . sqlSafeString($msgid) . "'";
					$query .= ' AND `playerid`<>' . "'" . sqlSafeString($one_inactive_player) . "'";
					// we only need to know whether there is more than zero rows the result of query
					$query .= ' LIMIT 1';
					if (!($result = @$site->execute_query($site->db_used_name(), 'messages_users_connection', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('MAINTENANCE ERROR: finding out whether actual private messages can be deleted failed.');
					}
					$rows = (int) mysql_num_rows($result);
					mysql_free_result($result);
					
					if ($rows < ((int) 1))
					{
						// delete actual message
						$query = 'DELETE FROM `messages_storage` WHERE `id`=' . "'" . sqlSafeString($msgid) . "'";
						@$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection);								
					}
				}
				unset($msgid);
				
				// mark account as deleted
				$query = 'UPDATE `players` SET `suspended`=' . "'" . sqlSafeString('1') . "'";
				$query .= ' WHERE `id`=' . "'" . sqlSafeString($one_inactive_player) . "'";
				// and again only one player needs to be updated
				$query .= ' LIMIT 1';
				@$site->execute_query($site->db_used_name(), 'players', $query, $connection);
				
				// FIXME: if user marked deleted check if he was leader of a team
				$query = 'SELECT `id` FROM `teams` WHERE `leader_playerid`=' . "'" . sqlSafeString($one_inactive_player) . "'";
				// only one player was changed and thus only one team at maximum needs to be updated
				$query .= ' LIMIT 1';
				// execute query
				if (!($result = @$site->execute_query($site->db_used_name(), 'teams', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('MAINTENANCE ERROR: finding out if inactive player was leader of a team failed.');
				}
				
				// walk through results
				$member_count_modified = false;
				while($row = mysql_fetch_array($result))
				{
					// set the leader to 0 (no player)
					$query = 'Update `teams` SET `leader_playerid`=' . "'" . sqlSafeString('0') . "'";
					$query .= ' WHERE `leader_playerid`=' . "'" . sqlSafeString($one_inactive_player) . "'";
					// execute query, ignore result
					@$site->execute_query($site->db_used_name(), 'teams', $query, $connection);
					
					// update member count of team
					$member_count_modified = true;
					$teamid = $row['id'];
					$query = 'UPDATE `teams_overview` SET `member_count`=(SELECT COUNT(*) FROM `players` WHERE `players`.`teamid`=';
					$query .= "'" . sqlSafeString($teamid) . "'" . ') WHERE `teamid`=';
					$query .= "'" . sqlSafeString($teamid) . "'";
					// execute query, ignore result
					@$site->execute_query($site->db_used_name(), 'teams', $query, $connection);
				}
				mysql_free_result($result);
				
				if ($member_count_modified)
				{
					// during next maintenance the team that has no leader would be deleted
					// however the time between maintenance can be different
					// and the intermediate state could confuse users
					// thus force the team maintenance again
					$this->cleanup_teams($site, $connection, $two_months_in_past);
				}
			}
			echo '<p>Maintenance performed successfully.</p>';
			
			// update maintenance date
			$query = 'UPDATE `misc_data` SET `last_maintenance`=' . sqlSafeStringQuotes($today);
			// execute query
			if (!($result = @$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('MAINTENANCE ERROR: Can not get last maintenance data from database.');
			}
			$site->dieAndEndPage();
		}
	}
?>