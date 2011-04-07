<?php
	class pmDisplay extends pmSystemPM
	{
		function sanityCheck(&$confirmed)
		{
			global $tmpl;
			
			if (!hasEditPermission())
			{			
				// editing cancelled due to missing user permission
				$confirmed = 0;
				return 'noperm';
			}
			
			// no need to check for a key match if no content was supplied
			if (($confirmed > 0) && !randomKeyMatch($confirmed))
			{
				// editing cancelled due to random key mismatch
				$confirmed = 0;
				return 'nokeymatch';
			}
			
			return true;
		}
		
		function folderNav($folder)
		{
			global $tmpl;
			
			// show currently selected mail folder
			$tmpl->assign('curFolder', $folder);
/*
			
			if (strcmp($folder, 'inbox') === 0)
			{
				$tmpl->touchBlock('INBOX_SELECTED');
				$tmpl->touchBlock('OUTBOX_NOT_SELECTED');
			} else
			{
				$tmpl->touchBlock('INBOX_NOT_SELECTED');
				$tmpl->touchBlock('OUTBOX_SELECTED');
			}
*/
		}
		
		function showMail($folder, $id)
		{
			global $config;
			global $tmpl;
			global $user;
			global $db;
			
			// set the template
			$tmpl->setTemplate('PMView');
			$tmpl->assign('title', 'Mail #' . $id);
			
			// show currently selected mail folder
			$this->folderNav($folder);			
			
			// collect the necessary data
			$query = $db->prepare('SELECT `subject`'
								  . ',IF(`messages_storage`.`author_id`<>0'
								  . ',(SELECT `name` FROM `players` WHERE `id`=`author_id`),?) AS `author`'
								  . ',IF(`messages_storage`.`author_id`<>0,'
								  . '(SELECT `status` FROM `players` WHERE `id`=`author_id`),'
								  . "''" . ') AS `author_status`'
								  . ',`author_id`,`timestamp`,`message`,`messages_storage`.`from_team`'
								  . ',`messages_storage`.`recipients`'
								  . ' FROM `messages_storage`,`messages_users_connection`'
								  . ' WHERE `messages_storage`.`id`=`messages_users_connection`.`msgid`'
								  . ' AND `messages_users_connection`.`playerid`=?'
								  . ' AND `messages_users_connection`.`in_' . $folder . '`=' . "'1'"
								  . ' AND `messages_storage`.`id`=? LIMIT 1');
			$db->execute($query, array($config->value('displayedSystemUsername'), $user->getID(), $id));
			
			$rows = $db->fetchAll($query);
			$db->free($query);
			
			// create PM navigation
			$query = $db->prepare('SELECT `msgid` FROM `messages_users_connection`'
								  . ' WHERE `playerid`=? AND `msgid`<?'
								  . ' AND `in_' . $folder . '`=' . "'1'"
								  . ' ORDER BY `id` DESC LIMIT 1');
			$db->execute($query, array($user->getID(), intval($_GET['view'])));
			$prevMSG = $db->fetchAll($query);
			$db->free($query);
			
			if (count($prevMSG) > 0)
			{
				$tmpl->assign('prevMsg', $prevMSG[0]['msgid']);
			}
			unset($prevMSG);
			
			$query = $db->prepare('SELECT `msgid` FROM `messages_users_connection`'
								  . ' WHERE `playerid`=? AND `msgid`>?'
								  . ' AND `in_' . $folder . '`=' . "'1'"
								  . ' ORDER BY `id` LIMIT 1');
			$db->execute($query, array($user->getID(), intval($_GET['view'])));
			$nextMSG = $db->fetchAll($query);
			$db->free($query);
			if (count($nextMSG) > 0)
			{
				$tmpl->assign('nextMsg', $nextMSG[0]['msgid']);
			}
			unset($nextMSG);
			
			if (count($rows) < 1)
			{
				// keep the error message generic to avoid
				$tmpl->assign('errorMsg', 'This message either does not exist or you do not have permission to view the message.');
				$tmpl->display('NoPerm');
			}
			
			// create PM view
			$tmpl->assign('subject', $rows[0]['subject']);
			if (intval($rows[0]['author_id']) > 0)
			{
				$authorLink = ($rows[0]['from_team']) ?
							   '../Teams/?profile=' . intval($rows[0]['author_id'])
							   :
							   '../Players/?profile=' . intval($rows[0]['author_id']);
				$tmpl->assign('authorLink', $authorLink);
			}
			$tmpl->assign('authorName', $rows[0]['author']);
			$tmpl->assign('time', $rows[0]['timestamp']);
			if ($config->value('bbcodeLibAvailable'))
			{
				$tmpl->assign('content', $tmpl->encodeBBCode($rows[0]['message']));
			} else
			{
				$tmpl->assign('content',  htmlent($rows[0]['message']));
			}
			
			// collect recipient list
			$recipients = explode(' ', $rows[0]['recipients']);
			
			$fromTeam = strcmp($rows[0]['from_team'], '0') !== 0;
			if ($fromTeam)
			{
				$tmpl->assign('teamID', intval($recipients[0]));
			}
			
			$queryTeamName = $db->prepare('SELECT `name` FROM `teams` WHERE `id`=?');
			$queryPlayerName = $db->prepare('SELECT `name` FROM `players` WHERE `id`=?');
			$countRecipients = count($recipients) -1;
			array_walk($recipients, 'self::displayRecipient'
					   , array($fromTeam, $queryTeamName, $queryPlayerName, $countRecipients));
			$tmpl->assign('recipients', $recipients);
			
			$tmpl->assign('msgID', intval($_GET['view']));
			
			// mark the message as read for the current user
			$query = $db->prepare('UPDATE LOW_PRIORITY `messages_users_connection`'
								  . 'SET `msg_status`=' . "'read'"
								  . ' WHERE `msgid`=?'
								  . ' AND `in_' . $folder . '`=' . "'1'"
								  . ' AND `playerid`=?'
								  . ' LIMIT 1');
			$db->execute($query, array($id, $user->getID()));
		}
		
		function showMails($folder)
		{
			global $config;
			global $tmpl;
			global $user;
			global $db;
			
			// set the template
			$tmpl->setTemplate('PMList');
			
			if ($_SESSION['allow_add_messages'])
			{
				$tmpl->assign('showNewButton', true);
			}
			
			// show currently selected mail folder
			$this->folderNav($folder);
			
			// show the overview
			$offset = 0;
			if (isset($_GET['i']))
			{
				$offset = intval($_GET['i']);
			}
			$query = $db->prepare('SELECT `messages_storage`.`id`'
								  . ',`messages_storage`.`author_id`'
								  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `name` FROM `players` WHERE `id`=`author_id`)'
								  . ',?) AS `author`'
								  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `status` FROM `players` WHERE `id`=`author_id`),'
								  . "'" . "'" . ') AS `author_status`'
								  . ',`messages_users_connection`.`msg_status`'
								  . ',`messages_storage`.`subject`'
								  . ',`messages_storage`.`timestamp`'
								  . ',`messages_storage`.`from_team`'
								  . ',`messages_storage`.`recipients`'
								  . ' FROM `messages_users_connection`,`messages_storage`'
								  . ' WHERE `messages_storage`.`id`=`messages_users_connection`.`msgid`'
								  . ' AND `messages_users_connection`.`playerid`=?'
								  . ' AND `in_' . $folder . '`=' . "'1'"
								  . ' ORDER BY `messages_users_connection`.`id` DESC'
								  . ' LIMIT ' . $offset . ', 201');
			$db->execute($query, array($config->value('displayedSystemUsername'), $user->getID()));
			$rows = $db->fetchAll($query);
			$db->free($query);
			$n = count($rows);
			// last row is only a lookup row
			// to find out whether to display next messages button
			$showNextMSGButton = false;
			if ($n > 200)
			{
				$n = 200;
				$showNextMSGButton = true;
			}
			
			$messages = array();
			for ($i = 0; $i < $n; $i++)
			{
				$messages[$i]['userProfile'] = ($config->value('baseaddress')
												. 'Players/?profile=' . $rows[$i]['author_id']);
				$messages[$i]['userName'] = $rows[$i]['author'];
				
				if (strcmp($rows[$i]['msg_status'], 'new') === 0)
				{
					$messages[$i]['unread'] = true;
				}
				if (strcmp($folder, 'inbox') !== 0)
				{
					$messages[$i]['link'] = '?view=' . $rows[$i]['id'] . '&amp;folder=' . $folder;
				} else
				{
					$messages[$i]['link'] = '?view=' . $rows[$i]['id'];
				}
				$messages[$i]['subject'] = $rows[$i]['subject'];
				$messages[$i]['time'] = $rows[$i]['timestamp'];
				
				// collect recipient list
				$recipients = explode(' ', $rows[$i]['recipients']);
				$fromTeam = strcmp($rows[$i]['from_team'], '0') !== 0;
				$queryTeamName = $db->prepare('SELECT `name` FROM `teams` WHERE `id`=?');
				$queryPlayerName = $db->prepare('SELECT `name` FROM `players` WHERE `id`=?');
				$countRecipients = count($recipients) -1;
				array_walk($recipients, 'self::displayRecipient'
						   , array($fromTeam, $queryTeamName, $queryPlayerName, $countRecipients));
				$messages[$i]['recipients'] = $recipients;
				
			}
			$tmpl->assign('messages', $messages);
			
			if ($offset > 0 || $showNextMSGButton)
			{
				if ($offset > 0)
				{
					// show previous messages
					$tmpl->assign('offsetPrev', intval($offset-200));
				}
				if ($showNextMSGButton)
				{
					// show next messages
					$tmpl->assign('offsetNext', strval($offset+200));
				}
			}
		}
	}
?>
