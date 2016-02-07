<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	if(isset($_GET['show']) && $_GET['show'] == 'all') {
		define('MAX_DISPLAY_SEARCH_RESULTS', '1000');
	}

	require('includes/application_top.php');

	require(DIR_WS_CLASSES . 'currencies.php');
	$currencies = new currencies();

	$date_from = (!empty($_GET['date_from'])) ? date('m/d/Y', strtotime($_GET['date_from'])) : date('01/01/Y');
	$date_to = (!empty($_GET['date_to'])) ? date('m/d/Y', strtotime($_GET['date_to'])) : date('m/d/Y');

  	$channel = (isset($_GET['channel']) ? $_GET['channel'] : '');

		// # Channel Source

        switch($channel) {

          // # Amazon
          case 'amazon':
            $channelFilter = " AND (o.orders_source = 'dbfeed_amazon_us' OR o.orders_source LIKE '%amazon%' OR o.customers_name LIKE 'Amazon%') ";
            break;

          // # eBay
          case 'ebay':
            $channelFilter = " AND o.orders_source LIKE '%ebay%' ";
            break;

          // # E-Mail
          case 'email':
            $channelFilter = " AND o.orders_source LIKE 'email%' ";
            break;

         // # Retail sales
          case 'retail':
            $channelFilter = " AND o.customers_name NOT LIKE 'Amazon%' AND o.orders_source != 'vendor' AND o.orders_source NOT LIKE '%amazon%' ";
            break;

         // # Vendor sales
          case 'vendor':
            $channelFilter = " AND o.orders_source LIKE 'vendor' ";
            break;

         // # all
          case 'default':
            $channelFilter = '';
            break;

        }


	$sort = $_GET['sortby'];

	switch($sort) {

		case 'sortbynameASC':
			$sortby = 'pd.products_name ASC, p.products_model';
		break;

		case 'sortbynameDESC':
			$sortby = 'pd.products_name DESC, p.products_model';
		break;

		case 'sortbymodelASC':
			$sortby = 'p.products_model ASC, pd.products_name';
		break;

		case 'sortbymodelDESC':
			$sortby = 'p.products_model DESC, pd.products_name';
		break;

		case 'sortbyqtyASC':
			$sortby = 'products_ordered ASC, pd.products_name';
		break;

		case 'sortbytotalASC':
			$sortby = 'item_total ASC, pd.products_name';
		break;

		case 'sortbytotalDESC':
			$sortby = 'item_total DESC, pd.products_name';
		break;

    	default:
			$sortby = 'products_ordered DESC, pd.products_name';
		break;
	}

	if (isset($_GET['page']) && ($_GET['page'] > 1)) {
		$rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS;
	}

	if(isset($_GET['export']) && $_GET['export'] == 'csv') { 

		$result = tep_db_query("SELECT DATE_FORMAT(o.date_purchased,'%M - %Y') AS `Date`,
								pd.products_name AS 'Product Name',
								p.products_model AS 'Model',
								p.products_sku AS 'SKU',
								p.products_upc AS 'UPC',
								spg.suppliers_sku AS 'Supplier SKU',
								SUM(op.products_quantity) AS 'Quantity Purchased',
								SUM(op.final_price * op.products_quantity) AS `Total Sales`
								FROM " . TABLE_PRODUCTS . " p
								LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = p.master_products_id AND pd.language_id = '" . $languages_id. "')
								LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " op ON op.products_id = p.master_products_id
								LEFT JOIN " . TABLE_ORDERS . " o ON o.orders_id = op.orders_id
								LEFT JOIN (
									SELECT suppliers_sku, products_id
									FROM suppliers_products_groups
									GROUP BY products_id
								) spg ON spg.products_id = p.products_id

								WHERE o.orders_status = 3
								AND op.products_returned = 0
								".($date_from ? "AND o.date_purchased >= '".date('Y-m-d 00:00:01', strtotime($date_from))."'" : "").
								  ($date_to ? "AND o.date_purchased < '".date('Y-m-d 23:59:59', strtotime($date_to))."' " : "")." 
								". $channelFilter ."
								GROUP BY p.products_id
								ORDER BY o.date_purchased ASC
								");
	
		$num_fields = mysql_num_fields($result);

		$headers = array();
		for ($i = 0; $i < $num_fields; $i++) {
    		$headers[] = mysql_field_name($result , $i);
		}

		$fp = fopen('php://output', 'w');
		if ($fp && $result) {
	    	header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="bestsellers_'.date('m-d-Y', strtotime($date_from)).'_to_'.date('m-d-Y', strtotime($date_to)).'.csv"');
    		header('Pragma: no-cache');
		    header('Expires: 0');
    		fputcsv($fp, $headers);

		    while ($row = mysql_fetch_row($result)) {
    		    fputcsv($fp, array_values($row));
	    	}
    	
			exit();
		}

	}
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

