<?php
	define ('BASEPATH', baseaddress());
	
	function writeLink($folder, $title, $current=false)
	{
		echo '<li>';
		if (!$current)
		{
			echo '<a href="' . (BASEPATH . $folder) . '">';
		} elseif (count($_GET) > 0)
		{
			echo '<a class="current_nav_entry" href="' . (BASEPATH . $folder) . '">';
		}
		echo $title;
		if (!$current || (count($_GET) > 0))
		{
			echo '</a>';
		}
		echo '</li>' . "\n";
	}
	
	if (!isset($site))
	{
		require_once 'CMS/siteinfo.php';
		$site = new siteinfo();
	}
	
	
	function useTemplate($file)
	{
		echo $file;
		echo ' use!';
	}
	
	// use the template
	// we're done if a template is used
	$stylePath = dirname(dirname(__FILE__)) .'/themes/' . $site->getStyle();
	
	if (file_exists($stylePath . $site->base_name()))
	{
		useTemplate($stylePath . $site->base_name());
		die();
	} elseif (file_exists($stylePath . '/html.template'))
	{
		useTemplate($stylePath . '/html.template');
		die();
	} elseif (file_exists($stylePath . '/php.template'))
	{
		// a theme specific template does exist
		
		// directly execute a template written in PHP
		include($stylePath . '/php.template');
	} else
	{
		// a generic template is required
		
		// directly execute a template written in PHP
		include(dirname($stylePath) . '/php.template');		
	}
?>