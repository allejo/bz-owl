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
			global $db;
			
			
			$query = $db->prepare('SELECT `id` FROM `countries` WHERE `id`=? LIMIT 1');
			$db->execute($query, '1');
			$insert_entry = ($db->fetchRow($query) === false);
			$db->free($query);
			
			if ($insert_entry)
			{
				$query = $db->prepare('INSERT INTO `countries` (`id`,`name`, `flagfile`) VALUES (?, ?, ?)');
				$db->execute($query, array('1', 'here be dragons', ''));
			}
			
			$dir = dirname(dirname(dirname(dirname(__FILE__)))) . '/Flags';
			$countries = array();
			if ($handle = opendir($dir))
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file != '.' && $file != '..' && $file != '.svn' && $file != '.DS_Store')
					{
						$countries[] = $file;
					}
				}
				closedir($handle);
			}
			
			$queryFlag = $db->prepare('SELECT `flagfile` FROM `countries` WHERE `name`=?');
			$queryInsertCountry = $db->prepare('INSERT INTO `countries` (`name`, `flagfile`) VALUES (?, ?)');
			$queryUpdateCountry = $db->prepare('UPDATE `countries` SET `flagfile`=? WHERE `name`=?');
			foreach($countries as &$one_country)
			{
				$flag_name_stripped = str_replace('Flag_of_', '', $one_country);
				$flag_name_stripped = str_replace('.png', '', $flag_name_stripped);
				$flag_name_stripped = str_replace('_', ' ', $flag_name_stripped);
				
				// check if flag exists in database
				$db->execute($queryFlag, $flag_name_stripped);
				
				$update_country = false;
				$insert_entry = false;
				if (!(mysql_num_rows($result) > 0))
				{
					$update_country = true;
					$insert_entry = true;
				}
				if (!$update_country)
				{
					while ($row = mysql_fetch_array($result))
					{
						if (!(strcmp($row['flagfile'], $one_country) === 0))
						{
							$update_country = true;
						}
					}
				}
				mysql_free_result($result);
				
				if ($update_country)
				{
					if ($insert_entry)
					{
						$db->execute($queryInsertCountry, array($flag_name_stripped, $one_country));
						$db->free($queryInsertCountry);
					} else
					{
						$db->execute($queryUpdateCountry, array($one_country, $flag_name_stripped));
						$db->free($queryUpdateCountry);
					}
				}
			}
		}
	}
?>
