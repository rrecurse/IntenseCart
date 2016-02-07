<?php

  require('includes/application_top.php');
  
  $magic_emails=Array('support@intensecart.com','support@intensegroupinc.com');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  $error = false;
  $processed = false;

  if (tep_not_null($action)) {
    switch ($action) {
      case 'send_password':
        include(DIR_FS_CATALOG_INCLUDES.'languages/'.$language.'/password_forgotten.php');
        $customers_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
	$check_customer=IXdb::read("SELECT * FROM customers WHERE customers_id='$customers_id'");
        $new_password = tep_create_random_value(8);
        $crypted_password = tep_encrypt_password($new_password);
        tep_db_query("update " . TABLE_CUSTOMERS . " set customers_password = '" . tep_db_input($crypted_password) . "' where customers_id = '$customers_id'");
        tep_mail($check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], $check_customer['customers_email_address'], EMAIL_PASSWORD_REMINDER_SUBJECT, sprintf(EMAIL_PASSWORD_REMINDER_BODY, $new_password), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        $messageStack->add_session('New password sent to '.$check_customer['customers_email_address'], 'success');
        tep_redirect(tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $customers_id));
      case 'update':
        $customers_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
        $customers_firstname = tep_db_prepare_input($HTTP_POST_VARS['customers_firstname']);
        $customers_lastname = tep_db_prepare_input($HTTP_POST_VARS['customers_lastname']);
        $customers_email_address = tep_db_prepare_input($HTTP_POST_VARS['customers_email_address']);
        $customers_telephone = tep_db_prepare_input($HTTP_POST_VARS['customers_telephone']);
        $customers_fax = tep_db_prepare_input($HTTP_POST_VARS['customers_fax']);
        $customers_newsletter = tep_db_prepare_input($HTTP_POST_VARS['customers_newsletter']);

// BOF Separate Pricing per Customer
	$customers_group_id = tep_db_prepare_input($HTTP_POST_VARS['customers_group_id']);
	$customers_group_ra = tep_db_prepare_input($HTTP_POST_VARS['customers_group_ra']);
	$entry_company_tax_id = tep_db_prepare_input($HTTP_POST_VARS['entry_company_tax_id']);
	if ($HTTP_POST_VARS['customers_payment_allowed'] && $HTTP_POST_VARS['customers_payment_settings'] == '1') {
	$customers_payment_allowed = tep_db_prepare_input($HTTP_POST_VARS['customers_payment_allowed']);
	} else { // no error with subsequent re-posting of variables	
	$customers_payment_allowed = '';
	if ($HTTP_POST_VARS['payment_allowed'] && $HTTP_POST_VARS['customers_payment_settings'] == '1') {
		while(list($key, $val) = each($HTTP_POST_VARS['payment_allowed'])) {
		    if ($val == true) { 
		    $customers_payment_allowed .= tep_db_prepare_input($val).';'; 
		    }
		 } // end while
		  $customers_payment_allowed = substr($customers_payment_allowed,0,strlen($customers_payment_allowed)-1);
	} // end if ($HTTP_POST_VARS['payment_allowed'])
	} // end else ($HTTP_POST_VARS['customers_payment_allowed']
	if ($HTTP_POST_VARS['customers_shipment_allowed'] && $HTTP_POST_VARS['customers_shipment_settings'] == '1') {
	$customers_shipment_allowed = tep_db_prepare_input($HTTP_POST_VARS['customers_shipment_allowed']);
	} else { // no error with subsequent re-posting of variables	

		$customers_shipment_allowed = '';
		if ($HTTP_POST_VARS['shipping_allowed'] && $HTTP_POST_VARS['customers_shipment_settings'] == '1') {
		  while(list($key, $val) = each($HTTP_POST_VARS['shipping_allowed'])) {
		    if ($val == true) { 
		    $customers_shipment_allowed .= tep_db_prepare_input($val).';'; 
		    }
		  } // end while
		  $customers_shipment_allowed = substr($customers_shipment_allowed,0,strlen($customers_shipment_allowed)-1);
		} // end if ($HTTP_POST_VARS['shipment_allowed'])
	} // end else ($HTTP_POST_VARS['customers_shipment_allowed']

// EOF Separate Pricing per Customer

        $customers_referred_by = tep_db_prepare_input($HTTP_POST_VARS['customers_referred_by']);

        $customers_gender = tep_db_prepare_input($HTTP_POST_VARS['customers_gender']);
        $customers_dob = tep_db_prepare_input($HTTP_POST_VARS['customers_dob']);

        $default_address_id = tep_db_prepare_input($HTTP_POST_VARS['default_address_id']);
        $entry_street_address = tep_db_prepare_input($HTTP_POST_VARS['entry_street_address']);
        $entry_suburb = tep_db_prepare_input($HTTP_POST_VARS['entry_suburb']);
        $entry_postcode = tep_db_prepare_input($HTTP_POST_VARS['entry_postcode']);
        $entry_city = tep_db_prepare_input($HTTP_POST_VARS['entry_city']);
        $entry_country_id = tep_db_prepare_input($HTTP_POST_VARS['entry_country_id']);
		$member_flag = tep_db_prepare_input($HTTP_POST_VARS['member_flag']);

        $entry_company = tep_db_prepare_input($HTTP_POST_VARS['entry_company']);
        $entry_state = tep_db_prepare_input($HTTP_POST_VARS['entry_state']);
        if (isset($HTTP_POST_VARS['entry_zone_id'])) $entry_zone_id = tep_db_prepare_input($HTTP_POST_VARS['entry_zone_id']);

        if (strlen($customers_firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
          $error = true;
          $entry_firstname_error = true;
        } else {
          $entry_firstname_error = false;
        }

        if (strlen($customers_lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
          $error = true;
          $entry_lastname_error = true;
        } else {
          $entry_lastname_error = false;
        }

        if (ACCOUNT_DOB == 'true') {
          if (checkdate(substr(tep_date_raw($customers_dob), 4, 2), substr(tep_date_raw($customers_dob), 6, 2), substr(tep_date_raw($customers_dob), 0, 4))) {
            $entry_date_of_birth_error = false;
          } else {
            $error = true;
            $entry_date_of_birth_error = true;
          }
        }

        if (strlen($customers_email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
          $error = true;
          $entry_email_address_error = true;
        } else {
          $entry_email_address_error = false;
        }

        if (!tep_validate_email($customers_email_address)) {
          $error = true;
          $entry_email_address_check_error = true;
        } else {
          $entry_email_address_check_error = false;
        }

        if (strlen($entry_street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
          $error = true;
          $entry_street_address_error = true;
        } else {
          $entry_street_address_error = false;
        }

        if (strlen($entry_postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
          $error = true;
          $entry_post_code_error = true;
        } else {
          $entry_post_code_error = false;
        }

        if (strlen($entry_city) < ENTRY_CITY_MIN_LENGTH) {
          $error = true;
          $entry_city_error = true;
        } else {
          $entry_city_error = false;
        }

        if ($entry_country_id == false) {
          $error = true;
          $entry_country_error = true;
        } else {
          $entry_country_error = false;
        }

        if (ACCOUNT_STATE == 'true') {
          if ($entry_country_error == true) {
            $entry_state_error = true;
          } else {
            $zone_id = 0;
            $entry_state_error = false;
            $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$entry_country_id . "'");
            $check_value = tep_db_fetch_array($check_query);
            $entry_state_has_zones = ($check_value['total'] > 0);
            if ($entry_state_has_zones == true) {
              $zone_query = tep_db_query("select zone_id from " . TABLE_ZONES . " where zone_country_id = '" . (int)$entry_country_id . "' and zone_name = '" . tep_db_input($entry_state) . "'");
              if (tep_db_num_rows($zone_query) == 1) {
                $zone_values = tep_db_fetch_array($zone_query);
                $entry_zone_id = $zone_values['zone_id'];
              } else {
                $error = true;
                $entry_state_error = true;
              }
            } else {
              if ($entry_state == false) {
                $error = true;
                $entry_state_error = true;
              }
            }
         }
      }

      if (strlen($customers_telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
        $error = true;
        $entry_telephone_error = true;
      } else {
        $entry_telephone_error = false;
      }

      $check_email = tep_db_query("select customers_email_address from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($customers_email_address) . "' and customers_id != '" . (int)$customers_id . "'");
      if (tep_db_num_rows($check_email)) {
        $error = true;
        $entry_email_address_exists = true;
      } else {
        $entry_email_address_exists = false;
      }

      if ($error == false) {

        $sql_data_array = array('customers_firstname' => $customers_firstname,
                                'customers_lastname' => $customers_lastname,
                                'customers_email_address' => $customers_email_address,
                                'customers_telephone' => $customers_telephone,
                                'customers_fax' => $customers_fax,
                                'customers_newsletter' => $customers_newsletter,

// BOF Separate Pricing per Customer
                                'customers_group_id' => $customers_group_id,
                                'customers_group_ra' => $customers_group_ra,
				'customers_payment_allowed' => $customers_payment_allowed,
				'customers_shipment_allowed' => $customers_shipment_allowed,
// EOF Separate Pricing per Customer

				'customers_referred_by' => $customers_referred_by,
				'member_flag' => $member_flag); // Manual Order Entry [1589]

        if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $customers_gender;
        if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($customers_dob);

        tep_db_perform(TABLE_CUSTOMERS, $sql_data_array, 'update', "customers_id = '" . (int)$customers_id . "'");
	
	if (isset($_POST['extra'])) {
	  $xlst=Array();
	  foreach ($_POST['extra'] AS $xkey=>$xval) $xlst[]="('$customers_id','$xkey','$xval')";
	  if ($xlst) IXdb::query("REPLACE INTO customers_extra (customers_id,customers_extra_key,customers_extra_value) VALUES ".join(',',$xlst));
	}

        tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set customers_info_date_account_last_modified = now() where customers_info_id = '" . (int)$customers_id . "'");

        if ($entry_zone_id > 0) $entry_state = '';

        $sql_data_array = array('entry_firstname' => $customers_firstname,
                                'entry_lastname' => $customers_lastname,
                                'entry_street_address' => $entry_street_address,
                                'entry_postcode' => $entry_postcode,
                                'entry_city' => $entry_city,
                                'entry_country_id' => $entry_country_id);

// BOF Separate Pricing Per Customer
        if (ACCOUNT_COMPANY == 'true') {
        $sql_data_array['entry_company'] = $entry_company;
         $sql_data_array['entry_company_tax_id'] = $entry_company_tax_id;
        } 
// EOF Separate Pricing Per Customer

        if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = $entry_company;
        if (ACCOUNT_SUBURB == 'true') $sql_data_array['entry_suburb'] = $entry_suburb;

        if (ACCOUNT_STATE == 'true') {
          if ($entry_zone_id > 0) {
            $sql_data_array['entry_zone_id'] = $entry_zone_id;
            $sql_data_array['entry_state'] = '';
          } else {
            $sql_data_array['entry_zone_id'] = '0';
            $sql_data_array['entry_state'] = $entry_state;
          }
        }

        tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', "customers_id = '" . (int)$customers_id . "' and address_book_id = '" . (int)$default_address_id . "'");

        tep_redirect(tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $customers_id));

        } else if ($error == true) {
          $cInfo = new objectInfo($HTTP_POST_VARS);
          $processed = true;
        }

        break;
      case 'deleteconfirm':
        $customers_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
	
	if (in_array(strtolower(IXdb::read("SELECT customers_email_address AS email FROM customers WHERE customers_id='$customers_id'",NULL,'email')),$magic_emails)) {
          $messageStack->add_session('You are not permitted to delete this account', 'error');
          tep_redirect(tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action'))));
	}

        if (isset($HTTP_POST_VARS['delete_reviews']) && ($HTTP_POST_VARS['delete_reviews'] == 'on')) {
          $reviews_query = tep_db_query("select reviews_id from " . TABLE_REVIEWS . " where customers_id = '" . (int)$customers_id . "'");
          while ($reviews = tep_db_fetch_array($reviews_query)) {
            tep_db_query("delete from " . TABLE_REVIEWS_DESCRIPTION . " where reviews_id = '" . (int)$reviews['reviews_id'] . "'");
          }

          tep_db_query("delete from " . TABLE_REVIEWS . " where customers_id = '" . (int)$customers_id . "'");
        } else {
          tep_db_query("update " . TABLE_REVIEWS . " set customers_id = null where customers_id = '" . (int)$customers_id . "'");
        }

// BOF Separate Pricing Per Customer
// Once all customers with a specific customers_group_id have been deleted from
// the table customers, the next time a customer is deleted, all entries in the table products_groups
// that have the (now apparently obsolete) customers_group_id will be deleted!
// If you don't want that, leave this section out, or comment it out
// Note that when customers groups are deleted from the table customers_groups, all the
// customers with that specific customer_group_id will be changed to customer_group_id = '0' (default/Retail)
$multiple_groups_query = tep_db_query("select customers_group_id from " . TABLE_CUSTOMERS_GROUPS . " ");
while ($group_ids = tep_db_fetch_array($multiple_groups_query)) {
  $multiple_customers_query = tep_db_query("select distinct customers_group_id from " . TABLE_CUSTOMERS . " where customers_group_id = " . $group_ids['customers_group_id'] . " ");
  if (!($multiple_groups = tep_db_fetch_array($multiple_customers_query))) {
    tep_db_query("delete from " . TABLE_PRODUCTS_GROUPS . " where customers_group_id = '" . $group_ids['customers_group_id'] . "'");
  }
}
// EOF Separate Pricing Per Customer

        tep_db_query("delete from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customers_id . "'");
        tep_db_query("delete from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customers_id . "'");
        tep_db_query("delete from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . (int)$customers_id . "'");
        tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customers_id . "'");
        tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customers_id . "'");
        tep_db_query("delete from " . TABLE_WHOS_ONLINE . " where customer_id = '" . (int)$customers_id . "'");
	tep_db_query("delete from " . TABLE_SOURCES_OTHER . " where customers_id = '" . (int)$customers_id . "'");

        tep_redirect(tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action'))));
        break;
      default:

      // BOF Separate Pricing Per Customer
// Next query modified for Manual Order Entry [1589]
        $customers_query = tep_db_query("select c.customers_id, c.customers_gender, c.customers_firstname, c.customers_lastname, c.customers_dob, c.customers_email_address, a.entry_company, a.entry_company_tax_id, a.entry_street_address, a.entry_suburb, a.entry_postcode, a.entry_city, a.entry_state, a.entry_zone_id, a.entry_country_id, c.customers_telephone, c.customers_fax, c.customers_newsletter, c.member_flag, c.customers_group_id,  c.customers_group_ra, c.customers_payment_allowed, c.customers_shipment_allowed, c.customers_referred_by, c.customers_default_address_id from " . TABLE_CUSTOMERS . " c left join " . TABLE_ADDRESS_BOOK . " a on (c.customers_default_address_id = a.address_book_id AND a.customers_id = c.customers_id) WHERE c.customers_id = '" . (int)$HTTP_GET_VARS['cID'] . "'");
	
        $module_directory = DIR_FS_CATALOG_MODULES . 'payment/';
        $ship_module_directory = DIR_FS_CATALOG_MODULES . 'shipping/';

        $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
        $directory_array = array();
        if ($dir = @dir($module_directory)) {
        while ($file = $dir->read()) {
        if (!is_dir($module_directory . $file)) {
           if (substr($file, strrpos($file, '.')) == $file_extension) {
              $directory_array[] = $file; // array of all the payment modules present in includes/modules/payment
                  }
               }
            }
        sort($directory_array);
        $dir->close();
        }

        $ship_directory_array = array();
        if ($dir = @dir($ship_module_directory)) {
        while ($file = $dir->read()) {
        if (!is_dir($ship_module_directory . $file)) {
           if (substr($file, strrpos($file, '.')) == $file_extension) {
              $ship_directory_array[] = $file; // array of all shipping modules present in includes/modules/shipping
                }
              }
            }
            sort($ship_directory_array);
            $dir->close();
        }
	
	$existing_customers_query = tep_db_query("select customers_group_id, customers_group_name from " . TABLE_CUSTOMERS_GROUPS . " order by customers_group_id ");
	
// EOF Separate Pricing Per Customer


// Next query modified for Manual Order Entry [1589]
//        $customers_query = tep_db_query("select c.customers_id, c.customers_gender, c.customers_firstname, c.customers_lastname, c.customers_dob, c.customers_email_address, a.entry_company, a.entry_street_address, a.entry_suburb, a.entry_postcode, a.entry_city, a.entry_state, a.entry_zone_id, a.entry_country_id, c.customers_telephone, c.customers_fax, c.customers_newsletter, c.member_flag, c.customers_default_address_id from " . TABLE_CUSTOMERS . " c left join " . TABLE_ADDRESS_BOOK . " a on c.customers_default_address_id = a.address_book_id where a.customers_id = c.customers_id and c.customers_id = '" . (int)$HTTP_GET_VARS['cID'] . "'");
        $customers = tep_db_fetch_array($customers_query);
        $cInfo = new objectInfo($customers);

// BOF Separate Pricing Per Customer
  //      $shipment_allowed = explode (";",$cInfo->customers_shipment_allowed);
// EOF Separate Pricing Per Customer

    }
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<?php
  if ($action == 'edit' || $action == 'update') {
?>
<script language="javascript"><!--

function check_form() {
  var error = 0;
  var error_message = "<?php echo JS_ERROR; ?>";

  var customers_firstname = document.customers.customers_firstname.value;
  var customers_lastname = document.customers.customers_lastname.value;
<?php if (ACCOUNT_COMPANY == 'true') echo 'var entry_company = document.customers.entry_company.value;' . "\n"; ?>
<?php if (ACCOUNT_DOB == 'true') echo 'var customers_dob = document.customers.customers_dob.value;' . "\n"; ?>
  var customers_email_address = document.customers.customers_email_address.value;
  var entry_street_address = document.customers.entry_street_address.value;
  var entry_postcode = document.customers.entry_postcode.value;
  var entry_city = document.customers.entry_city.value;
  var customers_telephone = document.customers.customers_telephone.value;

<?php if (ACCOUNT_GENDER == 'true') { ?>
  if (document.customers.customers_gender[0].checked || document.customers.customers_gender[1].checked) {
  } else {
    error_message = error_message + "<?php echo JS_GENDER; ?>";
    error = 1;
  }
<?php } ?>

  if (customers_firstname == "" || customers_firstname.length < <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_FIRST_NAME; ?>";
    error = 1;
  }

  if (customers_lastname == "" || customers_lastname.length < <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_LAST_NAME; ?>";
    error = 1;
  }

<?php if (ACCOUNT_DOB == 'true') { ?>
  if (customers_dob == "" || customers_dob.length < <?php echo ENTRY_DOB_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_DOB; ?>";
    error = 1;
  }
<?php } ?>

  if (customers_email_address == "" || customers_email_address.length < <?php echo ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_EMAIL_ADDRESS; ?>";
    error = 1;
  }

  if (entry_street_address == "" || entry_street_address.length < <?php echo ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_ADDRESS; ?>";
    error = 1;
  }

  if (entry_postcode == "" || entry_postcode.length < <?php echo ENTRY_POSTCODE_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_POST_CODE; ?>";
    error = 1;
  }

  if (entry_city == "" || entry_city.length < <?php echo ENTRY_CITY_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_CITY; ?>";
    error = 1;
  }

<?php
  if (ACCOUNT_STATE == 'true') {
?>
  if (document.customers.elements['entry_state'].type != "hidden") {
    if (document.customers.entry_state.value == '' || document.customers.entry_state.value.length < <?php echo ENTRY_STATE_MIN_LENGTH; ?> ) {
       error_message = error_message + "<?php echo JS_STATE; ?>";
       error = 1;
    }
  }
<?php
  }
?>

  if (document.customers.elements['entry_country_id'].type != "hidden") {
    if (document.customers.entry_country_id.value == 0) {
      error_message = error_message + "<?php echo JS_COUNTRY; ?>";
      error = 1;
    }
  }

  if (customers_telephone == "" || customers_telephone.length < <?php echo ENTRY_TELEPHONE_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_TELEPHONE; ?>";
    error = 1;
  }

  if (error == 1) {
    alert(error_message);
    return false;
  } else {
    return true;
  }
}
//--></script>
<?php
  }
?>
<style>

.AccordionPanelOpen .AccordionPanelTab tr {
	background-color: #FFFFC4;
}

.AccordionPanelOpen .AccordionPanelContent {
background-color: #FFFFC4;
}

.AccordionPanelTabHover {
background-color: #FFFFC4;
}

.AccordionPanelContent {
border:0
}

.pagejump select {font:8pt verdana;
}
</style>

<script type="text/JavaScript">
<!--
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
//-->
</script>

</head>
<body style="margin:5px 0 0 0; background-color:transparent" onload="SetFocus(); MM_preloadImages('images/minus.gif')">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
	<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<td>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>

    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if ($action == 'edit' || $action == 'update') {
    $newsletter_array = array(array('id' => '1', 'text' => ENTRY_NEWSLETTER_YES),
                              array('id' => '0', 'text' => ENTRY_NEWSLETTER_NO));
// START Manual Order Entry [1589]
    $member_flag_array = array(array('id' => '1', 'text' => ENTRY_MEMBER_YES),
                              array('id' => '0', 'text' => ENTRY_MEMBER_NO)); 							  
// END Manual Order Entry
?>
     <tr><?php echo tep_draw_form('customers', FILENAME_CUSTOMERS, tep_get_all_get_params(array('action')) . 'action=update', 'post', 'onSubmit="return check_form();"') . tep_draw_hidden_field('default_address_id', $cInfo->customers_default_address_id); ?>
        <td class="formAreaTitle"><?php echo CATEGORY_PERSONAL; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
<?php
    if (ACCOUNT_GENDER == 'true') {
?>
          <tr>
            <td class="main"><?php echo ENTRY_GENDER; ?></td>
            <td class="main">
<?php
    if ($error == true) {
      if ($entry_gender_error == true) {
        echo tep_draw_radio_field('customers_gender', 'm', false, $cInfo->customers_gender) . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('customers_gender', 'f', false, $cInfo->customers_gender) . '&nbsp;&nbsp;' . FEMALE . '&nbsp;' . ENTRY_GENDER_ERROR;
      } else {
        echo ($cInfo->customers_gender == 'm') ? MALE : FEMALE;
        echo tep_draw_hidden_field('customers_gender');
      }
    } else {
      echo tep_draw_radio_field('customers_gender', 'm', false, $cInfo->customers_gender) . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('customers_gender', 'f', false, $cInfo->customers_gender) . '&nbsp;&nbsp;' . FEMALE;
    }
?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td class="main"><?php echo ENTRY_FIRST_NAME; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_firstname_error == true) {
      echo tep_draw_input_field('customers_firstname', $cInfo->customers_firstname, 'maxlength="32"') . '&nbsp;' . ENTRY_FIRST_NAME_ERROR;
    } else {
      echo $cInfo->customers_firstname . tep_draw_hidden_field('customers_firstname');
    }
  } else {
    echo tep_draw_input_field('customers_firstname', $cInfo->customers_firstname, 'maxlength="32"', true);
  }
?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_LAST_NAME; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_lastname_error == true) {
      echo tep_draw_input_field('customers_lastname', $cInfo->customers_lastname, 'maxlength="32"') . '&nbsp;' . ENTRY_LAST_NAME_ERROR;
    } else {
      echo $cInfo->customers_lastname . tep_draw_hidden_field('customers_lastname');
    }
  } else {
    echo tep_draw_input_field('customers_lastname', $cInfo->customers_lastname, 'maxlength="32"', true);
  }
?></td>
          </tr>
<?php
    if (ACCOUNT_DOB == 'true') {
?>
          <tr>
            <td class="main"><?php echo ENTRY_DATE_OF_BIRTH; ?></td>
            <td class="main">

<?php
    if ($error == true) {
      if ($entry_date_of_birth_error == true) {
        echo tep_draw_input_field('customers_dob', tep_date_short($cInfo->customers_dob), 'maxlength="10"') . '&nbsp;' . ENTRY_DATE_OF_BIRTH_ERROR;
      } else {
        echo $cInfo->customers_dob . tep_draw_hidden_field('customers_dob');
      }
    } else {
      echo tep_draw_input_field('customers_dob', tep_date_short($cInfo->customers_dob), 'maxlength="10"', true);
    }
?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td class="main"><?php echo ENTRY_EMAIL_ADDRESS; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_email_address_error == true) {
      echo tep_draw_input_field('customers_email_address', $cInfo->customers_email_address, 'maxlength="96"') . '&nbsp;' . ENTRY_EMAIL_ADDRESS_ERROR;
    } elseif ($entry_email_address_check_error == true) {
      echo tep_draw_input_field('customers_email_address', $cInfo->customers_email_address, 'maxlength="96"') . '&nbsp;' . ENTRY_EMAIL_ADDRESS_CHECK_ERROR;
    } elseif ($entry_email_address_exists == true) {
      echo tep_draw_input_field('customers_email_address', $cInfo->customers_email_address, 'maxlength="96"') . '&nbsp;' . ENTRY_EMAIL_ADDRESS_ERROR_EXISTS;
    } else {
      echo $customers_email_address . tep_draw_hidden_field('customers_email_address');
    }
  } else {
    echo tep_draw_input_field('customers_email_address', $cInfo->customers_email_address, 'maxlength="96"', true);
  }
?></td>
          </tr>
        </table></td>
      </tr>
<?php
    if (ACCOUNT_COMPANY == 'true') {
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle"><?php echo CATEGORY_COMPANY; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
          <tr>
            <td class="main"><?php echo ENTRY_COMPANY; ?></td>
            <td class="main">
<?php
    if ($error == true) {
      if ($entry_company_error == true) {
        echo tep_draw_input_field('entry_company', $cInfo->entry_company, 'maxlength="32"') . '&nbsp;' . ENTRY_COMPANY_ERROR;
      } else {
        echo $cInfo->entry_company . tep_draw_hidden_field('entry_company');
      }
    } else {
      echo tep_draw_input_field('entry_company', $cInfo->entry_company, 'maxlength="32"');
    }
?></td>

<!-- BOF Separate Pricing Per Customer -->
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_COMPANY_TAX_ID; ?></td>
            <td class="main">
<?php 
    if ($error == true) {
      if ($entry_company_tax_id_error == true) {
        echo tep_draw_input_field('entry_company_tax_id', $cInfo->entry_company_tax_id, 'maxlength="32"') . '&nbsp;' . ENTRY_COMPANY_TAX_ID_ERROR;
      } else {
        echo $cInfo->entry_company . tep_draw_hidden_field('entry_company_tax_id');
      }
    } else {
      echo tep_draw_input_field('entry_company_tax_id', $cInfo->entry_company_tax_id, 'maxlength="32"');
      }
?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_CUSTOMERS_GROUP_REQUEST_AUTHENTICATION; ?></td>
            <td class="main">
<?php
    if ($error == true) {
      if ($customers_group_ra_error == true) {
        echo tep_draw_radio_field('customers_group_ra', '0', false, $cInfo->customers_group_ra) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_GROUP_RA_NO . '&nbsp;&nbsp;' . tep_draw_radio_field('customers_group_ra', '1', false, $cInfo->customers_group_ra) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_GROUP_RA_YES . '&nbsp;' . ENTRY_CUSTOMERS_GROUP_RA_ERROR;
      } else {
        echo ($cInfo->customers_group_ra == '0') ? ENTRY_CUSTOMERS_GROUP_RA_NO : ENTRY_CUSTOMERS_GROUP_RA_YES;
        echo tep_draw_hidden_field('customers_group_ra');
      }
    } else {
      echo tep_draw_radio_field('customers_group_ra', '0', false, $cInfo->customers_group_ra) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_GROUP_RA_NO . '&nbsp;&nbsp;' . tep_draw_radio_field('customers_group_ra', '1', false, $cInfo->customers_group_ra) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_GROUP_RA_YES;
    }
?></td>
          </tr>
<!-- EOF Separate Pricing Per Customer -->

          </tr>
        </table></td>
      </tr>
<?php
    }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle"><?php echo CATEGORY_ADDRESS; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
          <tr>
            <td class="main"><?php echo ENTRY_STREET_ADDRESS; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_street_address_error == true) {
      echo tep_draw_input_field('entry_street_address', $cInfo->entry_street_address, 'maxlength="64"') . '&nbsp;' . ENTRY_STREET_ADDRESS_ERROR;
    } else {
      echo $cInfo->entry_street_address . tep_draw_hidden_field('entry_street_address');
    }
  } else {
    echo tep_draw_input_field('entry_street_address', $cInfo->entry_street_address, 'maxlength="64"', true);
  }
?></td>
          </tr>
<?php
    if (ACCOUNT_SUBURB == 'true') {
?>
          <tr>
            <td class="main"><?php echo ENTRY_SUBURB; ?></td>
            <td class="main">
<?php
    if ($error == true) {
      if ($entry_suburb_error == true) {
        echo tep_draw_input_field('suburb', $cInfo->entry_suburb, 'maxlength="32"') . '&nbsp;' . ENTRY_SUBURB_ERROR;
      } else {
        echo $cInfo->entry_suburb . tep_draw_hidden_field('entry_suburb');
      }
    } else {
      echo tep_draw_input_field('entry_suburb', $cInfo->entry_suburb, 'maxlength="32"');
    }
?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td class="main"><?php echo ENTRY_POST_CODE; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_post_code_error == true) {
      echo tep_draw_input_field('entry_postcode', $cInfo->entry_postcode, 'maxlength="8"') . '&nbsp;' . ENTRY_POST_CODE_ERROR;
    } else {
      echo $cInfo->entry_postcode . tep_draw_hidden_field('entry_postcode');
    }
  } else {
    echo tep_draw_input_field('entry_postcode', $cInfo->entry_postcode, 'maxlength="8"', true);
  }
?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_CITY; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_city_error == true) {
      echo tep_draw_input_field('entry_city', $cInfo->entry_city, 'maxlength="32"') . '&nbsp;' . ENTRY_CITY_ERROR;
    } else {
      echo $cInfo->entry_city . tep_draw_hidden_field('entry_city');
    }
  } else {
    echo tep_draw_input_field('entry_city', $cInfo->entry_city, 'maxlength="32"', true);
  }
