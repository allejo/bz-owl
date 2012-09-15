<?php
	namespace pathLoaderSystem;
	
	class local
	{
		public function __construct(&$banned)
		{
			global $db;
			
			
			// do nothing in cli mode
			if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']))
			{
				return;
			}
			
			// report error if still no ip address found
			if (!isset($_SERVER['REMOTE_ADDR']) || !$_SERVER['REMOTE_ADDR'])
			{
				$db->logError(__FILE__ . ': $_SERVER[\'REMOTE_ADDR\'] not set and php_sapi_name is not cli!');
				return;
			}
			
			
			// check current ip-address against dynamic ban table
			$query = $db->prepare('SELECT `id`,`expiration_timestamp` FROM `cms_bans` WHERE `ip-address`=:ip LIMIT 1');
			if (!$db->execute($query, array(':ip' => array($_SERVER['REMOTE_ADDR'], \PDO::PARAM_STR))))
			{
				$db->logError('FATAL ERROR: ' . __FILE__ . ': could not check expiration_timestamp for ip ' . $_SERVER['REMOTE_ADDR']);
				return;
			}
			if (!$row = $db->fetchRow($query))
			{
				// no ban entry found for ip-address
				$banned = false;
				return;
			}
			
			if ($row['expiration_timestamp'] === '0000-00-00 00:00:00')
			{
				// permanent ban
				$banned = true;
				return;
			}
			
			// compare UTC based times
			$timezone = new \DateTimeZone('UTC');
			
			// current timestamp
			$curTimestampUTC = new \DateTime('now', $timezone);
			
			// ban expiration timestamp
			$expTimestampUTC = new \DateTime($row['expiration_timestamp'], $timezone);
			
			// ban expired?
			if ($expTimestampUTC >= $curTimestampUTC)
			{
				// no
				$banned = true;
				return;
			}
			
			// okay, give the ip another chance
			// clean up old ban entry
			$query = $db->prepare('DELETE FROM `cms_bans` WHERE `id`=:id');
			$db->execute($query, array(':id' => array($row['id'], \PDO::PARAM_INT)));
			
			
			
			$banned = false;
			
			return;
		}
	}
?>
