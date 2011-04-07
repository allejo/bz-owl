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
			
			
			$content = array();
			if ($readonly || isset($_POST['confirmationStep']))
			{
				// data passed to form -> use it
				
				$query = $db->prepare('SELECT `name` FROM `players` WHERE `id`=? LIMIT 1');
				$db->execute($query, $user->getID());
				$content['author'] = $db->fetchRow($query);
				$db->free($query);
			}
			
			$tmpl->assign('subject', $this->PMComposer->getSubject());
			$tmpl->assign('time', $this->PMComposer->getTimestamp());
			$tmpl->assign('playerRecipients', $this->PMComposer->getPlayerNames());
			$tmpl->assign('teamRecipients', $this->PMComposer->getTeamNames());
			$tmpl->assign('rawContent', htmlent($this->PMComposer->getContent()));
			
			switch($readonly)
			{
				case true:
					$tmpl->assign('authorName',  htmlent($content['author']['name']));
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
			
			return $this->PMComposer->send($user->getID());
		}
	}
?>
