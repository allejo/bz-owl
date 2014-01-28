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
		}
		
		function showMail($folder, $id)
		{
			global $config;
			global $tmpl;
			global $db;
			
			// set the template
			$tmpl->setTemplate('PMView');
			$tmpl->assign('title', 'Mail #' . $id);
			
			$id = 0;
			if (isset($_GET['view']))
			{
				$id = intval($_GET['view']);
			} elseif (isset($_GET['delete']))
			{
				$id = intval($_GET['delete']);
			} else
			{
				$tmpl->assign('errorMsg', 'You did not specify a message id to view');
				$tmpl->display('NoPerm');
				exit();
			}
			
			// show currently selected mail folder
			$this->folderNav($folder);
			
			// collect the necessary data
			$query = $db->prepare('SELECT `id`,`author_id`,`subject`,`timestamp`,`message`,`msg_status`,'
								  . ' IF(`pmsystem_msg_storage`.`author_id`<>0,'
								  . ' (SELECT `name` FROM `users` WHERE `id`=`author_id`),?) AS `author`'
								  . ' FROM `pmsystem_msg_storage`, `pmsystem_msg_users`'
								  . ' WHERE `pmsystem_msg_users`.`userid`=?'
								  . ' AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid`'
								  . ' AND `folder`=?'
								  . ' AND `pmsystem_msg_storage`.`id`=?'
								  . ' ORDER BY `pmsystem_msg_storage`.`id` DESC'
								  . ' LIMIT 1');
			$db->execute($query, array($config->getValue('displayedSystemUsername'), user::getCurrentUserId(), $folder, $id));
			
			$rows = $db->fetchAll($query);
			$db->free($query);
			
			// create PM navigation
			$query = $db->prepare('SELECT `msgid` FROM `pmsystem_msg_users`'
								  . ' WHERE `userid`=? AND `msgid`<?'
								  . ' AND `folder`=?'
								  . ' ORDER BY `msgid` DESC LIMIT 1');
			$db->execute($query, array(user::getCurrentUserId(), $id, $folder));
			
			$prevMSG = $db->fetchAll($query);
			$db->free($query);
			
			if (count($prevMSG) > 0)
			{
				$tmpl->assign('prevMsg', $prevMSG[0]['msgid']);
			}
			unset($prevMSG);
			
			$query = $db->prepare('SELECT `msgid` FROM `pmsystem_msg_users`'
								  . ' WHERE `userid`=? AND `msgid`>?'
								  . ' AND `folder`=?'
								  . ' ORDER BY `msgid` LIMIT 1');
			$db->execute($query, array(user::getCurrentUserId(), $id, $folder));
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
				
				exit();
			}
			
			// create PM view
			$tmpl->assign('subject', $rows[0]['subject']);
			
			if (intval($rows[0]['author_id']) > 0)
			{
				$tmpl->assign('authorLink', '../Players/?profile=' . intval($rows[0]['author_id']));
			}
			$tmpl->assign('authorName', $rows[0]['author']);
			
			
			// prepare recipients queries
			$usersQuery = $db->prepare('SELECT `userid`,`name`'
									   . ' FROM `pmsystem_msg_recipients_users` LEFT JOIN `users`'
									   . ' ON `pmsystem_msg_recipients_users`.`userid`=`users`.`id`'
									   . ' WHERE `msgid`=?');
			$teamsQuery = $db->prepare('SELECT `teamid`,`name`'
									   . ' FROM `pmsystem_msg_recipients_teams` LEFT JOIN `teams`'
									   . ' ON `pmsystem_msg_recipients_teams`.`teamid`=`teams`.`id`'
									   . ' WHERE `msgid`=?');
			
			// find out users in recipient list
			$db->execute($usersQuery, $rows[0]['id']);
			$userRecipients = array();
			while ($row = $db->fetchRow($usersQuery))
			{
				$userRecipients[] = array('link' => '../Players/?profile=' . intval($row['userid']),
										  'name' => $row['name']);
			}
			$db->free($usersQuery);
			
			if (isset($userRecipients[0]))
			{
				$tmpl->assign('userRecipients', $userRecipients);
			}
			
			// find out teams in recipient list
			$db->execute($teamsQuery, $rows[0]['id']);
			$teamRecipients = array();
			while ($row = $db->fetchRow($teamsQuery))
			{
				$teamRecipients[] = array('link' => '../Teams/?profile=' . intval($row['teamid']),
										  'name' => $row['name']);
			}
			$db->free($teamsQuery);
			
			if (isset($teamRecipients[0]))
			{
				$tmpl->assign('teamRecipients', $teamRecipients);
			}
			
			// compute if a 'reply to all' button should be shown (more than 1 recipient)
			$tmpl->assign('showReplyToAll', (count($userRecipients) > 1 || count($teamRecipients) > 0));
			unset($userRecipients);
			unset($teamRecipients);
			
			
			$tmpl->assign('time', $rows[0]['timestamp']);
			if ($config->getValue('bbcodeLibAvailable'))
			{
				$tmpl->assign('content', $tmpl->encodeBBCode($rows[0]['message']));
			} else
			{
				$tmpl->assign('content',  htmlent($rows[0]['message']));
			}
			
			$tmpl->assign('msgID', $id);
			
			// mark the message as read for the current user
			$query = $db->prepare('UPDATE LOW_PRIORITY `pmsystem_msg_users`'
								  . 'SET `msg_status`=?'
								  . ' WHERE `msgid`=?'
								  . ' AND `folder`=?'
								  . ' AND `userid`=?'
								  . ' LIMIT 1');
			$db->execute($query, array('read', $id, $folder, user::getCurrentUserId()));
		}
		
		function showMails($folder)
		{
			global $config;
			global $tmpl;
			global $db;
			
			$max_per_page = 200;	// FIXME: move to settings.php (or define per theme)
			
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
			
			// It is arguably a PDO bug, but LIMIT and OFFSET values require named
			// parameters rather than the simple use of '?' in the SQL statement.
			// get the list of private messages to be displayed (+1 one hidden due to next button)
			// userid requirement ensures user only sees the messages he's allowed to
			$query = $db->prepare('SELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`,'
								  . ' IF(`pmsystem_msg_storage`.`author_id`<>0,'
								  . ' (SELECT `name` FROM `users` WHERE `id`=`author_id`),:author) AS `author`'
								  . ' FROM `pmsystem_msg_storage`, `pmsystem_msg_users`'
								  . ' WHERE `pmsystem_msg_users`.`userid`=:userid'
								  . ' AND `pmsystem_msg_storage`.`id`=`pmsystem_msg_users`.`msgid`'
								  . ' AND `folder`=:folder'
								  . ' ORDER BY `pmsystem_msg_storage`.`id` DESC'
								  . ' LIMIT :limit OFFSET :offset');
			$params = array();
			$params[':author'] = array($config->getValue('displayedSystemUsername'), PDO::PARAM_STR);
			$params[':userid'] = array(user::getCurrentUserId(), PDO::PARAM_INT);
			$params[':folder'] = array($folder, PDO::PARAM_STR);
			$params[':limit'] = array($max_per_page+1, PDO::PARAM_INT);
			$params[':offset'] = array($offset, PDO::PARAM_INT);
			$db->execute($query, $params);
			$rows = $db->fetchAll($query);
			$db->free($query);
			$n = count($rows);
			
			// last row is only a lookup row
			// to find out whether to display next messages button
			$showNextMSGButton = false;
			if ($n > $max_per_page)
			{
				$n = $max_per_page;
				$showNextMSGButton = true;
			}
			
			
			// prepare recipients queries outside of the loop
			$usersQuery = $db->prepare('SELECT `userid`,`name`'
									   . ' FROM `pmsystem_msg_recipients_users` LEFT JOIN `users`'
									   . ' ON `pmsystem_msg_recipients_users`.`userid`=`users`.`id`'
									   . ' WHERE `msgid`=?');
			$teamsQuery = $db->prepare('SELECT `teamid`,`name`'
									   . ' FROM `pmsystem_msg_recipients_teams` LEFT JOIN `teams`'
									   . ' ON `pmsystem_msg_recipients_teams`.`teamid`=`teams`.`id`'
									   . ' WHERE `msgid`=?');
			
			$messages = array();
			for ($i = 0; $i < $n; $i++)
			{
				// only set up a link to user profile if user has no reserved id
				// 0 is an internal system user that shares its id with not logged-in users
				if (intval($rows[$i]['author_id']) > 0)
				{
					$messages[$i]['userProfile'] = ($config->getValue('baseaddress')
													. 'Players/?profile=' . $rows[$i]['author_id']);
				}
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
				
				$users = array();
				$db->execute($usersQuery, $rows[$i]['id']);
				while ($row = $db->fetchRow($usersQuery))
				{
					$users[] = array('id' => $row['userid'], 'name' => $row['name'],
									 'link' => '../Players/?profile=' . $row['userid']);
				}
				$db->free($usersQuery);
				
				$teams = array();
				$db->execute($teamsQuery, $rows[$i]['id']);
				while ($row = $db->fetchRow($teamsQuery))
				{
					$teams[] = array('id' => $row['teamid'], 'name' => $row['name'],
									 'link' => '../Teams/?profile=' . $row['teamid']);
				}
				$db->free($teamsQuery);
				$messages[$i]['recipients'] = (array('users' => $users, 'teams' => $teams));
			}
			$tmpl->assign('messages', $messages);
			
			if ($offset > 0 || $showNextMSGButton)
			{
				if ($offset > 0)
				{
					// show previous messages
					$tmpl->assign('offsetPrev', $offset-$max_per_page);
				}
				if ($showNextMSGButton)
				{
					// show next messages
					$tmpl->assign('offsetNext', $offset+$max_per_page);
				}
			}
		}
	}
?>
