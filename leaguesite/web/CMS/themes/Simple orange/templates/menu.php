<?php
	class menu
	{
		function writeLink($folder, $title, $current=false, $unread=false)
		{
			global $config;
			
			if (!$current && isset($_GET['path']))
			{
				if (strcmp($folder, $_GET['path']) === 0)
				{
					$current = true;
				}
			}
			
			$showCurNavLink = ((!isset($_GET['path']) && count($_GET) > 0) || (isset($_GET['path']) && count($_GET) > 1));
			
			$link = '<li>';
			if (!$current)
			{
				$link .= '<a ';
				if ($unread)
				{
					$link .= 'class="unread" ';
				}
				$link .= 'href="' . ($config->getValue('baseaddress') . $folder) . '">';
			} elseif ($showCurNavLink)
			{
				$link .= '<a class="current_nav_entry" href="' . ($config->getValue('baseaddress') . $folder) . '">';
			}
			$link .= $title;
			if (!$current || $showCurNavLink)
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
			$menu[] = '<div class="navigationBox"><ul class="navigation">' . "\n";
			
			$unread_messages = false;
			
			// update activity data
			$logged_in = true;
			if ($user->getID() > 0)
			{
				// the execution of the query is not that time critical and it happens often -> LOW_PRIORITY
				$query = $db->prepare('UPDATE LOW_PRIORITY `online_users` SET `last_activity`=?'
									  . ' WHERE `userid`=?');
				$db->execute($query, array(date('Y-m-d H:i:s'), $user->getID()));
				
				// are there unread messages?
				$query = $db->prepare('SELECT `msgid` FROM `pmsystem_msg_users` WHERE `msg_status`=?'
									  . ' AND `userid`=?'
									  . ' LIMIT 1');
				
				$result = $db->execute($query, array('new', $user->getID()));;
				$rows = $db->rowCount($query);
				if ($rows > 0)
				{
					$unread_messages = true;
				}
			} else
			{
				$logged_in = false;
			}
			
			$name = $site->basename();
			

			// top level dir has either no path set or it's /
			$topDir = !isset($_GET['path']) || (strcmp($_GET['path'], '/') === 0);
			
			if (!$logged_in)
			{
				$menu[] = $this->writeLink('Login/', 'Login', (strcmp($name, 'Login') == 0));
			}
			if ($topDir)
			{
				$menu[] = $this->writeLink('', 'Home' , !isset($_GET['path']));
			} else
			{
				$menu[] = '<li><a href="' . $config->getValue('baseaddress') . '">Home</a></li>' . "\n";
			}
			
			if ($user->getPermission('user_logged_in'))
			{
				if ($unread_messages)
				{
					$menu[] = $this->writeLink('PM/', 'Mail', (strcmp($name, 'PM') == 0), true);
				} else
				{
					$menu[] = $this->writeLink('PM/', 'Mail', (strcmp($name, 'PM') == 0));
				}
			}
			
			$menu[] = $this->writeLink('News/', 'News', (strcmp($name, 'News') == 0));
			
			
			$menu[] = '<li><img src="' . $config->getValue('baseaddress') . 'themes/Simple%20orange/img/matches.png" /></li>';
			$menu[] = '<li><img src="' . $config->getValue('baseaddress') . 'themes/Simple%20orange/img/teams.png" /></li>';
			$menu[] = '<li><img src="' . $config->getValue('baseaddress') . 'themes/Simple%20orange/img/players.png" /></li>';
			$menu[] = '<li><img src="' . $config->getValue('baseaddress') . 'themes/Simple%20orange/img/settings.png" /></li>';
//			$menu[] = $this->writeLink('Matches/', 'Matches', (strcmp($name, 'Matches') == 0));			
//			$menu[] = $this->writeLink('Teams/', 'Teams', (strcmp($name, 'Teams') == 0));
			
//			$menu[] = $this->writeLink('Players/', 'Players', (strcmp($name, 'Players') == 0));
			
			if ($logged_in && ($user->getPermission('allow_view_user_visits')))
			{
				$menu[] = $this->writeLink('Visits/', 'Visits', (strcmp($name, 'Visits') == 0));
			}
			
			$menu[] = $this->writeLink('Rules/', 'Rules', (strcmp($name, 'Rules') == 0));
			
			$menu[] = $this->writeLink('FAQ/', 'FAQ', (strcmp($name, 'FAQ') == 0));
			
			$menu[] = $this->writeLink('Links/', 'Links', (strcmp($name, 'Links') == 0));
			
			$menu[] = $this->writeLink('Contact/', 'Contact', (strcmp($name, 'Contact') == 0));
			
			$menu[] = $this->writeLink('Bans/', 'Bans', (strcmp($name, 'Bans') == 0));
			
			if ($logged_in && ($user->getPermission('allow_watch_servertracker')))
			{
				$menu[] = $this->writeLink('Servertracker/', 'Servers', (strcmp($name, 'Servertracker') == 0));
			}
			
//			$menu[] = $this->writeLink('Config/', 'Config', (strcmp($name, 'Config') == 0));
			
			$menu[] = '</ul></div>';
			
			return $menu;
		}
	}
?>
