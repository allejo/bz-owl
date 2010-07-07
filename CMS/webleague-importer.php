<?php
	set_time_limit(0);
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	
	$display_page_title = 'Webleague DB importer';
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
//	require realpath('../CMS/navi.inc');
	
	if (!isset($site))
	{
		$site = new siteinfo();
	}
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_user';
	$viewerid = (int) getUserID();
	
	$db_to_be_imported = 'bzleague_guleague';
	
//	$query = 'ALTER TABLE `messages_storage` CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci';
//	@$site->execute_query($site->db_used_name(), 'all', $query, $connection);
//	
//	$file = dirname(__FILE__) . '/ts-CMS_structure.sql';
//	if (file_exists($file) && is_readable($file))
//	{
//		$output_buffer = '';
//		ob_start();
//		
//		readfile($file);
//		$output_buffer .= ob_get_contents();
//		ob_end_clean();
//	} else
//	{
//		echo '<p>The file at ' . htmlent($file) . ' does not exist or is not readable</p>' . "\n";
//		return;
//	}
//	
//	$output_buffer = explode("\n", $output_buffer);
//	$db_structure_calls = '';
//	foreach($output_buffer as $one_line)
//	{
//		if (!(strcmp(substr($one_line,0,1), '#') === 0))
//		{
//				$db_structure_calls .= $one_line;
//		}
//	}
//	
//	$output_buffer = explode(';', $db_structure_calls);
//	$db_structure_calls = array();
//	foreach($output_buffer as $one_line)
//	{
//		if (!(strcmp(substr($one_line,0,2), '/*') === 0))
//		{
//			$db_structure_calls[] = $one_line;
//		}
//	}
//	
//	echo '<pre>';
//	print_r($db_structure_calls);
//	echo '</pre>';
//	foreach($db_structure_calls as $one_call)
//	{
//		@$site->execute_query($site->db_used_name(), 'all!', $one_call, $connection);
//	}
	
	// players
	$query = 'SELECT * FROM `l_player` ORDER BY `id`';
	if (!($result = @$site->execute_query($db_to_be_imported, 'l_player', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	// 0 means active player
	$suspended_status = 0;
	$deleted_players = array(array());
	
	// DUMMY player
	$deleted_players['0']['callsign'] = 'CTF League System';
	$deleted_players['0']['dummy'] = true;
	
	$index_num = 1;
	$players = array();
	while ($row = mysql_fetch_array($result))
	{
		$current_name = '(no name)';
		// skip deleted users as they can be several times in the db
		// player got deleted, keep track of him
		if (!(strcmp(substr($row['callsign'],-10), ' (DELETED)') === 0))
		{
			$current_name = htmlent($row['callsign']);
		} else
		{
			$current_name = htmlent(substr($row['callsign'],0,-10));
		}
		
		// is user already added to db?
		// callsigns are case treated insensitive
		if (!isset($players[strtolower($current_name)]))
		{
			$query = ('INSERT INTO `players` (`id`,`teamid`,`name`,`suspended`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($index_num) . ',' . sqlSafeStringQuotes($row['team'])
					  . ',' . sqlSafeStringQuotes($current_name) . ',' . sqlSafeStringQuotes($suspended_status)
					  . ')');
			// execute query, ignore result
			$site->execute_query($site->db_used_name(), 'players', $query, $connection);
			
			$query = ('INSERT INTO `players_profile` (`playerid`,`user_comment`,`raw_user_comment`,`joined`,`last_login`,`logo_url`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($index_num) . ',' . sqlSafeStringQuotes(utf8_encode($row['comment']))
					  . ',' . sqlSafeStringQuotes(utf8_encode($row['comment'])) . ',' . sqlSafeStringQuotes($row['created'])
					  . ',' . sqlSafeStringQuotes($row['last_login']) . ',' . sqlSafeStringQuotes($row['logo'])
					  . ')');
			// execute query, ignore result
			@$site->execute_query($site->db_used_name(), 'players_profile', $query, $connection);
		}
		$deleted_players[$row['id']]['callsign'] = $current_name;
		
		// mark the user has been added to db
		// callsigns are case treated insensitive
		$players[strtolower($current_name)] = true;
		
		$index_num++;
	}
	mysql_free_result($result);
	
	$query = 'SELECT * FROM `l_team` ORDER BY `created`';
	if (!($result = @$site->execute_query($db_to_be_imported, 'l_team', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	while ($row = mysql_fetch_array($result))
	{
		$query = ('INSERT INTO `teams` (`id`,`name`,`leader_playerid`)'
				  . ' VALUES '
				  . '(' . sqlSafeStringQuotes($row['id']) . ',' . sqlSafeStringQuotes(htmlent($row['name'])));
				  if (isset($deleted_players[$row['leader']]) && (isset($deleted_players[$row['leader']]['callsign'])) && (!isset($deleted_players[$row['leader']]['dummy'])))
				  {
					  $query .= ',(SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($deleted_players[($row['leader'])]['callsign']) . ')';
				  } else
				  {
					  $query .= ',' . sqlSafeStringQuotes('0');
				  }
				  $query .= ')';
		// execute query, ignore result
		@$site->execute_query($site->db_used_name(), 'teams', $query, $connection);
		
		$activity_status = 1;
		if (strcmp($row['status'], 'deleted') === 0)
		{
			$activity_status = 2;
		}
		$query = ('INSERT INTO `teams_overview` (`teamid`,`score`,`member_count`,`any_teamless_player_can_join`,`deleted`)'
				  . ' VALUES '
				  . '(' . sqlSafeStringQuotes($row['id']) . ',' . sqlSafeStringQuotes($row['score'])
				  . ',' . '(SELECT COUNT(*) FROM `players` WHERE `players`.`teamid`=' . sqlSafeStringQuotes($row['id']) . ') '
				  . ',' . sqlSafeStringQuotes($row['adminclosed']) . ',' .sqlSafeStringQuotes($activity_status)
				  . ')');
		// execute query, ignore result
		@$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection);
		
		$activity_status = 1;
		if (!(strcmp($row['active'], 'yes') === 0))
		{
			$activity_status = 0;
		}
		$query = ('INSERT INTO `teams_profile` (`teamid`,`num_matches_played`,`num_matches_won`,`num_matches_draw`,`num_matches_lost`'
				  . ',`description`,`raw_description`, `logo_url`,`created`)'
				  . ' VALUES '
				  . '(' . sqlSafeStringQuotes($row['id']));
		
		// total matches
		$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
				  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
				  . ')');
		if (!($tmp_result = @$site->execute_query($db_to_be_imported, 'bzl_match', $tmp_query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		while ($tmp_row = mysql_fetch_array($tmp_result))
		{
			$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
		}
		mysql_free_result($tmp_result);
		
		// matches won
		$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
					  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
					  . ') AND ((`team1`=' . sqlSafeStringQuotes($row['id'])
					  . ' AND `score1`>`score2`) OR (`team2`=' . sqlSafeStringQuotes($row['id'])
					  . ' AND `score2`>`score1`)'
					  . ')');
		if (!($tmp_result = @$site->execute_query($db_to_be_imported, 'bzl_match', $tmp_query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		while ($tmp_row = mysql_fetch_array($tmp_result))
		{
			$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
		}
		mysql_free_result($tmp_result);
		
		// matches draw
		$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
					  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
					  . ') AND (`score1`=`score2`'
					  . ')');
		if (!($tmp_result = @$site->execute_query($db_to_be_imported, 'bzl_match', $tmp_query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		while ($tmp_row = mysql_fetch_array($tmp_result))
		{
			$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
		}
		mysql_free_result($tmp_result);
		
		// matches won
		$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
					  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
					  . ') AND ((`team1`=' . sqlSafeStringQuotes($row['id'])
					  . ' AND `score1`<`score2`) OR (`team2`=' . sqlSafeStringQuotes($row['id'])
					  . ' AND `score2`<`score1`)'
					  . ')');
		if (!($tmp_result = @$site->execute_query($db_to_be_imported, 'bzl_match', $tmp_query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		while ($tmp_row = mysql_fetch_array($tmp_result))
		{
			$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
		}
		mysql_free_result($tmp_result);	
		
		$query .= (',' . sqlSafeStringQuotes(utf8_encode($row['comment'])) . ',' . sqlSafeStringQuotes(utf8_encode($row['comment']))
				   . ',' . sqlSafeStringQuotes($row['logo']) . ',' . sqlSafeStringQuotes($row['created'])
				   . ')');
		// execute query, ignore result
		@$site->execute_query($site->db_used_name(), 'teams_overview', $query, $connection);
	}
	mysql_free_result($result);
	
	echo '<pre>';
	print_r($deleted_players);
	echo '</pre>';
	
	// matches
	$query = 'SELECT * FROM `bzl_match` ORDER BY `id`';
	if (!($result = @$site->execute_query($db_to_be_imported, 'players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	// 0 means active player
	$suspended_status = 0;
	while ($row = mysql_fetch_array($result))
	{
		$query = ('INSERT INTO `matches` (`id`,`playerid`,`timestamp`,`team1_teamid`,`team2_teamid`,`team1_points`,`team2_points`,`team1_new_score`,`team2_new_score`)'
				  . ' VALUES '
				  . '(' . sqlSafeStringQuotes($row['id']));
		if (strcmp($row['idedit'],'') === 0)
		{
			// was player deleted?
			if (isset($deleted_players[$row['identer']]))
			{
				// grab id from database to ensure no foreign key constraint gets violated
				$query .= ',(SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($deleted_players[$row['identer']]['callsign']) . ')';
			} else
			{
				$query .= ',' . sqlSafeStringQuotes($row['identer']);
			}
		} else
		{
			// was player deleted?
			if (isset($deleted_players[$row['idedit']]))
			{
				// grab id from database to ensure no foreign key constraint gets violated
				$query .= ',(SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($deleted_players[$row['idedit']]['callsign']) . ')';
			} else
			{
				$query .= ',' . sqlSafeStringQuotes($row['idedit']);
			}
		}
		if (strcmp($row['tsedit'],'') === 0)
		{
			$query .= ',' . sqlSafeStringQuotes($row['tsenter']);
		} else
		{
			$query .= ',' . sqlSafeStringQuotes($row['tsedit']);
		}
		$query .= (',' . sqlSafeStringQuotes($row['team1']) . ',' . sqlSafeStringQuotes($row['team2'])
				   . ',' . sqlSafeStringQuotes($row['score1']) . ',' . sqlSafeStringQuotes($row['score2'])
				   . ',' . sqlSafeStringQuotes($row['newrankt1']) . ',' . sqlSafeStringQuotes($row['newrankt2'])
				   . ')');
		// execute query, ignore result
		@$site->execute_query($site->db_used_name(), 'matches', $query, $connection);
	}
	mysql_free_result($result);
	
	$query = 'SELECT * FROM `l_message` ORDER BY `datesent`';
	if (!($result = @$site->execute_query($db_to_be_imported, 'l_team', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	while ($row = mysql_fetch_array($result))
	{
		$query = ('INSERT INTO `messages_storage` (`id`,`author`,`author_id`,`subject`,`timestamp`,`message`,`from_team`,`recipients`)'
				  . ' VALUES '
				  . '(' . sqlSafeStringQuotes($row['msgid'])
				  . ',' . sqlSafeStringQuotes($deleted_players[$row['fromid']]['callsign']));
		if ((int) $row['fromid'] > 0)
		{
			$query .=  ',(SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($deleted_players[$row['fromid']]['callsign']) . ')';
		} else
		{
			$query .= ',0';
		}
		$query .= (',' . sqlSafeStringQuotes(utf8_encode(htmlentities($row['subject'], ENT_QUOTES, 'ISO-8859-1')))
				  . ',' . sqlSafeStringQuotes($row['datesent'])
				  . ',' . sqlSafeStringQuotes(utf8_encode($row['msg'])));
//		if (strcmp($row['team'], 'no') === 0)
//		{
//			$query .= ',' . sqlSafeStringQuotes('0');
//		} else
//		{
//			$query .= ',' . sqlSafeStringQuotes('1');
//		}
		// the messages are sent multiple times in practice
		$query .= ',' . sqlSafeStringQuotes('0');
		$query .= ',(SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($deleted_players[$row['toid']]['callsign']) . '))';
		// execute query, ignore result
		@$site->execute_query($site->db_used_name(), 'messages_storage', $query, $connection);
		
		$query = ('INSERT INTO `messages_users_connection` (`msgid`,`playerid`,`in_inbox`,`in_outbox`,`msg_unread`)'
				  . ' VALUES '
				  . '(' . sqlSafeStringQuotes($row['msgid'])
				  . ',(SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($deleted_players[$row['toid']]['callsign']) . ')'
				  // all messages only in inbox
				  . ',' . sqlSafeStringQuotes('1')
				  . ',' . sqlSafeStringQuotes('0')
				  // mark all messages as already read
				  . ',' . sqlSafeStringQuotes('0')
				  . ')');
		
		// execute query, ignore result
		@$site->execute_query($site->db_used_name(), 'messages_users_connection', $query, $connection);
	}
	
//	// convert messages to UTF-8
//	$query = 'ALTER TABLE `messages_storage` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci';
//	@$site->execute_query($site->db_used_name(), 'all', $query, $connection);
	
	
	
	
	// do maintenance after importing the database to clean it
	// a check inside the maintenance logic will make sure it will be only performed one time per day at max
	require_once('../CMS/maintenance/index.php');
	
	// done
	// (should take about 30 minutes to import the data)
?>