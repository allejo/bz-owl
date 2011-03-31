sendPM.php<?php
	class pmSystemAddPM extends pmSystemPM
	{
		private $editor;
		private $PMComposer;
		public $randomKeyName = 'pmSystemAddPM';
		
		function __construct()
		{
			global $user;
			global $tmpl;
			
			
			$this->PMComposer = new PMComposer();
			
			// FIXME: fallback to default permission name until add-on system is completly implemented
			$entry_add_permission = 'allow_add_news';
			
			if (!$user->getPermission($entry_add_permission))
			{
				// no permissions to write a new private message
				$tmpl->display('NoPerm');
				die();
			}
			
			
			include(dirname(dirname(dirname(__FILE__))) . '/classes/editor.php');
			$this->editor = new editor($this);
			$this->editor->addFormatButtons('content');
			$this->editor->edit();
			
			$tmpl->assign('title', 'New PM');
			$tmpl->display('PMAdd');
		}
		
		function insertEditText($readonly=false)
		{
			global $tmpl;
			global $page_title;
			global $author;
			global $last_modified;
			global $config;
			global $user;
			global $db;
			
			
			$content = array();
			$content['raw_msg'] = isset($_POST['content']) && (strlen($_POST['content']) > 0)
								  ?  strval($_POST['content']) : 'Enter message here';
			$content['subject'] = isset($_POST['subject']) && (strlen($_POST['subject']) > 0)
								  ? strval($_POST['subject']) : 'Enter subject here';
			
			if ($readonly || isset($_POST['confirmationStep']))
			{
				// data passed to form -> use it
				
				$query = $db->prepare('SELECT `name` FROM `players` WHERE `id`=? LIMIT 1');
				$db->execute($query, $user->getID());
				$content['author'] = $db->fetchRow($query);
				$db->free($query);
				
				$content['playerRecipients'] = $this->PMComposer->getPlayerNames();
				$content['timestamp'] = date('Y-m-d H:i:s');
			} else
			{
				// new message -> recipient players yet
				$content['playerRecipients'] = array();
			}
			
			$tmpl->assign('teamRecipients', $this->PMComposer->getTeamNames());
			
			switch($readonly)
			{
				case true:
					$tmpl->assign('playerRecipients', $content['playerRecipients']);
					$tmpl->assign('subject',  htmlent($content['subject']));
					$tmpl->assign('authorName',  htmlent($content['author']['name']));
					$tmpl->assign('time',  htmlent($content['timestamp']));
					$tmpl->assign('rawContent', htmlent($content['raw_msg']));
					if ($config->value('bbcodeLibAvailable'))
					{
						$tmpl->assign('content',  $tmpl->encodeBBCode($content['raw_msg']));
					} else
					{
						$tmpl->assign('content',  htmlent($content['raw_msg']));
					}
					$tmpl->assign('showPreview', true);
					// overwrite editor's default text ('Write changes')
					$tmpl->assign('submitText', 'Send PM');
					break;
				
				default:
					$tmpl->assign('playerRecipients', $content['playerRecipients']);
					$tmpl->assign('rawContent', htmlspecialchars($content['raw_msg']
																 , ENT_COMPAT, 'UTF-8'));
					$tmpl->assign('subject', htmlspecialchars($content['subject']
															   , ENT_COMPAT, 'UTF-8'));
					// display the formatting buttons addded by addFormatButtons
					$this->editor->showFormatButtons();
					break;
			}
		}
		
		function randomKeyMatch(&$confirmed)
		{
			global $site;
			
			
			$randomKeyValue = '';
			$randomKeyName = '';
			
			if (isset($_POST['key_name']))
			{
				$randomKeyName = htmlent_decode($_POST['key_name']);
				
				if (isset($_POST[$randomKeyName]))
				{
					$randomKeyValue = htmlent_decode($_POST[$randomKeyName]);
				}
			}
			
			return $randomkeysmatch = $site->validateKey($randomKeyName, $randomKeyValue);
		}
		
		function sanityCheck(&$confirmed)
		{
			global $user;
			global $tmpl;
			
			
			// < 0: undefined, 0: edit screen, 1: preview, 2: send, > 2: undefined
			if ($confirmed < 0 || $confirmed > 2)
			{
				// changed undefined values to a defined state
				$confirmed = 0;
			}
			
			if ($confirmed > 0 || isset($_POST['editPageAgain']))
			{
				// no need to check for a key match if no content was supplied
				if (!$this->randomKeyMatch($confirmed))
				{
					// editing cancelled due to random key mismatch
					$confirmed = 0;
					return 'nokeymatch';
				}
				
				
				// add all set team recipients
				$i = 0;
				while (isset($_POST['teamRecipient' . $i]))
				{
					// user requested removal of a recipient -> do not send now
					if (isset($_POST['removeTeamRecipient' . $i]))
					{
						$confirmed = 0;
					}
					
					// exclude team recipients that are requested to be removed
					if (isset($_POST['teamRecipient' . $i]) && !(isset($_POST['removeTeamRecipient' . $i])))
					{
						$this->PMComposer->addTeamName($_POST['teamRecipient' . $i]
														 , $confirmed > 0
														 && !isset($_POST['addTeamRecipient'])
														 && !isset($_POST['addPlayerRecipient'])
														 && !isset($_POST['editPageAgain']));
					}
					$i++;
				}
				
				// add new team recipient if requested and do not send the message
				if (isset($_POST['teamRecipient']) && isset($_POST['addTeamRecipient']))
				{
					$confirmed = 0;
					$this->PMComposer->addTeamName($_POST['teamRecipient'], $confirmed > 0);
				}
				
				
				// add all set player recipients
				$i = 0;
				while (isset($_POST['playerRecipient' . $i]))
				{
					// user requested removal of a recipient -> do not send now
					if (isset($_POST['removePlayerRecipient' . $i]))
					{
						$confirmed = 0;
					}
					
					// exclude player recipients that are requested to be removed
					if (isset($_POST['playerRecipient' . $i]) && !(isset($_POST['removePlayerRecipient' . $i])))
					{
						$this->PMComposer->addPlayerName($_POST['playerRecipient' . $i]
														 , $confirmed > 0
														 && !isset($_POST['addPlayerRecipient'])
														 && !isset($_POST['editPageAgain']));
					}
					$i++;
				}
				
				// add new player recipient if requested and do not send the message
				if (isset($_POST['playerRecipient']) && isset($_POST['addPlayerRecipient']))
				{
					$confirmed = 0;
					$this->PMComposer->addPlayerName($_POST['playerRecipient'], $confirmed > 0);
				}
			}
			
			
			if ($confirmed > 0 && $this->PMComposer->countPlayers() < 1 && $this->PMComposer->countTeams() < 1)
			{
				$tmpl->assign('MSG', 'A PM can not be sent without any recipients set.');
				$confirmed = 0;
			}
			
			return true;
		}
		
		function writeContent()
		{
			global $user;
			include(dirname(__FILE__) . '/sendPM.php');
			
			
			return send($user->getID());
		}
	}
	
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
		
		if (strlen($message) === 0)
		{
			$error = '<p>You must specify a message text in order to send a message.</p>';
			return false;
		}
		
		$recipients = $this->players;
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
			$db->execute($query, array($author_id, htmlent($this->subject), $this->timestamp, $this->content, $from_team, $recipientsSQL));
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
