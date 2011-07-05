<?php
	class local
	{
		private $xhtml = true;
		
		function __construct()
		{
			global $config;
			
			
			$this->xhtml = $config->getValue('useXhtml');
		}
		
		
		function showLoginText()
		{
			global $config;
			global $db;
			
			
			$oldWebsiteName = $config->getValue('login.local.oldWebsiteName');
			if ($oldWebsiteName === false)
			{
				$db->logError('CONFIG ERROR: Variable login.local.oldWebsiteName is not set. '
							  . 'Generator: ' . __FILE__);
				return 'ERROR: Variable login.local.oldWebsiteName is not set in config.';
			}
			
			
			// show login text only if configured to do so
			if ($config->getValue('login.local.showLoginText'))
			{
				$msg = ('<form action="./?module=local&action=form" method="post">' . "\n");
				$msg .= '<p class="first_p">' . "\n";
				if ($config->getValue('forceExternalLoginOnly'))
				{
					$msg .= ('<input type="submit" name="local_login_wanted"'
							 . 'value="Update old account from '
							 . $oldWebsiteName . '"');
					$msg .= $this->xhtml ? ' />' : '>';
				} else
				{
					$msg .= ('<input type="submit" name="local_login_wanted"'
							. 'value="Local login"');
					$msg .= $this->xhtml ? ' />' : '>';
				}
				$msg .= '</p>' . "\n";
				$msg .= '</form>' . "\n";
				return ($msg);
			}
		}
		
		
		function showForm()
		{
			global $config;
			
			
			$msg = '';
			$oldWebsiteName = $config->getValue('login.local.oldWebsiteName');
			if ($oldWebsiteName === false)
			{
				$db->logError('CONFIG ERROR: Variable login.local.oldWebsiteName is not set. '
							  . 'Generator: ' . __FILE__);
				return 'ERROR: Variable login.local.oldWebsiteName is not set in config.';
			}
			
			if ($config->getValue('login.local.convertUsersToExternalLogin'))
			{
				$modules = newWorldLogin::getModules();
				if (array_search('bzbb', $modules) !== false)
				{
					$msg .= ('<strong><span class="unread_messages">'
							 . 'Before you continue make absolutely sure your account here '
							 . 'and the my.bzflag.org/bb/ (forum) account have exactly the '
							 . 'same username or you will give someone else access to your account '
							 . 'and that access can never be revoked.</span></strong></p>');
				}
			}
			
			$msg .= 'Enter login data from <strong>' . $oldWebsiteName . '</strong> here!</p>';
			$msg .= "\n";
			
			// load form
			$msg .= '<form action="' . $config->getValue('baseaddress') . 'Login/'. '" method="post">' . "\n";
			$msg .= '<div class="p">Name:</div>' . "\n";
			$msg .= '<p class="first_p">' . "\n";
			$msg .= '<input type="text" class="small_input_field" name="loginname" value="" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</p>' . "\n";
			
			$msg .= '<div class="p">Password:</div>' . "\n";
			$msg .= '<p class="first_p">' . "\n";
			$msg .= '<input type="password" name="pw" value="" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</p>' . "\n";
			
			$msg .= '<div>' . "\n";
			$msg .= '<input type="hidden" name="module" value="local" maxlength="300"';
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</div>' . "\n";
			
			$msg .= '<div>' . "\n";
			if ($config->getValue('forceExternalLoginOnly'))
			{
				$msg .= '<input type="submit" value="Update"';
			} else
			{
				$msg .= '<input type="submit" value="Login"';
			}
			$msg .= $this->xhtml ? ' />' : '>';
			$msg .= '</div>' . "\n";
			$msg .= '</form>' . "\n";
			
			$msg .= '<p>Note: Only global login has the ability to allow more than standard permissions at the moment.</p>' . "\n";
			
			return $msg;
		}
		
		
		function validateLogin(&$output)
		{
			
		}
	}
?>
