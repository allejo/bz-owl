<?php
	class pageOperations extends database
	{
		public function changeRequested($id, $requestPath, $title, $addon)
		{
			// FIXME: lock CMS table!
			
			
			// check if id set in db
			// TODO: check if no data change -> abort soon
			$query = $this->prepare('SELECT `id`,`addon` FROM `CMS` WHERE `id`=? LIMIT 1');
			$this->execute($query, $id);
			$row = $this->fetchRow($query);
			if (!$row)
			{
				return 'FATAL ERROR: Change of id ' . $id . ' in pageSystem requested but id not in db.';
			}
			$this->free($query);
			
			// do not allow to change any entry of pageSystem
			if (strcmp($row['addon'], 'pageSystem') === 0)
			{
				return ('Removing pageSystem from any URL is not supported by this add-on. '
						.'Reason: It could really confuse people and cause plenty of trouble. '
						.'If you really want to do this, you must edit db directly instead');
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
