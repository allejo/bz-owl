<?php
	if (!isset($site))
	{
		die('this file is not meant to be called directly');
	}
	
	set_include_path( "ezcomponents-2009.2.1/" . PATH_SEPARATOR .  get_include_path());
	
	require_once "../CMS/ezcomponents-2009.2.1/Base/src/base.php";
//	function __autoload( $className )
//	{
//        	ezcBase::autoload( $className );
//	}

	$graph = new ezcGraphPieChart();
	$graph->palette = new ezcGraphPaletteBlack();
	$graph->title = 'Access statistics';
	$graph->options->label = '%2$d (%3$.1f%%)';

	$graph->driver = new ezcGraphGdDriver(); 
	$graph->options->font = '../CMS/libchart-1.2.1/libchart/fonts/DejaVuSansCondensed.ttf'; 	
	$graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
	 'Mozilla' => 19113,
	'Explorer' => 10917,
	'Opera' => 1464,
	'Safari' => 652,
	'Konqueror' => 474,
	) );

	//$graph->data['Access statistics']->highlight['Explorer'] = true;

	$graph->renderer->options->moveOut = .2;

	$graph->renderer->options->pieChartOffset = 63;

	$graph->renderer->options->pieChartGleam = .3;
	$graph->renderer->options->pieChartGleamColor = '#FFFFFF';
	$graph->renderer->options->pieChartGleamBorder = 2;

	$graph->renderer->options->pieChartShadowSize = 3;
	$graph->renderer->options->pieChartShadowColor = '#000000';

	$graph->renderer->options->legendSymbolGleam = .5;
	$graph->renderer->options->legendSymbolGleamSize = .9;
	$graph->renderer->options->legendSymbolGleamColor = '#FFFFFF';

	$graph->renderer->options->pieChartSymbolColor = '#BABDB688';

	//$graph->render( 400, 150, 'tutorial_pie_chart_pimped.svg' ); 

	
	$graph->render( 450, 250, 'img/zweiterversuch.png' );

?>	
