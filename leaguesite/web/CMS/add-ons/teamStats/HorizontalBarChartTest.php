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
	
	/**
	 * Horizontal bar chart demonstration
	 *
	 */

	include "../CMS/libchart-1.2.1/libchart/classes/libchart.php";

	$chart = new HorizontalBarChart(350, 350);

	$dataSet = new XYDataSet();
	$dataSet->addPoint(new Point("J", 12250));
	$dataSet->addPoint(new Point("F", 500));
	$dataSet->addPoint(new Point("M", 50));
	$dataSet->addPoint(new Point("A", 75));
	$dataSet->addPoint(new Point("M", 122));
	$chart->setDataSet($dataSet);
	$chart->getPlot()->setGraphPadding(new Padding(5, 30, 20, 20));

	$chart->setTitle("Random Crap");
	$chart->render("img/demo2.png");
?>
