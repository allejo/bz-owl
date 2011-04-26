<?php
	class newWorldLogin
	{
		function __construct()
		{
			global $tmpl;
			
			
			$modules = array();
			
			if ($this->getRequestedModule($_GET['module']) === false)
			{
				$this->showLoginText($modules);
			} else
			{
				if (strcasecmp($_GET['module'], 'form') === 0)
				{
					$this->showForm($_GET['module']);
				}
				
				// add known module to modules list
				$modules[] = $_GET['module'];
			}
			
			
			if (count($modules) > 0)
			{
				$tmpl->assign('modules', $modules);
			}
			
			$tmpl->display('newWorldLogin');
		}
		
		
		private function getRequestedModule($module_name)
		{
			return (isset($module_name)
				and preg_match('^[0-9A-Za-z]+$', $module_name)
				and file_exists(dirname(__FILE__) . '/modules/' . $module_name));
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
