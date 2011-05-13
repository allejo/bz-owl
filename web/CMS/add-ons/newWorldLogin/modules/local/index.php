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
			global $config;
			
			
			// show login text only if configured to do so
			if ($config->value('login.local.showLoginText'))
			{
				return ('local login form text');
			}
		}
		
		
		function showForm()
		{
			return 'bgoojikfodl';
		}
		
		
		function validateLogin(&$output)
		{
			
		}
	}
?>
