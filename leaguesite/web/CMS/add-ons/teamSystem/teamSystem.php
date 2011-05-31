<?php
	class teamSystem
	{
		function __construct()
		{
			global $tmpl;
			
			
			if (isset($_GET['add']))
			{
				require_once dirname(__FILE__) . '/teamAdd.php';
				new pmSystemAddPM();
				die();
			} elseif (isset($_GET['edit']))
			{
				require_once dirname(__FILE__) . '/teamEdit.php';
			} elseif (isset($_GET['delete']))
			{
				require_once dirname(__FILE__) . '/teamDelete.php';
				new pmDelete($folder, intval($_GET['delete']));
			} else
			{
				require_once dirname(__FILE__) . '/teamList.php';
				$display = new teamList();
				
				switch(isset($_GET['profile']))
				{
					case true: $display->showTeam(intval($_GET['profile'])); break;
					default: $display->showTeams(); break;
				}
			}
			
			$tmpl->display();
		}
	}
?>