?></td>
          </tr>
<?php
    if (ACCOUNT_STATE == 'true') {
?>
          <tr>
            <td class="main"><?php echo ENTRY_STATE; ?></td>
            <td class="main">
<?php
    $entry_state = tep_get_zone_name($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state);
    if ($error == true) {
      if ($entry_state_error == true) {
        if ($entry_state_has_zones == true) {
          $zones_array = array();
          $zones_query = tep_db_query("select zone_name from " . TABLE_ZONES . " where zone_country_id = '" . tep_db_input($cInfo->entry_country_id) . "' order by zone_name");
          while ($zones_values = tep_db_fetch_array($zones_query)) {
            $zones_array[] = array('id' => $zones_values['zone_name'], 'text' => $zones_values['zone_name']);
          }
          echo tep_draw_pull_down_menu('entry_state', $zones_array) . '&nbsp;' . ENTRY_STATE_ERROR;
        } else {
          echo tep_draw_input_field('entry_state', tep_get_zone_name($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state)) . '&nbsp;' . ENTRY_STATE_ERROR;
        }
      } else {
        echo $entry_state . tep_draw_hidden_field('entry_zone_id') . tep_draw_hidden_field('entry_state');
      }
    } else {
      echo tep_draw_input_field('entry_state', tep_get_zone_name($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state));
    }

?></td>
         </tr>
<?php
    }
