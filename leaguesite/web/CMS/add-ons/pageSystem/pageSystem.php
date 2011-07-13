<?php
	class pageSystem
	{
		public function __construct($title)
		{
			global $config;
			global $tmpl;
			global $db;
			
			
			$tmpl->assign('title', $title);
			
			// FIXME: check for permission!!
			if (!$config->getValue('debugSQL'))
			{
				$tmpl->display('NoPerm');
				die();
			}
			
			
			// setup support class
			require(dirname(__FILE__) . '/classes/pageOperations.php');
			$pageOperations = new pageOperations();
			
			
			if (!isset($_GET['action']))
			{
				$pages = $pageOperations->getPageList();
			}
			$tmpl->assign('pages', $pages);
			
			$tmpl->display('pageSystem');
		}
	}
?>