<?php
	// handle team related data
	
	// not loaded by default!
	class team
	{
		private $activityNew;
		private $activityOld;
		private $avatarURI;
		private $created;
		private $description;
		private $rawDescription;
		private $leaderid;
		private $getNameQuery;
		private $teamid;
		private $origTeamId;
		private $open;
		private $matchCount = array();
		private $memberCount;
		private $name;
		private $score;
		private $status;
		
		public function __construct($teamid = 0)
		{
			$this->teamid = (int) $teamid;
			$this->origTeamId = (int) $teamid;
		}
		
		// sanitises team name
		// return: true if name is ok
		public function cleanTeamName($name)
		{
			// check if every character is printable
			if (ctype_print($name))
			{
				// strip whitespace or other characters from beginning and end of the name
				$cleaned_name = trim($name);
				// "(teamless)" is a reserved name
				if (strcasecmp($_POST['edit_team_name'], '(teamless)') === 0)
				{
					$cleaned_name = '';
				}
				
				// check if cleaned team name is different than user entered team name
				if (strcmp($name, $cleaned_name) === 0)
				{
					return true;
				}
			}
			return false;
		}
		
		// create team in database
		// return: true on success, false otherwise
		public function create()
		{
			global $db;
			
			try
			{
				// create team
				$db->beginTransaction();
				$query = $db->prepare('INSERT INTO `teams` (name) VALUES(:name)');
				if ($db->execute($query, array(':name' => array($this->name, PDO::PARAM_STR))))
				{
					// fetch id
					$this->teamid = (int) $db->lastInsertId();
					$this->origTeamId = $this->teamid;
					
					$db->commit();
					
					// fill database with empty rows for team
					$db->beginTransaction();
					$query = $db->prepare('INSERT INTO `teams_overview` (teamid) VALUES(:teamid)');
					if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						$query = $db->prepare('INSERT INTO `teams_permissions` (teamid) VALUES(:teamid)');
						if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
						{
							$query = $db->prepare('INSERT INTO `teams_profile` (teamid) VALUES(:teamid)');
							if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
							{
								// use update function to set the values of team
								if ($this->update())
								{
									// everything good, write changes
									$db->commit();
									
									return true;
								}
							}
						}
					}
					
					// something went wrong, undo last changes
					$db->rollback();
					
					// delete traces of team
					$this->delete();
				}
			} catch(PDOExecption $e)
			{
				$db->rollback();
				$db->logError('Error while creating new team. The supplied database message was ' . $e->getMessage());
			}
			return false;
		}
		
		// permanently delete team from database
		// return: true on success, false otherwise
		public function delete()
		{
			global $db;
			
			$query = $db->prepare('DELETE FROM `teams` WHERE `id`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$query = $db->prepare('DELETE FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
				if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
				{
					$query = $db->prepare('DELETE FROM `teams_profile` WHERE `teamid`=:teamid LIMIT 1');
					if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						$query = $db->prepare('DELETE FROM `teams_permissions` WHERE `teamid`=:teamid LIMIT 1');
						if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
						{
							return true;
						}
					}
				}
			}
			return false;
		}
		
		// does team in database exist
		// return: true or false (boolean)
		public function exists()
		{
			global $db;
			
			$query = $db->prepare('SELECT `id` FROM `teams` WHERE `id`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				return $row === false ? false : true;
			}
			return false;
		}
		
		// returns number of matches in last 45 days
		public function getActivityNew()
		{
			global $db;
			
			$query = $db->prepare('SELECT `activityNew` FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->activityNew = $row['activityNew'];
				
				return $this->activityNew;
			}
			return false;
		}
		
		// returns number of matches in last 90 days
		public function getActivityOld()
		{
			global $db;
			
			$query = $db->prepare('SELECT `activityOld` FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->activityOld = $row['activityOld'];
				
				return $this->activityOld;
			}
			return false;
		}
		
		// returns URI pointing to team avatar image
		public function getAvatarURI()
		{
			global $db;
			
			$query = $db->prepare('SELECT `logo_url` FROM `teams_profile` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->avatarURI = $row['logo_url'];
				
				return $this->avatarURI;
			}
			return false;
		}
		
		// returns array of teamids (integer) of active teams
		public static function getActiveTeamIds()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `teamid` FROM `teams_overview` WHERE `deleted`=:status');
			if ($db->execute($query, array(':status' => array('1', PDO::PARAM_INT))))
			{
				$ids = array();
				while ($row = $db->fetchRow($query))
				{
					$ids[] = $row['teamid'];
				}
				$db->free($query);
				
				return $ids;
			}
			return array();
		}
		
		// returns creation date of team as string
		public function getCreationTimestampStr()
		{
			global $db;
			
			if (isset($this->created))
			{
				return $this->created;
			}
			
			$query = $db->prepare('SELECT `created` FROM `teams_profile` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->origTeamId, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$this->created = $row['created'];
				$db->free($query);
				
				return $this->created;
			}
			return array();
		}
		
		// returns team description (string)
		public function getDescription()
		{
			global $db;
			
			
			if (isset($this->description))
			{
				return $this->description;
			}
			
			$query = $db->prepare('SELECT `description` FROM `teams_profile` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->description = $row['description'];
				
				return $this->description;
			}
			return false;
		}
		
		// find out what teams have status deleted
		// return: an array containing team ids (array of integers), empty if no team has status deleted
		public static function getDeletedTeamIds()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `teamid` FROM `teams_overview` WHERE `deleted`=:status');
			if ($db->execute($query, array(':status' => array('2', PDO::PARAM_INT))))
			{
				$ids = array();
				while ($row = $db->fetchRow($query))
				{
					$ids[] = $row['teamid'];
				}
				$db->free($query);
				
				return $ids;
			}
			return array();
		}
		
		public function getID()
		{
			return $this->teamid;
		}
		
		public static function getInactiveTeamIds()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `teamid` FROM `teams_overview` WHERE `deleted`=:status');
			if ($db->execute($query, array(':status' => array('4', PDO::PARAM_INT))))
			{
				$ids = array();
				while ($row = $db->fetchRow($query))
				{
					$ids[] = $row['teamid'];
				}
				$db->free($query);
				
				return $ids;
			}
			return array();
		}
		
		// is team open for any user or not
		// return values: true on open team, false otherwise
		public function getOpen()
		{
			global $db;
			
			
			if (isset($this->open))
			{
				return $this->open;
			}
			
			$query = $db->prepare('SELECT `open` FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				// convert db entry to boolean
				$this->open = $row['open'] === '1' ? true : false;
				
				return $this->open;
			}
			return false;
		}
		
		// gets description before bbcode is processed
		public function getRawDescription()
		{
			global $db;
			
			
			if (isset($this->rawDescription))
			{
				return $this->rawDescription;
			}
			
			$query = $db->prepare('SELECT `raw_description` FROM `teams_profile` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->rawDescription = $row['raw_description'];
				
				return $this->rawDescription;
			}
			return false;
		}
		
		// get the userid of team leader
		// return: id of team leader (integer), false otherwise (boolean)
		public function getLeaderId()
		{
			global $db;
			
			if (isset($this->leaderid))
			{
				return $this->leaderid;
			}
			
			$query = $db->prepare('SELECT `leader_userid` FROM `teams` WHERE `id`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->origTeamId, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->leaderid = (int) $row['leader_userid'];
				
				return $this->leaderid;
			}
			return false;
		}
		
		// how many members belong to team?
		// use this function for fast, cached results
		// if you want to verify this using uncached results there are two SLOW solutions
		// count(user::getMemberIdsOfTeam($teamid))
		// \user::getNumberOfTeamMembers($teamid)
		// return: cached number of team members (integer), false on error (boolean)
		public function getMemberCount()
		{
			global $db;
			
			if (isset($this->memberCount))
			{
				return $this->memberCount;
			}
			
			$query = $db->prepare('SELECT `member_count` FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->origTeamId, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->memberCount = (int) $row['member_count'];
				return $this->memberCount;
			}
			
			return false;
		}
		
		// find out max number of members
		// return: config value for team.default.maxTeamSize or default of 20 (integer)
		public function getMaxMemberCount()
		{
			global $config;
			
			if (($maxMembers = $config->getValue('team.default.maxMemberCount')) !== false)
			{
				return $maxMembers;
			}
			
			return 20;
		}
		
		// how many matches of a certain type have been played?
		// use this function for fast, cached results
		// if you want to verify this using uncached results there is SLOW
		// match::getMatchCountForTeamId($teamid, $type)
		// input: type (string): 'all' | 'won' | 'draw' | 'lost'
		// return: cached number of matches this team played
		public function getMatchCount($type = 'all')
		{
			global $db;
			
			if (isset($this->matchCount[$type]))
			{
				return $this->matchCount[$type];
			}
			
			$column = 'num_matches_';
			switch($type)
			{
				case 'won':
					$column .= 'won';
					break;
				case 'draw':
					$column .= 'draw';
					break;
				case 'lost':
					$column .= 'lost';
					break;
				case 'all':
					$column .= 'total';
					break;
				default:
					return false;
			}
			
			$query = $db->prepare('SELECT `'. $column .'` FROM `teams_profile` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->origTeamId, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->matchCount[$type] = (int) $row[$column];
				return $this->matchCount[$type];
			}
			return false;
		}
		
		// returns team name belonging to teamid of current class instance
		public function getName()
		{
			global $config;
			global $db;
			
			
			if ($this->teamid === 0)
			{
				return '$team->getName(0): reserved teamid';
			}
			
			if (isset($this->name))
			{
				return $this->name;
			}
			
			// collect name from database
			$this->getNameQuery = $db->prepare('SELECT `name` FROM `teams` WHERE `id`=:teamid LIMIT 1');
			if ($db->execute($this->getNameQuery, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$teamName = $db->fetchRow($this->getNameQuery);
				$db->free($this->getNameQuery);
				
				return $teamName['name'];
			}
			
			// error handling: log error and show it in end user visible result
			$db->logError((__FILE__) . ': getName(' . strval($this->teamid) . ') failed.');
			return '$team->getName(' . strval($this->teamid) . ') failed.';
		}
		
		// returns array of ids (integer) of new teams
		public static function getNewTeamIds()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `teamid` FROM `teams_overview` WHERE `deleted`=:status');
			if ($db->execute($query, array(':status' => array('0', PDO::PARAM_INT))))
			{
				$ids = array();
				while ($row = $db->fetchRow($query))
				{
					$ids[] = $row['teamid'];
				}
				$db->free($query);
				
				return $ids;
			}
			return array();
		}
		
		// FIXME: Probably better to call a match class and pass teamid as parameter
		public function getNewestMatchTimestamp()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `timestamp` FROM `matches` WHERE `team1_id`=:teamid OR `team2_id`=:teamid ORDER BY `timestamp` DESC LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				return $row['timestamp'];
			}
			return false;
		}
		
		public function getPermission($permission, $userid)
		{
			// userid 0 is reserved and never has any permission
			// any user not identified or logged in has id 0
			if ((int) $userid === 0)
			{
				return false;
			}
			
			switch ($permission)
			{
				case 'edit': return $this->getLeaderId() === (int) $userid;
				default: return false;
			}
		}
		
		public static function getReactivatedTeamIDs()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `teamid` FROM `teams_overview` WHERE `deleted`=:status');
			if ($db->execute($query, array(':status' => array('3', PDO::PARAM_INT))))
			{
				$ids = array();
				while ($row = $db->fetchRow($query))
				{
					$ids[] = $row['teamid'];
				}
				$db->free($query);
				
				return $ids;
			}
			return array();
		}
		
		// obtain status of team
		// valid status values: 'new', 'active', 'deleted', 'reactivated'
		// returns false on error
		public function getStatus()
		{
			global $db;
			
			
			if ($this->teamid === 0)
			{
				return false;
			}
			
			if (isset($this->status))
			{
				return $this->status;
			}
			
			// collect name from database
			$query = $db->prepare('SELECT `deleted` FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->origTeamId, PDO::PARAM_INT))))
			{
				$teamStatus = $db->fetchRow($query);
				$db->free($query);
				
				switch((int) $teamStatus['deleted'])
				{
					case 0:
						$this->status = 'new';
						break;
					case 1:
						$this->status = 'active';
						break;
					case 2:
						$this->status = 'deleted';
						break;
					case 3:
						$this->status = 'reactivated';
						break;
					case 4:
						$this->status = 'inactive';
						break;
				}
				
				return $this->status;
			}
			
			return false;
		}
		
		public function getScore()
		{
			global $db;
			
			if (isset($this->score))
			{
				return $this->score;
			}
			
			$query = $db->prepare('SELECT `score` FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->origTeamId, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->score = $row['score'];
				
				return $this->score;
			}
			return false;
		}
		
		// function to obtain team instances from array of teamids
		// input values: teamids in array
		// return values: array of team instances
		public static function getTeamsFromIds(array $teams)
		{
			$result = array();
			foreach($teams AS &$team)
			{
				$result[] = new team($team);
				$team = new team($team);
			}
			
			return $teams;
		}
		
		// get ids of users belong to the team
		// return: array with user ids (array containing integers)
		public function getUserIds()
		{
			global $db;
			
			
			$ids = array();
			
			$query = $db->prepare('SELECT `id` FROM `users` WHERE `teamid`=:teamid');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				while ($row = $db->fetchRow($query))
				{
					$ids[] = (int) $row['id'];
				}
				$db->free($query);
			}
			return $ids;
		}
		
		// get instances of users belonging to the team
		// similar to the getUserIds function
		// return: array with user class instances (array containing users)
		public function getUsers()
		{
			$userids = $this->getUserIds();
			$members = array();
			foreach($userids AS $userid)
			{
				$members[] = new \user($userid);
			}
			
			return $members;
		}
		
		// sets the team avatar uri
		// the avatar is an image that serves as team logo
		public function setAvatarURI($uri)
		{
				$this->avatarURI = trim($uri);
		}
		
		// sets team description, encodes bbcode if possible
		// input values: $description (string)
		public function setDescription($description)
		{
			global $tmpl;
			
			$this->description = (string) $tmpl->encodeBBCode((string) $description);
		}
		
		// sets team leader to any member of the team
		// input: id of user (integer)
		// return: true on success, false on error (boolean)
		public function setLeaderId($userid)
		{
			$user = new user($userid);
			if ($user->getMemberOfTeam($this->origTeamId))
			{
				$this->leaderid = $userid;
				return true;
			}
			
			return false;
		}
		
		// sets team status to be either open or not
		// input values: $open (boolean)
		public function setOpen($open)
		{
			$this->open = (bool) $open;
		}
		
		// sets raw team description, does not change input
		// input values: $description (string)
		public function setRawDescription($description)
		{
			$this->rawDescription = (string) $description;
		}
		
		// set cached member count value
		// input: new member count (integer)
		public function setMemberCount($numMembers)
		{
			$this->memberCount = (int) $numMembers;
		}
	
		public function setName($name)
		{
			$this->name = (string) $name;
		}
		
		// set team score
		// input: score (integer)
		public function setScore($score)
		{
			$this->score = (int) $score;
		}
		
		// assign status to team
		// valid status values: new, active, deleted, reactivated
		// return: true on success, false on error (boolean)
		public function setStatus($status)
		{
			switch ($status)
			{
				case 'new':
					$this->status = 'new';
					return true;
				case 'active':
					$this->status = 'active';
					return true;
				case 'deleted':
					$this->status = 'deleted';
					return true;
				case 'reactivated':
					$this->status = 'reactivated';
					return true;
				case 'inactive':
					$this->status = 'inactive';
					return true;
			}
			return false;
		}
		
		public function setTeamid($teamid)
		{
			$this->teamid = (int) $teamid;
		}
		
		// update team in db
		// return: true if update is successful, false otherwise
		public function update()
		{
			global $db;
			
			
			// teamid 0 is reserved, not to be used
			// update not possible on new entry
			if ($this->teamid === 0 || $this->origTeamId === 0)
			{
				return false;
			}
			
			if (!isset($this->name))
			{
				$this->name = $this->getName();
			}
			
			$query = $db->prepare('UPDATE `teams` SET id=:id, name=:name WHERE id=:origid');
			if ($db->execute($query, array(':id' => array($this->teamid, PDO::PARAM_INT),
										   ':name' => array($this->name, PDO::PARAM_STR),
										   ':origid' => array($this->origTeamId, PDO::PARAM_INT))))
			{
				$this->origTeamId = $this->teamid;
				
				// update avatar uri
				if ($this->avatarURI !== false)
				{
					$query = $db->prepare('UPDATE `teams_profile` SET logo_url=:avataruri WHERE `teamid`=:teamid LIMIT 1');
					if (!$db->execute($query, array(':avataruri' => array($this->avatarURI, PDO::PARAM_STR),
													':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						return false;
					}
				}
				
				$status = false;
				switch ($this->status)
				{
					case 'new':
						$status = 0;
						break;
					case 'active':
						$status = 1;
						break;
					case 'deleted':
						$status = 2;
						break;
					case 'reactivated':
						$status = 3;
						break;
					case 'inactive':
						$status = 4;
						break;
				}
				if ($status !== false)
				{
					$query = $db->prepare('UPDATE `teams_overview` SET deleted=:status WHERE teamid=:teamid');
					if (!$db->execute($query, array(':status' => array($status, PDO::PARAM_INT),
													':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						return false;
					}
				}
				
				if (isset($this->open))
				{
					$query = $db->prepare('UPDATE `teams_overview` SET open=:open WHERE teamid=:teamid');
					if (!$db->execute($query, array(':open' => array($this->open ? 1 : 0, PDO::PARAM_INT),
													':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						return false;
					}
				}
				
				if (isset($this->description))
				{
					$query = $db->prepare('UPDATE `teams_profile` SET description=:description WHERE teamid=:teamid');
					if (!$db->execute($query, array(':description' => array($this->description, PDO::PARAM_STR),
													':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						return false;
					}
				}
				
				if (isset($this->leaderid))
				{
					$query = $db->prepare('UPDATE `teams` SET leader_userid=:leaderid WHERE id=:teamid');
					if (!$db->execute($query, array(':leaderid' => array($this->leaderid, PDO::PARAM_INT),
													':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						return false;
					}
				}
				
				if (isset($this->memberCount))
				{
					$query = $db->prepare('UPDATE `teams_overview` SET member_count=:memberCount WHERE teamid=:teamid');
					if (!$db->execute($query, array(':memberCount' => array($this->memberCount, PDO::PARAM_STR),
													':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						return false;
					}
				}
				
				if (isset($this->rawDescription))
				{
					$query = $db->prepare('UPDATE `teams_profile` SET raw_description=:rawDescription WHERE teamid=:teamid');
					if (!$db->execute($query, array(':rawDescription' => array($this->rawDescription, PDO::PARAM_STR),
													':teamid' => array($this->teamid, PDO::PARAM_INT))))
					{
						return false;
					}
				}
				
				if (isset($this->score))
				{
					$query = $db->prepare('UPDATE `teams_overview` SET `score`=:score WHERE `teamid`=:teamid LIMIT 1');
					if (!$db->execute($query, array(':teamid' => array($this->origTeamId, PDO::PARAM_INT),
													':score' => array($this->score, PDO::PARAM_INT))))
				   {
					   return false;
				   }

				}
				
				return true;
			}
			
			
			return false;
		}
	}
?>
