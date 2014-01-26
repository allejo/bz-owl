<?php
	// handle team related data
	
	// not loaded by default!
	class team
	{
		private $avatarURI = false;
		private $description = false;
		private $rawDescription = false;
		private $leaderid = false;
		private $getNameQuery;
		private $teamid;
		private $origTeamid;
		private $name = false;
		private $score = false;
		
		public function __construct($teamid = 0)
		{
			$this->teamid = $teamid;
			$this->origTeamid = $teamid;
		}
		
		// sanitises team name
		// returns true if name is ok
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
		
		public function getDescription()
		{
			global $db;
			
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
		
		// gets description before bbcode is processed
		public function getRawDescription()
		{
			global $db;
			
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
		
		public function getLeaderId()
		{
			global $db;
			
			$query = $db->prepare('SELECT `leader_userid` FROM `teams` WHERE `id`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->leaderid = $row['leader_userid'];
				
				return $this->leaderid;
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
			
			if ($this->name !== false)
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
		
		public function getPermission($permission, $userid)
		{
			// userid 0 is reserved and never has any permission
			// any user not identified or logged in has id 0
			if ($userid === 0)
			{
				return false;
			}
			
			switch ($permission)
			{
				case 'edit':
					return $this->getLeaderId() === $userid;
				default: return false;
			}
		}
		
		public function getScore()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `score` FROM `teams_overview` WHERE `teamid`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($query);
				$db->free($query);
				
				$this->score = $row['score'];
				
				return $this->score;
			}
			return false;
		}
		
		public function getUserIds()
		{
			global $db;
			
			
			$ids = array();
			
			$query = $db->prepare('SELECT `id` FROM `users` WHERE `teamid`=:teamid');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				while ($row = $db->fetchRow($query))
				{
					$ids[] = $row['id'];
				}
				$db->free($query);
			}
			return $ids;
		}
		
		
		public function setName($name)
		{
			$this->name = $name;
		}
		
		public function setTeamid($teamid)
		{
			$this->teamid = $teamid;
		}
		
		// update team in db
		// returns true if update is successful
		public function update()
		{
			global $db;
			
			
			// teamid 0 is reserved, not to be used
			// update not possible on new entry
			if ($this->teamid === 0 || $this->origTeamid === 0)
			{
				return false;
			}
			
			if ($this->name === false)
			{
				$this->name = $this->getName();
			}
			
			$query = $db->prepare('UPDATE `teams` SET id=:id, name=:name WHERE id=:origid');
			if ($db->execute($query, array(':name' => array($this->name, PDO::PARAM_STR),
										   ':id' => array($this->teamid),
										   ':origid' => array($this->origTeamid))))
			{
				$this->origTeamid = $this->teamid;
				return true;
			}
			
			return false;
		}
	}
?>
