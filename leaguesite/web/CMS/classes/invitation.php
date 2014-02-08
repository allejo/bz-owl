<?php
	// handle invitations of users
	class invitation
	{
		private $invitationid;
		
		public function __construct($id = 0)
		{
			$this->invitationid = $id;
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
		
		// find out if user has invitations to a team
		// input: $userid, the id of user (integer); $teamid, the id of team (integer)
		// output: array of invitation instances
		public static function getInvitationsForTeam($userid, $teamid)
		{
			global $db;
			
			
			$query = $db->prepare('SELECT `id` FROM `invitations` WHERE `userid`=:userid AND `teamid`=:teamid');
			if ($db->execute($query, array(':userid' => array((int) $userid, PDO::PARAM_INT),
										   ':teamid' => array((int) $teamid, PDO::PARAM_INT))))
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
		
		// deletes invitation from database
		// returns true on success, false otherwise (boolean)
		public function delete()
		{
			$query = $db->prepare('DELETE FROM `invitations` WHERE `id`=:invitationid');
			if ($db->execute($query, array(':invitationid' => array((int) $this->invitationid, PDO::PARAM_INT))))
			{
				return true;
			}
			
			return false;
		}
	}
?>
