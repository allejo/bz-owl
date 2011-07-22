<?php
	if (!isset($site))
	{
		die('this file is not meant to be called directly');
	}
	
	set_include_path( "../CMS/ezcomponents-2009.2.1/" . PATH_SEPARATOR .  get_include_path());
	
	require_once "../CMS/ezcomponents-2009.2.1/Base/src/base.php";
	function __autoload( $className )
	{
        	ezcBase::autoload( $className );
	}

	if (!isset($site))
	{
		die('this file is not meant to be called directly');
	}

//	include "../CMS/libchart-1.2.1/libchart/classes/libchart.php";

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
			$matches[$curTimestamp] ['timestamp'] = $curTimestamp;
		} else
		{
			// otherwise initialise with 1 (at least one match at that month)
			$matches[$curTimestamp]['matches'] = '1';
			$matches[$curTimestamp] ['timestamp'] = $curTimestamp;
			
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
	


	$graph = new ezcGraphBarChart();
	$graph->title = 'GU Matches 01/2005 -11/2010';
	// disable legend
	$graph->legend = false;

	$graph->driver = new ezcGraphGdDriver(); 
	$graph->options->font = '../CMS/libchart-1.2.1/libchart/fonts/DejaVuSansCondensed.ttf'; 	
/*	
echo '<pre>';
print_r($matches);
echo '</pre>';
*/
	$dataset = array();
	foreach ($matches AS $matchesPerMonth)
	{
		//$dataset[$matchesPerMonth['timestamp']] = $matchesPerMonth['month'] = $matchesPerMonth['matches'];
		$dataset[$matchesPerMonth['timestamp']] = $matchesPerMonth['month'] = $matchesPerMonth['matches'];
	}
	
	//foreach ($matches AS $matchesPerMonth)
	//{
		//$graph->data[ $matchesPerMonth['timestamp'] ] = new ezcGraphArrayDataSet( array($matchesPerMonth['timestamp'] => $matchesPerMonth['matches'])  );
		$graph->data[ $matchesPerMonth['timestamp'] ] = new ezcGraphArrayDataSet( $dataset );
	//}
echo '<pre>';
print_r($dataset);
echo '</pre>';

	$graph->data[ $matchesPerMonth['timestamp'] ]->displayType = ezcGraph::LINE;
	$graph->data[ $matchesPerMonth['timestamp'] ]->highlight = true;
	$graph->data[ $matchesPerMonth['timestamp'] ]->highlightSize = 3;
//print_r($matchesPerMonth);
	//$graph->data[ '1' ] = new ezcGraphArrayDataSet( array('M' => 3)  ); 		
	//$graph->data[ '2' ] = new ezcGraphArrayDataSet( array('W' => 6)  ); 		
	
	//$graph->options->label = '%3$.1f%%';
	//$graph->options->sum = 100;
	//$graph->options->percentThreshold = 0.02;
	//$graph->options->summarizeCaption = 'Others';


//	$graph->renderer = new ezcGraphRenderer3d(); 
	$graph->render( 1450, 450, 'img/ersterversuch.png' );

	
/*	$chart = new PieChart(450,250);
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
*/
?>
