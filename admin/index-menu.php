<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	define('ADMIN_PERMISSION','ALL');

	require('includes/application_top.php');

	global $$link;

	require(DIR_FS_LANGUAGES.$language.'/index.php');

	$theweek = date('Y-m-d 00:00:01',strtotime('monday this week - 1 days'));

	// # logic to update running outstanding orders count in order Manager tab
	if(isset($_GET['orders']) && $_GET['orders'] == 'outstanding') { 

		function outstanding() {

			$outstanding_query = tep_db_query("SELECT COUNT(0) FROM orders WHERE (orders_status = '1' OR orders_status ='2')");
			$outstanding = (tep_db_num_rows($outstanding_query) > 0 ? tep_db_result($outstanding_query,0) : 0);

			tep_db_free_result($outstanding_query);

			return $outstanding;
		}

		echo outstanding();
		exit();
	}

	if(isset($_GET['orders']) && $_GET['orders'] == 'orders_shipped') { 

		function orders_shipped() {

			$orders_shipped_query  = tep_db_query("SELECT COUNT(DISTINCT orders_id) AS ct
												   FROM orders_status_history
												   WHERE date_added >= CURDATE()
												   AND orders_status_id = '3'
												  ");

			$orders_shipped = (tep_db_num_rows($orders_shipped_query) > 0 ? tep_db_result($orders_shipped_query,0) : 0);

			tep_db_free_result($orders_shipped_query);

			return $orders_shipped;		
		}

		echo orders_shipped();
		exit();

	}

	if(isset($_GET['orders']) && $_GET['orders'] == 'orders_returns') { 

		function orders_returns() {

			$orders_returns_query = tep_db_query("SELECT COUNT(0) AS ct 
												  FROM returned_products r 
												  WHERE r.last_modified >= CURDATE()
												");
			$orders_returns = (tep_db_num_rows($orders_returns_query) > 0 ? tep_db_result($orders_returns_query, 0) : 0);


			tep_db_free_result($orders_returns_query);

			return $orders_returns;		
		}

		echo orders_returns();
		exit();
	}


	if(isset($_GET['orders']) && $_GET['orders'] == 'orders_canceled') { 

		function orders_canceled() {

			$orders_canceled_query = tep_db_query("SELECT COUNT(DISTINCT orders_id)
														   FROM orders_status_history
														   WHERE date_added >= CURDATE()
														   AND orders_status_id = '0'");
			$orders_canceled = (tep_db_num_rows($orders_canceled_query) > 0 ? tep_db_result($orders_canceled_query, 0) : 0);

			tep_db_free_result($orders_canceled_query);

			return $orders_canceled;		
		}

		echo orders_canceled();
		exit();

	}


	if(isset($_GET['orders']) && $_GET['orders'] == 'orders_today_ct') { 

		function orders_today_ct() {

			$orders_today_ct_query = tep_db_query("SELECT COUNT(orders_id) AS ct
														   FROM ". TABLE_ORDERS ." o 
														   WHERE o.date_purchased >= CURDATE() 
														   AND o.orders_status > 0");

			$orders_today_ct = (tep_db_num_rows($orders_today_ct_query) > 0 ? tep_db_result($orders_today_ct_query, 0) : 0);

			tep_db_free_result($orders_today_ct_query);

			return $orders_today_ct;
		}

		echo orders_today_ct();
		exit();

	}

	if(isset($_GET['orders']) && $_GET['orders'] == 'orders_today_amount') { 

		function orders_today_amount() {

			$orders_today_amount_query = tep_db_query("SELECT SUM(ot.value) AS amount 
															   FROM ". TABLE_ORDERS ." o 
															   LEFT JOIN orders_total ot ON (o.orders_id=ot.orders_id AND ot.class='ot_total') 
															   WHERE o.date_purchased >= CURDATE() 
															   AND o.orders_status > 0");

			$orders_today_amount = (tep_db_num_rows($orders_today_amount_query) > 0 ? tep_db_result($orders_today_amount_query, 0) : 0);

			tep_db_free_result($orders_today_amount_query);

			return money_format('$%i', $orders_today_amount);		
		}

		echo orders_today_amount();
		exit();

	}

	if(isset($_GET['orders']) && $_GET['orders'] == 'orders_weekly_ct') { 

		function orders_weekly_ct() {

			$theweek = date('Y-m-d 00:00:01',strtotime('monday this week - 1 days'));

			$orders_weekly_ct_query = tep_db_query("SELECT COUNT(0) AS ct
												 		 FROM ". TABLE_ORDERS ." o 
														 WHERE o.date_purchased >= '". $theweek ."' 
														 AND o.orders_status > 0");

			$orders_weekly_ct = (tep_db_num_rows($orders_weekly_ct_query) > 0  ? tep_db_result($orders_weekly_ct_query, 0) : 0);

			tep_db_free_result($orders_weekly_ct_query);
			return $orders_weekly_ct;		
		}

		echo orders_weekly_ct();
		exit();

	}

	if(isset($_GET['orders']) && $_GET['orders'] == 'orders_weekly_amount') { 

		function orders_weekly_amount() {

			$theweek = date('Y-m-d 00:00:01',strtotime('monday this week - 1 days'));

			$orders_weekly_amount_query = tep_db_query("SELECT SUM(ot.value) AS amount 
												 		 FROM ". TABLE_ORDERS ." o 
														 LEFT JOIN orders_total ot ON (o.orders_id=ot.orders_id AND ot.class='ot_total') 
														 WHERE o.date_purchased >= '". $theweek ."' 
														 AND o.orders_status > 0");

			$orders_weekly_amount = (tep_db_num_rows($orders_weekly_amount_query) > 0 ? tep_db_result($orders_weekly_amount_query, 0) : 0);

			tep_db_free_result($orders_weekly_amount_query);

			return money_format('$%i', $orders_weekly_amount);		
		}

		echo orders_weekly_amount();
		exit();

	}

	if(isset($_GET['customers']) && $_GET['customers'] == 'customers_new') { 

		function customers_new() {

			$customers_new_query = tep_db_query("SELECT COUNT(0)
														 FROM (
															SELECT NULL
															FROM orders
															WHERE date_purchased >= ADDDATE(CURDATE(), INTERVAL 1 - DAYOFWEEK(CURDATE())DAY)
															AND customers_id !=0
															GROUP BY customers_id
															HAVING COUNT(*) = 1
															) AS cnt");

			$customers_new = (tep_db_num_rows($customers_new_query) > 0 ? tep_db_result($customers_new_query, 0) : 0);

			tep_db_free_result($customers_new_query);

			return $customers_new;		
		}

		echo customers_new();
		exit();

	}

	if(isset($_GET['customers']) && $_GET['customers'] == 'customers_returning') { 

		function customers_returning() {

			$customers_returning_query = tep_db_query("SELECT COUNT(0)
														 	   FROM (
															   	SELECT NULL
																FROM orders
																WHERE date_purchased >= ADDDATE(CURDATE(), INTERVAL 1 - DAYOFWEEK(CURDATE())DAY)
																AND customers_id !=0
																GROUP BY customers_id
																HAVING COUNT(*) > 1
																) AS cnt");
			$customers_returning = (tep_db_num_rows($customers_new_query) > 0 ? tep_db_result($customers_returning_query, 0) : 0);

			tep_db_free_result($customers_returning_query);

			return $customers_returning;
		
		}

		echo customers_returning();
		exit();
	}



	// # which section is being requested?
	$sec = $_GET['sec'];

	// # check to see if any Amazon MWS modules are active 
	// # if so we will use this flag later for competitive pricing link
	$amazon_modules = tep_db_query("SELECT mods_module FROM module_sets WHERE mods_enabled = 1 AND mods_module LIKE 'dbfeed_amazon_%'");

	$outstanding_query = tep_db_query("SELECT COUNT(0) 
										  FROM orders 
										  WHERE (orders_status = '1' OR orders_status ='2')");

	$outstanding = (tep_db_num_rows($outstanding_query) > 0 ? tep_db_result($outstanding_query, 0) : 0);
	tep_db_free_result($outstanding_query);

	$orders_today_query = tep_db_query("SELECT COUNT(0) AS ct,
											   SUM(ot.value) AS amount 
										FROM ". TABLE_ORDERS ." o 
										LEFT JOIN orders_total ot ON (o.orders_id=ot.orders_id AND ot.class='ot_total') 
										WHERE o.date_purchased >= CURDATE()
										AND o.orders_status > 0");

	$orders_today = (tep_db_num_rows($orders_today_query) > 0 ? tep_db_fetch_array($orders_today_query) : 0);

	tep_db_free_result($orders_today_query);

	$orders_thisweek_query = tep_db_query("SELECT COUNT(0) AS ct,
											      SUM(ot.value) AS amount 
										   FROM ". TABLE_ORDERS ." o 
										   LEFT JOIN orders_total ot ON (o.orders_id=ot.orders_id AND ot.class='ot_total') 
										   WHERE o.date_purchased >= '". $theweek ."' 
										   AND o.orders_status > 0");

	$orders_thisweek = tep_db_fetch_array($orders_thisweek_query);
 	tep_db_free_result($orders_thisweek_query);


	$returns_count = IXdb::read("SELECT COUNT(0) AS ct FROM orders_products op,returned_products r WHERE op.exchange_returns_id=r.returns_id AND r.last_modified >= CURDATE()",NULL,'ct');

	$status_canceled_query  = tep_db_query("SELECT COUNT(DISTINCT orders_id)
										  	FROM orders_status_history
										 	WHERE date_added >= CURDATE()
										 	AND orders_status_id = '0'
										  ");

	$status_canceled = (tep_db_num_rows($status_canceled_query) > 0 ? tep_db_result($status_canceled_query, 0) : 0);
 	tep_db_free_result($status_canceled_query);

	$status_shipped_query  = tep_db_query("SELECT orders_status_id,
												  COUNT(DISTINCT orders_id) AS ct
										   FROM orders_status_history
										   WHERE date_added >= CURDATE()
										   AND orders_status_id = '3'
										  ");

	$status_shipped = tep_db_fetch_array($status_shipped_query);
 	tep_db_free_result($status_shipped_query);


	list ($orders_loss)=IXdb::read("select SUM(op.products_quantity * p.products_price_myself) AS loss FROM orders o LEFT JOIN orders_products op ON (o.orders_id=op.orders_id) LEFT JOIN products p ON (op.products_id=p.products_id) WHERE o.date_purchased>='$theweek' AND o.orders_status>0",NULL,'loss');

	$customers_new_query = tep_db_query("SELECT COUNT(0)
												 FROM (
													SELECT NULL
													FROM orders
													WHERE date_purchased >= ADDDATE(CURDATE(), INTERVAL 1 - DAYOFWEEK(CURDATE())DAY)
													AND customers_id !=0
													GROUP BY customers_id
													HAVING COUNT(*) = 1
													) AS cnt");

	$customers_new = (tep_db_num_rows($customers_new_query) > 0 ? tep_db_result($customers_new_query, 0) : 0);

 	tep_db_free_result($customers_new_query);

	$customers_returning_query = tep_db_query("SELECT COUNT(0)
												 	   FROM (
														SELECT NULL
														FROM orders
														WHERE date_purchased >= ADDDATE(CURDATE(), INTERVAL 1 - DAYOFWEEK(CURDATE())DAY)
														AND customers_id !=0
														GROUP BY customers_id
														HAVING COUNT(*) > 1
														) AS cnt");

	$customers_returning = (tep_db_num_rows($customers_returning_query) > 0 ? tep_db_result($customers_returning_query, 0) : 0);

 	tep_db_free_result($customers_returning_query);

	$review_count_query = tep_db_query("SELECT COUNT(0) AS ct 
												FROM reviews 
												WHERE date_added >= ADDDATE(CURDATE(), INTERVAL 1 - DAYOFWEEK(CURDATE())DAY)
											   ");

	$review_count = (tep_db_num_rows($review_count_query) > 0 ? tep_db_result($review_count_query, 0) : 0);

 	tep_db_free_result($review_count_query);

  if ($sec=='Dashboards') {

?>
	<table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
			<td valign="top">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td valign="top"  style="padding:7px;">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #D4D4D4; background-color:#FFFFFF;">
  <tr>
    <td style="height:25px; width:21px; padding:5px 0 5px 10px;"><img src="images/users-icon.jpg" width="21" height="21" alt=""></td><td align="left" width="80%" style="padding:6px 0 5px 5px;">Switch Dashboard Role</td>
  </tr>
  <tr>
    <td colspan="2" style="padding:8px 0 5px 10px; border-top:1px solid #D4D4D4;"><form action="" method="get" style="margin:0;"><span id="dash_sel_box">[n/a]</span>
    </form></td>
  </tr>
<tr>
    <td style="height:25px; width:14px; padding:0 0 0 12px;"><img src="images/add-icon.jpg" width="14" height="14" onclick="javascript:loadintoIframe('myframe','dashboard_control.php');" style="cursor:pointer;"></td><td align="left"><a href="javascript:loadintoIframe('myframe','dashboard_control.php');">Customize Dashboards</a></td>
  </tr>
</table>

<script type="text/javascript">

  function makeDashSelect() {

    var sel='<select name="dashboard" style="width:143px; font:normal 11px arial;" onChange="document.getElementsByTagName(\'a\')[0].focus(); loadintoIframe(\'myframe\',Dashboard=this.value);">\n';

    for (var i in Dashboards) {
      sel+='<option value="'+i+'"'+(i==Dashboard ? ' selected':'')+'>'+Dashboards[i]+'</option>';
    }

    sel+='\n</select>';

    return sel;
  }

  jQuery('#dash_sel_box').html(makeDashSelect());
</script>

</td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top" style="padding:0 6px 6px 6px;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
            <tr>
              <td width="22" height="23" align="center" style="padding:10px 0 3px 10px;"><img src="images/coin-icon.jpg" width="17" height="15"></td>
              <td width="142" style="padding:10px 0 3px 5px;"><b>Today's Orders</b></td>
            </tr>
            <tr>
              <td colspan="2" valign="top" style="padding:6px 5px 10px 15px;">
New Orders: <span id="orders_today_ct"><?php echo $orders_today['ct'];?></span> <br>
Returns: <span id="orders_returns"><?php echo $returns_count?></span><br>
Canceled: <span id="orders_canceled"><?php echo $status_canceled?></span><br>
Shipped: <span id="orders_shipped"><?php echo $status_shipped['ct']?></span><br>
<br>
Today's Revenue: <span id="orders_today_amount"><?php echo money_format('$%i', $orders_today['amount']);?></span>
</td>
            </tr>
      </table></td>
  </tr>


  <tr>
    <td valign="top" style="padding:0 6px 6px 6px;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
            <tr>
              <td width="22" height="23" align="center" style="padding:10px 0 3px 10px;"><img src="images/coin-icon.jpg" width="17" height="15"></td>
              <td width="142" style="padding:10px 0 3px 5px;"><b>Weekly Vitals</b></td>
            </tr>
            <tr>
              <td colspan="2" valign="top" style="padding:6px 5px 10px 15px;"> 
Order Count: <span id="orders_weekly_ct"><?php echo $orders_thisweek['ct'];?></span><br>
New Customers: <span id="customers_new"><?php echo $customers_new?></span><br>
Returning Customers: <span id="customers_returning"><?php echo $customers_returning?></span><br>
Product Reviews: <?php echo sprintf('%d',$review_count)?><br>
                      <br>
Weekly Gross: <span id="orders_weekly_amount"><?php echo money_format('$%i', $orders_thisweek['amount']);?></span>
			  </td>
            </tr>
      </table>
	</td>
  </tr>
  
</table></td>
  </tr>
</table>
<script type="text/javascript">
jQuery(document).ready(function(response){

	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=orders_today_ct", function(data) {
			jQuery("#orders_today_ct").html(data);
		});  
	}, 60000);

	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=orders_today_amount", function(data) {
			jQuery("#orders_today_amount").html(data);
		});  
	}, 60000);


	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=orders_weekly_ct", function(data) {
			jQuery("#orders_weekly_ct").html(data);
		});  
	}, 60000);

	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=orders_weekly_amount", function(data) {
			jQuery("#orders_weekly_amount").html(data);
		});  
	}, 60000);

	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=orders_canceled", function(data) {
			jQuery("#orders_canceled").html(data);
		});  
	}, 60000);

	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=orders_shipped", function(data) {
			jQuery("#orders_shipped").html(data);
		});  
	}, 60000);

	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=orders_returns", function(data) {
			jQuery("#orders_returns").html(data);
		});  
	}, 60000);

	setInterval(function(){ 
		jQuery.get("index-menu.php?customers=customers_new", function(data) {
			jQuery("#customers_new").html(data);
		});  
	}, 60000);

	setInterval(function(){ 
		jQuery.get("index-menu.php?customers=customers_returning", function(data) {
			jQuery("#customers_returning").html(data);
		});  
	}, 60000);


});

