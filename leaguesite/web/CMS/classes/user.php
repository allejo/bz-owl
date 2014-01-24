<?php
	// handle user related data
	class user
	{
		private $userid = 0;
		public function __construct($userid = 0)
		{
			// instance based on current user should be built
			$this->userid = $userid;
			
			// set default permissions for all users one time
			// make sure not to do it more than once because it would reset perms then
			// TODO: getPermission should return default permission instead
			if (!isset($_SESSION['defaultPermissionsSet']))
			{
				$this->setDefaultPermissions();
				$_SESSION['defaultPermissionsSet'] = true;
			}
		}
		
		public static function getCurrentUserId()
		{
			$userid = 0;
			
			if (static::getCurrentUserLoggedIn() && isset($_SESSION['viewerid']))
			{
				$userid = $_SESSION['viewerid'];
			}
			
			return (int) $userid;
		}
		
		public static function getCurrentUserLoggedIn()
		{
			return (isset($_SESSION['user_logged_in']) && ($_SESSION['user_logged_in'] === true));
		}
		
		// id > 0 means a user is logged in
		function getID()
		{
			$userid = 0;
			
			if (static::getCurrentUserLoggedIn() && isset($_SESSION['viewerid']))
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
			
			// set default theme based on site preferences
			if ($this->getMobile())
			{
				$default_theme = $config->getValue('defaultMobileTheme');
			} else
			{
				$default_theme = $config->getValue('defaultTheme');
			}
			
			// find out which theme to use
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
			if (!preg_match('/^[0-9A-Za-z ]+$/', $theme))
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
		
		function getName($userid = 0)
		{
			global $config;
			global $db;
			
			// returns current user if no userid specified, otherwise name of user of supplied userid
			if ($userid === 0)
			{
				$id = $this->userid;
			} else
			{
				$id = $userid;
			}
			
			if ($id === 0)
			{
				return $config->getValue('displayedSystemUsername');
			}
			
			
			// collect name from database
			$query = $db->prepare('SELECT `name` FROM `users` WHERE `id`=:id LIMIT 1');
			if ($db->execute($query, array(':id' => array($id, PDO::PARAM_INT))))
			{
				$userName = $db->fetchRow($query);
				$db->free($query);
				
				return $userName['name'];
			}
			
			// error handling: log error and show it in end user visible result
			$db->logError((__FILE__) . ': getName(' . strval($userid) . ') failed: transformed into (.' . $id . ').'); 
			return '$user->getName(' . strval($userid) . ') failed.';
		}
		
		function setPermission($permission, $value=true)
		{
			$_SESSION[$permission] = $value;
		}
		
		
		function setDefaultPermissions()
		{
			// sets user's permisssions to default ones
			
			// can change debug sql setting
			$this->setPermission('allow_change_debug_sql', false);
			
			// set all permission to default values
			// permissions for news page
			$this->setPermission('allow_set_different_news_author', false);
			$this->setPermission('allow_add_news', false);
			$this->setPermission('allow_edit_news', false);
			$this->setPermission('allow_delete_news', false);
			
			// permissions for all static pages
			$this->setPermission('allow_edit_static_pages', false);
			
			// permissions for gallery pages
			$this->setPermission('allow_list_gallery', true);
			$this->setPermission('allow_view_gallery', true);
			$this->setPermission('allow_add_gallery', false);
			$this->setPermission('allow_edit_gallery', false);
			$this->setPermission('allow_delete_gallery', false);

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
		
		
		function saveTheme($theme)
		{
			global $config;
			
			// save theme for two months
			setcookie('theme', $theme, time()+60*60*24*30*2, $config->getValue('basepath'), $config->getValue('domain'), 0);
			
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
		
		public function getLoggedIn()
		{
			return parent::getCurrentUserLoggedIn();
		}
		
		// logout the user
		function logout()
		{
			global $db;
			
			
			// remove user from online user list
			$query = $db->prepare('DELETE FROM `online_users` WHERE `userid`=:uid');
			$db->execute($query, array(':uid' => array($this->getID(), PDO::PARAM_INT)));
			
			// fill login related session variables with default data
			$_SESSION['user_logged_in'] = false;
			$_SESSION['viewerid'] = -1;
			
			// reset permissions back to standard
			$this->setDefaultPermissions();
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
