<?php
	class newWorldLogin
	{
		function __construct()
		{
			global $tmpl;
			
			
			$modules = array();
			
			if (!isset($_GET['module'])
				|| ($module = $this->getRequestedModule($_GET['module'])) === false)
			{
				$this->showLoginText($modules);
			} else
			{
			echo('module request:' . $_GET[$module]);
				if (strcasecmp($_GET[$module], 'form') === 0)
				{
					$this->showForm($module);
				} else
				{
					$this->doLogin($module);
				}
				
				// add known module to modules list
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
		
		
		private function showLoginText(&$modules)
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
		
		
		private function showForm(&$module)
		{
			include_once(dirname(__FILE__) . '/modules/' . $module . '/index.php');
			$class = new $module;
			$module = $class->showForm();
		}
		
		
		private function doLogin(&$module)
		{
			
		}
	}
?>
