<?php
	class pageSystem
	{
		private $hasPermission = false;
		private $pageOperations;
		
		
		public function __construct($title)
		{
			global $config;
			global $user;
			global $tmpl;
			global $db;
			
			
			// setup support class
			require(dirname(__FILE__) . '/classes/pageOperations.php');
			$this->pageOperations = new pageOperations();
			
			
			// FIXME: check for permission only in final version!!
			$this->hasPermission = $this->pageOperations->hasPermission();
			if (!$this->hasPermission)
			{
				$tmpl->display('NoPerm');
				die();
			}
			
			// set title
			$tmpl->assign('title', $title);
			
			
			
			if (!isset($_GET['action']) || count($_POST) < 1)
			{
				$pages = $this->pageOperations->getPageList();
				$tmpl->assign('pageList', $pages);
				die();
			}
			
			switch($_GET['action'])
			{
				case 'change':
					$this->handleChangePageRequest();
					break;
				
				// do nothing by default
				default:
					die();
			}
			
		}
		
		private function handleChangePageRequest()
		{
			global $tmpl;
			
			
			// tell template that we're trying to edit page assignment data
			$tmpl->assign('pageChange', true);
			
			// grab the first element of POST array by using each
			$operation = each($_POST);
			// reset array pointer that was changed to next element by using each
			reset($_POST);
			
			if (count(strval($operation[0])) > 1000000)
			{
				echo('either you use way too much pages for this CMS or you tried to break the pageSystem');
				die();
			}
			
			// see if first part of request is equal change (removing the number)
			if (substr_compare(strval($operation[0]), 'change', 0, 6))
			{
				echo('error: request illegal');
				die();
			}
			
			
			// get id in question from request string
			$id = intval(substr(strval($operation[0]), 6));
			
			// TODO: eek, count is slow! Ask db about size instead.
			$pages = $this->pageOperations->getPageList();
			if (count($pages) < $id)
			{
				echo('error: request was to change higher entry than existing');
				die();
			}
			
			$pageData = $this->pageOperations->getPageData($id);
			$tmpl->assign('pageData', $pageData);
		}
		
		public function __destruct()
		{
			global $tmpl;
			
			if (isset($this->hasPermission))
			{
				$tmpl->display('pageSystem');
			}
		}
	}
?>
