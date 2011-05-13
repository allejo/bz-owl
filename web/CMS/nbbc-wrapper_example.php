<?php
	class wrapper
	{
		function Parse($string)
		{
			global $config;
			
			
			require_once((dirname(__FILE__)) . '/nbbc/nbbc.php');
			$setup = new BBCode;
			
			if (!isset($config))
			{
				// old compatibility mode
				$setup->SetSmileyURL(baseaddress() . 'smileys');
			} else
			{
				$setup->SetSmileyURL($config->value('baseaddress') . 'smileys');
			}
			// $setup->SetEnableSmileys(false);
			$setup->SetAllowAmpersand(true);
			
			// escape (x)html entities
			return $setup->Parse(htmlent($string));
		}
    }
?>
