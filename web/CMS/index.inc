<?php
	if (!isset($site))
	{
		require_once ('siteinfo.php');
		$site = new siteinfo();
	}

	if ($site->use_xtml())
	{
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . "\n";
		echo '     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	} else
	{
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"' . "\n";
		echo '        "http://www.w3.org/TR/html4/strict.dtd">';
	}
	echo "\n" . '<html';
	if ($site->use_xtml())
	{
		echo ' xmlns="http://www.w3.org/1999/xhtml"';
	}
	echo '>' . "\n";
	echo '<head>' . "\n";
	$site->write_self_closing_tag('meta content="text/html; charset=utf-8" http-equiv="content-type"');

	// display favicon, if specified
	if (!(strcmp($site->favicon_path(), '') === 0))
	{
		echo '	';
		echo $site->write_self_closing_tag('link rel="icon" type="image/png" href="' . $site->favicon_path() . '"');
	}
		if (!isset($_GET['theme']))
	{
		echo '	';
		$site->write_self_closing_tag('link href="' . baseaddress() . 'themes/' . str_replace(' ', '%20', htmlspecialchars($site->getStyle())) . '/' . str_replace(' ', '%20', htmlspecialchars($site->getStyle())) . '.css"' . ' rel="stylesheet" type="text/css"');
	}
	
	if (isset($display_page_title) && (strcmp($display_page_title, 'Official match servers') === 0))
	{
		echo '	';
		$site->write_self_closing_tag('link rel="stylesheet" media="all" href="players.css" type="text/css"');
		// perhaps exclude email string, depending on browser
		$object = new siteinfo();
		if ($object->mobile_version())
		{
			// mobile browser
			echo '<style type="text/css">*.mehl { display: none; } table.punkte { left: 25em; }</style>';
		}
	}
	
	echo '	<title>';
	// display the title specified in $page_title
	// the latter is defined in the script that includes this file
	if (isset($display_page_title) && (!(strcmp($display_page_title, '') === 0)))
	{
		echo $display_page_title;
	} else
	{
		echo 'No page title set';
	}
	echo '</title>' . "\n";
	echo '</head>' . "\n";
	echo '<body>' . "\n";
	
	require_once (dirname(__FILE__) . '/Layout.php');
	

?>
