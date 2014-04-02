<?php
	// handle user related data
	class config
	{
		private $options = array();
		
		function __construct()
		{
			include dirname(__FILE__) . '/settings_path.php';
			$settings = new settings();
			
			// load the settings
			$this->options = $settings->settings();
		}
		
		// returns value of specified setting from config
		// NOTE: NULL can be returned at any time: prefer isset instead of empty
		function getValue($setting)
		{
			// lookup the value in the array 
			if (isset($this->options[$setting]))
			{
				// return its value, if set
				return $this->options[$setting];
			}
			
			// default to NULL
			return NULL;
		}
	}
?>
