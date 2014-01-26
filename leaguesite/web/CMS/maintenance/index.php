<?php
	// siteinfo class used all the time
	if (!(isset($site)))
	{
		require_once ((dirname(dirname(__FILE__)) . '/siteinfo.php'));
		$site = new siteinfo();
	}
	
	// set the date and time
	date_default_timezone_set($site->used_timezone());
	
	// find out if maintenance is needed (compare old date in plain text file)
	$today = date('Y-m-d');
	$file = (dirname(__FILE__)) . '/maintenance.txt';
	
	if (!(isset($connection)))
	{
		// database connection is used during maintenance
		$connection = $site->connect_to_db();
	}
	
	$maintenanceNeeded = true;
	
	// find out when last maintenance happened
	$last_maintenance = '0000-00-00';
	$query = 'SELECT `last_maintenance` FROM `misc_data` LIMIT 1';
	// execute query
	if (!($result = @$site->execute_query('misc_data', $query, $connection)))
	{
		$site->dieAndEndPage('MAINTENANCE ERROR: Can not get last maintenance data from database.');
	}
	
	// read out the date of last maintenance
	while ($row = mysql_fetch_array($result))
	{
		$last_maintenance = $row['last_maintenance'];
	}
	mysql_free_result($result);
	
	// was maintenance done today?
	if (strcasecmp($last_maintenance, $today) == 0)
	{
		// update team activity of teams in array $teams (defined in file that includes this file)
		if (isset($teams))
		{
			update_activity($teams);
		}
		
		// force update activity stats
		update_activity();
		
		// nothing else to do
		// stop silently
		$maintenanceNeeded = false;
	}
	
	if ($maintenanceNeeded)
	{
		// do the maintenance
		$maint = new maintenance_old();
		$maint->do_maintenance($site, $connection);
		// call the new maintenance add-on directly to do the job
		require_once(dirname(dirname(__FILE__)) . '/add-ons/maintenance/maintenance.php');
		$maintenance = new maintenance();
		// done
	}
	
	function update_activity($teamid=false)
	{
		global $maintenance;
		
		
		if (!isset($maintenance))
		{
			require_once(dirname(dirname(__FILE__)) . '/add-ons/maintenance/maintenance.php');
			$maintenance = new maintenance();
		}
		
		// old code does call this function directly with a teamid
		// just pass the call to the new add-on
		$maintenance->updateTeamActivity();
	}
	
	// set up a class to have a unique namespace
	class maintenance_old
	{
		function cleanup_teams($two_months_in_past)
		{
			global $settings;
			global $site;
			global $connection;
			
			
			// teams cleanup
			if (!$settings->maintain_teams_not_matching_anymore())
			{
				// in settings it was specified not to maintain inactive teams
				echo '<p>Skipped maintaining inactive teams (by config option)!</p>';
				return;
			}
			
			
			$query = 'SELECT `teamid`, `member_count`, `deleted` FROM `teams_overview`';
			$query .= ' WHERE `deleted`<>' . sqlSafeStringQuotes('2');
			// execute query
			if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
			{
				$site->dieAndEndPage('MAINTENANCE ERROR: getting list of teams with deleted not equal 2 (2 means deleted team) failed.');
			}
			
			// 6 months long inactive teams will be deleted during maintenance
			// inactive is defined as the team did not match 6 months
			$six_months_in_past = strtotime('-6 months');
			$six_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $six_months_in_past);
			
			// walk through results
			while ($row = mysql_fetch_array($result))
			{
				// the id of current team investigated
				$curTeam = $row['teamid'];
				
				// is the team new?
				$curTeamNew = ((int) $row['deleted'] === 0);
				
				$query = 'SELECT `timestamp` FROM `matches`';
				if ((int) $row['deleted'] === 3)
				{
					// re-activated team from admins will only last 2 months without matching
					$query .= ' WHERE `timestamp`>' . sqlSafeStringQuotes($two_months_in_past);
				} else
				{
					// team marked as active (deleted === 1) has 6 months to match before being deleted
					$query .= ' WHERE `timestamp`>' . sqlSafeStringQuotes($six_months_in_past);
				}
				$query .= ' AND (`team1ID`=' . sqlSafeStringQuotes($curTeam);
				$query .= ' OR `team2ID`=' . sqlSafeStringQuotes($curTeam) . ')';
				// only one match is sufficient to be considered active
				$query .= ' LIMIT 1';
				// execute query
				if (!($result_matches = @$site->execute_query('matches', $query, $connection)))
				{
					$site->dieAndEndPage('MAINTENANCE ERROR: getting list of recent matches from teams failed.');
				}
				
				// mark the team as inactive by default
				$cur_team_active = false;
				// walk through results
				while ($row_matches = mysql_fetch_array($result_matches))
				{
					// now we know the current team is active
					$cur_team_active = true;
				}
				mysql_free_result($result_matches);
				
				if (!$cur_team_active && !$settings->maintain_teams_not_matching_anymore_users_still_loggin_in())
				{
					$query = ('SELECT `users`.`id` AS `active_player`'
							  . ' FROM `users`,`users_profile`'
							  . ' WHERE `teamid` = ' . sqlSafeStringQuotes($curTeam)
							  . ' AND `users_profile`.`last_login`>' . sqlSafeStringQuotes($six_months_in_past)
							  . ' AND `users`.`id`=`users_profile`.`userid`'
							  // only 1 active player is enough not to deactivate the team
							  . ' LIMIT 1');
					// execute query
					if (!($result_active_users = @$site->execute_query('matches', $query, $connection)))
					{
						$site->dieAndEndPage('MAINTENANCE ERROR: getting list of recent logged in player from team'
											 . sqlSafeStringQuotes($curTeam)
											 . ' failed.');
					}
					if ((int) mysql_num_rows($result_active_users) > 0)
					{
						// at least one player logged in during the last 6 months
						// in settings it was specified to count the current team as active then
						$cur_team_active = true;
					}
					mysql_free_result($result_active_users);
				}
				
				if (((int) $row['member_count']) === 0)
				{
					// no members in team implies team inactive
					$cur_team_active = false;
				}
				
				// if team not active and is not new, delete it for real (do not mark as deleted but actually do it!)
				if (!$cur_team_active && $curTeamNew)
				{
					// set users belonging to the deleted team to teamless
					$query = 'UPDATE `users` SET `last_teamid`=' . sqlSafeStringQuotes($curTeam);
					$query .= ', `teamid`=' . sqlSafeStringQuotes('0');
					$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($curTeam);
					@$site->execute_query('users', $query, $connection);
					
					// delete (for real) the new team
					$query = 'DELETE FROM `teams` WHERE `id`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams', $query, $connection);
					$query = 'DELETE FROM `teams_overview` WHERE `teamid`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams_overview', $query, $connection);
					$query = 'DELETE FROM `teams_permissions` WHERE `teamid`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams_permissions', $query, $connection);
					$query = 'DELETE FROM `teams_profile` WHERE `teamid`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams_profile', $query, $connection);
				}
				
				// if team not active but is not new, mark it as deleted
				if (!$cur_team_active && !$curTeamNew)
				{
					// delete team data:
					
					// delete description
					$query = 'UPDATE `teams_profile` SET description=' . "'" . "'";
					$query .= ', logo_url=' . "'" . "'";
					$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($curTeam);
					// only one player needs to be updated
					$query .= ' LIMIT 1';
					// execute query, ignore result
					@$site->execute_query('teams_profile', $query, $connection);	
					
					// mark the team as deleted
					$query = 'UPDATE `teams_overview` SET deleted=' . sqlSafeStringQuotes('2');
					$query .= ', member_count=' . sqlSafeStringQuotes('0');
					$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($curTeam);
					// only one team with that id in database (id is unique identifier)
					$query .= ' LIMIT 1';
					// execute query, ignoring result
					@$site->execute_query('teams_overview', $query, $connection);
					
					// mark who was where, to easily restore an unwanted team deletion
					$query = 'UPDATE `users` SET `last_teamid`=' . sqlSafeStringQuotes($curTeam);
					$query .= ', `teamid`=' . sqlSafeStringQuotes('0');
					$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($curTeam);
					if (!($result_update = @$site->execute_query('users', $query, $connection)))
					{
						$site->dieAndEndPage();
					}
				}
			}
		}
		
		function do_maintenance($site, $connection)
		{
			global $settings;
			global $today;
			echo '<p>Performing maintenance...</p>';
			
			$settings = new maintenance_settings();
			
			
			// date of 2 months in past will help during maintenance
			$two_months_in_past = strtotime('-3 months');
			$two_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $two_months_in_past);
			
			// clean teams first
			// if team gets deleted users will be maintained later
			$this->cleanup_teams($two_months_in_past);
			
			
			// maintain users now
			
			// get player id of teamless users that have not been logged-in in the last 2 months
			$query = 'SELECT `userid` FROM `users`, `users_profile`';
			$query .= ' WHERE `users`.`teamid`=' . sqlSafeStringQuotes('0');
			$query .= ' AND `users`.`status`=' . sqlSafeStringQuotes('active');
			$query .= ' AND `users_profile`.`userid`=`users`.`id`';
			$query .= ' AND `users_profile`.`last_login`<' . sqlSafeStringQuotes($two_months_in_past);
			
			// execute query
			if (!($result = @$site->execute_query('users, users_profile', $query, $connection)))
			{
				$site->dieAndEndPage('MAINTENANCE ERROR: getting list of 3 months long inactive users failed.');
			}
			
			// store inactive users in an array
			$inactive_users = Array();
			while($row = mysql_fetch_array($result))
			{
				$inactive_users[] = $row['userid'];
			}
			mysql_free_result($result);
			
			// handle each inactive player seperately
			foreach ($inactive_users as $one_inactive_player)
			{
				// delete account data:
				
				// user entered comments
				$query = 'UPDATE `users_profile` SET `user_comment`=' . "'" . "'";
				$query .= ', logo_url=' . "'" . "'";
				$query .= ' WHERE `userid`=' . sqlSafeStringQuotes($one_inactive_player);
				// only one player needs to be updated
				$query .= ' LIMIT 1';
				// execute query, ignore result
				@$site->execute_query('users_profile', $query, $connection);
				
				// visits log (ip-addresses and host data)
				$query = 'DELETE FROM `visits` WHERE `userid`=' . sqlSafeStringQuotes($one_inactive_player);
				@$site->execute_query('visits', $query, $connection);
				
				// mark account as deleted
				$query = 'UPDATE `users` SET `status`=' . sqlSafeStringQuotes('deleted');
				$query .= ' WHERE `id`=' . sqlSafeStringQuotes($one_inactive_player);
				// and again only one player needs to be updated
				$query .= ' LIMIT 1';
				@$site->execute_query('users', $query, $connection);
				
				// FIXME: if user marked deleted check if he was leader of a team
				$query = 'SELECT `id` FROM `teams` WHERE `leader_userid`=' . sqlSafeStringQuotes($one_inactive_player);
				// only one player was changed and thus only one team at maximum needs to be updated
				$query .= ' LIMIT 1';
				// execute query
				if (!($result = @$site->execute_query('teams', $query, $connection)))
				{
					$site->dieAndEndPage('MAINTENANCE ERROR: finding out if inactive player was leader of a team failed.');
				}
				
				// walk through results
				$member_count_modified = false;
				while ($row = mysql_fetch_array($result))
				{
					// set the leader to 0 (no player)
					$query = 'Update `teams` SET `leader_userid`=' . sqlSafeStringQuotes('0');
					$query .= ' WHERE `leader_userid`=' . sqlSafeStringQuotes($one_inactive_player);
					// execute query, ignore result
					@$site->execute_query('teams', $query, $connection);
					
					// update member count of team
					$member_count_modified = true;
					$teamid = $row['id'];
					$query = 'UPDATE `teams_overview` SET `member_count`=(SELECT COUNT(*) FROM `users` WHERE `users`.`teamid`=';
					$query .= sqlSafeStringQuotes($teamid) . ') WHERE `teamid`=';
					$query .= sqlSafeStringQuotes($teamid);
					// execute query, ignore result
					@$site->execute_query('teams', $query, $connection);
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
						
			// do not update maintenance date, assume the new maintenance add-on does the job
		}
	}
?>
