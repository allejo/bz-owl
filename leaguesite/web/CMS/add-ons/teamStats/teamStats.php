<?php
	class teamStats
	{
		function __construct($title, $path)
		{
			// this add-on generates graphics for team activity
			// TODO: engine to generate the images not chosen yet
			echo 'yearly';
			include_once(dirname(__FILE__) . '/yearly.php');
/*			include_once('teams.php');
			include_once('teams_ez.php');
			include_once('schoenes.php');
			include_once('VerticalBarChartTest.php');
			include_once('matches.per.hour.php');
			include_once('matches.per.hour.eie-ff.php');
*/
		}
	}
?>
