<?php
/*
  Copyright IntenseCart eCommerce (c) 2007 
*/

  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
	
	$update_product_weight = false;
	$product_weight = 0;
	if(isset($_POST['submitbutton_x']) || isset($_POST['submitbutton_y'])) { // "save" button pressed
	  // validate form
	  $error = false;
	  if (isset($_POST['new_item']['insert'])) {
	    if (round((float)$_POST['new_item']['length'], 2) <= 0) {
	      $error = true;
	      $messageStack->add(ERROR_INVALID_LENGTH, 'error');
	    }
	    if (round((float)$_POST['new_item']['width'], 2) <= 0) {
	      $error = true;
	      $messageStack->add(ERROR_INVALID_WIDTH, 'error');
	    }
	    if (round((float)$_POST['new_item']['height'], 2) <= 0) {
	      $error = true;
	      $messageStack->add(ERROR_INVALID_HEIGHT, 'error');
	    }
	    if (round((float)$_POST['new_item']['products_weight'], 2) <= 0) {
	      $error = true;
	      $messageStack->add(ERROR_INVALID_WEIGHT, 'error');
	    }
	    $val_frac = round((float)$_POST['new_item']['value_fraction'], 5);
	    if (($val_frac < 0) || ($val_frac >= 1)) {
	      $error = true;
	      $messageStack->add(ERROR_INVALID_FRACTION, 'error');
	    }
	  }
    if (isset($_POST['split_item']) && tep_not_null($_POST['split_item'])) {
      foreach($_POST['split_item'] as $id => $subarray) {
        if (!isset($subarray['del'])) { // only validate if not being deleted
    	    if (round((float)$subarray['length'], 2) <= 0) {
	          $error = true;
	          $messageStack->add(ERROR_INVALID_LENGTH, 'error');
    	    }
    	    if (round((float)$subarray['width'], 2) <= 0) {
	          $error = true;
	          $messageStack->add(ERROR_INVALID_WIDTH, 'error');
    	    }
    	    if (round((float)$subarray['height'], 2) <= 0) {
	          $error = true;
	          $messageStack->add(ERROR_INVALID_HEIGHT, 'error');
    	    }
    	    if (round((float)$subarray['products_weight'], 2) <= 0) {
	          $error = true;
	          $messageStack->add(ERROR_INVALID_WEIGHT, 'error');
    	    }
    	    $val_frac = round((float)$subarray['value_fraction'], 5);
	        if (($val_frac < 0) || ($val_frac >= 1)) {
	          $error = true;
    	      $messageStack->add(ERROR_INVALID_FRACTION, 'error');
	        }
        }
      }
    }
	  if (!$error) { // update changes if no errors found
      if (isset($_POST['new_item']['insert'])) { // checkbox for new item checked
        $insert_new_item_sql_data = array('id' => 'NULL',
		  	   'products_id' => (int)$_GET['pid'],
           'products_length' => round((float)$_POST['new_item']['length'], 2),
           'products_width' => round((float)$_POST['new_item']['width'], 2),
           'products_height' => round((float)$_POST['new_item']['height'], 2),
           'products_ready_to_ship' => (int)$_POST['new_item']['ready_to_ship'],
           'value_fraction' => round((float)$_POST['new_item']['value_fraction'], 5),
           'products_weight' => round((float)$_POST['new_item']['products_weight'], 2));
        tep_db_perform(TABLE_PRODUCTS_SPLIT, $insert_new_item_sql_data);
        $product_weight += round((float)$_POST['new_item']['products_weight'], 2);
        $update_product_weight = true;
      } // end if (isset($_POST['new_item']['insert']
      if (isset($_POST['split_item']) && tep_not_null($_POST['split_item'])) {
         foreach($_POST['split_item'] as $id => $subarray) {
           if (isset($subarray['del'])) {
             tep_db_query("delete from " . TABLE_PRODUCTS_SPLIT . " where id = '" . (int)$id . "' and products_id = '" . (int)$_GET['pid'] . "'");
           } else {
             $sql_data_array = array(); // make sure other values are removed
             $sql_data_array = array('products_length' => round((float)$subarray['length'], 2),
               'products_width' => round((float)$subarray['width'], 2),
               'products_height' => round((float)$subarray['height'], 2),
               'products_ready_to_ship' => (int)$subarray['ready_to_ship'],
               'value_fraction' => round((float)$subarray['value_fraction'], 5),
               'products_weight' => round((float)$subarray['products_weight'], 2));
             tep_db_perform(TABLE_PRODUCTS_SPLIT, $sql_data_array, 'update', "id = '" . (int)$id . "' and products_id = '" . (int)$_GET['pid'] . "'");
             $product_weight += round((float)$subarray['products_weight'], 2);
             $update_product_weight = true;
           }
         } // end foreach($_POST['split_item'] as $id => $subarray)
      } // end if (isset($_POST['split_item']) && tep_not_null($_POST['split_item']))
      $messageStack->add(NUMBER_OF_SAVES . (isset($_POST['no_of_saves']) ? (int)$_POST['no_of_saves']+1 : 0), 'success'); 
    } // end if not error
	} // end if(isset($_POST['submitbutton_x']) || isset($_POST['submitbutton_y']))
	if ($update_product_weight) // piece values have changed, update product weight with total of all piece weights combined
	  tep_db_query("update " . TABLE_PRODUCTS . " set products_weight = '" . tep_db_input($product_weight) . "' where products_id = " . (int)$_GET['pid']);
