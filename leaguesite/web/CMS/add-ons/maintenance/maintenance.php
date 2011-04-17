<?php
	class maintenance
	{
		function __autoload($class_name)
		{
			require_once dirname(dirname(dirname(__FILE__))) . '/classes/' . $class_name . '.php';
		}
		
		function __construct()
		{
			if (isMaintenanceNeeded())
			{
				doMaintaince();
			}
		}
		
		function isMaintenanceNeeded()
		{
			global $db;
			
			
			$today = date('d.m.Y');
			$last_maintenance = '00.00.0000';
			
			// check last time where maintenance was performed
			$query = $db->SQL('SELECT `last_maintenance` FROM `misc_data` LIMIT 1');
			$lastMaintenanceSaved = false;
			while ($row = $db->fetchRow($query))
			{
				$lastMaintenanceSaved = true;
				
				if (isset($row['last_maintenance']))
				{
					$last_maintenance = $row['last_maintenance'];
				}
			}
			$db->free($query);
			
			// save new maintenance timestamp
			if ($lastMaintenanceSaved)
			{
				$query = $db->prepare('UPDATE `misc_data` SET `last_maintenance`=?');
				$db->execute($query, $today);
				$db->free($query);
			} else
			{
				$query = $db->prepare('INSERT INTO `misc_data` (`last_maintenance`) VALUES (?)');
				$db->execute($query, $today);
				$db->free($query);
			}
			
			// daily maintenance
			return (strcasecmp($today, $last_maintenance) !== 0);
		}
		
		function doMaintaince()
		{
			
			echo '<p>Maintenance performed successfully.</p>';
		}
		
		function maintainPlayers()
		{
			
		}
		
		function maintainTeams()
		{
			
		}
		
		function updateCountries()
		{
			
		}
	}
?>
