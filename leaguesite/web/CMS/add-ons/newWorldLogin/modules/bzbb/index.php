<?php
	class bzbb
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
			
			
			$text = ('<p class="first_p">Please log in using your account at <a href='
					 . '"http://my.bzflag.org/weblogin.php?action=weblogin&amp;url='
					 . urlencode($config->value('baseaddress') . 'Login2/' . '?bzbb=%TOKEN%,%USERNAME%')
					 . '">my.bzflag.org (BZBB)</a>.</p>' . "\n");
			
			return ($text);
		}
		
		function showForm()
		{
			// remote bzflag weblogin shows the form
			// nothing to see here, move along
			return '';
		}
	}
?>
