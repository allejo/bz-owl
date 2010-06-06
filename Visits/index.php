<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	
	$display_page_title = 'Visits log';
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require realpath('../CMS/navi.inc');
	
	$site = new siteinfo();
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_user';
	$viewerid = (int) getUserID();
	
	$allow_view_user_visits = false;
	if (isset($_SESSION['allow_view_user_visits']))
	{
		if (($_SESSION['allow_view_user_visits']) === true)
		{
			$allow_view_user_visits = true;
		}
	}
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0)
	{
		echo '<p>You need to login in order to view the visits log!</p>';
		$site->dieAndEndPage('');
	}
	
	// only allow looking when having the permission
	if ($allow_view_user_visits === false)
	{
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . 'have no permissions to view the visits log!');
	}
	
	// form letting search for ip-address or host
	// this form is considered not to be dangerous, thus no key checking at all and also using the get method
	echo "\n" . '<form enctype="application/x-www-form-urlencoded" method="get" action="./">' . "\n";
	
	// input string
	echo '<div style="display:inline"><label for="visit_search_string">Search for: </label>' . "\n";
	echo '<span><input type="text" id="visit_search_string" name="search_string"';
	if (isset($_GET['search']))
	{
		echo ' value="' . $_GET['search_string'] . '"';	
	}
	echo '>';
	echo '</span></div> ' . "\n";
	
	// looking for either ip-address or host?
	echo '<div style="display:inline"><label for="visit_search_type">Search for: </label>' . "\n";
	echo '<span><select id="visit_search_type" name="search_type">';
	echo '<option>ip-address</option>';
	echo '<option';
	
	// search for ip-address by default
	$search_type = 'ip-address';
	if (isset($_GET['search_type']))
	{
		if (!(strcmp($_GET['search_type'], $search_type) === 0))
		{
			// avoid to let the user enter a custom table column at all costs
			// only let them switch between ip-address and host search
			echo ' selected="selected"';
		}
	}
	echo '>host</option>';
	echo '</select></span></div> ' . "\n";
	
	echo '<div style="display:inline"> <input type="submit" name="search" value="Search" id="send"></div>' . "\n";
	echo '</form>' . "\n";
	
	// search for either ip-address or host
	if (isset($_GET['search']))
	{
		echo '<a class="button" href="./">overview</a>' . "\n";
		
		// check if a different search type than the default one was used
		$search_for_host = false;
		if (isset($_GET['search_type']))
		{
			if (!(strcmp($_GET['search_type'], $search_type) === 0))
			{
				// avoid to let the user enter a custom table column at all costs
				// only let them switch between ip-address and host search
				$search_type = 'host';
				$search_for_host = true;
			}
		}
		
		// search for nothing by default
		$search_expression = '';
		if (isset($_GET['search_string']))
		{
			$search_expression = $_GET['search_string'];
		}
		// people like to use * as wildcard
		$search_expression = str_replace('*', '%', $search_expression);
		
		// get list of last 200 visits
		$query = 'SELECT `visits`.`playerid`,`players`.`name`,`visits`.`ip-address`,`visits`.`host`,`visits`.`timestamp` FROM `visits`,`players` ';
		$query .= 'WHERE `visits`.`playerid`=`players`.`id` AND `visits`.`' . sqlSafeString($search_type);
		if ($search_for_host)
		{
			$query .= '` LIKE ' . "'" . sqlSafeString($search_expression) . '%' . "'";
		} else
		{
			$query .= '` LIKE ' . "'" . sqlSafeString($search_expression) . '%' . "'";
		}
		$query .= ' ORDER BY `visits`.`id` DESC LIMIT 0,200';
		
		if (!($result = @$site->execute_query($site->db_used_name(), 'visits, players', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		// sadly while searching the no results case should be handled
		if ((int) mysql_num_rows($result) < 1)
		{
			mysql_free_result($result);
			echo '<p>There were no matches for that expression in the visits log.</p>';
			$site->dieAndEndPage('');
		}
		
		echo "\n" . '<table class="table_team_members">' . "\n";
		echo '<caption>Search related visits log entries</caption>' . "\n";
		echo '<tr>' . "\n";
		echo '	<th>Name</th>' . "\n";
		echo '	<th>ip-address</th>' . "\n";
		echo '	<th>host</th>' . "\n";
		echo '	<th>login time</th>' . "\n";
		echo '</tr>' . "\n\n";
		
		// print out each entry
		while($row = mysql_fetch_array($result))
		{
			echo '<tr>' . "\n";
			echo '	<td><a href="./?profile=' . htmlspecialchars($row['playerid']) . '">';
			echo htmlentities($row['name']);
			echo '</a></td>' . "\n";
			echo '	<td>' . htmlentities($row['ip-address']) . '</td>' . "\n";
			echo '	<td>' . htmlentities($row['host']) . '</td>' . "\n";
			echo '	<td>' . htmlentities($row['timestamp']) . '</td>' . "\n";
			echo '</tr>' . "\n";
		}
		mysql_free_result($result);
		echo '</table>' . "\n";
		
		// done with the search
		$site->dieAndEndPage('');
	}
	
	if (isset($_GET['profile']))
	{
		$profile = $_GET['profile'];
		
		// need an overview button to enable navigation within the page
		echo '<a class="button" href="./">overview</a>' . "\n";
		
		if ($profile < 0)
		{
			echo '<p>You tried to view the visits log of a not existing user!</p>';
			$site->dieAndEndPage('');
		}
		
		if ($profile === 0)
		{
			echo '<p>The user id 0 is reserved for not logged in players and thus no user with that id could ever exist.</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		$query = 'SELECT `name` FROM `players` WHERE `players`.`id`=' . sqlSafeStringQuotes($profile) . ' LIMIT 1';
		if (!($result = @$site->execute_query($site->db_used_name(), 'players', $query, $connection)))
		{
			$site->dieAndEndPage('<p>It seems like the name of player with id ' . sqlSafeStringQuotes(htmlent($profile)) . ' can not be accessed for an unknown reason.</p>');
		}
		
		// existance test of user skipped intentionally
		// if the user does not exist, there will be no visits for him
		
		// sanity checks passed
		
		// get the name of the player in question
		$player_name = '(no player name)';
		while ($row = mysql_fetch_array($result))
		{
			$player_name = $row['name'];
		}
		mysql_free_result($result);
		$html_save_player_name = htmlent($player_name);
		
		// collect visits list of that player
		// example query: SELECT `ip-address`, `host` FROM `visits` WHERE `playerid`='16'
		$query = 'SELECT `ip-address`, `host`, `timestamp` FROM `visits` WHERE `playerid`=' . "'" . sqlSafeString($profile) . "'";
		// only get first 200 entries by default
		$query .= ' ORDER BY `id` DESC ';
		$query .= ' LIMIT 0,200';
		if (!($result = @$site->execute_query($site->db_used_name(), 'visits', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		if ((int) mysql_num_rows($result) < 1)
		{
			$site->dieAndEndPage('There are no visits by this user (id=' . sqlSafeString(htmlent($profile)) . '). Make sure the user is not deleted.');
		}
		
		// format the output with a nice table
		echo "\n" . '<table class="table_team_members">' . "\n";
		echo '<caption>Visits log of player ' . $html_save_player_name . '</caption>' . "\n";
		echo '<tr>' . "\n";
		echo '	<th>Name</th>' . "\n";
		echo '	<th>ip-address</th>' . "\n";
		echo '	<th>host</th>' . "\n";
		echo '	<th>login time</th>' . "\n";
		echo '</tr>' . "\n\n";
		
		// print out each entry
		while($row = mysql_fetch_array($result))
		{
			echo '<tr>' . "\n";
			echo '	<td> ' . $html_save_player_name . '</td>' . "\n";
			echo '	<td>' . htmlentities($row['ip-address']) . '</td>' . "\n";
			echo '	<td>' . htmlentities($row['host']) . '</td>' . "\n";
			echo '	<td>' . htmlentities($row['timestamp']) . '</td>' . "\n";
			echo '</tr>' . "\n";
		}
		mysql_free_result($result);
		echo '</table>' . "\n";
		
		// done with the player profile
		$site->dieAndEndPage('');
	}
	
	// display visits log overview
		
	// get list of last 200 visits
	$query = 'SELECT `visits`.`playerid`,`players`.`name`,`visits`.`ip-address`,`visits`.`host`,`visits`.`timestamp` FROM `visits`,`players`';
	$query .= ' WHERE `visits`.`playerid`=`players`.`id` ORDER BY `visits`.`id` DESC  LIMIT 0,200';
	if (!($result = @$site->execute_query($site->db_used_name(), 'visits, players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// for performance reasons the case with no visits will be skipped
	// in that case the table would have no entries
	// however as someone needs to login there should be always at least one ip-address in the table
	// except the table got deleted after someone logged in and the user then looks at the visits log
	// format the output with a nice table
	echo "\n" . '<table class="table_team_members">' . "\n";
	echo '<caption>Visits log of all players</caption>' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Name</th>' . "\n";
	echo '	<th>ip-address</th>' . "\n";
	echo '	<th>host</th>' . "\n";
	echo '	<th>login time</th>' . "\n";
	echo '</tr>' . "\n\n";
	
	// print out each entry
	while($row = mysql_fetch_array($result))
	{
		echo '<tr>' . "\n";
		echo '	<td><a href="./?profile=' . htmlspecialchars($row['playerid']) . '">';
		echo htmlentities($row['name']);
		echo '</a></td>' . "\n";
		echo '	<td>' . htmlentities($row['ip-address']) . '</td>' . "\n";
		echo '	<td>' . htmlentities($row['host']) . '</td>' . "\n";
		echo '	<td>' . htmlentities($row['timestamp']) . '</td>' . "\n";
		echo '</tr>' . "\n";
	}
	mysql_free_result($result);
	echo '</table>' . "\n";
?>
</div>
</body>
</html>
