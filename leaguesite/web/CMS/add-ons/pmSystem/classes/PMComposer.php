<?php
	class PMComposer
	{
		private $users = array();
		private $teams = array();
		private $subject = 'Enter subject here';
		private $content = 'Enter message here';
		private $timestamp = '';
		private $usernameQuery;
		private $teamnameQuery;
		
		
		function __construct()
		{
			global $db;
		
			$this->timestamp = date('Y-m-d H:i:s');
			$this->usernameQuery = $db->prepare('SELECT `id`, `name` FROM `players` WHERE `name`=? AND `status`=? LIMIT 1');
			$this->teamnameQuery = $db->prepare('SELECT `teams`.`id`, `name` FROM `teams` LEFT JOIN `teams_overview`'
												. ' ON `teams`.`id`=`teams_overview`.`teamid`'
												. ' WHERE `name`=? AND `deleted` < 2 LIMIT 1');
		}
		
		private function lookupUserName(&$userID)
		{
			global $db;
			
			$query = $db->prepare('SELECT `name` FROM `players` WHERE `id`=? and `status`=? LIMIT 1');
			$db->execute($query, array($userID, 'active'));
			
			$row = $db->fetchRow($query);
			$db->free($query);
			
			return $row ? $row['name'] : false;
		}
		
		private function lookupTeamName(&$teamID)
		{
			global $db;
			
			$query = $db->prepare('SELECT `name` FROM `teams` LEFT JOIN `teams_overview`'
								  . ' ON `teams`.`id`=`teams_overview`.`teamid`'
								  . ' WHERE `teamid`=? AND `deleted` < 2 LIMIT 1');
			$db->execute($query, $teamID);
			
			$row = $db->fetchRow($query);
			$db->free($query);
			
			return $row ? $row['name'] : false;
		}
		
		function getSubject()
		{
			return $this->subject;
		}
		
		function setSubject($subject)
		{
			$this->subject = $subject;
		}
		
		
		function getContent()
		{
			return $this->content;
		}
		
		function setContent($content)
		{
			$this->content = $content;
		}
		
		
		function getTimestamp()
		{
			return $this->timestamp;
		}
		
		function setTimestamp($timestamp)
		{
			$this->timestamp = $timestamp;
		}
		
		
		function addUserID($id, $lookupName=false)
		{
			$id = intval($id);
			$userID = array('id' => $id);
			if ($lookupName)
			{
				if ($name = $this->lookupUserName($id))
				{
					$userID['name'] = $name;
				}
			}
			$userID['link'] = "../Players/$id";
			$this->users[] = $userID;
		}
		
		function addUserName($recipientName, $preview=false)
		{
			global $db;
		
		
			// remove double entries, invalid names etc
		
			// lookup if username is already in recipient array
			$alreadyAdded = false;
			foreach ($this->users as $oneRecipient)
			{
				// assume case insensitive usernames
				if (strcasecmp($oneRecipient['name'], $recipientName) === 0)
				{
					$alreadyAdded = true;
				}
			}
			
			if (!$alreadyAdded)
			{
				$db->execute($this->usernameQuery, array(htmlent($recipientName), 'active'));
				if (($row = $db->fetchRow($this->usernameQuery))
					&& isset($row['name'])
					&& (strcasecmp($recipientName, $row['name']) === 0))
				{
					// assume case preserving usernames
					$this->users[] = array('id'=>$row['id'],
						'name'=>$row['name'],
						'link' => '../Players/?profile=' . $row['id']);
					unset($row);
				}
				$db->free($this->usernameQuery);
			}
		}
		
		function addTeamID($id, $lookupName=false)
		{
			$id = intval($id);
			$teamID = array('id' => $id);
			if ($lookupName)
			{
				if ($name = $this->lookupTeamName($id))
				{
					$teamID['name'] = $name;
				}
			}
			$teamID['link'] = "../Teams/$id";
			$this->teams[] = $teamID;
		}
		
		function addTeamName($recipientName, $preview=false)
		{
			global $db;
			
			
			// remove double entries, invalid names etc
			
			// lookup if username is already in recipient array
			$alreadyAdded = false;
			foreach ($this->teams as $oneRecipient)
			{
				// assume case insensitive usernames
				if (strcasecmp($oneRecipient['name'], $recipientName) === 0)
				{
					$alreadyAdded = true;
				}
			}
			
			if (!$alreadyAdded)
			{
				$db->execute($this->teamnameQuery, htmlent($recipientName));
				if (($row = $db->fetchRow($this->teamnameQuery))
					&& isset($row['name'])
					&& (strcasecmp($recipientName, $row['name']) === 0))
				{
					// assume case preserving usernames
					$this->teams[] = array('id'=>$row['id'],
						'name'=>$row['name'],
						'link' => '../Teams/?profile=' . $row['id']);
					unset($row);
				}
				$db->free($this->teamnameQuery);
			}
		}
		
		
		function countUsers()
		{
			return count($this->users);
		}
		
		function countTeams()
		{
			return count($this->teams);
		}
		
		
		function getUserIDs()
		{
			global $db;
			
			
			// initialise variables
			$recipientIDs = array();
			$query = $db->prepare('SELECT `id` FROM `players` WHERE `name`=?');
			
			// no need for queries to find out id's if id of first item already set
			if ((count($this->users) > 0) && isset($this->users[0]['id']))
			{
				foreach ($this->users as $oneRecipient)
				{
					$recipientIDs[] = $oneRecipient['id'];
				}
				
				return $recipientIDs;
			}
			
			// id's not in array, have to look them up
			foreach ($this->users as $oneRecipient)
			{
				$db->execute($query, $oneRecipient['name']);
				
				if ($row = $db->fetchRow($query))
				{
					$recipientIDs[] = $row['id'];
				}
				
				$db->free($query);
			}
			
			return $recipientIDs;
		}
		
		function getUsersInTeam($teamid)
		{
			global $db;
			
			$query = $db->prepare('SELECT `id` FROM `players` WHERE `teamid`=?');
			$db->execute($query, intval($teamid));
			
			// build a new array that contains all the user id's
			$result = array();
			while ($row = $db->fetchRow($query))
			{
				$result[] = $row['id'];
			}
			$db->free($query);
			
			return $result;
		}
		
		function getUserNames()
		{
			return $this->users;
		}
		
		function getTeamNames()
		{
			return $this->teams;
		}
		
		// send private message to players and teams
		// if an error occurs, $error will contain its description and the function will return false
		function send($author_id=0, $ReplyToMSGID=0)
		{
			global $config;
			global $db;
			
			
			// remove duplicates
			if ($this->removeDuplicates($this->users) || $this->removeDuplicates($this->teams))
			{
				// back to overview to let them check
				return '<p>Some double entries were removed. Please check your recipients.<p>';
			}
			
			if (strlen($this->content) === 0)
			{
				$return = '<p>You must specify a message text in order to send a message.</p>';
			}
			
			$recipients = array();
			foreach ($this->users as $player)
			{
				$recipients[] = $player['id'];
			}
			
			// add the players belonging to the specified teams to the recipients array
			foreach ($this->teams as $teamid)
			{
				$tmp_players = $this->getUsersInTeam($teamid['id']);
				foreach ($tmp_players as $userid)
				{
					$recipients[] = $userid;
				}
			}
			
			// put message in database
			$query = $db->prepare('INSERT INTO `pmsystem_msg_storage`'
								  . ' (`author_id`, `subject`, `timestamp`, `message`)'
								  . ' VALUES (?, ?, ?, ?)');
			
			// lock tables for critical section
			$db->SQL('LOCK TABLES `pmsystem_msg_storage` WRITE');
			$db->SQL('SET AUTOCOMMIT = 0');
			
			// do the insert
			$db->execute($query, array($author_id, htmlent($this->subject), $this->timestamp, $this->content));
			$db->free($query);
			$db->SQL('COMMIT');
			
			// find out generated id
			$queryLastID = $db->SQL('SELECT `id` FROM `pmsystem_msg_storage` ORDER BY `id` DESC LIMIT 1');
			$rowId = $db->fetchRow($queryLastID);
			$rowId = intval($rowId['id']);
			$db->free($queryLastID);
			$db->SQL('COMMIT');
			
			// unlock tables as critical section passed
			$db->SQL('UNLOCK TABLES');
			$db->SQL('SET AUTOCOMMIT = 1');
			
			
			// add teams as visible recipients
			$query = $db->prepare('INSERT INTO `pmsystem_msg_recipients_teams`'
								  . '(`msgid`, `teamid`)'
								  . 'VALUES (?, ?)');
			foreach ($this->teams as $team)
			{
					$db->execute($query, array($rowId, $team['id']));
					$db->free($query);
			}
			unset($team);
			
			// add users as visible recipients
			// be careful to not overwrite global variable $user
			$query = $db->prepare('INSERT INTO `pmsystem_msg_recipients_users`'
								  . '(`msgid`, `userid`)'
								  . 'VALUES (?, ?)');
			$userIDs = $this->getUserIDs();
			foreach ($userIDs as $userID)
			{
					$db->execute($query, array($rowId, $userID));
					$db->free($query);
			}
			unset($userID);
			
			foreach (array_unique($recipients, SORT_NUMERIC) as $recipient)
			{
				// put message in people's inbox
				if ($ReplyToMSGID > 0)
				{
					// this is a reply
					$query = $db->prepare('INSERT INTO `pmsystem_msg_users`'
										  . ' (`msgid`, `userid`, `folder`, `msg_replied_to_msgid`)'
										  . ' VALUES (?, ?, ?, ?)');
					$db->execute($query, array($rowId, $recipient, 'inbox', $ReplyToMSGID));
				} else
				{
					// this is a new message
					$query = $db->prepare('INSERT INTO `pmsystem_msg_users`'
										  . ' (`msgid`, `userid`, `folder`)'
										  . ' VALUES (?, ?, ?)');
					$db->execute($query, array($rowId, $recipient, 'inbox'));
				}
				$db->free($query);
			}
			
			// put message in sender's outbox if sent by a human
			if ($author_id > 0)
			{
				$query = $db->prepare('INSERT INTO `pmsystem_msg_users`'
									  . ' (`msgid`, `userid`, `folder`, `msg_status`)'
									  . ' VALUES (?, ?, ?, ?)');
				$db->execute($query, array($rowId, $author_id, 'outbox', 'read'));
			}
			
			return true;
		}
		
		function removeDuplicates(array &$someArray)
		{
			$filtered = array();
			$seen = array();
			foreach ($someArray as $element)
			{
				if (!isset($seen[$element['id']]))
				{
					$seen[$element['id']] = true;
					$filtered[] = $element;
				}
			}

			if (count($someArray) !== count($filtered))
			{
				// duplicates were removed
				$someArray = $filtered;
				return true;
			}
			
			// neither duplicates found nor removed
			return false;
		}
		
		function playersInTeam($teamid)
		{
			global $db;
			
			$teamid = intval($teamid);
			$result = array();
			
			if ($teamid < 1)
			{
				// no valid team id -> return empty player list
				return $result;
			}
			
			$query = $db->prepare('SELECT `id` FROM `players` WHERE `teamid`=?');
			$db->execute($query, $teamid);
			
			while ($row = $db->fetchRow($query))
			{
				$result[] = $row['id'];
			}
			
			return $result;
		}
	}
?>
