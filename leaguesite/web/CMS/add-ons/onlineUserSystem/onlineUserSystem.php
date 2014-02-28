<?php
	class onlineUserSystem
	{
		public function __construct($title)
		{
			global $config;
			global $tmpl;
			global $db;
			
			$tmpl->setCaching(Smarty::CACHING_OFF);
			$tmpl->setTemplate('onlineUserSystem');
			//$tmpl->clearCache('onlineUserSystem.xhtml.tmpl');
			$tmpl->assign('title', $title);
			
			$query = 'SELECT * FROM `online_users` ORDER BY last_activity DESC';	
			if (!$result = ($db->SQL($query)))
			{
				$db->free($result);
				return;
			}
			
			// use the resulting data
			if ($result)
			{
				$row = $db->fetchRow($result);
				// only logged in users are shown -> an unregistered guest is not counted
				if ($row == false)
				{
					// no users logged in
					return;
				} else
				{
					$onlineUsers = array();
					$basepath = $config->getValue('basepath');
					do
					{
						$onlineUsers[$row['userid']] = array('id' => $row['userid'], 'name' => $row['username'],
															 'idle' => $this->showTimeSince($this->convert_datetime($row['last_activity'])));
					} while ($row = $db->fetchRow($result));
					
					// last_activity timestamp is set based on a low priority update that may finish after page data has been collected
					// to avoid old info show about visitor user account just force set that value to 0
					if (isset($onlineUsers[user::getCurrentUserId()]))
					{
						$onlineUsers[user::getCurrentUserId()]['idle'] = '0s';
					}
					
					$tmpl->assign('onlineUserSystem', $onlineUsers);
				}
				$db->free($result);
			}
			// list of online users computed successfully
		}
		
		public function __destruct()
		{
			global $tmpl;
			
			$tmpl->display();
		}
		
		private function showTimeSince($gettime)
		{
			// compute days
			$gettime = time() - $gettime;
			$d = floor($gettime / (24 * 3600));
			
			// compute hours
			$gettime = $gettime - ($d * (24 * 3600));
			$h = floor($gettime / (3600));
			
			// compute minutes
			$gettime = $gettime - ($h * (3600));
			$m = floor($gettime / (60));
			
			// compute seconds
			$gettime = $gettime - ($m * 60);
			$s = $gettime;
			
			// format return value
			$rtn = '';
			if ($d != 0) $rtn .= $d.'d ';
			if ($h != 0) $rtn .= $h.'h ';
			if ($m != 0) $rtn .= $m.'m ';
			if ($s != 0) $rtn .= $s.'s';
			if ($rtn == '') $rtn = '0s';
			
			return $rtn;
		}
		
		private function convert_datetime($str)
		{
			list($date, $time) = explode(' ', $str);
			list($year, $month, $day) = explode('-', $date);
			list($hour, $minute, $second) = explode(':', $time);
			
			$ts = mktime($hour, $minute, $second, $month, $day, $year);
			return $ts;
		}		
	}	
?>
