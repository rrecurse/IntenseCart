<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_ORDER_PROCESS);

  
	function tep_bold_sections($string, $chr) {
    	$chr = preg_replace("/[^a-zA-Z0-9 ]/", "", $chr);
		return preg_replace("/($chr)/i", "<b>\${1}</b>", $string);
	}


	function retrieveCustomerNames($chr) {
    
		$ret_limit = 20;
		$ret = getSearchQuery($chr);

		if (is_array($ret)) {
		
			$result = tep_db_query("SELECT c.customers_id, 
										   c.customers_firstname, 
										   c.customers_lastname,
										   a.entry_company AS company
								  FROM customers c 
								  LEFT JOIN address_book a ON a.address_book_id  = c.customers_default_address_id
								  WHERE ". $ret['query'] ." 
								  LIMIT ". $ret_limit);

      if (tep_db_num_rows($result) <= $ret_limit) {

		echo '<ul>';

        while ($name = tep_db_fetch_array($result)) {
			
			$firstname = ($ret['bold'] != 'last' ? tep_bold_sections($name['customers_firstname'], $ret['chr'][1]) : $name['customers_firstname']);
			$lastname = ($ret['bold'] != 'first' ? tep_bold_sections($name['customers_lastname'], $ret['chr'][0]) : $name['customers_lastname']);
			
	          echo '<li id="cID_' . $name['customers_id'] . '" onClick="updateCID(\'' . $name['customers_lastname'] . ', ' . $name['customers_firstname'] . '\', \'' . $name['customers_id'] . '\')">' . $lastname . ', ' . $firstname . (!empty($name['company']) ? ' - ' . $name['company']  : '') . "</li>";
        }

		echo '</ul>';

      } else {

		echo '<ul>';
        echo '<li>More than '.$ret_limit.' matches</li>';
		echo '</ul>';
      }

    }
	exit();
  }
  
	function getSearchQuery($chr) {
		
		$error = NULL;

		// # A little sanity checking
		$chr = preg_replace('/[^a-zA-Z0-9\*_\-., ]/i', '', $chr);
		$chr = str_replace('*', '%', $chr);
    
		if (stripos($chr, ',') !== false) {

			$chr = explode(',', $chr, 2);
			$chr[0] = trim($chr[0]);
			$chr[1] = trim($chr[1]);

			// # Remove any ending wildcards because they are automatically added
			if ($chr[0] != '' && substr($chr[0], -1) == '%') $chr[0] = trim(substr($chr[0], 0, -1));
			if ($chr[1] != '' && substr($chr[1], -1) == '%') $chr[1] = trim(substr($chr[1], 0, -1));

			if ($chr[0] != '' && $chr[1] != '') {
    	    	$query_string = "customers_firstname LIKE '" . $chr[1] . "%' AND customers_lastname LIKE '" . $chr[0] . "%'";
	    	    $bold_format = 'both';
			} elseif ($chr[0] != '' && $chr[1] == '') {
    		    $query_string = "customers_lastname LIKE '" . $chr[0] . "%'";
        		$bold_format = 'last';
			} elseif ($chr[0] == '' && $chr[1] != '') {
		        $query_string = "customers_firstname LIKE '" . $chr[1] . "%'";
    		    $bold_format = 'first';
			} else {
    	    	$error = true;
			}

	    } else {

			if ($chr != '') {
        		$chr = array($chr, $chr);
	        	$query_string = "customers_firstname LIKE '" . $chr[1] . "%' OR customers_lastname LIKE '" . $chr[0] . "%' OR entry_company LIKE '" . $chr[0] . "%'";
	
				if(preg_match_all('/(\\d)/',$chr[0],$ph)) $query_string.=" OR customers_telephone REGEXP '".join('[^0-9]*',$ph[1])."'";
    			$bold_format = 'both';
			} else {
        		$error = true;
			}
		}
    
		if ($error) {
    		return false;
		} else {
    		return array('query' => $query_string, 'bold' => $bold_format, 'chr' => array($chr[0], $chr[1]));
		}
	
		exit();
	}  


	if($_GET['action'] == 'autoComplete' && !empty($_GET['searchName'])) { 

		retrieveCustomerNames(trim($_GET['searchName']));

	}

	$result = tep_db_query("SELECT code, value FROM " . TABLE_CURRENCIES . " ORDER BY code");
	
	if (tep_db_num_rows($result) > 0) {

 		$selectCurrencyBox = "<select name='Currency'><option value='' SELECTED>" . TEXT_SELECT_CURRENCY . "</option>\n";

 		while($db_Row = tep_db_fetch_array($result)) { 

			$selectCurrencyBox .= "<option value='" . $db_Row["code"] . " , " . $db_Row["value"] . "'" . ($db_Row["code"] == 'USD' ? ' SELECTED' : '');
		  	$selectCurrencyBox .= ">" . $db_Row["code"] . "</option>\n";
		}
		
		$selectCurrencyBox .= "</select>\n";
	}

	
	if(isset($_GET['Customer']) && empty($_GET['Customer'])) {

		$ret = getSearchQuery($_GET['searchName']);

		$customers_id_query = tep_db_query("SELECT c.customers_id FROM customers c LEFT JOIN address_book a ON c.customers_default_address_id=a.address_book_id WHERE " . $ret['query'] . " LIMIT 1");

		$customers_id = (tep_db_num_rows($customers_id_query) > 0 ? tep_db_result($customers_id_query,0) : '');

		$_GET['Customer'] = $customers_id;

	} else if(!empty($_GET['Customer']) || !empty($_GET['Customer_nr'])) {

	 	$account_query = tep_db_query("SELECT * FROM " . TABLE_CUSTOMERS . " WHERE customers_id = '" . $_GET['Customer'] . "'");
 		$account = tep_db_fetch_array($account_query);

 		$customers_id = $account['customers_id'];

 		$address_query = tep_db_query("SELECT * FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . $customers_id . "'");
 		$address = tep_db_fetch_array($address_query);

		$existing_info_query = tep_db_query("SELECT o.billing_name, 
													o.billing_company,
													o.billing_street_address,
													o.billing_suburb,
													o.billing_postcode,
													o.billing_city,
													o.billing_state,
													o.billing_country,
													o.delivery_name,
													o.delivery_company,
													o.delivery_street_address,
													o.delivery_suburb,
													o.delivery_postcode,
													o.delivery_city,
													o.delivery_state,
													o.delivery_country
											  FROM " . TABLE_ORDERS . " o 
											  WHERE o.customers_id = '" . $customers_id . "'
											  ORDER BY o.orders_id DESC
											  LIMIT 1
											 ");

 		$existing_info = tep_db_fetch_array($existing_info_query);

	}

?>	

<!DOCTYPE html>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE ?></title>

<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

<style type="text/css">
li {
	list-style: none;
}
</style>

<script type="text/javascript" src="includes/javascript/prototype.lite.js"></script>

<script type="text/javascript">
  var timeout = '';

  function autocomplet() {
    clearTimeout(timeout);
    timeout = setTimeout('autocomplete_execute()',300);
  }

  function autocomplete_execute() {
    document.quickSearchForm.Customer.value='';

    new ajax ('/admin/create_order.php?action=autoComplete&searchName='+$('searchName').value, {
		method: 'get', update: $('autocomplete_choices')
	});


	if($('autocomplete_choices').innerHTML != "<ul></ul>" || $('autocomplete_choices').innerHTML != "") { 
    	$('autocomplete_choices').style.display = "block";
	} else { 
		$('autocomplete_choices').style.display = "none";
//console.log($('autocomplete_choices').innerHTML);
	}

  }

  function updateCID(cName,cID) {
    $('autocomplete_choices').style.display = "none";
    $('searchName').value = cName;
    $('Customer').value = cID;
  }
</script>
<?php require('includes/form_check.js.php'); ?>
</head>

<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top" colspan="2">
		<table border="0" width="100%">
			<tr>
			  <td style="height:20px; background-color:#6295FD; font:bold 12px arial; color:#FFFFFF;">&nbsp; <b><?php echo TEXT_STEP_1 ;?></b></td>
			</tr>
		</table>

		<table border="0" cellpadding="7">
			<tr>
				<td class="main" valign="top">
				<b>Customer Lookup:</b> <i><small>(Name or Phone or Email)</small></i>
				<br>

				<form name="quickSearchForm" method="GET" action="/admin/create_order.php" autocomplete="off">
					<input type="hidden" id="Customer" name="Customer" value="">
					<input type="text" name="searchName" id="searchName" value="" style="width:200px" onkeyup="autocomplet();">
					<input type="submit" value="Go!" style="width:30px">
					<div id="autocomplete_choices"></div>
				</form>

<?php
	print "<form action='$PHP_SELF' method='GET'>";
	print "<table border='0'>";
	print "<tr>\n";
	print "<td><font class=main><b><br>" . TEXT_OR_BY . "</b></font><br><br><input type=text name='Customer_nr'></td>\n";
	print "<td valign='bottom'><input type='submit' value=\"" . BUTTON_SUBMIT . "\"></td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "</form>\n";

?>	
</td>
		<tr>
        
    <td width="100%" valign="top">
		
		<?php echo tep_draw_form('create_order', FILENAME_CREATE_ORDER_PROCESS, '', 'post', 'onSubmit="return check_form();"'); ?>

	<table border="0" width="100%" cellspacing="0" cellpadding="0">
								  
	 </tr> <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_CREATE; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td>
<?php 
		require(DIR_WS_MODULES . 'create_order_details.php'); 
?>
        </td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo '<a href="javascript:history.go(-1);">' . tep_image_button('button_back.gif', IMAGE_BUTTON_BACK) . '</a>'; ?></td>
            <td class="main" align="right"><?php echo tep_image_submit('button_next.gif', 'Procced to product entry'); ?></td>
          </tr>
        </table></td>
     </tr>
    </table>
</form>
</td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
