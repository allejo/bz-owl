<?php
	// handle invitations of users
	class invitation
	{
		public function __construct()
		{
			
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
	}
?>
