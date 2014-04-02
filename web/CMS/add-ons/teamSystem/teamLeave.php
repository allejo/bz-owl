<?php
	class teamLeave
	{
		private $team;
		private $user;
		
		public function __construct($userid, $teamid)
		{
			global $tmpl;
			
			
			$tmpl->setTemplate('teamSystemLeave');
			
			// userid and teamid must not be reserved ids
			if ($userid === 0 || $teamid === 0)
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			$tmpl->assign('teamid', $teamid);
			
			$this->user = new user($userid);
			$this->team = new team($teamid);
			
			$tmpl->assign('teamName', $this->team->getName());
			
			// user and team must exist, user must belong to team
			// require a visitor to be logged in, anonymous user removal from teams not allowed
			if (!\user::getCurrentUserLoggedIn() || !$this->user->exists() || !$this->team->exists() || !$this->user->getMemberOfTeam($teamid))
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			// either team leader or user with special permissions can kick members
			if (\user::getCurrentUserId() !== $this->team->getLeaderId()
				// special users also have permission to kick members
				&& !\user::getCurrentUser()->getPermission('allow_kick_any_team_members')
				&& \user::getCurrentUserId() !== $this->user->getID())
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			// no leader can leave the team
			// ensures that team always has at least one leader
			if ($this->user->getId() === $this->team->getLeaderId())
			{
				$tmpl->assign('title', 'Leave ' . htmlent($this->team->getName()));
				$tmpl->assign('error', 'A team leader can not leave a team. You should set someone else in charge of leadership first.');
				return;
			}
			
			$tmpl->assign('targetUserName', $this->user->getName());
			$tmpl->assign('targetUserId', $this->user->getID());
			if ($this->user->getID() === \user::getCurrentUserId())
			{
					$tmpl->assign('userRequestsLeave', true);
				$tmpl->assign('title', 'Leave ' . htmlent($this->team->getName()));
			} else
			{
				$tmpl->assign('userRequestsLeave', false);
				$tmpl->assign('title', 'Remove ' . htmlent($this->user->getName()) . ' from ' . htmlent($this->team->getName()));
			}
			
			
			$confirmed = !isset($_POST['confirmed']) ? 0 : (int) $_POST['confirmed'];
			if ($confirmed === 0)
			{
				$this->showForm();
			} elseif ($confirmed === 1)
			{
				$this->leaveTeam();
			}
		}
		
		// fill form with values
		protected function showForm()
		{
			global $site;
			global $tmpl;
			
			
			// protected against cross site injection attempts
			$randomKeyName = 'teamLeave_' . $this->team->getID() . '_' . microtime();
			// convert some special chars to underscores
			$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
			$randomkeyValue = $site->setKey($randomKeyName);
			$tmpl->assign('keyName', $randomKeyName);
			$tmpl->assign('keyValue', htmlent($randomkeyValue));
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
			
			// check if max team size allows joining
			if ($this->team->getMemberCount() < 0)
			{
				return 'Leaving or removing user from team failed. There are already no members in the team.';
			}
			
			return true;
		}
		
		// remove user from team
		protected function leaveTeam()
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
				if (\user::getCurrentUserId() === $this->user->getID())
				{
					// notify team members about left member
					$pm->setSubject($this->user->getName() . ' left your team');
					$pm->setContent('Player ' . $this->user->getName() . ' just left your team.');
					
					$pm->setTimestamp(date('Y-m-d H:i:s'));
					$pm->addTeamID($this->team->getID());
					
					// send it
					$pm->send();
				} else
				{
					// notify team members of kicked member
					$pm->setSubject($this->user->getName() . ' got kicked from your team');
					$pm->setContent('Player ' . $this->user->getName() . ' got kicked from your team by ' . \user::getCurrentUser()->getName() . '.');
					$pm->setTimestamp(date('Y-m-d H:i:s'));
					$pm->addTeamID($this->team->getID());
					
					// send it
					$pm->send();
					
					// notify kicked member of the kick
					$pm = new pm();
					$pm->setSubject('You got kicked from your team by ' . \user::getCurrentUser()->getName());
					$pm->setContent('Player ' . \user::getCurrentUser()->getName() . ' just kicked you from your team.');
					$pm->setTimestamp(date('Y-m-d H:i:s'));
					$pm->addUserID($this->user->getID());
					
					// send it
					$pm->send();
				}
				
				
				// tell joined user that join was successful
				$tmpl->assign('teamLeaveSuccessful', true);
			}
		}
	}
?>
