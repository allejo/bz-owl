<?php
	class PMComposer
	{
		private $players = array();
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
			$this->usernameQuery = $db->prepare('SELECT `id`, `name` FROM `players` WHERE `name`=? LIMIT 1');
			$this->teamnameQuery = $db->prepare('SELECT `id`, `name` FROM `teams` WHERE `name`=? LIMIT 1');
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
		
		
		function addPlayerID($id)
		{
			$this->players[] = array('id' => $id);
		}
		
		function addPlayerName($recipientName, $preview=false)
		{
			global $db;
			
			
			// remove double entries, invalid names etc
			
			// lookup if username is already in recipient array
			$alreadyAdded = false;
			foreach ($this->players as $oneRecipient)
			{
				// assume case insensitive usernames
				if (strcasecmp(($preview) ? $oneRecipient['name'] : $oneRecipient,
							   $recipientName) === 0)
				{
					$alreadyAdded = true;
				}
			}
			
			if (!$alreadyAdded)
			{
				$db->execute($this->usernameQuery, htmlent($recipientName));
				if ($row = $db->fetchRow($this->usernameQuery))
				{
					// assume case preserving usernames
					$this->players[] = ($preview) ? array('id'=>$row['id'],
														  'name'=>$row['name'],
														  'link' => '../Players/?profile=' . $row['id'])
					: $row['name'];
					unset($row);
				}
				$db->free($this->usernameQuery);
			}
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
				if (strcasecmp(($preview) ? $oneRecipient['name'] : $oneRecipient,
							   $recipientName) === 0)
				{
					$alreadyAdded = true;
				}
			}
			
			if (!$alreadyAdded)
			{
				$db->execute($this->teamnameQuery, htmlent($recipientName));
				if ($row = $db->fetchRow($this->teamnameQuery))
				{
					// assume case preserving usernames
					$this->teams[] = ($preview) ? array('id'=>$row['id'],
														'name'=>$row['name'],
														'link' => '../Teams/?profile=' . $row['id'])
					: $row['name'];
					unset($row);
				}
				$db->free($this->teamnameQuery);
			}
		}
		
		
		function countPlayers()
		{
			return count($this->players);
		}
		
		function countTeams()
		{
			return count($this->teams);
		}
		
		
		function getPlayerIDs()
		{
			global $db;
			
			
			// initialise variables
			$recipientIDs = array();
			$query = $db->prepare('SELECT `id` FROM `players` WHERE `name`=?');
			
			// no need for queries to find out id's if id of first item already set
			if ((count($this->players) > 0) && isset($this->players[0]['id']))
			{
				foreach ($this->players as $oneRecipient)
				{
					$recipientIDs[] = $oneRecipient['id'];
				}
				
				return $recipientIDs;
			}
			
			// id's not in array, have to look them up
			foreach ($this->players as $oneRecipient)
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
		
		function getPlayerNames()
		{
			return $this->players;
		}
		
		function getTeamNames()
		{
			return $this->teams;
		}
	
	// send private message to players and teams
	// if an error occurs, $error will contain its description and the function will return false
	function send($author_id=0, $from_team=0,
				  $msg_replied_team=0, $ReplyToMSGID=0,
				  &$error='')
	{
		global $config;
		global $user;
		global $db;
		
		
		// remove duplicates
		if (removeDuplicates($this->players) || removeDuplicates($this->teams))
		{
			// back to overview to let them check
			$error = '<p>Some double entries were removed. Please check your recipients.<p>';
			return false;
		}
		
		if (strlen($this->content) === 0)
		{
			$error = '<p>You must specify a message text in order to send a message.</p>';
			return false;
		}
		
		$recipients = array();
		foreach ($this->players as $player)
		{
			$recipients[] = $player['id'];
		}

		// add the players belonging to the specified teams to the recipients array
		foreach ($this->teams as $teamid)
		{
			$tmp_players = playersInTeam($teamid);
			foreach ($tmp_players as $playerID)
			{
				$recipients[] = $playerID;
			}
		}
		
		// remove possible duplicates
		@removeDuplicates($recipients);
		
		// put message in database
		$query = $db->prepare('INSERT INTO `messages_storage`'
							  . ' (`author_id`, `subject`, `timestamp`, `message`, `from_team`, `recipients`)'
							  . ' VALUES (?, ?, ?, ?, ?, ?)');
		
		// prepare recipients for SQL statement
		$recipientsSQL = is_array($recipients) ? implode(' ', $recipients) : $recipients;
		
		foreach ($recipients as $recipient)
		{
			// lock tables for critical section
			$db->SQL('LOCK TABLES `messages_storage` WRITE');
			$db->SQL('SET AUTOCOMMIT = 0');
			
			// do the insert
			$db->execute($query, array($author_id, htmlent($this->subject), $this->timestamp, $this->content, $from_team, $recipientsSQL));
			$db->free($query);
			$db->SQL('COMMIT');
			
			// find out generated id
			$queryLastID = $db->SQL('SELECT `id` FROM `messages_storage` ORDER BY `id` DESC LIMIT 1');
			$rowId = intval($db->fetchRow($queryLastID));
			$db->free($queryLastID);
			$db->SQL('COMMIT');
			
			// unlock tables as critical section passed
			$db->SQL('UNLOCK TABLES');
			$db->SQL('SET AUTOCOMMIT = 1');
			
			// put message in people's inbox
			if ($ReplyToMSGID > 0)
			{
				// this is a reply
				$query = $db->prepare('INSERT INTO `messages_users_connection`'
									  . '(`msgid`, `playerid`, `in_inbox`, `in_outbox`, `msg_replied_to_msgid)'
									  . 'VALUES (?, ?, ?, ?, ?)');
				$db->execute($query, array($rowId, $recipient, 1, 0, $ReplyToMSGID));
				$db->free($query);
			} else
			{
				// this is a new message
				$query = $db->prepare('INSERT INTO `messages_users_connection`'
									  . ' (`msgid`, `playerid`, `in_inbox`, `in_outbox`)'
									  . ' VALUES (?, ?, ?, ?)');
				$db->execute($query, array($rowId, $recipient, 1, 0));
				$db->free($query);
			}
			
			// put message in sender's outbox
			if ($author_id > 0)
			{
				// system did not send the message but a human
				$query = $db->prepare('INSERT INTO `messages_users_connection`'
									  . ' (`msgid`, `playerid`, `in_inbox`, `in_outbox`)'
									  . ' VALUES (?, ?, ?, ?)');
				$db->execute($query, array($rowId, $author_id, 0, 1));
			}
		}
		
		return true;
	}
	}
?>
