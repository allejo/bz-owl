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