?>
          <tr>
            <td class="main"><?php echo ENTRY_COUNTRY; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_country_error == true) {
      echo tep_draw_pull_down_menu('entry_country_id', tep_get_countries(), $cInfo->entry_country_id) . '&nbsp;' . ENTRY_COUNTRY_ERROR;
    } else {
      echo tep_get_country_name($cInfo->entry_country_id) . tep_draw_hidden_field('entry_country_id');
    }
  } else {
    echo tep_draw_pull_down_menu('entry_country_id', tep_get_countries(), $cInfo->entry_country_id);
  }
?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle"><?php echo CATEGORY_CONTACT; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
          <tr>
            <td class="main"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
            <td class="main">
<?php
  if ($error == true) {
    if ($entry_telephone_error == true) {
      echo tep_draw_input_field('customers_telephone', $cInfo->customers_telephone, 'maxlength="32"') . '&nbsp;' . ENTRY_TELEPHONE_NUMBER_ERROR;
    } else {
      echo $cInfo->customers_telephone . tep_draw_hidden_field('customers_telephone');
    }
  } else {
    echo tep_draw_input_field('customers_telephone', $cInfo->customers_telephone, 'maxlength="32"', true);
  }
?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_FAX_NUMBER; ?></td>
            <td class="main">
<?php
  if ($processed == true) {
    echo $cInfo->customers_fax . tep_draw_hidden_field('customers_fax');
  } else {
    echo tep_draw_input_field('customers_fax', $cInfo->customers_fax, 'maxlength="32"');
  }
