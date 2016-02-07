<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_SUPPLY_REQUEST_PROCESS);

	
	$pID = (int)$_GET['pID'];

	// # Get available Suppliers
	$sup_query = tep_db_query("SELECT suppliers_id, suppliers_group_name 
							  FROM " . TABLE_SUPPLIERS . " 
							  ORDER BY suppliers_group_name
							  ");

	// # Get Supplier relevant to products_id (pID) $_GET variable
	$prod_to_sup_query = tep_db_query("SELECT s.suppliers_id, 
											  s.suppliers_group_name
									   FROM " . TABLE_SUPPLIERS . "  s
									   LEFT JOIN suppliers_products_groups spg ON spg.suppliers_group_id = s.suppliers_id
									   WHERE spg.products_id = ".$pID."
									   ORDER BY suppliers_group_name
									  ");
	$prod_to_sup = tep_db_fetch_array($prod_to_sup_query);
	
	$slist = array();

	if (tep_db_num_rows($sup_query) > 0) {
 		while($sup = tep_db_fetch_array($sup_query)) {

			$slist[] = array(id => $sup["suppliers_id"], text => $sup["suppliers_group_name"]);
		}
	}
	
	// # Get available Currencies
	$curr_query = tep_db_query("select code, value from " . TABLE_CURRENCIES . " ORDER BY code");
	$curr_result = $curr_query;
	
	if (tep_db_num_rows($curr_result) > 0) {

 		$currencySelect = '<select name="currency">
								<option value="">' . TEXT_SELECT_CURRENCY . '</option>';

 		while($curr = tep_db_fetch_array($curr_result)) { 

			$currencySelect .= '<option value="' . $curr['code'] . ' ' . number_format($curr['value'],2) . '" '. ($curr["code"] == 'USD' ? ' selected="selected"' : '') .'>' . $curr['code'] . '</option>';
		}
		
		$currencySelect .= "</select>\n";
	}


	if(isset($_GET['suppliers'])) {

	 	$account_query = tep_db_query("SELECT * FROM " . TABLE_SUPPLIERS . " WHERE suppliers_id = '" . $_GET['suppliers'] . "'");
		$account = tep_db_fetch_array($account_query);

 		$suppliers = $account['suppliers_id'];

	} 

	// # Get available Suppliers
	$warehouse_query = tep_db_query("SELECT suppliers_to_warehouse_id, suppliers_to_warehouse_name 
									FROM suppliers_to_warehouse 
									ORDER BY suppliers_to_warehouse_id
									");
	$warehouse_list = array();

	if (tep_db_num_rows($warehouse_query) > 0) {
 		while($warehouse = tep_db_fetch_array($warehouse_query)) {

			$warehouse_list[] = array(id => $warehouse["suppliers_to_warehouse_id"], text => $warehouse["suppliers_to_warehouse_name"]);
		}
	}

?>	


<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo HEADING_TITLE ?></title>
 
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<style type="text/css">
#autocomplete_choices {
	margin: -15px 0 0 -40px; 
	width: 200px; 
	font: norma 12px arial; 
	position: absolute; 
	top: 145px; 
	left: 145px; 
	cursor: pointer;
}

#autocomplete_choices li {
	list-style: none;
	width: 200px;
	background: #FFF;
	border: 1px solid #EFEFEF;
	padding: 2px; 
	border-bottom: 0px; 
}

#autocomplete_choices li:hover {
	background: #E7E9F2; 
	cursor: pointer;
}

#autocomplete_choices li.selected {
	background: #FFF;
}

li { 
	list-style: none;
}
</style>

<script src="includes/javascript/prototype.lite.js" type="text/javascript"></script>

<script type="text/javascript">
var timeout = '';

function autocomplete() {
	clearTimeout(timeout);
	timeout = setTimeout('autocomplete_execute()',300);
}

function autocomplete_execute() {
	document.quickSearchForm.Customer.value='';
    
	new ajax ('/admin/lib_create_supply_request.php?doAction=autoComplete&qs_searchName='+$('qs_searchName').value, {method: 'get', update: $('autocomplete_choices')});
    
	$('autocomplete_choices').style.visibility = "visible";
  }

  function updateCID(cName,sID) {
    $('autocomplete_choices').style.visibility = "hidden";
    $('qs_searchName').value = cName;
    $('supplier').value = sID;
  }

</script>

<?php require('includes/form_check.js.php'); ?>
</head>

<body style="margin:0; background-color:#F0F5FB;">

<?php require(DIR_WS_INCLUDES . 'header.php');?>
	
<table border="0" width="99%" cellspacing="0" cellpadding="0" align="center">
  <tr>
	<td class="pageHeading"><table><tr><td width="53"><img src="/admin/images/icons/supply-icon.png" width="48" height="48"></td><td class="pageHeading"><?php echo HEADING_TITLE; ?></td></tr></table>
	</td>
	</tr>
	<tr>
	<td>
	<table width="100%" cellpadding="5" cellspacing="0" border="0">

		<tr>
			<td style="font:bold 12px arial; color:#FFFFFF; height:20px; background-color:#6295FD;" colspan="2">&nbsp; <?php echo TEXT_STEP_1 ?></td>
		</tr>
		<tr>
			<td class="main" valign="top">

<?php echo tep_draw_form('create_request', FILENAME_CREATE_SUPPLY_REQUEST_PROCESS.(isset($pID) ? '?pID='.$pID : '').(isset($prod_to_sup['suppliers_id']) ? '&supplier_id='.$prod_to_sup['suppliers_id'] : ''), '', 'POST');?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td class="main">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td>			<table border="0" cellspacing="0" cellpadding="2" class="formArea">
                  <tr>
                    <td class="main">&nbsp;Select Supplier:</td>
                    <td class="main"><?php echo tep_draw_pull_down_menu('suppliers_id', $slist, isset($prod_to_sup['suppliers_id']) ? $prod_to_sup['suppliers_id'] : '');?>
					</td>
                  </tr>

                  <tr>
                    <td class="main">&nbsp;Payment Method:</td>
                    <td class="main">
							<select name="payment_method">
								<option value="" selected="selected">Select Method</option>
								<option value="Credit Card">Credit Card</option>
								<option value="Check">Check</option>
								<option value="Terms">Terms / Open Balance</option>
								<option value="Bank Wire">Wire Transfer</option>
							</select>
				</td>
                  </tr>
                  <tr>
                    <td class="main">&nbsp;Select Currency:</td>
                    <td class="main"><?php echo $currencySelect ?></td>
                  </tr>
                </table></td>
		<td>		
				<table border="0" cellspacing="0" cellpadding="2" class="formArea">
                  <tr>
                    <td class="main">&nbsp;Select Delivery Warehouse:</td>
                    <td class="main"><?php echo tep_draw_pull_down_menu('suppliers_to_warehouse_id', $warehouse_list, isset($_GET['suppliers_to_warehouse_id']) ? $_GET['suppliers_to_warehouse_id'] : '');?>
					</td>
                  </tr>
                </table></td>
	</tr>
</table>
	</td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="5">
          <tr>
            <td class="main"><?php echo tep_image_submit('button_confirm.gif', IMAGE_BUTTON_CONFIRM); ?></td>
            <td class="main" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, '', 'SSL') . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_BACK) . '</a>'; ?></td>
          </tr>
        </table></td>
      </tr>
    </table></form></td>
  </tr>
</table></td>
	</tr>
	</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>