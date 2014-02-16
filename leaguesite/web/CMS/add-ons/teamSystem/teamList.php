<?php
	class teamList
	{
		public function __construct($teamid = 0)
		{
			if ($teamid === 0)
			{
				$this->showTeams();
				return;
			}
			
			$this->showTeam($teamid);
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
		
		
		private function addToTeamList(team $team)
		{
				global $tmpl;
				
				
				// rename db result fields and assemble some additional informations
				
				$prepared = array();
				$prepared['id'] = $team->getID();
				$prepared['profileLink'] = './?profile=' . $team->getID();
				$prepared['name'] = $team->getName();
				$prepared['score'] = $team->getScore();
				$prepared['scoreClass'] = $this->rankScore($prepared['score']);
				$prepared['matchSearchLink'] = ('../Matches/?search_string=' . $team->getName()
												. '&amp;search_type=team+name'
												. '&amp;search_result_amount=200'
												. '&amp;search=Search');
				$prepared['matchCount'] = $team->getMatchCount();
				$prepared['memberCount'] = $team->getMemberCount();
				$prepared['leaderLink'] = '../Players/?profile=' . $team->getLeaderId();
				$prepared['leaderName'] = (new user($team->getLeaderId()))->getName();
				$prepared['activityNew'] = $team->getActivityNew();
				$prepared['activityOld'] = $team->getActivityOld();
				$prepared['created'] = $team->getCreationTimestampStr();
				$prepared['canJoin'] = (new user(user::getCurrentUserId()))->getIsTeamless() && (new user(user::getCurrentUserId()))->getAllowedToJoinTeam($team->getID());
				
				$tmpl->assign('showTeamActionOptions', $prepared['canJoin']);
				
				// return result data
				return $prepared;
		}
		
		
		public function showTeams()
		{
			global $tmpl;
			global $user;
			global $db;
			
			
			// tell template if user can reactivate maintained/deleted teams
			$tmpl->assign('canReactivateTeams', $user->getPermission('allow_reactivate_teams'));
			
			if (!$tmpl->setTemplate('teamSystemList'))
			{
				$tmpl->noTemplateFound();
				die();
			}
			$tmpl->assign('title', 'Team overview');
			
			// get list of new, active, inactive and reactivated teams (no deleted and inactive teams)
			$teams = team::getNewTeamIds();
			$teams = array_merge($teams, team::getActiveTeamIds());
			$teams = array_merge($teams, team::getReactivatedTeamIds());
			$teams = array_merge($teams, team::getInactiveTeamIds());
			
			
			$teams = team::getTeamsFromIds($teams);
			
			// sort teams by score, highest score first
			function scoreSort(team $a, team $b)
			{
				if ($a->getScore() === $b->getScore())
				{
					return 0;
				}
				
				return ($a->getScore() > $b->getScore()) ? -1 : 1;
			}
			usort($teams, 'scoreSort');
			
			// display all teams that are not deleted or inactive as active
			$activeTeams = array();
			foreach($teams AS $team)
			{
				$activeTeams[] = $this->addToTeamList($team);
			}
			
			$teams = team::getTeamsFromIds(team::getInactiveTeamIds());
			usort($teams, 'scoreSort');
			
			$inactiveTeams = array();
			foreach($teams AS $team)
			{
				// use a temporary array for better readable (but slower) code
				// append team data
				$inactiveTeams[] = $this->addToTeamList($team);
			}
			
			$tmpl->assign('activeTeams', $activeTeams);
			$tmpl->assign('inactiveTeams', $inactiveTeams);
		}
		
		
		public function showTeam($teamid)
		{
			global $tmpl;
			global $db;
			
			
			if (!$tmpl->setTemplate('teamSystemProfile'))
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
								  . ', (SELECT `name` FROM `users`'
								  . ' WHERE `users`.`id`=`teams`.`leader_userid` LIMIT 1)'
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
							  . user::getCurrentUserId() . ' failed, request URI: ' . $_SERVER['REQUEST_URI']);
				$tmpl->setTemplate('NoPerm');
				$tmpl->display();
				die();
			}
			
			$team = new team($teamid);
			if (!$team->exists())
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			
			$teamData = array();
			while ($row = $db->fetchRow($query))
			{
				$tmpl->assign('title', 'Team ' . htmlent($row['name']));
				
				$teamLeader = $team->getLeaderId();
				
				
				$teamData['profileLink'] = './?profile=' . $team->getID();
				$teamData['name'] = $team->getName();
				$teamData['score'] = $team->getScore();
				$teamData['scoreClass'] = $this->rankScore($teamData['score']);
				$teamData['matchSearchLink'] = ('../Matches/?search_string=' . $teamData['name']
												. '&amp;search_type=team+name'
												. '&amp;search_result_amount=200'
												. '&amp;search=Search');
				$teamData['matchCount'] = $team->getMatchCount();
				$teamData['memberCount'] = $team->getSize();
				$teamData['leaderLink'] = '../Players/?profile=' . $team->getLeaderId();
				$teamData['leaderName'] = (new \user($team->getLeaderId()))->getName();
				$teamData['activityNew'] = $team->getActivityNew();
				$teamData['activityOld'] = $team->getActivityOld();
				$teamData['created'] = $row['created'];
				
				$teamData['wins'] = $team->getMatchCount('won');
				$teamData['draws'] = $team->getMatchCount('draw');
				$teamData['losses'] = $team->getMatchCount('lost');
				
				$teamData['logo'] = $team->getAvatarURI();
				
				$tmpl->assign('teamDescription', $team->getDescription());
			}
			$db->free($query);
			$tmpl->assign('team', $teamData);
			
			$tmpl->assign('teamid', $teamid);
			$tmpl->assign('canPMTeam', \user::getCurrentUserId() > 0 ? true : false);
			
			// tell template if user can edit this team
			$tmpl->assign('canEditTeam', (\user::getCurrentUserId() === $teamLeader) || \user::getCurrentUser()->getPermission('allow_edit_any_team_profile'));
			
			// tell template if user can delete this team
			// either user has deletion permission for team
			// or user is leader of team and there are one or less members in team
			$tmpl->assign('canDeleteTeam', \user::getCurrentUser()->getPermission('team.allowDelete ' . $team->getID()) ||
						 (\user::getCurrentUserId() === $team->getLeaderId() && $team->getSize() < 2 && $team->getSize() !== false));
			
			$showMemberActionOptions = false;
			if (\user::getCurrentUserId() === $teamLeader
				|| \user::getCurrentUser()->getPermission('allow_kick_any_team_members'))
			{
				$showMemberActionOptions = true;
			}
			$members = array();
			$memberids = $team->getUserIds();
			foreach($memberids AS $memberid)
			{
				$user = new \user($memberid);
				$member = array();
				// rename db result fields and assemble some additional informations
				// use a temporary array for better readable (but slower) code
				if (!$showMemberActionOptions && \user::getCurrentUserId() === $memberid)
				{
					$showMemberActionOptions = true;
				}
				$member['profileLink'] = '../Players/?profile=' . $user->getID();
				$member['userName'] = $user->getName();
				$member['permissions'] = $teamLeader === $memberid ? 'Leader' : 'Standard';
				if (($country = $user->getCountry()))
				{
					$member['countryName'] = $country->getName();
					if (strlen($country->getFlag()) > 0)
					{
						$member['countryFlag'] = $country->getFlag();
					}
				}
				
				$member['joined'] = $user->getJoinTimestampStr();
				$member['last_login'] = $user->getLastLoginTimestampStr();
				
				// show leave/kick links if permission is given
				// a team leader can neither leave or be kicked
				// a leader must first give someone else leadership to leave
				if ((\user::getCurrentUserId() === $teamLeader
					|| \user::getCurrentUser()->getPermission('allow_kick_any_team_members'))
					&& $user->getID() !== $teamLeader)
				{
					$member['removeLink'] = './?remove=' . $user->getID() . '&amp;team=' . $teamid;
					if (\user::getCurrentUserId() === $user->getID())
					{
						$member['removeDescription'] = 'Leave team';
					} else
					{
						$member['removeDescription'] = 'Kick member from team';
					}
				}
				
				// append current member data
				$members[] = $member;
				unset($user);
			}
			
			$tmpl->assign('members', $members);
			$tmpl->assign('showMemberActionOptions', $showMemberActionOptions);
			
			
			// show last entered matches
			$matches = array();
			
			// show available options if any available
			$allowEdit = \user::getCurrentUser()->getPermission('allow_edit_match');
			$allowDelete = \user::getCurrentUser()->getPermission('allow_delete_match');
			$tmpl->assign('showMatchActionOptions', $allowEdit || $allowDelete);
			$tmpl->assign('allowEdit', $allowEdit);
			$tmpl->assign('allowDelete', $allowDelete);
			
			// get match data
			// sort the data by id to find out if abusers entered a match at a long time in the past
			$query = $db->prepare('SELECT `timestamp`,`team1_id`,`team2_id`,'
								  . '(SELECT `name` FROM `teams` WHERE `id`=`team1_id`) AS `team1_name`'
								  . ',(SELECT `name` FROM `teams` WHERE `id`=`team2_id`) AS `team2_name`'
								  . ',`team1_points`,`team2_points`,`userid`'
								  . ',(SELECT `users`.`name` FROM `users`'
								  . ' WHERE `users`.`id`=`matches`.`userid`)'
								  . ' AS `username`'
								  . ',`matches`.`id`'
								  . ' FROM `matches` WHERE `matches`.`team1_id`=?'
								  . ' OR `matches`.`team2_id`=?'
								  . ' ORDER BY `id` DESC LIMIT 0,10');
			$db->execute($query, array($teamid, $teamid));
			while ($row = $db->fetchRow($query))
			{
				// rename db result fields and assemble some additional informations
				// use a temporary array for better readable (but slower) code
				$prepared = array();
 				$prepared['time'] = $row['timestamp'];
				$prepared['team1Link'] = '../Teams/?profile=' . $row['team1_id'];
				$prepared['team2Link'] = '../Teams/?profile=' . $row['team2_id'];
				$prepared['team1Name'] = $row['team1_name'];
				$prepared['team2Name'] = $row['team2_name'];
				$prepared['score1'] = $row['team1_points'];
				$prepared['score2'] = $row['team2_points'];
				$prepared['lastModById'] = $row['userid'];
				$prepared['lastModByName'] = $row['username'];
				
				$prepared['lastModByLink'] = '../Players/?profile=' . $prepared['lastModById'];
				if ($allowEdit)
				{
					$prepared['editLink'] = '../Matches/?edit=' . $row['id'];
				}
				if ($allowDelete)
				{
					$prepared['deleteLink'] = '../Matches/?delete=' . $row['id'];
				}
				
				$matches[] = $prepared;
			}
			$tmpl->assign('matches', $matches);
		}
	}
?>
