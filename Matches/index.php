<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require ('../CMS/navi.inc');
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_matches';
	$viewerid = (int) getUserID();
	
	$allow_add_match = false;
	if (isset($_SESSION['allow_add_match']))
	{
		if (($_SESSION['allow_add_match']) === true)
		{
			$allow_add_match = true;
		}
	}
	
	$allow_edit_match = false;
	if (isset($_SESSION['allow_edit_match']))
	{
		if (($_SESSION['allow_edit_match']) === true)
		{
			$allow_edit_match = true;
		}
	}
	
	$allow_delete_match = false;
	if (isset($_SESSION['allow_delete_match']))
	{
		if (($_SESSION['allow_delete_match']) === true)
		{
			$allow_delete_match = true;
		}
	}	

	
	function get_table_checksum($site, $connection)
	{
		$query = 'CHECKSUM TABLE `matches`';
		
		if (!($result = @$site->execute_query($site->db_used_name(), 'matches', $query, $connection)))
		{
			// a severe problem with the table exists
			$site->dieAndEndPageNoBox('Checksum of the matches could not be generated');
		}
		
		$checksum = '';
		while($row = mysql_fetch_array($result))
		{
			$checksum = $row['Checksum'];
		}
		
		return $checksum;
	}
	
	function setTableUnchanged($site, $connection)
	{
		$_SESSION['checksum_matches'] = get_table_checksum($site, $connection);
	}
	
	function team_name_from_id($id, $name)
	{
		echo '<a href="../Teams?profile=' . ((int) $id) . '">' . $name . '</a>';
	}
	
	if (isset($_GET['enter']) || isset($_GET['edit']) || isset($_GET['delete']))
	{
		echo '<a class="button" href="./">overview</a>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		include_once('match_list_changing_logic.php');
		// all the operations requested have been dealt with
		$site->dieAndEndPage();
	}
	
	
	// form letting search for team name or time
	// this form is considered not to be dangerous, thus no key checking at all and also using the get method
	echo "\n" . '<form enctype="application/x-www-form-urlencoded" method="get" action="./">' . "\n";
	
	// input string
	echo '<div style="display:inline"><label for="visit_search_string">Search for:</label> ' . "\n";
	echo '<span><input type="text" id="visit_search_string" name="search_string"';
	if (isset($_GET['search']))
	{
		echo ' value="' . $_GET['search_string'] . '"';	
	}
	echo '>';
	echo '</span></div> ' . "\n";
	
	// looking for either team name or time
	echo '<div style="display:inline"><label for="visit_search_type">result type:</label> ' . "\n";
	echo '<span><select id="visit_search_type" name="search_type">';
	
	// avoid to let the user enter a custom table column at all costs
	// only let them switch between team name and time search
	
	// search for team name by default
	$search_type = '';
	$search_team = false;
	$search_time = false;
	
	if (isset($_GET['search_type']))
	{
		switch ($_GET['search_type'])
		{
			case 'team': $search_team = true; break;
			case 'time': $search_time = true; break;
			default: $search_team = true;
		}
	}
	
	echo '<option';
	if ($search_team)
	{
		$search_type = 'team';
		echo ' selected="selected"';
	}
	echo '>team name</option>';
	
	echo '<option';
	if ($search_time)
	{
		$search_type = 'time';
		echo ' selected="selected"';
	}
	echo '>time</option>';
	
	echo '</select></span>';
	
	echo ' <label for="match_search_result_amount">Entries:</label> ';
	echo '<span><select id="match_search_result_amount" name="match_result_amount">';
	echo '<option>200</option>';
	echo '<option>400</option>';
	echo '<option>800</option>';
	echo '<option>1600</option>';
	echo '<option>3200</option>';
	echo '</select></span>';
	echo '</div> ' . "\n";
	
	echo '<div style="display:inline"> <input type="submit" name="search" value="Search" id="send"></div>' . "\n";
	echo '</form>' . "\n";
	
	// end search toolbar
	
	
	if (isset($_GET['search']))
	{
		echo '<a class="button" href="./">overview</a>' . "\n";
	}
	if ($allow_add_match)
	{
		if (isset($_GET['search']))
		{
			$site->write_self_closing_tag('br');
			$site->write_self_closing_tag('br');
		}
		echo '<a class="button" href="./?enter">Enter a new match</a>' . "\n";
	}
	
	if (isset($_GET['search']))
	{
		// search for nothing by default
		$search_expression = '';
		if (isset($_GET['search_string']))
		{
			$search_expression = $_GET['search_string'];
		}
		// people like to use * as wildcard
		$search_expression = str_replace('*', '%', $search_expression);
	}
	
	if (isset($_GET['search']))
	{
		// outer select
		$query = 'SELECT * FROM';
		$query .= ' (SELECT `matches`.`timestamp`';
	} else
	{
		$query = 'SELECT `matches`.`timestamp`';
	}
		// get name of team 1
		$query .= ',(SELECT `name` FROM `teams` WHERE `matches`.`team1_teamid`=`teams`.`id` LIMIT 1) AS `team1_name`';
		// get name of team 2
		$query .= ',(SELECT `name` FROM `teams` WHERE `matches`.`team2_teamid`=`teams`.`id` LIMIT 1) AS `team2_name`';
		// also need the id's for quick links to team profiles
		$query .= ',`matches`.`team1_teamid`,`matches`.`team2_teamid`';
		// the rest of the needed data
		$query .= ',`matches`.`team1_points`,`matches`.`team2_points`,`players`.`name` AS `player_name`,`matches`.`id`';
		// the tables in question
		$query .= ' FROM `matches`,`players` WHERE `players`.`id`=`matches`.`playerid`';
	if (isset($_GET['search']))
	{
		// Every derived table must have its own alias
		$query .= ') AS `t1`';
		// now do the search thing
		if ($search_team)
		{
			// team name search
			$query .= 'WHERE `team1_name` LIKE ' . sqlSafeStringQuotes($search_expression);
			$query .= ' OR `team2_name` LIKE ' . sqlSafeStringQuotes($search_expression);
		} else
		{
			// timestamp search
			$query .= 'WHERE `timestamp` LIKE ' . sqlSafeStringQuotes($search_expression . '%');
		}
	}
	
	// newest matches first please
	$query .= ' ORDER BY `timestamp` DESC ';
	// limit the output to the requested rows to speed up displaying
	$query .= 'LIMIT ';
	// the "LIMIT 0,200" part of query means only the first 200 entries are received
	// the range of shown matches is set by the GET variable i
	$view_range = (int) 0;
	if (isset($_GET['i']))
	{
		if (((int) $_GET['i']) > 0)
		{
			$view_range = (int) $_GET['i'];
			$query .=  $view_range . ',';
		} else
		{
			// force write 0 for value 0 (speed)
			// and 0 for negative values (security: DBMS error handling prevention)
			$query .= '0,';
		}
	} else
	{
		// no special value set -> write 0 for value 0 (speed)
		$query .= '0,';
	}
	$query .= ((int) $view_range)+201;
	
	if (!($result = @$site->execute_query($site->db_used_name(), 'matches', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of matches could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);
	$show_next_matches_button = false;
	if ($rows > 200)
	{
		$show_next_messages_button = true;
	}
	if ($rows === (int) 0)
	{
		echo '<p>No matches have been played yet.</p>' . "\n";
		setTableUnchanged($site, $connection);
		$site->dieAndEndPageNoBox();
	}
	unset($rows);
	
	echo '<table id="table_matches_played" class="big">' . "\n";
	echo '<caption>Matches played</caption>' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Time</th>' . "\n";
	echo '	<th>Teams</th>' . "\n";
	echo '	<th>Result</th>' . "\n";
	// show edit/delete links in a new table column if user has permission to use these
	// adding matches is not done from within the table
	if ($allow_edit_match || $allow_delete_match)
	{
		echo '	<th>Allowed actions</th>' . "\n";
	}
	echo '</tr>' . "\n\n";
	// display message overview
	$matchid_list = Array (Array ());
	// read each entry, row by row
	$current_match_row = 0;
	while($row = mysql_fetch_array($result))
	{
		$matchid_list[$current_match_row]['timestamp'] = $row['timestamp'];
		$matchid_list[$current_match_row]['team1_name'] = $row['team1_name'];
		$matchid_list[$current_match_row]['team2_name'] = $row['team2_name'];
		$matchid_list[$current_match_row]['team1_teamid'] = $row['team1_teamid'];
		$matchid_list[$current_match_row]['team2_teamid'] = $row['team2_teamid'];
		$matchid_list[$current_match_row]['team1_points'] = $row['team1_points'];
		$matchid_list[$current_match_row]['team2_points'] = $row['team2_points'];
		$matchid_list[$current_match_row]['player_name'] = $row['player_name'];
		$matchid_list[$current_match_row]['id'] = $row['id'];

		$current_match_row++;
	}
	unset($current_match_row);
	// query result no longer needed
	mysql_free_result($result);
	
	// are more than 200 rows in the result?
	if ($show_next_matches_button)
	{
		// only show 200 messages, not 201
		// NOTE: array_pop would not work on a resource (e.g. $result)
		array_pop($matchid_list);
	}
	
	// walk through the array values
	foreach($matchid_list as $match_entry)
	{
		echo '<tr class="matches_overview">' . "\n";
		echo '<td>';
		echo $match_entry['timestamp'];
		echo '</td>' . "\n" . '<td>';
		
		// get name of first team
		team_name_from_id($match_entry['team1_teamid'], $match_entry['team1_name']);
		
		// seperator showing that opponent team will be named soon
		echo ' - ';
		
		// get name of second team
		team_name_from_id($match_entry['team2_teamid'], $match_entry['team2_name']);
		
		// done with the table field, go to next field
		echo '</td>' . "\n" . '<td>';
		
		echo htmlentities($match_entry['team1_points']);
		echo ' - ';
		echo htmlentities($match_entry['team2_points']);
		echo '</td>' . "\n";
		
		// show allowed actions based on permissions
		if ($allow_edit_match || $allow_delete_match)
		{
			echo '<td>';
			if ($allow_edit_match)
			{
				echo '<a class="button" href="./?edit=' . htmlspecialchars($match_entry['id']) . '">Edit match result</a>';
			}
			if ($allow_edit_match && $allow_delete_match)
			{
				echo ' ';
			}
			if ($allow_edit_match)
			{
				echo '<a class="button" href="./?delete=' . htmlspecialchars(urlencode($match_entry['id'])) . '">Delete match</a>';
			}
			echo '</td>' . "\n";
		}
		
		
		echo '</tr>' . "\n\n";
	}
	unset($matchid_list);
	unset($match_entry);
	
	// no more matches to display
	echo '</table>' . "\n";
	
	// look up if next and previous buttons are needed to look at all messages in overview
	if ($show_next_matches_button || ($view_range !== (int) 0))
	{
		// browse previous and next entries, if possible
		echo "\n" . '<p>'  . "\n";
		
		if ($view_range !== (int) 0)
		{
			echo '	<a href="./?i=';
			echo ((int) $view_range)-200;
			echo '">Previous matches</a>' . "\n";
		}
		if ($show_next_matches_button)
		{
			
			echo '	<a href="./?i=';
			echo ((int) $view_range)+200;
			echo '">Next matches</a>' . "\n";
		}
		echo '</p>' . "\n";
	}	
	
	if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
	{
		setTableUnchanged($site, $connection);
	}
?>
</div>
</body>
</html>