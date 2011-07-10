<?php
set_include_path('jpgraph-3.5.0b1/src:' . get_include_path());
require ("jpgraph.php");
require 'jpgraph_utils.inc.php';
require ("jpgraph_line.php");
require ("jpgraph_bar.php");
require ("jpgraph_mgraph.php");

$files = glob('*.json');
$datax = $times = array();
$items = NULL;
foreach ($files as $file) {
	$info = json_decode(file_get_contents($file), 1);
	if ($items === NULL) {
		$items = array_keys($info);
	}
	foreach ($items as $item) {
		if (isset($info[$item])) {
			$datax[$item][] = (int)substr($file, 0, -5);
			$datay[$item][] = $info[$item];
		}
	}
}


$w = 450;
$lm=25; $rm=15; 
$grace = 400000;

$total = 0;
//----------------------
// Setup the line graph
//----------------------
$graphs = array();
foreach ($items as $item) {
	$n = count($datax[$item]);
	if ($n < 10) continue;
	//var_dump($item);
	//var_dump($datay);
	$total++;
		
	list($tickPositions,$minTickPositions) = 
		DateScaleUtils::getTicks($datax[$item],DSUTILS_MONTH2);

	// We add some grace to the end of the X-axis scale so that the first and last
	// data point isn't exactly at the very end or beginning of the scale
	$xmin = $datax[$item][0]-$grace;
	$xmax = $datax[$item][$n-1]+$grace;
	$ymin = min($datay[$item]);
	$ymax = max($datay[$item]);
	$ampl = 2;
	if ($ymax - $ymin < 5) $ampl = 4;

	$graph = new Graph($w,250);
	$graph->SetScale('linlin',max($ymin - $ampl, 0), $ymax + $ampl,$xmin,$xmax);
	$graph->SetMargin($lm,$rm,10,30);
	$graph->SetMarginColor('white');
	$graph->SetFrame(false);
	$graph->SetBox(true);
	$graph->title->Set(str_replace(' % ', ' ', urldecode(str_replace('=', '%', $item))));
	$graph->title->SetFont(FF_ARIAL,FS_NORMAL,14);
	$graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);
	$graph->xaxis->SetLabelFormatString('My',true);
	$graph->xgrid->Show();
	$p1 = new LinePlot($datay[$item],$datax[$item]);
	$graph->Add($p1);
	$graphs[] = $graph;
}

//-----------------------
// Create a multigraph
//----------------------
$mgraph = new MGraph();
$mgraph->SetImgFormat('jpeg',60);
$mgraph->SetMargin(2,2,2,2);
$mgraph->SetFrame(true,'darkgray',2);
//$mgraph->SetBackgroundImage('tiger1.jpg');
$y = 0;
$x = 0;
$per_row = floor(sqrt($total));
$i = 0;
foreach ($graphs as $graph) {
	if ($i == 0) {
		$i++;
	} else if ($i++ % $per_row == 0 && $i != 1) {
		$y += 280;
		$x = 0;
	} else {
		$x += $w + 50;
	}
	$mgraph->AddMix($graph,$x,$y,85);
}
$mgraph->Stroke();
