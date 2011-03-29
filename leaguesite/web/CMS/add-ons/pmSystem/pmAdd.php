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
				
				$content['recipientPlayers'] = $this->PMComposer->getRecipientNames();
				$content['timestamp'] = date('Y-m-d H:i:s');
			} else
			{
				// new message -> recipient players yet
				$content['recipientPlayers'] = array();
			}
			
			
			switch($readonly)
			{
				case true:
					$tmpl->assign('recipientPlayers', $content['recipientPlayers']);
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
					$tmpl->assign('recipientPlayers', $content['recipientPlayers']);
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
			
			
			if ($confirmed > 0 || isset($_POST['editPageAgain']))
			{
				// no need to check for a key match if no content was supplied
				if (!$this->randomKeyMatch($confirmed))
				{
					// editing cancelled due to random key mismatch
					$confirmed = 0;
					return 'nokeymatch';
				}
				
				// add all set player recipients
				$i = 0;
				while (isset($_POST['recipientPlayer' . $i]))
				{
					// user requested removal of a recipient -> do not send now
					if (isset($_POST['removeRecipientPlayer' . $i]))
					{
						$confirmed = 0;
					}
					
					// exclude recipients that are requested to be removed
					if (isset($_POST['recipientPlayer' . $i]) && !(isset($_POST['removeRecipientPlayer' . $i])))
					{
						$this->PMComposer->addRecipientName($_POST['recipientPlayer' . $i]
															, $confirmed > 0
															&& !isset($_POST['addPlayerRecipient'])
															&& !isset($_POST['editPageAgain']));
					}
					$i++;
				}
				
				// add new player recipient if requested and do not send the message
				if (isset($_POST['recipientPlayer']) && isset($_POST['addPlayerRecipient']))
				{
					$confirmed = 0;
					$this->PMComposer->addRecipientName($_POST['recipientPlayer'], $confirmed > 0);
				}
			}
			
			return true;
		}
	}
	
	class PMComposer
	{
		private $recipients = array();
		private $usernameQuery;
		
		
		function __construct()
		{
			global $db;
			
			
			$this->usernameQuery = $db->prepare('SELECT `id`, `name` FROM `players` WHERE `name`=? LIMIT 1');
		}
		
		function addRecipientName($recipientName, $preview=false)
		{
			global $db;
			
			
			// remove double entries, invalid names etc
			
			// lookup if username is already in recipient array
			$alreadyAdded = false;
			foreach ($this->recipients as $oneRecipient)
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
					$this->recipients[] = ($preview) ? array('id'=>$row['id'],
															 'name'=>$row['name'],
															 'link' => '../Players/?profile=' . $row['id'])
															 : $row['name'];
					unset($row);
				}
				$db->free($this->usernameQuery);
			}
		}
		
		function getRecipientNames()
		{
			return $this->recipients;
		}
	}
?>
