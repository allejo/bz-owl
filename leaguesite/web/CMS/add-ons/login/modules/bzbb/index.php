<?php
if ( (isset($_GET['bzbbauth'])) && ($_GET['bzbbauth']) )
{
	require 'checkToken.php';
	
	// initialise permissions
	$user->removeAllPermissions();
	
	// groups used for permissions
	// each group can use the fine grained permission system
	$groups = Array ('VERIFIED','GU-LEAGUE.REFEREES','GU-LEAGUE.ADMINS');
	$args = explode (',', urldecode($_GET['bzbbauth']));
	// $args[0] is token, $args[1] is callsign
	if (!$info = validate_token ($args[0], $args[1], $groups, false))
	{
		// login did not work, removing permissions not necessary as additional permissions where never granted
		// after permissions were removed at the beginning of the file
		
		$helper->done('<p class="first_p">Login failed: The returned values could not be validated! You may check your username and password.</p>' . "\n"
					. '<p>Please <a href="./">try again</a>.</p>' . "\n");
	}
	
	// NOTE: invalid bzid should be set to -1
	
	// assume user is in the
	// VERIFIED group
	// because login worked
		
	// since we use a global login for auth any user should be in that group
	$_SESSION['username'] = $info['username'];
	$external_login_id = $info['bzid'];
	$_SESSION['bzid'] = $external_login_id;
	$_SESSION['user_logged_in'] = true;
	
	// permissions for private messages
	$user->setPermission('allow_add_messages');
	$user->setPermission('allow_delete_messages');
	
	// server tracker permissions
	$user->setPermission('allow_watch_servertracker');
	
	
	
	// test only for GU-LEAGUE.ADMINS group
	$group_test = array_slice($groups, 1, 1);
	$in_group = false;
	foreach ($info['groups'] as $one_group)
	{
		// case insensitive comparison
		if (strcasecmp($one_group, $group_test[0]) === 0)
		{
			$in_group = true;
			break;
		}
	}
	unset($one_group);
	
	if ($in_group === true)
	{
		if ($db->getDebugSQL())
		{
			$helper->addMsg('<p>gu league referee detected</p>');
		}
		// GU-LEAGUE.REFEREES group
		
		// match permissions
		$user->setPermission('allow_add_match');
		$user->setPermission('allow_edit_match');
	}
	
	// test only for GU-LEAGUE.ADMINS group
	$in_group = false;
	$group_test = array_slice($groups, -1, 1);
	foreach ($info['groups'] as $one_group)
	{
		// case insensitive comparison
		if (strcasecmp($one_group, $group_test[0]) === 0)
		{
			$in_group = true;
			break;
		}
	}
	if ($in_group === true)
	{
		if ($db->getDebugSQL())
		{
			$helper->addMsg('<p>gu league admin detected</p>');
		}
		// GU-LEAGUE.ADMINS group
		
		// can change debug sql setting
		$user->setPermission('allow_change_debug_sql');
		
		// permissions for news page
		$user->setPermission('allow_add_news');
		$user->setPermission('allow_edit_news');
		$user->setPermission('allow_delete_news');
		
		// permissions for all static pages
		$user->setPermission('allow_edit_static_pages');
		
		// permissions for bans page
		$user->setPermission('allow_add_bans');
		$user->setPermission('allow_edit_bans');;
		$user->setPermission('allow_delete_bans');
		
		// permissions for team page
		$user->setPermission('allow_kick_any_team_members');
		$user->setPermission('allow_edit_any_team_profile');
		$user->setPermission('allow_invite_in_any_team');
		$user->setPermission('allow_delete_any_team');
		$user->setPermission('allow_reactivate_teams');
		
		// user permissions
		$user->setPermission('allow_edit_any_user_profile');
		$user->setPermission('allow_add_admin_comments_to_user_profile');
		$user->setPermission('allow_ban_any_user');
		
		// visits log permissions
		$user->setPermission('allow_view_user_visits');
		
		// match permissions
		$user->setPermission('allow_add_match');
		$user->setPermission('allow_edit_match');
		$user->setPermission('allow_delete_match');
		
		// server tracker permissions
		$user->setPermission('allow_watch_servertracker');
		
		// TODO permissions
		$user->setPermission('allow_view_todo');
		$user->setPermission('allow_edit_todo');
		
		// aux permissions
		$user->setPermission('is_admin');
	}
	
	if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
	{
		if (isset($_SESSION['bzid']) && (!(strcmp($_SESSION['bzid'], '-1') == 0) || !(strcmp($_SESSION['bzid'], '0') == 0)))
		{
			$_SESSION['external_id'] = $_SESSION['bzid'];
		}
		
		$_SESSION['external_login'] = true;
//		echo '<p class="first_p">Login information validated!</p>' . "\n";
	}
}
?>