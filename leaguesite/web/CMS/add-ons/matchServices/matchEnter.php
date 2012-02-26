<?php
	class matchEnter
	{
		function __construct($title)
		{
			global $user;
			global $tmpl;
			
			
			if (!$user->getPermission($entry_add_permission))
			{
				// no permissions to enter a new match
				$tmpl->display('NoPerm');
				die();
			}
			
			
			include(dirname(__FILE__) . '/classes/matchLogic.php');
			
			
			// get user confirmation status
			$confirmed = isset($_GET['confirmed']) ? $_GET['confirmed'] : 'no';
			
			if ($data = sanityCheck($confirmed))
			{
				switch ($confirmed)
				{
					case 'no': $this->showEnterPreview(); break();
					case 'action': $this->matchEnter($data); break();
					default: break();
				}
			}
			
			// done
		}
		
		private function sanityCheck(&$confirmed)
		{
		
		}
		
		private function showEnterPreview()
		{
		
		}
		
		private function matchEnter(&$data)
		{
		
		}
		
		function displayResult()
		{
			require_once dirname(__FILE__) . '/matchShow.php';
			
			// self is matchServices that displays matches by default
			$this->matchClass->displayMatches();
		}
	}
?>
