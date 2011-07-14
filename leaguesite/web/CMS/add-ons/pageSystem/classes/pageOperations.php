<?php
	class pageOperations extends database
	{
		public function changeRequested($id, $requestPath, $title, $addon)
		{
			// FIXME: lock CMS table!
			
			
			// check if id set in db
			// TODO: check if no data change -> abort soon
			$curAddon = $this->getAddonUsed($id);
			if (!$curAddon)
			{
				return 'FATAL ERROR: Change of id ' . $id . ' in pageSystem requested but id not in db.';
			}
			
			// do not allow to change any entry of pageSystem
			if (strcmp($curAddon, 'pageSystem') === 0)
			{
				return ('Removing pageSystem from any URL is not supported by this add-on. '
						.'Reason: It could really confuse people and cause plenty of trouble. '
						.'If you really want to do this, you must edit database directly instead.');
			}
			
			// remove illegal characters from request path
			if (strlen($requestPath) > strlen($this->cleanRequestPath($requestPath)))
			{
				echo('<br><br>' . $requestPath . '<br>' . $this->cleanRequestPath($requestPath));
				return 'Invalid characters in request path detected. Please check your input.';
			}
			
			// check if request path is unique
			$query = $this->prepare('SELECT `id` FROM `CMS` WHERE `id`<>? AND `requestPath`=? LIMIT 1');
			$this->execute($query, array($id, $requestPath));
			$row = $this->fetchRow($query);
			$this->free($query);
			if ($row)
			{
				return 'Request path already in use, request path must be unique in db';
			}
			
			// write the changes
			$query = $this->prepare('UPDATE `CMS` SET `requestPath`=?,`title`=?,`addon`=? WHERE `id`=? LIMIT 1');
			$this->execute($query, array($requestPath, $title, $addon, $id));
			
			return 'Changes written successfully.';
		}
		
		
		private function cleanRequestPath(&$path)
		{
			// TODO: consider using a regular expression to only allow valid chars (whitelist)
			
			// remove special chars such as ? from request path
			$i = strpos($path, '?');
			// do not remove chars on no match
			if ($i !== false)
			{
				$path = substr($path, 0, $i);
			}
			
			// remove whitespace from the end at least
			rtrim($path);
			
			return $path;
		}
		
		
		public function getAddonList($include='')
		{
			// this function returns an array of installed add-ons
			// as array, excluding the add-ons that can not be handle pages directly
			
			// the directory where the add-ons reside
			$addonDir = dirname(dirname(dirname(__FILE__)));
			
			// create an array of everything found in that directory
			$addons = scandir($addonDir);
			
			// blacklist unwanted entries
			foreach ($addons as $i => $curFile)
			{
				// remove entry from array if it's no folder
				if (!is_dir($addonDir . '/' . $curFile))
				{
					unset($addons[$i]);
					continue;
				}
				
				// filter reserved directory names
				switch ($curFile)
				{
					case (strcasecmp('.', $curFile) === 0):
						unset($addons[$i]);
						break;
						
					case (strcasecmp('..', $curFile) === 0):
						unset($addons[$i]);
						break;
						
					case (strcasecmp('.svn', $curFile) === 0):
						unset($addons[$i]);
						break;
				}
				
				// whitelist explicitly included addon
				if (strcmp($include, $curFile) === 0)
				{
					continue;
				}
				
				// filter addons that can not handle pages directly
				if (isset($addons[$i])
					&& file_exists($addonDir . '/' . $curFile . '/' . 'indirect'))
				{
					unset($addons[$i]);
				}
				
				// filter pageSystem (this) add-on based on file path
				if (isset($addons[$i])
					&& strcmp($addonDir . '/' . $curFile, dirname(dirname(__FILE__))) === 0)
				{
					unset($addons[$i]);
				}
			}
			unset($curFile);
			
			return $addons;
		}
		
		
		public function getIdExists($id)
		{
			// you may use getAddonUsed instead
			
			$query = $this->prepare('SELECT `id` FROM `CMS` WHERE `id`=? LIMIT 1');
			$this->execute($query, $id);
			$row = $this->fetchRow($query);
			if (!$row)
			{
				return false;
			}
			$this->free($query);
			
			return true;
		}
		
		public function getAddonUsed($id)
		{
			$query = $this->prepare('SELECT `addon` FROM `CMS` WHERE `id`=? LIMIT 1');
			$this->execute($query, $id);
			$row = $this->fetchRow($query);
			if (!$row)
			{
				return false;
			}
			$this->free($query);
			
			return $row['addon'];
		}
		
		
		public function getPageList()
		{
			// this function collects the list of assigned pages directly from database
			
			$query = $this->SQL('SELECT `id`,`requestPath`,`title`,`addon` FROM `CMS` ORDER BY `id`');
			$pages = $this->fetchAll($query);
			
			return $pages;
		}
		
		
		public function getPageData($id)
		{
			$query = $this->prepare('SELECT `id`,`requestPath`,`title`,`addon` FROM `CMS` WHERE `id`=? LIMIT 1');
			$this->execute($query, $id);
			
			return $this->fetchRow($query);
		}
		
		
		public function hasPermission()
		{
			global $user;
			global $config;
			
			
			if (!$config->getValue('debugSQL') && !$user->getPermission('allow_admin_pageSystem'))
			{
				return false;
			}
			
			return true;
		}
	}
?>
