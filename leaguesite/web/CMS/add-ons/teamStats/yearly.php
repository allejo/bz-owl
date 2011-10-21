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
	
	include dirname(dirname(dirname(__FILE))) . '/libchart-1.2.1/libchart/classes/libchart.php';
	
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
		
		// assume year has always 4 digits
		// e.g. 2005
		$curTimestamp = substr($curTimestamp, 0, 4);
		
		if (!isset($matches[$curTimestamp]['year']))
		{
			$matches[$curTimestamp]['year'] = $curTimestamp;
		}
		
		if (strcmp($curTimestamp, $oldTimestamp) === 0)		
		{
			// if we are still in the same month: 1 more match in the month
			$matches[$curTimestamp]['matches']++;
		} else
		{
			// otherwise initialise with 1 (at least one match at that month)
			$matches[$curTimestamp]['matches'] = '1';
		}
		
		// done with this month
		$oldTimestamp = $curTimestamp;
	}
	
	$chart = new VerticalBarChart(450,250);
	$dataSet = new XYDataSet();
	$chart->getPlot()->setGraphPadding(new Padding(0, 15, 15, 50));	
	// add a data point for each month
	foreach ($matches AS $matchesPerYear)
	{
		$dataSet->addPoint(new Point($matchesPerYear['year'], $matchesPerYear['matches']));
	}

	$chart->setDataSet($dataSet);
	$chart->setTitle("Official GU Matches [ 2005 - 2010 ]");
	$chart->render("img/demo2.png");
?>