if (!isset($_GET['pid'])) {
  $messageStack->add(ERROR_NO_PRODUCT_ID, 'error');
} elseif (isset($_GET['pid']) && !tep_not_null($_GET['pid'])) {
  $messageStack->add(ERROR_NO_PRODUCT_ID, 'error');
} else {
  $check_query = tep_db_query("select products_weight from " . TABLE_PRODUCTS . " where products_id = " . (int)$_GET['pid']);
  if (tep_db_num_rows($check_query) > 0) {
    $product_id = (int)$_GET['pid'];
  } else {
    $messageStack->add(ERROR_PRODUCT_NOT_FOUND, 'error');
  }
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta name="robots" content="noindex, nofollow">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" type="text/javascript">
// trimWhitespace part of: Form Validation Functions  v1.1.6
// http://www.dithered.com/javascript/form_validation/index.html (2007/05/15: no longer functional )
// code by Chris Nott (chris@NOSPAMdithered.com - remove NOSPAM)
// Remove leading and trailing whitespace from a string

function trimWhitespace(string) {
	var newString  = '';
	var substring  = '';
	beginningFound = false;
	// copy characters over to a new string
	// retain whitespace characters if they are between other characters
	for (var i = 0; i < string.length; i++) {
		// copy non-whitespace characters
		if (string.charAt(i) != ' ' && string.charCodeAt(i) != 9) {
			// if the temporary string contains some whitespace characters, copy them first
			if (substring != '') {
				newString += substring;
				substring = '';
			}
			newString += string.charAt(i);
			if (beginningFound == false) beginningFound = true;
		}
		// hold whitespace characters in a temporary string if they follow a non-whitespace character
		else if (beginningFound == true) substring += string.charAt(i);
	}
	return newString;
}

function fractionCheck(fieldName, fieldValue) {
  fieldValue = trimWhitespace(fieldValue);
  if (isNaN(fieldValue) ) {
    alert(fieldValue + "<?php echo JS_ERROR_NOT_A_VALID_NUMBER; ?>");
    fieldName.focus();
    fieldName.select(); 
    return false;
  } else if (fieldValue=='') {
    return true;
  } else if (fieldValue >= 1) {
    alert(fieldValue + "<?php echo JR_ERROR_FRACTION_LARGER_THAN_ONE; ?>");
    fieldName.focus();
    fieldName.select(); 
    return false;
  } else if (fieldValue < 0) {
    alert("<?php echo ERROR_NON_NEGATIVE; ?>");
    fieldName.focus();
    fieldName.select(); 
    return false;
  }
  return true;
}
function ValueCheck(fieldName, fieldValue) {
  fieldValue = trimWhitespace(fieldValue);
  if (isNaN(fieldValue) ) {
    alert(fieldValue + "<?php echo JS_ERROR_NOT_A_VALID_NUMBER; ?>");
    fieldName.focus();
    fieldName.select(); 
    return false;
  } else if (fieldValue=='') {
    return true;
  } else if (fieldValue <= 0) {
    alert("<?php echo ERROR_NOT_ZERO; ?>");
    fieldName.focus();
    fieldName.select(); 
    return false;
  }
  return true;
}
</script>
</head>
<body marginwidth="1" marginheight="1" topmargin="1" bottommargin="1" leftmargin="1" rightmargin="1" bgcolor="#FFFFFF">
<?php
  if ($messageStack->size > 0) {
    echo $messageStack->output();
  }

	if (isset($product_id)) {
		  $split_product_query = tep_db_query("select ps.id, ps.products_id, p.products_price, p.products_weight as total_weight, ps.products_length, ps.products_width, ps.products_height, ps.products_ready_to_ship, ps.products_weight, ps.value_fraction, pd.products_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_SPLIT . " ps where pd.products_id = ps.products_id and pd.products_id = p.products_id and p.products_id = '" . $product_id . "' and language_id = '" . $languages_id . "'");
       while ($_split_product = tep_db_fetch_array($split_product_query)) {
         $split_product[] = $_split_product;
       }

  $no_of_items = count($split_product);
	$value_fraction_total = 0;
  
  if ($no_of_items < 1) {
    $product_query = tep_db_query("select p.products_id, p.products_price, p.products_weight as total_weight, pd.products_name, p.products_length, p.products_width, p.products_height, p.products_ready_to_ship, p.products_weight from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and p.products_id = '" . $product_id . "' and language_id = '" . $languages_id . "'");
    $product = tep_db_fetch_array($product_query);
    $products_name = $product['products_name'];
    $products_price = $product['products_price'];
    $total_weight = $product['total_weight'];
  }  else {
    $products_name = $split_product[0]['products_name'];
    $products_price = $split_product[0]['products_price'];
    $total_weight = $split_product[0]['total_weight'];
    foreach ($split_product as $item) {
      $value_fraction_total += $item['value_fraction'];
    }
  } // end if/else ($no_of_items < 1)
  $ready_to_ship_array = array(array('id' => '0', 'text' => 'no'),
                              array('id' => '1', 'text' => 'yes'));

?>
<div style="margin-top: 5px; margin-left: 20px;" id="product_details" name="product_details">
<p class="pageHeading" style="margin-bottom: 0px; padding: 2px;"><?php echo HEADING_TITLE; ?></p>
<p class="main" style="margin-bottom: 0px; padding: 2px;"><?php echo TEXT_INFO; ?></p>
<?php 
  echo '<form name="split_products" action="' . tep_href_link(FILENAME_SPLIT_PRODUCT,'pid=' . $product_id, 'NONSSL') . '"  method="post">' ."\n";
  if (isset($_POST['no_of_saves'])) {
	  $noofsaves = (int)$_POST['no_of_saves']+1;
  } else {
    $noofsaves = '0';
	}
  echo tep_draw_hidden_field('no_of_saves', $noofsaves) . "\n";
  echo tep_draw_hidden_field('products_id', $product_id) . "\n";
?>
<table border="0" cellspacing="0" cellpadding="2">
   <tr>
      <td class="main"><?php echo TEXT_PRODUCTS_NAME; ?></td>
      <td class="main"><?php echo $products_name; ?></td>
   </tr>
   <tr>
      <td class="main"><?php echo TEXT_PRODUCTS_PRICE_NET; ?></td>
      <td class="main"><?php echo $currencies->display_price($products_price, 0); ?></td>
   </tr>
   <tr>
      <td class="main"><?php echo TEXT_PRODUCTS_WEIGHT; ?></td>
      <td class="main"><?php echo $total_weight; ?></td>
   </tr>
   <tr>
      <td class="main"><?php echo TEXT_TOTAL_FRACTION; ?></td>
      <td class="main"><?php echo $value_fraction_total . VF_EXPLAIN; ?></td>
   </tr>
</table>
</div>
<div align="center" style="margin-top: 10px;" id="product_split" name="product_split">
<table border="0" cellspacing="0" cellpadding="2">
  <tr>
    <td colspan="10"><?php echo tep_black_line(); ?></td>
  </tr>
  <tr class="dataTableHeadingRow">
    <td class="dataTableHeadingContent" align="center">&nbsp;<?php echo TABLE_HEADING_INSERT; ?>&nbsp;</td>
    <td class="dataTableHeadingContent" align="center">&nbsp;<?php echo TABLE_HEADING_DELETE; ?>&nbsp;</td>
    <td class="dataTableHeadingContent" align="center">&nbsp;<?php echo TABLE_HEADING_READY_TO_SHIP; ?>&nbsp;</td>
    <td class="dataTableHeadingContent">&nbsp;<?php echo TABLE_HEADING_LENGTH; ?>&nbsp;</td>
    <td class="dataTableHeadingContent">&nbsp;<?php echo TABLE_HEADING_WIDTH; ?>&nbsp;</td>
    <td class="dataTableHeadingContent">&nbsp;<?php echo TABLE_HEADING_HEIGHT; ?>&nbsp;</td>
    <td class="dataTableHeadingContent">&nbsp;<?php echo TABLE_HEADING_WEIGHT_AMOUNT; ?>&nbsp;</td>
    <td class="dataTableHeadingContent">&nbsp;<?php echo TABLE_HEADING_VALUE_FRACTION; ?>&nbsp;</td>
    <td class="dataTableHeadingContent" align="right">&nbsp;<?php echo TABLE_HEADING_VALUE_AMOUNT; ?>&nbsp;</td>
    <td class="dataTableHeadingContent" align="right">&nbsp;&nbsp;</td>
  </tr>
  <tr>
    <td colspan="10"><?php echo tep_black_line(); ?></td>
  </tr>
<?php
	$rows = 0;
	for ($x = 0; $x < $no_of_items; $x++) {
		echo '  <tr class="' . (floor($rows/2) == ($rows/2) ? 'attributes-even' : 'attributes-odd') . '">' . "\n";
    echo '    <td class="smallText">&#160;</td>' . "\n";
    echo '    <td class="smallText" align="center">' . tep_draw_checkbox_field('split_item[' . $split_product[$x]['id'] . '][del]')  . '</td>' . "\n";
		echo '    <td class="smallText" align="center">' . tep_draw_pull_down_menu('split_item['. $split_product[$x]['id'] .'][ready_to_ship]', $ready_to_ship_array, (($split_product[$x]['products_ready_to_ship'] == '0') ? '0' : '1')) . "</td>\n";
		echo '    <td class="smallText">' . tep_draw_input_field("split_item[" . $split_product[$x]['id'] . "][length]", $split_product[$x]['products_length'], 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
    echo '    <td class="smallText">' . tep_draw_input_field("split_item[" . $split_product[$x]['id'] . "][width]", $split_product[$x]['products_width'], 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
    echo '    <td class="smallText"' . tep_draw_input_field("split_item[" . $split_product[$x]['id'] . "][height]", $split_product[$x]['products_height'], 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
    echo '    <td class="smallText"' . tep_draw_input_field("split_item[" . $split_product[$x]['id'] . "][products_weight]", $split_product[$x]['products_weight'], 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
    echo '    <td class="smallText"' . tep_draw_input_field("split_item[" . $split_product[$x]['id'] . "][value_fraction]", $split_product[$x]['value_fraction'], 'size="8" onblur="fractionCheck(this, this.value)"') . "</td>\n";
    echo '    <td class="smallText" align="right">' . $currencies->display_price(($split_product[$x]['value_fraction'] * $products_price), 0) . "</td><td></td>\n";
		$rows++;
		echo "  </tr>\n";
	} // end for ($x = 0; $x < $no_of_items; $x++)
  // table row for adding a new item
	echo '  <tr class="' . (floor($rows/2) == ($rows/2) ? 'attributes-even' : 'attributes-odd') . '">' . "\n";
  echo '    <td class="smallText" align="center">' . tep_draw_checkbox_field('new_item[insert]', '', ($no_of_items < 1))  . "</td>\n";
  echo '    <td class="smallText">&#160;</td>' . "\n";
	echo '    <td class="smallText" align="center">' . tep_draw_pull_down_menu('new_item[ready_to_ship]', $ready_to_ship_array, (($no_of_items < 1) ? $product['products_ready_to_ship'] : '0')) . "</td>\n";
	echo '    <td class="smallText"' . tep_draw_input_field("new_item[length]", (($no_of_items < 1) ? $product['products_length'] : ""), 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
  echo '    <td class="smallText"' . tep_draw_input_field("new_item[width]", (($no_of_items < 1) ? $product['products_width'] : ""), 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
  echo '    <td class="smallText"' . tep_draw_input_field("new_item[height]", (($no_of_items < 1) ? $product['products_height'] : ""), 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
  echo '    <td class="smallText"' . tep_draw_input_field("new_item[products_weight]", (($no_of_items < 1) ? $product['products_weight'] : ""), 'size="8" onblur="ValueCheck(this, this.value)"') . "</td>\n";
  echo '    <td class="smallText"' . tep_draw_input_field("new_item[value_fraction]", (string)min((1 - $value_fraction_total), 0.5), 'size="8" onblur="fractionCheck(this, this.value)"') . "</td><td></td><td></td>\n";
  echo "  </tr>\n";
?>
</table>
<?php echo '<p style="margin-top: 10px;">' . tep_image_submit('button_save.gif', IMAGE_SAVE, 'name="submitbutton"') . '&#160;' . tep_image_button('button_cancel.gif', IMAGE_CANCEL, 'onclick=\'self.close()\'') .'</p>' . "\n";
?>
</form>
</div>
<?php
} else { // end if (isset($product_id))
  echo '<div align="center" style="margin-top: 50px;">' . "\n" . '<form name="close">' . "\n" . tep_image_button('button_cancel.gif', IMAGE_CLOSE, 'onclick=\'self.close()\'') .'</form>' . "\n" . '</div>' . "\n";
}
?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
