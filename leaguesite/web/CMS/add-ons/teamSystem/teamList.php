<?php
	class teamList
	{
		function __construct()
		{
		
		}
		
		
		private function rankScore($score)
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
		
		
		private function addToTeamList(&$row)
		{
				// rename db result fields and assemble some additional informations
				
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
				$prepared['leaderLink'] = '../Players/?profile=' . $row['leader_userid'];
				$prepared['leaderName'] = $row['leader_name'];
				$prepared['activityNew'] = $row['activityNew'];
				$prepared['activityOld'] = $row['activityOld'];
				$prepared['created'] = $row['created'];
				
				// return result data
				return $prepared;
		}
		
		
		public function showTeams()
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
								  . ' WHERE `players`.`id`=`teams`.`leader_userid` LIMIT 1)'
								  . ' AS `leader_name`'
								  . ' FROM `teams`, `teams_overview`, `teams_profile`'
								  . ' WHERE `teams`.`id`=`teams_overview`.`teamid`'
								  . ' AND `teams`.`id`=`teams_profile`.`teamid`'
								  . ' AND `teams_overview`.`deleted`<>?'
								  . ' AND `teams_overview`.`activityNew`<>0'
								  . ' ORDER BY `teams_overview`.`score` DESC'
								  . ', `teams_overview`.`activityNew` DESC');
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
			
			$activeTeams = array();
			while ($row = $db->fetchRow($query))
			{
				// use a temporary array for better readable (but slower) code
				// append team data
				$activeTeams[] = $this->addToTeamList($row);
			}
			$db->free($query);
			
			$query = $db->prepare('SELECT *'
								  . ', (SELECT `name` FROM `players`'
								  . ' WHERE `players`.`id`=`teams`.`leader_userid` LIMIT 1)'
								  . ' AS `leader_name`'
								  . ' FROM `teams`, `teams_overview`, `teams_profile`'
								  . ' WHERE `teams`.`id`=`teams_overview`.`teamid`'
								  . ' AND `teams`.`id`=`teams_profile`.`teamid`'
								  . ' AND `teams_overview`.`deleted`<>?'
								  . ' AND `teams_overview`.`activityNew`=0'
								  . ' ORDER BY `teams_overview`.`score` DESC'
								  . ', `teams_overview`.`activityNew` DESC');
			$db->execute($query, '2');
			$inactiveTeams = array();
			while ($row = $db->fetchRow($query))
			{
				// use a temporary array for better readable (but slower) code
				// append team data
				$inactiveTeams[] = $this->addToTeamList($row);
			}
			$db->free($query);
			
			$tmpl->assign('activeTeams', $activeTeams);
			$tmpl->assign('inactiveTeams', $inactiveTeams);
		}
		
		
		public function showTeam($teamid)
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
			// FIXME: implement something to avoid hardcoded paths
			$tmpl->assign('pmLink', '../PM/?add&teamid=' . $teamid);
			
			// the team's leader
			$teamLeader = 0;
			
			$query = $db->prepare('SELECT *'
								  . ', (SELECT `name` FROM `players`'
								  . ' WHERE `players`.`id`=`teams`.`leader_userid` LIMIT 1)'
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
				$teamLeader = intval($row['leader_userid']);
				
				
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
				$team['leaderLink'] = '../Players/?profile=' . $row['leader_userid'];
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

			$tmpl->assign('teamid', $teamid);
			$tmpl->assign('showPMButton', $user->getID() > 0 ? true : false);
			
			
			$showActionColumn = false;
			if ($user->getID() === $teamLeader
				|| $user->getPermission('allow_kick_any_team_members'))
			{
				$showActionColumn = true;
			}
			$members = array();
			$query = $db->prepare('SELECT `players`.`name` AS `player_name`'
								  . ', `players`.`id` AS `userid`'
								  . ', (SELECT `name` FROM `countries`'
								  . ' WHERE `countries`.`id`=`players_profile`.`location`'
								  . ' AND `players_profile`.`playerid`=`players`.`id` LIMIT 1)'
								  . ' AS `country_name`'
								  . ', (SELECT `flagfile` FROM `countries`'
								  . ' WHERE `countries`.`id`=`players_profile`.`location`'
								  . ' AND `players_profile`.`playerid`=`players`.`id` LIMIT 1)'
								  . ' AS `flagfile`'
								  . ', `players_profile`.`joined`'
								  . ', `players_profile`.`last_login`'
								  . ' FROM `players`,`players_profile`'
								  . 'WHERE `players`.`id`=`players_profile`.`playerid`'
								  . ' AND `teamid`=?');
			$db->execute($query, $teamid);
			while ($row = $db->fetchRow($query))
			{
				// rename db result fields and assemble some additional informations
				// use a temporary array for better readable (but slower) code
				$prepared = array();
				if (!$showActionColumn && $user->getID() === intval($row['userid']))
				{
					$showActionColumn = true;
				}
				$prepared['profileLink'] = '../Players/?profile=' . $row['userid'];
				$prepared['userName'] = $row['player_name'];
				$prepared['permissions'] = $teamLeader === intval($row['userid']) ? 'Leader' : 'Standard';
				$prepared['countryName'] = $row['country_name'];
				if (strlen($row['flagfile']) > 0)
				{
					$prepared['countryFlag'] = '../Flags/' . $row['flagfile'];
				}
				$prepared['joined'] = $row['joined'];
				
				// show leave/kick links if permission is given
				// a team leader can not leave or be kicked
				// you must first give someone else leadership
				if (($user->getID() === $teamLeader
					|| $user->getID() === intval($row['userid'])
					|| $user->getPermission('allow_kick_any_team_members'))
					&& $user->getID() !== $teamLeader)
				{
					$prepared['removeLink'] = './?remove=' . intval($row['userid']);
					
					if ($user->getID() === intval($row['userid']))
					{
						$prepared['removeDescription'] = 'Leave team';
					} else
					{
						$prepared['removeDescription'] = 'Kick member from team';
					}
				}
				
				// append current member data
				$members[] = $prepared;
			}
			$db->free($query);
			unset($prepared);
/* 			echo('<pre>'); print_r($members); echo('</pre>'); */
			$tmpl->assign('members', $members);
			$tmpl->assign('showActionColumn', $showActionColumn);
			
			$matches = array();
			$tmpl->assign('matches', $matches);
		}
	}
?>
