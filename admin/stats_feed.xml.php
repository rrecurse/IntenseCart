<?php 

	require('includes/application_top.php');

	header("Content-Type: ".(isset($_GET['ctype']) ? $_GET['ctype'] : 'text/xml'));
	

	// # report times based on default time zone in php config or override as seen below
	// # default application timezone is UCT set in /includes/application_top.php
	if(date_default_timezone_get() != 'EST' && STORE_TZ == '-5') { 
		date_default_timezone_set('EST');
	}


  function to_time($date) {
    if (!preg_match('/(\d+)-(\d+)-(\d+)/',$date,$dp)) return time();
    return mktime(0,0,1,$dp[2],$dp[3],$dp[1]);
  }
  
  function adjust_date($date) {

	// # daylight savings time check with date('I');
    $time = (date('I') == 1 ? strtotime($date)+(STORE_TZ - 1)*3600 : strtotime($date)+STORE_TZ*3600);

    return date('Y-m-d H:i:s', $time);

  }

  
  function percent_change($prev, $curr) {

	if($prev) (float)$prev;
	if($curr) (float)$curr;

    if($prev == $curr) {
		return '0%';
	}

    if($prev < 0.01) {
		return '-';
	}

	$p = (($curr - $prev) / $prev * 100);

	$percent_change = sprintf("%+.2f%%", $p);

    return $percent_change;
  }


// # usage: $lst = thisweek, lastweek, lastmonth etc
// # $dtag = sales channel, e.g. 'sales'
function pull_ranges($lst, $dtag) { 

    $ranges = preg_split('/,/',$lst);

    if (!sizeof($ranges)) {
		$ranges[] = 'today';
	}

    $rs = array();

	// # daylight savings time check with date('I');
    $time = (date('I') == 1) ? time()+(STORE_TZ - 1)*3600: time()+STORE_TZ*3600;

	foreach ($ranges AS $rline) {

		$pstart = $pfinish='';

		if(!$rtag) $rtag = $dtag;

		list($rtag,$start,$finish) = preg_split('/:/',$rline);

		if (!$start) {
        
			switch ($rtag) {

  		 		case 'today':
					$start = date('Y-m-d 00:00:01');
					$finish = date('Y-m-d 23:59:59');

					// # pstart and pfinish vars are used for percentage difference calc based on period
					$pstart = date('Y-m-d 00:00:01', strtotime('yesterday'));
					$pfinish = date('Y-m-d 23:59:59',strtotime('yesterday'));

		   	  	break;

        		case 'yesterday':
					$start = date('Y-m-d 00:00:01', strtotime('yesterday'));
					$finish = date('Y-m-d 23:59:59',strtotime('yesterday'));

					$pstart = date('Y-m-d 00:00:01', strtotime('yesterday -1 days'));
					$pfinish = date('Y-m-d 23:59:59',strtotime('yesterday -1 days'));

		   	  	break;

        		case 'thisweek':
					$start = date('Y-m-d 00:00:01', strtotime('monday this week - 1 days'));
					$finish = date('Y-m-d 23:59:59');

					$pstart = date('Y-m-d 00:00:01', strtotime('monday last week - 1 days'));
					$pfinish = date('Y-m-d 23:59:59',strtotime('saturday this week - 7 days'));

			  	break;

        		case 'lastweek':
					$start = date('Y-m-d 00:00:01', strtotime('monday last week - 1 days'));
					$finish = date('Y-m-d 23:59:59',strtotime('saturday this week - 7 days'));

					$pstart = date('Y-m-d 00:00:01', strtotime('monday last week - 8 days'));
					$pfinish = date('Y-m-d 23:59:59',strtotime('saturday this week - 14 days'));

			  	break;

        		case 'thismonth':
					$start = date('Y-m-01 00:00:01', strtotime('this month'));
					$finish = date('Y-m-d 23:59:59');

					$pstart = date('Y-m-01 00:00:01', strtotime('first day of last month'));
					$pfinish = date('Y-m-t 23:59:59', strtotime('first day of last month'));

			  	break;
		
        		case 'lastmonth':
					$start = date('Y-m-01 00:00:01', strtotime('first day of last month'));
					$finish = date('Y-m-t 23:59:59', strtotime('first day of last month'));

					$pstart = date('Y-m-01 00:00:01', strtotime('first day of last month - 1 month'));
					$pfinish = date('Y-m-t 23:59:59', strtotime('first day of last month - 1 month'));

			  	break;

				case 'thisyear':
	  				$start = date('Y-01-01 00:00:01', time());
					$finish = date('Y-m-d H:i:s', time());

					$pstart = date('Y-01-01 00:00:01', strtotime('-1 years'));
					$pfinish = date('Y-m-d H:i:s', strtotime('-1 years'));

			  	break;

				case 'lastyear':
					$start = date('Y-01-01 00:00:01', strtotime('-1 years'));
					$finish = date('Y-m-d 23:59:59', strtotime('-1 years'));

					$pstart = date('Y-01-01 00:00:01', strtotime('-2 years'));
					$pfinish = date('Y-m-d 23:59:59', strtotime('-2 years'));

			 	break;
			}
		}

      if (!$start) $start=date('Y-m-d 00:00:01', time());
      if (!$finish) $finish=date('Y-m-d 23:59:59', strtotime('+1 days'));
      if (!$pfinish) $pfinish=$start;
      if (!$pstart) $pstart=date('Y-m-d 00:00:01',to_time($pfinish)+to_time($start)-to_time($finish));


		$rs[] = array('tag' => $rtag,
					  'start' => $start,
					  'finish' => $finish,
					  'pstart' => $pstart,
					  'pfinish' => $pfinish
					 );
	}

	return $rs;
}

