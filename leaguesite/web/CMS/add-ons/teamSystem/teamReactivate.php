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
			}
			
			// remove user from team
			if (!$this->user->removeTeamMembership($this->team->getID()) || !$this->user->update())
			{
				$tmpl->assign('error', 'An unknown error occurred while leaving the team.');
			} else
			{
				// notify team members using a private message
				$pm = new pm();
				$pm->setSubject($this->user->getName() . ' got kicked from your team');
				$pm->setContent('Player ' . $this->user->getName() . ' got kicked from your team by ' . \user::getCurrentUser()->getName() . '.');
				
				$pm->setTimestamp(date('Y-m-d H:i:s'));
				$pm->addTeamID($this->team->getID());
				
				// send it
				$pm->send();
				
				// tell user that team reactivation was successful
				$tmpl->assign('teamReactivationSuccessful', true);
			}
		}
	}
?>
