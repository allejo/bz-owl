<?php
	namespace matchServices;
	
	class matchServices
	{
		private $noGUI = false;
		private $version = '1';
		
		public function __construct($title, $path)
		{
			global $config;
			
			
			// fallback to different event version by config
			$version = $config->getValue('matchServices.eventVersion');
			if ($version)
			{
				$this->version = $version;
			}
			unset($version);
			
			// assume this add-on will not be directly called by user if no path is given
			if (strlen($path) === 0)
			{
				$this->noGUI = true;
				
				// ignore GET data if no GUI output is wished
				// code somewhere else will steer matchServoces
				return;
			}
			
			global $tmpl;
			global $user;
			
			
			// anon users may only view matches
			if ($user->getID() < 0)
			{
				require_once dirname(__FILE__) . '/versions/' . $this->version . '/matchList.php';
				new matchList(false);
				return;
			}
			
			// setup permissions for templates
			$tmpl->assign('canEnterMatch', $user->getPermission('allow_add_match'));
			$tmpl->assign('canEditMatch', $user->getPermission('allow_edit_match'));
			$tmpl->assign('canDeleteMatch', $user->getPermission('allow_delete_match'));
			
			if (isset($_GET['enter']))
			{
				require_once dirname(__FILE__) . '/versions/' . $this->version . '/matchEnter.php';
				new matchEnter(false);
			} elseif (isset($_GET['edit']))
			{
				require_once dirname(__FILE__) . '/versions/' . $this->version . '/matchEdit.php';
				new matchEdit(false);
			} elseif (isset($_GET['delete']))
			{
				require_once dirname(__FILE__) . '/versions/' . $this->version . '/matchDelete.php';
				new matchDelete(false);
			} else
			{
				require_once dirname(__FILE__) . '/versions/' . $this->version . '/matchList.php';
				new matchList(false);
			}
		}
		
		public function __destruct()
		{
			global $tmpl;
			
			
			// display template if and only if GUI is wished
			if (!$this->noGUI)
			{
				$tmpl->display();
			}
		}
		
		
		public function getMatchData($offset=0, $numRows=200)
		{
			require_once dirname(__FILE__) . '/versions/' . $this->version . '/matchList.php';
		}
		
		public function enterMatch($matchData, $matchDataFormat=1)
		{
			// enter a match
			// returns true if operation completed successfully
			// $matchData must be an associative array, its content structure is determined by $matchDataFormat
			// NOTE: Ignores auth, make sure to check permission before calling this function
			
			// Specification:
			// $matchDataFormat=1;
			// $matchData = array('timestamp' => 'YYYY:MM:DD HH:MM:SS', 'duration' => (int) 30,'team1ID' => (int) 1, 'team2ID' => (int) 2, 'team1Score' => (int) 3, 'team2Score' => (int) 4);
			// where team1ID and team2ID are unique id's from team table and both id's must not be identical
			// timestamp must be in UTC zone
			
			// call private function that deals with specific matchDataFormat 
			if (function_exists(enterMatch . $matchDataFormat))
			{
				// pass through the result
				return enterMatch . $matchDataFormat($matchData);
			}
			
			
			return false;
		}
		
		private function enterMatch1($matchData)
		{
			// check if all keys are set in $matchData
			if (!isset($matchData['timestamp']) || !isset($matchData['duration']) || !isset($matchData['team1ID'])
				|| !isset($matchData['team2ID']) || !isset($matchData['team1Score']) || !isset($matchData['team2Score']))
			{
				return 'E_REQ_PARAMS_NOT_SET';
			}
			
			
			// a match may not be played in the future -> final result not known 
			$curTime = (int) strtotime('now');
			if ((((int) $matchData['timestamp']) - $curTime) >= 0)
			{
				return 'E_TIMESTAMP_IN_FUTURE';
			}
			
			// a team may not play against itself
			if ($matchData['teamID1'] == $matchData['teamID2'])
			{
				return 'E_IDENTICAL_TEAMS';
			}
			
			// begin critical database part
			$db->SQL('LOCK TABLES `matches` WRITE');
			
			// get the score of both teams at the time we want to insert the new match
			$team1TotalScore = getTeamScore($matchData['team1ID'], $matchData['timestamp']);
			$team2TotalScore = getTeamScore($matchData['team2ID'], $matchData['timestamp']);
			
			
			$db->SQL('UNLOCK TABLES');
			return true;
		}
		
		
		private function getTeamScore($teamID, $timestamp='9999:99:99 99:99:99')
		{
			global $config;
			global $db;
			
			
			// 1200 is default starting value for all teams
			$defaultScore = 1200;
			
			// if a different default value is specified in settings, us that custom one
			$configValue = $config->getValue('cms.addon.matchServices.teamStartingScore');
			$score = isset($configValue) && $configValue ? $configValue : $defaultScore;
			
			$query = $db->prepare('SELECT * FROM `matches` WHERE `timestamp` <= \':timestamp\' AND (`team1_id`=\':teamID\' OR `team2_id`=\':teamID\')LIMIT 1');
			if (!$db->execute($query, array(':timestamp' => array($timestamp, PDO::PARAM_STR), ':teamID' => array($teamID, PDO::PARAM_INT))))
			{
				return 'E_TEAM_SCORE_QUERY_FAILURE';
			}
			
			$row = $db->fetchRow($query);
			$db->free($query);
			
			if (!$row)
			{
				// no match played
				return $score;
			}
			
			if ((int) $row['team1_id'] === $teamID)
			{
				$score = $row['team1_new_score'];
			} else
			{
				$score = $row['team2_new_score'];
			}
			
			return $score;
		}
	}
?>
