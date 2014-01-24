<?php
	namespace matchServices;
	
	class matchList
	{
		public function __construct($noGUI)
		{
			if (!$noGUI)
			{
				$this->displayMatches();
			}
		}
		
		public function displayMatches($offset=0, $numRows=200)
		{
			global $tmpl;
			global $db;
			
			$tmpl->setTemplate('MatchServices.MatchList');
			
			
			// type specifications
			settype($offset, 'int');
			settype($numRows, 'int');
			
			
			// build match data query
			$query = 'SELECT * FROM `matches` ORDER BY `timestamp` DESC LIMIT ';
			
			$query .= (string) ($offset);
			$query .= ', ';
			$query .= (string) ($numRows);
			
			$matchData = $db->SQL($query);
			
			// log error if something went wrong
			if (!$matchData)
			{
				$db->logError(realpath(__FILE__) . ': displayMatches(): query failed (' . $query . ')');
				die();
			}
			
			
			// FIXME: implement generic class loader somewhere else
			require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/classes/team.php');
			
			// collect data to display from query result in database
			$tmplMatchData = array(array());;
			while ($row = $db->fetchRow($matchData))
			{
				$id = (int) $row['id'];
				$tmplMatchData[$id]['id'] = $row['id'];
				$tmplMatchData[$id]['dateAndTime'] = $row['timestamp'];
				// get team names using team class
				$tmplMatchData[$id]['team1Name'] = (new \team((int) $row['team1_id']))->getName();
				$tmplMatchData[$id]['team2Name'] = (new \team((int) $row['team2_id']))->getName();
				// pass through team id list and scores
				$tmplMatchData[$id]['team1ID'] = $row['team1_id'];
				$tmplMatchData[$id]['team2ID'] = $row['team2_id'];
				$tmplMatchData[$id]['team1Score'] = $row['team1_points'];
				$tmplMatchData[$id]['team2Score'] = $row['team2_points'];
				// lookup user name using user class
				$tmplMatchData[$id]['lastModUserID'] = (int) $row['userid'];
				$tmplMatchData[$id]['lastModUserName'] = (new \user((int) $row['userid']))->getName();
			}
			unset($tmplMatchData[0]);
			unset($id);
			
			
			// we are done here :)
			$tmpl->assign('matchData', $tmplMatchData);
			
			return;
		}
	}
?>
