<?php
	class teamJoin
	{
		private $team;
		private $user;
		
		public function __construct($teamid)
		{
			global $tmpl;
			
			
			$tmpl->setTemplate('teamSystemJoin');
			
			// check if team exists
			$this->team = new team($teamid);
			if (!$this->team->exists())
			{
				$tmpl->assign('canJoinTeam', false);
				return;
			}
			$tmpl->assign('teamid', $this->team->getID());
			
			// team exists, pass team name to template
			$tmpl->assign('teamName', $this->team->getName());
			
			// check if user has permission
			$this->user = user::getCurrentUser();
			if (!$this->user->getAllowedToJoinTeam($this->team->getID()))
			{
				$tmpl->assign('canJoinTeam', false);
				return;
			}
			$tmpl->assign('canJoinTeam', true);
			
			// step 0: display confirmation question
			// step 1: join team
			$confirmed = !isset($_POST['confirmed']) ? 0 : (int) $_POST['confirmed'];
			if ($confirmed === 0)
			{
				$this->showForm();
			} elseif ($confirmed === 1)
			{
				$this->joinTeam($this->user);
			}
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
			if (!($memberCount = $this->team->getSize()) || !($memberMax = $this->team->getMaxSize()) || $memberCount+1 > $memberMax)
			{
				return 'Joining team failed. There are too many members in the team.';
			}
			
			return true;
		}
		
		protected function showForm()
		{
			global $site;
			global $tmpl;
			
			
			// protected against cross site injection attempts
			$randomKeyName = 'teamJoin_' . $this->team->getID() . '_' . microtime();
			// convert some special chars to underscores
			$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
			$randomkeyValue = $site->setKey($randomKeyName);
			$tmpl->assign('keyName', $randomKeyName);
			$tmpl->assign('keyValue', htmlent($randomkeyValue));
		}
		
		protected function joinTeam(user $user)
		{
			global $tmpl;
			
			
			// perform sanity checks
			if (($result = $this->sanityCheck()) !== true)
			{
				$tmpl->assign('error', $result === false ? 'An unknown error occurred while checking your request' : $result);
			}
			
			// join team
			if (!$this->user->addTeamMembership($this->team->getID()) || !$this->user->update())
			{
				$tmpl->assign('error', 'An unknown error occurred while joining the team.');
			} else
			{
				$tmpl->assign('teamJoinSuccessful', true);
			}
		}
	}
?>