<script language="javascript" src="includes/general.js"></script>
<script language="javascript" src="js/prototype.lite.js"></script>
<script language="javascript" src="js/ajaxfade.js"></script>

<script type="text/javascript">

	var chartSalesColors=['7F7FFF','007F00'];
	var chartViewsColors=['3F3FDF50','003F0050'];
	var chartPids=[];

	function showChart(pid) {

		if (chartPids.length==1 && chartPids[0]==pid) return true;
		var fadd = true;

		for (var i=0;chartPids[i]!=null;i++) if (chartPids[i]==pid || chartSalesColors[i+1]==null) {

			var icn=$('chart_icon_'+chartPids[i]);
			if (icn) icn.innerHTML='';
			if (chartPids[i]==pid) fadd=false;
			chartPids.splice(i--,1);
		}

		if (fadd) chartPids.push(pid);
		var img = $('chart_image');

		var url='sales_chart.php?width=790&height=300&bg_color=F0F5FB&start_date=<?php echo $date_from?>&end_date=<?php echo $date_to?>';

		for (var i=0;chartPids[i]!=null;i++) {
			url += '&pids[]='+chartPids[i]+'&sales_color[]='+chartSalesColors[i]+'&views_color[]='+chartViewsColors[i];
			var icn=$('chart_icon_'+chartPids[i]);
			if (icn) icn.innerHTML='<img src="images/sales_chart_icon_'+i+'.gif" border="0">';
		}

		ajaxFade(img,.75,url);
	}

</script>
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php');?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    
    <td width="100%" valign="top" colspan="2">
		<table border="0" width="100%" cellspacing="0" cellpadding="2">
			<tr>
				<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
          <td width="58" style="padding:0 0 0 12px"><img src="/admin/images/best_seller-icon.gif" width="48" height="48" alt=""></td>
								<td class="pageHeading" colspan="2"><?php echo  HEADING_TITLE ?></td></tr></table></td>
            <td class="pageHeading" align="right">
