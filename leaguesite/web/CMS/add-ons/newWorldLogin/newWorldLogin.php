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
							$module .= $this->doLogin();
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
		
		
		private function doLogin()
		{
			
		}
	}
?>
