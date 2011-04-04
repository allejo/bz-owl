<?php
	/* Libchart - PHP chart library
	 * Copyright (C) 2005-2008 Jean-Marc Trémeaux (jm.tremeaux at gmail.com)
	 * 
	 * This program is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 * 
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 * 
	 */
	
	/*
	 * Vertical bar chart demonstration
	 *
	 */

	if (!isset($site))
	{
		die('this file is not meant to be called directly');
	}

	include "../CMS/libchart-1.2.1/libchart/classes/libchart.php";

	// get stats from database
	$query = 'SELECT `timestamp` FROM `matches` ORDER BY `timestamp`';
	if (!$result = $site->execute_query('matches', $query, $connection))
	{
		die('Could not grab history of all matches ever played.');
	}

	// interpret results
	$oldTimestamp = '';
	$matches = array();
	while ($row = mysql_fetch_array($result))
	{
		// raw database result
		// e.g. 2005-01-23 22:42:20  
		$curTimestamp = $row['timestamp'];
		
		// assume year has always 4 digits, a sign (here -) follows and then there follows always  2 digit month
		// e.g. 2005-01
		$curTimestamp = substr($curTimestamp, 0, 7);
		
		
		if (strcmp($curTimestamp, $oldTimestamp) === 0)		
		{
			// if we are still in the same month: 1 more match in the month
			$matches[$curTimestamp]['matches']++;
		} else
		{
			// otherwise initialise with 1 (at least one match at that month)
			$matches[$curTimestamp]['matches'] = '1';
			
			// map numeric month to human readable text
			switch (substr($curTimestamp, 5))
			{
				case "01": $matches[$curTimestamp]['month'] = 'J'; break;
				case "02": $matches[$curTimestamp]['month'] = 'F'; break;
				case "03": $matches[$curTimestamp]['month'] = 'M'; break;
				case "04": $matches[$curTimestamp]['month'] = 'A'; break;
				case "05": $matches[$curTimestamp]['month'] = 'M'; break;
				case "06": $matches[$curTimestamp]['month'] = 'J'; break;
				case "07": $matches[$curTimestamp]['month'] = 'J'; break;
				case "08": $matches[$curTimestamp]['month'] = 'A'; break;
				case "09": $matches[$curTimestamp]['month'] = 'S'; break;
				case "10": $matches[$curTimestamp]['month'] = 'O'; break;
				case "11": $matches[$curTimestamp]['month'] = 'N'; break;
				case "12": $matches[$curTimestamp]['month'] = 'D'; break;
			}
		}
		
		// done with this month
		$oldTimestamp = $curTimestamp;
	}
	
	
	$chart = new VerticalBarChart(1400,550);
	$dataSet = new XYDataSet();
	$chart->getPlot()->setGraphPadding(new Padding(5, 25, 10, 25));	
	// add a data point for each month
	foreach ($matches AS $matchesPerMonth)
	{
		$dataSet->addPoint(new Point($matchesPerMonth['month'], $matchesPerMonth['matches']));
	}
	
	$chart->setDataSet($dataSet);
	$chart->setTitle("Official GU Matches [ 01/2005 - $curTimestamp ]");
	$chart->render("img/demo1.png");
?>
