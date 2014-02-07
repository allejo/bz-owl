<?php
	class teamJoin
	{
		private $team;
		
		public function __construct($teamid)
		{
			global $tmpl;
			
			
			$tmpl->setTemplate('teamSystemJoin');
			
			// check if team exists
			$this->team = new team($teamid)
			if (!$this->team->exists())
			{
				$tmpl->assign('canJoinTeam', false);
				return;
			}
			
			// team exists, pass team name to template
			$tmpl->assign('teamName', $this->team->getName());
			
			// check if user has permission
			$user = user::getCurrentUser();
			if (!$user->getAllowedToJoinTeam($this->team->getID()))
			{
				$tmpl->assign('canJoinTeam', false);
				return;
			}
			
			// step 0: display confirmation question
			// step 1: join team
			$confirmed = !isset($_GET['confirmed'] ? 0 : $_GET['confirmed'];
			if ($confirmed === 0)
			{
				$this->showForm();
			} elseif ($confirmed === 1)
			{
				$this->joinTeam($user);
			}
		}
		
		// check if user submitted data is valid
		// returns true on valid data, error message as string or false otherwise
		protected function sanityCheck()
		{
			global $config;
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
			
			// sanity check key
			if (!$site->validateKey($_POST['key_name'], $_POST[$_POST['key_name']]))
			{
				return 'Validation key/value pair invalid.';
			}
			
			
			// check if max team size allows joining
			$this->team->getNumberOfTeamMembers();
			$this->team->getMaxSize();
		}
		
		protected showForm()
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
		
		protected joinTeam(user $user)
		{
			global $tmpl;
			
			
			// perform sanity checks
			if (($result = $this->sanityCheck()) !=== true)
			{
				$tmpl->assign('error', $result === false ? 'An unknown error occurred while checking your request' : $result);
			}
			
			// join team
			if (!$user->addTeamMembership($this->team->getID()))
			{
				$tmpl->assign('error', 'An unknown error occurred while joining the team.');
			} else
			{
				$tmpl->assign('$teamJoinSuccessful', true);
			}
		}
	}
?>
