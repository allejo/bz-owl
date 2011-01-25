<?php
	class menu
	{
		
		function writeLink($folder, $title, $current=false, $unread=false)
		{
			$link = '<li>';
			if (!$current)
			{
				$link .= '<a ';
				if ($unread)
				{
					$link .= 'class="unread" ';
				}
				$link .= 'href="' . (baseaddress() . $folder) . '">';
			} elseif (count($_GET) > 0)
			{
				$link .= '<a class="current_nav_entry" href="' . (baseaddress() . $folder) . '">';
			}
			$link .= $title;
			if (!$current || (count($_GET) > 0))
			{
				$link .= '</a>';
			}
			$link .= '</li>' . "\n";
			
			return	$link;
		}
		
		function createMenu()
		{
			global $site;
			global $connection;
			
			// menu is returned as array
			// each entry in the array will be a new line
			$menu = array();
			$menu[] = '<ul class="navigation">' . "\n";
			require_once dirname(dirname(dirname(__FILE__))) . '/CMS/permissions.php';
			
			$unread_messages = false;
			
			// set the date and time
			date_default_timezone_set($site->used_timezone());
			
			// update activity data
			$logged_in = true;
			if (getUserID() > 0)
			{
				// the execution of the query is not that time critical and it happens often -> LOW_PRIORITY
				$query = 'UPDATE LOW_PRIORITY `online_users` SET `last_activity`=';
				$query .= sqlSafeStringQuotes(date('Y-m-d H:i:s')) . ' WHERE `playerid`=' . sqlSafeStringQuotes(getUserID());
				@mysql_select_db($site->db_used_name(), $connection);
				@mysql_query($query, $connection);
				
				// are there unread messages?
				// are there unread messages?
				$query = ('SELECT `id` FROM `messages_users_connection` WHERE `msg_status`='
						  . sqlSafeStringQuotes('new')
						  . ' AND `playerid`=' . sqlSafeStringQuotes(getUserID())
						  . ' LIMIT 1');
				$result = @mysql_query($query, $connection);
				$rows = (int) @mysql_num_rows($result);
				if ($rows > 0)
				{
					$unread_messages = true;
				}
				mysql_free_result($result);
			} else
			{
				$logged_in = false;
			}
			
			$name = $site->base_name();
			
			// public_html on FreeBSD or Sites on Mac OS X
			$topDir = 'public_html';
			// top level dir depends on siteconfig
			
			$pos = strrpos(dirname(dirname(dirname(__FILE__))), '/');
			
			if ($pos !== false)
			{
				$topDir = substr(dirname(dirname(dirname(__FILE__))), $pos+1);;
			}
			
			$topDir = strcmp($name, $topDir) === 0;
			
			if (!$logged_in)
			{
				$menu[] = $this->writeLink('Login/', 'Login', (strcmp($name, 'Login') == 0));
			}
			if ($topDir)
			{
				if (count($_GET) === 0)
				{
					$menu[] = '<li>Home</li>' . "\n";
				} else
				{
					$menu[] = '<li><a class="current_nav_entry" href="' . (baseaddress()) . '">Home</a></li>' . "\n";
				}
			} else
			{
				$menu[] = '<li><a href="' . baseaddress() . '">Home</a></li>' . "\n";
			}
			
			if ((isset($_SESSION['user_logged_in'])) && ($_SESSION['user_logged_in']))
			{
				if ($unread_messages)
				{
					$menu[] = $this->writeLink('Messages/', 'Mail', (strcmp($name, 'Messages') == 0), true);
				} else
				{
					$menu[] = $this->writeLink('Messages/', 'Mail', (strcmp($name, 'Messages') == 0));
				}
			}
			
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0));
			
			$menu[] = $this->writeLink('Matches/', 'Matches', (strcmp($name, 'Matches') == 0));
			
			$menu[] = $this->writeLink('Teams/', 'Teams', (strcmp($name, 'Teams') == 0));
			
			$menu[] = $this->writeLink('Players/', 'Players', (strcmp($name, 'Players') == 0));
			
			if ($logged_in && (isset($_SESSION['allow_view_user_visits'])) && ($_SESSION['allow_view_user_visits']))
			{
				$menu[] = $this->writeLink('Visits/', 'Visits', (strcmp($name, 'Visits') == 0));
			}
			
			$menu[] = $this->writeLink('Rules/', 'Rules', (strcmp($name, 'Rules') == 0));
			
			$menu[] = $this->writeLink('FAQ/', 'FAQ', (strcmp($name, 'FAQ') == 0));
			
			$menu[] = $this->writeLink('Links/', 'Links', (strcmp($name, 'Links') == 0));
			
			$menu[] = $this->writeLink('Contact/', 'Contact', (strcmp($name, 'Contact') == 0));
			
			$menu[] = $this->writeLink('Bans/', 'Bans', (strcmp($name, 'Bans') == 0));
			
			if ($logged_in && (isset($_SESSION['allow_watch_servertracker'])) && ($_SESSION['allow_watch_servertracker']))
			{
				$menu[] = $this->writeLink('Servertracker/', 'Servers', (strcmp($name, 'Servertracker') == 0));
			}
			
			$menu[] = $this->writeLink('Config/', 'Config', (strcmp($name, 'Config') == 0));
			
			$menu[] = '</ul>';
			return $menu;
		}
	}
?>