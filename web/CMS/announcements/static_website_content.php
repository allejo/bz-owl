<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	
	
	require_once (dirname(dirname(__FILE__)) . '/siteinfo.php');
	$site = new siteinfo();
	
	if (strcmp($page_title, '') === 0)
	{
		$site->dieAndEndPage('Error: No page title specified!');;
	}
	
	if (isset($_GET['edit']))
	{
		$display_page_title = 'Page content editor: ' . $page_title;
	}
	require_once (dirname(dirname(__FILE__)) . '/index.inc');
	
	$site = new siteinfo();
	
	function errormsg()
	{
		echo '<p>You do not have the permission to edit this content.</p>' . "\n";
	}
	
	
	// initialise variables
	$previewSeen = '';
	$content = '';
	
	
	// set their values in case the POST variables are set
	if (isset($_POST["preview"]))
	{
		$previewSeen = (int) $_POST['preview'];
	}
	if (isset($_POST['edit_page']))
	{
		// user looked at preview but chose to edit the message again
		$previewSeen = 0;
	}
	if (isset($_POST['announcement']))
	{
		$content = $_POST['announcement'];
	}
	
	
	if ((isset($_SESSION[$entry_edit_permission])) && ($_SESSION[$entry_edit_permission]))
	{
		// user has permission to edit the page
		if (isset($_GET['edit']))
		{
			// user looks at page in edit mode
			echo '<a href="./" class="button">overview</a>' ."\n";
			echo '<div class="static_page_box">' . "\n";
		} else
		{
			// user looks at page in read mode
			echo '<a href="./?edit" class="button">edit</a>' . "\n";
			$site->write_self_closing_tag('br');
			echo "\n";
		}
	} else
	{
		// user has no permission to edit the page
		if (isset($_GET['edit']))
		{
			// user wants to edit the page
			// show a button to let the user look at the page in read only mode
			echo '<p><a href="./" class="button">overview</a>' . '</p>' ."\n";
			// stop here or the user will be able to edit the content despite he has no permission
			errormsg();
			$site->dieAndEndPageNoBox();
		}
	}
	
	// prevent links letting people modify the page unintentionally
	if (isset($_GET['edit']) && ($previewSeen > 0))
	{
		$new_randomkey_name = '';
		if (isset($_POST['key_name']))
		{
			$new_randomkey_name = html_entity_decode($_POST['key_name']);
		}
		$randomkeysmatch = $site->compare_keys(urldecode($randomkey_name), $new_randomkey_name);
		
		if (!$randomkeysmatch)
		{
			// automatically back to main view
			echo '<p>The magic key does not match, it looks like you came from somewhere else or your session expired.';
			echo ' Going back to compositing mode.</p>' . "\n";
			$previewSeen = 0;
		}
	}
	
	function readContent($page_title, $site, $connection, &$author, &$last_modified, $raw=false)
	{
		// initialise return variable so any returned value will be always in a defined state
		$content = '';
		if (!$raw)
		{
			$content = '<p class="first_p">No content available yet.</p>';
		}
		
		$query = 'SELECT * FROM `static_pages` WHERE `page_name`=' . sqlSafeStringQuotes($page_title) . ' LIMIT 1';
		if (!($result = @$site->execute_query('static_pages', $query, $connection)))
		{
			$site->dieAndEndPage('An error occured getting content for page ' . $page_title . '!');
		}
		
		// process query result array
		while ($row = mysql_fetch_array($result))
		{	 
			$author = $row['author'];
			$last_modified = $row['last_modified'];
			if ($raw && $site->bbcode_lib_available())
			{
				$content = $row['raw_content'];
			} else
			{
				$content = $row['content'];
			}
		}
		
		mysql_free_result($result);
		
		return $content;
	}
	
	function writeContent(&$content, $page_title, $site, $connection)
	{
		if (strcmp($content, '') === 0)
		{
			// empty content
			$query = 'DELETE FROM `static_pages` WHERE `page_name`=' . sqlSafeStringQuotes($page_title);
			if (!($result = @$site->execute_query('static_pages', $query, $connection)))
			{
				$site->dieAndEndPage('An error occured deleting content for page ' . $page_title . '!');
			}
			return;
		}
		
		$query = 'SELECT `id` FROM `static_pages` WHERE `page_name`=' . sqlSafeStringQuotes($page_title) . ' LIMIT 1';
		if (!($result = @$site->execute_query('static_pages', $query, $connection)))
		{
			$site->dieAndEndPage('An error occured getting content for page ' . $page_title . '!');
		}
		
		// number of rows
		$rows = (int) mysql_num_rows($result);
		$date_format = date('Y-m-d H:i:s');
		if ($rows < ((int) 1))
		{
			// no entry in table regarding current page
			// thus insert new data
			$query = 'INSERT INTO `static_pages` (`author`, `page_name`, `raw_content`, `content`, `last_modified`) VALUES (';
			// getUserID() is a function from siteinfo.php that identifies the current user
			$query .= sqlSafeStringQuotes(getUserID());
			$query .= ', ' . sqlSafeStringQuotes($page_title);
			$query .= ', ' . sqlSafeStringQuotes($content);
			if ($site->bbcode_lib_available())
			{
				$query .= ', ' . sqlSafeStringQuotes($site->bbcode($content));
			} else
			{
				$query .= ', ' . sqlSafeStringQuotes($content);
			}
			$query .= ', ' . sqlSafeStringQuotes($date_format);
			$query .= ')';
		} else
		{
			// either 1 or more entries found, just assume there is only one
			$query = 'UPDATE `static_pages` SET `author`=' . sqlSafeStringQuotes(getUserID());
			$query .= ', `raw_content`=' . sqlSafeStringQuotes($content);
			if ($site->bbcode_lib_available())
			{
				$query .= ', `content`=' . sqlSafeStringQuotes($site->bbcode($content));
			} else
			{
				$query .= ', `content`=' . sqlSafeStringQuotes($content);
			}			
			$query .= ', `last_modified`=' . sqlSafeStringQuotes($date_format);
			$query .= ' WHERE `page_name`=' . sqlSafeStringQuotes($page_title);
			$query .= ' LIMIT 1';
		}
		
		if (!($result = @$site->execute_query('static_pages', $query, $connection)))
		{
			$site->dieAndEndPage('An error occured updating content for page ' . $page_title
								 . ' by user ' . sqlSafeString(getUserID()) . '!');
		}
	}
	
	if ($previewSeen === 2)
	{
		writeContent($content, $page_title, $site, $connection);
		echo '<p>Updating: No problems occured, changes written successfully!</p>' . "\n";
		// we are done updating, do not show the edit field again
		$site->dieAndEndPage();
	}
		
	if (isset($_GET['edit']))
	{
		echo '<form action="./?edit" enctype="application/x-www-form-urlencoded" method="post" accept-charset="utf-8">' . "\n";
		$new_randomkey_name = $randomkey_name . microtime();
		$new_randomkey = $site->set_key($new_randomkey_name);
		echo '<div>';
		$site->write_self_closing_tag('input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"');
		echo '</div>' . "\n";
		echo '<div>';
		$site->write_self_closing_tag('input type="hidden" name="' . htmlentities($randomkey_name) . '" value="'
									  . urlencode(($_SESSION[$new_randomkey_name])) . '"');
		echo '</div>' . "\n";
		
		if ($previewSeen === 1)
		{
			echo '<p>Preview:</p>' . "\n";
			echo '<div>';
			if ($site->bbcode_lib_available())
			{
				echo $site->bbcode($content);
			} else
			{
				echo $content;
			}
			echo '</div>' . "\n";
			echo '<div>';
			$site->write_self_closing_tag('input type="hidden" name="announcement" value="' . htmlent($content) . '"');
			echo '</div>' . "\n";
			echo '<div>';
			$site->write_self_closing_tag('input type="hidden" name="preview" value="2"');
			echo '</div>' . "\n";
			echo '</div>' . "\n";
			
			echo '<p>';
			$site->write_self_closing_tag('input type="submit" value="Confirm changes"');
			$site->write_self_closing_tag('input type="submit" name="edit_page" value="Edit page"');
			echo '</p>' . "\n";
			
			$site->dieAndEndPageNoBox();
		} else
		{
			if ($site->bbcode_lib_available())
			{
				echo '<div>Keep in mind to use BBCode instead of HTML or XHTML.</div>' . "\n";
				echo '<div>';
				include dirname(dirname(__FILE__)) . '/bbcode_buttons.php';
				$bbcode = new bbcode_buttons();
				$bbcode->showBBCodeButtons();
				unset($bbcode);
				echo '</div>';
			} else
			{
				if ($site->use_xtml())
				{
					echo '<div>Keep in mind the home page currently uses XHTML, not HTML or BBCode.</div>' . "\n";
				} else
				{
					echo '<div>Keep in mind the home page currently uses HTML, not XHTML or BBCode.</div>' . "\n";
				}
			}
			$buffer = '';
			if (isset($_POST['edit_page']))
			{
				$buffer = $_POST['announcement'];
			} else
			{
				$buffer = readContent($page_title, $site, $connection, $author, $last_modified, true);
			}
			echo '<div><textarea cols="75" rows="20" name="announcement">' . htmlent($buffer) . '</textarea></div>' . "\n";
			echo '<div>';
			$site->write_self_closing_tag('input type="hidden" name="preview" value="1"');
			echo '</div>' . "\n";
			
			echo '<p>';
			$site->write_self_closing_tag('input type="submit" value="Preview"');
			echo '</p>' . "\n";
		}
		echo '</form>' . "\n";
	} else
	{
		echo '<div class="static_page_box">' . "\n";
		$author = '';
		$last_modified = '';
		
		$buffer = readContent($page_title, $site, $connection, $author, $last_modified);
		echo $buffer;
	}
?>
</div>
</div>
</body>
</html>
