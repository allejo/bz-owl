<?php
	namespace matchServices;
	
	class teamOperations
	{
		public function getName($teamID)
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `name` from `teams` WHERE `id`=:teamID LIMIT 1');
			if (!$db->execute($query, array(':teamID' => array($teamID, \PDO::PARAM_INT))))
			{
				return false;
			}
			
			$name = $db->fetchRow($query);
			$db->free($query);
			
			return $name[0];
		}
	}
?>
