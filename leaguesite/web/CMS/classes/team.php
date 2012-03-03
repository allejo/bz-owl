<?php
	// handle team related data
	
	// not loaded by default!
	class team
	{
		private $getNameQuery;
		
		function getName($teamid = 0)
		{
			global $config;
			global $db;
			
			// returns current user if no userid specified, otherwise name of user of supplied userid			
			
			if ($teamid === 0)
			{
				return '$team->getName(0): reserved teamid';
			}
			
			
			// collect name from database
			$this->getNameQuery = $db->prepare('SELECT `name` FROM `teams` WHERE `id`=:teamid LIMIT 1');
			if ($db->execute($this->getNameQuery, array(':teamid' => array($teamid, PDO::PARAM_INT))))
			{
				$teamName = $db->fetchRow($this->getNameQuery);
				$db->free($this->getNameQuery);
				
				return $teamName['name'];
			}
			
			// error handling: log error and show it in end user visible result
			$db->logError((__FILE__) . ': getName(' . strval($userid) . ') failed.'); 
			return '$team->getName(' . strval($teamid) . ') failed.';
		}
	}
?>
