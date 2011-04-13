<?php
	class staticPageEditor
	{
		private $editor;
		private $path;
		public $randomKeyName = 'staticPageEditor';
		
		function __construct($title, $path)
		{
			global $entry_edit_permission;
			global $site;
			global $tmpl;
			global $user;
			
			
			$templateToUse = 'static';
			if (!$tmpl->setTemplate($templateToUse))
			{
				$tmpl->noTemplateFound();	// does not return
			}
			
			$this->path = $path;
			$tmpl->assign('title', $title);
			
			// FIXME: fallback to default permission name until add-on system is completly implemented
			$entry_edit_permission = 'allow_edit_static_pages';
			
			require_once (dirname(dirname(dirname(__FILE__))) . '/classes/editor.php');
			
			if ($user->getPermission($entry_edit_permission) && isset($_GET['edit']))
			{
				// remove the slashes if magic quotes are sadly on
				if ($site->magic_quotes_on())
				{
					stripslashes($_POST);
				}
				if (!$tmpl->setTemplate($templateToUse . '.edit'))
				{
					$tmpl->noTemplateFound();	// does not return
				}
				$this->editor = new editor($this);
				$this->editor->addFormatButtons('staticContent');
				$this->editor->edit();
				$tmpl->display();
				die();
			}
			
			// user looks at page in read mode
			if ($user->getPermission($entry_edit_permission))
			{
				$tmpl->assign('showEditButton', true);
			}
			$tmpl->assign('content', $this->readContent($path, $author, $last_modified, false));
			
			// done, display page
			$tmpl->display();
		}
		
		
		function sanityCheck(&$confirmed)
		{
			global $entry_edit_permission;
			global $user;
			global $tmpl;
			
			if (!$user->getPermission($entry_edit_permission))
			{
				// editing cancelled due to missing user permission
				$confirmed = 0;
				return 'noperm';
			}
			
			// no need to check for a key match if no content was supplied
			if (($confirmed > 0) && !$this->randomKeyMatch($confirmed))
			{
				// editing cancelled due to random key mismatch
				$confirmed = 0;
				return 'nokeymatch';
			}
			
			return true;
		}
		
		function insertEditText($readonly=false)
		{
			global $tmpl;
			global $author;
			global $last_modified;
			global $config;
			
			if ($readonly || isset($_POST['confirmationStep']))
			{
				$content = $_POST['staticContent'];
			} elseif (isset($_GET['edit']))
			{
				$content = $this->readContent($this->path, $author, $last_modified, true);
			} else
			{
				$content = 'Replace this text with the page content.';
			}
			
			switch($readonly)
			{
				case true:
					$tmpl->assign('rawContent', htmlent($content));
					if ($config->value('bbcodeLibAvailable'))
					{
						$tmpl->assign('contentPreview',  $tmpl->encodeBBCode($content));
					} else
					{
						$tmpl->assign('contentPreview',  $content);
					}
					break;
				
				default:
					$tmpl->assign('rawContent', htmlspecialchars($content
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
				$randomKeyName = html_entity_decode($_POST['key_name']);

				if (isset($_POST[$randomKeyName]))
				{
					$randomKeyValue = html_entity_decode($_POST[$randomKeyName]);
				}
			}
			
			return $randomkeysmatch = $site->validateKey($randomKeyName, $randomKeyValue);
		}
		
		function readContent($path, &$author, &$last_modified, $raw=false)
		{
			global $site;
			global $db;
			global $config;
			
			// initialise return variable so any returned value will be always in a defined state
			$content = '';
			if (!$raw)
			{
				$content = 'No content available yet.';
			}
			
			$query = $db->prepare('SELECT * FROM `static_pages` WHERE `page`=? LIMIT 1');
			$result = $db->execute($query, $path);
			
			// process query result array
			while ($row = $db->fetchRow($query))
			{
				$author = $row['author'];
				$last_modified = $row['last_modified'];
				if ($raw && $config->value('bbcodeLibAvailable'))
				{
					$content = $row['raw_content'];
				} else
				{
					$content = $row['content'];
				}
			}
			$db->free($query);
			
			return $content;
		}
		
		function writeContent(&$content)
		{
			global $config;
			global $user;
			global $tmpl;
			global $db;
			
			if (strcmp($content, '') === 0)
			{
				// empty content
				$query = $db->prepare('DELETE FROM `static_pages` WHERE `page`=?');
				$db->execute($query, $this->path);
				$db->free($query);
				return;
			}
			
			$query = $db->prepare('SELECT `id` FROM `static_pages` WHERE `page`=? LIMIT 1');
			$db->execute($query, $this->path);
			
			// number of rows
			$rows = $db->rowCount($query);
			$db->free($query);
			$date_format = date('Y-m-d H:i:s');
			
			$args = array($user->getID(), $date_format, $content);
			if ($config->value('bbcodeLibAvailable'))
			{
				$args[] = $tmpl->encodeBBCode($content);
			} else
			{
				$args[] = $content;
			}
			$args[] = $this->path;
			
			if ($rows < ((int) 1))
			{
				// no entry in table regarding current page
				// thus insert new data
				$query = $db->prepare('INSERT INTO `static_pages`'
						. ' (`author`, `last_modified`, `raw_content`, `content`, `page`)'
						. ' VALUES (?, ?, ?, ?, ?)');
			} else
			{
				// either 1 or more entries found, just assume there is only one
				$query = $db->prepare('UPDATE `static_pages` SET `author`=?'
									  . ', `last_modified`=?'
									  . ', `raw_content`=?'
									  . ', `content`=?'
									  . ' WHERE `page`=?'
									  . ' LIMIT 1');
			}
			
			$db->execute($query, $args);
			$db->free($query);
		}
	}
?>