<div style="position:relative;">
<?php echo tep_draw_form('date_range', 'stats_products_purchased.php', (isset($_GET['show']) && $_GET['show'] == 'all' ? 'show=all' : '').(isset($_GET['sortby']) ? '&sortby='.$_GET['sortby'].'' : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : ''), 'GET');
?>
					<script type="text/javascript" src="js/popcalendar.js"></script>
					  <table border="0" cellpadding="0" cellspacing="0">

                        <tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?php echo $date_from;?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onclick="popUpCalendar(this,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?php echo $date_to?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onclick="popUpCalendar(document.date_range.date_to,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding-right:7px; padding-top:1px;">&nbsp;<a href="javascript:document.date_range.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a>                          </td>
                        </tr>
</table>
<input type="hidden" name="channel" value="<?php echo $_GET['channel']; ?>">
</form>
</div>
	    </td>
          </tr>
	  <tr>
	  <td colspan="2">
		<img src="" id="chart_image" width="99%" height="300" bgcolor="#F0F5FB">
	  </td>
	  </tr>
	  <tr>
		<td colspan="2" style="padding:0 0 0 10px">

			<table width="99%" border="0" cellpadding="0" cellspacing="0" align="center">
				<tr>
					<td width="80%" style="font:bold 11px arial">  Sales Channel:  &nbsp;  

<?php 
	echo tep_draw_form('channel', 'stats_products_purchased.php',  '?date_from='.$date_from.'&date_to='.$date_to.(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '').(isset($_GET['sortby']) ? '&sortby='.$_GET['sortby'].'' : ''), 'GET');

	echo '<select name="channel" onchange="this.form.submit();">
		    <option value="" '.(empty($_GET['channel']) ? 'selected="selected"' : '').'>ALL</option>
    		<option value="retail" '.($_GET['channel'] == 'retail' ? 'selected' : '').'>Retail</option>
			<option value="vendor" '.($_GET['channel'] == 'vendor' ? 'selected' : '').'>Vendors</option>
    		<option value="amazon" '.($_GET['channel'] == 'amazon' ? 'selected' : '').'>Amazon</option>
	    	<option value="ebay" '.($_GET['channel'] == 'ebay' ? 'selected' : '').'>eBay</option>
	    	<option value="email" '.($_GET['channel'] == 'email' ? 'selected' : '').'>E-Mail</option>
    	</select>
		<input type="hidden" name="date_from" value="'.$date_from.'">
		<input type="hidden" name="date_to" value="'.$date_to.'">
	
	</form>';
?> &nbsp;  &nbsp; 
</td>
			<td width="100" align="center" style="padding:10px 5px; line-height:25px; font:bold 11px arial;"><img src="images/sales_chart_icon_bar.gif"> Sales</td>
			<td width="100" align="center" style="padding:10px 5px; line-height:25px; font:bold 11px arial;"><img src="images/sales_chart_icon_line.gif"> Views</td>
		</tr>
	</table>
</td>
        </table>
	
	
	</td>
      </tr>
      <tr>
        <td style="padding:0 10px 0 10px">
	
	<table border="0" width="100%" cellspacing="0" cellpadding="0" align="center">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">

                <td class="dataTableHeadingContent"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sortby='.($_GET['sortby'] == 'sortbynameASC' ? 'sortbynameDESC':'sortbynameASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');?>" style="font:bold 11px arial; color:#fff">Product Name</a></td>

                <td class="dataTableHeadingContent" align="left"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sortby='.($_GET['sortby'] == 'sortbymodelASC' ? 'sortbymodelDESC':'sortbymodelASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');?>" style="font:bold 11px arial; color:#fff">Model</a></td>

                 <td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sortby='.($_GET['sortby'] == 'sortbyqtyDESC' ? 'sortbyqtyASC':'sortbyqtyDESC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_PURCHASED; ?></a></td>


 <td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sortby='.($_GET['sortby'] == 'sortbytotalDESC' ? 'sortbytotalASC':'sortbytotalDESC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');?>" style="font:bold 11px arial; color:#fff">Totals</a></td>

                <td class="dataTableHeadingContent" align="center" style="width:100px">Compare</td>
              </tr>
<?php

	$page = !empty($HTTP_GET_VARS['page']) ? $HTTP_GET_VARS['page'] : 1;
	$start_from = ($page-1) * MAX_DISPLAY_SEARCH_RESULTS;

	$sql = "SELECT * FROM students ORDER BY name ASC LIMIT $start_from, 20"; 

	$total = 0;
	$products_ordered = 0;

	$products_query = tep_db_query("SELECT op.products_id, 
									SUM(op.products_quantity) AS products_ordered, 
									pd.products_name,
									p.products_model,
									p.products_sku,
									p.products_upc,
									spg.suppliers_sku,
									SUM(op.final_price * op.products_quantity) AS item_total
									FROM " . TABLE_PRODUCTS . " p
									LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = p.master_products_id AND pd.language_id = '" . $languages_id. "')
									LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " op ON op.products_id = p.products_id
									LEFT JOIN " . TABLE_ORDERS . " o ON o.orders_id = op.orders_id

									LEFT JOIN (
										SELECT suppliers_sku, products_id
										FROM suppliers_products_groups
										GROUP BY products_id
										) spg ON spg.products_id = p.products_id

									WHERE o.orders_status = 3
									AND op.products_returned = 0
									".($date_from ? " AND o.date_purchased >= '".date('Y-m-d 00:00:01',strtotime($date_from))."'" : "").
									  ($date_to ? " AND o.date_purchased <= '".date('Y-m-d 23:59:59',strtotime($date_to))."' " : "")." 
									". $channelFilter ."
									GROUP BY p.products_id
									ORDER BY ".$sortby."
									LIMIT ".$start_from.", ".MAX_DISPLAY_SEARCH_RESULTS);

	$pid0 = NULL;

	while ($products = tep_db_fetch_array($products_query)) {

		$date_from = date('m/d/Y',strtotime($date_from));
		$date_to = date('m/d/Y',strtotime($date_to));
	
		if(!$pid0) $pid0 = $products['products_id'];

?>
			<tr class="dataTableRow <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd');?>" onclick="showChart('<?php echo $products['products_id']?>');">

                <td class="dataTableContent"><?php echo $products['products_name']?></td>
                <td class="dataTableContent"><?php echo $products['products_model']?></td>
                <td class="dataTableContent" align="center"><a href="orders.php?pID=<?php echo $products['products_id']?><?php echo ($date_from ? '&date_from='.$date_from:'')?><?php echo ($date_to ? '&date_to='.$date_to : '')?>"><?php echo $products['products_ordered']; ?></a>&nbsp;</td>
                <td class="dataTableContent" align="right"><?php echo $currencies->display_price($products['item_total'],0);?></td>
				<td class="dataTableContent"  style="width:100px; padding:5px 0 0 5px"><img src="images/graph-icon.jpg" border="0" style="cursor:pointer;" alt="Sales/Views Chart"> &nbsp; <span id="chart_icon_<?php echo $products['products_id']?>"></span></td>
              </tr>
<?php
  }
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3" style="padding:15px 0 0 0"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top">
<?php 


	// # start new pagination routine. 
	// # split_results class does not like multiple FROM statements, for example in sub queries and JOIN's.
	// # so emulate what pagination class instead of hacking pagination class to compensate.

	$prodCount_query = tep_db_query("SELECT COUNT(p.products_id)
											FROM " . TABLE_PRODUCTS . " p
											LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " op ON op.products_id = p.products_id
											LEFT JOIN " . TABLE_ORDERS . " o ON o.orders_id = op.orders_id
											WHERE o.date_purchased >= '".date('Y-m-d 00:00:01',strtotime($date_from))."'
										  	AND o.date_purchased < '".date('Y-m-d 23:59:59',strtotime($date_to))."' 
											". $channelFilter ."
											GROUP BY p.products_id
											");

	$prodCount = tep_db_num_rows($prodCount_query);

	$pageCount = ceil($prodCount / MAX_DISPLAY_SEARCH_RESULTS);

	echo 'Displaying '. ($start_from+1). ' to ' . (MAX_DISPLAY_SEARCH_RESULTS+$start_from) . ' of ' . $prodCount;

?>  &nbsp;|&nbsp; 

<?php
if(isset($_GET['show']) && $_GET['show'] == 'all'){
echo '<a href="'.$PHP_SELF.(!empty($_GET['date_from']) ? '?date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').(!empty($_GET['sortby']) ? '&sortby='.$_GET['sortby'] : '').'"><b>Back to paging</b></a>';
} else { 
echo '<a href="'.$PHP_SELF.'?show=all'.(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'"><b>Show All</b></a>';
}
?> 
<br><br>

<table><tr><td><img src="images/icons/excel_csv.png" width="16" height="16"></td><td>&nbsp;<a href="<?php echo $PHP_SELF.'?export=csv'.(isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] :'') . (isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] :'') ;?>"><b>Export to CSV</b></a></td></tr></table>


</td>
	<td class="smallText" align="right">
<?php 

	echo '<form name="pages" action="'.$PHP_SELF.'?page='.$page.(isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] :'') . (isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] :'').'" method="GET">';

	if($page == 1) { 
		echo '&lt;&lt;';
	} else {
		echo '<a href="'. $PHP_SELF .'?page='.($page-1).(isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] :'') . (isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] :'').'" class="splitPageLink">&lt;&lt;</a>';
	}
?> &nbsp; Page <select name="page" onchange="this.form.submit();">
<?php 

	for ($i=1; $i <= $pageCount; $i++) {
		echo '	<option value="'.$i.'" '.($_GET['page'] == $i ? 'selected' : '').'>'.$i.'</option>';
	}

	echo '</select> of '.$pageCount .' &nbsp;&nbsp;<a href="'. $PHP_SELF .'?page='.($page+1).(isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] :'') . (isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] :'').'" class="splitPageLink">&gt;&gt;</a>';

	echo tep_draw_hidden_field('date_from', $_GET['date_from']);
	echo tep_draw_hidden_field('date_to', $_GET['date_to']);
	echo tep_draw_hidden_field('channel', $_GET['channel']);

?>
</form>
&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<?php if ($pid0) { ?>
<script language="javascript">
  showChart('<?php echo $pid0?>');
</script>
<? } ?>

  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
