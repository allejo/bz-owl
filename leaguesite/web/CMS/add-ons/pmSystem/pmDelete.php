<?php
	require_once dirname(__FILE__) . '/List.php';
	class pmDelete extends pmDisplay
	{
		function __construct($folder, $id)
		{
			// get confirmation step info
			$confirmed = isset($_POST['confirmed']) ? $_POST['confirmed'] : 0;
			// run sanity checks
			$this->sanityCheck($confirmed);
			
			switch ($confirmed)
			{
				case 1:
					// delete message
					$this->delete($folder, $id);
					break;
				
				default:
					// display preview
					$this->preview($folder, $id);
					break;
			}
		}
		
		function preview($folder, $id)
		{
			global $tmpl;
			
			parent::showMail($folder, $id);
			
			$tmpl->setTemplate('PMDelete');
			
			$tmpl->assign('title', 'Delete ' . $tmpl->getTemplateVars('title'));
		}
		
		function sanityCheck(&$confirmed)
		{
		
		}
	}
?>
