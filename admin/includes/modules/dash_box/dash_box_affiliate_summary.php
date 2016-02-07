<?php
class dash_box_affiliate_summary {

		var $table_cols=2;
	 	var $table_rows=1;
		var $title="Affiliate Summary";

function _affStats($st,$end) {

	$rs = IXdb::read("SELECT COUNT(s.affiliate_id) as aff_salescount, 
						SUM(s.affiliate_value) AS aff_sales, 
						SUM(s.affiliate_payment) AS aff_comm
					  FROM " . TABLE_AFFILIATE_SALES . " s 
					  LEFT JOIN " . TABLE_ORDERS . " o ON (s.affiliate_orders_id = o.orders_id) 
					  WHERE o.orders_status > 1 
					  AND s.affiliate_date >= '".$st."' 
					  AND s.affiliate_date <= '".$end."'
					  ");
	$rs['aff_newaffs'] = IXdb::read("SELECT COUNT(DISTINCT o.customers_id) as ct 
									  FROM " . TABLE_AFFILIATE_SALES. " s 
									  LEFT JOIN orders o ON (s.affiliate_orders_id=o.orders_id) 
									  WHERE affiliate_date >= '$st' 
									  AND affiliate_date <= '$end'",NULL,'ct');

    $rs['aff_clicks']=IXdb::read("SELECT COUNT(*) AS ct 
								  FROM " . TABLE_AFFILIATE_CLICKTHROUGHS. " 
								  WHERE affiliate_clientdate >= '$st' 
								  AND affiliate_clientdate <= '$end'",NULL,'ct');

    if($rs['aff_clicks']) {
		$rs['aff_convs'] = $rs['aff_salescount'] / $rs['aff_clicks'];
	}
    if($rs['aff_salescount']) {
		$rs['aff_salesavg'] = $rs['aff_sales']/$rs['aff_salescount'];
	}
    return $rs;
  
}

function render() {

  // # delete clickthroughs
  if (AFFILIATE_DELETE_CLICKTHROUGHS != 'false' && is_numeric(AFFILIATE_DELETE_CLICKTHROUGHS)) {
    $time = mktime (1,1,1,date("m"),date("d") - AFFILIATE_DELETE_CLICKTHROUGHS, date("Y"));
    $time = date("Y-m-d", $time);
    tep_db_query("delete from " . TABLE_AFFILIATE_CLICKTHROUGHS . " where affiliate_clientdate < '". $time . "'");
  }
  // delete old records from affiliate_banner_history
  if (AFFILIATE_DELETE_AFFILIATE_BANNER_HISTORY != 'false' && is_numeric(AFFILIATE_DELETE_AFFILIATE_BANNER_HISTORY)) {
    $time = mktime (1,1,1,date("m"),date("d") - AFFILIATE_DELETE_AFFILIATE_BANNER_HISTORY, date("Y"));
    $time = date("Y-m-d", $time);
    tep_db_query("delete from " . TABLE_AFFILIATE_BANNERS_HISTORY . " where affiliate_banners_history_date < '". $time . "'");
  }

  $ly = date('Y', strtotime('-1 year'));
  
  $ytd = $this->_affStats(date('Y-01-01'),date('Y-m-d h:i:s'));
  $lytd = $this->_affStats("$ly-01-01",$ly.date('-m-d h:i:s'));

  	$val = array();
	$pdata = array();
	$sm=0;
	foreach ($val AS $s => $v) {
	  if ($sm) $v=abs(floor($v*100/$sm+.5));
	  if ($v>0) $pdata[]=$color[$s].':'.$val[$s].(isset($explode[$s])?':'.$explode[$s]:'');
	}
?>
<div style="padding:0 0 5px 0">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr><td width="50%" valign="top" style="padding:0 5px 0 0">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
   <tr><td colspan="2" style="background-color:#6295FD; height:1px;"></td></tr>
   <tr><td colspan="2" style="background-color:#FFFFFF; height:1px;"></td></tr>
<tr>
<td class="dashbox_bluetop">&nbsp; Affiliate Performance Summary:</td> 
<td align="right" style="padding:3px 0 0 0; background-color:#6295FD;"><div class="helpicon" onmouseover="ddrivetip('Help')" onMouseout="hideddrivetip()"></div>
   </tr>
<tr><td colspan="2" style="background-color:#FFFFFF; height:1px;"></td></tr>
   <tr><td colspan="2">
     <table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr style="background-color:#DEEAF8; height:20px;">
<td style="color:#0B2D86; font-size:12px;">&nbsp; <b>Year to Date</b></td>
<td align="center"></td>
<td align="right" style="color:#8197C6">Last YTD &nbsp;</td>
</tr>
	<tr style="background-color:#FFFFFF; height:1px;">
<td colspan="2"></td>
</tr>
	<tr>
      <td class="tableinfo_right-btm" style="padding: 0 4px 0 5px;" bgcolor="#EBF1F5">New Affiliates:</td>
	  <td class="tableinfo_right-btm" align="right" bgcolor="#EBF1F5"><?=$ytd['aff_newaffs']?>
	    &nbsp;</td>
	  <td class="tableinfo_right-end" align="right" bgcolor="#EBF1F5"><?=$lytd['aff_newaffs']?>
	    &nbsp;</td>
	  </tr>
	<tr>
      <td class="tableinfo_right-btm" style="padding: 0 4px 0 5px;">Affiliate Referrals:</td>
	  <td class="tableinfo_right-btm" align="right"><?=$ytd['aff_clicks']?> &nbsp;</td>
	  <td class="tableinfo_right-end" align="right"><?=$lytd['aff_clicks']?>
	    &nbsp;</td>
	  </tr>
	<tr>
      <td class="tableinfo_right-btm" bgcolor="#EBF1F5" style="padding: 0 4px 0 5px;">Total Sales:</td>
	  <td class="tableinfo_right-btm" align="right" bgcolor="#EBF1F5"><?=$ytd['aff_salescount']?> &nbsp;</td>
	  <td class="tableinfo_right-end" align="right" bgcolor="#EBF1F5"><?=$lytd['aff_salescount']?>
	    &nbsp;</td>
	  </tr>
	<tr>
      <td class="tableinfo_right-btm" style="padding: 0 4px 0 5px;">Conversion %</td>
	  <td class="tableinfo_right-btm" align="right"><?=isset($ytd['aff_convs'])?number_format($ytd['aff_convs']*100,2).'%':'n/a'?>
	    &nbsp;</td>
	  <td class="tableinfo_right-end" align="right"><?=isset($lytd['aff_convs'])?number_format($lytd['aff_convs']*100,2).'%':'n/a'?>
	    &nbsp;</td>
	  </tr>
	<tr>
      <td class="tableinfo_right-btm"  bgcolor="#EBF1F5" style="padding: 0 4px 0 5px;">Affiliate Sales:</td>
	  <td class="tableinfo_right-btm" align="right" bgcolor="#EBF1F5">$<?=tep_fmt_number($ytd['aff_sales'],2)?>
	    &nbsp;</td>
	  <td class="tableinfo_right-end" align="right" bgcolor="#EBF1F5">$<?=tep_fmt_number($lytd['aff_sales'],2)?>
	    &nbsp;</td>
	  </tr>
	<tr>
      <td class="tableinfo_right-btm" style="padding: 0 4px 0 5px;">Avg. Affiliate Sale:</td>
	  <td class="tableinfo_right-btm" align="right">
	      <?=isset($ytd['aff_salesavg'])?sprintf('$%.2f',$ytd['aff_salesavg']):'n/a'?> &nbsp;</td>
	  <td class="tableinfo_right-end" align="right"><?=isset($lytd['aff_salesavg'])?sprintf('$%.2f',$lytd['aff_salesavg']):'n/a'?> &nbsp;</td>
	  </tr>
	
	<tr>
      <td class="tableinfo_right-btm"  style="padding: 0 4px 0 5px;" bgcolor="#EBF1F5">Affiliate Comm.:</td>
      <td class="tableinfo_right-btm" align="right" bgcolor="#EBF1F5">$ <?=tep_fmt_number($ytd['aff_comm'],2)?>  &nbsp;</td>
<td class="tableinfo_right-end" align="right" bgcolor="#EBF1F5">$ <?=tep_fmt_number($lytd['aff_comm'],2)?>  &nbsp;</td>
</tr>
     </table>
	<table border="0" align="center" cellpadding="0" cellspacing="0" style="padding-top:8px;">
                        <tr>
                          <td><img src="data:image/jpg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAsICAgJCAwJCQwRCwoLERQPDAwPFBcSEhISEhcYExQUFBQTGBYaGxwbGhYiIiQkIiIuLi4uLjAwMDAwMDAwMDD/2wBDAQwMDBAQEBcRERcYFBMUGB4bHBwbHiQeHh8eHiQpIyAgICAjKSYoJCQkKCYrKykpKyswMDAwMDAwMDAwMDAwMDD/wAARCAAUABQDAREAAhEBAxEB/8QAGQAAAgMBAAAAAAAAAAAAAAAAAAUBAgYD/8QAJxAAAgECBgEDBQAAAAAAAAAAAQMCBBEABRITITEiBhRRIyRSYXP/xAAaAQACAwEBAAAAAAAAAAAAAAAEBQABAgMG/8QAJhEAAQIFAgcBAQAAAAAAAAAAAQACAxExUXESIQQiM2GhsdFBgf/aAAwDAQACEQMRAD8A3nvW0lGkqOiJYwFbATPlxHJMr3F+e8FiGHEzsKYQpiFukD9JrlMKyu21VAVeLlQ1AyjePdv1fC9sZry4Nq2qPhsm9oNCjJ6p1XQxc+xYZTiTEaR4yMRxc/GOgUjsDHloostm1WFnLV7hnFrWeR4M/uOCRx3hizZsQ2A9JY7dzMn2nOZ1K4iuNwdpEtXI/Md/GPO8L1o+U5hDmh5Xf0s2LsmWyPUpttb+ksMRRY4vqn+elR6WqrEJg+YW6bpWMVS09s8TJZPeCAZtJlSV8XQJEiN72RTQqHuqlzqZ2W3b4gnkWB8vpYpwaJHSNx3+rTS6Z5jseynLxVNokt90yBnHUYxgkDn4G1iP0hxGkefqoaiJknwv/9k=" width="20" height="20" alt="" /></td>
                          <td style="padding-left:6px;"><a href="affiliate_summary.php">view full report</a></td>
                        </tr>
              </table>
   </td>
</tr>
   </table>
</td>
<td valign="top" width="50%" style="padding:0 5px 0 0">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr><td colspan="2" style="background-color:#6295FD; height:1px;"></td></tr>
   <tr><td colspan="2" style="background-color:#FFFFFF; height:1px;"></td></tr>
   <tr>
<td class="dashbox_bluetop">&nbsp; Affiliate Performance Averages:</td>
   </tr>
<tr><td colspan="2" style="background-color:#FFFFFF; height:1px;"></td></tr>
	<tr style="background-color:#DEEAF8; height:20px;">
<td style="color:#0B2D86; font-size:12px;">&nbsp;<b>Year to Date</b></td>
</tr>
	<tr style="background-color:#FFFFFF; height:1px;">
<td colspan="2"></td>
</tr>
   <tr>
<td align="center" style="padding:5px;">
	<img id="affChartImage" src="affpulse.php?width=700&height=300&qtyprv_color=6295FD-1&qty_color=85B761-1&ret_color=BED9AA-1&retprv_color=BED9AA-1&ytd_color=11911C-25&ytd_markcolor=E4E4E4-50&ytd_mark=9&ytd_thick=4&prv_color=9FB9D6-25&prv_markcolor=E4E4E4-50&prv_mark=8&prv_thick=4&bg_color=F4F4F4&bg_plot_color=FFFFFF&x_font=9-0B2D86&y_font=9-0B861D&pad_left=40&pad_top=10&pad_bottom=20&pad_right=40&bar_width=100" width="99%" alt="">

<script>

jQuery(document).ready(function() {

	var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;

	jQuery(window).resize(function() {

		width = (window.innerWidth > 0) ? window.innerWidth : screen.width;

		if(width > 1090) { 


			jQuery('#affChartImage').attr("src", "affpulse.php?width=700&height=300&qtyprv_color=6295FD-1&qty_color=85B761-1&ret_color=BED9AA-1&retprv_color=BED9AA-1&ytd_color=11911C-25&ytd_markcolor=E4E4E4-50&ytd_mark=9&ytd_thick=4&prv_color=9FB9D6-25&prv_markcolor=E4E4E4-50&prv_mark=8&prv_thick=4&bg_color=F4F4F4&bg_plot_color=FFFFFF&x_font=9-0B2D86&y_font=9-0B861D&pad_left=40&pad_top=10&pad_bottom=20&pad_right=40&bar_width=100");


		} else {

			jQuery('#affChartImage').attr("src", "affpulse.php?width=390&height=185&qtyprv_color=6295FD-1&qty_color=85B761-1&ret_color=BED9AA-1&retprv_color=BED9AA-1&ytd_color=11911C-25&ytd_markcolor=E4E4E4-50&ytd_mark=8&ytd_thick=4&prv_color=9FB9D6-25&prv_markcolor=E4E4E4-50&prv_mark=8&prv_thick=4&bg_color=F4F4F4&bg_plot_color=FFFFFF&x_font=7-0B2D86&y_font=7-0B861D&pad_left=28&pad_top=10&pad_bottom=20&pad_right=20&bar_width=85");


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

	<script language="javascript">
	<!--
<?php
	include_once(DIR_FS_DASH_BOX_JS."main_dash.js");
	include_once(DIR_FS_DASH_BOX_JS."ext_dash.js");
?>
	// -->
	</script>
<?php
  }
}
?>

