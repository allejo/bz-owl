<?php
	class userOperations extends db
	{
		public function activateAccount($id)
		{
			$query = $this->prepare('UPDATE `players` SET `status`=:status WHERE `id`=:id');
			$this->execute($query, array(':status' => array('active', PDO::PARAM_STR, 6)));
			$this->free($query);
		}
		
		
		public function findIDByExternalLogin($id)
		{
			// internal id is an integer of length 11 by definition
			$internalID = 0;
			
			$query = $this->prepare('SELECT `id` FROM `players` WHERE `external_id`=:id LIMIT 1');
			// the id can be of any data type and of any length
			// just assume it's a 50 characters long string
			$this->execute($query, array(':id' => array($id, PDO::PARAM_STR, 50)));
			
			if ($row = $this->fetchRow($query))
			{
				$internalID = intval($row['id']);
			}
			$this->free($query);
			
			return $internalID;
		}
		
		
		public function getAccountStatus($id)
		{
			$query = $this->prepare('SELECT `status` FROM `players` WHERE `id`=:uid LIMIT 1');
			$this->execute($query, array(':id' => array($id, PDO::PARAM_INT)));
			
			if ($row = $this->fetchRow($query))
			{
				$status = $row['status'];
			}
			$this->free($query);
			
			return $status;
		}
		
		
		public function updateLastLogin($id)
		{
			$query = $this->prepare('UPDATE `players_profile` SET `last_login`=:lastLogin'
									. ' WHERE `playerid`=:uid LIMIT 1');
			$this->execute($query, array(':lastLogin' => array(date('Y-m-d H:i:s'), PDO::PARAM_STR),
										 ':uid' => array($id, PDO::PARAM_INT)));
			
			if ($row = $this->fetchRow($query))
			{
				$status = $row['status'];
			}
			$this->free($query);
			
			return $status;
		}
		
		public function sendWelcomeMessage($id)
		{
			global $config;
			
			
			$subject = ($config->getValue('login.welcome.subject') ?
						$config->getValue('login.welcome.subject') :
						'Welcome');
			$content = ($config->getValue('login.welcome.content') ?
						$config->getValue('login.welcome.content') :
						'Welcome and thanks for registering at this website!' . "\n"
									. 'In the FAQ you can find the most important informations'
									. ' about organising and playing matches.' . "\n\n"
									. 'See you on the battlefield.');
			// prepare welcome message
			include(dirname(dirname(__FILE__)) . '/pmSystem/classes/PMComposer.php');
			$pmComposer = new pmComposer();
			$pmComposer->setSubject($subject);
			$pmComposer->setContent($content);
			$pmComposer->setTimestamp(date('Y-m-d H:i:s'));
			$pmComposer->addUserID($id);
			
			// send it
			$pmComposer->send();
		}
	}
?>
