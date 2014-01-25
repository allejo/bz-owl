<?php
	class teamEdit
	{
		public function __construct($teamid)
		{
			global $tmpl;
			global $user;
			
			if (!$tmpl->setTemplate('teamSystemEditTeam'))
			{
				$tmpl->noTemplateFound();
				die();
			}
			$tmpl->assign('title', 'Edit team');
			
			$team = new team($teamid);
			
			
			$tmpl->assign('teamid', $teamid);
			$tmpl->assign('teamName', $team->getName());
			
			$editPermission = $user->getPermission('allow_edit_any_team_profile') || 
							  $team->getPermission('edit', user::getCurrentUserId());
			
			$tmpl->assign('canEditTeam', $editPermission);
			
			$tmpl->assign('leaderId', $team->getLeaderId());
			
			$userids = $team->getUserIds();
			$members = array();
			foreach($userids AS $userid)
			{
				$members[] = array('id' => $userid,
								   'name' => (new user($userid))->getName());
			}
			
			$tmpl->assign('members', $members);
		}
	}
?>
