<?php
	class logoutSystem
	{		
		public function __construct()
		{
			global $user;
			
			// not much to do here
			// the sole purpose of add-on is to logout the user
			$user->logout();
		}
		
		public function __destruct()
		{
			global $tmpl;
			
			$tmpl->display('logoutSystem');
		}
	}
?>
