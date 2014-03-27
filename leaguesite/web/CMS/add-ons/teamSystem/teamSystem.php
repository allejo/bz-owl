<?php
	class teamSystem
	{
		function __construct()
		{
			global $tmpl;
			global $site;
			
			// load team class source code
			require_once($site->installationPath() . '/CMS/classes/team.php');
			
			if (isset($_GET['add']))
			{
				require_once(dirname(__FILE__) . '/teamAdd.php');
				new teamAdd();
			} elseif (isset($_GET['edit']))
			{
				require_once(dirname(__FILE__) . '/teamEdit.php');
				new teamEdit((int) $_GET['edit']);
			} elseif (isset($_GET['delete']))
			{
				require_once(dirname(__FILE__) . '/teamDelete.php');
				new teamDelete((int) $_GET['delete']);
			} elseif (isset($_GET['join']))
			{
				require_once(dirname(__FILE__) . '/teamJoin.php');
				new teamJoin((int) $_GET['join']);
			} elseif (isset($_GET['opponent_stats']))
			{
				require_once(dirname(__FILE__) . '/teamOpponents.php');
				$teamOpponents = new teamOpponents();
				$teamOpponents->showOpponentStats(intval($_GET['opponent_stats']));
			} elseif (isset($_GET['reactivate']))
			{
				require_once(dirname(__FILE__) . '/teamReactivate.php');
				new teamReactivate();
			} elseif (isset($_GET['remove']) && isset($_GET['team']))
			{
				require_once(dirname(__FILE__) . '/teamLeave.php');
				new teamLeave((int) $_GET['remove'], (int) $_GET['team']);
			} else
			{
				require_once(dirname(__FILE__) . '/teamList.php');
				
				switch(isset($_GET['profile']))
				{
					case true: $display = new teamList((int) $_GET['profile']); break;
					default: $display = new teamList(); break;
				}
			}
			
			$tmpl->display();
		}
	}
?>
