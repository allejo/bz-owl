<?php
	class passThroughSystem
	{		
		public function __construct($title, $path)
		{
			global $config;
			global $tmpl;
			
			// not much to do here
			// the sole purpose of add-on is to pass through the template without any changes

			if (substr($path, -1 ) == '/')
			{
				if (substr($path, 0, -1 ) == false)
				{
					$path = 'index';
				} else
				{
					$path = substr($path, 0, -1) . 'index';
				}
			}
			
			if (!$tmpl->setTemplate($path))
			{
				$tmpl->noTemplateFound();
				die();
			}
			
			$tmpl->display();
		}
	}
?>
