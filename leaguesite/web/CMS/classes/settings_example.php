<?php
	
	function settings()
	{
		return array(
					 'dbHost' => '127.0.0.1',
					 'dbUser' => 'insert database user here',
					 'dbPw' => 'insert database password here',
					 'dbName' => 'bz-owl',
					 // nl2br needs php newer or equal to 4.0.5 to support xhtml
					 // if php version is higher or equal to 4.0.5 but lower than 5.3
					 // then xhtml will be always on
					 // if php version is lower than 4.0.5 then xhtml will be always off
					 // see http://www.php.net/manual/en/function.nl2br.php
					 'useXhtml' => true,
					 // either return an empty string or path to favicon
					 'favicon' => '',
					 // set the timezone used
					 // look at http://www.php.net/manual/en/timezones.php
					 // for a complete list of supported timezones
					 'timezone' => 'UTC',
					 // shut entire site down (e.g. during install or update)
					 'maintenance.now' => false,
					 // message shown during maintenance.now
					 'maintenance.msg' => 'This site has been shut down due to maintenance' . "\n",
					 // enable updating db from previous version
					 'maintenance.updateDB' => false,
					 // display local login on front login page
					 'login.local.showLoginText' => false,
					 // accepted bzbb login groups
					 'login.bzbb.groups' => array('VERIFIED' => array('name' => 'VERIFIED',
																	  'permissions' => array('allow_add_messages' => true,
																							 'allow_delete_messages' => true,
																							 'allow_watch_servertracker' => true)),
												  'GU-LEAGUE.REFEREES' => array('name' => 'GU-LEAGUE.REFEREES',
																				'permissions' => array('allow_add_messages' => true,
																									   'allow_delete_messages' => true,
																									   'allow_watch_servertracker' => true,
																									   'allow_add_match' => true,
																									   'allow_edit_match' => true)),
												  'GU-LEAGUE.ADMINS' => array('name' => 'GU-LEAGUE.ADMINS',
																			  'permissions' => array('allow_add_messages' => true,
																									 'allow_delete_messages' => true,
																									 'allow_watch_servertracker' => true,
																									 'allow_add_match' => true,
																									 'allow_edit_match' => true,
																									 // permissions for news page
																									 'allow_add_news' => true,
																									 'allow_edit_news' => true,
																									 'allow_delete_news' => true,
																									 // permissions for all static pages
																									 'allow_edit_static_pages' => true,
																									 // permissions for bans page
																									 'allow_add_bans' => true,
																									 'allow_edit_bans' => true,
																									 'allow_delete_bans' => true,
																									 'allow_edit_bans' => true,
																									 // permissions for team page
																									 'allow_kick_any_team_members' => true,
																									 'allow_edit_any_team_profile' => true,
																									 'allow_invite_in_any_team' => true,
																									 'allow_delete_any_team' => true,
																									 'allow_reactivate_teams' => true,
																									 // user permissions
																									 'allow_edit_any_user_profile' => true,
																									 'allow_add_admin_comments_to_user_profile' => true,
																									 'allow_ban_any_user' => true,
																									 // visits log permissions
																									 'allow_view_user_visits' => true,
																									 // match permissions
																									 'allow_add_match' => true,
																									 'allow_edit_match' => true,
																									 'allow_delete_match' => true,
																									 // server tracker permissions
																									 'allow_watch_servertracker' => true,
																									 // TODO permissions
																									 'allow_view_todo' => true,
																									 'allow_edit_todo' => true,
																									 // aux permissions
																									 'is_admin' => true))),
					 // regenerate session id after x seconds, default 15 minutes (60*15)
					 // the lower x: safer, higher x faster
					 'sessionRegenTime' => 60*15,
					 // logout a user after no action in x seconds, default 2 hours (60*60*2)
					 'logoutUserAfterXSecondsInactive' => 60*60*2,
					 // print out debug messages
					 'debugSQL' => true,
					 'domain' => 'example.com',
					 'basepath' => '/',
					 'baseaddress' => 'http://example.com/',
					 'forceExternalLoginOnly' => true,
					 'convertUsersToExternalLogin' => true,
					 'bbcodeLibAvailable' => true,
					 'displayedSystemUsername' => 'CTF League System',
					 // the name displayed in mails sent by the system
					 'oldWebsiteName' => 'gu.bzleague.com',
					 'bbcodeLibPath' => (dirname(__FILE__)) . '/nbbc-wrapper.php',
					 'bbcodeClass' => 'wrapper',
					 'bbcodeSetsLinebreaks' => true,
					 'bbcodeCommand' => 'Parse',
					 // specify default themes
					 'defaultMobileTheme' => 'White',
					 'defaultTheme' => '6x7',
					 // template engine paths
					 'tmpl.compiled' => dirname(__FILE__) . '/themes/compiled/',
					 'tmpl.cached' => dirname(__FILE__) . '/themes/cached/',
					 // where errors should be written if db connection is gone
					 // file must exist in order to be used
					 // do not forget to give PHP write perms to that file
					 'errorLogFile' => dirname(__FILE__) . '/errorlog.txt',
					 'test' => true
					 );
	}
	
	function database_to_be_imported()
	{
		return 'bzleague_guleague';
	}
	
	// make posts anonymous
	function force_username(&$section)
	{
		if (strcmp($section, 'bans') === 0)
		{
			return 'GU League Council';
		} else
		{
			return '';
		}
	}
    
	function bbcode_lib_path()
	{
		return ((dirname(__FILE__)) . '/nbbc-wrapper.php');
	}
	
	function bbcode_class()
	{
		return 'wrapper';
	}
	
	function bbcode_sets_linebreaks()
	{
		return true;
	}
	
	function bbcode_command()
	{
		return 'Parse';
	}
	
	function maintain_inactive_teams()
	{
		// remove or deactivate teams that do not match anymore?
		return true;
	}
	
	function maintain_inactive_teams_with_active_players()
	{
		// players from inactive team do login but they do not match
		// remove or deactive the teams with active players?
		return false;
	}
?>