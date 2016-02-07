<?php

  require('includes/application_top.php');
  require(DIR_FS_CATALOG.'artichow/BarPlot.class.php');
  require(DIR_FS_CATALOG.'artichow/LinePlot.class.php');
  require(DIR_FS_CATALOG.'artichow/ScatterPlot.class.php');


	$width = (isset($_GET['width'])) ? $_GET['width'] : 380;
	$height = (isset($_GET['height'])) ? $_GET['height'] : 185;

	$graph = new Graph($width,$height);
	$graph->setAntiAliasing(TRUE);

	$values = array();

	function make_color($fld,$hex) {
		if(isset($_GET[$fld])) $hex = $_GET[$fld];
		$op = 0;
		if (preg_match('/([0-9a-fA-F]{6})(-(\d+))?/',$hex,$p)) {
			$hex=$p[1];
			if(isset($p[2]) && $p[2]) $op=$p[3];
		}
		$rgb = sscanf($hex,'%02x%02x%02x');
		return new awColor($rgb[0],$rgb[1],$rgb[2],$op);
	}

	function set_font(&$obj,$fld) {
  		if(isset($_GET[$fld])) {
			if(preg_match('/^(\d\d?)?(b|i|bi|ib)?(-|$)/',$_GET[$fld],$p)) {
				$s = $p[1];
				if(!$s) $s=10;
				$at = (isset($p[2])) ? $p[2] : '';

				if(strstr($at,'b')) {
					$font = (strstr($at,'i')) ? new TuffyBoldItalic($s) : new TuffyBold($s);
				} elseif(strstr($at,'i')) { 
					$font = new TuffyItalic($s);
				} else {
					$font = new Tuffy($s);
				}

				$obj->setFont($font,make_color($fld,'000000'));
			} else {
				$obj->setColor(make_color($fld,'000000'));
			}
		}
	}


	function make_num($fld,$v) {
		if(isset($_GET[$fld])) return $_GET[$fld]+0;
		return $v;
	}

	function wdiff($v1,$w1,$v2,$w2) {
		if($w1==0 || $w2==0) return 0;
		return ($v1/$w1-$v2/$w2)*($w1+$w2)/2;
	}

	function mdays($m) {
		$cm = date('m') + 0;
		return ($m < $cm) ? 30 : ($m == $cm ? date('d') : 0);
	}

	$val_ytd = array();
	$val_prv = array();
	$val_biz = array();
	$total = array();

	$total_query = tep_db_query("
	SELECT YEAR(s.affiliate_date) AS y,
	MONTH(s.affiliate_date) AS m,
	SUM(COALESCE(s.affiliate_value, 0.00)) AS s
	FROM " . TABLE_AFFILIATE_SALES . " s 
	LEFT JOIN " . TABLE_ORDERS . " o ON s.affiliate_orders_id = o.orders_id 
	WHERE o.orders_status > 0 
	AND s.affiliate_date >= '".(date('Y')-1)."-01-01'
	AND s.affiliate_date <= '".date('Y-m-d')."'
	GROUP BY YEAR(s.affiliate_date), MONTH(s.affiliate_date)
	");

		while ($total_row = tep_db_fetch_array($total_query)) {
			if(!isset($total[$total_row['y']])) {  
				$total[$total_row['y']] = array();
			}
			$total[$total_row['y']][$total_row['m']] = array('sum' => $total_row['s']);
			
		}
		
		$ytd = (isset($total[date('Y')])) ? $total[date('Y')] : array();
		$prv = (isset($total[date('Y')-1])) ? $total[date('Y')-1] : array();
		$total = array();

		$qnty_query = tep_db_query("
		SELECT YEAR(s.affiliate_date) AS y,
		MONTH(s.affiliate_date) AS m,
        SUM(op.products_quantity) AS q
	 	FROM " . TABLE_AFFILIATE_SALES . " s 
		INNER JOIN " . TABLE_ORDERS . " o ON s.affiliate_orders_id = o.orders_id 
        LEFT JOIN ".TABLE_ORDERS_PRODUCTS." op ON o.orders_id = op.orders_id
	    WHERE o.orders_status > 0 
		AND s.affiliate_date >= '".(date('Y')-1)."-01-01'
		AND s.affiliate_date <= '".date('Y-m-d')."'
		GROUP BY YEAR(s.affiliate_date), MONTH(s.affiliate_date)
		");

		while ($qnty_row = tep_db_fetch_array($qnty_query)) {
			if(!isset($total[$qnty_row['y']])) { 
				$total[$qnty_row['y']] = array();
			}
			$total[$qnty_row['y']][$qnty_row['m']] = array('qty' => $qnty_row['q']);
		}
		
		$ytd_qty = (isset($total[date('Y')])) ? $total[date('Y')] : array();
		$prv_qty = (isset($total[date('Y')-1])) ? $total[date('Y')-1] : array();

		$returns_query = tep_db_query("
		SELECT YEAR(s.affiliate_date) AS y,
		MONTH(s.affiliate_date) AS m,
		SUM(COALESCE(rpd.products_quantity, 0)) as r
		FROM ".TABLE_AFFILIATE_SALES." s 
		LEFT JOIN ".TABLE_RETURNS." rp ON s.affiliate_orders_id = rp.order_id 
		LEFT JOIN ".TABLE_RETURNS_PRODUCTS_DATA." rpd ON rp.order_id = rpd.order_id
		WHERE s.affiliate_date >= '".(date('Y')-1)."-01-01'
		AND s.affiliate_date <= '".date('Y-m-d')."'
		GROUP BY YEAR(s.affiliate_date), MONTH(s.affiliate_date)	
		");

		while($return_row = tep_db_fetch_array($returns_query)) {
			if(!isset($returned[$return_row['y']])) { 
				$returned[$return_row['y']] = array();
			}
			$returned[$return_row['y']][$return_row['m']]['ret'] = $return_row['r'];
		}

		$ytd_ret = (isset($returned[date('Y')])) ? $returned[date('Y')] : array();
		$prv_ret = (isset($returned[date('Y')-1])) ? $returned[date('Y')-1] : array();

		$canc_query = tep_db_query("
		SELECT YEAR(s.affiliate_date) AS y,
		MONTH(s.affiliate_date) AS m,
		SUM(COALESCE(rpd.final_price, 0.00)) AS s,
        SUM(COALESCE(rpd.products_quantity,0)) AS q
		FROM ".TABLE_AFFILIATE_SALES." s 
		LEFT JOIN ".TABLE_RETURNS_PRODUCTS_DATA." rpd ON s.affiliate_orders_id = rpd.order_id
        LEFT JOIN ".TABLE_ORDERS." o ON rpd.order_id = o.orders_id
	    WHERE o.orders_status = 0 
		AND s.affiliate_date >= '".(date('Y')-1)."-01-01'
		AND s.affiliate_date <= '".date('Y-m-d')."'
		GROUP BY YEAR(s.affiliate_date), MONTH(s.affiliate_date)
		");

		while ($canc_row=tep_db_fetch_array($canc_query)) {
			if(!isset($total[$canc_row['y']])) $total[$canc_row['y']] = array();
		    $canceled[$canc_row['y']][$canc_row['m']] = array('sum' => $canc_row['s'], 'qty' => $canc_row['q']);
		}

		$ytd_c = isset($canceled[date('Y')]) ? $canceled[date('Y')] : array();
		$prv_c = isset($canceled[date('Y')-1]) ? $canceled[date('Y')-1] : array();

		$anydata = false;
		$lastdata = 0;
		for($i=1;$i<=12;$i++) {
			if($anydata || isset($prv[$i])) {
				$anydata = true;
				if(isset($prv[$i])) {
					$lastdata = $i;
				}
				$val_prv[$i-1] = isset($prv[$i]) ? $prv[$i]['sum'] : 0;
				$val_retprv[$i-1] = isset($prv_ret[$i]) ? $prv_ret[$i]['ret'] : 0;
				$val_qtyprv[$i-1] = isset($prv_qty[$i]) ? $prv_qty[$i]['qty'] : 0;
				$val_retprv[$i-1]+= isset($prv_c[$i]) ? $prv_c[$i]['qty'] : 0;
			} else {
				$val_retprv[$i-1] = $val_qtyprv[$i-1] = NULL;
			}
		}

		for($i=$lastdata+1;$i<12;$i++) {
			unset($val_prv[$i]);
		}
		
		$anydata = false;
		$lastdata = 0;
		for ($i=1;$i<=12;$i++) {
			if ($anydata || isset($ytd[$i])) {
				$anydata = true;
				if(isset($ytd[$i])) {
					$lastdata = $i;
				}
				
				$val_ytd[$i-1] = isset($ytd[$i]) ? $ytd[$i]['sum'] : 0;
      			$val_ret[$i-1] = isset($ytd_ret[$i]) ? $ytd_ret[$i]['ret'] : 0;
				$val_qty[$i-1] = isset($ytd_qty[$i]) ? $ytd_qty[$i]['qty'] : 0;
				$val_ret[$i-1]+= isset($ytd_c[$i]) ? $ytd_c[$i]['qty'] : 0;
			} else {
      			$val_ret[$i-1] = $val_qty[$i-1] = NULL;
			}
  		}

		for($i=$lastdata+1;$i<12;$i++) {
			unset($val_ytd[$i]);
		}


	$min_val_ret = isset($val_ret)?min($val_ret):0;
	$min_val_retprv = isset($val_retprv)?min($val_retprv):0;
	$max_val_qty = isset($val_qty)?max($val_qty):0;
	$max_val_qtyprv = isset($val_qtyprv)?max($val_qtyprv):0;

$qty_min = min(0, $min_val_ret, $min_val_retprv);
$qty_max = 1+max(1, $max_val_qty, $max_val_qtyprv);
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
  return abs($val)>=1000000 ? '$'.round($val/1000000,1).'M' : abs($val)>=1000 ? '$'.round($val/1000,1).'K' : '$'.round($val,1);
}

$grp = new PlotGroup();

$grp->setPadding(make_num('pad_left',50), make_num('pad_right', 50), make_num('pad_top',NULL), make_num('pad_bottom',NULL));

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
$qty_min=- ceil(-$qty_min / $qty_step) * $qty_step;
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
$grp->axis->left->setLabelNumber($qty_label_count);
$grp->axis->right->setLabelNumber($qty_label_count);

$bw=make_num('bar_width',70)/100;

if (isset($val_qty)) {
    $plot = new BarPlot($val_qty, 2, 2, 0);
    $plot->setBarColor(make_color('qty_color','4040FF'));
    $plot->setBarSize($bw);
    $plot->setYAxis(PLOT_RIGHT);
    $plot->setYMin($qty_min);
    $plot->setYMax($qty_max);
    $grp->add($plot);
}

$plot = new BarPlot($val_qtyprv, 1, 2, 0);
$plot->setBarColor(make_color('qtyprv_color','C0FFC0'));
$plot->setBarSize($bw);
$plot->setYAxis(PLOT_RIGHT);
$plot->setYMin($qty_min);
$plot->setYMax($qty_max);
$grp->add($plot);

if (isset($val_ret)) {
    $plot = new BarPlot($val_ret, 2, 2, 0);
    $plot->setBarColor(make_color('ret_color','FFEEEE'));
    $plot->setBarSize($bw);
    $plot->setYAxis(PLOT_RIGHT);
    $plot->setYMin($qty_min);
    $plot->setYMax($qty_max);
    $grp->add($plot);
}

$plot = new BarPlot($val_retprv, 1, 2, 0);
$plot->setBarColor(make_color('retprv_color','FFFAFA'));
$plot->setBarSize($bw);
$plot->setYAxis(PLOT_RIGHT);
$plot->setYMin($qty_min);
$plot->setYMax($qty_max);
$grp->add($plot);

if ($val_prv) {
    foreach($val_prv as $k=>$v) {
        $xkeys[$k]=$k+.5;
    }
    //for ($i=0;isset($val_prv[$i]);$i++) $xkeys[$i]=$i+.5;
    $plot = new ScatterPlot(array_values($val_prv), array_values($xkeys));
    $plot->link(TRUE,make_color('prv_color','40FFFF'));
    $plot->mark->setSize(make_num('prv_mark',10));
    $plot->mark->setFill(make_color('prv_markcolor','F0F0F0'));
    $plot->setThickness(make_num('prv_thick',5));
    $plot->setYAxis(PLOT_LEFT);
    $plot->setYMin($min);
    $plot->setYMax($max);
    $grp->add($plot);
}

if ($val_ytd) {
    $xkeys= array();
    foreach($val_ytd as $k=>$v) {
        $xkeys[$k]=$k+.5;
    }
    //for ($i=0;isset($val_ytd[$i]);$i++) $xkeys[$i]=$i+.5;
    $plot = new ScatterPlot(array_values($val_ytd), array_values($xkeys));
    $plot->link(TRUE,make_color('ytd_color','40FFFF'));
    $plot->mark->setSize(make_num('ytd_mark',10));
    $plot->mark->setFill(make_color('ytd_markcolor','F0F0F0'));
    $plot->setThickness(make_num('ytd_thick',5));
    $plot->setYAxis(PLOT_LEFT);
    $plot->setYMin($min);
    $plot->setYMax($max);
    $grp->add($plot);
}

$grp->axis->bottom->setLabelText(Array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'));
$grp->axis->left->label->setCallbackFunction('FmtAmount');

set_font($grp->axis->bottom->label,'x_font');
set_font($grp->axis->left->label,'y_font');
set_font($grp->axis->right->label,'y_font');

$graph->border->hide();
$grp->setBackgroundColor(make_color('bg_color','FFFFFF'));
$grp->grid->setBackgroundColor(make_color('bg_plot_color','FFFFFF'));

$graph->add($grp);
$graph->draw();

// # Transpose array ($foo['baz']['bar'] --> $foo['bar']['baz'])
	function a_t($array)  {
    	$r= array();
	    foreach($array as $k1=>$ia) {
    	    foreach($ia as $k2=>$v) {
        	    $r[$k2][$k1]=$v;
        	}
    	}
    	return $r;
	}
?>
