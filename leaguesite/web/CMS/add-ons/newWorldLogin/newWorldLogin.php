<?php
	class newWorldLogin
	{
		function __construct()
		{
			global $tmpl;
			
			
			// TODO: hardcoded settings, move them into settings file after add-on is behaving good
			$this->loginText($modules);
			
			if (count($modules) > 0)
			{
				$tmpl->assign('modules', $modules);
			}
			
			$tmpl->display('newWorldLogin');
		}
		
		private function loginText(&$modules)
		{
			global $config;
			
			
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
					$modules[$i] = $class->loginText($config->value('useXhtml'));
				}
			}
		}
	}
?>
