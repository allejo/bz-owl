<?php
	class menu
	{
		function writeLink($folder, $title, $current=false, $unread=false, $img='')
		{
			global $config;
			global $tmpl;
			global $user;
			
			$link = '<li>';
			if (!$current)
			{
				$link .= '<a ';
				if ($unread)
				{
					$link .= 'class="unread" ';
				}
				$link .= 'href="' . ($config->value('baseaddress') . $folder) . '">';
			} elseif (count($_GET) > 0)
			{
				$link .= '<a class="current_nav_entry" href="' . ($config->value('baseaddress') . $folder) . '">';
			}
			if (strlen($img) > 0)
			{
				$link .= $tmpl->return_self_closing_tag('img src="'
														. $config->value('baseaddress') . 'styles/'
														. $user->getStyle() . '/img/'
														. $img . '" alt="' . $title . '"');
			} else
			{
				$link .= $title;
			}
			if (!$current || (count($_GET) > 0))
			{
				$link .= '</a>';
			}
			$link .= '</li>' . "\n";
			
			return	$link;
		}
		
		function createMenu()
		{
			global $config;
			global $user;
			global $db;
			global $site;
			
			// menu is returned as array
			// each entry in the array will be a new line
			$menu = array();
			$menu[] = '<ul class="navigation">' . "\n";
			require_once dirname(dirname(dirname(__FILE__))) . '/CMS/Login/permissions.php';
			
			$unread_messages = false;
			
			// update activity data
			$logged_in = true;
			if ($user->getID() > 0)
			{
				// the execution of the query is not that time critical and it happens often -> LOW_PRIORITY
				$query = $db->prepare('UPDATE LOW_PRIORITY `online_users` SET `last_activity`=?'
									  . ' WHERE `playerid`=?');
				$db->execute($query, array(date('Y-m-d H:i:s'), $user->getID()));
				
				// are there unread messages?
				$query = $db->prepare('SELECT `msgid` FROM `pmSystem.Msg.Users` WHERE `msg_status`=?'
									  . ' AND `playerid`=?'
									  . ' LIMIT 1');
				
				$result = $db->execute($query, array('new', $user->getID()));;
				$rows = $query->rowCount();
				if ($rows > 0)
				{
					$unread_messages = true;
				}
			} else
			{
				$logged_in = false;
			}
			
			$name = $site->basename();
			
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
					$menu[] = '<li><a class="current_nav_entry" href="' . ($config->value('baseaddress')) . '">Home</a></li>' . "\n";
				}
			} else
			{
				$menu[] = '<li><a href="' . $config->value('baseaddress') . '">Home</a></li>' . "\n";
			}
			
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea_3D.png');
/*
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idea.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idee_pfui.png');
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0), false, 'button_idee_rund.png');
*/
			
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