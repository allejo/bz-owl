<?php
	class userOperations extends database
	{
		public function addToVisitsLog($id)
		{
			// insert login of user to visits log
			$ip_address = getenv('REMOTE_ADDR');
			$host = gethostbyaddr($ip_address);
			$query = ('INSERT INTO `visits` (`userid`,`ip-address`,`host`,`forwarded_for`,`timestamp`) VALUES'
					  . ' (?, ?, ?, ?, ?)');
			$query = $this->prepare($query);
			$args = array($id, htmlent($ip_address), htmlent($host)
						  // try to detect original ip-address in case proxies are used
						  , htmlent(getenv('HTTP_X_FORWARDED_FOR')), date('Y-m-d H:i:s'));
			$this->execute($query, $args);
		}
		
		
		public function updateLastLogin($id)
		{
			$query = $this->prepare('UPDATE `users_profile` SET `last_login`=:lastLogin'
									. ' WHERE `userid`=:uid LIMIT 1');
			$this->execute($query, array(':lastLogin' => array(date('Y-m-d H:i:s'), PDO::PARAM_STR),
										 ':uid' => array($id, PDO::PARAM_INT)));
			
			if ($row = $this->fetchRow($query))
			{
				$status = $row['status'];
			}
			$this->free($query);
			
			return;
		}
		
		
		public function addToOnlineUserList($name, $id)
		{
			// find out if table exists
			$query = $this->SQL('SHOW TABLES LIKE ' . "'" . 'online_users' . "'");
			$numRows = count($this->fetchRow($query));
			$this->free($query);
			
			$onlineUsers = false;
			if ($numRows > 0)
			{
				// no need to create table in case it does not exist
				// any interested viewer looking at the online page will create it
				$onlineUsers = true;
			}
			
			// use the resulting data
			if ($onlineUsers)
			{
				$query = $this->prepare('SELECT * FROM `online_users` WHERE `userid`=?');
				$this->execute($query, $id);
				$rows = $this->rowCount($query);
				// done
				$this->free($query);
				
				$onlineUsers = false;
				if ($rows > 0)
				{
					// already logged in
					// so log him out
					$query = $this->prepare('DELETE FROM `online_users` WHERE `userid`=?');
					if (!$this->execute($query, $id))
					{
						$this->logError('Could not remove already logged in user from online user table (userid=' . strval($id) . ').');
					}
				}
				
				// insert logged in user into online_users table
				$query = $this->prepare('INSERT INTO `online_users` (`userid`, `username`, `last_activity`) Values (?, ?, ?)');
				$date = new DateTime('now', new DateTimeZone('UTC'));
				$curDate = $date->format('Y-m-d H:i:s');
				$args = array($id, $name, $curDate);
				$this->execute($query, $args);
			}
		}
		
		
		public function registerAccount($moduleInstance, $externalLogin=false)
		{
			$uid = false;
			
			// lock tables before registering
			$this->SQL('LOCK TABLES `users` WRITE');
			$this->SQL('SET AUTOCOMMIT = 0');
			
			if ($externalLogin)
			{
				$query = $this->prepare('INSERT INTO `users` (`name`, `external_id`) VALUES (?,?)');
				$this->execute($query, array($moduleInstance->getName(), $moduleInstance->getID()));
			} else
			{
				$query = $this->prepare('INSERT INTO `users` (`name`) VALUES (?)');
				$this->execute($query, $moduleInstance->getName());
			}
			
			// retrieve last insertion id database independently
			// by asking for 1 id, beginning with last one
			$query = $this->SQL('SELECT `id` FROM `users` ORDER BY `id` DESC LIMIT 1');
			$row = $this->fetchRow($query);
			$uid = $row['id'];
			$this->free($query);
			
			// unlock tables after registering
			$this->SQL('UNLOCK TABLES');
			$this->SQL('COMMIT');
			$this->SQL('SET AUTOCOMMIT = 1');
			
			// create empty profile connected to new user id
			$query = $this->prepare('INSERT INTO `users_profile` (`userid`, `joined`) VALUES (?,?)');
			$date = new DateTime('now', new DateTimeZone('UTC'));
			$curDate = $date->format('Y-m-d H:i:s');
			$this->execute($query, array($uid, $curDate));
			
			return $uid;
		}
		
		
		public function updateUserName($internalID, $externalID=0, $name='LoginSystem:userOperations:updateUserNameFailed')
		{
			// update usernames based on data frome external logins
			
			// usernames based on internal login can not be updated
			if ($externalID === 0)
			{
				return;
			}
			
			// copy name so we have original name if an error occurs
			$newName = $name;
			
			// currently only bzbb module allowed as external login
			// so hardwire it until we have a database field for
			// external login module used
			// check if bzbb module is loaded
			if (array_search('bzbb', loginSystem::getModules()) === true)
			{
				$result = bzbb::updateUserName($externalID, $newName);
			}
			
			if ($newName !== false && strlen($newName) > 0)
			{
				$query = $this->prepare('UPDATE `users` SET `name`=:name WHERE `id`=:id LIMIT 1');
				$this->execute($query, array(':name' => array($newName, PDO::PARAM_STR, 50),
											 ':id' => array($internalID, PDO::PARAM_INT)));
				$this->free($query);
			}
			
			// report error if external login module (e.g. bzbb) failed doing its job
			if (isset($result) && $result === false)
			{
				$this->logError('login/classes/userOperations.php: updateUserName:'
								. ' bzbb::updateUserName(' . strval($externalID) . ', ' . strval($name) . ')'
								. ' returned false (unknown error).');
			}
		}
	}
?>
