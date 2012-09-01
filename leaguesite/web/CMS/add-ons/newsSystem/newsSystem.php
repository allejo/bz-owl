<?php
	class newsSystem
	{
		private $editor;
		private $edit_id;
		private $page_path;
		public $randomKeyName = 'newsSystem';
		
		function __construct($title, $path)
		{
			global $entry_add_permission;
			global $entry_edit_permission;
			global $entry_delete_permission;
			global $site;
			global $tmpl;
			global $user;
			
/*
			if (isset($path) && (strcmp($path, 'News/') === 0
							  || strcmp($path, 'Bans/') === 0))
			{
*/
				$this->page_path = $path;
/*
			} else
			{
				$tmpl->setTemplate('404');
				$tmpl->assign('errorMsg', "No support for $path as a newsSystem page.");
				$tmpl->display();
				die();
			}
*/
			
			$templateToUse = 'News';
			if (!$tmpl->setTemplate($templateToUse))
			{
				$tmpl->noTemplateFound();
				die();
			}
			
			$tmpl->assign('title', $title);
			
			// FIXME: fallback to default permission name until add-on system is completly implemented
			if ($path === 'Bans/')
			{
				$entry_add_permission = 'allow_add_bans';
				$entry_edit_permission = 'allow_edit_banss';
				$entry_delete_permission = 'allow_delete_bans';
			} else
			{
				$entry_add_permission = 'allow_add_news';
				$entry_edit_permission = 'allow_edit_news';
				$entry_delete_permission = 'allow_delete_news';
			}
			
			require_once (dirname(dirname(dirname(__FILE__))) . '/classes/editor.php');
			
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
					die();
				}
				$this->edit_id = intval($_GET['edit']);	// FIXME: use better validation than intval()
				$tmpl->assign('formAction', './?edit=' . $this->edit_id);
				$this->editor = new editor($this);
				$this->editor->addFormatButtons('staticContent');
				$this->editor->edit();
				$tmpl->display();
				die();
			}
			
			
			if ($user->getPermission($entry_add_permission) && isset($_GET['add']))
			{
				// user has permission to add news to the page and requests it
				if (!$tmpl->setTemplate($templateToUse . '.edit'))
				{
					$tmpl->noTemplateFound();
					die();
				}
				$tmpl->assign('formAction', './?add');
				$this->editor = new editor($this);
				$this->editor->addFormatButtons('staticContent');
				$this->editor->edit();
				$tmpl->display();
				die();
			}
			
			
			if ($user->getPermission($entry_delete_permission) && isset($_GET['delete']))
			{
				// user has permission to delete news from the page and requests it
				if (!$tmpl->setTemplate($templateToUse . '.delete'))
				{
					$tmpl->noTemplateFound();
					die();
				}
				
				$confirmed = isset($_POST['submit']) ? 1 : 0;
				$confirmed = $this->sanityCheck($confirmed);
				if ($confirmed === true)
				{
					$this->delete();
					$tmpl->display();
				} else
				{
					$tmpl->display('NoPerm') || $tmpl->noTemplateFound();
				}
				die();
			}
			
			// user looks at page in read mode
			if ($user->getPermission($entry_add_permission))
			{
				$tmpl->assign('showAddButton', true);
			}
			$this->readContent($this->page_path, $author, $last_modified, false);
			
			// done, display page
			$tmpl->display();
		}
		
		
		// returns old timestamp if possible, false on failure
		private function getOriginalTimestamp()
		{
			global $db;
			
			if (isset($this->edit_id))
			{
				$query = $db->prepare('SELECT `timestamp` FROM `newssystem` WHERE `id`=? LIMIT 1');
				$db->execute($query, $this->edit_id);
				
				$row = $db->fetchRow($query);
				$db->free($query);
				
				if (isset($row) && is_array($row) && isset($row['timestamp']))
				{
					return $row['timestamp'];
				}
			}
			return false;
		}
		
		
		function delete()
		{
			global $site;
			global $tmpl;
			global $db;
			
			
			if (!isset($_POST['submit']))
			{
				$tmpl->assign('formAction', './?delete=' . intval($_GET['delete']));
				
				$query = $db->prepare('SELECT `title`, `timestamp`, `author`, `msg`'
									  . ' FROM `newssystem` WHERE `id`=:id LIMIT 1');
				$db->execute($query, array(':id' => array(intval($_GET['delete']), PDO::PARAM_INT)));
				
				$row = $db->fetchRow($query);
				if (!$row)
				{
					$tmpl->setTemplate('NoPerm') || $tmpl->noTemplateFound();
					$tmpl->assign('errorMsg', 'Query failed, request aborted.');
				}
				
				$tmpl->assign('titlePreview', $row['title']);
				$tmpl->assign('authorPreview', $row['author']);
				$tmpl->assign('timestamp', $row['timestamp']);
				$tmpl->assign('content', $row['msg']);
				
				
				$tmpl->assign('contentPreview', true);
				$randomKeyName = $this->randomKeyName . microtime();
				// convert some special chars to underscores
				$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
				$randomkeyValue = $site->setKey($randomKeyName);
				$tmpl->assign('keyName', $randomKeyName);
				$tmpl->assign('keyValue', htmlent($randomkeyValue));
				
				// tell template to ask before deletion is executed
				$tmpl->assign('askForConfirmation', true);
			} else
			{
				$query = $db->prepare('DELETE FROM `newssystem` WHERE `id`=?');
				if ($db->execute($query, intval($_GET['delete'])))	// FIXME: use better validation than intval()
				{
					$tmpl->assign('MSG', 'Article deleted.' . $tmpl->linebreaks("\n\n"));
				} else
				{
					$tmpl->assign('MSG', 'No such article.' . $tmpl->linebreaks("\n\n"));
				}
				$db->free($query);
			}
		}
		
		
		public function sanityCheck(&$confirmed)
		{
			global $entry_add_permission;
			global $entry_edit_permission;
			global $entry_delete_permission;
			global $user;
			global $tmpl;
			global $db;
			
			
			if ((!$user->getPermission($entry_add_permission) && isset($_GET['add']))
				|| (!$user->getPermission($entry_edit_permission) && isset($_GET['edit']))
				|| (!$user->getPermission($entry_delete_permission) && isset($_GET['delete'])))
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
			
			
			// get id value
			$id = 0;
			if (isset($_GET['edit']))
			{
				$id = intval($_GET['edit']);
			} elseif (isset($_GET['delete']))
			{
				$id = intval($_GET['delete']);
			}
			
			// lookup value in db
			if (isset($_GET['edit']) || isset($_GET['delete']))
			{
				$query = $db->prepare('SELECT `id` FROM `newssystem` WHERE `id`=:id LIMIT 1');
				$db->execute($query, array(':id' => array($id, PDO::PARAM_INT)));
				
				// check if specified id does exist in db result
				if (!$db->fetchRow($query))
				{
					// entry does not exist
					$confirmed = 0;
					return 'noperm';
				}
			}
			
			// all tests passed
			return true;
		}
		
		
		public function insertEditText($readonly=false)
		{
			global $tmpl;
			global $author;
			global $last_modified;
			global $config;
			global $user;
			global $db;
			
			
			if ($readonly || isset($_POST['confirmationStep']))
			{
				$content = array();
				$content['raw_msg'] = $_POST['staticContent'];
				
				$query = $db->prepare('SELECT `name` FROM `users` WHERE `id`=? LIMIT 1');
				$db->execute($query, $user->getID());
				$content['author'] = $db->fetchRow($query);
				$db->free($query);
				
				$content['title'] = $_POST['title'];
			} elseif (isset($_GET['edit']))
			{
				$content = $this->readContent($this->page_path, $author, $last_modified, true);
			} else
			{
				$content = array();
				$content['raw_msg'] = 'Your message goes here';
				
				// clean page name
				$pageName = $this->page_path;
				
				// remove trailing /
				if ((($i = strrpos($pageName, '/')) !== false) && $i === strlen($pageName) -1)
				{
					$pageName = substr($pageName, 0, $i);
				}
				
				// remove last occurrence of / (remove path info from pageName)
				if (($i = strrpos($pageName, '/')) !== false)
				{
					$pageName = substr($pageName, $i);
				}				
				
				// do not remove trailing s if pageName is News, otherwise just do it
				// set title to cleaned pageName
				if ((strcmp(substr($pageName, -1), 's') === 0) && strcmp($pageName, 'News') !== 0)
				{
					$content['title'] = substr($pageName, 0, strlen($pageName) -1);
				} else
				{
					$content['title'] = $pageName;
				}
			}
			
			// let authors change timestamp, if allowed in config
			if ($config->getValue('newsSystem.permissions.allowChangeTimestampOnEdit'))
			{
				if ($readonly || isset($_POST['confirmationStep']) && isset($_POST['time']))
				{
					$tmpl->assign('timestamp', htmlspecialchars($_POST['time']));
				} else
				{
					$tmpl->assign('timestamp', $this->getOriginalTimestamp());
				}
			}
			
			
			switch($readonly)
			{
				case true:
					$tmpl->assign('titlePreview',  htmlent($content['title']));
					$tmpl->assign('authorPreview',  htmlent($content['author']['name']));
					$tmpl->assign('rawContent', htmlent($content['raw_msg']));
					if ($config->getValue('bbcodeLibAvailable'))
					{
						$tmpl->assign('contentPreview',  $tmpl->encodeBBCode($content['raw_msg']));
					} else
					{
						// TODO: only fall back to using raw data if config says so
						$tmpl->assign('contentPreview',  $content['raw_msg']);
					}
					break;
				
				default:
					$tmpl->assign('rawContent', htmlent($content['raw_msg']));
					$tmpl->assign('msgTitle', htmlent($content['title']));
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
			global $config;
			global $tmpl;
			global $user;
			global $db;
			
			$max_per_page = 20;	// FIXME: move to settings.php (or define per theme)
			
			// initialise return variable so any returned value will be always in a defined state
			$content = array();
			$offset = 0;
			if (!$edit)
			{
				if (isset($_GET['i']) && (intval($_GET['i']) > -1))
				{
					$offset = intval($_GET['i']);	// FIXME: use better validation than intval()
				}
				
				// It is arguably a PDO bug, but LIMIT and OFFSET values require named
				// parameters rather than the simple use of '?' in the SQL statement.
				// TODO: id only needed if user can edit or delete
				// TODO: meaning room for optimisation
				$query = $db->prepare('SELECT `id`,`title`,`timestamp`,`author`,`msg`'
								  . ' FROM `newssystem` WHERE `page`=:page'
								  . ' ORDER BY `timestamp` DESC'
								  . ' LIMIT :limit OFFSET :offset');
				$params = array();
				$params[':page'] = array($path, PDO::PARAM_STR);
				$params[':limit'] = array($max_per_page+1, PDO::PARAM_INT);
				$params[':offset'] = array($offset, PDO::PARAM_INT);
			} else
			{
				$query = $db->prepare('SELECT `title`,`timestamp`,`author`,`raw_msg`'
								  . ' FROM `newssystem` WHERE `id`=?');
				$params = isset($this->edit_id) ? $this->edit_id : -1;
			}
			$db->execute($query, $params);
			$rows = $db->fetchAll($query);
			$db->free($query);
			
			if ($edit)
			{
				// let the edit insertion function pass it to the editor class
				return $rows[0];
			}
			
			if ($offset > 0)
			{
				$prev_offset = $offset - $max_per_page;
				if ($prev_offset < 0)
				{
					$prev_offset = 0;
				}
				$tmpl->assign('offsetPrev', $prev_offset);
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
				if ($n > $max_per_page)
				{
					// remove the last row of result to show only $max_per_page entries
					unset($rows[$offset+$n-1]);
					$n--;
					
					// show the button
					$tmpl->assign('offsetNext', $offset+$max_per_page);
				}
				
				// overwrite genuine author if requested in config
				// can be handy for bans to avoid a single person to be critised for posting a majority decision
				$anonAuthor = $config->getValue('cms.addon.newsSystem.hideIdentity');
				$anonAuthor = ($anonAuthor['path'] == $path && !$user->getPermission('allow_see_anon_author')) ? $anonAuthor['anonUser'] : false;
				
				
				// article box
				for($i = 0; $i < $n; $i++)
				{
					$content[$i]['id'] = $rows[$i]['id'];
					$content[$i]['title'] = (strcmp($rows[$i]['title'], '') === 0)?
									   'News' : $rows[$i]['title'];
					if ($anonAuthor)
					{
						$content[$i]['author'] = $anonAuthor;
					} else
					{
						$content[$i]['author'] = $rows[$i]['author'];
					}
					$content[$i]['time'] = $rows[$i]['timestamp'];
					$content[$i]['content'] = $rows[$i][$edit ? 'raw_msg' : 'msg'];
					
					$author = $rows[$i]['author'];
				}
			}
			
			$tmpl->assign('entries', $content);
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
				$query = $db->prepare('DELETE FROM `newssystem` WHERE `id`=?');
				$db->execute($query, $this->edit_id);
				$db->free($query);
				return true;
			}
			
			// check if there is an existing news entry that corresponds to the request
			$udateEntry = $this->getOriginalTimestamp();
			
			switch(isset($_POST['time']))
			{
				case false:
					if ($udateEntry === false)
					{
						$date_format = date('Y-m-d H:i:s');
					} else
					{
						$date_format = $udateEntry;
					}
					break;
				
				
				default:
					if (isset($_POST['time']))
					{
						if ($config->getValue('newsSystem.permissions.allowChangeTimestampOnEdit'))
						{
							if (strtotime($_POST['time']) === false)
							{
								// timestamp submitted was invalid
								if ($date_format = $this->getOriginalTimestamp() === false)
								{
									// could not get original timestamp, either
									// fall back to current time
									$date_format = date('Y-m-d H:i:s');
								}
							} else
							{
								// timestamp was a valid timestamp
								// so use it
								$date_format = $_POST['time'];
							}
						} else
						{
							$date_format = date('Y-m-d H:i:s');
						}
					}
			}
			
			$query = $db->prepare('SELECT `name` FROM `users` WHERE `id`=? LIMIT 1');
			$db->execute($query, $user->getID());
			$author = $db->fetchRow($query);
			$db->free($query);
			
			if (isset($_POST['title']))
			{
				$title = htmlspecialchars_decode($_POST['title'], ENT_COMPAT);
			} else
			{
				$title = 'News';
			}
			
			$args = array($author['name'], $title, $date_format, $content);
			if ($config->getValue('bbcodeLibAvailable'))
			{
				$args[] = $tmpl->encodeBBCode($content);
			} else
			{
				$args[] = $content;
			}
			
			
			if ($udateEntry === false)
			{
				// no entry in table regarding current page
				// thus insert new data
				$query = $db->prepare('INSERT INTO `newssystem`'
					. ' (`author`, `title`, `timestamp`, `raw_msg`, `msg`, `page`)'
					. ' VALUES (?, ?, ?, ?, ?, ?)');
				$args[] = $this->page_path;
			} else
			{
				// either 1 or more entries found, just assume there is only one
				$query = $db->prepare('UPDATE `newssystem` SET `author`=?'
									  . ', `title`=?'
									  . ', `timestamp`=?'
									  . ', `raw_msg`=?'
									  . ', `msg`=?'
									  . ' WHERE `id`=?'
									  . ' LIMIT 1');
				$args[] = $this->edit_id;
			}
			
			$db->execute($query, $args);
			$db->free($query);
			
			return true;
		}
	}
?>