?></td>
          </tr>
        </table></td>
      </tr>


      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle">Referrer</td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
          <tr>
            <td class="main">Referred By Affiliate</td>
            <td class="main">
<?
  if ($processed == true) {
    echo $cInfo->customers_referred_by . tep_draw_hidden_field('customers_referred_by', $cInfo->customers_referred_by);
  } else {
    $aff_list=Array(Array(id=>0,text=>'-none-'));
    $aff_query=tep_db_query("SELECT affiliate_id,affiliate_firstname,affiliate_lastname,affiliate_email_address FROM ".TABLE_AFFILIATE." ORDER BY affiliate_id");
    while ($aff_row=tep_db_fetch_array($aff_query)) {
      $aff_list[]=Array(id=>$aff_row['affiliate_id'],text=>$aff_row['affiliate_id'].': '.$aff_row['affiliate_firstname'].' '.$aff_row['affiliate_lastname'].' ('.$aff_row['affiliate_email_address'].')');
    }
    echo tep_draw_pull_down_menu('customers_referred_by', $aff_list, $cInfo->customers_referred_by);
  }
?></td>
          </tr>
        </table></td>
      </tr>

      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle"><?php echo CATEGORY_OPTIONS; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
          <tr>
            <td class="main"><?php echo ENTRY_NEWSLETTER; ?></td>
            <td class="main">
