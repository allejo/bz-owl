<?php
	class teamStats
	{
		function __construct($title, $path)
		{
			global $site;
			global $db;
			
			// this add-on generates graphics for team activity
			// TODO: engine to generate the images not chosen yet
			include_once('yearBar.php');
			include_once('yearPie.php');
//			include_once('teams_ez.php'); // broken
//			include_once('schoenes_ez.php'); // broken
			include_once('matchesPerMonthBar.php');
			include_once('matchesPerHour.php');
//			include_once('matches.per.hour.eie-ff.php');
		}
	}
?>
