<?php
	class maintenance
	{
		function __autoload($class_name)
		{
			require_once dirname(dirname(dirname(__FILE__))) . '/classes/' . $class_name . '.php';
		}
		
		function __construct()
		{
			if ($this->isMaintenanceNeeded())
			{
				$this->doMaintaince();
			}
		}
		
		function isMaintenanceNeeded()
		{
			global $db;
			
			
			$today = date('d.m.Y');
			$last_maintenance = '00.00.0000';
			
			// check last time where maintenance was performed
			$query = $db->SQL('SELECT `last_maintenance` FROM `misc_data` LIMIT 1');
			$lastMaintenanceSaved = false;
			while ($row = $db->fetchRow($query))
			{
				$lastMaintenanceSaved = true;
				
				if (isset($row['last_maintenance']))
				{
					$last_maintenance = $row['last_maintenance'];
				}
			}
			$db->free($query);
			
			// save new maintenance timestamp
			if ($lastMaintenanceSaved)
			{
				$query = $db->prepare('UPDATE `misc_data` SET `last_maintenance`=?');
				$db->execute($query, $today);
				$db->free($query);
			} else
			{
				$query = $db->prepare('INSERT INTO `misc_data` (`last_maintenance`) VALUES (?)');
				$db->execute($query, $today);
				$db->free($query);
			}
			
			// daily maintenance
			return (strcasecmp($today, $last_maintenance) !== 0);
		}
		
		function doMaintaince()
		{
			$this->maintainPlayers();
			$this->maintainTeams();
			$this->updateCountries();
			$this->updateTeamActivity();
			echo '<p>Maintenance performed successfully.</p>';
		}
		
		function maintainPlayers()
		{
			
		}
		
		function maintainTeams()
		{
			
		}
		
		
		function maintainPMs($userid)
		{
			global $db;
			
			
			// delete no more used PMs
			$queryInMailboxOfUserid = $db->prepare('SELECT `msgid` FROM `pmSystem.Msg.Users` WHERE `userid`=?');
			$queryInMailboxOfOthers = $db->prepare('SELECT `msgid` FROM `pmSystem.Msg.Users` WHERE `msgid`<>? LIMIT 1');
			$queryDeletePMNoOwner = $db->prepare('DELETE FROM `pmSystem.Msg.Storage` WHERE `id`=? LIMIT 1');
			
			$db->execute($queryInMailboxOfUserid, $userid);
			
			while ($row = $db->fetchRow($queryInMailboxOfUserid))
			{
				$pmInMailboxOfOthers = false;
				
				$db->execute($queryInMailboxOfOthers, $row['msgid']);
				while ($row = $db->fetchRow($queryInMailboxOfOthers))
				{
					$pmInMailboxOfOthers = true;
				}
				$db->free($queryInMailboxOfOthers);
				
				
				if ($pmInMailboxOfOthers === false)
				{
					// delete in global PM storage
					$db->execute($queryDeletePMNoOwner, $row['msgid']);
					$db->free($queryDeletePMNoOwner);
				}
			}
			$db->free($queryInMailboxOfUserid);
			
			// delete any PM in mailbox of $userid
			$queryDeletePMInMailbox = $db->prepare('DELETE FROM `pmSystem.Msg.Users` WHERE `userid`=?');
			$db->execute($queryDeletePMInMailbox, $userid);
			$db->free($queryDeletePMInMailbox);
			
			if ($mailOnlyInMailboxFromUserid)
			{
				$db->free($queryDeletePMNoOwner);
			}
		}
		
		
		function updateTeamActivity($teamid=false)
		{
			global $db;
			
			
			// update team activity
			if ($teamid === false)
			{
				$num_active_teams = 0;
				// find out the number of active teams
				$query = $db->SQL('SELECT COUNT(*) AS `num_teams` FROM `teams_overview` WHERE `deleted`<>2');
				while ($row = $db->fetchRow($query))
				{
					$num_active_teams = (int) $row['num_teams'] -1;
				}
				$db->free($query);
				
				$query = $db->SQL('SELECT `teamid` FROM `teams_overview` WHERE `deleted`<>2');
				$teamid = array();
				while ($row = $db->fetchRow($query))
				{
					$teamid[] = (int) $row['teamid'];
				}
				$db->free($query);
			} else
			{
				$num_active_teams = count($teamid) -1;
			}
			
			$team_activity45 = array();
			$timestamp = strtotime('-45 days');
			$timestamp = strftime('%Y-%m-%d %H:%M:%S', $timestamp);
			$query = $db->prepare('SELECT COUNT(*) as `num_matches` FROM `matches` WHERE `timestamp`>?'
								  . ' AND (`team1_teamid`=? OR `team2_teamid`=?)');
			// find out how many matches each team did play
			for ($i = 0; $i <= $num_active_teams; $i++)
			{
				$db->execute($query, array($timestamp, $teamid[$i], $teamid[$i]));
				while ($row = $db->fetchRow($query))
				{
					$team_activity45[$i] = intval($row['num_matches']);
				}
				$db->free($query);
				
				$team_activity45[$i] = ($team_activity45[$i] / 45);
				// number_format may round but it is not documented (behaviour may change), force doing it
				$team_activity45[$i] = number_format(round($team_activity45[$i], 2), 2, '.', '');
			}
			
			$team_activity90 = array();
			$timestamp = strtotime('-90 days');
			$timestamp = strftime('%Y-%m-%d %H:%M:%S', $timestamp);
			// find out how many matches each team did play
			$query = $db->prepare('SELECT COUNT(*) as `num_matches` FROM `matches` WHERE `timestamp`>?'
								  .' AND (`team1_teamid`=? OR `team2_teamid`=?)');
			for ($i = 0; $i <= $num_active_teams; $i++)
			{
				$db->execute($query, array($timestamp, $teamid[$i], $teamid[$i]));
				while ($row = $db->fetchRow($query))
				{
					$team_activity90[$i] = intval($row['num_matches']);
				}
				$db->free($query);
				
				$team_activity90[$i] = ($team_activity90[$i] / 90);
				// number_format may round but it is not documented (behaviour may change), force doing it
				$team_activity90[$i] = number_format(round($team_activity90[$i], 2), 2, '.', '');
			}
			
			$db->prepare('Update `teams_overview` SET `activity`=? WHERE `teamid`=?');

			for ($i = 0; $i <= $num_active_teams; $i++)
			{
				$team_activity45[$i] .= ' (' . $team_activity90[$i] . ')';
				
				// update activity entry
				$db->execute($query, array($team_activity45[$i], $teamid[$i]));
			}
			
			unset($teamid);
			unset($team_activity45);
			unset($team_activity90);
		}
		
		
		function updateCountries()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `id` FROM `countries` WHERE `id`=? LIMIT 1');
			$db->execute($query, '1');
			$insert_entry = ($db->fetchRow($query) === false);
			$db->free($query);
			
			if ($insert_entry)
			{
				$query = $db->prepare('INSERT INTO `countries` (`id`,`name`, `flagfile`) VALUES (?, ?, ?)');
				$db->execute($query, array('1', 'here be dragons', ''));
			}
			
			$dir = dirname(dirname(dirname(dirname(__FILE__)))) . '/Flags';
			$countries = array();
			if ($handle = opendir($dir))
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file != '.' && $file != '..' && $file != '.svn' && $file != '.DS_Store')
					{
						$countries[] = $file;
					}
				}
				closedir($handle);
			}
			
			$queryFlag = $db->prepare('SELECT `flagfile` FROM `countries` WHERE `name`=?');
			$queryInsertCountry = $db->prepare('INSERT INTO `countries` (`name`, `flagfile`) VALUES (?, ?)');
			$queryUpdateCountry = $db->prepare('UPDATE `countries` SET `flagfile`=? WHERE `name`=?');
			foreach($countries as &$one_country)
			{
				$flag_name_stripped = str_replace('Flag_of_', '', $one_country);
				$flag_name_stripped = str_replace('.png', '', $flag_name_stripped);
				$flag_name_stripped = str_replace('_', ' ', $flag_name_stripped);
				
				// check if flag exists in database
				$db->execute($queryFlag, $flag_name_stripped);
				
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
						$db->execute($queryInsertCountry, array($flag_name_stripped, $one_country));
						$db->free($queryInsertCountry);
					} else
					{
						$db->execute($queryUpdateCountry, array($one_country, $flag_name_stripped));
						$db->free($queryUpdateCountry);
					}
				}
			}
		}
	}
?>