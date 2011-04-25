<?php
	class newWorldLogin
	{
		function __construct()
		{
			global $tmpl;
			
			
			$modules = array();
			
			if ($this->getRequestedModule() === false)
			{
				$this->showLoginText($modules);
			} else
			{
				if (strcasecmp($_GET[$module], 'form') === 0)
				{
					$this->showForm($_GET['module']);
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
		
		
		private function getRequestedModule()
		{
			if (!isset($_GET['module']))
			{
				return false;
			}
			
			$module = $_GET['module'];
			
			// clean module name
			str_replace('.', '', $module);
			str_replace(':', '', $module);
			str_replace('/', '', $module);
			str_replace('\\', '', $module);
			
			// check if file exists
			if (!file_exists(dirname(__FILE__) . $module))
			{
				return false;
			}
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
		
		private function showForm($module)
		{
			include_once(dirname(__FILE__) . '/modules/' . $module . '/index.php');
			$class = new $module;
			$class->showForm();
		}
	}
?>
