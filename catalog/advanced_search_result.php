<?php

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ADVANCED_SEARCH);

	$keywords = (!empty($_REQUEST['keywords'])) ? $_REQUEST['keywords'] : '';

	// # Removes special chars.
	$keywords = preg_replace('/[^0-9a-zA-Z -]/i', '', trim($keywords));

	$categories_id = (int)$_GET['categories_id'];

	// # to help thrawrt Ddos attacks, we've added a 3-second sleep function to prevent db from being overwhelmed.	
	sleep(1);


// # Search enhancement mod start
if(!empty($keywords)) {

	if(!isset($_GET['s'])){
		$pwstr_check = strtolower(substr($keywords, strlen($keywords)-1, strlen($keywords)));

  	    if($pwstr_check == 's'){
  			     $pwstr_replace = substr($keywords, 0, strlen($keywords)-1);
  			     header('location: ' . tep_href_link( FILENAME_ADVANCED_SEARCH_RESULT , 'keywords=' . urlencode($pwstr_replace) . '&search_in_keywords=1&plural=1&s=1'));
  			     exit();
  	    }
    }

		$pw_keywords = explode(' ',stripslashes(strtolower($keywords)));
		$pw_replacement_words = $pw_keywords;
		$pw_boldwords = $pw_keywords;

		$sql_words = tep_db_query("SELECT * FROM searchword_swap");

		$pw_replacement = '';

		while ($sql_words_result = tep_db_fetch_array($sql_words)) {
			   if(stripslashes(strtolower($keywords)) == stripslashes(strtolower($sql_words_result['sws_word']))){
					$pw_replacement = stripslashes($sql_words_result['sws_replacement']);
					$pw_link_text = '<b><i>' . stripslashes($sql_words_result['sws_replacement']) . '</i></b>';
					$pw_phrase = 1;
					$pw_mispell = 1;
					break;
			   }
		    for($i=0; $i<sizeof($pw_keywords); $i++){
		 		if($pw_keywords[$i]  == stripslashes(strtolower($sql_words_result['sws_word']))){
		 			   $pw_replacement_words[$i] = stripslashes($sql_words_result['sws_replacement']);
		 		    $pw_boldwords[$i] = '<b><i>' . stripslashes($sql_words_result['sws_replacement']) . '</i></b>';
		 		    $pw_mispell = 1;
		 		    break;
		 		}
		    }
		}

		tep_db_free_result($sql_words);

		if(!isset($pw_phrase)){
		    for($i=0; $i<sizeof($pw_keywords); $i++){
		 		$pw_replacement .= $pw_replacement_words[$i] . ' ';
					$pw_link_text   .= $pw_boldwords[$i]. ' ';
		    }
		}
		
		$pw_replacement = trim($pw_replacement);
		$pw_link_text   = trim($pw_link_text);
		$pw_string      = '<br><span class="main"><font color="red">' . TEXT_REPLACEMENT_SUGGESTION . '</font><a href="' . tep_href_link( FILENAME_ADVANCED_SEARCH_RESULT , 'keywords=' . urlencode($pw_replacement) . '&search_in_description=1&pfrom=0.01' ) . '">' . $pw_link_text . '</a></span><br><br>';
		 
}
// # END Search enhancement

	$error = false;

	if( empty($keywords) && empty($categories_id) && !is_numeric($_GET['pfrom']) && !is_numeric($_GET['pto']) ) {

		$error = true;
		$messageStack->add_session('search', ERROR_AT_LEAST_ONE_INPUT);

	} elseif(empty($keywords) && empty($categories_id)) { 

		$error = true;
		$messageStack->add_session('search', ERROR_AT_LEAST_ONE_INPUT);


	} else {

 	   $pfrom = '';
 	   $pto = '';

		if(isset($_GET['pfrom'])) $pfrom = $_GET['pfrom'];

		if(isset($_GET['pto'])) $pto = $_GET['pto'];

		$date_check_error = false;


		$price_check_error = false;

		if (tep_not_null($pfrom)) {
			if (!settype($pfrom, 'double')) {
				$error = true;
				$price_check_error = true;
				$messageStack->add_session('search', ERROR_PRICE_FROM_MUST_BE_NUM);
			}
		}

		if (tep_not_null($pto)) {
			if (!settype($pto, 'double')) {
				$error = true;
				$price_check_error = true;

				$messageStack->add_session('search', ERROR_PRICE_TO_MUST_BE_NUM);
			}
		}

    if (($price_check_error == false) && is_float($pfrom) && is_float($pto)) {
      if ($pfrom >= $pto) {
		 $error = true;

		 $messageStack->add_session('search', ERROR_PRICE_TO_LESS_THAN_PRICE_FROM);
      }
    }

    if (tep_not_null($keywords)) {
      if (!tep_parse_search_string($keywords, $search_keywords)) {
		 $error = true;

		 $messageStack->add_session('search', ERROR_INVALID_KEYWORDS);
      }
    }
  }

  if (empty($pfrom) && empty($pto) && empty($keywords) && empty($manufacturers_id) && empty($categories_id)) {

    $error = true;

    $messageStack->add_session('search', ERROR_AT_LEAST_ONE_INPUT);
  }

  if ($error == true) {
    tep_redirect(tep_href_link(FILENAME_ADVANCED_SEARCH, tep_get_all_get_params(), 'SSL', true, false));
  }

	// # Search enhancement mod start
	 $search_enhancements_keywords = tep_db_input($keywords);
	 $search_enhancements_keywords = strip_tags($search_enhancements_keywords);
	 $search_enhancements_keywords = addslashes($search_enhancements_keywords);		 		 
		   
	 if (!empty($search_enhancements_keywords) && $search_enhancements_keywords != $last_search_insert) {

/* // # commented out to free up some resources. Table is far too large as is.
	 		 tep_db_query("INSERT IGNORE INTO search_queries 
						   SET search_text = TRIM('" .  trim($search_enhancements_keywords) . "'),
						   user_ip = '".$_SERVER['REMOTE_ADDR']."'
	 					  ");
*/
	if(!tep_session_is_registered('last_search_insert')) {
		tep_session_register('last_search_insert');
	}

	$last_search_insert = $search_enhancements_keywords;
		 		 }
	// # END Search enhancement


	$breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ADVANCED_SEARCH));
	$breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_ADVANCED_SEARCH_RESULT, tep_get_all_get_params(), 'NONSSL', true, false));
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<title><?php echo TITLE; ?></title>
<script language="javascript">
<!--
function check_form() {

  var error_message = "<?php echo JS_ERROR; ?>";
  var error_found = false;
  var error_field;
  var keywords = document.advanced_search.keywords.value;
  var pfrom = document.advanced_search.pfrom.value;
  var pto = document.advanced_search.pto.value;
  var pfrom_float;
  var pto_float;

  if ( ((keywords == '') || (keywords.length < 1)) && ((pfrom == '') || (pfrom.length < 1)) && ((pto == '') || (pto.length < 1)) ) {
    error_message = error_message + "* <?php echo ERROR_AT_LEAST_ONE_INPUT; ?>\n";
    error_field = document.advanced_search.keywords;
    error_found = true;
  }

  if (pfrom.length > 0) {
    pfrom_float = parseFloat(pfrom);
    if (isNaN(pfrom_float)) {
      error_message = error_message + "* <?php echo ERROR_PRICE_FROM_MUST_BE_NUM; ?>\n";
      error_field = document.advanced_search.pfrom;
      error_found = true;
    }
  } else {
    pfrom_float = 0;
  }

  if (pto.length > 0) {
    pto_float = parseFloat(pto);
    if (isNaN(pto_float)) {
      error_message = error_message + "* <?php echo ERROR_PRICE_TO_MUST_BE_NUM; ?>\n";
      error_field = document.advanced_search.pto;
      error_found = true;
    }
  } else {
    pto_float = 0;
  }

  if ( (pfrom.length > 0) && (pto.length > 0) ) {
    if ( (!isNaN(pfrom_float)) && (!isNaN(pto_float)) && (pto_float < pfrom_float) ) {
      error_message = error_message + "* <?php echo ERROR_PRICE_TO_LESS_THAN_PRICE_FROM; ?>\n";
      error_field = document.advanced_search.pto;
      error_found = true;
    }
  }
  if (error_found == true) {
    alert(error_message);
    error_field.focus();
    return false;
  } else {
    return true;
  }
}
//-->
</script>
</head>
<body style="margin:0">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
		 <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		   <tr>
		     <td class="pageHeading"><?php echo HEADING_TITLE_2; ?></td>
		     <td class="pageHeading" align="right" style="padding:10px;"><?php echo tep_image(DIR_WS_IMAGES . 'table_background_browse.gif', HEADING_TITLE_2, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
		   </tr>
<tr>
		     <td class="main">


		     <?php if (isset($_GET['plural']) && ($_GET['plural'] == '1')) {
		     	  	echo TEXT_REPLACEMENT_SEARCH_RESULTS . ' <b><i>' . stripslashes($keywords) . 's</i></b>';
		      	  } else {
		     		echo TEXT_REPLACEMENT_SEARCH_RESULTS . ' <b><i>' . stripslashes($keywords) . '</i></b>';
		      	  }
		     ?>

</td>
		   </tr>
		 </table></td>
      </tr>
      <tr>
		 <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
		 <td>
<?php

// # create column list
	$define_list = array('PRODUCT_LIST_NAME' => PRODUCT_LIST_NAME,
					     'PRODUCT_LIST_INFO' => PRODUCT_LIST_INFO,
    					   'PRODUCT_LIST_IMAGE' => PRODUCT_LIST_IMAGE
						);

	asort($define_list);

	$column_list = array();

	reset($define_list);

	while (list($key, $value) = each($define_list)) {
		if ($value > 0) $column_list[] = $key;
	}

	if(!tep_session_is_registered('sppc_customer_group_id')) {
		$customer_group_id = '0';
	} else {
    	$customer_group_id = $sppc_customer_group_id;
	}
   


	$select_column_list = 'pd.products_info, ';

	for($i=0, $n=sizeof($column_list); $i<$n; $i++) {
	    switch ($column_list[$i]) {
    		case 'PRODUCT_LIST_MODEL':
			$select_column_list .= 'p.products_model, ';
		break;

		case 'PRODUCT_LIST_INFO':  
			$select_column_list .= 'pd.products_info, ';
    	break;

		case 'PRODUCT_LIST_MANUFACTURER':
			$select_column_list .= 'm.manufacturers_name, ';
		 break;

      case 'PRODUCT_LIST_QUANTITY':
		 $select_column_list .= 'p.products_quantity, ';
		 break;
      case 'PRODUCT_LIST_IMAGE':
		 $select_column_list .= 'p.products_image, ';
		 break;
      case 'PRODUCT_LIST_WEIGHT':
		 $select_column_list .= 'p.products_weight, ';
		 break;
    }
  }

  // BOF Separate Pricing Per Customer
   $status_tmp_product_prices_table = false;
   $status_need_to_get_prices = false;
   // find out if sorting by price has been requested
   if ( (isset($_GET['sort'])) && (preg_match('/[1-8][ad]/i', $_GET['sort'])) && (substr($_GET['sort'], 0, 1) <= sizeof($column_list)) ){
    $_sort_col = substr($_GET['sort'], 0 , 1);
    if ($column_list[$_sort_col-1] == 'PRODUCT_LIST_PRICE') {
      $status_need_to_get_prices = true;
      }
   }

   if ((tep_not_null($pfrom) || tep_not_null($pto) || $status_need_to_get_prices == true) && $customer_group_id != '0') {

	   $product_prices_table = 'products_group_prices_'.$customer_group_id;

   // # the table with product prices for a particular customer group is re-built only a number of times per hour
   // # (setting in /includes/database_tables.php called MAXIMUM_DELAY_UPDATE_PG_PRICES_TABLE, in minutes)
   // # to trigger the update the next function is called (new function that should have been
   // # added to includes/functions/database.php)
   tep_db_check_age_products_group_prices_cg_table($customer_group_id);

   $status_tmp_product_prices_table = true;

   } elseif ((tep_not_null($pfrom) || tep_not_null($pto) || $status_need_to_get_prices == true) && $customer_group_id == '0') {

   // # to be able to sort on retail prices we *need* to get the special prices instead of leaving them
   // # NULL and do product_listing the job of getting the special price
   // # first make sure that table exists and needs no updating

   tep_db_check_age_specials_retail_table();

   $status_tmp_special_prices_table = true;

   } // # end elseif ((tep_not_null($pfrom) || (tep_not_null($pfrom)) && ....

   if ($status_tmp_product_prices_table == true) {

   $select_str = "select distinct ".$select_column_list." m.manufacturers_id, p.manufacturers_id, p.products_id, pd.products_name, tmp_pp.products_price, p.products_tax_class_id, if(tmp_pp.status, tmp_pp.specials_new_products_price, NULL) as specials_new_products_price, IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) as final_price ";
 
  } elseif ($status_tmp_special_prices_table == true) {

$select_str = "select distinct ".$select_column_list." m.manufacturers_id, p.products_id, pd.products_name, p.products_price, p.products_tax_class_id, if(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, if(s.status, s.specials_new_products_price, p.products_price) as final_price ";
  
 } else {

$select_str = "select distinct ".$select_column_list." m.manufacturers_id, p.products_id, pd.products_name, p.products_price, p.products_tax_class_id, NULL as specials_new_products_price, NULL as final_price ";

}


  if ( (DISPLAY_PRICE_WITH_TAX == 'true') && (tep_not_null($pfrom) || tep_not_null($pto)) ) {
    $select_str .= ", SUM(tr.tax_rate) as tax_rate ";
  }

      if ($status_tmp_product_prices_table == true) {

  $from_str = "from ".TABLE_PRODUCTS." p
			   LEFT JOIN ".TABLE_MANUFACTURERS." m ON m.manufacturers_id = p.manufacturers_id
			   INNER JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id = pd.products_id
			   LEFT JOIN ".$product_prices_table." AS tmp_pp ON tmp_pp.products_id = pd.products_id
		 		INNER JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p.products_id = p2c.products_id
			   INNER JOIN ".TABLE_CATEGORIES." c ON p2c.categories_id = c.categories_id";

      } elseif ($status_tmp_special_prices_table == true) {

/*  $from_str = "from " . TABLE_PRODUCTS . " p 
			 LEFT JOIN " . TABLE_MANUFACTURERS . " m USING(manufacturers_id) 
			 LEFT JOIN " . TABLE_SPECIALS_RETAIL_PRICES . " s ON p.products_id = s.products_id"; 
*/ 


   $from_str = "from ".TABLE_PRODUCTS." p 
			   LEFT JOIN ".TABLE_MANUFACTURERS." m USING(manufacturers_id)
			   INNER JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id = pd.products_id
			   LEFT JOIN ".TABLE_SPECIALS_RETAIL_PRICES." s ON p.products_id = s.products_id
			   INNER JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p.products_id = p2c.products_id
			   INNER JOIN ".TABLE_CATEGORIES." c ON p2c.categories_id = c.categories_id";


      } else {

  $from_str = "from ".TABLE_PRODUCTS." p 
			   LEFT JOIN ".TABLE_MANUFACTURERS." m USING(manufacturers_id)
			   INNER JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id = pd.products_id
		 		LEFT JOIN ".TABLE_SPECIALS." s on p.products_id = s.products_id
		 		INNER JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p.products_id = p2c.products_id
			   INNER JOIN ".TABLE_CATEGORIES." c ON p2c.categories_id = c.categories_id";
      }

  // EOF Separate Pricing Per Customer


  if ( (DISPLAY_PRICE_WITH_TAX == 'true') && (tep_not_null($pfrom) || tep_not_null($pto)) ) {
  if (!tep_session_is_registered('customer_country_id')) {
    $customer_country_id = STORE_COUNTRY;
    $customer_zone_id = STORE_ZONE;
  }
  $from_str .= " left join " . TABLE_TAX_RATES . " tr on p.products_tax_class_id = tr.tax_class_id left join " . TABLE_ZONES_TO_GEO_ZONES . " gz on tr.tax_zone_id = gz.geo_zone_id and (gz.zone_country_id is null or gz.zone_country_id = '0' or gz.zone_country_id = '" . (int)$customer_country_id . "') and (gz.zone_id is null or gz.zone_id = '0' or gz.zone_id = '" . (int)$customer_zone_id . "')";
}

//$from_str .= ", " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c";

$where_str = " where p.products_status = '1' and p.products_price > 0 and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id ";

  if (!empty($categories_id)) {
    if (isset($_GET['inc_subcat']) && ($_GET['inc_subcat'] == '1')) {
      $subcategories_array = array();
      tep_get_subcategories($subcategories_array, $categories_id);

      $where_str .= " and p2c.products_id = p.products_id and p2c.products_id = pd.products_id and (p2c.categories_id = '" . $categories_id . "'";

      for ($i=0, $n=sizeof($subcategories_array); $i<$n; $i++ ) {
		 $where_str .= " or p2c.categories_id = '" . (int)$subcategories_array[$i] . "'";
      }

      $where_str .= ")";
    } else {
      $where_str .= " and p2c.products_id = p.products_id and p2c.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '" . $categories_id . "'";
    }
  }

  if (isset($_GET['manufacturers_id']) && tep_not_null($_GET['manufacturers_id'])) {
    $where_str .= " and m.manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "'";
  }

  if (isset($search_keywords) && (sizeof($search_keywords) > 0)) {
    $where_str .= " and (";
    for ($i=0, $n=sizeof($search_keywords); $i<$n; $i++ ) {
      switch ($search_keywords[$i]) {
		 case '(':
		 case ')':
		 case 'and':
		 case 'or':
		   $where_str .= " " . $search_keywords[$i] . " ";
      	break;
      
		default:
		   $keyword = tep_db_prepare_input($search_keywords[$i]);
		   $where_str .= "(pd.products_name like '%" . $keyword . "%' or pd.products_info like '%" . $keyword . "%' or p.products_model like '%" . $keyword . "%' or m.manufacturers_name like '%" . $keyword . "%'";
		   if (isset($_GET['search_in_description']) && ($_GET['search_in_description'] == '1')) $where_str .= " or pd.products_description like '%" . $keyword . "%'";
		   $where_str .= ')';
		 break;
      }
    }
    $where_str .= " )";
  }


  if (tep_not_null($pfrom)) {
    if ($currencies->is_set($currency)) {
      $rate = $currencies->get_value($currency);

      $pfrom = $pfrom / $rate;
    }
  }

  if (tep_not_null($pto)) {
    if (isset($rate)) {
      $pto = $pto / $rate;
    }
  }

	// # BOF Separate Pricing Per Customer
	if ($status_tmp_product_prices_table == true) {

		if (DISPLAY_PRICE_WITH_TAX == 'true') {

			if ($pfrom > 0) $where_str .= " and (IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) * if(gz.geo_zone_id is null, 1, 1 + (tr.tax_rate / 100) ) >= " . (double)$pfrom . ")";
			if ($pto > 0) $where_str .= " and (IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) * if(gz.geo_zone_id is null, 1, 1 + (tr.tax_rate / 100) ) <= " . (double)$pto . ")";

		} else {

			if ($pfrom > 0) $where_str .= " and (IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) >= " . (double)$pfrom . ")";
			if ($pto > 0) $where_str .= " and (IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) <= " . (double)$pto . ")";
		}

	} else { // # $status_tmp_product_prices_table is not true: uses p.products_price instead of cg_products_price
	   // # because in the where clause for the case $status_tmp_special_prices is true, the table
	   // # specials_retail_prices is abbreviated with "s" also we can use the same code for "true" and for "false"

		if (DISPLAY_PRICE_WITH_TAX == 'true') {

			if ($pfrom > 1) $where_str .= " and (IF(s.status AND s.customers_group_id = '" . $customer_group_id . "', s.specials_new_products_price, p.products_price) * if(gz.geo_zone_id is null, 1, 1 + (tr.tax_rate / 100) ) >= " . (double)$pfrom . ")";

			if ($pto > 0) $where_str .= " and (IF(s.status AND s.customers_group_id = '" . $customer_group_id . "', s.specials_new_products_price, p.products_price) * if(gz.geo_zone_id is null, 1, 1 + (tr.tax_rate / 100) ) <= " . (double)$pto . ")";

		} else {
			if ($pfrom > 0) $where_str .= " and (IF(s.status AND s.customers_group_id = '" . $customer_group_id . "', s.specials_new_products_price, p.products_price) >= " . (double)$pfrom . ")";
			if ($pto > 0) $where_str .= " and (IF(s.status AND s.customers_group_id = '" . $customer_group_id . "', s.specials_new_products_price, p.products_price) <= " . (double)$pto . ")";
		}
	} 
	// # EOF Separate Pricing Per Customer

	if ( (DISPLAY_PRICE_WITH_TAX == 'true') && (tep_not_null($pfrom) || tep_not_null($pto)) ) {
		$where_str .= " group by p.products_id, tr.tax_priority";
	}

	if ( (!isset($_GET['sort'])) || (!preg_match('/[1-8][ad]/i', $_GET['sort'])) || (substr($_GET['sort'], 0, 1) > sizeof($column_list)) ) {
    
		for ($i=0, $n=sizeof($column_list); $i<$n; $i++) {
			if ($column_list[$i] == 'PRODUCT_LIST_NAME') {
				$_GET['sort'] = $i+1 . 'a';
				$order_str = ' order by pd.products_name';
				break;
			}
		}

	} else {
    
		$sort_col = substr($_GET['sort'], 0 , 1);
	    $sort_order = substr($_GET['sort'], 1);
    	$order_str = ' order by ';

	    switch ($column_list[$sort_col-1]) {
    		case 'PRODUCT_LIST_MODEL':
				$order_str .= "p.products_model " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
			break;

			case 'PRODUCT_LIST_NAME':
				$order_str .= "pd.products_name " . ($sort_order == 'd' ? "desc" : "");
			break;
    		case 'PRODUCT_LIST_INFO':
				$order_str .= "pd.products_info " . ($sort_order == 'd' ? "desc" : "");
			break;
    		case 'PRODUCT_LIST_MANUFACTURER':
				$order_str .= "m.manufacturers_name " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
			break;
    		case 'PRODUCT_LIST_QUANTITY':
				$order_str .= "p.products_quantity " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
			break;
    		case 'PRODUCT_LIST_IMAGE':
				$order_str .= "pd.products_name";
			break;
    		case 'PRODUCT_LIST_WEIGHT':
				$order_str .= "p.products_weight " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
			break;
    		case 'PRODUCT_LIST_PRICE':
				$order_str .= "final_price " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
			break;
		}
	}
  
	if (isset($_REQUEST['advsearch'])) foreach ($_REQUEST['advsearch'] AS $m=>$arg)  {

		//$mod=IXmodule::module('advsearch_'.$m);
		$mod = tep_module('advsearch_'.$m,'advsearch');

		if (isset($mod)) {
			$sqlf=$mod->searchSQLReqParts($arg);
			if (isset($sqlf['from'])) $from_str.=' '.$sqlf['from'];
			if (isset($sqlf['where'])) $where_str.=' AND ('.$sqlf['where'].')';
		}
	}

	$listing_sql = $select_str . $from_str . $where_str . $order_str;
	$GLOBALS['advSearchSQL']="SELECT pd.*,p.* $from_str $where_str GROUP BY p.master_products_id";

	require(DIR_WS_MODULES . FILENAME_PRODUCT_LISTING);
?>
		 </td>
      </tr>
      <tr>
		 <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
		 <td class="main"><?php echo '<a href="' . tep_href_link(FILENAME_ADVANCED_SEARCH, tep_get_all_get_params(array('sort', 'page')), 'NONSSL', true, false) . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_BACK) . '</a>'; ?></td>
      </tr>
    </table></td>

    <td valign="top"><table border="0" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
