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
		
		function value($setting)
		{
			// lookup the value in the array 
			if (isset($this->options[$setting]))
			{
				// return its value, if set
				return $this->options[$setting];
			}
			
			// default to false
			return false;
		}
	}
?>