<?php
  if ($processed == true) {
    if ($cInfo->customers_newsletter == '1') {
      echo ENTRY_NEWSLETTER_YES;
    } else {
      echo ENTRY_NEWSLETTER_NO;
    }
    echo tep_draw_hidden_field('customers_newsletter');
  } else {
    echo tep_draw_pull_down_menu('customers_newsletter', $newsletter_array, (($cInfo->customers_newsletter == '1') ? '1' : '0'));
  }
?></td>
          </tr>
	  
<!--  BOF Separate Pricing per Customer -->
<tr>
  <td class="main"><?php echo ENTRY_CUSTOMERS_GROUP_NAME; ?></td>
  <?php
  if ($processed != true) {
  $index = 0;
  while ($existing_customers =  tep_db_fetch_array($existing_customers_query)) {
  $existing_customers_array[] = array("id" => $existing_customers['customers_group_id'], "text" => "&#160;".$existing_customers['customers_group_name']."&#160;");
    ++$index;
  }
  } // end if ($processed != true )
?>
  <td class="main"><?php if ($processed == true) {
    echo $cInfo->customers_group_id . tep_draw_hidden_field('customers_group_id');
  } else {	
  echo tep_draw_pull_down_menu('customers_group_id', $existing_customers_array, $cInfo->customers_group_id);
  } ?></td>
</tr>
<!-- EOF Separate Pricing per Customer -->

          <tr>
            <td class="main"><?php echo ENTRY_MEMBER; ?></td>
            <td class="main"><?php echo tep_draw_pull_down_menu('member_flag', $member_flag_array, $cInfo->member_flag); ?></td>
          </tr>		  
        </table></td>
      </tr>

<!-- BOF Separate Pricing per Customer -->      
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle"><?php include_once(DIR_WS_LANGUAGES . $language . '/modules.php');
	echo HEADING_TITLE_MODULES_PAYMENT; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
          <tr bgcolor="#DEE4E8">
            <td class="main" colspan="2"><?php if ($processed == true) {
            if ($cInfo->customers_payment_settings == '1') {
		echo ENTRY_CUSTOMERS_PAYMENT_SET ;
		echo ' : ';
	    } else {
		echo ENTRY_CUSTOMERS_PAYMENT_DEFAULT;
	    }  
	    echo tep_draw_hidden_field('customers_payment_settings');
            } else { // $processed != true
            echo tep_draw_radio_field('customers_payment_settings', '1', false, (tep_not_null($cInfo->customers_payment_allowed)? '1' : '0' )) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_PAYMENT_SET . '&nbsp;&nbsp;' . tep_draw_radio_field('customers_payment_settings', '0', false, (tep_not_null($cInfo->customers_payment_allowed)? '1' : '0' )) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_PAYMENT_DEFAULT ; } ?></td>
	  </tr>
<?php if ($processed != true) {
    $payments_allowed = explode (";",$cInfo->customers_payment_allowed);
    $module_active = explode (";",MODULE_PAYMENT_INSTALLED);
    $installed_modules = array();
    $paymods=tep_module('checkout');
    foreach ($paymods->getModules() AS $class=>$module) {
?>
          <tr>
            <td class="main" colspan="2"><?php echo tep_draw_checkbox_field('payment_allowed[]', $class , (in_array ($class, $payments_allowed)) ?  1 : 0); ?>&#160;&#160;<?php echo $module->getName(); ?></td>
	  </tr>
<?php
    } // end for ($i = 0, $n = sizeof($directory_array); $i < $n; $i++)
	?>
	   <tr>
            <td class="main" colspan="2" style="padding-left: 30px; padding-right: 10px; padding-top: 10px;"><?php echo ENTRY_CUSTOMERS_PAYMENT_SET_EXPLAIN ?></td>
           </tr>
<?php 
   } else { // end if ($processed != true)
?>
	    <tr>
            <td class="main" colspan="2"><?php if ($cInfo->customers_payment_settings == '1') {
		echo $customers_payment_allowed;
	    } else {
		echo ENTRY_CUSTOMERS_PAYMENT_DEFAULT;
	    } 
	    echo tep_draw_hidden_field('customers_payment_allowed'); ?></td>
	  </tr>
<?php 
 } // end else: $processed == true
?>
	   </td>
	  </tr>
	 </table>
	</td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle"><?php echo HEADING_TITLE_MODULES_SHIPPING; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
          <tr bgcolor="#DEE4E8">
            <td class="main" colspan="2"><?php if ($processed == true) {
            if ($cInfo->customers_shipment_settings == '1') {
		echo ENTRY_CUSTOMERS_SHIPPING_SET ;
		echo ' : ';
	    } else {
		echo ENTRY_CUSTOMERS_SHIPPING_DEFAULT;
	    }  
	    echo tep_draw_hidden_field('customers_shipment_settings');
            } else { // $processed != true
            echo tep_draw_radio_field('customers_shipment_settings', '1', false, (tep_not_null($cInfo->customers_shipment_allowed)? '1' : '0' )) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_SHIPPING_SET . '&nbsp;&nbsp;' . tep_draw_radio_field('customers_shipment_settings', '0', false, (tep_not_null($cInfo->customers_shipment_allowed)? '1' : '0' )) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_SHIPPING_DEFAULT ; } ?></td>
	  </tr>
<?php if ($processed != true) {
    $shipment_allowed = explode (";",$cInfo->customers_shipment_allowed);
    $ship_module_active = explode (";",MODULE_SHIPPING_INSTALLED);
    $installed_shipping_modules = array();
    for ($i = 0, $n = sizeof($ship_directory_array); $i < $n; $i++) {
    $file = $ship_directory_array[$i];
    if (in_array ($ship_directory_array[$i], $ship_module_active)) {
      include(DIR_FS_CATALOG_LANGUAGES . $language . '/modules/shipping/' . $file);
      include($ship_module_directory . $file);

     $ship_class = substr($file, 0, strrpos($file, '.'));
     if (tep_class_exists($ship_class)) {
       $ship_module = new $ship_class;
       if ($ship_module->check() > 0) {
         $installed_shipping_modules[] = $file;
       }
     } // end if (tep_class_exists($ship_class))
?>
          <tr>
            <td class="main" colspan="2"><?php echo tep_draw_checkbox_field('shipping_allowed[' . $i . ']', $ship_module->code.".php" , (in_array ($ship_module->code.".php", $shipment_allowed)) ?  1 : 0); ?>&#160;&#160;<?php echo $ship_module->title; ?></td>
	  </tr>
<?php
  } // end if (in_array ($ship_directory_array[$i], $ship_module_active)) 
 } // end for ($i = 0, $n = sizeof($ship_directory_array); $i < $n; $i++)
	?>
	   <tr>
            <td class="main" colspan="2" style="padding-left: 30px; padding-right: 10px; padding-top: 10px;"><?php echo ENTRY_CUSTOMERS_SHIPPING_SET_EXPLAIN ?></td>
           </tr>
<?php 
   } else { // end if ($processed != true)
?>
	    <tr>
            <td class="main" colspan="2"><?php if ($cInfo->customers_shipment_settings == '1') {
		echo $customers_shipment_allowed;
	    } else {
		echo ENTRY_CUSTOMERS_SHIPPING_DEFAULT;
	    } 
	    echo tep_draw_hidden_field('customers_shipment_allowed'); ?></td>
	  </tr>
<?php 
 } // end else: $processed == true
?>
	   </td>
	  </tr>
	 </table>
	</td>
      </tr>
<!-- EOF Separate Pricing per Customer -->


<?
  $custset=tep_module('custaccount');
  $custmods=$custset->getModules();
  foreach ($custmods AS $class=>$mod) {
    $flds=$mod->getAdminFields($customers['customers_id']);
    if ($flds) {
?>

      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="formAreaTitle"><?=$mod->getName()?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
<?
      foreach ($flds AS $fld) {
?>
  <tr><td><?=$fld['title']?></td><td><?=$fld['html']?></td></tr>
<?
      }
?>
	 </table>
	</td>
      </tr>
<?
    }
  }
