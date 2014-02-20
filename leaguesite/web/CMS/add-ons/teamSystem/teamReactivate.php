<?php
	class teamReactivate
	{
		private $user;
		private $team;
		
		public function __construct()
		{
			global $tmpl;
			
			
			$tmpl->setTemplate('teamSystemReactivate');
			
			// user (that opens form must have permission to reactivate teams
			if (!\user::getCurrentUser()->getPermission('allow_reactivate_teams'))
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			$tmpl->assign('title', 'Reactivate a team');
			
			
			$confirmed = !isset($_POST['confirmed']) ? 0 : (int) $_POST['confirmed'];
			if ($confirmed === 0)
			{
				$this->showForm();
			} elseif ($confirmed === 1)
			{
				$this->reactivateTeam();
			}
		}
		
		// fill form with values
		protected function showForm()
		{
			global $site;
			global $tmpl;
			
			
			// protected against cross site injection attempts
			$randomKeyName = 'teamReactivate_' . microtime();
			// convert some special chars to underscores
			$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
			$randomkeyValue = $site->setKey($randomKeyName);
			$tmpl->assign('keyName', $randomKeyName);
			$tmpl->assign('keyValue', htmlent($randomkeyValue));
			
			// display teams that can be reactivated
			$teamids = \team::getDeletedTeamIds();
			$teamData = array();
			foreach($teamids AS $teamid)
			{
				$teamData[] = array('id' => $teamid, 'name' => (new team($teamid))->getName());
			}
			$tmpl->assign('teams', $teamData);
			
			// a team must always have a leader
			// display user choice to admin
			
			// get all teamless users
			$users = \user::getTeamlessUsers();
			$userData = array();
			foreach($users AS $user)
			{
				// a team should only be reactivated so it can play...no point of inactive, disabled or banned user
				if ($user->getStatus() === 'active')
				{
					$userData[] = array('id' => $user->getID(), 'name'=> $user->getName());
				}
			}
			$tmpl->assign('users', $userData);
		}
		
		// check if user submitted data is valid
		// returns true on valid data, error message as string or false otherwise
		protected function sanityCheck()
		{
			global $site;
			
			
			// require random access key name
			if (!isset($_POST['key_name']))
			{
				return 'Submitted validation key name invalid.';
			}
			
			// require random access key value
			if (!isset($_POST[$_POST['key_name']]))
			{
				return 'Submitted validation key content invalid.';
			}
			
			// check key and value combination
			if (!$site->validateKey($_POST['key_name'], $_POST[$_POST['key_name']]))
			{
				return 'Validation key/value pair invalid.';
			}
			
			// check team to be reactivated
			if (!isset($_POST['reactivate_team_id']))
			{
				return 'You did not specify which team should be reactivated.';
			}
			
			$this->team = new team((int) $_POST['reactivate_team_id']);
			if (!$this->team->exists())
			{
				return 'The specified team does not exist.';
			}
			
			if ($this->team->getStatus() !== 'deleted')
			{
				return 'You can only reactivate deleted teams.';
			}
			
			// check chosen team leader
			if (!isset($_POST['reactivate_team_new_leader_id']))
			{
				return 'You did not specify any leader for the team.';
			}
			
			$this->user = new user((int) $_POST['reactivate_team_new_leader_id']);
			if (!$this->user->exists())
			{
				return 'The specified team leader does not exist.';
			}
			
			if (!$this->user->getIsTeamless())
			{
				return 'The team leader is not teamless. A leader must be teamless.';
			}
			
			return true;
		}
		
		// reactivate team with chosen leader
		protected function reactivateTeam()
		{
			global $tmpl;
			
			
			// perform sanity checks
			if (($result = $this->sanityCheck()) !== true)
			{
				$tmpl->assign('error', $result === false ? 'An unknown error occurred while checking your request' : $result);
				return;
			}
			
			$tmpl->assign('teamName', $this->team->getName());
			$tmpl->assign('teamid', $this->team->getID());
			$tmpl->assign('userName', $this->user->getName());
			$tmpl->assign('userid', $this->user->getID());
			
			// reactivate team with chosen leader
			// issue an invitation for team leader so he can join
			$invitation = new invitation();
			$invitation->forUserId($this->user->getID());
			$invitation->toTeam($this->team->getID());
			$invitation->insert(false);
			// now change team status to reactivate and add the user to team then make the user leader
			if (!$this->team->setStatus('reactivated') || !$this->team->update()
				|| !$this->user->addTeamMembership($this->team->getID()) || !$this->user->update()
				|| !$this->team->setLeaderId($this->user->getID()) || !$this->team->update())
			{/* var_dump($this->user->addTeamMembership($this->team->getID())); */
				$tmpl->assign('error', 'An unknown error occurred while reactivating the team.');
			} else
			{
				// notify team members using a private message
				$pm = new pm();
				$pm->setSubject(\user::getCurrentUser()->getName() . ' reactivated team ' . $this->team->getName());
				$pm->setContent('Congratulations: Player ' . \user::getCurrentUser()->getName() . ' reactivated team ' . $this->team->getName() . ' with you as its leader.');
				
				$pm->setTimestamp(date('Y-m-d H:i:s'));
				$pm->addUserID($this->user->getID());
				
				// send it
				$pm->send();
				
				// tell user that team reactivation was successful
				$tmpl->assign('teamReactivationSuccessful', true);
			}
		}
	}
?>
