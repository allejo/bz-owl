<?php
	// handle user related data
	class user
	{
		// id > 0 means a user is logged in
		function getID()
		{
			$userid = 0;
			
			if ($this->loggedIn() && isset($_SESSION['viewerid']))
			{
				$userid = $_SESSION['viewerid'];
			}
			
			return (int) $userid;
		}
		
		function setID($id=0)
		{
			$_SESSION['viewerid'] = intval($id);
			$_SESSION['user_logged_in'] = (intval($id) > 0) ?  true : false;
		}
		
		
		function getTheme()
		{
			global $config;
			
			if ($this->getMobile())
			{
				$default_theme = $config->value('defaultMobileTheme');
			} else
			{
				$default_theme = $config->value('defaultTheme');
			}
			
			$theme = $default_theme;
			if (isset($_SESSION['theme']))
			{
				// use theme chosen this session
				$theme = $_SESSION['theme'];
			} else
			{
				// otherwise use cookie
				if (isset($_COOKIE['theme']))
				{
					// cookies turned on
					$theme = $_COOKIE['theme'];
				}
			}
			
			// clean theme name
			if (!preg_match('/^[0-9A-Za-z]+$/', $theme))
			{
				$theme = $default_theme;
			}
			
			if (!(file_exists(dirname(dirname(dirname(__FILE__))) . '/themes/' . $theme . '/' . $theme . '.css')))
			{
				// stylesheet in question does not exist, go back to default
				$theme = $default_theme;
				
				// save theme
				$this->saveTheme($theme);
			}
			
			if (strcasecmp($theme, '') == 0)
			{
				// nothing is set, go back to default
				$theme = $default_style;
			}
			
			if (!(isset($_SESSION['themeSaved'])) || !$_SESSION['themeSaved'])
			{
				$this->saveTheme($theme);
			}
			
			return $theme;
		}
		
		
		function getPermission($permission)
		{
			if (isset($_SESSION[$permission]))
			{
				return $_SESSION[$permission];
			}
			
			return false;
		}
		
		function setPermission($permission, $value=true)
		{
			$_SESSION[$permission] = $value;
		}
		
		function saveTheme($theme)
		{
			global $config;
			
			// save theme for two months
			setcookie('theme', $theme, time()+60*60*24*30*2, $config->value('basepath'), $config->value('domain'), 0);
			
			// save it in session based variable if setting cookie failed
			// it could fail because user did not accept cookie for instance
			$_SESSION['theme'] = $theme;
			
			// mark it saved
			$_SESSION['themeSaved'] = true;
		}
		
		
		function getMobile()
		{
			// this switch should be used sparingly and only in cases where content would not fit on the display
			if (isset($_SERVER['HTTP_USER_AGENT']))
			{
				$browser = $_SERVER['HTTP_USER_AGENT'];
				if (preg_match("/.(Mobile|mobile)/", $browser))
				{
					// mobile browser
					return true;
				} else {
					return false;
				}
			} else
			{
				return false;
			}
		}
		
		
		function loggedIn()
		{
			return (isset($_SESSION['user_logged_in']) && ($_SESSION['user_logged_in'] === true));
		}
		
		
		// logout the user
		function logout()
		{
			// reset all session variables of this session
			session_unset();
			
			// fill login related session variables with default data
			$_SESSION['user_logged_in'] = false;
			$_SESSION['viewerid'] = -1;
		}
		
		function removeAllPermissions()
		{
			// delete bzid
			unset($_SESSION['bzid']);
			
			// no external id by default
			$_SESSION['external_id'] = 0;
			
			// assume local login by default
			$this->setPermission('external_login', false);
			
			// can change debug sql setting
			$this->setPermission('allow_change_debug_sql', false);
			
			// set all permission to false by default
			// permissions for news page
			$this->setPermission('allow_set_different_news_author', false);
			$this->setPermission('allow_add_news', false);
			$this->setPermission('allow_edit_news', false);
			$this->setPermission('allow_delete_news', false);
			
			// permissions for all static pages
			$this->setPermission('allow_edit_static_pages', false);
			
			// permissions for bans page
			$this->setPermission('allow_set_different_bans_author', false);
			$this->setPermission('allow_add_bans', false);
			$this->setPermission('allow_edit_bans', false);
			$this->setPermission('allow_delete_bans', false);
			
			// permissions for private messages
			$this->setPermission('allow_add_messages', false);
			// private messages are never supposed to be edited at all by a 3rd person
			$this->setPermission('allow_edit_messages', false);
			$this->setPermission('allow_delete_messages', false);
			
			// team permissions
			$this->setPermission('allow_kick_any_team_members', false);
			$this->setPermission('allow_edit_any_team_profile', false);
			$this->setPermission('allow_delete_any_team', false);
			$this->setPermission('allow_invite_in_any_team', false);
			$this->setPermission('allow_reactivate_teams', false);
			
			
			// user permissions
			$this->setPermission('allow_edit_any_user_profile', false);
			$this->setPermission('allow_add_admin_comments_to_user_profile', false);
			$this->setPermission('allow_ban_any_user', false);
			
			// visits log permissions
			$this->setPermission('allow_view_user_visits', false);
			
			// match permissions
			$this->setPermission('allow_add_match', false);
			$this->setPermission('allow_edit_match', false);
			$this->setPermission('allow_delete_match', false);
			
			// server tracker permissions
			$this->setPermission('allow_watch_servertracker', false);
			
			// TODO permissions
			$this->setPermission('allow_view_todo', false);
			$this->setPermission('allow_edit_todo', false);
			
			// aux permissions
			$this->setPermission('IsAdmin', false);
		}
	}
?>
