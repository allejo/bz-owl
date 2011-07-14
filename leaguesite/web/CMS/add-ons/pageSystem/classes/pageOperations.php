<?php
	class pageOperations extends database
	{
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
