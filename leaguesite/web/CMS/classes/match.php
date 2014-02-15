<?php
	// handle match related data
	
	// not loaded by default!
	class match
	{
		public function __construct()
		{
			
		}
		
		// find out number of matches for specified team
		// input: teamid (integer), type (string): 'all' | 'won' | 'draw' | 'lost'
		// output: count of matches (integer) played for teamid
		public static function getMatchCountForTeamId($teamid, $type = 'all')
		{
			global $db;
			
			switch($type)
			{
				case 'won':
					$query = $db->prepare('SELECT COUNT(*) AS `num_matches` FROM `matches` WHERE (`team1_id`=:teamid AND `team1_points`>`team2_points`) OR (`team2_id`=:teamid AND `team1_points`<`team2_points`)');
					break;
				case 'draw':
					$query = $db->prepare('SELECT COUNT(*) AS `num_matches` FROM `matches` WHERE `team1_id`=:teamid OR `team2_id`=:teamid AND `team1_points`=`team2_points`');
					break;
				case 'lost':
					$query = $db->prepare('SELECT COUNT(*) AS `num_matches` FROM `matches` WHERE (`team1_id`=:teamid AND `team1_points`<`team2_points`) OR (`team2_id`=:teamid AND `team1_points`>`team2_points`)');
					break;
				default:
					$query = $db->prepare('SELECT COUNT(*) AS `num_matches` FROM `matches` WHERE `team1_id`=:teamid OR `team2_id`=:teamid');
			}
			if ($db->execute($query, array(':teamid' => array($teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$matchCount = $row['num_matches'];
				$db->free($query);
				
				return (int) $matchCount;
			}
			
			return false;
		}
	}
?>
