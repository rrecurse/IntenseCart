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

$dates=Array();
for (list ($y,$m,$d) = explode('-',date('Y-m-d',$start_time));;) {
  $intvl=Array('start'=>mktime(0,0,0,$m,$d,$y));
  $d+=$i_days;
  $m+=$i_months;
  $intvl['end']=min($end_time,mktime(0,0,0,$m,$d,$y));
  $dates[]=$intvl;
  if ($intvl['end']>=$end_time) break;
}


$sales=Array();
$views=Array();
$sales_max=4;
$views_max=4;

$pidlst=(isset($_GET['pids']) && is_array($_GET['pids']))?$_GET['pids']:Array('35');
foreach ($pidlst AS $idx=>$pidl) {
  $pids = explode(',',$pidl);
  $lpids=IXdb::read("SELECT products_id FROM products WHERE master_products_id IN ('".join("','",$pids)."')",Array(NULL),'products_id');
  
  $sales[$idx]=$views[$idx]=Array();
  foreach ($dates AS $i=>$dr) $sales[$idx][$i]=$views[$idx][$i]=0;
  
  if ($pids && $lpids) {
    $total_query=tep_db_query("
    SELECT
		SUM(op.products_quantity) AS qty,
		DATE(o.date_purchased) AS date_purchased
    	FROM ".TABLE_ORDERS." o
    	LEFT JOIN ".TABLE_ORDERS_PRODUCTS." op
            ON (o.orders_id=op.orders_id  and op.products_id IN (".join(',',$lpids)."))
    WHERE
		o.orders_status>0 AND 
		o.date_purchased>='".date('Y-m-d',$start_time)."' AND 
		o.date_purchased<'".date('Y-m-d',$end_time+86400)."' 
	GROUP BY
        DATE(o.date_purchased)
    ");
    while ($total_row=tep_db_fetch_array($total_query)) {
      $d=strtotime($total_row['date_purchased']);
      foreach ($dates AS $i=>$dr) if ($d>=$dr['start'] && $d<$dr['end']) $sales[$idx][$i]+=$total_row['qty'];
    }

    $vcond=Array();
    foreach ($pids AS $pid) $vcond[]="products_viewed LIKE '%*$pid?%'";
    $cov_q = IXdb::query("select COUNT(0) As qty, DATE(time_arrived) AS date_viewed
    	FROM supertracker
	WHERE
		time_arrived>='".date('Y-m-d',$start_time)."' AND 
		time_arrived<'".date('Y-m-d',$end_time+86400)."' AND
		(".join (' OR ',$vcond).")
	GROUP BY
        DATE(time_arrived)
    ");
    while ($total_row=tep_db_fetch_array($cov_q)) {
      $d=strtotime($total_row['date_viewed']);
      foreach ($dates AS $i=>$dr) if ($d>=$dr['start'] && $d<$dr['end']) $views[$idx][$i]+=$total_row['qty'];
    }



  }


  foreach ($sales[$idx] AS $s) if ($s>$sales_max) $sales_max=$s;  
  foreach ($views[$idx] AS $s) if ($s>$views_max) $views_max=$s;  
}

//print_r($sales);
//exit;

/*
$min_val_ret=isset($val_ret)?min($val_ret):0;
$min_val_retprv=isset($val_retprv)?min($val_retprv):0;
$max_val_qty=isset($val_qty)?max($val_qty):0;
$max_val_qtyprv=isset($val_qtyprv)?max($val_qtyprv):0;

$qty_min = min(0, $min_val_ret, $min_val_retprv);
$qty_max = 1.05*max(1, $max_val_qty, $max_val_qtyprv);
if (isset($val_ytd[0]) && isset($val_prv[0]) && isset($val_prv[11])) {
  $val_biz[0]=($val_ytd[0]+$val_prv[0])/2+wdiff($val_ytd[0],mdays(1),$val_prv[11],30);
} else {
  $val_biz[0]=0;
}
for ($i=1;$i<12;$i++) {
  if (isset($val_ytd[$i]) && isset($val_prv[$i]) && isset($val_ytd[$i-1]) && isset($val_prv[$i-1])) {
    $val_biz[$i]=($val_ytd[$i]+$val_prv[$i])/2+wdiff($val_ytd[$i],mdays($i+1),$val_ytd[$i-1],mdays($i))-wdiff($val_prv[$i],30,$val_prv[$i-1],30)/2;
  } else {
    $val_biz[$i]=0;
  }
}

function FmtAmount($val) {
  return abs($val)>=1000000?'$'.round($val/1000000,1).'M':abs($val)>=1000?'$'.round($val/1000,1).'K':'$'.round($val,1);
}

*/


$grp = new PlotGroup();

$grp->setPadding(make_num('pad_left',25), make_num('pad_right', 25), make_num('pad_top',NULL), make_num('pad_bottom',NULL));

/*
$max_val_ytd=$val_ytd?max($val_ytd):0;
$max_val_prv=$val_prv?max($val_prv):0;
$max=max(1.05*$max_val_ytd,1.05*$max_val_prv);

$min_val_ytd=$val_ytd?min($val_ytd):0;
$min=min(0,$min_val_ytd);

$pwr_qty = 1;
while ($qty_max > 99) {
	$qty_max /= 10;
	$pwr_qty *= 10;
}
$qty_max = $pwr_qty * ceil($qty_max/5) * 5;
$qty_step = $qty_max/5;
$qty_min = -ceil(-$qty_min / $qty_step) * $qty_step;
$qty_min = 0;
$qty_label_count = 6;
//$qty_min = -$pwr_qty * ceil(-$qty_min/5/$pwr_qty) * 5;
$pwr = 1;
while ($max > 99) {
	$max /= 10;
	$pwr *= 10;
};
$max = $pwr * ceil($max/5) * 5;
$min = -$pwr * ceil(-$qty_min/$qty_max*$max/$pwr);
$min = 0;
*/

function axis_gauge($n) {
  for ($q=1;;$q*=10) {
    foreach (Array(10,15,20,25,30,40,50,60,75) AS $r) if ($q*$r>$n) return $q*$r;
  }
}

$qty_label_count=6;
$grp->axis->left->setLabelNumber($qty_label_count);
$grp->axis->right->setLabelNumber($qty_label_count);

$bw=make_num('bar_width',70)/100;

$sales_max=axis_gauge($sales_max);
$views_max=axis_gauge($views_max);

foreach ($sales AS $idx=>$sl) {
    $plot = new BarPlot($sl, $idx+1, sizeof($sales), 0);
//    $plot = new BarPlot($sl, 1, 1,$idx*10);
    $plot->setBarColor(make_color('sales_color','4040FF',$idx));
    $plot->setBarSize($bw);
    $plot->setYAxis(PLOT_RIGHT);
    $plot->setYMin(0);
    $plot->setYMax($sales_max);
    $grp->add($plot);
}
foreach ($views AS $idx=>$vw) {
    $xkeys=array();
    foreach($sales[$idx] as $k=>$v) {
        $xkeys[$k]=$k+.5+($idx-(count($views)-1)/2)/count($views)*$bw;
    }
    //for ($i=0;isset($val_ytd[$i]);$i++) $xkeys[$i]=$i+.5;
    $plot = new ScatterPlot(array_values($vw), array_values($xkeys));
    $plot->link(TRUE,make_color('views_color','40FFFF',$idx));
    $plot->mark->setSize(make_num('views_mark',10));
    $plot->mark->setFill(make_color('views_markcolor','F0F0F0'));
    $plot->setThickness(make_num('views_thick',5));
    $plot->setYAxis(PLOT_LEFT);
    $plot->setYMin(0);
    $plot->setYMax($views_max);
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
