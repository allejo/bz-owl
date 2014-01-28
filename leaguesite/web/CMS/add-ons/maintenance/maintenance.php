<?php
	class maintenance
	{
//		function __autoload($class_name)
//		{
//			require_once dirname(dirname(dirname(__FILE__))) . '/classes/' . $class_name . '.php';
//		}
		
		function __construct()
		{
			if ($this->isMaintenanceNeeded())
			{
				$this->doMaintaince();
			}
		}
		
		function __destruct()
		{
			$this->unlockTables();
		}
		
		
		function isMaintenanceNeeded()
		{
			global $config;
			global $db;
			
			
			// TODO: remove this loading after legacy maintenance code has been deleted
			if (!isset($db))
			{
				require_once dirname(dirname(dirname(__FILE__))) . '/classes/config.php';
				$config = new config();
				require_once dirname(dirname(dirname(__FILE__))) . '/classes/db.php';
				$db = new database();
			}
			
			$this->lockTable('misc_data');
			
			$today = date('Y-m-d');
			$last_maintenance = '0000-00-0000';
			
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
			$this->unlockTables();
			
			// daily maintenance
			return (strcasecmp($today, $last_maintenance) !== 0);
		}
		
		
		function doMaintaince()
		{
			$this->maintainUsers();
			$this->maintainPMs();
			$this->maintainTeams();
			$this->updateCountries();
			$this->updateTeamActivity();
			echo '<p>Performed maintenance.</p>';
		}
		
		function unlockTables()
		{
			global $db;
			
			// $db won't be set if called from old code
			// TODO: remove check after old code has been removed
			if (isset($db))
			{
				$db->unlockTables();
			}
		}
		
		function lockTable($tableName, $write=true)
		{
			global $db;
			
			$db->lockTable($tableName, $write);
		}
		
		
		function maintainUsers()
		{
			
		}
		
		private function maintainTeams()
		{
			$this->maintainNewTeams();
			$this->maintainActiveTeams();
			$this->maintainReactivatedTeams();
			$this->maintainInactiveTeams();
		}
		
		protected function maintainActiveTeams()
		{
			// 6 months long inactive teams will be marked as deleted during maintenance
			// inactive is defined as the team did not match and no member logged in during last 6 months
			$six_months_in_past = strtotime('-6 months');
			$six_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $six_months_in_past);
			
			$teamIds = team::getActiveTeamIds();
			foreach ($teamIds AS $teamid)
			{
				// check if team matched during last 6 months
				$team = new team($teamid);
				if (($lastMatch = $team->getNewestMatchTimestamp()) && $lastMatch < $six_months_in_past)
				{
					$uids = $team->getUserIds();
					// check if user logged in during last 6 months
					$memberLoggedInRecently = false;
					foreach($uids AS $userid)
					{
						$user = new user($userid);
						if (($lastLogin = $user->getLastLoginTimestampStr()) && $lastLogin > $six_months_in_past)
						{
							$memberLoggedInRecently = true;
						}
					}
					
					// team did not match and none of its users logged in during last 6 months
					if (!$memberLoggedInRecently)
					{
						foreach($uids AS $userid)
						{
							$user = new user($userid);
							$user->removeTeamMembership($teamid);
							$user->update();
						}
						$team->setStatus('deleted');
						$team->update();
					}
				}
			}
		}
		
		protected function maintainInactiveTeams()
		{
			// mark any team that has not matched during last 45 days as inactive
			// set teams other than deleted to active if matched during that timeframe
			
			$forty_five_days_in_past = strtotime('-45 days');
			$forty_five_days_in_past = strftime('%Y-%m-%d %H:%M:%S', $forty_five_days_in_past);
			
			// apply new status to any new, active, reactivated or inactive team
			$teamIds = team::getNewTeamIds();
			$teamIds = array_merge(team::getActiveTeamIds());
			$teamIds = array_merge(team::getReactivatedTeamIds());
			$teamIds = array_merge(team::getInactiveTeamIds());
			foreach ($teamIds AS $teamid)
			{
				$team = new team($teamid);
				$lastMatch = $team->getNewestMatchTimestamp()
				// non deleted team did not match last 45 days -> inactive
				if ($team->getStatus() !== 'inactive' && $lastMatch && $lastMatch < $forty_five_days_in_past)
				{
					$team->setStatus('inactive');
				} else
				{
					// non deleted team did match last 45 days -> active
					if ($team->getStatus() !== 'active' && $lastMatch && $lastMatch > $forty_five_days_in_past)
					{
						$team->setStatus('active');
					}
				}
				$team->update();
			}
		}
		
		protected function maintainNewTeams()
		{
			// permanently delete new teams which did not match in last 3 months
			$three_months_in_past = strtotime('-3 months');
			$three_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $three_months_in_past);
			
			$teamIds = team::getNewTeamIds();
			foreach ($teamIds AS $teamid)
			{
				$team = new team($teamid);
				if (($lastMatch = $team->getNewestMatchTimestamp()) && $lastMatch < $three_months_in_past)
				{
					$uids = $team->getUserIds();
					foreach($uids AS $userid)
					{
						$user = new user($userid);
						$user->removeTeamMembership($teamid);
						$user->update();
					}
					$team->delete();
				}
			}
		}
		
		protected function maintainReactivatedTeams()
		{
			// permanently delete new teams which did not match in last 2 months
			$two_months_in_past = strtotime('-2 months');
			$two_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $two_months_in_past);
			
			$teamIds = team::getNewTeamIds();
			foreach ($teamIds AS $teamid)
			{
				$team = new team($teamid);
				if (($lastMatch = $team->getNewestMatchTimestamp()) && $lastMatch < $two_months_in_past)
				{
					$uids = $team->getUserIds();
					foreach($uids AS $userid)
					{
						$user = new user($userid);
						$user->removeTeamMembership($teamid);
						$user->update();
					}
					$team->delete();
				}
			}
		}
		
		private function maintainPMs($userid)
		{
			global $db;
			
			
			// delete no more used PMs
			$queryInMailboxOfUserid = $db->prepare('SELECT `msgid` FROM `pmsystem_msg_users` WHERE `userid`=?');
			$queryInMailboxOfOthers = $db->prepare('SELECT `msgid` FROM `pmsystem_msg_users` WHERE `msgid`<>? LIMIT 1');
			$queryDeletePMNoOwner = $db->prepare('DELETE FROM `pmsystem_msg_storage` WHERE `id`=? LIMIT 1');
			$queryDeletePMTeamRecipients = $db->prepare('DELETE FROM `pmsystem_msg_recipients_teams` WHERE `msgid`=? LIMIT 1');
			$queryDeletePMUserRecipients = $db->prepare('DELETE FROM `pmsystem_msg_recipients_users` WHERE `msgid`=? LIMIT 1');
			
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
					$db->execute($queryDeletePMTeamRecipients, $row['msgid']);
					$db->free($queryDeletePMTeamRecipients);
					$db->execute($queryDeletePMUserRecipients, $row['msgid']);
					$db->free($queryDeletePMUserRecipients);
				}
			}
			$db->free($queryInMailboxOfUserid);
			
			// delete any PM in mailbox of $userid
			$queryDeletePMInMailbox = $db->prepare('DELETE FROM `pmsystem_msg_users` WHERE `userid`=?');
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
				
				// wrap $teamid into an array if it is no array already
				if (!is_array($teamid))
				{
					$teamid = array($teamid);
				}
			}
			
			// TODO: merge the two activity calculations into a single loop
			$team_activity45 = array();
			$timestamp = strtotime('-45 days');
			$timestamp = strftime('%Y-%m-%d %H:%M:%S', $timestamp);
			
			// find out how many matches each team did play
			$matchCountQuery = $db->prepare('SELECT COUNT(*) as `num_matches` FROM `matches` WHERE `timestamp`>?'
								  . ' AND (`team1ID`=? OR `team2ID`=?)');
			
			// find out how many matches each team did play
			for ($i = 0; $i <= $num_active_teams; $i++)
			{
				$db->execute($matchCountQuery, array($timestamp, $teamid[$i], $teamid[$i]));
				while ($row = $db->fetchRow($matchCountQuery))
				{
					$team_activity45[$i] = intval($row['num_matches']);
				}
				$db->free($matchCountQuery);
				
				$team_activity45[$i] = ($team_activity45[$i] / 45);
				// number_format may round but it is not documented (behaviour may change), force doing it
				$team_activity45[$i] = number_format(round($team_activity45[$i], 2), 2, '.', '');
			}
			
			$team_activity90 = array();
			$timestamp = strtotime('-90 days');
			$timestamp = strftime('%Y-%m-%d %H:%M:%S', $timestamp);
			for ($i = 0; $i <= $num_active_teams; $i++)
			{
				$db->execute($matchCountQuery, array($timestamp, $teamid[$i], $teamid[$i]));
				while ($row = $db->fetchRow($matchCountQuery))
				{
					$team_activity90[$i] = intval($row['num_matches']);
				}
				$db->free($matchCountQuery);
				
				$team_activity90[$i] = ($team_activity90[$i] / 90);
				// number_format may round but it is not documented (behaviour may change), force doing it
				$team_activity90[$i] = number_format(round($team_activity90[$i], 2), 2, '.', '');
			}
			
			
			// set newer activity value
			$query = $db->prepare('UPDATE `teams_overview` SET `activityNew`=? WHERE `teamid`=?');
			for ($i = 0; $i <= $num_active_teams; $i++)
			{
				$db->execute($query, array($team_activity45[$i], $teamid[$i]));
			}
			
			// set older activity value
			$query = $db->prepare('UPDATE `teams_overview` SET `activityOld`=? WHERE `teamid`=?');
			for ($i = 0; $i <= $num_active_teams; $i++)
			{
				$db->execute($query, array($team_activity90[$i], $teamid[$i]));
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
				
				$update_country = false;
				$insert_entry = true;
				
				
				// check if flag exists in database
				$db->execute($queryFlag, $flag_name_stripped);
				while ($row = $db->fetchRow($queryFlag))
				{
					if (!(strcmp($row['flagfile'], $one_country) === 0))
					{
						$update_country = true;
						$insert_entry = false;
					}
				}
				$db->free($queryFlag);
				
				
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
