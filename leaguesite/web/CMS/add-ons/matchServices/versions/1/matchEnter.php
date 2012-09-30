<?php
	namespace matchServices;
	
	class matchDataSubmitted
	{
		const	ok = 1,
				checkTeam1 = 2;
				
	}
	
	class matchEnter
	{
		private $teamList = array();
		
		function __construct()
		{
			global $user;
			global $tmpl;
			global $db;
			
//			$tmpl->setTemplate('MatchServicesMatchEnter');
			
			if (!$user->getPermission('allow_add_match'))
			{
				// no permissions to enter a new match
//				$tmpl->display('NoPerm');
//				die();
			}
			
			// get list of teams eligible to match
			$query = $db->SQL('SELECT `teams`.`id`,`teams`.`name` FROM `teams`,`teams_overview` '
							. 'WHERE (`teams_overview`.`deleted`<>2) '
							. 'AND `teams`.`id`=`teams_overview`.`teamid` ORDER BY `teams`.`name`');
			if (!$query)
			{
				die('The world has come to an end.');
			}
			
			// fill internal variable with result for later re-use
			while($row = $db->fetchRow($query))
			{
				$this->teamList[$row['id']]['id'] = $row['id'];
				$this->teamList[$row['id']]['name'] = $row['name'];
			}
			
			//pass teamList to template logic
			$tmpl->assign('teamList',$this->teamList);
			
			// get user confirmation status
			// NOTE: enum would be really cool
			$confirmed = isset($_GET['confirmed']) ? $_GET['confirmed'] : 'no';
			
			$this->sanityCheck($confirmed);
			switch ($confirmed)
			{
				// show preview
				case 'no': $this->showEnterPreview(); break;
				// user has confirmed preview, enter the match now
				case 'action': $this->matchEnter($data); break;
				default: break;
			}
			
			echo $confirmed;
			$this->defaultForm();
			
			// done
		}
		
		private function defaultForm()
		{
			global $tmpl;
			
			// pass default values for new match to template
			$tmpl->assign('team1CurPoints',  0);
			$tmpl->assign('team2CurPoints',  0);
			
			$tmpl->assign('curDay',  date('Y-m-d'));
			$tmpl->assign('curTime',  date('H:i:s'));
			
			$tmpl->assign('matchDay',  date('Y-m-d'));
			$tmpl->assign('matchTime',  date('H:i:s'));
			
			$tmpl->setTemplate('MatchServicesMatchEnter');
		}
		
		private function sanityCheck(&$confirmed)
		{
			global $randomkey_name;
			global $team_id1;
			global $team_id2;
			global $team1_caps;
			global $team2_caps;
			global $timestamp;
			global $match_id;
			global $similarMatchFound;
			global $db;
			
			// sanitise match id
			if (isset($_GET['edit']))
			{
				$match_id = intval($_GET['edit']);
			}
			if (isset($_GET['delete']))
			{
				$match_id = intval($_GET['delete']);
			}
			
			// sanitise team variables
			
			if (isset($_POST['match_team_id1']))
			{
				$team_id1 = intval($_POST['match_team_id1']);
			} elseif (isset($_POST['team_id1']))
			{
				$team_id1 = intval($_POST['team_id1']);
			} else
			{
				$team_id1 = 0;
			}
			if ($team_id1 < 1)
			{
				$team_id1 = 0;
			}
			
			if (isset($_POST['match_team_id2']))
			{
				$team_id2 = intval($_POST['match_team_id2']);
			} elseif (isset($_POST['team_id2']))
			{
				$team_id2 = intval($_POST['team_id2']);
			} else
			{
				$team_id2 = 0;
			}
			if ($team_id2 < 1)
			{
				$team_id2 = 0;
			}
			
			
			// do the teams exist?
			
			// teams specified?
			if (!isset($_GET['delete']) && ($team_id1 > 0 && $team_id2 > 0))
			{
				$team_exists = 0;
				$query = $db->prepare('SELECT COUNT(`id`) as `team_exists` FROM `teams` WHERE `id`=? LIMIT 1');
				if (!($result = $db->execute($query, $team_id1)))
				{
					$db->logError('Could not find out name of team #' . ($team_id1) . '.');
				}
				while ($row = $db->fetchRow($query))
				{
					$team_exits = intval($row['team_exists']);
				}
				$db->free($query);
				if ($team_exits === 0)
				{
					echo '<p>Error: The specified team #1 does not exist</p>';
					$confirmed = 'checkTeam1';
					return;
				}
				
				// reset variable for team 2
				$team_exits = 0;
				$query = $db->prepare('SELECT COUNT(`id`) as `team_exists` FROM `teams` WHERE `id`=? LIMIT 1');
				if (!($result = $db->execute($query, $team_id2)))
				{
					$db->logError('Could not find out name of team #' . sqlSafeString($team_id2) . '.');
				}
				while ($row = $db->fetchRow($query))
				{
					$team_exits = intval($row['team_exists']);
				}
				$db->free($query);
				if ($team_exits === 0)
				{
					echo '<p>Error: The specified team #2 does not exist</p>';
					$confirmed = 'checkTeam2';
					return;
				}
				
				// teams are the same (and chosen by user)
				if ((($team_id1 > 0) && ($team_id2 > 0)) && ($team_id1 === $team_id2))
				{
					echo '<p>In order to be an official match, teams would have to be different!</p>';
					$confirmed = 'checkDifferentTeams';
					return;
				}
			}
			
			// sanitise score variables
			
			if (isset($_POST['team1_points']))
			{
				$team1_caps = intval($_POST['team1_points']);
			} else
			{
				$team1_caps = 0;
			}
			
			if (isset($_POST['team2_points']))
			{
				$team2_caps = intval($_POST['team2_points']);
			} else
			{
				$team2_caps = 0;
			}
			
			// sanitise day and time variables
			
			if (isset($_POST['match_day']))
			{
				$match_day = $_POST['match_day'];
			} else
			{
				$match_day = date('Y-m-d');
			}
			
			if (isset($_POST['match_time']))
			{
				$match_time = $_POST['match_time'];
			} else
			{
				$match_time = date('H:i:s');
			}
			
			if (isset($_POST['match_day']) && isset($_POST['match_time']))
			{
				$timestamp = ($_POST['match_day']) . ' ' . ($_POST['match_time']);
			}
			
			// user wants to edit match data again
			if (isset($_POST['match_cancel']))
			{
				$confirmed = 'edit';
				return;
			}
			
			if (isset($_POST['$match_id']))
			{
				$match_id = intval($_POST['$match_id']);
			}
			
			// does the match exist?
			if (isset($match_id))
			{
				$query = $db->prepare('SELECT `id` FROM `matches` WHERE `id`=?');
				if (!($result = $db->execute($query, $match_id)))
				{
					$db->logError('Could not find out id for team 1 given match id '
								  . ($match_id) . ' due to a sql problem!');
				}
				if ((intval($db->rowCount($query)) < 1))
				{
					// match did not exist!
					$confirmed = 'checkMatch';
				}
			}
			
			
			// sanitise date and time specified
			
			// sanity checks regarding day format
			// sample day: 2009-12-15
			if (!(preg_match('/(2)(0|1|2|3|4|5|6|7|8|9){3,}-(0|1)(0|1|2|3|4|5|6|7|8|9)-(0|1|2|3)(0|1|2|3|4|5|6|7|8|9)/', $match_day)))
			{
				echo '<p>Please make sure your specified date is in correct format. Do not forget leading zeros.</p>' . "\n";
				$confirmed = 'no';
				return;
			}
			
			// sanity checks regarding time format
			// sample time: 15:21:35
			if (!(preg_match('/(0|1|2)([0-9]):([0-5])([0-9]):([0-5])([0-9])/', $match_time)))
			{
				echo '<p>Please make sure your specified time is in correct format. Do not forget leading zeros.</p>' . "\n";
				$confirmed = 'no';
				return;
			}
			
			// get the unix timestamp from the date and time
			if (!($specifiedTime = strtotime($match_day . ' ' . $match_time)))
			{
				echo '<p>Please make sure your specified date and time is valid!</p>' . "\n";
				$confirmed = 'no';
				return;
			}
			
			// look up if the day does exist in Gregorian calendar
			// checkdate expects order to be month, day, year
			if (!(checkdate(date('m', $specifiedTime), date('d', $specifiedTime), date('Y', $specifiedTime))))
			{
				echo '<p>Please make sure your specified date and time is a valid Gregorian date.</p>' . "\n";
				$confirmed = 'no';
				return;
			}
			
			// is match in the future?
			if (isset($timestamp))
			{
				$curTime = (int) strtotime('now');
				if ((((int) $specifiedTime) - $curTime) >= 0)
				{
					echo '<p>You tried to enter, edit or delete a match that would have been played in the future.';
					echo ' Only matches in the past can be entered, edited or deleted.</p>' . "\n";
					$confirmed = 'no';
					return;
				}
			}
			
			// is match older than 2 months?
			$eightWeeksAgo = (int) strtotime('now -8 weeks');
			if (((int) $specifiedTime) <= $eightWeeksAgo)
			{
				echo ('<p>You tried to enter, edit or delete a match that is older than 8 weeks.'
					  . 'Only matches played in the last 8 weeks can be entered, edited or deleted.</p>' . "\n");
				$confirmed = 'no';
				return;
			}
			
			// check if there is already a match entered at that time
			// scores depend on the order, two matches done at the same time lead to undefined behaviour
			$query = $db->prepare('SELECT `timestamp` FROM `matches` WHERE `timestamp`=?');
			if (!($result = $db->execute($query, $timestamp)))
			{
				unlock_tables();
				$db->logError('Unfortunately there seems to be a database problem'
							  . ' and thus comparing timestamps (using equal operator) of matches failed.');
			}
			$rows = (int) $db->rowCount($query);
			$db->free($query);
			
			if ($rows > 0 && !isset($_GET['edit']) && !isset($_GET['delete']))
			{
				// go back to the first step of entering a match
				echo '<p>There is already a match entered at that exact time.';
				echo ' There can be only one finished at the same time because the scores depend on the order of the played matches.</p>' . "\n";
				// just warn them and let them enter it all again by hand
				echo 'Please enter the match with a different time.</p>' . "\n";
				echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?enter">' . "\n";
				echo '<div>';
				$site->write_self_closing_tag('input type="hidden" name="confirmed" value="0"');
				echo '</div>' . "\n";
				
				
				// pass the match values to the next page so the previously entered data can be set default for the new form
				show_form($team_id1, $team_id2, $team1_caps, $team2_caps, $readonly=false);
				
				echo '<div>';
				$site->write_self_closing_tag('input type="submit" name="match_cancel" value="Cancel and change match data" id="send"');
				echo '</div>' . "\n";
				echo '</form>' . "\n";
				$site->dieAndEndPage();
			}
			
			
			// random key validity check
			if ($confirmed==='action')
			{
				$new_randomkey_name = '';
				if (isset($_POST['key_name']))
				{
					$new_randomkey_name = html_entity_decode($_POST['key_name']);
				}
				$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
				
				if (!($randomkeysmatch))
				{
					echo '<p>The magic key did not match. It looks like you came from somewhere else. Going back to compositing mode.</p>';
					// reset the confirmed value
					$confirmed = 'no';
				}
			}
			
			// check for similar match in database and warn user if at least one was found
			// skip warning if already warned (no infinite warning loop)
			if ($confirmed ==='action' && !isset($_POST['similar_match']))
			{
				// find out if there are similar matches
				$similarMatchFound = false;
				$similarMatchFound = similarMatchEntered(true);
				if (!$similarMatchFound)
				{
					// look for a possible last show stopper
					$similarMatchFound = similarMatchEntered(false);
				} else
				{
					// add space between last similar match and the one probably following
					$site->write_self_closing_tag('br');
					
					// only call the function for user information, ignore result
					similarMatchEntered(false);
				}
				
				if ($similarMatchFound)
				{
					// ask for confirmation again and do not go ahead automatically
					$confirmed = 'no';
				}
			}
			
			
			// no double confirmation about deletion - user saw confirmation step with $confirmed = 0 already
			if ($confirmed === 'action' && isset($_GET['delete']))
			{
				$confirmed = 'action';
			}
		}
		
		private function showEnterPreview()
		{
			global $tmpl;
			
			
//			echo 'showEnterPreview called';
			$tmpl->setTemplate('MatchServicesMatchPreview');
		}
		
		private function matchEnter(&$data)
		{
			echo 'matchEnter called';
		}
		
		function displayResult()
		{
			require_once dirname(__FILE__) . '/matchShow.php';
			
			// self is matchServices that displays matches by default
			$this->matchClass->displayMatches();
		}
	}
?>
