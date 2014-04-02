<?php
	class teamAdd
	{
		private $team;
		
		public function __construct()
		{
			global $tmpl;
			
			// does user have permission to create a team?
			if (!$this->checkPermission())
			{
				$tmpl->setTemplate('NoPerm');
				return;
			}
			
			// user has permission, choose template to use for output
			$tmpl->setTemplate('teamSystemCreateTeam');
			$tmpl->assign('canCreateTeam', true);
			
			// check which action is requested by the user
			$confirmed = !isset($_POST['confirmed']) ? 0 : (int) $_POST['confirmed'];
			if ($confirmed === 0)
			{
				$this->showForm();
			} elseif ($confirmed === 1)
			{
				// handle for new team
				$this->team = new team();
				
				// try to create team
				// show creation form on error
				if (($validation = $this->sanityCheck()) !== true || ($validation = $this->createTeam()) !== true)
				{
					if ($validation !== true)
					{
						$tmpl->assign('form_error', $validation);
					}
					$this->showForm();
				} else
				{
					$tmpl->assign('teamid', $this->team->getID());
					$tmpl->assign('teamCreateSuccessful', true);
				}
			}
		}
		
		// check create team permission
		// returns true if permission is granted, false otherwise
		protected function checkPermission()
		{
			// no anonymous team creation
			// user must have allow_create_teams permission
			// user must be teamless
			return \user::getCurrentUserLoggedIn() &&
				   \user::getCurrentUser()->getPermission('allow_create_teams') &&
				   \user::getCurrentUser()->getIsTeamless();
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
			
			// validate team name
			if (!isset($_POST['team_name']))
			{
				return 'No team name set. A name is required.';
			}
			
			$teamName = (string) $_POST['team_name'];
			// check for leading and trailing whitespace
			if (strlen(trim($teamName)) !== strlen($teamName))
			{
				return 'Check your team name, no leading or trailing whitespace allowed.';
			}
			
			// (teamless) is a reserved name
			if ($teamName === '(teamless)')
			{
				return 'Check your team name. The name (teamless) is reserved and can not be used.';
			}
			
			// 3 characters min team name size
			if (strlen($teamName) < 3)
			{
				return 'Check your team name, at least 3 characters are required.';
			}
			
			// 30 characters max team name size
			if (strlen($teamName) > 30)
			{
				return 'Check your team name, maximum length is 30 characters.';
			}
			
			if (($teamNameUsed = \team::getTeamsByName($teamName)) && count($teamNameUsed) > 0)
			{
				return 'Team name is already in use. A team name must be unique.';
			}
			unset($teamNameUsed);
			
			$this->team->setName($teamName);
			
			// validate open team
			if (isset($_POST['team_open']))
			{
				if ($_POST['team_open'] === '0')
				{
					$this->team->setOpen(false);
				} else
				{
					$this->team->setOpen(true);
				}
			}
			
			// validate team description
			if (isset($_POST['team_description']))
			{
				// just pass through the values to team class
				$this->team->setDescription((string) $_POST['team_description']);
				$this->team->setRawDescription((string) $_POST['team_description']);
			}
			
			// validate team avatar uri
			if (isset($_POST['team_avatar_uri']))
			{
				if ($_POST['team_avatar_uri'] === '')
				{
					$avatarURI = '';
				} else
				{
					// image formats: GIF, JPEG, PNG and SVG are supported
					// protocols: HTTP and HTTPS are supported
					
					// check for image suffix rather than connecting to uri
					// reason: avoid to trigger other forms or to download image with copyright
					$avatarURI = trim($_POST['team_avatar_uri']);
					$allowedExtensions = array('.gif', '.jpg', '.jpeg', '.png', '.svg');
					$extensionOK = false;
					foreach($allowedExtensions AS $allowedExtension)
					{
						if (substr($avatarURI, -strlen($allowedExtension)) === $allowedExtension)
						{
							$extensionOK = true;
							break;
						}
					}
					
					if (!$extensionOK)
					{
						return 'Check your avatar URI, it must end with .gif, .jpg, .jpeg, .png or .svg, other formats are not supported.';
					}
					
					if (!(substr($avatarURI, 0, 7) == 'http://') && !(substr($avatarURI, 0, 8) == 'https://'))
					{
						return 'Check protocol of avatar URI, HTTP and HTTPS are supported.';
					}
				}
				
				$this->team->setAvatarURI($avatarURI);
			}
			
			return true;
		}
		
		// fill form with values
		protected function showForm()
		{
			global $site;
			global $tmpl;
			
			
			// protected against cross site injection attempts
			$randomKeyName = 'teamCreate_' . \user::getCurrentUser()->getID() . '_' . microtime();
			// convert some special chars to underscores
			$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
			$randomkeyValue = $site->setKey($randomKeyName);
			$tmpl->assign('keyName', $randomKeyName);
			$tmpl->assign('keyValue', htmlent($randomkeyValue));
			
			// bbcode editor
			include_once(dirname(dirname(dirname(__FILE__))) . '/bbcode_buttons.php');
			$bbcode = new bbcode_buttons();
			// set up name of field to edit so javascript knows which element to manipulate
			$tmpl->assign('buttonsToFormat', $bbcode->showBBCodeButtons('team_description'));
			unset($bbcode);
		}
		
		protected function createTeam()
		{
			// create team using submitted data
			$result = $this->team->create();
			
			// add user to team
			$user = \user::getCurrentUser();
			if (!$user->addTeamMembership($this->team->getID()))
			{
				return 'Could not add current user to team.';
			}
			
			if (!$user->update())
			{
				return 'Could not save changes of current user.';
			}
			
			if ($result !== true)
			{
				return $result;
			}
			
			// set current user to leader
			if (!$this->team->setLeaderId(\user::getCurrentUserId()))
			{
				return 'Could not set user to new team leader.';
			}
			
			if (!$this->team->update())
			{
				return 'Could not save user as team leader.';
			}
			
			return true;
		}
	}
?>
