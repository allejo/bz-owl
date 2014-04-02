<?php
	// handle invitations of users
	class invitation
	{
		private $expiration;
		private $invitationid;
		private $userids = array();
		
		public function __construct($id = 0)
		{
			$this->expiration = strtotime('+7 days');
			$this->invitationid = $id;
		}
		
		// deletes invitation from database
		// returns true on success, false otherwise (boolean)
		public function delete()
		{
			global $db;
			
			
			$query = $db->prepare('DELETE FROM `invitations` WHERE `id`=:invitationid');
			if ($db->execute($query, array(':invitationid' => array((int) $this->invitationid, PDO::PARAM_INT))))
			{
				return true;
			}
			
			return false;
		}
		
		public static function deleteOldInvitations()
		{
			global $db;
			
			
			// delete expired invitations
			$query = $db->prepare('DELETE LOW_PRIORITY FROM `invitations` WHERE `expiration`<=?');
			if (!$db->execute($query, date('Y-m-d H:i:s')))
			{
				$db->logError('Could not delete expired invitations.');
			}
		}
		
		// set for who the invitation is valid
		// input: id of a user (integer)
		public function forUserId($userid)
		{
			$this->userids[] = (int) $userid;
		}
		
		// find out when the invitation expires
		// return: Expiration time (String)
		public function getExpiration()
		{
			global $db;
			
			$query = $db->prepare('SELECT `expiration` FROM `invitations` WHERE `id`=:invitationid');
			if ($db->execute($query, array(':invitationid' => array((int) $this->invitationid, PDO::PARAM_INT))))
			{
				while ($row = $db->fetchRow($query))
				{
					// build invitation instance based on id stored in db
					$this->expiration = $row['expiration'];
				}
				$db->free($query);
				
				return $this->expiration;
			}
			
			return false;
		}
		
		// find out if user has invitations to a team
		// input: $teamid, the id of team (integer); $userid, the id of user (integer), use false (boolean) for all users
		// output: array of invitation instances
		public static function getInvitationsForTeam($teamid, $userid = false)
		{
			global $db;
			
			
			if ($userid === false)
			{
				$query = $db->prepare('SELECT `id` FROM `invitations` WHERE `teamid`=:teamid');
				$args = array(':teamid' => array((int) $teamid, PDO::PARAM_INT));
			} else
			{
				$query = $db->prepare('SELECT `id` FROM `invitations` WHERE `userid`=:userid AND `teamid`=:teamid');
				$args = array(':teamid' => array((int) $teamid, PDO::PARAM_INT),
						      ':userid' => array((int) $userid, PDO::PARAM_INT));
			}
			if ($db->execute($query, $args))
			{
				$ids = array();
				while ($row = $db->fetchRow($query))
				{
					// build invitation instance based on id stored in db
					$ids[] = new invitation((int) $row['id']);
				}
				$db->free($query);
				
				return $ids;
			}
			return array();
		}
		
		// find out if invitation expiration date is in the past
		// output: true if invitation is outdated, false otherwise (bool)
		public function getIsExpired()
		{
			// any valid invitation must have an expiration date
			if (!isset($this->expiration))
			{
				return true;
			}
			
			// compare two dates using > operator in combination with DateTime class
			// values inside database are stored as UTC
			// date format: YYYY-MM-DD HH:MM:SS
			$now = new DateTime('Y-m-d H:i:s', DateTimeZone::UTC);
			if (new DateTime($this->expiration, DateTimeZone::UTC) > $now)
			{
				return false;
			}
			
			return true;
		}
		
		// find out when the invitation expires
		// return: Expiration time (String)
		public function getUsers()
		{
			global $db;
			
			$users = array();
			$query = $db->prepare('SELECT `userid` FROM `invitations` WHERE `id`=:invitationid');
			if ($db->execute($query, array(':invitationid' => array((int) $this->invitationid, PDO::PARAM_INT))))
			{
				while ($row = $db->fetchRow($query))
				{
					$users[] = new user($row['userid']);
				}
				$db->free($query);
				
				return $users;
			}
			
			return false;
		}
		
		// enter the invitation into database
		// input: Should a private message be issued to target audience (boolean)
		// return: true on success, false on error (boolean)
		public function insert($sendPM = true)
		{
			global $db;
			
			
			if (count($this->teamids) > 0)
			{
				foreach($this->teamids AS $teamid)
				{
					if (count($this->userids) > 0)
					{
						$query = $db->prepare('INSERT INTO `invitations` (`userid`, `teamid`, `expiration`) VALUES (:userid, :teamid, :expiration)');
						foreach($this->userids AS $userid)
						{
							if (!$db->execute($query, array(':userid' => array((int) $userid, PDO::PARAM_INT),
														   ':teamid' => array((int) $teamid, PDO::PARAM_INT),
														   ':expiration' => array(strftime('%Y-%m-%d %H:%M:%S', $this->expiration), PDO::PARAM_STR))))
							{
								return false;
							}
							if ($sendPM)
							{
								$pm = new pm();
								$pm->setSubject(\user::getCurrentUser()->getName() . ' invited you to ' . (new team($teamid))->getName());
								$pm->setContent('Congratulations: ' . \user::getCurrentUser()->getName() . ' invited you to ' . (new team($teamid))->getName()
												. '. The invitation is valid until ' . strftime('%Y-%m-%d %H:%M:%S', $this->expiration) . '.');
								
								$pm->setTimestamp(date('Y-m-d H:i:s'));
								$pm->addUserID($userid);
								
								// send it
								$pm->send();
							}
						}
					}
				}
			}
			
			return true;
		}
		
		// where should the target audience of the invite be able to join?
		// input: id of team to be able to join (integer)
		public function toTeam($teamid)
		{
			$this->teamids[] = (int) $teamid;
		}
	}
?>
