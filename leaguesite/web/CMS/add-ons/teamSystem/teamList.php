<?php
	class teamList
	{
		function __construct()
		{
		
		}
		
		function showTeams()
		{
			global $tmpl;
			global $db;
			
			
			if (!$tmpl->setTemplate('teamSystem'))
			{
				$tmpl->noTemplateFound();
				die();
			}
			$tmpl->assign('title', 'Team overview');
			
			// get list of active, inactive and new teams (no deleted teams)
			// TODO: move creation date in db from teams_profile to teams_overview
			$query = $db->prepare('SELECT * FROM `teams`, `teams_overview`, `teams_profile`'
								  . ' WHERE `teams`.`id`=`teams_overview`.`teamid`=`teams_profile`.`teamid`'
								  . ' AND `teams_overview`.`deleted`<>?'
								  . ' ORDER BY `teams_overview`.`score` DESC');
			// value 2 in deleted column means team has been deleted
			$db->execute($query, '2');
			while ($row = $db->fetchRow($query))
			{
				print_r($row);
			}
		}
	}
?>
