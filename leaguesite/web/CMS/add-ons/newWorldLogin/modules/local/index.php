<?php
	class local
	{
		private $xhtml = true;
		
		function __construct()
		{
			global $config;
			
			
			$this->xhtml = $config->value('useXhtml');
		}
		
		function showLoginText()
		{
			return ('local login');
		}
		
		function showForm()
		{
			return 'bgoojikfodl';
		}
	}
?>
