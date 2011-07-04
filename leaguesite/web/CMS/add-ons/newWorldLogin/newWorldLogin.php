<?php
	class newWorldLogin
	{
		private $moduleOutput = '';
		
		public function __construct()
		{
			global $tmpl;
			global $user;
			
			
			// abort process if user already logged in
			if ($user->getID() > 0)
			{
				$this->moduleOutput = ('<p>You are already logged in. '
									  . 'If you want to login with a different account '
									  . 'you must first logout.</p>');
				return;
			}
			
			// if no module chosen, display login text of all modules
			// NOTE: certain modules may suppress their login text, depending on circumstances
			if (isset($_GET['module']) === false)
			{
				$this->getLoginText($this->moduleOutput);
				return;
			}
			
			// if requested module is unavailable print error msg
			// append module login text to provide a choice to continue
			if (($module = $this->getRequestedModule($_GET['module'])) === false)
			{
				$this->moduleOutput = '<p>An error occurred, module name not accepted.</p>';
				$this->getLoginText($modules);
				return;
			}
			
			if (isset($_GET['action']) === false)
			{
				$this->moduleOutput = '<p>An error occurred, module action not specified.</p>';
				return;
			}
			
			// activate module code based on user requested action
			include_once(dirname(__FILE__) . '/modules/' . $module . '/index.php');
			$moduleInstance = new $module;
			
			switch($_GET['action'])
			{
				case 'form':
					$this->moduleOutput = $moduleInstance->showForm();
					break;
				case 'login':
					if ($moduleInstance->validateLogin($message))
					{
						$this->doLogin($moduleInstance, $module);
					}
					if (strlen($message) > 0)
					{
						$this->moduleOutput .= '<p> returned a message: ' . $message . '</p>' . "\n";
					}
					break;
				default: 
					$this->moduleOutput = '<p>Unknown module action requested, request not accepted.</p>';
			}
			
			
			print_r($module);
		}
		
		public function __destruct()
		{
			global $tmpl;
			
			
			$tmpl->assign('modules', $this->moduleOutput);
			$tmpl->display('newWorldLogin');
		}		
		
		
		private function getLoginText(&$modules)
		{
			// first scan the files in the modules directory
			$modules = scandir(dirname(__FILE__) . '/modules/');
			foreach ($modules as $i => $curFile)
			{
				// remove entry from array if it's no folder
				if (!is_dir(dirname(__FILE__) . '/modules/' . $curFile))
				{
					unset($modules[$i]);
					continue;
				}
				
				// filter reserved directory names
				switch ($curFile)
				{
					case (strcasecmp('.', $curFile) === 0):
						unset($modules[$i]);
						break;
						
					case (strcasecmp('..', $curFile) === 0):
						unset($modules[$i]);
						break;
						
					case (strcasecmp('.svn', $curFile) === 0):
						unset($modules[$i]);
						break;
				}
				
				
				if (isset($modules[$i]))
				{
					include_once(dirname(__FILE__) . '/modules/' . $curFile . '/index.php');
					$class = new $curFile();
					$modules[$i] = $class->showLoginText();
				}
			}
		}
		
		
		private function getRequestedModule($moduleName)
		{
			// search user supplied module name for invalid data
			
			// return false if invalid data was found
			return (isset($moduleName)
					&& preg_match('/^[0-9A-Za-z]+$/', $moduleName)
					&& file_exists(dirname(__FILE__) . '/modules/' . $moduleName))
					? $moduleName : false;
		}
		
		
		private function doLogin($moduleInstance, $moduleName)
		{
			global $config;
			global $user;
			
			
			// if used login module is not local, then an external login has been used
			$externalLogin = strcasecmp($moduleName, 'local') !== 0;
			// init user id to reserved value 0
			$uid = 0;
			
			// load operations framework
			include(dirname(__FILE__) . '/classes/userOperations.php');
			$userOperations = new userOperations();
			
			
			if ($externalLogin)
			{
				// lookup internal id using external id
				$uid = $userOperations->findIDByExternalLogin($moduleInstance->getID());
			} else
			{
				// local login id is equal to internal login id by definition
				$uid = $moduleInstance->getID();
			}
			
			
			// uid is 0 if it's a user with no external login id
			// but an external login id has been provided
			// uid is also 0 if a user registers
			if ($uid === 0)
			{
				// try to recover uid by username based db lookup
				$uid = $userOperations->findIDByName($moduleInstance->getName());
				
				// init newUser to false (do not send welcome message by default)
				$newUser = false;
				
				// find out if an internal id can be found for callsign
				$newUser = ($uid !== 0) ? false: true;
				
				if ($newUser)
				{
					if ($config->getValue('login.welcome.summary'))
					{
						$this->moduleOutput .= '<p>' . $config->getValue('login.welcome.summary') . '</p>';
					} else
					{
						$this->moduleOutput .= '<p>Welcome and thanks for registering on this website.</p>';
					}
					
					$userOperations->sendWelcomeMessage($uid);
				}
				
				if ($uid === 0)
				{
					// call logout as bandaid for erroneous login modules
					// these may log the user in, even though they never should
					$user->logout();
					$this->moduleOutput .= '<p>An internal error occurred: $uid === 0.</p>' . "\n";
					return false;
				}
			}
			
			
			// re-activate deleted accounts
			// stop processing disabled/banned or broken accounts
			$status = $userOperations->getAccountStatus($uid);
			switch ($status)
			{
				case 'active' : break;
				case 'deleted':	$userOperations->activateAccount($uid); break;
				case 'login disabled' :
					$this->moduleOutput .= '<p>Your account is disabled: No login possible.</p>';
					return false;
					break;
				// TODO: implement site wide ban list
				case 'banned' :
					$this->moduleOutput .= '<p>You have been banned from this website.</p>';
					return false;
					break;
				default:
					$this->moduleOutput .= ('<p>The impossible happened: Account status is'
											. htmlent($status) . '.</p>');
					return false;
			}
			
			if ($uid > 0)
			{
				$user->setID($uid);
				$moduleInstance->givePermissions();
				$userOperations->updateLastLogin($uid);
				
				$this->moduleOutput .= '<p>Login was successful!</p>';
				return true;
			} else
			{
				$user->logout();
			}
			
			return false;
		}
	}
?>
