<?php

  require('includes/application_top.php');
  require(DIR_FS_CATALOG.'artichow/BarPlot.class.php');
  require(DIR_FS_CATALOG.'artichow/LinePlot.class.php');
  require(DIR_FS_CATALOG.'artichow/ScatterPlot.class.php');
  
  error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
  error_reporting(0);


$width=isset($_GET['width'])?$_GET['width']:100;
$height=isset($_GET['height'])?$_GET['height']:100;

$graph = new Graph($width,$height);
$graph->setAntiAliasing(TRUE);

$values = Array();

function make_color($fld,$hex,$idx=NULL) {
  if (isset($_GET[$fld])) $hex=is_array($_GET[$fld])?$_GET[$fld][$idx]:$_GET[$fld];
  $op=0;
  if (preg_match('/([0-9a-fA-F]{6})(-(\d+))?/',$hex,$p)) {
    $hex=$p[1];
    if (isset($p[2]) && $p[2]) $op=$p[3];
  }
  $rgb=sscanf($hex,'%02x%02x%02x');
  return new awColor($rgb[0],$rgb[1],$rgb[2],$op);
}

function set_font(&$obj,$fld) {
  if (isset($_GET[$fld])) {
    if (preg_match('/^(\d\d?)?(b|i|bi|ib)?(-|$)/',$_GET[$fld],$p)) {
      $s=$p[1];
      if (!$s) $s=10;
      $at=isset($p[2])?$p[2]:'';
      $font=strstr($at,'b')?(strstr($at,'i')?new TuffyBoldItalic($s):new TuffyBold($s)):(strstr($at,'i')?new TuffyItalic($s):new Tuffy($s));
//      echo $obj;
      $obj->setFont($font,make_color($fld,'000000'));
    } else {
      $obj->setColor(make_color($fld,'000000'));
    }
  }
}

function make_num($fld,$v) {
  if (isset($_GET[$fld])) return $_GET[$fld]+0;
  return $v;
}

function wdiff($v1,$w1,$v2,$w2) {
  if ($w1==0 || $w2==0) return 0;
  return ($v1/$w1-$v2/$w2)*($w1+$w2)/2;
}

function mdays($m) {
 $cm=date('m')+0;
 return $m<$cm?30:($m==$cm?date('d'):0);
}




function FmtAmount($val) {
  return abs($val)>=1000000?'$'.round($val/1000000,1).'M':abs($val)>=1000?'$'.round($val/1000,1).'K':'$'.round($val,1);
}



$grp = new PlotGroup();

$grp->setPadding(make_num('pad_left',35), make_num('pad_right', 10), make_num('pad_top',NULL), make_num('pad_bottom',30));


function axis_gauge($n) {
  for ($q=1;;$q*=10) {
    foreach (Array(10,15,20,25,30,40,50,60,75) AS $r) if ($q*$r>$n) return $q*$r;
  }
}

$qty_label_count=6;
$grp->axis->left->setLabelNumber($qty_label_count);
//$grp->axis->right->setLabelNumber($qty_label_count);

$bw=make_num('bar_width',70)/100;

$y_max=axis_gauge(max(max($_GET['order_sum']),max($_GET['order_avg'])));

    $plot = new BarPlot($_GET['order_sum'], 1, 1, 0);
	//$plot = new BarPlot($sl, 1, 1,$idx*10);
    $plot->setBarColor(make_color('sum_color','7EAD5C'));
    $plot->setBarSize($bw);
    $plot->setYAxis(PLOT_LEFT);
    $plot->setYMin(0);
    $plot->setYMax($y_max);
	
	$grp->legend->add($plot,'Totals',LEGEND_BACKGROUND);
    $grp->add($plot);

    $plot = new BarPlot($_GET['order_avg'], 1, 1, 0);
	//$plot = new BarPlot($sl, 1, 1,$idx*10);
    $plot->setBarColor(make_color('avg_color','A6C68E'));
    $plot->setBarSize($bw);
    $plot->setYAxis(PLOT_LEFT);
    $plot->setYMin(0);
    $plot->setYMax($y_max);

	$grp->legend->add($plot,'Averages',LEGEND_BACKGROUND);
    $grp->add($plot);


$grp->axis->bottom->setLabelText($_GET['text']);
$grp->axis->left->label->setCallbackFunction('FmtAmount');



set_font($grp->axis->bottom->label,'x_font');
set_font($grp->axis->left->label,'y_font');
set_font($grp->axis->right->label,'y_font');

$graph->border->hide();
$grp->setBackgroundColor(make_color('bg_color','FFFFFF'));
$grp->grid->setBackgroundColor(make_color('bg_plot_color','FFFFFF'));

$graph->add($grp);
$graph->draw();

// Transpose array ($foo['baz']['bar'] --> $foo['bar']['baz'])
function a_t($array)
{
    $r=array();
    foreach($array as $k1=>$ia) {
        foreach($ia as $k2=>$v) {
            $r[$k2][$k1]=$v;
        }
    }
    return $r;
}
?>