</script>
<?php

} elseif ($sec=='Orders') {

?>
			<table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px;">
<div style="border:1px solid #B7B7B7; background-color:#FFFFC6; padding:10px 0 10px 0;" align="center">
<a href="javascript:loadintoIframe('myframe','orders.php?date_from=&amp;date_to=&amp;cFind=&amp;action=cust_search&amp;status=1,2');"> Outstanding Orders: <span id="outstanding"> <?php echo $outstanding;?> </span></a>
      </div>
  </td></tr>
              <tr>
                <td  style="padding:0 6px 6px 6px;"><table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                          <tr>
                            <td width="22" height="23" align="center"><img src="images/money-icon.gif" width="22" height="21"></td>
                            <td><a href="javascript:loadintoIframe('myframe','orders.php');"><b>Manage All Orders</b></a></td>
                          </tr>
                          <tr>
                            <td height="23" align="center"><img src="images/cart-icon.jpg" width="21" height="20"></td>
                            <td><a href="javascript:loadintoIframe('myframe','create_order.php');"><b>Create New Order</b></a></td>
                          </tr>
                          <tr>
                            <td height="23" style="padding-left:7px;"><img src="images/coupon-icon.gif" width="18" height="17"></td>
                            <td><a href="javascript:loadintoIframe('myframe','coupon_admin.php');"><b>Manage Coupons</b></a></td>
                          </tr>
                          <tr>
                            <td height="23" style="padding-left:7px;"><img src="images/coupons-icon.gif" width="17" height="17"></td>
                            <td><a href="javascript:loadintoIframe('myframe','coupon_admin.php?page=1&amp;cID=90&amp;action=new');"><b>Create New Coupon </b></a></td>
                          </tr>

<tr>
                            <td height="23" style="padding-left:7px;"><img src="images/coupon-icon.gif" width="17" height="17"></td>
                            <td><a href="javascript:loadintoIframe('myframe','gv_sent.php');"><b>View Gift Certs</b></a></td>
                          </tr>
                      </table></td>
              </tr>
              <tr>
                <td  style="padding:0 6px 6px 6px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">

                        <tr>
                          <td width="22" height="23" align="center"><img src="images/returns_icon.gif" width="18" height="18"></td>
                          <td><a href="javascript:loadintoIframe('myframe','returns.php?status=2');"><b>View
                                Returns</b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/crosssell-icon.gif" width="19" height="19"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','return_product.php');">Create Return</a></b></td>
                        </tr>
                  </table>
				</td>
              </tr>
              <tr>
                <td  style="padding:0 6px 6px 6px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                          <tr>
                           <td width="22" align="center" style="padding:9px 5px 5px 5px;"><img src="images/bulk-icon.gif" width="16" height="16"></td>
                            <td style="padding:9px 5px 5px 5px;"><a href="javascript:loadintoIframe('myframe','exportorders.php');"><b>CSV Order Exporter</b></a></td>
                          </tr>
                          <tr>
                            <td width="22" height="23" align="center"><img src="images/qb_icon.gif" width="18" height="18"></td>
                            <td><a href="javascript:loadintoIframe('myframe','qbi_create.php');"><b>Quickbooks Export</b></a></td>
            </tr>
			<tr>
                            <td height="23" align="center"><img src="images/coin-icon.gif" width="17" height="22"></td>
                            <td><a href="javascript:loadintoIframe('myframe','orders_bulk_update.php');"><b>Quick Batch Orders</b></a></td>
                  </tr>
                         
                  </table></td>
              </tr>
</table>

<script type="text/javascript">
jQuery(document).ready(function(response){

	setInterval(function(){ 
		jQuery.get("index-menu.php?orders=outstanding", function(data) {
			jQuery("#outstanding").html(data);
		});  
	}, 10000);
});

</script>

<?php

} elseif ($sec=='Customers') {


?>
		  <table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
              <td  style="padding:6px;">
			  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                        <tr>
                          <td width="22" height="23" align="center"><img src="images/consult-icon.gif" width="22" height="21"></td>
                          <td><a href="javascript:loadintoIframe('myframe','customers.php');"><b>View
                              Customers </b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/returns-icon.gif" width="15" height="14"></td>
                          <td><a href="javascript:loadintoIframe('myframe','create_account.php');"><b>Create Account </b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/people-icon.gif" width="16" height="16"></td>
                          <td><a href="javascript:loadintoIframe('myframe','customers.php?cust_group=2');"><b>View Vendors </b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/resellers-icon.gif" width="20" height="20"></td>
                          <td><a href="javascript:loadintoIframe('myframe','customers_groups.php');"><b>Edit Pricing Groups</b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/customer-icon.gif" width="18" height="16"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','stats_customers.php');">Top Customers</a></b></td>
                        </tr>
                </table>
			  </td>
            </tr>
</table>
<?php

} elseif ($sec=='Inventory') {

?>
<table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:6px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
        <tr>
          <td width="22" height="23" align="center"><img src="images/tag-icon.gif" width="16" height="15"></td>
          <td><a href="javascript:loadintoIframe('myframe','categories.php?pclass=product_default&status=1');"><b>Categories/Products</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/message_icon.gif" width="22" height="21"></td>
          <td><a href="javascript:loadintoIframe('myframe','specials.php');"><b>Product Specials</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/watch-icon.gif" width="19" height="19"></td>
          <td><a href="javascript:loadintoIframe('myframe','featured.php');"><b> Featured Products </b></a></td>
        </tr>
			<tr>
				<td height="23" align="center"><img src="images/coupon-icon.gif" width="18" height="17"></td>
				<td><a href="javascript:loadintoIframe('myframe','gift_certs.php');"><b>Gift Certificates</b></a></td>
        	</tr>
        	<tr>
        	  <td width="22" height="23" align="center"><img src="images/manufacturer.gif" width="21" height="16"></td>
        	  <td><a href="javascript:loadintoIframe('myframe','manufacturers.php');"><b>Edit Manufacturers </b></a>            </td>
        	</tr>
        	<tr>
        	  <td height="23" align="center"><img src="images/reviews-icon.gif" width="19" height="19"></td>
        	  <td> <a href="javascript:loadintoIframe('myframe','reviews.php');"><b>Product Reviews</b></a></td>
        	</tr>
    	    <tr>
	      	  <td height="23" align="center"><img src="images/newsletter-icon.gif" width="19" height="17"></td>
    	      <td><a href="javascript:loadintoIframe('myframe','products_expected.php');"><b>Upcoming Products</b></a></td>
	        </tr>
		</table>
	</td>
</tr>
<tr>
	<td style="padding:0 6px 6px 6px;">
		<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
			<tr>
    	       <td height="23" align="center"><img src="images/warehouse_icon.gif" width="21" height="20"></td>
        	    <td><span class="menuBoxContent"><b><a href="javascript:loadintoIframe('myframe','stats_products_backordered.php');">Inventory Levels</a></b></span></td>
	        </tr>
			<tr>
    	       <td height="21" align="center"><img src="images/vendors-icon.png" width="23" height="23"></td>
        	    <td><span class="menuBoxContent"><b><a href="javascript:loadintoIframe('myframe','vendor_pricing_report.php');">Vendor Pricing</a></b></span></td>
	        </tr>
<?php 
	if(tep_db_num_rows($amazon_modules) > 0) { 
?>
			<tr>
				<td height="23" align="center"><img src="images/money-icon.gif" width="22" height="21"></td>
				<td><b><a href="javascript:loadintoIframe('myframe','competitive_pricing_report.php');">Competitive Pricing</a></b></td>
			</tr>
<?php } ?>
			<tr>
				<td height="23" style="padding:0 0 0 8px;"><img src="images/bulk-icon.gif" width="16" height="16"></td>
	        	<td><a href="javascript:loadintoIframe('myframe','ez_populate.php');"><b>Bulk CSV Update </b></a></td>
    	    </tr>
		</table>
	</td>
</tr>
</table>
<?php

} elseif ($sec=='Suppliers') {


?>
		  <table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
              <td  style="padding:6px;">
			  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                        <tr>
                          <td width="22" height="23" align="center"><img src="images/warehouse_icon.gif" width="21" height="20"></td>
                          <td><a href="javascript:loadintoIframe('myframe','supply_request.php');"><b>Supply Requests </b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/fork-icon.gif" width="20" height="16"></td>
                          <td><a href="javascript:loadintoIframe('myframe','create_supply_request.php');"><b>Create New Request </b></a></td>
                        </tr>	
                </table>
			</td>
</tr>
<tr>
	<td style="padding:0 6px 6px 6px;">
		<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                        <tr>
                          <td height="23" align="center"><img src="images/people2-icon.gif" width="19" height="15"></td>
                          <td><a href="javascript:loadintoIframe('myframe','suppliers.php');"><b>Supplier Manager </b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/manufacturer.gif" width="21" height="16"></td>
                          <td><a href="javascript:loadintoIframe('myframe','suppliers.php?page=1&amp;hID=3&amp;action=new');"><b>Create New Supplier </b></a></td>
 </tr>

</table>
</td>
		</tr>
        <tr>
	<td style="padding:0 6px 6px 6px;">
			  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                        <tr>
                          <td width="22" height="23" align="center"><img src="images/warehouse-icon.png" width="21" height="18"></td>
                          <td><a href="javascript:loadintoIframe('myframe','warehouse_manager.php');"><b>Warehouse Manager</b></a></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/boxes-on-palette-icon.png" width="21" height="21"></td>
                          <td><a href="javascript:loadintoIframe('myframe','create_supply_request.php');"><b>Fulfillment Manager</b></a></td>
                        </tr>
						<tr>
			    	       <td height="23" align="center"><img src="images/warehouse_icon.gif" width="21" height="20"></td>
        				    <td><span class="menuBoxContent"><b><a href="javascript:loadintoIframe('myframe','stats_products_backordered.php');">Inventory Levels</a></b></span></td>
				        </tr>
            	    </table>
				</td>
			</tr>
		</table>
<?php
} elseif ($sec=='Reports') {
?>

	<table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
			<td style="padding:6px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
					<tr>
						<td width="22" height="23" align="center"><img src="images/graph-icon.jpg" width="20" height="20"></td>
						<td><b><a href="javascript:loadintoIframe('myframe','stats_sales_report.php')">Sales Performance</a></b> </td>
					</tr>
					<tr>
                          <td height="16" align="center"><img src="images/percent-icon.gif" width="15" height="13"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','stats_sales.php?&amp;by=name&amp;status=3');">Sales Summary</a></b></td>
                        </tr>
<?php if(tep_db_num_rows($amazon_modules) > 0) { ?>
                        <tr>
                          <td height="23" align="center"><img src="images/money-icon.gif" width="22" height="21"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','competitive_pricing_report.php');">Competitive Pricing</a></b></td>
                        </tr>
<?php } ?>
                        <tr>
                          <td height="23" align="center"><img src="images/best-icon.gif" width="22" height="17"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','stats_products_purchased.php');">Best Sellers</a></b></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/graph-icon.jpg" width="20" height="20"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','returns_report.php?date_from=01%2F01%2F<?php echo date("Y")?>&amp;date_to=<?php echo date("n/j/Y")?>');">Refunds Report</a></b></td>
                        </tr>
 <tr>
                          <td height="23" align="center"><img src="images/mag-icon.gif" width="17" height="16"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','supertracker.php?special=prod_coverage');">Products Viewed</a></b></td>
                        </tr>
                        <!--<tr>
                          <td width="22" height="23" align="center"><img src="images/banner-icon.gif" width="21" height="13"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','stats_ad_results.php');">Ad Results</a></b></td>
                        </tr>-->
                        <tr>
                          <td height="23" align="center"><img src="images/warehouse_icon.gif" width="21" height="20"></td>
                          <td><span class="menuBoxContent">
							<b><a href="javascript:loadintoIframe('myframe','stats_products_backordered.php');">Low Stock Report</a></b></span></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/customer-icon.gif" width="18" height="16"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','stats_customers.php');">Top Customers</a></b></td>
                        </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/piechart-icon.jpg" width="22" height="14"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','traffic_details.php');">Traffic Analytics</a></b></td>
                        </tr>
						<tr>
                          <td height="23" align="center"><img src="images/chat-icon24x24.png" width="21" height="21"></td>
                          <td><a href="javascript:loadintoIframe('myframe','stats_referral_sources.php?date_from=<?php echo date("Y-m-d")?>&amp;date_to=<?php echo date("Y-m-d")?>');"><b>Referrals Report</b></a></td>
               </tr>
                        <tr>
                          <td height="23" align="center"><img src="images/graph-icon.jpg" width="20" height="20"></td>
                          <td><b><a href="javascript:loadintoIframe('myframe','affiliate_sales.php');">Affiliate Sales</a></b></td>
                        </tr>
                </table>
			  </td>
            </tr>
