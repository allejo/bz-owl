<?php
	// send private message to players and teams
	// if an error occurs, $error will contain its description and the function will return false
	function sendPM($players=array(), $teams=array(),
					$author_id=0, $subject='Enter subject here',
					$timestamp, $message, $from_team=0,
					$msg_replied_team=0, $ReplyToMSGID=0, &$error='')
	{
		global $config;
		global $user;
		global $db;
		
		
		// remove duplicates
		if (removeDuplicates($players) || removeDuplicates($teams))
		{
			// back to overview to let them check
			$error = '<p>Some double entries were removed. Please check your recipients.<p>';
			return false;
		}
		
		if (strlen($message) === 0)
		{
			$error = '<p>You must specify a message text in order to send a message.</p>';
			return false;
		}
		
		$recipients = $players;
		// add the players belonging to the specified teams to the recipients array
		foreach ($teams as $teamid)
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
		if (is_array($recipients))
		{
			$recipientsSQL = implode(' ', $recipients);
		} else
		{
			$recipientsSQL = $recipients;
		}
		   
		foreach ($recipients as $recipient)
		{
			$db->execute($query, array($author_id, htmlent($subject), $timestamp, $message, $from_team, $recipientsSQL));
			$db->free($query);
			$rowId = $db->lastInsertId();
			
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
	
	
	function removeDuplicates(&$someArray)
	{
		$dup_check = count($someArray);
		// array_unique is case sensitive, thus the loading of name from database
		$players = array_unique($someArray);
		if (!($dup_check === (count($someArray))))
		{
			// duplicates were removed
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
?>
