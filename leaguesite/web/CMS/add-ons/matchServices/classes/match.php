<?php
	class match
	{
		function __construct()
		{
			
		}
		
		function displayMatches($offset=0, $numRows=200)
		{
			global $user;
			global $tmpl;
			global $db;
			
			$tmpl->setTemplate('MatchServicesMatchList');
			
			
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
			require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/classes/team.php');
			$teamClass = new team();
			
			// collect data to display from query result in database
			$tmplMatchData = array(array());;
			while ($row = $db->fetchRow($matchData))
			{
				$id = (int) $row['id'];
				$tmplMatchData[$id]['id'] = $row['id'];
				$tmplMatchData[$id]['dateAndTime'] = $row['timestamp'];
				$tmplMatchData[$id]['team1Name'] = $teamClass->getName((int) $row['team1ID']);
				$tmplMatchData[$id]['team2Name'] = $teamClass->getName((int) $row['team2ID']);
				$tmplMatchData[$id]['team1ID'] = $row['team1ID'];
				$tmplMatchData[$id]['team2ID'] = $row['team2ID'];
				$tmplMatchData[$id]['team1Score'] = $row['team1_points'];
				$tmplMatchData[$id]['team2Score'] = $row['team2_points'];
				$tmplMatchData[$id]['lastModUserName'] = $user->getName((int) $row['userid']);
			}
			unset($tmplMatchData[0]);
			unset($id);
			
			
			// we are done here :)
			$tmpl->assign('matchData', $tmplMatchData);
			
			return;
		}
		
		function displayMatch()
		{
			global $tmpl;
			global $db;
			
/*
			$query = 'SELECT * FROM `matches`';
			$db->SQL($query);
*/
			
			die();
		}
		
		function enter($teamID1, $teamID2, $team1Score, $team2score)
		{
			
		}
	}
?>