</table>


<?php
} elseif ($sec=='Marketing') {
?>

<table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:6px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">

                          <tr>
                            <td height="23" style="padding-left:7px;"><img src="images/coupon-icon.gif" width="18" height="17"></td>
                            <td><a href="javascript:loadintoIframe('myframe','coupon_admin.php');"><b>Promotion Codes</b></a></td>
                          </tr>

<tr>
                                <td width="22" height="23" align="right"><img src="images/banner-icon.gif" width="21" height="13"></td>
                                <td><a href="javascript:loadintoIframe('myframe','banner_manager.php');"><b>Banner Manager </b></a></td>
                  </tr>
         <tr>
          <td height="23" align="center"><img src="images/target-icon.gif" width="20" height="20"></td>
<!--/*<td><a href="javascript:loadintoIframe('myframe','apilitax/index.php');"><b>PPC Ad Manager </b></a></td>*/-->
          <td><a href="javascript:loadintoIframe('myframe','product_ads.php?campaigns=1');"><b>PPC Ad Manager </b></a></td>
        </tr>
<tr>
          <td height="23" align="center"><img src="images/target-icon.gif" width="20" height="20"></td>
          <td><a href="javascript:loadintoIframe('myframe','supertracker.php?special=ppc_summary');"><b>Tracked Campaigns</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/seo-icon.gif" width="19" height="19"></td>
          <td><a href="javascript:loadintoIframe('myframe','seotools/seo-tools.php');"><b>SEO Tools</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/seo-icon.gif" width="19" height="19"></td>
          <td><a href="javascript:loadintoIframe('myframe','header_tags_english.php');"><b>META Tags Control</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/graph-icon.jpg" width="16" height="15"></td>
          <td><a href="javascript:loadintoIframe('myframe','ad_campaigns.php');"><b>Create Campaign</b></a></td>
        </tr>
      </table>
	  </td>
              </tr>
              <tr>
                <td style="padding:0 6px 6px 6px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
		<tr>
          <td width="22" height="23" align="center"><img src="images/newsletter-icon.gif" width="19" height="17"></td>
          <td><a href="javascript:loadintoIframe('myframe','mails.php?selected_box=tools');"><b>Email Customers</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/newsletter-icon.gif" width="19" height="17"></td>
          <td><a href="javascript:loadintoIframe('myframe','newsletters.php');"><b>Newsletter Manager </b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/newsletter-icon.gif" width="19" height="17"></td>
          <td><a href="javascript:loadintoIframe('myframe','newsletters_subscribers_view.php');"><b>Subscriber Manager</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/csv-icon.gif" width="17" height="17"></td>
          <td><a href="javascript:loadintoIframe('myframe','customer_export.php');"><b>CSV Email Export</b></a></td>
        </tr>

		<tr>
          <td height="23" align="center"><img src="images/csv-icon.gif" width="17" height="17"></td>
          <td><a href="javascript:loadintoIframe('myframe','email_import.php');"><b>CSV Email Import</b></a></td>
        </tr>

      </table>
	  </td>
              </tr>
              <tr>
                <td><table width="175" border="0" cellpadding="0" cellspacing="0">
  <tr>
  <td style="padding:0 6px 6px 6px">
  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
  <tr>
              <td width="22" height="23" align="center" style="padding:10px 0 0 10px"><img src="images/coin-icon.jpg" width="17" height="15"></td>
              <td width="143" style="padding:10px 0 0 6px"><b>Affiliate Vitals</b></td>
            </tr>
            <tr>
              <td colspan="2" valign="top" style="padding:10px 0 10px 15px">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td valign="top">
<?php
	// # Affiliate  
	$affiliate_sales_raw = "SELECT COUNT(*) AS count, SUM(affiliate_value) AS total, SUM(affiliate_payment) AS payment FROM " . TABLE_AFFILIATE_SALES;
	$affiliate_sales_query= tep_db_query($affiliate_sales_raw);
	$affiliate_sales = tep_db_fetch_array($affiliate_sales_query);

	$affiliate_clickthroughs_raw = "SELECT COUNT(*) AS count FROM " . TABLE_AFFILIATE_CLICKTHROUGHS;
	$affiliate_clickthroughs_query = tep_db_query($affiliate_clickthroughs_raw);
	$affiliate_clickthroughs = tep_db_fetch_array($affiliate_clickthroughs_query);
	$affiliate_clickthroughs = $affiliate_clickthroughs['count'];
	$affiliate_transactions = $affiliate_sales['count'];
	$affiliate_conversions = ($affiliate_transactions > 0) ? tep_round($affiliate_transactions/$affiliate_clickthroughs,6)."%" : 'n/a';
	$affiliate_amount = $affiliate_sales['total'];
	$affiliate_average = ($affiliate_transactions > 0) ? tep_round($affiliate_amount/$affiliate_transactions,2) : 'n/a';
	$affiliate_commission = $affiliate_sales['payment'];
	$affiliates_raw = "SELECT COUNT(*) AS count FROM " . TABLE_AFFILIATE;
	$affiliates_raw_query=tep_db_query($affiliates_raw);
	$affiliates_raw = tep_db_fetch_array($affiliates_raw_query);
	$affiliate_number = $affiliates_raw['count'];


	if(!isset($currencies)) {
		include_once(DIR_FS_CLASSES.'currencies.php');
		$currencies=new currencies;
	}

	$heading = array();
	$contents = array();

	$contents[] = array('params' => '',
    					'text' => BOX_ENTRY_AFFILIATES . ' &nbsp; &nbsp; ' . $affiliate_number . '<br>' .
								  BOX_ENTRY_CONVERSION . ' &nbsp; &nbsp; ' . $affiliate_conversions . '<br>' .
								  BOX_ENTRY_COMMISSION . ' &nbsp; &nbsp; ' . $currencies->display_price($affiliate_commission, ''));
	$box = new box;
	echo '<a href="javascript:loadintoIframe(\'myframe\',\'affiliate_affiliates.php\');">' . BOX_ENTRY_AFFILIATES . '</a> ' . $affiliate_number . '<br>';
	echo BOX_ENTRY_CONVERSION . ' ' . $affiliate_conversions . '<br>';
	echo BOX_ENTRY_COMMISSION . ' ' . $currencies->display_price($affiliate_commission, '');
?>
</td>
          </tr>
        
      </table></td>
  </tr>
</table></td></tr>
<tr>
    <td style="padding:0 6px 6px 6px;">
	<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
<!--  <tr>
          <td width="22" height="23" align="center"><img src="images/handshake-icon.jpg" width="22" height="15"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_summary.php');"><b>Affiliate Summary</b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/consult-icon.gif" width="22" height="21"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_affiliates.php');"><b>Affiliate Manager </b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/money-icon.gif" width="22" height="21"></td>
          <td><a href="javascript:loadintoIframe('myframe','customers_groups.php');"><b>Commission Groups </b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/affiliate-icon.gif" width="18" height="18"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_payment.php');"><b>Disburse Payments</b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/handshake-icon.jpg" width="22" height="15"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_clicks.php');"><b>Affiliate Referrals</b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/banner-icon.gif" width="21" height="13"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_banners.php');"><b>Affiliate Banners</b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/newsletter-icon.gif" width="19" height="17"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_news.php');"><b>Affiliate Newsletters</b></a></td>
        </tr>
		<tr>
          <td height="23" align="center"><img src="images/newsletter-icon.gif" width="19" height="17"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_contact.php');"><b>Create Newsletter</b></a></td>
        </tr>
