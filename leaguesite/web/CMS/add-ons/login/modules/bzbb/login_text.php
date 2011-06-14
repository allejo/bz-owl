<?php
	$msg = '<p class="first_p">Please log in using your account at <a href=';
	$url = urlencode($config->getValue('baseaddress') . 'Login/' . '?bzbbauth=%TOKEN%,%USERNAME%');
	
	// process login information
	require dirname(__FILE__) . '/index.php';
	
	$msg .= '"' . htmlspecialchars('http://my.bzflag.org/weblogin.php?action=weblogin&url=') . $url;
	$msg .= '">my.bzflag.org (BZBB)</a>.</p>' . "\n";
	
	$this->helper->addMsg($msg);
	$msg = '';
?>
