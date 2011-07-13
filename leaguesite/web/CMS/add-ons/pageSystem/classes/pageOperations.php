<?php
	class pageOperations extends database
	{
		public function getPageList()
		{
			// this function collects the list of assigned pages directly from database
			
			$query = $this->SQL('SELECT `requestPath`,`title`,`addon` FROM `CMS` ORDER BY `id`');
			$pages = $this->fetchAll($query);
			
			return $pages;
		}
	}
?>