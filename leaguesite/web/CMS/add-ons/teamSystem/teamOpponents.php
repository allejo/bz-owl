<?php
	class teamOpponents
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
		
		
		private function addToOpponentTeamList($teamid, &$row, &$stats)
		{
			global $db;
			
			// rename db result fields and assemble some additional informations
			
			// Who is opponent?
			$opponentTeamId = ((int) $row['team1_id'] === $teamid) ? '2' : '1';
			$opponentTeam = $row['team'. $opponentTeamId . '_id'];
			$referenceTeamId = $opponentTeamId === '1' ? '2': '1';
			
			if (!isset($stats[$opponentTeam]))
			{
				$stats[$opponentTeam] = (object) array('teamId'=>$opponentTeam,'matchCount'=>0,'won'=>0,'tied'=>0,'lost'=>0,'ratio'=>0);
				$query = $db->prepare('SELECT *,`name` FROM `teams` WHERE `id`=:opponentid LIMIT 1');
				$db->execute($query, array(':opponentid' => array($opponentTeam, PDO::PARAM_INT)));
				while ($nameRow = $db->fetchRow($query))
				{
					$stats[$opponentTeam]->name = $nameRow['name'];
				}
			}
			
			$stats[$opponentTeam]->matchCount++;
			if ($row['team'.$referenceTeamId.'_points'] > $row['team'.$opponentTeamId.'_points'])
			{
				$stats[$opponentTeam]->won++;
			} else if ($row['team'.$referenceTeamId.'_points'] < $row['team'.$opponentTeamId.'_points'])
			{
				$stats[$opponentTeam]->lost++;
			} else
			{
				$stats[$opponentTeam]->tied++;
			}
		}
		
		public function sortOpponents($key, $order)
		{
			// sort teams by column name in $key, order is either 'asc' or 'desc'
			return function ($a, $b) use ($key, $order)
			{
				return ($order === 'desc') ? strnatcmp($b->$key, $a->$key) : strnatcmp($a->$key, $b->$key);
			};
		}
		
		public function showOpponentStats($teamid)
		{
			global $tmpl;
			global $user;
			global $db;
			
			
			if (!$tmpl->setTemplate('teamSystemOpponentStats'))
			{
				$tmpl->noTemplateFound();
				die();
			}
			$tmpl->assign('title', 'Team opponent statistics');
			$tmpl->assign('teamid', $teamid);
			
			
			// get team name for specified teamid
			$params = array(':teamid' => array($teamid, PDO::PARAM_INT));
			$query = $db->prepare('SELECT `name` FROM `teams` WHERE `id`=:teamid LIMIT 0,1');
			if (!$db->execute($query, $params))
			{
				$tmpl->assign('teamName', 'Error: No team name found for specified teamid.');
				$tmpl->display();
				$db->logError('FATAL ERROR: Invalid query to find out team name in '
							  . __FILE__ . ': function: showOpponentStats');
				die();
			}
			
			// if teamName not set, template has to present an error and stop output, too
			if ($row = $db->fetchRow($query))
			{
				$tmpl->assign('teamName', $row['name']);
			} else
			{
				$tmpl->display();
				die();
			}
			$db->free($query);
			unset($row);
			
			
			// collect team opponent informations for specified teamid
			$teamOpponents = array();
			$query = $db->prepare('SELECT * FROM `matches` '
								  . 'WHERE `team1_id`=:teamid OR `team2_id`=:teamid');
			$db->execute($query, $params);

			while ($row = $db->fetchRow($query))
			{
				$this->addToOpponentTeamList($teamid, $row, $stats);
			}
			$db->free($query);
			unset($row);
			
			foreach($stats AS $opponentTeam)
			{
				$opponentTeam->winRatio = round(($opponentTeam->won / $opponentTeam->matchCount)*100, 2);
				$query = $db->prepare('SELECT * FROM `teams_overview`
									  JOIN `teams_profile` ON `teams_overview`.`teamid`=`teams_profile`.`teamid`
									  WHERE `teams_overview`.`teamid`=:opponentid LIMIT 1');
				$db->execute($query, array(':opponentid' => array($opponentTeam->teamId, PDO::PARAM_INT)));
				while ($nameRow = $db->fetchRow($query))
				{
					$team = new team($nameRow['teamid']);
					$opponentTeam->score = $nameRow['score'];
					$opponentTeam->scoreClass = $this->rankScore($team->getScore());
					$opponentTeam->matchSearchLink = ('../Matches/?search_string=' . $team->getName()
													  . '&amp;search_type=team+name'
													  . '&amp;search_result_amount=20'
													  . '&amp;search=Search');
					unset($team);
				}
				$opponentTeam->profileLink = './?profile=' . $opponentTeam->teamId;
				$opponentTeam->memberCount = $nameRow['member_count'];
				$opponentTeam->activityNew = $nameRow['activityNew'];
				$opponentTeam->activityOld = $nameRow['activityOld'];
				$opponentTeam->created = $nameRow['created'];
				if (empty($opponentTeam->name))
				{
					$opponentTeam->name = '<span style="font-style:bold;">Could not resolve team name</span>';
				}
				$db->free($query);
			}
			
			// sort data
			// check if sort column is set and exists in dataset
			if (isset($_GET['sort']) && isset($opponentTeam->{$_GET['sort']}))
			{
				// sort ascending by default
				$order = (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'desc': 'asc';
				// use user defined sorting function, utilising a closure
				uasort($stats, $this->sortOpponents($_GET['sort'], $order));
				
				// pass sorting infos to template
				$tmpl->assign('sortCol', $_GET['sort']);
				$tmpl->assign('sortOrder', ($order === 'desc') ? 'desc' : 'asc');
			}
			
			// pass the opponent data to template
			$tmpl->assign('teamOpponents', $stats);
		}
	}
?>
