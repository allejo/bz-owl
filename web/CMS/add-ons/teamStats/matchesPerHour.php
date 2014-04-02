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

	echo 'matchesPerHour' . "\n";
	require_once('libchart/libchart/classes/libchart.php');

	// get stats from database
//	$query = 'SELECT `timestamp` FROM `matches` WHERE `timestamp` LIKE ' . '2010%' . ' ORDER BY `timestamp`';
	$query = 'SELECT `timestamp` FROM `matches` ORDER BY `timestamp`';
	if (!$result = $db->SQL($query))
	{
		die('Could not grab history of all matches ever played.');
	}
	
	// interpret results
	$oldestDate = '';
	$oldTimestamp = '';
	$matches = array();
	while ($row = $db->fetchRow($result))
	{
		// raw database result
		// e.g. 2005-01-23 22:42:20 
		$curTimestamp = $row['timestamp'];
		
		// $curTimestamp contains oldest month and year, if $oldestDate is not set
		if (strlen($oldestDate) === 0)
		{
			// e.g. 01
			$oldestDate = substr($curTimestamp, 5, 2);
			// e.g. 01/2005
			$oldestDate .= '/' . substr($curTimestamp, 0, 4);
		}
		$oldTimestamp = $curTimestamp;
		
		// assume year has always 4 digits, a dash (here -) follows and then there follows always 2 digit month
		// as well as a dash and 2 digit day
		// e.g. 22:
		$curTimestamp = substr($curTimestamp, 11, 2);
		
		if (!isset($matches[$curTimestamp]['hours']))
		{
			// initialise with 1 (at least one match at that hour)
			$matches[$curTimestamp]['matches'] = '1';
			$matches[$curTimestamp]['hours'] = $curTimestamp;
		} else
		{
			// if we are still in the same day: 1 more match in the day
			$matches[$curTimestamp]['matches']++;
		}
	}
	
	// sort matches, beginning with soonest hour of day
	function cmp($a, $b)
	{
		if ($a['hours'] == $b['hours']) {
        		return 0;
		}
		return ($a['hours'] < $b['hours']) ? -1 : 1;
	}
	usort($matches, 'cmp');
	
	$chart = new VerticalBarChart(1400,550);
	$dataSet = new XYDataSet();
//	$chart->getPlot()->setGraphPadding(new Padding(5, 25, 10, 25));	
	// add a data point for each month
	foreach ($matches AS $matchesPerHour)
	{
		$dataSet->addPoint(new Point($matchesPerHour['hours'], $matchesPerHour['matches']));
	}
	
	$chart->setDataSet($dataSet);
	
	// compute oldest date in a nice formatting for chart
	$oldestMonth = substr($oldTimestamp, 5, 2);
	$oldestMonth .=  '/' . substr($oldTimestamp, 0, 4);
	
	$chart->setTitle('Official GU Matches Per Hour (UTC) [ ' . $oldestDate . ' - ' . $oldestMonth . ' ]');
	
	// FIXME: Where should the graph be saved?
	$chart->render(dirname(__FILE__) . '/img/matchesPerHourBar.png');
	
	
	
	$chart = new LineChart(1400,550);
	$dataSet = new XYDataSet();
	//	$chart->getPlot()->setGraphPadding(new Padding(5, 25, 10, 25));	
	// add a data point for each month
	foreach ($matches AS $matchesPerHour)
	{
		$dataSet->addPoint(new Point($matchesPerHour['hours'], $matchesPerHour['matches']));
	}
	
	$chart->setDataSet($dataSet);
	
	// compute oldest date in a nice formatting for chart
	$oldestMonth = substr($oldTimestamp, 5, 2);
	$oldestMonth .=  '/' . substr($oldTimestamp, 0, 4);
	
	$chart->setTitle('Official GU Matches Per Hour (UTC) [ ' . $oldestDate . ' - ' . $oldestMonth . ' ]');
	
	// FIXME: Where should the graph be saved?
	$chart->render(dirname(__FILE__) . '/img/matchesPerHourLine.png');
?>