?>









	  
	  
	  
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td align="right" class="main"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE) . ' <a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('action'))) .'">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr></form>
<?php
  } else {
?>



          <?php  // BOF customer_sort_admin_v1 adapted for Separate Pricing Per Customer
          switch ($listing) {
              case "id-asc":
              $order = "c.customers_id";
	          break;
	          case "cg_name":
              $order = "cg.customers_group_name, c.customers_lastname";
	          break;
              case "cg_name-desc":
              $order = "cg.customers_group_name DESC, c.customers_lastname";
              break;
              case "firstname":
              $order = "c.customers_firstname";
              break;
              case "firstname-desc":
              $order = "c.customers_firstname DESC";
              break;
              case "company":
              $order = "a.entry_company, c.customers_lastname";
              break;
              case "company-desc":
              $order = "a.entry_company DESC,c .customers_lastname DESC";
              break;
              case "ra":
              $order = "c.customers_group_ra DESC, c.customers_id DESC";
              break;
              case "ra-desc":
              $order = "c.customers_group_ra, c.customers_id DESC";
              break;
              case "lastname":
              $order = "c.customers_lastname, c.customers_firstname";
              break;
              case "lastname-desc":
              $order = "c.customers_lastname DESC, c.customers_firstname";
              break;
              default:
              $order = "c.customers_id DESC";
          }

// By MegaJim
    if (isset($HTTP_GET_VARS['vendors']) && $HTTP_GET_VARS['vendors']) {
      $show_groups=1;
      $search = 'WHERE c.customers_group_id!=0';
    } else {
      $show_groups=0;
      $search = 'WHERE c.customers_group_id=0';
    }

    $show_phone=0;
    if (isset($HTTP_GET_VARS['search']) && tep_not_null($HTTP_GET_VARS['search'])) {
      $keywords = tep_db_input(tep_db_prepare_input($HTTP_GET_VARS['search']));
      $search .= " AND (c.customers_lastname like '%" . $keywords . "%' or c.customers_firstname like '%" . $keywords . "%' or c.customers_email_address like '%" . $keywords . "%' or a.entry_company like '%" . $keywords . "%')";
    } else if (isset($_GET['find_phone']) && tep_not_null($_GET['find_phone']) && preg_match_all('/([0-9])/',$_GET['find_phone'],$phone_array)) {
      $phone_regex=join('[^0-9]*',$phone_array[1]);
      $search .= " AND c.customers_telephone REGEXP '$phone_regex'";
      $show_phone=1;
    } else if (isset($_GET['find_name']) && tep_not_null($_GET['find_name'])) {
      $name_cond=Array();
      foreach(split(' ',$_GET['find_name']) AS $find_name_word) {
        if ($find_name_word!='') {
	  $name_cond[]="(c.customers_firstname LIKE '%$find_name_word%' OR c.customers_lastname LIKE '%$find_name_word%' or a.entry_company like '%$find_name_word%'".(preg_match('/@/',$find_name_word)?" or c.customers_email_address like '%$find_name_word%'":"").")";
	}
      }
      if (sizeof($name_cond)) {
        $search .= " AND (".join(' OR ',$name_cond).")";
        $order = join(' + ',$name_cond)." DESC";
      }
    }
    if ($_GET['cust_group']) {
      $search .= " AND (c.customers_group_id='{$_GET['cust_group']}')";
    }

          ?>
             <td valign="top">
	     
	     
	<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
            <td colspan="3" align="right" style="padding:0 0 5px 0;">

<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
<td width="58" style="padding:0 0 0 5px"><img src="/admin/images/customers-icon.gif" width="48" height="48" alt=""></td>
								<td class="pageHeading"><?=$_GET['vendors']?'Vendors':'Customers'?></td>
<td align="right">
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr><?php echo tep_draw_form('customer_search', FILENAME_CUSTOMERS, '', 'get'); ?>
<?=tep_draw_hidden_field('vendors',$_GET['vendors'])?>
<td class="smallText" align="right" nowrap><?php echo '<b>Name Search:</b> ' . tep_draw_input_field('find_name', ' First or Last or Both', 'size="18"')?>
<input type="submit" value=" &raquo; "></td>
</tr>
</form>
<tr>
<?=tep_draw_form('customers', FILENAME_CUSTOMERS, '', 'get')?>
<?=tep_draw_hidden_field('vendors',$_GET['vendors'])?>
<td class="smallText" align="right" nowrap>Phone # Search: <?=tep_draw_input_field('find_phone',(isset($HTTP_POST_VARS['find_phone'])?$HTTP_POST_VARS['find_phone']:''),' size="18"')?>
<input type="submit" value=" &raquo; "></td>
</form>

              </tr>            
            </table>
</td>	
							</tr>
						</table></td>
          </tr>
        </table>
	     
	     
	     <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" valign="top"><a href="<?php echo "$PHP_SELF?listing=company"; ?>"><?php echo tep_image_button('ic_up.gif', ' Sort ' . ENTRY_COMPANY . ' --> A-B-C From Top '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=company-desc"; ?>"><?php echo tep_image_button('ic_down.gif', ' Sort ' . ENTRY_COMPANY . ' --> Z-X-Y From Top '); ?></a><br><?php echo ENTRY_COMPANY; ?></td>
                <td class="dataTableHeadingContent" valign="top"><a href="<?php echo "$PHP_SELF?listing=lastname"; ?>"><?php echo tep_image_button('ic_up.gif', ' Sort ' . TABLE_HEADING_LASTNAME . ' --> A-B-C From Top '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=lastname-desc"; ?>"><?php echo tep_image_button('ic_down.gif', ' Sort ' . TABLE_HEADING_LASTNAME . ' --> Z-X-Y From Top '); ?></a><br><?php echo TABLE_HEADING_LASTNAME; ?></td>
                <td class="dataTableHeadingContent" valign="top"><a href="<?php echo "$PHP_SELF?listing=firstname"; ?>"><?php echo tep_image_button('ic_up.gif', ' Sort ' . TABLE_HEADING_FIRSTNAME . ' --> A-B-C From Top '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=firstname-desc"; ?>"><?php echo tep_image_button('ic_down.gif', ' Sort ' . TABLE_HEADING_FIRSTNAME . ' --> Z-X-Y From Top '); ?></a><br><?php echo TABLE_HEADING_FIRSTNAME; ?></td>
<?
	if ($show_groups) { ?>
		<td class="dataTableHeadingContent" valign="top"><a href="<?php echo "$PHP_SELF?listing=cg_name"; ?>"><?php echo tep_image_button('ic_up.gif', ' Sort ' . TABLE_HEADING_CUSTOMERS_GROUPS . ' --> A-B-C From Top '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=cg_name-desc"; ?>"><?php echo tep_image_button('ic_down.gif', ' Sort ' . TABLE_HEADING_CUSTOMERS_GROUPS . ' --> Z-X-Y From Top '); ?></a><br><?php echo TABLE_HEADING_CUSTOMERS_GROUPS; ?></td>
<?
	} ?>
                <td class="dataTableHeadingContent" align="right" valign="top"><a href="<?php echo "$PHP_SELF?listing=id-asc"; ?>"><?php echo tep_image_button('ic_up.gif', ' Sort ' . TABLE_HEADING_ACCOUNT_CREATED . ' --> 1-2-3 From Top '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=id-desc"; ?>"><?php echo tep_image_button('ic_down.gif', ' Sort ' . TABLE_HEADING_ACCOUNT_CREATED . ' --> 3-2-1 From Top '); ?></a><br><?php echo TABLE_HEADING_ACCOUNT_CREATED; ?>








	<div class="AccordionPanel">
	  <div class="AccordionPanelTab <?=$ct++&1?'tabEven':'tabOdd'?>" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover="preloadCustomers('<?=$customers['customers_id']?>')">
	    <table width="571" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
            <td width="63" align="center" class="tableinfo_right-btm"><b><?php echo $customers['customers_id']; ?></b></td>
            <td width="117" align="center" class="tableinfo_right-btm"><div style="width:112px; white-space:nowrap; overflow-x:hidden; color:#000000;"><b>
<? if ($customers['customers_company']) { ?><?=$orders['customers_company']?><? } else { ?><?=$customers['customers_name']?><? } ?>
			</b></div></td>
            <td width="88" align="center" class="tableinfo_right-btm align_right"><b>xxxxxxxxxx</b></td>
            <td width="79" align="center" class="tableinfo_right-btm" style="font-weight:normal;">
			
			<a href="" style="font: 10px verdana; color:0000FF;"><u>view</u></a>
			
             
             | <?php echo '<a href="' . tep_href_link(FILENAME_EDIT_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID='. $customers['customers_id']) . '" style="font: 10px verdana; color:0000FF;"><u>edit</u></a>'  ?></td>
            <td width="96" height="23" align="center" class="tableinfo_right-btm"><b>xxxxxxxxxxxx</b></td>
            <td width="98" height="23" align="center" class="tableinfo_right-btm"><b>xxxxxxxxxxxxx</b></td>
            </tr>
        </table>
	  </div>
	  <div class="AccordionPanelContent" onExpanderOpenPanel="viewOrder('<?=$customers['customers_id']?>')"><div id="view_customer_<?=$customers['customers_id']?>"></div></div>
    </div>
<?php
    }
?>
  </div>
<script language="JavaScript" type="text/javascript">

function customersLoadComplete(req,id) {
//  window.alert(id);
  var box=$('view_order_'+id);
  if (box) box.innerHTML=req.responseText;
  customersLoaded[id]=true;
  customersAccordion.openPendingPanel();
}

var customersLoaded={};
function viewCustomer (id) {
  if (customersLoaded[id]==undefined) {
    customersLoaded[id]=false;
    new ajax('<?=tep_href_link(FILENAME_CUSTOMERS,'action=edit&oID=\'+id+\'')?>',{ onComplete:customersLoadComplete, onCompleteArg:id });
  }
  return customersLoaded[id];
}

function preloadOrder(id) {
  for (var i in customersLoaded) if (!customersLoaded[i]) return true;
  viewOrder(id);
  return true;
}

var customersAccordion = new Spry.Widget.Accordion("customersAccordion",{enableClose:true});
</script>
</div>







<?
	if ($show_phone) { ?>
		<td class="dataTableHeadingContent" valign="top"><a href="<?php echo "$PHP_SELF?listing=phone"; ?>"><?php echo tep_image_button('ic_up.gif', ' Sort Phones --> 1-2-3 From Top '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=phone-desc"; ?>"><?php echo tep_image_button('ic_down.gif', ' Sort Phones --> 3-2-1 From Top '); ?></a><br>Phone</td>
<?
	} else { ?>
                                </td><td class="dataTableHeadingContent" align="middle" valign="top"><a href="<?php echo "$PHP_SELF?listing=ra"; ?>"><?php echo tep_image_button('ic_up.gif', ' Sort ' . TABLE_HEADING_REFERRED_BY . ' --> RA first (to Top) '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=ra-desc"; ?>"><?php echo tep_image_button('ic_down.gif', ' Sort ' . TABLE_HEADING_REFERRED_BY . ' --> RA last (to Bottom)'); ?></a><br><?php echo TABLE_HEADING_REFERRED_BY; ?>&nbsp;</td>
<?
	} ?>
                </td><td class="dataTableHeadingContent" align="right" valign="top"><?php echo tep_draw_separator('pixel_trans.gif', '11', '12'); ?>&nbsp;<br><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>     
<?php  

// #### END - SORT CUSTOMERS BY ACCOUNT CREATION DATE  #### //

// BOF customer_sort_admin_v1 adapted for Separate Pricing Per Customer - modified by MegaJim
    $customers_query_raw = "select c.customers_id, c.customers_lastname, c.customers_firstname, c.customers_email_address, c.customers_group_id, c.customers_group_ra, c.customers_telephone, a.entry_country_id, a.entry_company, cg.customers_group_name from " . TABLE_CUSTOMERS . " c left join " . TABLE_ADDRESS_BOOK . " a on c.customers_id = a.customers_id and c.customers_default_address_id = a.address_book_id left join customers_groups cg on c.customers_group_id = cg.customers_group_id " . $search . " order by $order";
// EOF customer_sort_admin_v1 adapted for Separate Pricing Per Customer

//$customers_query_raw = "select c.customers_id, c.customers_lastname, c.customers_firstname, c.customers_email_address, a.entry_country_id, c.customers_advertiser, c.customers_referer_url from " . TABLE_CUSTOMERS . " c left join " . TABLE_ADDRESS_BOOK . " a on c.customers_id = a.customers_id and c.customers_default_address_id = a.address_book_id " . $search . " order by $order";
// #### COMMENTED OUT FOR CUSTOMER SORT ####  //
//c.customers_lastname, c.customers_firstname";
    $customers_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $customers_query_raw, $customers_query_numrows);
    $customers_query = tep_db_query($customers_query_raw);
    while ($customers = tep_db_fetch_array($customers_query)) {
      $info_query = tep_db_query("select customers_info_date_account_created as date_account_created, customers_info_date_account_last_modified as date_account_last_modified, customers_info_date_of_last_logon as date_last_logon, customers_info_number_of_logons as number_of_logons from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . $customers['customers_id'] . "'");
      $info = tep_db_fetch_array($info_query);

      if ((!isset($HTTP_GET_VARS['cID']) || (isset($HTTP_GET_VARS['cID']) && ($HTTP_GET_VARS['cID'] == $customers['customers_id']))) && !isset($cInfo)) {
        $country_query = tep_db_query("select countries_name from " . TABLE_COUNTRIES . " where countries_id = '" . (int)$customers['entry_country_id'] . "'");
        $country = tep_db_fetch_array($country_query);

        $reviews_query = tep_db_query("select count(*) as number_of_reviews from " . TABLE_REVIEWS . " where customers_id = '" . (int)$customers['customers_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);

        $customer_info = array_merge($country, $info, $reviews);

        $cInfo_array = array_merge($customers, $customer_info);
        $cInfo = new objectInfo($cInfo_array);
      }

      if (isset($cInfo) && is_object($cInfo) && ($customers['customers_id'] == $cInfo->customers_id)) {

?>
<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=edit')?>'" style="cursor:pointer;">

<?php } else { ?>
       <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID')) . 'cID=' . $customers['customers_id'])?>'" style="cursor:pointer;">


     <?php  } ?>

<?php 

//rmh referral start
      $source_query = tep_db_query("select customers_info_source_id  from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . $customers['customers_id'] . "'");
      $source = tep_db_fetch_array($source_query);

      $entry_referral = tep_get_sources_name($source['customers_info_source_id'], $customers['customers_id'])
//rmh referral end


?>
                <td class="dataTableContent" style="cursor:pointer;"><?php
                if (strlen($customers['entry_company']) > 16 ) {
             print ("<acronym title=\"".$customers['entry_company']."\">".substr($customers['entry_company'], 0, 16)."&#160;</acronym>");
             } else {
                echo $customers['entry_company']; } ?></td>
                <td class="dataTableContent"><?php 
                if (strlen($customers['customers_lastname']) > 15 ) {
             print ("<acronym title=\"".$customers['customers_lastname']."\">".substr($customers['customers_lastname'], 0, 15)."&#160;</acronym>");
             } else {
                echo $customers['customers_lastname']; } ?></td>
                <td class="dataTableContent"><?php
                if (strlen($customers['customers_firstname']) > 15 ) {
             print ("<acronym title=\"".$customers['customers_firstname']."\">".substr($customers['customers_firstname'], 0, 15)."&#160;</acronym>");
             } else {
            echo $customers['customers_firstname']; } ?></td>
<?
	if ($show_groups) { ?>
		<td class="dataTableContent"><?php
                if (strlen($customers['customers_group_name']) > 17 ) {
             print ("<acronym title=\"".$customers['customers_group_name']."\"> ".substr($customers['customers_group_name'], 0, 17)."&#160;</acronym>");
             } else {
                echo $customers['customers_group_name'] ;
                }  
?>

</td>

<? } ?>

            <td class="dataTableContent" align="right"><?php echo tep_date_short($info['date_account_created']); ?></td>
<?
	if ($show_phone) { ?>
		<td class="dataTableContent"><?php
                if (strlen($customers['customers_telephone']) > 17 ) {
             print ("<acronym title=\"".$customers['customers_telephone']."\"> ".substr($customers['customers_telephone'], 0, 17)."&#160;</acronym>");
             } else {
                echo $customers['customers_telephone'] ;
                }  
?></td><?
	} else {
?>
	    <td class="dataTableContent" align="right"><?php echo $entry_referral; //rmh referral ?></td>
<?
	}
?>

<!--
<td class="dataTableContent"><?php echo $customers['customers_advertiser']; ?></td>
<td class="dataTableContent"><?php echo $customers['customers_referer_url']; ?></td>-->
                <td class="dataTableContent" align="right"><?php if (isset($cInfo) && is_object($cInfo) && ($customers['customers_id'] == $cInfo->customers_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID')) . 'cID=' . $customers['customers_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
?>

              <tr><!-- BOF customer_sort_admin_v1 adapted for Separate Pricing Per Customer colspan 4 to 7 -->
                <td colspan="7"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<!-- EOF customer_sort_admin_v1 adapted for Separate Pricing Per Customer -->


                  <tr>
                    <td class="smallText" valign="top"><?php echo $customers_split->display_count($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_CUSTOMERS); ?></td>
                    <td class="smallText" align="right"><?php echo $customers_split->display_links($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_all_get_params(array('page', 'info', 'x', 'y', 'cID'))); ?></td>
                  </tr>
<?php
    if (isset($HTTP_GET_VARS['search']) && tep_not_null($HTTP_GET_VARS['search'])) {
?>
                  <tr>
                    <td align="right" colspan="2"><?php echo '<a href="' . tep_href_link(FILENAME_CUSTOMERS) . '">' . tep_image_button('button_reset.gif', IMAGE_RESET) . '</a>'; ?></td>
                  </tr>
<?php
    }
?>
                </table></td>
              </tr>

<!-- BOF Separate Pricing Per Customer: show numbers of customers in each customers group -->
<?php
   if (!isset($HTTP_GET_VARS['search'])) {
   $customers_groups_query = tep_db_query("select customers_group_id, customers_group_name from " . TABLE_CUSTOMERS_GROUPS . " order by customers_group_id ");
   while ($existing_customers_groups =  tep_db_fetch_array($customers_groups_query)) {
   $existing_customers_groups_array[] = array("id" => $existing_customers_groups['customers_group_id'], "text" => $existing_customers_groups['customers_group_name']);
   }
   $grp_select=Array(Array('id'=>'','text'=>'ALL'));
   $count_groups_query = tep_db_query("select customers_group_id, count(*) as count from " . TABLE_CUSTOMERS . " group by customers_group_id order by count desc");
   while ($count_groups = tep_db_fetch_array($count_groups_query)) {
	for ($n = 0; $n < sizeof($existing_customers_groups_array); $n++) {
		if ($count_groups['customers_group_id'] == $existing_customers_groups_array[$n]['id']) {
			$count_groups['customers_group_name'] = $existing_customers_groups_array[$n]['text'];
		}
	} // end for ($n = 0; $n < sizeof($existing_customers_groups_array); $n++)
	$count_groups_array[] = array("id" => $count_groups['customers_group_id'], "number_in_group" => $count_groups['count'], "name" => $count_groups['customers_group_name']); 
	$grp_select[]=Array("id" => $count_groups['customers_group_id'], "text" => $count_groups['customers_group_name']); 
   }
?>
              <tr>
	         <td style="padding-top: 10px;" align="center" colspan="7">
		 <table border="0" cellspacing="0" cellpadding="0" width="100%">
		 <tr><td align="left">
		 <table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #c9c9c9">
		 <tr class="dataTableHeadingRow">
		 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS_GROUPS ?></td>
		 <td class="dataTableHeadingContent">&#160;</td>
		 <td class="dataTableHeadingContent" align="right">No.</td>
		 </tr>
<?php $c = '0'; // variable used for background coloring of rows
   for ($z = 0; $z < sizeof($count_groups_array); $z++) { 
	  $bgcolor = ($c++ & 1) ? ' class="dataTableRow"' : ''; 
	   ?>
		 <tr<?php echo $bgcolor; ?>>
		 <td class="dataTableContent"><?php echo $count_groups_array[$z]['name']; ?></td>
		 <td class="dataTableContent">&#160;</td>
		 <td class="dataTableContent" align="right"><?php echo $count_groups_array[$z]['number_in_group'] ?></td>
		 </tr>
<?php 
   } // end for ($z = 0; $z < sizeof($count_groups_array); $z++) 
?>		 </table>
		</td><td class="smallText" align="right" valign="top">
		<form method="get" action="customers.php">
		<input type="hidden" name="vendors" value="1">
		Show Customer Group:<br>
		<?=tep_draw_pull_down_menu('cust_group',$grp_select,$_GET['cust_group'],'onChange="this.form.submit()"')?>
		</form>
		</td></tr></table>
		 </td>
              <tr>
<?php
  } // end if (!isset($HTTP_GET_VARS['search']))
?>
<!-- EOF Separate Pricing Per Customer: show numbers of customers in each customers group -->

            </table></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'confirm':

// BOF Separate Pricing Per Customer: dark grey field with customer name higher
      $heading[] = array('text' => ''. tep_draw_separator('pixel_trans.gif', '11', '12') .'&nbsp;<br><b>' . TEXT_INFO_HEADING_DELETE_CUSTOMER . '</b>');
// EOF Separate Pricing Per Customer

      $contents = array('form' => tep_draw_form('customers', FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO . '<br><br><b>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</b>');
      if (isset($cInfo->number_of_reviews) && ($cInfo->number_of_reviews) > 0) $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_reviews', 'on', true) . ' ' . sprintf(TEXT_DELETE_REVIEWS, $cInfo->number_of_reviews));
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (isset($cInfo) && is_object($cInfo)) {

// BOF Separate Pricing Per Customer: dark grey field with customer name higher
        $heading[] = array('text' => ''. tep_draw_separator('pixel_trans.gif', '11', '12') .'&nbsp;<br><b>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</b>');
// EOF Separate Pricing Per Customer

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=confirm') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS, 'cID=' . $cInfo->customers_id) . '">' . tep_image_button('button_orders.gif', IMAGE_ORDERS) . '</a> <a href="' . tep_href_link(FILENAME_MAIL, 'selected_box=tools&customer=' . $cInfo->customers_email_address) . '">' . tep_image_button('button_email.gif', IMAGE_EMAIL) . '</a>');
	$contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=send_password') . '">Send new password</a>');

//        $contents[] = array('align' => 'center', 'text' => '<a href="password_forgotten.php?cID=' . $cInfo->customers_id . '">Send new password</a>');
        $contents[] = array('text' => '<br>' . TEXT_DATE_ACCOUNT_CREATED . ' ' . tep_date_short($cInfo->date_account_created));
        $contents[] = array('text' => '<br>' . TEXT_DATE_ACCOUNT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->date_account_last_modified));
        $contents[] = array('text' => '<br>' . TEXT_INFO_DATE_LAST_LOGON . ' '  . tep_date_short($cInfo->date_last_logon));
        $contents[] = array('text' => '<br>' . TEXT_INFO_NUMBER_OF_LOGONS . ' ' . $cInfo->number_of_logons);
        $contents[] = array('text' => '<br>' . TEXT_INFO_COUNTRY . ' ' . $cInfo->countries_name);
        $contents[] = array('text' => '<br>' . TEXT_INFO_NUMBER_OF_REVIEWS . ' ' . $cInfo->number_of_reviews);
      }
      break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table></td>
      </tr>
<?php
//  }
?>
    </table></td>

  </tr>
</table>

<? // ########## START Manual Order Entry [1589] ##########
$customersrecords = mysql_query("SELECT * FROM customers") or die ("What Happen??? Error 1");
while($customerrows = tep_db_fetch_array($customersrecords))
{
$e = mysql_query("SELECT * FROM address_book WHERE customers_id ='$customerrows[customers_id]'") or die ("What Happen??? Error 
2");
$real = tep_db_fetch_array($e);
$updatedefaultaddress = mysql_query("UPDATE customers SET customers_default_address_id = '$real[address_book_id]' WHERE 
customers_id='$customerrows[customers_id]'") or die ("What Happen??? Error 3");
}

// Fuck
  function tep_create_random_value($length, $type = 'mixed') {
    if ( ($type != 'mixed') && ($type != 'chars') && ($type != 'digits')) return false;

    $rand_value = '';
    while (strlen($rand_value) < $length) {
      if ($type == 'digits') {
        $char = tep_rand(0,9);
      } else {
        $char = chr(tep_rand(0,255));
      }
      if ($type == 'mixed') {
        if (eregi('^[a-z0-9]$', $char)) $rand_value .= $char;
      } elseif ($type == 'chars') {
        if (eregi('^[a-z]$', $char)) $rand_value .= $char;
      } elseif ($type == 'digits') {
        if (ereg('^[0-9]$', $char)) $rand_value .= $char;
      }
    }

    return $rand_value;
  }





?>

<?php //require(DIR_WS_INCLUDES . 'footer.php'); ?>

<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
