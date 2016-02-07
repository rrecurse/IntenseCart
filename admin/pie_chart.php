<?php

  require('includes/application_top.php');
  require(DIR_FS_CATALOG.'artichow/Pie.class.php');


$width=isset($_GET['width'])?$_GET['width']:100;
$height=isset($_GET['height'])?$_GET['height']:100;
$graph = new Graph($width,$height);
$graph->setAntiAliasing(TRUE);

$colors = Array();
$values = Array();
$explode = Array();

function make_color($hex) {
  $rgb=sscanf($hex,'%02x%02x%02x');
  return new awColor($rgb[0],$rgb[1],$rgb[2]);
}

$parse_data=Array();
$maxexpl=0;
if (preg_match_all('/([0-9a-f]{6}):([\d\.]+)(:([\d\.]+))?/i', $_GET['data'], $parse_data)) {
  for ($i=0;isset($parse_data[1][$i]);$i++) {
    $colors[]=make_color($parse_data[1][$i]);
    $values[]=$parse_data[2][$i];
    if (isset($parse_data[3][$i])) {
      $explode[sizeof($colors)-1]=$expl=$parse_data[4][$i];
      if ($expl>$maxexpl) $maxexpl=$expl;
    }
  }
}
if (!sizeof($colors)) {
  $colors[]=new awColor(192,192,192);
  $values[]=1;
}

$plot = new Pie($values, $colors);
$thick=isset($_GET['thickness'])?$_GET['thickness']:20;
$plot->setCenter(0.5, 0.5-$thick/200);
$pwidth=isset($_GET['pwidth'])?$_GET['pwidth']:100;
$pheight=isset($_GET['pheight'])?$_GET['pheight']:87;
$plot->setSize(1.5*$pwidth/105, 1.5*$pheight/105);
$border=isset($_GET['border'])?$_GET['border']:808080;
//if(isset($_GET['border'])) $plot->border = make_color($_GET['border']);
$plot->set3D(floor($thick*$height/100));
$plot->setStartAngle(isset($_GET['start_angle'])?$_GET['start_angle']:270);

if(isset($_GET['legend'])) {
$plot->legend->show();
} else {
$plot->legend->hide();
}
if ($explode) $plot->explode($explode);
if (isset($_GET['label'])) {
  $plot->setLabelPosition($_GET['label']);
  $plot->setLabelMinimum(.01);
} else {
  $plot->setLabelMinimum(100000);
}


$graph->border->hide();
if (isset($_GET['bgcolor'])) {
 $graph->setBackgroundColor(make_color($_GET['bgcolor']));
 $plot->setBackgroundColor(make_color($_GET['bgcolor']));
}
$graph->add($plot);
$graph->draw();

?>
