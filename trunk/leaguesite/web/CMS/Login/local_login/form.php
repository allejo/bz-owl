<?php
	function writeForm()
	{
		global $site;
		global $config;
		global $tmpl;
		
		$msg = '<form action="' . baseaddress() . 'Login/'. '" method="post">' . "\n";
		$msg .= '<div class="p">Name:</div>' . "\n";
		$msg .= '<p class="first_p">' . "\n";
		$msg .= $tmpl->return_self_closing_tag('input type="text" class="small_input_field" name="loginname" value="" maxlength="300"');
		$msg .= '</p>' . "\n";
		
		$msg .= '<div class="p">Password:</div>' . "\n";
		$msg .= '<p class="first_p">' . "\n";
		$msg .= $tmpl->return_self_closing_tag('input type="password" name="pw" value="" maxlength="300"');
		$msg .= '</p>' . "\n";
		
		$msg .= '<div>' . "\n";
		$msg .= $tmpl->return_self_closing_tag('input type="hidden" name="module" value="local" maxlength="300"');
		$msg .= '</div>' . "\n";
		
		$msg .= '<div>' . "\n";
		if ($config->value('forceExternalLoginOnly'))
		{
			$msg .= $tmpl->return_self_closing_tag('input type="submit" value="Update"');
		} else
		{
			$msg .= $tmpl->return_self_closing_tag('input type="submit" value="Login"');
		}
		$msg .= '</div>' . "\n";
		$msg .= '</form>' . "\n";
		
		return $msg;
	}
?>

