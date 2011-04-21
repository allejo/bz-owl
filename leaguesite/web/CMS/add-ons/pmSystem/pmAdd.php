<?php
	class pmSystemAddPM extends pmSystemPM
	{
		private $editor;
		private $PMComposer;
		public $randomKeyName = 'pmSystemAddPM';
		
		function __construct()
		{
			global $user;
			global $tmpl;
			
			
			include(dirname(__FILE__) . '/classes/PMComposer.php');
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
			global $config;
			global $user;
			global $db;
			
			
			if ($readonly || isset($_POST['confirmationStep']))
			{
				// data passed to form -> use it
				
				$query = $db->prepare('SELECT `name` FROM `players` WHERE `id`=? LIMIT 1');
				$db->execute($query, $user->getID());
				$author = $db->fetchRow($query);
				if ($author === false)
				{
					$author = 'error: no author could be determined';
				}
				$db->free($query);
			}
			
			// do not drop original message id that a reply would be refering to
			// but drop reply mode (users and teams are already added to recipients at this point)
			$formArgs = '';
			if (isset($_GET['id']))
			{
				$formArgs .= '&amp;id=' . $_GET['id'];
			}
			$tmpl->assign('formArgs', $formArgs);
			
			$tmpl->assign('subject', $this->PMComposer->getSubject());
			$tmpl->assign('time', $this->PMComposer->getTimestamp());
			$tmpl->assign('playerRecipients', $this->PMComposer->getUserNames());
			$tmpl->assign('teamRecipients', $this->PMComposer->getTeamNames());
			$tmpl->assign('rawContent', htmlent($this->PMComposer->getContent()));
			
			switch($readonly)
			{
				case true:
					$tmpl->assign('authorName',  htmlent($author['name']));
					if ($config->value('bbcodeLibAvailable'))
					{
						$tmpl->assign('content',  $tmpl->encodeBBCode($this->PMComposer->getContent()));
					} else
					{
						$tmpl->assign('content',  htmlent($this->PMComposer->getContent()));
					}
					$tmpl->assign('showPreview', true);
					// overwrite editor's default text ('Write changes')
					$tmpl->assign('submitText', 'Send PM');
					break;
				
				default:
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
			global $db;
			
			
			// < 0: undefined, 0: edit screen, 1: preview, 2: send, > 2: undefined
			if ($confirmed < 0 || $confirmed > 2)
			{
				// changed undefined values to a defined state
				$confirmed = 0;
			}
			
			
			if (isset($_GET['reply']) && isset($_GET['id']) && intval($_GET['id']) > 0)
			{
				// add all original recipients and author or only original author to default recipients
				
				// find out if original message was readable for user
				$query = $db->prepare('SELECT COUNT(*) FROM `pmsystem.msg.users` WHERE `msgid`=? AND `userid`=?');
				$db->execute($query, array($_GET['id'], $user->getID()));
				$rows = $db->fetchRow($query);
				$db->free($query);
				
				// silently drop on no permisson issue
				// message to self may be listed twice, for inbox and outbox
				// TODO: output error
				if (count($rows) > 0 && $rows['COUNT(*)'] > 0)
				{
					$query = $db->prepare('SELECT `subject` FROM `pmsystem.msg.storage` WHERE `id`=?');
					$db->execute($query, $_GET['id']);
					$row = $db->fetchRow($query);
					$db->free($query);
					if (count($row) > 0)
					{
						$this->PMComposer->setSubject($row['subject']);
					}
					
					if (strcmp($_GET['reply'], 'all') === 0)
					{
						// prepare recipients queries
						$usersQuery = $db->prepare('SELECT `name`'
												   . ' FROM `pmsystem.Msg.Recipients.Users` LEFT JOIN `players`'
												   . ' ON `pmsystem.Msg.Recipients.Users`.`userid`=`players`.`id`'
												   . ' WHERE `msgid`=?');
						$teamsQuery = $db->prepare('SELECT `name`'
												   . ' FROM `pmsystem.msg.recipients.teams` LEFT JOIN `teams`'
												   . ' ON `pmsystem.msg.recipients.teams`.`teamid`=`teams`.`id`'
												   . ' WHERE `msgid`=?');
						
						// add users to recipients
						$db->execute($usersQuery, intval($_GET['id']));
						while ($row = $db->fetchRow($usersQuery))
						{
							$this->PMComposer->addUserName($row['name']);
						}
						$db->free($usersQuery);
						
						// add teams to recipients
						$db->execute($teamsQuery, intval($_GET['id']));
						while ($row = $db->fetchRow($teamsQuery))
						{
							$this->PMComposer->addTeamName($row['name']);
						}
						$db->free($teamsQuery);
					} elseif (strcmp($_GET['reply'], 'author') === 0)
					{
						// only 1 author, thus no loop
						$query = $db->prepare('SELECT `name` FROM `players`'
											  . ' WHERE `id`=(SELECT `author_id` FROM `pmsystem.msg.storage`'
											  . ' WHERE `id`=? LIMIT 1) LIMIT 1');
						$db->execute($query, intval($_GET['id']));
						$row = $db->fetchRow($query);
						$db->free($query);
						
						$this->PMComposer->addUserName($row['name']);
					}
				}
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
				
				
				if (isset($_POST['subject']) && (strlen($_POST['subject']) > 0))
				{
					$this->PMComposer->setSubject(strval($_POST['subject']));
				}
				
				if (isset($_POST['content']) && (strlen($_POST['content']) > 0))
				{
					$this->PMComposer->setContent(strval($_POST['content']));
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
						$this->PMComposer->addUserName($_POST['playerRecipient' . $i]
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
					$this->PMComposer->addUserName($_POST['playerRecipient'], $confirmed > 0);
				}
			}
			
			
			if ($confirmed > 0 && $this->PMComposer->countUsers() < 1 && $this->PMComposer->countTeams() < 1)
			{
				$tmpl->assign('MSG', 'A PM can not be sent without any recipients set.');
				$confirmed = 0;
			}
			
			return true;
		}
		
		function writeContent()
		{
			global $user;
			
			return $this->PMComposer->send($user->getID());
		}
	}
?>
