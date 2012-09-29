<?php
	namespace matchServices;
	
	class matchServices extends \db
	{
		public function enterMatch($matchData, $matchDataFormat=1)
		{
			// enter a match
			// returns true if operation completed successfully
			// $matchData must be an associative array, its content structure is determined by $matchDataFormat
			// NOTE: Ignores auth, make sure to check permission before calling this function
			
			// Specification:
			// $matchDataFormat=1;
			// $matchData = array('timestamp' => 'YYYY:MM:DD HH:MM:SS', 'duration' => (int) 30,'teamID1' => (int) 1, 'teamID2' => (int) 2, 'team1Score' => (int) 3, 'team2Score' => (int) 4);
			// where teamID1 and teamID2 are unique id's from team table and both id's must not be identical
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
			if (!isset($matchData['timestamp'] || !isset($matchData['duration']) || !isset($matchData['teamID1'])
				|| !isset($matchData['teamID2']) || !isset($matchData['team1Score']) || !isset($matchData['team2Score'])))
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
			$this->SQL('LOCK TABLES `matches` WRITE');
			$team1TotalScore = getTeamScore($matchData['teamID1'], $matchData['timestamp']);
			$team2TotalScore = getTeamScore($matchData['teamID2'], $matchData['timestamp']);
			
			
			$this->SQL('UNLOCK TABLES');
			return true;
		}
		
		
		private function getTeamScore($teamID, $timestamp='9999:99:99 99:99:99')
		{
			$defaultScore = 1200;
			
			
			$query = $this->prepare('SELECT * FROM `matches` WHERE `timestamp` < \':timestamp\' LIMIT 1');
			if (!$this->execute($query, array(':timestamp' => array($timestamp, PDO::PARAM_STR)))
			{
				return 'E_TEAM_SCORE_QUERY_FAILURE';
			}
			
			$row = $this->fetchRow($query);
			$this->free($query);
			
			if (!$row)
			{
				// no match played
				return $defaultScore;
			}
			
			if ((int) $row['team1ID'] === $teamID)
			{
				
			} else
			{
				
			}
			
			// 1200 is default value
			return $defaultScore;
		}
	}
?>
