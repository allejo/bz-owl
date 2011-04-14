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
			global $user;
			global $db;
			
			// set the template
			$tmpl->setTemplate('PMView');
			$tmpl->assign('title', 'Mail #' . $id);
			
			// show currently selected mail folder
			$this->folderNav($folder);
			
			// collect the necessary data
			$query = $db->prepare('SELECT `id`,`author_id`,`subject`,`timestamp`,`message`,`msg_status`,'
								  . ' IF(`pmSystem.Msg.Storage`.`author_id`<>0,'
								  . ' (SELECT `name` FROM `players` WHERE `id`=`author_id`),?) AS `author`'
								  . ' FROM `pmSystem.Msg.Storage`, `pmSystem.Msg.Users`'
								  . ' WHERE `pmSystem.Msg.Users`.`userid`=?'
								  . ' AND `pmSystem.Msg.Storage`.`id`=`pmSystem.Msg.Users`.`msgid`'
								  . ' AND `folder`=?'
								  . ' AND `pmSystem.Msg.Storage`.`id`=?'
								  . ' ORDER BY `pmSystem.Msg.Storage`.`id` DESC'
								  . ' LIMIT 1');
			$db->execute($query, array($config->value('displayedSystemUsername'), $user->getID(), $folder, $id));
			
			$rows = $db->fetchAll($query);
			$db->free($query);
			
			// create PM navigation
			$query = $db->prepare('SELECT `msgid` FROM `pmSystem.Msg.Users`'
								  . ' WHERE `userid`=? AND `msgid`<?'
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
			
			$query = $db->prepare('SELECT `msgid` FROM `pmSystem.Msg.Users`'
								  . ' WHERE `userid`=? AND `msgid`>?'
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
									   . ' FROM `pmSystem.Msg.Recipients.Users` LEFT JOIN `players`'
									   . ' ON `pmSystem.Msg.Recipients.Users`.`userid`=`players`.`id`'
									   . ' WHERE `msgid`=?');
			$teamsQuery = $db->prepare('SELECT `teamid`,`name`'
									   . ' FROM `pmSystem.Msg.Recipients.Teams` LEFT JOIN `teams`'
									   . ' ON `pmSystem.Msg.Recipients.Teams`.`teamid`=`teams`.`id`'
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
			unset($userRecipients);
			
			// find out teams in recipient list
			$db->execute($teamsQuery, $rows[0]['id']);
			$teamRecipients = array();
			while ($row = $db->fetchRow($teamsQuery))
			{
				$teamRecipients[] = array('link' => '../Players/?profile=' . intval($row['teamid']),
										  'name' => $row['name']);
			}
			$db->free($teamsQuery);
			
			if (isset($teamRecipients[0]))
			{
				$tmpl->assign('teamRecipients', $teamRecipients);
			}
			unset($teamRecipients);
			
			
			$tmpl->assign('time', $rows[0]['timestamp']);
			if ($config->value('bbcodeLibAvailable'))
			{
				$tmpl->assign('content', $tmpl->encodeBBCode($rows[0]['message']));
			} else
			{
				$tmpl->assign('content',  htmlent($rows[0]['message']));
			}
			
			$tmpl->assign('msgID', intval($_GET['view']));
			
			// mark the message as read for the current user
			$query = $db->prepare('UPDATE LOW_PRIORITY `messages_users_connection`'
								  . 'SET `msg_status`=' . "'read'"
								  . ' WHERE `msgid`=?'
								  . ' AND `folder=?'
								  . ' AND `userid`=?'
								  . ' LIMIT 1');
			$db->execute($query, array($id, $folder, $user->getID()));
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
			
			// get the list of private messages to be displayed (+1 one hidden due to next button)
			// userid requirement ensures user only sees the messages he's allowed to
			$query = $db->prepare('SELECT `id`,`author_id`,`subject`,`timestamp`,`folder`,`msg_status`,'
								  . ' IF(`pmSystem.Msg.Storage`.`author_id`<>0,'
								  . ' (SELECT `name` FROM `players` WHERE `id`=`author_id`),?) AS `author`'
								  . ' FROM `pmSystem.Msg.Storage`, `pmSystem.Msg.Users`'
								  . ' WHERE `pmSystem.Msg.Users`.`userid`=?'
								  . ' AND `pmSystem.Msg.Storage`.`id`=`pmSystem.Msg.Users`.`msgid`'
								  . ' AND `folder`=?'
								  . ' ORDER BY `pmSystem.Msg.Storage`.`id` DESC'
								  . ' LIMIT ' . $offset . ', 201');
			$db->execute($query, array($config->value('displayedSystemUsername'), $user->getID(), $folder));
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
			
			
			// prepare recipients queries outside of the loop
			$usersQuery = $db->prepare('SELECT `userid`,`name`'
									   . ' FROM `pmSystem.Msg.Recipients.Users` LEFT JOIN `players`'
									   . ' ON `pmSystem.Msg.Recipients.Users`.`userid`=`players`.`id`'
									   . ' WHERE `msgid`=?');
			$teamsQuery = $db->prepare('SELECT `teamid`,`name`'
									   . ' FROM `pmSystem.Msg.Recipients.Teams` LEFT JOIN `teams`'
									   . ' ON `pmSystem.Msg.Recipients.Teams`.`teamid`=`teams`.`id`'
									   . ' WHERE `msgid`=?');
			
			$messages = array();
			for ($i = 0; $i < $n; $i++)
			{
				// only set up a link to user profile if user has no reserved id
				// 0 is an internal system user that shares its id with not logged-in users
				if (intval($rows[$i]['author_id']) > 0)
				{
					$messages[$i]['userProfile'] = ($config->value('baseaddress')
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
