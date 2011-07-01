<?php
	class newWorldLogin
	{
		function __construct()
		{
			global $tmpl;
			
			
			$modules = array();
						
			if (isset($_GET['module']) === false)
			{
				$this->getLoginText($modules);
			} elseif (($module = $this->getRequestedModule($_GET['module'])) === false)
			{
				$module = '<p>An error occurred, module name not accepted.</p>';
			} elseif (isset($_GET['action']) === false)
			{
				$module = '<p>An error occurred, module action not specified.</p>';
			} else
			{
				include_once(dirname(__FILE__) . '/modules/' . $module . '/index.php');
				$moduleInstance = new $module;
				switch($_GET['action'])
				{
					case 'form':
						$module = $moduleInstance->showForm();
						break;
					case 'login':
						if ($moduleInstance->validateLogin($message))
						{
							$module .= $this->doLogin($moduleInstance, $module);
						}
						if (strlen($message) > 0)
						{
							$module .= '<p> returned a message: ' . $message . '</p>' . "\n";
						}
						break;
					default: 
						$module = '<p>Unknown module action requested, request not accepted.</p>';
				}
			}
			
			if (isset($module))
			{
				$modules[] = $module;
			}
			
			
			if (count($modules) > 0)
			{
				$tmpl->assign('modules', $modules);
			}
			
			$tmpl->display('newWorldLogin');
		}
		
		
		private function getRequestedModule($moduleName)
		{
			return (isset($moduleName)
					&& preg_match('/^[0-9A-Za-z]+$/', $moduleName)
					&& file_exists(dirname(__FILE__) . '/modules/' . $moduleName))
					? $moduleName : false;
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
		
		
		private function doLogin($moduleInstance, $moduleName)
		{
			global $user;
/* 			global $db; */
			
			
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
					$userOperations->sendWelcomeMessage($uid);
				}
				
				if ($uid === 0)
				{
					// call logout as bandaid for erroneous login modules
					// these may log the user in, even though they never should
					$user->logout();
					return '<p>An internal error occurred: $uid === 0.</p>' . "\n";
				}
			}
			
			
			// re-activate deleted accounts
			// stop processing disabled/banned or broken accounts
			$status = $userOperations->getAccountStatus($uid);
			switch ($status)
			{
				case 'active' : break;
				case 'deleted':	$userOperations->activateAccount($uid); break;
				case 'login disabled' : return '<p>Your account is disabled: No login possible.</p>'; break;
				// TODO: implement site wide ban list
				case 'banned' : return '<p>You have been banned from this website.</p>'; break;
				default: return '<p>The impossible happened: Account status is' . htmlent($status) . '.</p>' ;
			}
			
			if ($uid > 0)
			{
				$user->setID($uid);
				$moduleInstance->givePermissions();
				$userOperations->updateLastLogin($uid);
				
				return true;
			} else
			{
				$user->logout();
			}
			
			return false;
		}
	}
?>
