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


$start_time=isset($_GET['start_date'])?strtotime($_GET['start_date']):0;
$end_time=isset($_GET['end_date'])?strtotime($_GET['end_date']):0;
if ($end_time<=0) $end_time=time();
if ($start_time<=0) $start_time=$end_time-86400*7;
$i_days=$i_months=0;
if ($end_time-$start_time<=86400*14) $i_days=1;
else if ($end_time-$start_time<=86400*7*12) $i_days=7;
else $i_months=1;

$dates = array();
for (list ($y,$m,$d) = preg_split('/-/',date('Y-m-d',$start_time));;) {
  $intvl=Array('start'=>mktime(0,0,0,$m,$d,$y));
  $d+=$i_days;
  $m+=$i_months;
  $intvl['end']=min($end_time,mktime(0,0,0,$m,$d,$y));
  $dates[]=$intvl;
  if ($intvl['end']>=$end_time) break;
}

$channels = array('google'=>'FF4040',
				  'yahoo'=>'FF40FF',
				  'bing'=>'4040FF',
				  'aol'=>'40FFFF',
				  'amazon.com'=>'FFAD5B',
				  'pricegrabber'=>'FFFFFF',
				  'shopping.com'=>'FFFFFF',
				  'shopzilla'=>'FFFFFF',
				  'ebay'=>'40FF40',
				  'facebook'=>'40FF40',
				  'LinkedIn'=>'40FF40',
				  'other'=>'40FF40',
				  'twitter' => '40FF40',
				  'google-ppc' => '40FF40',
				  'amazon.ca' => '40FF40',
				  'direct' => '40FF40'
				  );

$traffic = array();
$traffic_max = 4;

foreach ($channels AS $ch => $c) {
  $traffic[$ch] = array();
  foreach ($dates AS $i=>$dr) { 
	$traffic[$ch][$i] = 0;
  }
}


for($traffic_query = tep_db_query("SELECT traffic_source,SUM(hit_count) AS hit_count,traffic_date 
									FROM traffic_stats 
									WHERE traffic_date >= '".date('Y-m-d',$start_time)."' 
									AND traffic_date <= '".date('Y-m-d',$end_time+86400)."' 
									GROUP BY traffic_source,traffic_date
									");
	$total_row = tep_db_fetch_array($traffic_query);
) {
    $ch = $total_row['traffic_source'];
    $d = strtotime($total_row['traffic_date']);
    foreach ($dates AS $i => $dr) {
		if(($d >= $dr['start']) && ($d < $dr['end'])) { 
			$traffic[$ch][$i] += $total_row['hit_count'];
		}
	}
}

  $ctraffic=Array();
  $labels=Array();
  $tr=Array();
  foreach ($dates AS $i=>$dr) $tr[$i]=0;
  foreach ($channels AS $ch=>$c) {
    $ctraffic[$ch]=Array();
    foreach ($dates AS $i=>$dr) {
      $tr[$i]+=$traffic[$ch][$i];
      $ctraffic[$ch][$i]=$tr[$i];
      if ($tr[$i]>$traffic_max) $traffic_max=$tr[$i];
    }
  }
  foreach ($channels AS $ch=>$c) {
    $labels[$ch]=Array();
    foreach ($dates AS $i=>$dr) $labels[$ch][$i]=$traffic[$ch][$i]/$traffic_max>=0.04?sprintf('%d%%',$traffic[$ch][$i]/$tr[$i]*100):'';
  }

//  foreach ($traffic AS $ch=>$tr) if (max($tr)>$traffic_max) $traffic_max=max($tr);



$grp = new PlotGroup();

$grp->setPadding(make_num('pad_left',50), make_num('pad_right', 50), make_num('pad_top',NULL), make_num('pad_bottom',NULL));


function axis_gauge($n) {
  for ($q=1;;$q*=10) {
    foreach (Array(10,15,20,25,30,40,50,60,75) AS $r) if ($q*$r>$n) return $q*$r;
  }
}

$qty_label_count=6;
$grp->axis->left->setLabelNumber($qty_label_count);
//$grp->axis->right->setLabelNumber($qty_label_count);

$bw=make_num('bar_width',70)/100;

$traffic_max=axis_gauge($traffic_max);

foreach (array_reverse(array_keys($traffic)) AS $ch) {
    $plot = new BarPlot($ctraffic[$ch], 1, 1, 0);
//    $plot = new BarPlot($sl, 1, 1,$idx*10);
    $plot->setBarColor(make_color($ch.'_color',$channels[$ch]));
    $plot->setBarSize($bw);
    $plot->setYAxis(PLOT_LEFT);
    $plot->setYMin(0);
    $plot->setYMax($traffic_max);
    $plot->label->set($labels[$ch]);
    $plot->label->move(0,8);
    $grp->add($plot);
}

$lbls=Array();
foreach ($dates AS $idx=>$dr) $lbls[]=date('M d',$dr['start']).($dr['end']-$dr['start']>86400?"\n".date('M d',$dr['end']-86400):'');

$grp->axis->bottom->setLabelText($lbls);
//$grp->axis->left->label->setCallbackFunction('FmtAmount');



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
