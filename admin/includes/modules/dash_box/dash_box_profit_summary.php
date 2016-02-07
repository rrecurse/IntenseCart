<?php
class dash_box_profit_summary {

		var $table_cols = 2;
	 	var $table_rows = 1;
		var $title = 'Profit &amp; Loss Summary';

	function prod_sum($from,$to) {

		$from = date('Y-m-d 00:00:01', strtotime($from));
		$to = date('Y-m-d 23:59:59', strtotime($to));


		$cost_qry = tep_db_query("SELECT SUM(COALESCE((op.cost_price * op.products_quantity), 0.00)) AS cost
								  FROM orders_products op
								  JOIN orders o ON op.orders_id = o.orders_id
								  WHERE o.date_purchased
								  BETWEEN '".$from."'
								  AND '".$to."'
								  AND o.orders_status > 0
								");

		$total = 0;

		while($row = mysql_fetch_array($cost_qry)) {
			$total = $total + $row['cost'];
		}

		return $total;
	}


	function affl_sum($from,$to) {

		$qry = tep_db_query("SELECT SUM(COALESCE(affs.affiliate_payment, 0.00)) AS s 
							 FROM ".TABLE_AFFILIATE_SALES." affs
							 WHERE affs.affiliate_date 
							 BETWEEN '".$from."'
							 AND '".$to."'
							");

		$row = tep_db_fetch_array($qry);

		return $row['s'];
	}


	function ads_sum($from,$to) {

		$ads_qry = tep_db_query("SELECT SUM(COALESCE(mc.acc_cost, 0.00)) AS ads
								 FROM maintenance_cost mc
								 WHERE mc.acc_month
								 BETWEEN ' " . $from . "'
								 AND '". $to ."'
								 GROUP BY mc.acc_month
								");

		$total = 0;

		while($row=mysql_fetch_array($ads_qry)) {
			$total = $total + $row['ads'];
		}

		return $total;
	}


	function ret_sum($from,$to) {

		$ret_qry = tep_db_query("SELECT (SUM(COALESCE(rpd.refund_amount, 0.00)) - SUM(COALESCE(rpd.exchange_amount, 0.00))) AS ret, 
										rp.date_purchased
								 FROM returned_products rp 
								 LEFT JOIN returns_products_data rpd ON rpd.returns_id = rp.returns_id 
								 LEFT JOIN return_reasons s ON (s.return_reason_id = rp.returns_reason AND s.language_id = '1')
								 LEFT JOIN returns_status rs ON rs.returns_status_id = rp.returns_status
								 WHERE rp.date_purchased BETWEEN '".$from."' AND '".$to."'
								 AND rp.returns_status = 4
								 GROUP BY s.language_id
								");

		$total = 0;

		while($row = mysql_fetch_array($ret_qry)) {
			$total = $total + $row['ret'];
		}

		return $total;
	} 


/*

	function ppc_sum($from,$to) {

		$modset=tep_module('ppc_ads');

		foreach ($modset->getModules() AS $key=>$m) {
			$st = $m->getStats($from,$to);
			$a+=$st['cost'];
		}

		return $a;
	}
*/

	function gross_sum($from,$to) {
		//$time=gmdate(mktime())+STORE_TZ*3600;
		//$from = date('Y-m-d H:i:s',strtotime($from));
		//$to = date('Y-m-d H:i:s',strtotime($to)+86400);

		$qry = tep_db_query("SELECT SUM(COALESCE(ot.value, 0.00)) AS s 
							 FROM ".TABLE_ORDERS." o 
							 LEFT JOIN ".TABLE_ORDERS_TOTAL." ot ON ot.orders_id = o.orders_id AND ot.class = 'ot_total' 
							 LEFT JOIN ".TABLE_AFFILIATE_SALES." a ON a.affiliate_orders_id = o.orders_id
							 WHERE o.date_purchased
							 BETWEEN '".$from."' AND '".$to."'
							 AND o.orders_status > 0 
							 # added new status - Hold for Verification - do not count
							 AND o.orders_status != 4
							");

		$row = tep_db_fetch_array($qry);

		return $row['s'];
	}

	function calc_profit(&$sm) {
		$p = 0;
		foreach ($sm AS $k=>$v) if ($k=='gross') $p+=$v; else $p-=$v;
		$sm['profit']=$p;
	}

	function render() {

		$ytd = array();
		$lytd = array();
		$ly = time() - (86400 * 365) + STORE_TZ * 3600;

		$ytd['prod'] = $this->prod_sum(date('Y-01-01 00:00:01'),date('Y-m-d 23:59:59'));
		$lytd['prod'] = $this->prod_sum(date('Y-01-01 00:00:01',$ly),date('Y-m-d 23:59:59',$ly));

		$ytd['ret'] = $this->ret_sum(date('Y-01-01 00:00:01'),date('Y-m-d 23:59:59'));
		$lytd['ret'] = $this->ret_sum(date('Y-01-01 00:00:01',$ly),date('Y-m-d 23:59:59',$ly));

		$ytd['affl'] = $this->affl_sum(date('Y-01-01 00:00:01'),date('Y-m-d 23:59:59'));
		$lytd['affl'] = $this->affl_sum(date('Y-01-01 00:00:01',$ly),date('Y-m-d 23:59:59',$ly));

		$ytd['ads'] = $this->ads_sum(date('Y-01-01 00:00:01'),date('Y-m-d 23:59:59'));
		$lytd['ads'] = $this->ads_sum(date('Y-01-01 00:00:01',$ly),date('Y-m-d 23:59:59',$ly));

		$ytd['gross'] = $this->gross_sum(date('Y-01-01 00:00:01'),date('Y-m-d 23:59:59'));
		$lytd['gross'] = $this->gross_sum(date('Y-01-01 00:00:01',$ly),date('Y-m-d 23:59:59',$ly));

		$this->calc_profit($ytd);
		$this->calc_profit($lytd);

		$color = array('prod'=>'007FFF','ret'=>'FF0000','ads'=>'FF00FF','affl'=>'FF7F00','gross'=>'007F00','profit'=>'11911C');
		$explode = array('ads'=>10,'prod'=>10,'affl'=>10,'profit'=>10,'ret'=>10);

		$val = array();
		$pdata = array();
		$sm = 0;
		foreach (Array('profit','affl','ret','prod','ads') AS $s) $sm+=$val[$s]=$ytd[$s];

		foreach ($val AS $s=>$v) {
			if ($sm) $v=abs(floor($v*100/$sm+.5));
			if ($v>0) $pdata[]=$color[$s].':'.$val[$s].(isset($explode[$s])?':'.$explode[$s]:'');
		}
?>
<div style="padding:0 0 5px 0">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="50%" valign="top" style="padding:0 5px 0 0">
		   <table width="100%" border="0" cellspacing="0" cellpadding="0">
			   <tr><td colspan="4" style="background-color:#6295FD; height:1px;"></td></tr>
			   <tr><td colspan="4" style="background-color:#FFFFFF; height:1px;"></td></tr>
				<tr>
					<td colspan="3" class="dashbox_bluetop">&nbsp; Profit &amp; Loss Summary:</td> <td align="right" style="padding:3px 0 0 0; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<div align=justify><b>Profit &amp; Loss Summary</a></b> is a bird\'s-eye view of your overall cost and overhead versus profitability. You must populate your Cost and Spend fields to add these financial\'s to your overall stats. To populate the Advertising Spend fields, click the segment name below. For product Cost, you must edit each product at the Product Editing level.</div>')" onMouseout="hideddrivetip()"></div>
			   </tr>
				<tr><td colspan="4" style="background-color:#FFFFFF; height:1px;"></td></tr>
			   <tr>
					<td colspan="4">
					     <table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr style="background-color:#DEEAF8; height:20px;">
								<td width="119" style="color:#0B2D86; font-size:12px;">&nbsp; <b>Year to Date</b></td>
								<td align="right" style="opacity:0.75">Last YTD &nbsp;</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="4" style="background-color:#FFFFFF; height:1px;"></td>
				</tr>
				<tr bgcolor="#EBF1F5">
<td width="14" class="tableinfo_right-btm" style="color:#0B2D86; padding:0 10px 0 5px;">
	<div style="width:12px; height:10px; background-color:#<?php echo $color['gross']?>; border:1px solid #000000;">&nbsp;</div>
</td>
<td class="tableinfo_right-btm" style="padding: 0 10px;">Total GR. Sales:</td>
<td class="tableinfo_right-btm" style="color:#<?php echo $color['gross']?>;" align="right">$<?php echo number_format($ytd['gross'],2)?>  &nbsp;</td>
<td class="tableinfo_right-end" style="color:#999; opacity:0.75;" align="right">$<?php echo number_format($lytd['gross'],2)?>  &nbsp;</td>
</tr>
	<tr>
<td class="tableinfo_right-btm" width="14" style="padding:0 10px 0 5px; color:#0B2D86"><div style="width:12px; height:10px; background-color:#<?php echo $color['ret']?>; border:1px solid #000000;">&nbsp;</div></td>
<td class="tableinfo_right-btm" style="padding: 0 10px;"><a href="returns_report.php">Total Refunds</a>:</td>
<td class="tableinfo_right-btm" style="color:#<?php echo $color['ret']?>;" align="right">($<?php echo number_format($ytd['ret'],2)?>)  &nbsp; <?php echo $see ?></td>
<td class="tableinfo_right-end" style="color:#<?php echo $color['ret']?>; opacity:0.55;" align="right">($<?php echo number_format($lytd['ret'],2)?>)  &nbsp;</td>
</tr>
	<tr bgcolor="#EBF1F5">
<td class="tableinfo_right-btm" width="14" style="padding:0 10px 0 5px; color:#0B2D86;"><div style="width:12px; height:10px; background-color:#<?php echo $color['ads']?>; border:1px solid #000000;">&nbsp;</div></td>
<td class="tableinfo_right-btm" style="padding: 0 10px;"><a href="maintenance_admin.php">Advert. Spend:</a></td>
<td class="tableinfo_right-btm" style="color:#<?php echo $color['ads']?>;" align="right" bgcolor="#EBF1F5">($<?php echo number_format($ytd['ads'],2)?>)  &nbsp;</td>
<td class="tableinfo_right-end" style="color:#<?php echo $color['ads']?>; opacity:0.55;" align="right">($<?php echo number_format($lytd['ads'],2)?>)  &nbsp;</td>
</tr>
	<tr>
<td class="tableinfo_right-btm" width="14" style="padding:0 10px 0 5px; color:#0B2D86"><div style="width:12px; height:10px; background-color:#<?php echo $color['prod']?>; border:1px solid #000000;">&nbsp;</div></td>
<td class="tableinfo_right-btm" style="padding: 0 10px;">Product Cost:</td>
<td class="tableinfo_right-btm" style="color:#<?php echo $color['prod']?>;" align="right">($<?php echo number_format($ytd['prod'],2)?>)  &nbsp; <?php echo $see ?></td>
<td class="tableinfo_right-end" style="color:#<?php echo $color['prod']?>; opacity:0.55;" align="right">($<?php echo number_format($lytd['prod'],2)?>)  &nbsp;</td>
</tr>
	<tr bgcolor="#EBF1F5">
<td class="tableinfo_right-btm" width="14" style="padding:0 10px 0 5px; color:#0B2D86"><div style="width:12px; height:10px; background-color:#<?php echo $color['affl']?>; border:1px solid #000000;">&nbsp;</div></td>
<td class="tableinfo_right-btm" style="padding: 0 10px;"><a href="affiliate_affiliates.php">Affiliate Comm:</a></td>
<td class="tableinfo_right-btm" style="color:#<?php echo $color['affl']?>;" align="right">($<?php echo number_format($ytd['affl'],2)?>)  &nbsp;</td>
<td class="tableinfo_right-end" style="color:#<?php echo $color['affl']?>; opacity:0.55;" align="right">($<?php echo number_format($lytd['affl'],2)?>)  &nbsp;</td>
</tr>
	<tr>
<td class="tableinfo_right-btm" width="14" style="padding:0 10px 0 5px; color:#0B2D86;"><div style="width:12px; height:10px; background-color:#<?php echo $color['profit']?>; border:1px solid #000000;">&nbsp;</div></td>
<td class="tableinfo_right-btm" style="padding: 0 10px;">GR. Profit (<span style="font-size:9px">ROI</span>):</td>
<td class="tableinfo_right-btm" style="color:#<?php echo $color['profit']?>;" align="right">$<?php echo number_format($ytd['profit'],2)?>  &nbsp;</td>
<td class="tableinfo_right-end" style="color:#<?php echo $color['profit']?>; opacity:0.55;" align="right">$<?php echo number_format($lytd['profit'],2)?>  &nbsp;</td>
</tr>
     </table>

	 <div style="margin:0 auto; text-align:center; padding:10px 0 0 0">
<div style="display:inline-block; width:20px">
<img src="data:image/jpg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAsICAgJCAwJCQwRCwoLERQPDAwPFBcSEhISEhcYExQUFBQTGBYaGxwbGhYiIiQkIiIuLi4uLjAwMDAwMDAwMDD/2wBDAQwMDBAQEBcRERcYFBMUGB4bHBwbHiQeHh8eHiQpIyAgICAjKSYoJCQkKCYrKykpKyswMDAwMDAwMDAwMDAwMDD/wAARCAAUABQDAREAAhEBAxEB/8QAGQAAAgMBAAAAAAAAAAAAAAAAAAUBAgYD/8QAJxAAAgECBgEDBQAAAAAAAAAAAQMCBBEABRITITEiBhRRIyRSYXP/xAAaAQACAwEBAAAAAAAAAAAAAAAEBQABAgMG/8QAJhEAAQIFAgcBAQAAAAAAAAAAAQACAxExUXESIQQiM2GhsdFBgf/aAAwDAQACEQMRAD8A3nvW0lGkqOiJYwFbATPlxHJMr3F+e8FiGHEzsKYQpiFukD9JrlMKyu21VAVeLlQ1AyjePdv1fC9sZry4Nq2qPhsm9oNCjJ6p1XQxc+xYZTiTEaR4yMRxc/GOgUjsDHloostm1WFnLV7hnFrWeR4M/uOCRx3hizZsQ2A9JY7dzMn2nOZ1K4iuNwdpEtXI/Md/GPO8L1o+U5hDmh5Xf0s2LsmWyPUpttb+ksMRRY4vqn+elR6WqrEJg+YW6bpWMVS09s8TJZPeCAZtJlSV8XQJEiN72RTQqHuqlzqZ2W3b4gnkWB8vpYpwaJHSNx3+rTS6Z5jseynLxVNokt90yBnHUYxgkDn4G1iP0hxGkefqoaiJknwv/9k=" width="20" height="20" alt="" />
</div>
<div style="display:inline-block; height:20px; vertical-align:top"><a href="stats_sales.php?&by=name" style="line-height:20px;">view full report</a></div>
	</div>
                        

</td>
<td valign="top" style="padding:0 5px 0 0">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr><td colspan="2" style="background-color:#6295FD; height:1px;"></td></tr>
   <tr><td colspan="2" style="background-color:#FFFFFF; height:1px;"></td></tr>
   <tr>
<td class="dashbox_bluetop">&nbsp; Gross Revenue Averages:</td>
   </tr>
<tr><td colspan="2" style="background-color:#FFFFFF; height:1px;"></td></tr>
	<tr style="background-color:#DEEAF8; height:20px;">
<td style="color:#0B2D86; font-size:12px;">&nbsp;<b>Year to Date</b></td>
</tr>
	<tr style="background-color:#FFFFFF; height:1px;">
<td colspan="2"></td>
</tr>
   <tr>
<td align="center" style="padding:10px 0;">
<img id="profitChartImage" src="pie_chart.php?width=700&height=280&pwidth=60&pheight=60&thickness=11&start_angle=240&label=14&bgcolor=F0F5FB&data=<?php echo join(',',$pdata)?>" width="99%" style="max-height:280px">

<script>

jQuery(document).ready(function() {

	var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;

	jQuery(window).resize(function() {

		width = (window.innerWidth > 0) ? window.innerWidth : screen.width;

		if(width > 1090) { 

			jQuery('#profitChartImage').attr("src", "pie_chart.php?width=700&height=280&pwidth=60&pheight=60&thickness=11&start_angle=240&label=14&bgcolor=F0F5FB&data=<?php echo join(',',$pdata)?>");

		} else {

			jQuery('#profitChartImage').attr("src", "pie_chart.php?width=393&height=170&pwidth=60&pheight=60&thickness=10&start_angle=240&label=10&bgcolor=F0F5FB&data=<?php echo join(',',$pdata)?>");

		}
		
	});

});
</script>

</td>
</tr>
  </table>



</td></tr>
</table>
</div>
<?
  }
}
?> 
