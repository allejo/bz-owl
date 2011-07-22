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
			
			
			// check for permission and abort if user has none
			$this->hasPermission = $this->pageOperations->hasPermission();
			if (!$this->hasPermission)
			{
				$tmpl->display('NoPerm');
				die();
			}
			
			// set title
			$tmpl->assign('title', $title);
			
			
			// show overview by default if no action specified, post parameters missing or cancel requested
			if (!isset($_POST['action']) || count($_POST) < 1 || isset($_POST['cancel']))
			{
				$this->showOverview();
				die();
			}
			
			// call the event handlers to process the requested task
			switch($_POST['action'])
			{
				case 'change':
					$this->handleChangePageRequest();
					break;
				
				// do nothing by default
				default:
					die();
			}
			
		}
		
		
		private function showOverview()
		{
			global $tmpl;
			
			
			$pages = $this->pageOperations->getPageList();
			$tmpl->assign('pageList', $pages);
		}
		
		
		private function handleChangePageRequest()
		{
			global $tmpl;
			
			
			// grab the first element of POST array by using each
			$operation = each($_POST);
			// reset array pointer that was changed to next element by using each
			reset($_POST);
			
			if (!isset($_POST['key_name']) && count(strval($operation[0])) > 1000000)
			{
				echo('either you use way too much pages for this CMS or you tried to break the pageSystem');
				die();
			}
			
			// check validity of data if write change requested
			if (isset($_POST['change'])
			&& isset($_POST['requestPath'])
			&& isset($_POST['title'])
			&& isset($_POST['addon']))
			{
				// initialise guards
				$keyName = '';
				$keyValue = '';
				
				if (isset($_POST['key_name']))
				{
					$keyName = $_POST['key_name'];
				}
				
				if (isset($_POST[$keyName]))
				{
					$keyValue = $_POST[$keyName];
				}
				
				if (strlen($keyName) > 0
					&& strlen($keyValue) > 0
					&& $this->validateKey($keyName, $keyValue))
				{
					// pass this to the pageOperations class which will do db sanity checks and apply changes
					// it needs id, request path, title and add-on to be used
					$changeOutcome = ($this->pageOperations->changeRequested($_POST['change'],
																			 $_POST['requestPath'],
																			 $_POST['title'],
																			 $_POST['addon']));
					if ($changeOutcome !== true && $changeOutcome !== false && strlen($changeOutcome) > 0)
					{
						$tmpl->assign('operationMessage', $changeOutcome);
					}
				} else
				{
					echo('error: request illegal: Could not confirm key data');
				}
				$this->showOverview();
				die();
				
			}
			
			// write request logic finished
			// now back to form request logic
			
			// see if first part of request is equal change (removing the number) if form requested
			if (substr_compare(strval($operation[0]), 'change', 0, 6))
			{
				echo('error: request illegal: ' . strval($operation[0]));
				die();
			}
			
			
			// get id in question from request string
			$id = intval(substr(strval($operation[0]), 6));
			
			// find out if id is valid
			$curAddon = $this->pageOperations->getAddonUsed($id);
			if ($curAddon === false)
			{
				echo('error: request was to change higher entry than existing');
				die();
			}
			
			// give template information about page assignement in question
			$pageData = $this->pageOperations->getPageData($id);
			$tmpl->assign('pageData', $pageData);
			
			// tell template that we're trying to edit page assignment data
			$tmpl->assign('pageChange', true);
			$tmpl->assign('addonDropDownChoices', $this->pageOperations->getAddonList($curAddon));
			$tmpl->assign('curAddon', $curAddon);
			
			// generate random keys to avoid changing data through accidental clicking on 3rd party forms
			$this->generateKey();
		}
		
		private function generateKey()
		{
			global $site;
			global $tmpl;
			
			$randomKeyName = 'addon.pageSystem.' . microtime();
			// convert some special chars to underscores
			$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
			$randomkeyValue = $site->setKey($randomKeyName);
			$tmpl->assign('keyName', $randomKeyName);
			$tmpl->assign('keyValue', htmlent($randomkeyValue));
		}
		
		private function validateKey($key, $value)
		{
			global $site;
			
			return $site->validateKey($key, $value);
		}
		
		public function __destruct()
		{
			global $tmpl;
			
			if ($this->hasPermission)
			{
				$tmpl->display('pageSystem');
			}
		}
	}
?>
