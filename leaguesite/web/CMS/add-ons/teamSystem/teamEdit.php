<?php
	class teamEdit
	{
		public function __construct()
		{
			global $tmpl;
			echo "huhu";
			
			if (!$tmpl->setTemplate('teamSystemEdit'))
			{
				$tmpl->noTemplateFound();
				die();
			}
		}
	}
?>