?>
<xml>
<?php  if (isset($_GET['traffic'])) { ?>
<traffic>
<?php 

	foreach (pull_ranges($_GET['traffic'],'traffic') AS $range) {

		$range['start'] = (date('Y-m-d', strtotime($range['start'])));
		$range['finish'] = (date('Y-m-d', strtotime($range['finish'])));

		$traffic_query = tep_db_query("SELECT traffic_source,
									   SUM(hit_count) AS hit_count 
									   FROM traffic_stats 
									   WHERE traffic_date BETWEEN '".$range['start']."' AND '".$range['finish']."' 
									   AND (traffic_source LIKE '%google%' 
											OR traffic_source LIKE '%bing%' 
											OR traffic_source LIKE '%yahoo%' 
											OR traffic_source LIKE '%aol%' 
											OR traffic_source LIKE 'retail')
									   GROUP BY traffic_source
									  ");
?>
<<?php echo $range['tag']?>>
<?php 

	while ($stats_row = tep_db_fetch_array($traffic_query)) { 
?>
		<<?php echo $stats_row['traffic_source']?>>
			<count><?php echo $stats_row['hit_count']?></count>
		</<?php echo $stats_row['traffic_source']?>>
<?php 

	}

	//tep_db_free_result($traffic_query);
?>
</<?php echo $range['tag']?>>
<?php } ?>
</traffic>

<?php 

  }

  if (isset($_GET['sales'])) {

?>
<sales>
<?php 
	foreach (pull_ranges($_GET['sales'],'sales') AS $range) {

    $sales_query = tep_db_query("SELECT COUNT(0) AS sales_count, 
								 SUM(COALESCE(ot.value, 0.00)) AS sales_total,
								 IF(a.affiliate_id, 'affiliate', IF(c.customers_group_id > 1, 'vendor', IF(o.orders_source = 'email', 'email', IF(o.orders_source LIKE '%amazon%', 'amazon', 'retail')))) AS sales_type 
								 FROM ".TABLE_ORDERS." o 
								 LEFT JOIN ". TABLE_CUSTOMERS ." c ON c.customers_id = o.customers_id
								 LEFT JOIN ".TABLE_ORDERS_TOTAL." ot ON (ot.orders_id = o.orders_id AND ot.class = 'ot_total') 
								 LEFT JOIN ".TABLE_AFFILIATE_SALES." a ON a.affiliate_orders_id = o.orders_id
								 WHERE o.date_purchased BETWEEN '". $range['start'] ."' AND '". $range['finish'] ."'
								 AND o.orders_status > 0
								 AND o.orders_status != 4 
								 GROUP BY sales_type
								");
?>
<<?php echo $range['tag']?>>
<?php 
	
	$total_ct = 0;
	$total_a = 0;

	while($stats_row = tep_db_fetch_array($sales_query)) {

		//$s_tag = ($stats_row['affiliate_id'] ? 'affiliate' : 'direct');

		$s_tag = $stats_row['sales_type'];
		$total_ct += $stats_row['sales_count'];
		$total_a += $stats_row['sales_total'];
?>
		<<?php echo $s_tag?>>
			<count><?php echo tep_fmt_number($stats_row['sales_count'])?></count>
			<amount><?php echo number_format($stats_row['sales_total'],2)?></amount>
		</<?php echo $s_tag?>>
<?php 

	} // # end while

	tep_db_free_result($sales_query);


   $pr_query = tep_db_query("SELECT COUNT(0) AS sales_count,
									SUM(COALESCE(op.final_price, 0.00)) AS sales_total,
									p.products_class,
									p.products_id
							 FROM ".TABLE_ORDERS." o 
						     LEFT JOIN ".TABLE_ORDERS_PRODUCTS." op ON op.orders_id = o.orders_id 
						     LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id = op.products_id
							 WHERE o.date_purchased BETWEEN '". $range['start'] ."' AND '". $range['finish'] ."'
						     AND o.orders_status > 0
						     AND o.orders_status != 4 
						     GROUP BY p.products_class
						    ");

	while($pr_row = tep_db_fetch_array($pr_query)) {


		$p_tag = $pr_row['products_class'];

		if(!$p_tag) {
			$p_tag = 'product_other';
		}

		echo '<'.$p_tag.'>
		<count>'. tep_fmt_number($pr_row['sales_count']) .'</count>
		<amount>'. number_format($pr_row['sales_total'],2).'</amount>
		</'. $p_tag.'>';

	}

	tep_db_free_result($pr_query);


	$diff_query = tep_db_query("SELECT COUNT(0) AS sales_count,
								SUM(COALESCE(ot.value, 0.00)) AS sales_total 
								FROM ".TABLE_ORDERS." o 
								LEFT JOIN ".TABLE_ORDERS_TOTAL." ot ON ot.orders_id = o.orders_id AND ot.class='ot_total' 
								LEFT JOIN ".TABLE_AFFILIATE_SALES." a ON a.affiliate_orders_id=o.orders_id 
								WHERE o.date_purchased BETWEEN '". $range['pstart'] ."' AND '". $range['pfinish'] ."'
								AND o.orders_status > 0 
						    	AND o.orders_status != 4 
							  ");

	$diff_row = tep_db_fetch_array($diff_query);

?>
<total>
	<count><?php echo tep_fmt_number($total_ct)?></count>
	<amount><?php echo number_format($total_a,2)?></amount>
</total>
<previous>
	<count><?php echo tep_fmt_number($diff_row['sales_count'])?></count>
	<amount><?php echo number_format($diff_row['sales_total'],2)?></amount>
</previous>
<percent_change><?php echo percent_change($diff_row['sales_total'], $total_a);?></percent_change>
</<?php echo $range['tag']?>>

<?php 
    } // # end foreach
?>
</sales>
<?php 

}

	tep_db_free_result($diff_query);

  if (isset($_GET['ppc'])) {
?>
<ppc>

<?php

	$ppcset = tep_module('ppc_ads');
	$ppcmods = $ppcset->getModules();
	$ppc_channels = preg_split('/,/',$_GET['ppc_channels']);

	foreach (pull_ranges($_GET['ppc'],'ppc') AS $range) {
		$ppc_nocache = isset($_GET['ppc_nocache']) || ($range['finish']>date('Y-m-d'));
		$ppc_stats = array();

		if(!$ppc_nocache) {

			$ppc_cache_query = tep_db_query("SELECT * FROM ppc_stats WHERE start_date='".$range['start']."' AND finish_date='".$range['finish']."'");

			while ($ppc_cache = tep_db_fetch_array($ppc_cache_query)) {

          		$ppc_stats[$ppc_cache['ppc_source']] = array('clicks' => $ppc_cache['ppc_clicks'],
															 'cost' => $ppc_cache['ppc_cost'],
															 'convs' => $ppc_cache['ppc_conversions'],
															 'cache' => 1);
			}

		}

		foreach ($ppcmods AS $mkey => $mod) if (!isset($ppc_stats[$mkey])) {
        	$stats_row = $ppc_stats[$mkey] = $mod->getStats($range['start'],$range['finish']);

			if(isset($stats_row)) {
				tep_db_query("INSERT IGNORE INTO ppc_stats 
							   SET start_date = '".$range['start']."',
							   finish_date = '".$range['finish']."',
							   ppc_source = '". $mkey ."',
							   ppc_clicks = '".$stats_row['clicks']."',
							   ppc_cost = '".$stats_row['cost']."',
							   ppc_conversions = '".$stats_row['convs']."',
							   ppc_impressions = '".$stats_row['imprs']."'
							 ");
			}
      }

      $ppc_rows = array();

      $ppc_rows['total'] = array('clicks'=>0,'convs'=>0,'cost'=>0.00,'imprs'=>0);

      foreach ($ppc_stats AS $scope=>$stats_row) {

        foreach ($ppc_rows['total'] AS $key=>$val) $ppc_rows['total'][$key]+=$stats_row[$key];

        if ($ppc_channels && !in_array($scope,$ppc_channels)) {

			if (!isset($ppc_rows['other'])) $ppc_rows['other']=$stats_row;
		else {
			foreach ($ppc_rows['other'] AS $key=>$val) $ppc_rows['other'][$key]+=$stats_row[$key];
		}
	} else $ppc_rows[$scope]=&$ppc_stats[$scope];
      }
?>
<<?php echo $range['tag']?>>
<?php foreach ($ppc_rows AS $scope=>$stats_row) { ?>

<<?php echo $scope?>>

<?php foreach ($stats_row AS $s_key=>$s_val) { ?>

<<?php echo $s_key?>><?php echo $s_val?></<?php echo $s_key?>>

<?php } ?>
</<?php echo $scope?>>
<?php } ?>
</<?php echo $range['tag']?>>
<?php } ?>
</ppc>
<?php 
}
 ?>
</xml>