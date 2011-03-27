<?php
	class newsSystem
	{
		private $editor;
		
		function __construct($title, $path)
		{
			global $entry_edit_permission;
			global $entry_delete_permission;
			global $site;
			global $tmpl;
			global $user;
			
			if (!isset($site))
			{
				require_once (dirname(dirname(dirname(__FILE__))) . '/site.php');
				$site = new site();
			}
			
			$tmpl->setTemplate('News');
			
			$tmpl->assign('name', 'Ned');
			$tmpl->assign('title', $title);
			
			// FIXME: fallback to default permission name until add-on system is completly implemented
			$entry_add_permission = 'allow_add_news';
			$entry_edit_permission = 'allow_edit_news';
			$entry_delete_permission = 'allow_delete_news';
			
			// find out which template should be used
			// fallback template is static
			$templateToUse = 'News';
			
			// use suggested name in $page_title
			if (isset($path))
			{
				if (strcmp($path, '/') === 0)
				{
					// force call this template home
					$templateToUse = 'Home';
				} else
				{
					$templateToUse = $title;
				}
				
				// revert back to default if file does not exist
				if (!$tmpl->existsTemplate($templateToUse))
				{
					$templateToUse = 'News';
				}
			}
			
			
			
			// otherwise we'd need a custom name per setup
			// as top level dir could be named different
			if ($user->getPermission($entry_edit_permission) && isset($_GET['edit']))
			{
				// remove the slashes if magic quotes are sadly on
				if ($site->magic_quotes_on())
				{
					stripslashes($_POST);
				}
				if (!$tmpl->setTemplate($templateToUse . '.edit'))
				{
					$tmpl->noTemplateFound();
				}
				include(dirname(dirname(dirname(__FILE__))) . '/classes/editor.php');
				$this->editor = new editor($this);
				$this->editor->addFormatButtons('staticContent');
				$this->editor->edit();
				$tmpl->display();
				die();
			}
			
			
			if ($user->getPermission($entry_add_permission) && isset($_GET['add']))
			{
				// user has permission to add news to the page and requests it
				$tmpl->setTemplate($templateToUse . '.edit');
				$this->add();
				$tmpl->display();
				die();
			}
			
			// user looks at page in read mode
			$tmpl->setTemplate($templateToUse);
			
			if ($user->getPermission($entry_add_permission))
			{
				$tmpl->assign('showAddButton', true);
			}
			$this->readContent($path, $author, $last_modified, false);
			
			// done, display page
			$tmpl->display();
		}
		
		
		function add()
		{
			global $tmpl;
			
			echo('blub');
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
			global $page_title;
			global $author;
			global $last_modified;
			global $config;
			global $user;
			global $db;
			
			
			if ($readonly || isset($_POST['confirmationStep']))
			{
				$content = array();
				$content['raw_msg'] = $_POST['staticContent'];
				
				$query = $db->prepare('SELECT `name` FROM `players` WHERE `id`=? LIMIT 1');
				$db->execute($query, $user->getID());
				$content['author'] = $db->fetchRow($query);
				$db->free($query);
				
				$content['title'] = $_POST['title'];
				$content['timestamp'] = $_POST['time'];
			} else
			{
				$content = $this->readContent($page_title, $author, $last_modified, true);
			}
			
			
			switch($readonly)
			{
				case true:
					$tmpl->assign('titlePreview',  htmlent($content['title']));
					$tmpl->assign('authorPreview',  htmlent($content['author']['name']));
					$tmpl->assign('timestampPreview',  htmlent($content['timestamp']));
					$tmpl->assign('rawContent', htmlent($content['raw_msg']));
					if ($config->value('bbcodeLibAvailable'))
					{
						$tmpl->assign('contentPreview',  $tmpl->encodeBBCode($content['raw_msg']));
					} else
					{
						$tmpl->assign('contentPreview',  htmlent($content['raw_msg']));
					}
					break;
				
				default:
					$tmpl->assign('rawContent', htmlspecialchars($content['raw_msg']
																 , ENT_COMPAT, 'UTF-8'));
					$tmpl->assign('timestamp', htmlspecialchars($content['timestamp']
																, ENT_COMPAT, 'UTF-8'));
					$tmpl->assign('msgTitle', htmlspecialchars($content['title']
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
		
		function readContent($path, &$author, &$last_modified, $edit=false)
		{
			global $entry_edit_permission;
			global $entry_delete_permission;
			global $tmpl;
			global $user;
			global $db;
			
			
			// initialise return variable so any returned value will be always in a defined state
			$content = array();
			$offset = 0;
			if (!$edit)
			{
				if (isset($_GET['i']) && (intval($_GET['i']) > -1))
				{
					$offset = intval($_GET['i']);
				}
				
				// TODO: id only needed if user can edit or delete
				// TODO: meaning room for optimisation
				$query = $db->SQL('SELECT `id`,`title`,`timestamp`,`author`,`msg`'
								  . ' FROM `news` ORDER BY `timestamp` DESC'
								  . ' LIMIT ' . intval($offset) .', 21');
			} else
			{
				$query = $db->SQL('SELECT `title`,`timestamp`,`author`,`raw_msg`'
								  . ' FROM `news` ORDER BY `timestamp` DESC'
								  . ' LIMIT ' . intval($offset) .', 1');
			}
			$db->execute($query, $offset);
			$rows = $db->fetchAll($query);
			$db->free($query);
			
			if ($edit)
			{
				// let the edit insertion function pass it to the editor class
				return $rows[0];
			}
			
			if ($offset > 0)
			{
				$tmpl->assign('offsetPrev', $offset-20);
			}
			
			// process query result array
			$n = count($rows);
			if ($n > 0)
			{
				$showButtons = false;
				if (!$edit
					&& $user->getPermission($entry_edit_permission)
					|| $user->getPermission($entry_delete_permission))
				{
					$tmpl->assign('showEditButton', $user->getPermission($entry_edit_permission));
					$tmpl->assign('showDeleteButton', $user->getPermission($entry_delete_permission));
				}
				
				if (isset($buttons))
				{
					$showButtons = true;
				}
				
				// next news button needed
				if ($n > 20)
				{
					// remove the last row of result: show only 20 entries per page
					unset($rows[$offset+$n-1]);
					$n--;
					
					// show the button
					$tmpl->assign('offsetNext', $offset+20);
				}
				
				// article box
				for($i = 0; $i < $n; $i++)
				{
					$content[$i]['id'] = $rows[$i]['id'];
					$content[$i]['title'] = (strcmp($rows[$i]['title'], '') === 0)?
									   'News' : $rows[$i]['title'];

					$content[$i]['author'] = $rows[$i]['author'];
					$content[$i]['time'] = $rows[$i]['timestamp'];
					
					$edit ? $content[$i]['content'] = $rows[$i]['raw_msg']
						 : $content[$i]['content'] = $rows[$i]['msg'];
					$author = $rows[$i]['author'];

/*
					if ($edit)
					{
						$content = $rows[$i]['raw_msg'];
					} else
					{
						$content[$i]['content'] = $rows[$i]['msg'];
					}
*/
				}
			}
			
			$tmpl->assign('entries', $content);
		}
		
		function writeContent(&$content, $page_title, $table)
		{
			global $site;
			global $config;
			global $user;
			global $tmpl;
			global $db;
			
			if (strcmp($content, '') === 0)
			{
				// empty content
				$query = $db->prepare('DELETE FROM `static_pages` WHERE `page_name`=?');
				$db->execute($query, $page_title);
				return;
			}
			
			$query = $db->prepare('SELECT `id` FROM `static_pages` WHERE `page_name`=? LIMIT 1');
			$db->execute($query, $page_title);
			
			// number of rows
			$rows = $db->rowCount($query);
			$date_format = date('Y-m-d H:i:s');
			if ($rows < ((int) 1))
			{
				// no entry in table regarding current page
				// thus insert new data
				$query = $db->prepare('INSERT INTO `?`'
									  . ' (`author`, `page_name`, `raw_content`, `content`, `last_modified`)'
									  . ' VALUES (?, ?, ?, ?, ?)');
				$args = array($table, $user->getID(), $page_title, $content);
				if ($config->value('bbcodeLibAvailable'))
				{
					$args[] = $tmpl->encodeBBCode($content);
				} else
				{
					$args[] = $content;
				}
				$args[] = $date_format;
				
				//			$db->prepare($query);
				//			$db->execute();
			} else
			{
				// either 1 or more entries found, just assume there is only one
				$query = $db->prepare('UPDATE `?` SET `author`?='
									  . ', `raw_content`=?'
									  . ', `content`=?'
									  . ', `last_modified`=?'
									  . ' WHERE `page_name`=?'
									  . ' LIMIT 1');
				$args = array($table, $user->getID(), $content);
				if ($config->value('bbcodeLibAvailable'))
				{
					$args[] = $tmpl->encodeBBCode($content);
				} else
				{
					$args[] = $content;
				}
				$ags[] = $date_format;
				$args[] = $page_title;
			}
			
			$result = $db->execute($query, $args);
		}
	}
?>