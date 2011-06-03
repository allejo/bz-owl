<?php
	class teamList
	{
		function __construct()
		{
		
		}
		
		
		function rankScore($score)
		{
			switch ($score)
			{
				case ($score >1900):
					return 'score1900';
					
				case ($score >1800):
					return 'score1800';
					
				case ($score >1700):
					return 'score1700';
					
				case ($score >1600):
					return 'score1600';
					
				case ($score >1500):
					return 'score1500';
					
				case ($score >1400):
					return 'score1400';
					
				case ($score >1300):
					return 'score1300';
					
				case ($score >1200):
					return 'score1200';
					
				case ($score >1100):
					return 'score1100';
					
				case ($score >1000):
					return 'score1000';
					
				case ($score >900):
					return 'score900';
					
				case ($score >800):
					return 'score800';
					
				case ($score <700):
					return 'score700';
			}
		}
		
		
		function showTeams()
		{
			global $tmpl;
			global $user;
			global $db;
			
			
			if (!$tmpl->setTemplate('teamSystemList'))
			{
				$tmpl->noTemplateFound();
				die();
			}
			$tmpl->assign('title', 'Team overview');
			
			// get list of active, inactive and new teams (no deleted teams)
			// TODO: move creation date in db from teams_profile to teams_overview
			$query = $db->prepare('SELECT *'
								  . ', (SELECT `name` FROM `players`'
								  . ' WHERE `players`.`id`=`teams`.`leader_playerid` LIMIT 1)'
								  . ' AS `leader_name`'
								  . ' FROM `teams`, `teams_overview`, `teams_profile`'
								  . ' WHERE `teams`.`id`=`teams_overview`.`teamid`'
								  . ' AND `teams`.`id`=`teams_profile`.`teamid`'
								  . ' AND `teams_overview`.`deleted`<>?'
								  . ' ORDER BY `teams_overview`.`score` DESC'
								  . ', `teams_overview`.`activityNew` DESC'
								  . ' LIMIT 100');
			// value 2 in deleted column means team has been deleted
			// see if query was successful
			if ($db->execute($query, '2') == false)
			{
				// fatal error -> die
				$db->logError('FATAL ERROR: Query in teamList.php (teamSystem add-on) by user '
							  . $user->getID() . ' failed, request URI: ' . $_SERVER['REQUEST_URI']);
				$tmpl->setTemplate('NoPerm');
				$tmpl->display();
				die();
			}
			
			$teams = array();
			while ($row = $db->fetchRow($query))
			{
				// use a temporary array for better readable (but slower) code
				$prepared = array();
				$prepared['profileLink'] = './?profile=' . $row['teamid'];
				$prepared['name'] = $row['name'];
				$prepared['score'] = $row['score'];
				$prepared['scoreClass'] = $this->rankScore($row['score']);
				$prepared['matchSearchLink'] = ('../Matches/?search_string=' . $row['name']
												. '&amp;search_type=team+name'
												. '&amp;search_result_amount=20'
												. '&amp;search=Search');
				$prepared['matchCount'] = $row['num_matches_played'];
				$prepared['memberCount'] = $row['member_count'];
				$prepared['leaderLink'] = '../Players/?profile=' . $row['leader_playerid'];
				$prepared['leaderName'] = $row['leader_name'];
				$prepared['activityNew'] = $row['activityNew'];
				$prepared['activityOld'] = $row['activityOld'];
				$prepared['created'] = $row['created'];
				// append team data
				$teams[] = $prepared;
			}
			$db->free($query);
			unset($prepared);
			
/*
			echo('<pre>');
			print_r($teams);
			echo('</pre>');
*/
			
			$tmpl->assign('teams', $teams);
		}
		
		
		function showTeam($teamid)
		{
			global $tmpl;
			global $user;
			global $db;
			
			
			if (!$tmpl->setTemplate('teamSystemView'))
			{
				$tmpl->noTemplateFound();
				die();
			}
			$tmpl->assign('title', 'Team overview');
			
			$query = $db->prepare('SELECT *'
								  . ', (SELECT `name` FROM `players`'
								  . ' WHERE `players`.`id`=`teams`.`leader_playerid` LIMIT 1)'
								  . ' AS `leader_name`'
								  . ' FROM `teams`, `teams_overview`, `teams_profile`'
								  . ' WHERE `teams`.`id`=`teams_overview`.`teamid`'
								  . ' AND `teams`.`id`=`teams_profile`.`teamid`'
								  . ' AND `teams`.`id`=:teamid'
								  . ' LIMIT 1');
			// see if query was successful
			if ($db->execute($query, array(':teamid' => array(intval($teamid), PDO::PARAM_INT))) == false)
			{
				// fatal error -> die
				$db->logError('FATAL ERROR: Query in teamList.php (teamSystem add-on) by user '
							  . $user->getID() . ' failed, request URI: ' . $_SERVER['REQUEST_URI']);
				$tmpl->setTemplate('NoPerm');
				$tmpl->display();
				die();
			}
			
			$team = array();
			while ($row = $db->fetchRow($query))
			{
/*
				echo('<pre>');
				print_r($row);
				echo('</pre>');
*/
				
				$team['profileLink'] = './?profile=' . $row['id'];
				$team['name'] = $row['name'];
				$team['score'] = $row['score'];
				$team['scoreClass'] = $this->rankScore($row['score']);
				$team['matchSearchLink'] = ('../Matches/?search_string=' . $row['name']
												. '&amp;search_type=team+name'
												. '&amp;search_result_amount=20'
												. '&amp;search=Search');
				$team['matchCount'] = $row['num_matches_played'];
				$team['memberCount'] = $row['member_count'];
				$team['leaderLink'] = '../Players/?profile=' . $row['leader_playerid'];
				$team['leaderName'] = $row['leader_name'];
				$team['activityNew'] = $row['activityNew'];
				$team['activityOld'] = $row['activityOld'];
				$team['created'] = $row['created'];
				
				$team['wins'] = intval($row['num_matches_won']);
				$team['draws'] = intval($row['num_matches_draw']);
				$team['losses'] = intval($row['num_matches_lost']);
				$team['total'] = $team['wins'] + $team['draws'] + $team['losses'];
				
				$tmpl->assign('teamDescription', $row['description']);
			}
			$db->free($query);
			$tmpl->assign('team', $team);

			$tmpl->assign('teamid', intval($teamid));
			$tmpl->assign('showPMButton', $user->getID() > 0 ? true : false);
			
			$matches = array();
			$tmpl->assign('matches', $matches);
		}
	}
?>
