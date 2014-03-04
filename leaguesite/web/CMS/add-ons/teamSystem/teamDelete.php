<?php
	class teamDelete
	{
		private $team;
		
		public function __construct($teamid)
		{
			global $tmpl;
			
			
			$tmpl->setTemplate('teamSystemDelete');
			
			// teamid 0 is reserved and can not be deleted
			// no anon team deletion
			if ((int) $teamid === 0 || !\user::getCurrentUserLoggedIn())
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			$tmpl->assign('teamid', (int) $teamid);
			$this->team = new team((int) $teamid);
			
			// is this a valid teamid?
			if (!$this->team->exists())
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			// is team already deleted?
			if ($this->team->getStatus() === 'deleted')
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			// does the user have permission to delete the team?
			if (!\user::getCurrentUser()->getPermission('team.allowDelete ' . $this->team->getID())
				&& !\user::getCurrentUser()->getPermission('allow_delete_any_team')
				&& !(\user::getCurrentUserId() === $this->team->getLeaderId()))
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}

			// now that we know the team name we can setup a proper title and tell the template the teamName
			$tmpl->assign('teamName', $this->team->getName());
			$tmpl->assign('title', 'Delete team ' . $this->team->getName());
			
			// check which action is requested by the user
			$confirmed = !isset($_POST['confirmed']) ? 0 : (int) $_POST['confirmed'];
			if ($confirmed === 0)
			{
				$this->showForm();
			} elseif ($confirmed === 1)
			{
				$this->deleteTeam();
			}
		}
		
		// fill form with values
		protected function showForm()
		{
			global $site;
			global $tmpl;
			
			
			// protected against cross site injection attempts
			$randomKeyName = 'teamDelete_' . $this->team->getID() . '_' . microtime();
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
			
			return true;
		}
		
		// delete the team (does not physically delete but marks as deleted in database)
		protected function deleteTeam()
		{
			global $tmpl;
			
			
			// perform sanity checks
			if (($result = $this->sanityCheck()) !== true)
			{
				$tmpl->assign('error', $result === false ? 'An unknown error occurred while checking your request' : $result);
				return;
			}
			
			
			// notify team members using a private message first because later we won't have the membership info
			$pm = new pm();
			$pm->setSubject(\user::getCurrentUser()->getName() . ' deleted ' . $this->team->getName());
			$pm->setContent('Player ' . \user::getCurrentUser()->getName() . ' just deleted the team '. $this->team->getName() .' you were member of.');
			$pm->setTimestamp(date('Y-m-d H:i:s'));
			$pm->addTeamID($this->team->getID());
			
			// send it
			$pm->send();
			
			// remove the members from team
			$members = $this->team->getUsers();
			foreach($members AS $member)
			{
				$member->removeTeamMembership($this->team->getID());
				$member->update();
			}
			unset($members);
			unset($member);
			
			// set the teams status to deleted
			$this->team->setStatus('deleted');
			
			// save team changes
			if (!$this->team->update())
			{
				$tmpl->assign('error', 'An unknown error occurred while deleting the team.');
			} else
			{
				
				// tell joined user that join was successful
				$tmpl->assign('teamDeleteSuccessful', true);
			}
		}
	}

?>
