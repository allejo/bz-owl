<?php
	// handle team related data
	
	// not loaded by default!
	class team
	{
		private $getNameQuery;
		private $teamid;
		private $name = false;
		private $score = false;
		
		public function __construct($teamid = 0)
		{
			$this->teamid = $teamid;
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
		
		public function getName()
		{
			global $config;
			global $db;
			
			// returns current user if no userid specified, otherwise name of user of supplied userid			
			
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
		
		public function getScore()
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `score` FROM `teams_overview` WHERE `id`=:teamid LIMIT 1');
			if ($db->execute($query, array(':teamid' => array($this->teamid, PDO::PARAM_INT))))
			{
				$row = $db->fetchRow($this->getNameQuery);
				$db->free($this->getNameQuery);
				
				$this->score = $row['score'];
				
				return $this->score;
			}
		}
		
		
		// save changes
		// returns true if save is successful
		public function save()
		{
			global $db;
			
			
			$query = $db->prepare('UPDATE `teams` SET name=:name');
			if ($db->execute($query, array(':name' => array($name, PDO::PARAM_STR))))
			{
				return true;
			}
			
			return false;
		}
		
		public function setName($name)
		{
			$this->name = $name;
		}
	}
?>
