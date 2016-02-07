<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


if(isset($_GET['show']) && $_GET['show'] == 'all') {
	define('MAX_DISPLAY_SEARCH_RESULTS', '20000');
}

  define ('HEADING_TITLE','Vendor Pricing');
  require('includes/application_top.php');


  function _get_attribute_values_list($products_id) {

    $res = tep_db_query(
    "SELECT po.products_options_name, pov.products_options_values_name
        FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
        INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po ON (pa.options_id = po.products_options_id AND po.language_id = {$GLOBALS['languages_id']})
        INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ON (pa.options_values_id = pov.products_options_values_id AND pov.language_id = {$GLOBALS['languages_id']})
        WHERE pa.products_id = {$products_id} ");
    $values = array();
    while ($row = tep_db_fetch_array($res)) {
        $values[$row['products_options_name']][] = $row['products_options_values_name'];
    }
    reset($values);
    while (list($id, $value) = each($values)) {
        $result[$id] = implode(",", array_unique($value));
    }

  return $result;

  }

if($_GET['action'] == "update" && is_array($_POST['products_price'])) {

    foreach($_POST['products_price'] as $products_id => $products_price) {

        $products_price = floatval($products_price);
   	    $products_id = intval($products_id);

		//# Update the prices!
		tep_db_query("UPDATE ".TABLE_PRODUCTS." 
					  SET products_price = '".$products_price."', 
					  products_last_modified = NOW() 
					  WHERE products_id = '".$products_id."'
					 ");

		// # also update pricing group table.
		tep_db_query("UPDATE ".TABLE_PRODUCTS_GROUPS." 
					  SET customers_group_price = '" . $products_price."'
					  WHERE products_id = '".$products_id."'
					  AND customers_group_id = '0'
					 "); 
	}

} 

if($_GET['action'] == "update" && is_array($_POST['cost_price'])) {

    foreach($_POST['cost_price'] as $products_id => $cost_price) {

        $cost_price = floatval($cost_price);
   	    $products_id = intval($products_id);

		//# Update the prices in the products table
		tep_db_query("UPDATE ".TABLE_PRODUCTS." 
					  SET products_price_myself = '".$cost_price."', 
					  products_last_modified = NOW() 
					  WHERE products_id = '".$products_id."'
					 ");

		//# Update the prices in the suppliers_products_groups for primary supplier only.
		tep_db_query("UPDATE suppliers_products_groups 
					  SET suppliers_group_price = '".$cost_price."'
					  WHERE products_id = '".$products_id."'
					  AND priority = 0
					 ");
	}

} 

if($_GET['action'] == "update" && is_array($_POST['products_msrp'])) {

    foreach($_POST['products_msrp'] as $products_id => $products_msrp) {

        $products_msrp = floatval($products_msrp);
   	    $products_id = intval($products_id);

		//# Update the msrp in the suppliers_products_groups
		tep_db_query("UPDATE suppliers_products_groups 
					  SET products_msrp = '".$products_msrp."'
					  WHERE products_id = '".$products_id."'
					  AND priority = 0
					 ");
	}

} 

if($_GET['action'] == "update" && is_array($_POST['vendor_price'])) { 

	foreach ($_POST['vendor_price'] as $products_id => $vendor_price) {

		$vendor_price = floatval($vendor_price);
   	    $products_id = intval($products_id);

		// # check if our vendors price exists
		$check_vendor_query = tep_db_query("SELECT products_id FROM ".TABLE_PRODUCTS_GROUPS." WHERE products_id = '".$products_id."' AND customers_group_id = '2'");

		// # check if our retail price exists
		$check_retail_query = tep_db_query("SELECT products_id FROM ".TABLE_PRODUCTS_GROUPS." WHERE products_id = '".$products_id."' AND customers_group_id = '0'");

		// # if vendor price DOES exist
		if(tep_db_num_rows($check_vendor_query) > 0) { 

			// # also update pricing group table.
			tep_db_query("UPDATE ".TABLE_PRODUCTS_GROUPS." 
						  SET customers_group_price = '" . $vendor_price."'
						  WHERE products_id = '".$products_id."'
						  AND customers_group_id = '2'
						 "); 
		} else { // # if vendor price does NOT exist

			// # if retail price DOES exist
			if(tep_db_num_rows($check_retail_query) > 0) {

				// # insert as vendor base price into pricing group table.
				tep_db_query("INSERT IGNORE INTO ".TABLE_PRODUCTS_GROUPS." 
							  SET customers_group_price = '" . $vendor_price."',
							  customers_group_id = '2',
							  products_id = '".$products_id."'
							 ");
			} else { 

				// # if retail  price does NOT exist - get values from products table as base prices
				tep_db_query("INSERT IGNORE INTO ".TABLE_PRODUCTS_GROUPS." (customers_group_id, customers_group_price,products_id)
							  SELECT '0', p.products_price, '".$products_id."'
							  FROM ".TABLE_PRODUCTS." p
							  WHERE p.products_id = '".$products_id."'
							");
	
				tep_db_query("INSERT IGNORE INTO ".TABLE_PRODUCTS_GROUPS." (customers_group_id, customers_group_price,products_id)
							  SELECT '2', p.products_price, '".$products_id."'
							  FROM ".TABLE_PRODUCTS." p
							  WHERE p.products_id = '".$products_id."'
							");				
			}

		}
	}
}

	$cust_group = (!empty($_GET['cust_group']) ? (int)$_GET['cust_group'] : 2);

	switch($cust_group) {

		case '0':
			$group = 'AND pg.customers_group_id = 0';
		break;

    	default:
			$group = " AND pg.customers_group_id = ".$cust_group;	
	}

	if($_GET['action'] == "export")  { 

		$products_ids_string = tep_db_input($_POST['products_ids_string']);


		$products_ids_string = preg_replace(array('/[^\d,]/','/(?<=,),+/','/^,+/','/,+$/'), '', $_POST['products_ids_string']);

		$sortby = tep_db_input($_POST['sortby']);

		$export_query = tep_db_query("SELECT pd.products_name AS 'Product Name', 
											 p.products_model AS 'Model',
											 CONCAT('$', FORMAT(p.products_price_myself, 2)) AS 'Product Cost', 
											 CONCAT('$', FORMAT(p.products_price, 2)) AS 'Retail Price',  
											 CONCAT('$', FORMAT(pg.customers_group_price, 2)) AS 'Dealer Price',
											 CONCAT('$', FORMAT(SUM(pg.customers_group_price - p.products_price_myself), 2)) AS 'Gross Profit (GP)',
											 CONCAT(FORMAT(SUM(((pg.customers_group_price - p.products_price_myself) / pg.customers_group_price) * 100), 2), '%') AS 'GP %',
											 CONCAT('$', FORMAT(SUM(((p.products_price - pg.customers_group_price) / p.products_price) * 100), 2)) AS 'Price Diff from Retail',
											 p.products_quantity AS 'Current Stock', 
											 IF(p.products_free_shipping > 0, 'Yes', 'No') AS 'Shipped Free'
						   			   FROM " . TABLE_PRODUCTS . " p
						   			   LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
						   			   LEFT JOIN ".TABLE_PRODUCTS_GROUPS." pg ON pg.products_id = p.products_id
						   			   WHERE pd.language_id = '" . $languages_id. "' 
										". $group ."
						 			   AND p.products_id IN(".$products_ids_string.")
						  			   AND p.products_status = 1 
									   AND (p.products_price > 0 OR pg.customers_group_price > 0)
									   GROUP BY pd.products_id 
									   ORDER BY ".$sortby);

		$filename = SITE_DOMAIN.'_vendor_pricing_'.date('m').'-'.date('Y').'.csv';
		$filename = str_replace('www.','',$filename);

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename=' . $filename);

		$count = mysql_num_fields($export_query);
	
		for ($i = 0; $i < $count; $i++) {
    		$header[] = mysql_field_name($export_query, $i);
		}

		print implode(',', $header) . "\r\n";

		while ($row = tep_db_fetch_array($export_query)) {
    		foreach ($row as $value) {
        		$values[] = '"' . str_replace('"', '""', html_entity_decode(strip_tags($value))) . '"';
	    	}
    	
			print implode(',', $values) . "\r\n";
		    unset($values);
		}

		exit();
		
	}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>

<style type="text/css">

.up {
    cursor: pointer;
    width: 11px;
    height: 11px;
    background: url("images/plusmin.gif") no-repeat scroll 0px 0px transparent;
}

.down {
    cursor: pointer;
    width: 11px;
    height: 11px;
    background: url("images/plusmin.gif") no-repeat scroll 0px -11px transparent;
}

.price_input {
	font:normal 11px arial; 
	width:50px; 
	height:18px; 
	text-align:center;
}

</style>
</head>
<body style="background-color:transparent; margin:0;">

<script type="text/javascript">
$(document).ready( function() {

    var price='';
	var cost=''
	var gp='';
	var gpPercent='';
	var besticon='';
	var diff='';
	var surcharge='';
	var shipping_cost='';
	var final='';
	var newVal ='';

    function change(incr,id,amt) {

		// # live price	
		price = $('#input_vendor_price_'+id);	

        if (price.val() == '' || price.val() <  0.01) {
        	newVal = 0.00;
        } else {
            newVal = parseFloat(amt).toFixed(2);
        }

        if (newVal > 0) {
            price.val(newVal);
        }

		cost = parseFloat($('#cost_price_'+id).val()).toFixed(2);
		//cost = parseFloat(cost.replace('$','')).toFixed(2);

		if($('#div_surcharge_'+id).length) { 
			surcharge = $('#div_surcharge_'+id).text();
			surcharge = parseFloat(surcharge.replace('Surcharge: + $','')).toFixed(2);
		} else {
			surcharge = parseFloat(0).toFixed(2);
		}

		if($('#div_shipping_cost_'+id).length) { 
			shipping_cost = $('#div_shipping_cost_'+id).text();
			if(shipping_cost.search("FREE") > 0) {
				shipping_cost = parseFloat(0).toFixed(2);
			} else {
				shipping_cost = parseFloat(shipping_cost.replace('Shipping: + $','')).toFixed(2);
			}
		} else {
			shipping_cost = parseFloat(0).toFixed(2);
		}

		// # gross profit
		gp = $('#div_gp_'+id).text();
		gp = parseFloat(gp.replace('$','')).toFixed(2);

		
		// # aggregate all charges into one price
		var newPrice = (+(newVal) + +(surcharge) + +(shipping_cost));
		
		var priceWsurcharge = (+(newVal) + +(surcharge));	


		var newGP = parseFloat(newPrice - +(cost)).toFixed(2);

		if(gp != newGP) {
			$('#div_gp_'+id).text('$'+newGP)
		}

		if(newGP < 0) {
			$('#div_gp_'+id).css({'color':'#FF0000'});
		} else {
			$('#div_gp_'+id).css({'color':'#000000'});
		}

		// # gross profit by percentage
		gpPercent = $('#div_gpPercent_'+id).text();
		gpPercent = parseFloat(gpPercent.replace('%','')).toFixed(2);


		final = parseFloat(priceWsurcharge + +(shipping_cost)).toFixed(2);

		newGPpercent = parseFloat(((final - +(cost)) / final) * 100).toFixed(2);

		if(gpPercent != newGP) {
			$('#div_gpPercent_'+id).text(newGPpercent+'%')
		}

		if(newGPpercent < 0) {
			$('#div_gpPercent_'+id).css({'color':'#FF0000'});
		} else {
			$('#div_gpPercent_'+id).css({'color':'#000000'});
		}

		var productsPrice = $('#div_productsPrice_'+id).text();
		productsPrice = parseFloat(productsPrice.replace('$','')).toFixed(2);
	
		// # the difference in percentage
		diff = $('#div_diff_'+id).text();
		diff = parseFloat(diff.replace('%','')).toFixed(2);

		var newDiff = parseFloat((+(productsPrice) - +(priceWsurcharge)) / +(productsPrice) * 100).toFixed(2);

		if(+(priceWsurcharge) < +(productsPrice)) {
			$('#div_diff_'+id).html('<font style="color:green">'+newDiff+'%</font>');
		} else if(+(priceWsurcharge) > +(productsPrice)) {
			$('#div_diff_'+id).html('<font style="color:red">'+newDiff+'%</font>');
		} else {
			$('#div_diff_'+id).html('<font style="color:black">-</font>');
		}
    }

    $('.up').click( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("up_","");

		var incr = 1.00;

		var amt = parseFloat($('#input_vendor_price_'+theid).val()) + incr;

        change(incr, theid, amt);
    });

    $('.down').click( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("down_","");
		var incr = -1.00;

		var amt = parseFloat($('#input_vendor_price_'+theid).val()) + incr;

        change(incr,theid, amt);
    });

	$('.thePrice').change( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("input_vendor_price_","");
		var incr = 0;

		var amt = parseFloat($(this).val()) + incr;

        change(incr,theid, amt);
    });


    $('.productsPrice').dblclick( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("productsPrice_","");

		if($('#div_productsPrice_'+theid).is(":visible")){
			$('#productsPrice_price_'+theid).prop('type', 'text');
			$('#productsPrice_price_'+theid).addClass('price_input');
			$('#productsPrice_price_'+theid).css({'width':'40px'});
			$('#productsPrice_price_'+theid).focus();
			$('#div_productsPrice_'+theid).hide();			
		} else {
			$('#productsPrice_price_'+theid).prop('type', 'hidden');
			$('#div_productsPrice_'+theid).html('$'+$('#productsPrice_price_'+theid).val());
			$('#div_productsPrice_'+theid).show();
		}
    });

	$('.productsPrice_input').bind("change blur", function(e) {
		e.preventDefault(); 

		var theid = $(this).attr("id");
		theid = theid.replace("productsPrice_price_","");	

		$('#div_productsPrice_'+theid).html('$'+$('#productsPrice_price_'+theid).val());

		var incr = 0;
		var amt = parseFloat($('#input_vendor_price_'+theid).val()) + incr;
        change(incr,theid, amt);


		if($('#div_productsPrice_'+theid).is(":hidden")){
			$('#productsPrice_price_'+theid).prop('type', 'hidden');
			$('#div_productsPrice_'+theid).show();	
		}

    });

    $('.theCost').dblclick( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("theCost_","");

		if($('#div_cost_'+theid).is(":visible")){
			$('#cost_price_'+theid).prop('type', 'text');
			$('#cost_price_'+theid).addClass('price_input');
			$('#cost_price_'+theid).css({'width':'40px'});
			$('#cost_price_'+theid).focus();
			$('#div_cost_'+theid).hide();			
		} else {
			$('#cost_price_'+theid).prop('type', 'hidden');
			$('#div_cost_'+theid).html('$'+$('#cost_price_'+theid).val());
			$('#div_cost_'+theid).show();
		}
    });

	$('.cost_input').bind("change blur", function(e) {
		e.preventDefault(); 

		var theid = $(this).attr("id");
		theid = theid.replace("cost_price_","");	

		$('#div_cost_'+theid).html('$'+$('#cost_price_'+theid).val());

		var incr = 0;
		var amt = parseFloat($('#input_vendor_price_'+theid).val()) + incr;
        change(incr,theid, amt);


		if($('#div_cost_'+theid).is(":hidden")){
			$('#cost_price_'+theid).prop('type', 'hidden');
			$('#div_cost_'+theid).show();	
		}

    });


    $('.productsMSRP').dblclick( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("productsMSRP_","");

		if($('#div_productsMSRP_'+theid).is(":visible")){
			$('#productsMSRP_msrp_'+theid).prop('type', 'text');
			$('#productsMSRP_msrp_'+theid).addClass('price_input');
			$('#productsMSRP_msrp_'+theid).css({'width':'40px'});
			$('#productsMSRP_msrp_'+theid).focus();
			$('#div_productsMSRP_'+theid).hide();			
		} else {
			$('#productsMSRP_msrp_'+theid).prop('type', 'hidden');
			$('#div_productsMSRP_'+theid).html('$'+$('#productsMSRP_msrp_'+theid).val());
			$('#div_productsMSRP_'+theid).show();
		}
    });

	$('.productsMSRP_input').bind("change blur", function(e) {
		e.preventDefault(); 

		var theid = $(this).attr("id");
		theid = theid.replace("productsMSRP_msrp_","");	

		var productsPrice = parseFloat($('#productsPrice_price_'+theid).val());
		var productsMSRP = parseFloat($('#productsMSRP_msrp_'+theid).val());

		$('#div_productsMSRP_'+theid).html('MSRP: $' + productsMSRP);

		if($('#div_productsMSRP_'+theid).is(":hidden")){
			$('#productsMSRP_msrp_'+theid).prop('type', 'hidden');
			$('#div_productsMSRP_'+theid).show();	
		}

		if(productsPrice > productsMSRP && productsMSRP > 0) {
			$('#productsMSRP_msrp_'+theid).css({'color':'red'});
			$('#div_productsMSRP_'+theid).css({'color':'red'});
		} else { 
			$('#productsMSRP_msrp_'+theid).css({'color':'black'});
			$('#div_productsMSRP_'+theid).css({'color':'black'});
		}



    });	
});
</script>

<?php require(DIR_WS_INCLUDES . 'header.php'); 


	switch($sort) {

		case 'sortbynameDESC':
			$sortby = 'pd.products_name DESC, p.products_model';
		break;

		case 'sortbymodelASC':
			$sortby = 'p.products_model ASC, pd.products_name';
		break;

		case 'sortbymodelDESC':
			$sortby = 'p.products_model DESC, pd.products_name';
		break;

		case 'sortbycostASC':
			$sortby = 'p.products_price_myself ASC, pd.products_name';
		break;

		case 'sortbycostDESC':
			$sortby = 'p.products_price_myself DESC, pd.products_name';
		break;

		case 'sortbyvendorpriceASC':
			$sortby = 'vendor_price ASC, pd.products_name';
		break;

		case 'sortbyvendorpriceDESC':
			$sortby = 'vendor_price DESC, pd.products_name';
		break;

		case 'sortbyretailpriceASC':
			$sortby = 'p.products_price ASC, pd.products_name';
		break;

		case 'sortbyretailpriceDESC':
			$sortby = 'p.products_price DESC, pd.products_name';
		break;

		case 'sortbygpASC':
			$sortby = 'gp ASC, pd.products_name';
		break;

		case 'sortbygpDESC':
			$sortby = 'gp DESC, pd.products_name';
		break;

		case 'sortbygppercentASC':
			$sortby = 'gpPercent ASC, pd.products_name';
		break;

		case 'sortbygppercentDESC':
			$sortby = 'gpPercent DESC, pd.products_name';
		break;

		case 'sortbydiffASC':
			$sortby = 'diff ASC, pd.products_name';
		break;

		case 'sortbydiffDESC':
			$sortby = 'diff DESC, pd.products_name';
		break;

    	default:
			$sortby = 'pd.products_name ASC, p.products_model';
	}


	$customers_groups_query = tep_db_query("SELECT customers_group_id, customers_group_name 
												FROM " . TABLE_CUSTOMERS_GROUPS . " 
												WHERE customers_group_id > 1
												ORDER BY customers_group_id
											   ");

		while ($existing_customers_groups =  tep_db_fetch_array($customers_groups_query)) {

			$existing_customers_groups_array[] = array("id" => $existing_customers_groups['customers_group_id'], 
													   "text" => $existing_customers_groups['customers_group_name']
													  );
		}

		$grp_select = array();

		$count_groups_query = tep_db_query("SELECT customers_group_id, COUNT(*) AS count 
											FROM " . TABLE_CUSTOMERS . " 
											WHERE customers_group_id > 1
											GROUP BY customers_group_id 
											ORDER BY count DESC
										   ");

		while ($count_groups = tep_db_fetch_array($count_groups_query)) {

			for ($n = 0; $n < sizeof($existing_customers_groups_array); $n++) {

				if($count_groups['customers_group_id'] == $existing_customers_groups_array[$n]['id']) {
					$count_groups['customers_group_name'] = $existing_customers_groups_array[$n]['text'];
				}
			}

			$count_groups_array[] = array("id" => $count_groups['customers_group_id'], 
										  "number_in_group" => $count_groups['count'], 
										  "name" => $count_groups['customers_group_name']
										 ); 

			$grp_select[] = array("id" => $count_groups['customers_group_id'], "text" => $count_groups['customers_group_name']); 		
		}

	$cust_group_name = mysql_result(mysql_query("SELECT customers_group_name FROM " . TABLE_CUSTOMERS_GROUPS . " WHERE customers_group_id = ".$cust_group),0);
?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td> <table><tr><td width="53"><img src="/admin/images/icons/vendors.png" width="48" height="48"></td><td class="pageHeading"><?php echo HEADING_TITLE; ?></td></tr></table></td>
            <td align="right">

	<form name="cust_group" method="get" action="<?php echo $_SERVER['PHP_SELF'].'?'.(isset($_GET['cust_group']) ? 'cust_group='.$_GET['cust_group'] : '') . (isset($_GET['show']) && $_GET['show'] == 'all' ? '&show=all' : '').(isset($_GET['sort']) ? '&sort='.$_GET['sort'] : '');?>">
				<?php if($_GET['vendor'] == 1) { ?>
					<input type="hidden" name="vendors" value="1">
				<?php } ?>
			<span style="font:bold 11px arial"> Select Pricing Group:</span> &nbsp; <?php echo tep_draw_pull_down_menu('cust_group',$grp_select,$_GET['cust_group'],'onchange="this.form.submit()"')?>
		</form>

</td>
      </tr>
      <tr>
        <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top">

<form name="stockForm" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?action=update<?php echo (isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['sort']) ? '&sort='.$_GET['sort'].'' : '');?>">
		<table border="0" width="100%" cellspacing="0" cellpadding="5">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == '' ? 'sortbynameDESC':'').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">Product Info</a></td>
                <td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbymodelASC' ? 'sortbymodelDESC':'sortbymodelASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">Model</a></td>
				<td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbycostASC' ? 'sortbycostDESC':'sortbycostASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">Cost</a></td>
				<td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbyvendorpriceASC' ? 'sortbyvendorpriceDESC':'sortbyvendorpriceASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo $cust_group_name;?> Price</a></td>
				<td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbyretailpriceASC' ? 'sortbyretailpriceDESC':'sortbyretailpriceASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">Retail Price</a></td>
				<td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbygpASC' ? 'sortbygpDESC':'sortbygpASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">GP</a></td>
				<td class="dataTableHeadingContent" align="center"><a href=""><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbygppercentASC' ? 'sortbygppercentDESC':'sortbygppercentASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">GP%</a></td>
                <td class="dataTableHeadingContent" align="center"><a href=""><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbydiffASC' ? 'sortbydiffDESC':'sortbydiffASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">Retail Diff.</a></td>
              </tr>
<?php

	$products_ids_query = "
		SELECT
            p.products_id,
            p.products_quantity,
			pd.products_name,
            count(subp.products_id) as total_sub
        FROM
            ".TABLE_PRODUCTS." p
        LEFT JOIN
            ".TABLE_PRODUCTS." subp ON (p.products_id = subp.master_products_id 
				and (subp.master_products_id <> subp.products_id and subp.master_products_id <> 0))
		LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id = pd.products_id
        WHERE
            p.products_status = 1
        GROUP BY p.products_id
		ORDER BY p.products_quantity, pd.products_name ASC";

	$result = tep_db_query($products_ids_query);

	while ($row = tep_db_fetch_array($result)) {
		if ($row['total_sub'] > 0) continue;
		$products_ids[] = $row['products_id'];

	}

	$products_ids_string = 0;

	if (is_array($products_ids) && count($products_ids)) {
		$products_ids_string = implode(',', $products_ids);
	}


	if ($_GET['page'] > 1) {
		$rows = ($_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS);
	}

	// # Add products which don't exist in Vendor pricing_groups
	tep_db_query("INSERT IGNORE INTO ".TABLE_PRODUCTS_GROUPS." (customers_group_id, customers_group_price, products_id)
				  SELECT '2', customers_group_price, products_id
				  FROM ".TABLE_PRODUCTS_GROUPS."
				  WHERE products_id = products_id
				  AND customers_group_id = 0
				");

	$products_query_raw = "SELECT p.products_id, 
							p.products_status, 
							p.products_free_shipping, 
							p.products_price_myself, 
							p.products_price, 
							p.master_products_id, 
							p.products_model, 
							p.products_quantity, 
							pd.products_name, 
							pg.customers_group_price AS vendor_price,
							spg.products_msrp,
							SUM(pg.customers_group_price - p.products_price_myself) AS gp,
							SUM(((pg.customers_group_price - p.products_price_myself) / pg.customers_group_price) * 100) AS gpPercent,
							SUM(((p.products_price - pg.customers_group_price) / p.products_price) * 100) AS diff
						   FROM " . TABLE_PRODUCTS . " p
						   LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
						   LEFT JOIN ".TABLE_PRODUCTS_GROUPS." pg ON pg.products_id = p.products_id
						   LEFT JOIN suppliers_products_groups spg ON spg.products_id = p.products_id
						   WHERE pd.language_id = '" . $languages_id. "' 
							". $group ."
						   AND p.products_id IN($products_ids_string) 
						   AND p.products_status = 1 
						   AND (p.products_price > 0 OR pg.customers_group_price > 0)
						   GROUP BY pd.products_id 
						   ORDER BY ".$sortby;
 
	$products_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $products_query_raw, $products_query_numrows);

 	$products_query = tep_db_query($products_query_raw);

	if(tep_db_num_rows($products_query) > 0) { 

		while($products = tep_db_fetch_array($products_query)) {
		    $rows++;
	
			if ($products['master_products_id'] == 0) {
				$products['master_products_id'] = $products['products_id'];
			}

			$attributes = _get_attribute_values_list($products['products_id']);

			if(is_array($attributes)) {
				$attributes_string = array();
			
				while (list($id, $value) = each($attributes)) {
					$attributes_string[]= "$id:" . ((is_array($value))?implode(',', $value):$value);
				}

				if (!empty($attributes_string)) {
					$products['products_name'] .= " (".join('; ',$attributes_string).")";
				}
			}

			if (strlen($rows) < 2) {
    		  $rows = '0' . $rows;
	    	}

			$products_price = $products['products_price'];
			$products_msrp = $products['products_msrp'];
			$vendor_price = $products['vendor_price'];
?>
			<tr class="dataTableRow <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd');?>">
                <td class="dataTableContent" valign="top" style="padding:10px 5px; font:normal 10px arial;">
<?php 
	echo $products['products_name'] . '<br>';
	echo '<br><br> <b>Current Stock: <b style="font:bold 11px arial;">'. max($products['products_quantity'],0).'</b></b>';
?>
<?php // echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=new_product_preview&read=only&pID=' . $products['master_products_id'] . '&origin=' . FILENAME_STATS_PRODUCTS_BACKORDERED . '?page=' . $_GET['page'], 'SSL') . '" style="font:normal 11px arial">' . $products['products_name'] . '</a>';?>

</td>

<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px;"><?php echo $products['products_model'];?></td>

<td class="dataTableContent theCost" valign="top" style="width:50px; text-align:center; padding:10px 5px 0 5px;" id="theCost_<?php echo $products['products_id']?>">
<div id="div_cost_<?php echo $products['products_id']?>">
$<?php echo number_format($products['products_price_myself'],2);?>
</div>
<?php echo tep_draw_hidden_field('cost_price['.$products['products_id'].']', number_format($products['products_price_myself'],2), 'id="cost_price_'.$products['products_id'].'" class="cost_input"');?>
</td>

<td class="dataTableContent" valign="top" style="color:#FF0000; font:normal 10px arial; text-align:center; padding: 10px 5px" nowrap>
<table align="center" style="height:25px; padding-bottom:5px;" cellpadding="0" cellspacing="0" border="0">
<tr><td>
<?php echo '<input name="vendor_price['.$products['products_id'].']" value="'.number_format($vendor_price,2).'" id="input_vendor_price_'.$products['products_id'].'" class="thePrice price_input">';?>
</td>
<td>
<div class="up" id="up_<?php echo $products['products_id']?>"></div>
<div class="down" id="down_<?php echo $products['products_id']?>"></div>
</td>
</tr>
</table>
<?php 

if($products['products_free_shipping'] == 1){
	echo '<div id="div_shipping_cost_'.$products['products_id'].'">Shipping: FREE</div>';
}

?>
</td>
<td class="dataTableContent" valign="top" nowrap>
	<table width="100%" cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td valign="top" class="productsPrice" style="text-align:center; padding:5px 4px 10px 4px" id="productsPrice_<?php echo $products['products_id']?>">

				<div id="div_productsPrice_<?php echo $products['products_id']?>"><?php echo '$'.number_format($products_price,2); ?></div>

				<?php echo tep_draw_hidden_field('products_price['.$products['products_id'].']', number_format($products['products_price'],2), 'id="productsPrice_price_'.$products['products_id'].'" class="productsPrice_input"');?>

			</td>
		</tr>
		<tr>
			<td class="productsMSRP" style="text-align:center; padding:10px 4px; font:normal 10px tahoma;<?php echo ($products_price > $products_msrp && $products_msrp > 0 ? ' color:red;' : ' color:black;');?>" id="productsMSRP_<?php echo $products['products_id']?>">
				<div id="div_productsMSRP_<?php echo $products['products_id']?>">MSRP: <?php echo '$'.number_format($products_msrp,2); ?></div>

				<?php echo tep_draw_hidden_field('products_msrp['.$products['products_id'].']', number_format($products['products_msrp'],2), 'id="productsMSRP_msrp_'.$products['products_id'].'" class="productsMSRP_input"');?>
			</td>
		</tr>
	</table>
</td>

<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px">
<div id="div_gp_<?php echo $products['products_id']?>">
	<?php echo '$'.number_format(($vendor_price - $products['products_price_myself']), 2); ?>
</div>
</td>
<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px">
<div id="div_gpPercent_<?php echo $products['products_id']?>">
<?php 

	if($vendor_price > 0) { 
		echo number_format( (($vendor_price - $products['products_price_myself']) / $vendor_price) * 100, 2). '%';
	} else {
		echo 'no pricing';
	}
?>
</div>
</td>

<td class="dataTableContent" align="center" width="65" style="padding: 10px 5px" valign="top">
<div id="div_diff_<?php echo $products['products_id']?>" style="color:#000;">
<?php
	if($products_price > 0 && $products_price > $vendor_price) {
		echo '<font style="color:green;">' .number_format((($products_price - $vendor_price) / $products_price) * 100, 2). '%</font>';
	} elseif($products_price > 0 && $products_price < $vendor_price) { 
		echo '<font style="color:red;">' .number_format((($products_price - $vendor_price) / $products_price) * 100, 2). '%</font>';
	} else {
		echo ' - ';
	}	
?>
</div>
</td>
</tr>
<?php } ?>
<tr>
<td colspan="7"></td> <td align="center"><input type="submit" name="action" value="Update All"></td>
</tr>
<?php } else { ?>

<tr><td colspan="8" class="dataTableContent">There are no products assigned to a Vendor level pricing group. Please assign products to the group and try again.</td></tr>

<?php } ?>
            </table>

</form>

</td>
          </tr>
          <tr>
            <td colspan="3">
<form action="vendor_pricing_report.php?action=export" method="post">
<input type="submit" value="Download CSV" name="export">
<input type="hidden" name="products_ids_string" value="<?php echo $products_ids_string;?>">
<input type="hidden" name="sortby" value="<?php echo $sortby;?>">
</form>

<table border="0" width="100%" cellspacing="0" cellpadding="5" style="margin:10px 0 0 0">
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?> 

<?php
if(tep_db_num_rows($products_query) > 0) { 
	if(isset($_GET['show']) && $_GET['show'] == 'all'){
		echo ' &nbsp;|&nbsp; <a href="'.$PHP_SELF.'"><b>Back to paging</b></a>';
	} else { 
		echo ' &nbsp;|&nbsp; <a href="'.$PHP_SELF.'?show=all"><b>Show All</b></a>';
	}
}
?>

</td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>