<tr>-->
          <td height="25" align="center" style="padding:0 0 0 3px;"><img src="images/handshake-icon.jpg" width="22" height="15"></td>
          <td><a href="javascript:loadintoIframe('myframe','affiliate_summary.php');"><b>Affiliate Manager</b></a></td>
        </tr>
      </table>
	  </td>
  </tr> 
</table></td>
              </tr>
</table>
</td>
              </tr>
            </table>

<?php
} elseif ($sec=='Design') {
?>
		  <table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
              <td style="padding:6px;">
			  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                        <tr>
                          <td height="23" align="center"><img src="images/templates-icon.gif" width="16" height="16"></td>
                          <td><a href="javascript:loadintoIframe('myframe','information_manager.php');" ><b>Web Page Control</b></a></td>
                        </tr>
			<tr>
                                <td width="22" height="23" align="right"><img src="images/banner-icon.gif" width="21" height="13"></td>
                                <td><a href="javascript:loadintoIframe('myframe','banner_manager.php');"><b>Banner Manager </b></a></td>
                  </tr>	
                       <!-- <tr>
                          <td width="22" height="23" align="center"><img src="images/pallette-icon.gif" width="19" height="17"></td>
                          <td><a href="javascript:loadintoIframe('myframe','#');"><b>Drag &amp; Drop Builder</b></a></td>
                        </tr> 
                        <tr>
                          <td height="23" align="center"><img src="images/files-icon.gif" width="16" height="16"></td>
                          <td><a href="javascript:loadintoIframe('myframe','file_manager.php');" ><b>File Manager</b></a></td>
                        </tr>-->
                </table>
			  </td>
            </tr>
</table>
<?php
} elseif ($sec=='Webmail') {
?>

		  <table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
              <td style="padding:6px;">
			  
			  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                      <tr>
                        <td width="22" height="23" align="center"><img src="images/webmail-icon-sm.gif" width="21" height="21" alt=""></td>
                        <td><!--a href="javascript:loadintoIframe('myframe','../../mail/index.php');"><b>Launch Webmail</b></a-->
							<a href="http://e.zwaveproducts.com" target="_blank"><b>Launch Webmail</b></a>
						</td>
                      </tr>
                      <!--tr>
                        <td height="23" align="center"><img src="images/mailbox-icon.gif" width="14" height="15" alt=""></td>
                        <td><a href="javascript:loadintoIframe('myframe','mailboxes.php');"><b>Manage Mailboxes</b></a></td>
                      </tr-->


                </table>
					
			  </td>
            </tr>
</table>
<?php
} elseif ($sec=='KnowledgeBase') {
?>

		  <table width="175" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
              <td style="padding:9px 6px 9px 6px;">
			  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                     <tr>
                        <td width="14" height="20" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14" alt=""></td>
                        <td><a href="javascript:loadintoIframe('myframe','../knowledgebase/index.php?page=kb_cat&amp;id=1');"><b>Admin Walkthroughs </b></a></td>
                      </tr>
                      <tr>
                        <td height="20" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14" alt=""></td>
                        <td><a href="javascript:loadintoIframe('myframe','../knowledgebase/index.php?page=kb_cat&amp;id=20');"><b>Front of Store Config </b></a></td>
                      </tr>
                      <tr>
                        <td height="20" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14" alt=""></td>
                        <td><a href="javascript:loadintoIframe('myframe','../knowledgebase/index.php?page=kb_cat&amp;id=24');" ><b> Marketing Tools</b></a></td>
                      </tr>
                      <tr>
                        <td height="20" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14" alt=""></td>
                        <td><a href="javascript:loadintoIframe('myframe','../knowledgebase/index.php');" ><b>Support Help Desk</b></a></td>
                      </tr> 
                </table>
			  </td>
            </tr>
            <tr>
              <td style="padding:0 6px 6px 6px;">
			  <table width="100%" border="0" cellspacing="0" cellpadding="5" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                      <tr>
<td width="22" height="23" align="center" style="padding:10px 0 0 10px"><img src="images/mag-icon.gif" width="15" height="15" alt=""></td>
                        <td style="padding:10px 0 0 6px" align="left" width="143"><a href="javascript:loadintoIframe('myframe','../knowledgebase/index.php?q=&amp;page=kb_search');"><b>Quick Search</b></a></td>
                      </tr>
                      <tr>
                        <td colspan="2" style="padding:5px 0 0 10px;">
<span style="padding:10px 0 3px 0; color:#000000;">Features, Tools, Products:</span>
						<form name="searchkb" method="get" action="/knowledgebase/index.php" style="margin:0;" target="contentiframe">
						<input style="font:9pt verdana; width:135px;" name="q">
 <input type="hidden" name="page" value="kb_search">
						</form>
<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
                          <tr>
                            <td align="center" style="color:#000000;"><span style="cursor:pointer; text-decoration:underline;" onclick="javascript:loadintoIframe('myframe', '../knowledgebase/index.php');">More Options</span></td>
                            <td align="center" style="padding:7px 0 5px 0;"><div style="border:2px solid #D4D4D4; cursor:pointer; background:#F5F5F5; width:52px; text-align:center; padding:3px;" onclick="document.searchkb.submit()">Search</div></td>
                          </tr>
                        </table>
						</td>
                    </tr>
                </table>
			  </td>
            </tr>
</table>
<?php
}
?>