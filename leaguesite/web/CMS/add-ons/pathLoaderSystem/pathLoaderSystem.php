<?php
	class pathLoaderSystem
	{
		function __construct()
		{
			global $config;
			global $tmpl;
			global $site;
			
			
			// if path specified use it, otherwise default to root
			$path = (isset($_GET['path'])) ? $_GET['path'] : '/';
			
			$title = 'Untitled';
			
			if ($this->getUserBanned())
			{
				$tmpl->setTemplate('NoPerm');
				$tmpl->display();
				return;
			}
			
			if ($config->getValue('maintenance.now'))
			{
				header('Content-Type: text/plain');
				exit($config->getValue('maintenance.msg') ? $config->getValue('maintenance.msg') : 'This site has been shut down due to maintenance.' . "\n");
			}
			
			// load the add-on
			$this->loadAddon($this->addonToUse($path, $title), $title, $path);
		}
		
		
		function addonToUse($path, &$title)
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `addon`, `title` FROM `CMS` WHERE `request_path`=? LIMIT 1');
			$db->execute($query, $path);
			
			$row = $db->fetchRow($query);
			$db->free($query);
			
			$addon = '';
			if ($this->getFixedPageAddon($path, $title, $addon))
			{
				return $addon;
			}
			
			if ($row && count($row) > 0)
			{
				$addon = $row['addon'];
				$title = $row['title'];
				
				return $addon;
			}
			
			return false;
		}
		
		
		function getFixedPageAddon($path, &$title, &$addon)
		{
			if (strcmp($path, 'Login/') === 0)
			{
				$title = 'Login';
				$addon = 'loginSystem';
				
				return true;
			}
			
			return false;
		}
		
		
		function getUserBanned()
		{
			// find out if user should have access
			// to do this ask installed helper modules
			$modules = $this->getModules();
			
			$banned = false;
			$n = count($modules);
			$firstLoginModuleText = true;
			
			for ($i = 1; $i <= $n && !$banned; $i++)
			{
				if ($module = array_shift($modules))
				{
					include_once(dirname(__FILE__) . '/modules/' . $module . '/' . $module . '.php');
					// use a variable because adding the name space in combination with new operator won't work
					$className = 'pathLoaderSystem\\' .$module;
					$class = new $className($banned);
				}
				unset($class);
			}
			
			return $banned;
		}
		

		private function getModules()
		{
			$modules = array();
			
			// get a list of ban modules
			
			// it is important that the local module (checks in db if already banned)
			// is listed first to reduce requests to external sources
			// then scan the files in the modules directory
			$modules = array_merge(array('local'), scandir(dirname(__FILE__) . '/modules/'));
			
			// remove blacklisted module entries from $modules
			foreach ($modules as $i => $curFile)
			{
				// remove entry from array if it's no folder
				if (!is_dir(dirname(__FILE__) . '/modules/' . $curFile))
				{
					unset($modules[$i]);
					continue;
				}
				
				// remove module if DEACTIVATED file is found in its root folder
				if (file_exists(dirname(__FILE__) . '/modules/' . $curFile . '/DEACTIVATED'))
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
					
					// also leave out local if not first element of array to remove duplicate entry
					// (local is content of first element of array by definition)
					case ((strcasecmp('local', $curFile) === 0) && ($i !==0)):
						unset($modules[$i]);
						break;
				}
			}

			return $modules;
		}
		
		
		function loadAddon($addon, $title, $path)
		{
			global $site;
			global $tmpl;
			
			
			$file = dirname(dirname(__FILE__)) . '/' . $addon . '/' . $addon . '.php';
			if (file_exists($file))
			{
				// init the addon
				include($file);
				
				// use special namespace if declaration file found
				$namespaceFile = dirname($file) . '/' . $addon . '.namespace';
				if (file_exists($namespaceFile))
				{
					$namespace = file_get_contents($namespaceFile);
					$addon = $addon . '\\' . $namespace;
				}
				
				// load the add-on
				$addon = new $addon($title, $path);
			} else
			{
				// the path could not be found in database
				$tmpl->setTemplate('404');
				$tmpl->display();
			}
		}
	}
?>
