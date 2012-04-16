<?php
	class matchServices
	{
		private $matchClass;
		
		function __construct($title)
		{
			global $site;
			global $config;
			global $user;
			global $tmpl;
			
			
			if (!isset($site))
			{
				require_once(dirname(dirname(dirname(__FILE__))) . '/site.php');
				$site = new site();
			}
			
			include(dirname(__FILE__) . '/classes/match.php');
			$matchClass = new match();
			
			// accessible by public (show..)
			// find out which template should be used
			if ($user->getID() < 0)
			{
				$matchClass->displayMatches();
 				$tmpl->display();
				return;
			}
			
			
			// setup permissions for templates
			$tmpl->assign('canEnterMatch', $user->getPermission('allow_add_match'));
			$tmpl->assign('canEditMatch', $user->getPermission('allow_edit_match'));
			$tmpl->assign('canDeleteMatch', $user->getPermission('allow_delete_match'));
			
			
			if (isset($_GET['enter']))
			{
				require_once dirname(__FILE__) . '/matchEnter.php';
				new matchEnter();
			} elseif (isset($_GET['edit']))
			{
				require_once dirname(__FILE__) . '/matchEdit.php';
				new matchEdit();
			} elseif (isset($_GET['delete']))
			{
				require_once dirname(__FILE__) . '/matchDelete.php';
				new matchDelete();
			} else
			{
				$matchClass->displayMatches();
			}
			
			$tmpl->display();
		}
		
/*
		function displayMatches()
		{
			require_once dirname(__FILE__) . '/matchShow.php';
			$display = new pmDisplay();
			
			switch(isset($_GET['view']))
			{
				case true: $display->showMail($folder, intval($_GET['view'])); break;
				default: $tmpl->assign('title', $title); $display->showMails($folder); break;
			}
		}
*/
	}
?>
