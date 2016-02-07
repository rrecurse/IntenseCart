<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');
  
	$shoppingCart = new shoppingCart();

	global $shoppingCart;

	$magic_emails = array('support@intensecart.com');

	$action = (isset($_GET['action']) ? $_GET['action'] : '');

	$customers_id = (int)$_GET['cID'];

	if(!empty($customers_id)) { 

		$check_customer = IXdb::read("SELECT * FROM customers WHERE customers_id='".$customers_id."'");

	}

	$error = false;
	$processed = false;

 if(!empty($action)) {

    switch ($action) {

		case 'send_password':

	        include(DIR_FS_CATALOG_INCLUDES.'languages/'.$language.'/password_forgotten.php');
	        $new_password = substr(md5(rand()), 0, 8);
	        $crypted_password = tep_encrypt_password($new_password);

	        tep_db_query("UPDATE " . TABLE_CUSTOMERS . " SET customers_password = '" . tep_db_input($crypted_password) . "' WHERE customers_id = '$customers_id'");

	        tep_mail($check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], $check_customer['customers_email_address'], EMAIL_PASSWORD_REMINDER_SUBJECT, sprintf(EMAIL_PASSWORD_REMINDER_BODY, $new_password), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
			
			// # Email admin copy of password reset confirmation mail
			tep_mail($check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], STORE_OWNER_EMAIL_ADDRESS, EMAIL_PASSWORD_REMINDER_SUBJECT, sprintf(EMAIL_PASSWORD_REMINDER_BODY_ADMIN, $new_password), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

	        $messageStack->add_session('New password sent to '.$check_customer['customers_email_address'], 'success');
			$messageStack->add('New password sent to '.$check_customer['customers_email_address'], 'success');

	        //tep_redirect(tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $customers_id));
		break;

		case 'update':

	        $customers_firstname = tep_db_input($_POST['customers_firstname']);
    	    $customers_lastname = tep_db_input($_POST['customers_lastname']);
	        $customers_email_address = tep_db_input($_POST['customers_email_address']);
    	    $customers_telephone = tep_db_input($_POST['customers_telephone']);
        	$customers_fax = tep_db_input($_POST['customers_fax']);
	        $customers_newsletter = tep_db_input($_POST['customers_newsletter']);
			$customers_group_id = tep_db_input($_POST['customers_group_id']);
			$customers_group_ra = tep_db_input($_POST['customers_group_ra']);

			// # Password reset for customer edit form.
			$existing_customers_password = tep_db_result(tep_db_query("SELECT customers_password FROM ". TABLE_CUSTOMERS ." WHERE customers_id = '".(int)$_GET['cID']."'"));
	        $new_password = tep_db_input($_POST['customers_password']);
	        $customers_password = tep_encrypt_password($new_password);

			if(isset($customers_group_ra) && $customers_group_ra == '1') {

				$toName = $customers_firstname.' '.$customers_lastname;
				$toEmail = $customers_email_address;
				$emailSubject = EMAIL_VENDOR_CONFIRM_SUBJECT;
				$emailText = sprintf(EMAIL_VENDOR_CONFIRM_BODY, $toName, $customers_email_address);
				$fromName = STORE_OWNER;
				$fromEmail = STORE_OWNER_EMAIL_ADDRESS; 
	
				tep_mail($toName, $toEmail, $emailSubject, $emailText, $fromName, $fromEmail);

				$messageStack->add_session('Approval email sent to '. $check_customer['customers_email_address'] , 'success');
				$messageStack->add('Approval email sent to '.$check_customer['customers_email_address'], 'success');
	 		}
	
	
			$entry_company_tax_id = tep_db_input($_POST['entry_company_tax_id']);

			if ($_POST['customers_payment_allowed'] && $_POST['customers_payment_settings'] == '1') {

				$customers_payment_allowed = tep_db_input($_POST['customers_payment_allowed']);

			} else { // # no error with subsequent re-posting of variables	

				$customers_payment_allowed = '';

				if ($_POST['payment_allowed'] && $_POST['customers_payment_settings'] == '1') {

					while(list($key, $val) = each($_POST['payment_allowed'])) {
			    		if($val == true) $customers_payment_allowed .= tep_db_input($val).';';
					 }
					
					$customers_payment_allowed = substr($customers_payment_allowed,0,strlen($customers_payment_allowed)-1);

				}

			} // # end else ($_POST['customers_payment_allowed']

			if ($_POST['customers_shipment_allowed'] && $_POST['customers_shipment_settings'] == '1') {

				$customers_shipment_allowed = tep_db_input($_POST['customers_shipment_allowed']);

			} else { // no error with subsequent re-posting of variables	

				$customers_shipment_allowed = '';
	
				if ($_POST['shipping_allowed'] && $_POST['customers_shipment_settings'] == '1') {
	
					while(list($key, $val) = each($_POST['shipping_allowed'])) {
					    if ($val == true) { 
						    $customers_shipment_allowed .= tep_db_input($val).';'; 
					    }
					} // end while

					$customers_shipment_allowed = substr($customers_shipment_allowed,0,strlen($customers_shipment_allowed)-1);

				} // end if ($_POST['shipment_allowed'])

			} // end else ($_POST['customers_shipment_allowed']

// EOF Separate Pricing per Customer

        $customers_referred_by = tep_db_input($_POST['customers_referred_by']);

        $customers_gender = tep_db_input($_POST['customers_gender']);
        $customers_dob = tep_db_input($_POST['customers_dob']);

        $default_address_id = tep_db_input($_POST['default_address_id']);
        $entry_street_address = tep_db_input($_POST['entry_street_address']);
        $entry_suburb = tep_db_input($_POST['entry_suburb']);
        $entry_postcode = tep_db_input($_POST['entry_postcode']);
        $entry_city = tep_db_input($_POST['entry_city']);
        $entry_country_id = tep_db_input($_POST['entry_country_id']);
		$member_flag = tep_db_input($_POST['member_flag']);

        $entry_company = tep_db_input($_POST['entry_company']);
        $entry_state = tep_db_input($_POST['entry_state']);
        if (isset($_POST['entry_zone_id'])) $entry_zone_id = tep_db_input($_POST['entry_zone_id']);

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
            $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$entry_country_id . "'");
            $check_value = tep_db_fetch_array($check_query);
            $entry_state_has_zones = ($check_value['total'] > 0);
            if ($entry_state_has_zones == true) {
              $zone_query = tep_db_query("select zone_id from " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$entry_country_id . "' and zone_name = '" . tep_db_input($entry_state) . "'");
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

      $check_email = tep_db_query("select customers_email_address from " . TABLE_CUSTOMERS . " WHERE customers_email_address = '" . tep_db_input($customers_email_address) . "' and customers_id != '" . (int)$customers_id . "'");
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
                                'customers_group_id' => $customers_group_id,
                                'customers_group_ra' => $customers_group_ra,
								'customers_payment_allowed' => $customers_payment_allowed,
								'customers_shipment_allowed' => $customers_shipment_allowed,
								'customers_referred_by' => $customers_referred_by,
								'member_flag' => $member_flag,
								'customers_password' => (!empty($_POST['customers_password']) ? $customers_password : $existing_customers_password)
								);

        if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $customers_gender;
        if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($customers_dob);

        tep_db_perform(TABLE_CUSTOMERS, $sql_data_array, 'update', "customers_id = '" . (int)$customers_id . "'");
	
	if(isset($_POST['extra'])) {
		$xlst = array();

		foreach ($_POST['extra'] AS $xkey=>$xval) {
			$xlst[]="('$customers_id','$xkey','$xval')";
		}

		if($xlst) { 
			IXdb::query("REPLACE INTO customers_extra (customers_id,customers_extra_key,customers_extra_value) VALUES ".join(',',$xlst));
		}
	}

        tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set customers_info_date_account_last_modified = now() where customers_info_id = '" . (int)$customers_id . "'");

        if ($entry_zone_id > 0) $entry_state = '';

        $sql_data_array = array('entry_firstname' => $customers_firstname,
                                'entry_lastname' => $customers_lastname,
                                'entry_street_address' => $entry_street_address,
                                'entry_postcode' => $entry_postcode,
                                'entry_city' => $entry_city,
                                'entry_country_id' => $entry_country_id);

		if (ACCOUNT_COMPANY == 'true') {
        	$sql_data_array['entry_company'] = $entry_company;
         	$sql_data_array['entry_company_tax_id'] = $entry_company_tax_id;
        } 

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
        $customers_id = tep_db_input($_GET['cID']);
	
	if (in_array(strtolower(IXdb::read("SELECT customers_email_address AS email FROM customers WHERE customers_id='$customers_id'",NULL,'email')),$magic_emails)) {
          $messageStack->add_session('You are not permitted to delete this account', 'error');
          tep_redirect(tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action'))));
	}

        if (isset($_POST['delete_reviews']) && ($_POST['delete_reviews'] == 'on')) {
          $reviews_query = tep_db_query("select reviews_id from " . TABLE_REVIEWS . " WHERE customers_id = '" . (int)$customers_id . "'");
          while ($reviews = tep_db_fetch_array($reviews_query)) {
            tep_db_query("DELETE FROM " . TABLE_REVIEWS_DESCRIPTION . " WHERE reviews_id = '" . (int)$reviews['reviews_id'] . "'");
          }

          tep_db_query("DELETE FROM " . TABLE_REVIEWS . " WHERE customers_id = '" . (int)$customers_id . "'");
        } else {
          tep_db_query("update " . TABLE_REVIEWS . " set customers_id = null where customers_id = '" . (int)$customers_id . "'");
        }

// # Once all customers with a specific customers_group_id have been deleted from
// # the table customers, the next time a customer is deleted, all entries in the table products_groups
// # that have the (now apparently obsolete) customers_group_id will be deleted!
// # If you don't want that, leave this section out, or comment it out
// # Note that when customers groups are deleted from the table customers_groups, all the
// # customers with that specific customer_group_id will be changed to customer_group_id = '0' (default/Retail)

	$multiple_groups_query = tep_db_query("select customers_group_id from " . TABLE_CUSTOMERS_GROUPS . " ");

	while($group_ids = tep_db_fetch_array($multiple_groups_query)) {
		$multiple_customers_query = tep_db_query("select distinct customers_group_id from " . TABLE_CUSTOMERS . " WHERE customers_group_id = " . $group_ids['customers_group_id'] . " ");

		if(!($multiple_groups = tep_db_fetch_array($multiple_customers_query))) {
			tep_db_query("DELETE FROM " . TABLE_PRODUCTS_GROUPS . " WHERE customers_group_id = '" . $group_ids['customers_group_id'] . "'");
		}
	}

        tep_db_query("DELETE FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . (int)$customers_id . "'");
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS . " WHERE customers_id = '" . (int)$customers_id . "'");
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_INFO . " WHERE customers_info_id = '" . (int)$customers_id . "'");
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_BASKET . " WHERE customers_id = '" . (int)$customers_id . "'");
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " WHERE customers_id = '" . (int)$customers_id . "'");
        tep_db_query("DELETE FROM " . TABLE_WHOS_ONLINE . " WHERE customer_id = '" . (int)$customers_id . "'");
		tep_db_query("DELETE FROM " . TABLE_SOURCES_OTHER . " WHERE customers_id = '" . (int)$customers_id . "'");

        tep_redirect(tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action'))));
        break;

		
		case 'export':

			$customers_group = (isset($_GET['cust_group']) && $_GET['cust_group'] != 'all' ? $_GET['cust_group'] : '');

	        $customers_query = tep_db_query("SELECT c.customers_id AS `Customer ID`, 
													c.customers_firstname AS `Customer First Name`, 
													c.customers_lastname AS `Customer Last Name`, 
													c.customers_email_address AS `Customer Email Address`, 
													c.customers_password AS `Password (Hashed)`, 			
													c.customers_telephone AS `Customer Phone`,  
													cg.customers_group_name AS `Customer Group`,
													ci.customers_info_date_account_created AS `Date Created`,
													a.entry_company AS `Company Name`, 
													a.entry_company_tax_id AS `Tax ID / EIN`, 
													a.entry_street_address AS `Street Address`, 
													a.entry_suburb AS `Address 2`, 
													a.entry_postcode AS `Postal code`, 
													a.entry_city AS `City`, 
													a.entry_state AS `State`, 
													cn.countries_name AS `Country`
											FROM " . TABLE_CUSTOMERS . " c 
											LEFT JOIN " . TABLE_ADDRESS_BOOK . " a ON (a.customers_id = c.customers_id AND a.address_book_id = c.customers_default_address_id)
											LEFT JOIN ". TABLE_CUSTOMERS_INFO." ci ON ci.customers_info_id = c.customers_id	
											LEFT JOIN ". TABLE_CUSTOMERS_GROUPS ." cg ON cg.customers_group_id = c.customers_group_id
											LEFT JOIN " . TABLE_COUNTRIES . " cn ON cn.countries_id = a.entry_country_id
											" . (!empty($customers_group) ? " WHERE c.customers_group = '".$customers_group."'" : ""));


			$num_fields = mysql_num_fields($customers_query);

			$headers = array();
			for ($i = 0; $i < $num_fields; $i++) {
    			$headers[] = mysql_field_name($customers_query , $i);
			}

			$fp = fopen('php://output', 'w');

			if ($fp && $customers_query) {
	    		header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="customers'.(!empty($customers_group) ? '_group-'.$customers_group : '').'_'.date('m-d-Y').'.csv"');
    			header('Pragma: no-cache');
		    	header('Expires: 0');
	    		fputcsv($fp, $headers);

			    while ($row = mysql_fetch_row($customers_query)) {
    			    fputcsv($fp, array_values($row));
	    		}
    	
				exit();
			}

        break;

      default:

        $module_directory = DIR_FS_CATALOG_MODULES . 'payment/';
        $ship_module_directory = DIR_FS_CATALOG_MODULES . 'shipping/';

        $customers_query = tep_db_query("SELECT c.customers_id, 
												c.customers_gender, 
												c.customers_firstname, 
												c.customers_lastname, 
												c.customers_dob,
												c.customers_email_address,  
												c.customers_telephone, 
												c.customers_fax, 
												c.customers_newsletter, 
												c.member_flag, 
												c.customers_group_id, 
												c.customers_group_ra, 
												c.customers_payment_allowed, 
												c.customers_shipment_allowed, 
												c.customers_referred_by, 
												c.customers_default_address_id,
												ci.customers_info_date_of_last_logon AS date_last_logon,
												ci.customers_info_number_of_logons AS number_of_logons,
												ci.customers_info_date_account_created AS date_account_created,
												ci.customers_info_date_account_last_modified AS date_account_last_modified,
												a.entry_company, 
												a.entry_company_tax_id, 
												a.entry_street_address, 
												a.entry_suburb, 
												a.entry_postcode, 
												a.entry_city, 
												a.entry_state, 
												a.entry_zone_id, 
												a.entry_country_id,
												cn.countries_name,
										COUNT(r.reviews_id) AS number_of_reviews
										FROM " . TABLE_CUSTOMERS . " c 
										LEFT JOIN " . TABLE_ADDRESS_BOOK . " a ON (a.customers_id = c.customers_id AND a.address_book_id = c.customers_default_address_id)
										LEFT JOIN ". TABLE_CUSTOMERS_INFO." ci ON ci.customers_info_id = c.customers_id	
										LEFT JOIN " . TABLE_COUNTRIES . " cn ON cn.countries_id = a.entry_country_id
										LEFT JOIN " . TABLE_REVIEWS . " r ON r.customers_id = c.customers_id
										WHERE c.customers_id = '" . (int)$customers_id . "'
										");

        $customers = tep_db_fetch_array($customers_query);
        $cInfo = new objectInfo($customers);

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
	

    }
  }
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<?php
  if ($action == 'edit' || $action == 'update') {
?>
<script type="text/javascript">

<!--

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
//-->
</script>
<?php
  }
?>
<style>


@media screen and (min-width: 1451px) {

	* {
		font-size:100% !important;
	}

}

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

.pagejump select {
	font:normal 8pt verdana;
}

.dataTableRow td {
	padding:5px;
	text-transform: capitalize;
}
.dataTableRowSelected td {
	padding:5px;
	background-color:#FFFFC4 !important;
	text-transform: capitalize;
	font:bold 11px arial;
}
</style>


</head>
<body style="margin:5px 0 0 0; background-color:#F0F5FB"">
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

    $member_flag_array = array(array('id' => '1', 'text' => ENTRY_MEMBER_YES),
                              array('id' => '0', 'text' => ENTRY_MEMBER_NO)); 							  

?>
	<tr>

<?php 

		echo tep_draw_form('customers', FILENAME_CUSTOMERS, tep_get_all_get_params(array('action')) . 'action=update', 'post', 'onsubmit="return check_form();"') . tep_draw_hidden_field('default_address_id', $cInfo->customers_default_address_id); ?>

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

          <tr>
            <td class="main">New Password:</td>
            <td class="main">
<?php echo tep_draw_input_field('customers_password', '', 'id="customers_password" maxlength="32"');?> &nbsp; 
<a href="javascript:randomPassword(8, '0123456789abcdefghijklmnopqrstuvwxyz')">Generate random password</a>
<script>
function randomPassword(length, chars) {
    var result = '';
    for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
	document.getElementById("customers_password").value = result;
    //return;
}

</script>
</td>
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
          $zones_query = tep_db_query("select zone_name from " . TABLE_ZONES . " WHERE zone_country_id = '" . tep_db_input($cInfo->entry_country_id) . "' order by zone_name");
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

<tr>
            <td class="main" width="100" nowrap><?php echo ENTRY_NEWSLETTER; ?></td>
            <td class="main" align="left">
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
<?php
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
  <td class="main">

<?php 
if($processed == true) {
    echo $cInfo->customers_group_id . tep_draw_hidden_field('customers_group_id');
  } else {	
  echo tep_draw_pull_down_menu('customers_group_id', $existing_customers_array, $cInfo->customers_group_id);
  } ?>
</td>
</tr>

         <tr>
            <td class="main" width="100" nowrap><?php echo ENTRY_MEMBER; ?></td>
            <td class="main" align="left"><?php echo tep_draw_pull_down_menu('member_flag', $member_flag_array, $cInfo->member_flag); ?></td>
          </tr>		  
        </table></td>
      </tr>    
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
            <td class="main" colspan="2">
<?php 

	if($processed == true) {

		if ($cInfo->customers_shipment_settings == '1') {
			echo ENTRY_CUSTOMERS_SHIPPING_SET ;
			echo ' : ';
	    } else {
			echo ENTRY_CUSTOMERS_SHIPPING_DEFAULT;
	    }  

	    echo tep_draw_hidden_field('customers_shipment_settings');

	} else {

            echo tep_draw_radio_field('customers_shipment_settings', '1', false, (tep_not_null($cInfo->customers_shipment_allowed)? '1' : '0' )) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_SHIPPING_SET . '&nbsp;&nbsp;' . tep_draw_radio_field('customers_shipment_settings', '0', false, (tep_not_null($cInfo->customers_shipment_allowed)? '1' : '0' )) . '&nbsp;&nbsp;' . ENTRY_CUSTOMERS_SHIPPING_DEFAULT ; 

	} 

?>

</td>
	  </tr>
<?php 

		if ($processed != true) {
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
					}
 						echo '<tr>
								<td class="main" colspan="2">'. tep_draw_checkbox_field('shipping_allowed[' . $i . ']', $ship_module->code.'.php' , (in_array ($ship_module->code.'.php', $shipment_allowed)) ?  1 : 0) .'
&#160;&#160;'. $ship_module->title.'</td></tr>';
				} 
			}
?>
	   <tr>
            <td class="main" colspan="2" style="padding-left: 30px; padding-right: 10px; padding-top: 10px;"><?php echo ENTRY_CUSTOMERS_SHIPPING_SET_EXPLAIN ?></td>
           </tr>
<?php 
   } else {
?>
	    <tr>
            <td class="main" colspan="2">
<?php 
		if ($cInfo->customers_shipment_settings == '1') {
			echo $customers_shipment_allowed;
		} else {
			echo ENTRY_CUSTOMERS_SHIPPING_DEFAULT;
	    } 
		    echo tep_draw_hidden_field('customers_shipment_allowed'); 
?>
</td>
	  </tr>
<?php 
 }
?>
   </td>
	  </tr>
	 </table>
	</td>
      </tr>

<?php

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
        <td class="formAreaTitle"><?php echo $mod->getName()?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
<?php
      foreach ($flds AS $fld) {
?>
  <tr><td class="main"><?php echo $fld['title']?></td><td><?php echo $fld['html']?></td></tr>
<?php
      }
?>
	 </table>
	</td>
      </tr>
<?php
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

	switch ($listing) {
	
	case "id-asc":
		$order = "c.customers_id ASC";
	break;

	case "cg_name":
		$order = "cg.customers_group_name ASC, c.customers_id ASC";
	break;

	case "cg_name-desc":
		$order = "cg.customers_group_name DESC, c.customers_id DESC";
	break;

	case "firstname":
		$order = "c.customers_firstname ASC, c.customers_id ASC";
	break;

	case "firstname-desc":
		$order = "c.customers_firstname DESC, c.customers_id DESC";
	break;

	case "company":
		$order = "a.entry_company ASC, c.customers_id ASC";
	break;

	case "company-desc":
		$order = "a.entry_company DESC, c.customers_id DESC";
	break;

	case "ra":
		$order = "c.customers_group_ra ASC";
	break;

	case "ra-desc":
		$order = "c.customers_group_ra DESC";
	break;

	case "lastname":
		$order = "c.customers_lastname ASC, c.customers_id";
	break;

	case "lastname-desc":
		$order = "c.customers_lastname DESC, c.customers_id DESC";
	break;

	default:

		if(isset($_GET['cust_group']) && $_GET['cust_group'] > 1) {
			$show_groups=1;
			$search = " c.customers_group_id > 1";
    	} elseif(!empty($_GET['find_name'])) {
			$show_groups=1;
			$search = " c.customers_group_id >= 0";
		} else {
			$show_groups=0;
			$search = " c.customers_group_id = '".$_GET['cust_group']."'";
		}

		$order = "ci.customers_info_date_account_created DESC, c.customers_id DESC";
	}

    $show_phone = 0;

	if(!empty($_GET['find_name'])) {

		$name_cond = array();

		$whole_name = (!empty($_GET['find_name']) ? tep_db_input(str_replace("'", " " , $_GET['find_name'])) : '');
	
		if(!empty($whole_name)) { 
	
			$find_name = explode(' ', $whole_name, 2);

			if(sizeof($find_name) > 1){


				$name_cond[] = "(c.customers_firstname LIKE '%". $find_name[0] ."%' AND c.customers_lastname LIKE '%". $find_name[1] ."%')
								 OR a.entry_company LIKE '%". $find_name[0]. ' ' . $find_name[1] ."%'
							 ". (preg_match('/@/',$find_name[0]) ? " OR c.customers_email_address LIKE '%". $find_name[0] ."%'" : "");

			} else {
			
				$name_cond[] = "(c.customers_firstname LIKE '%". $find_name[0] ."%' 
								 OR c.customers_lastname LIKE '%". $find_name[0] ."%'
								 OR a.entry_company LIKE '%". $find_name[0] ."%'
								 ". (preg_match('/@/',$find_name[0]) ? " OR c.customers_email_address LIKE '%". $find_name[0] ."%'" : "")."
								)";
			}
				// # clean up whitespace and tabs.
				$name_cond = preg_replace('/\s+/S', ' ', $name_cond);
		

			if(sizeof($name_cond) > 0) {
				$search .= " AND (".join(' OR ',$name_cond).")";
				$order = join(' + ',$name_cond)." DESC";
			}

	}

	} elseif(!empty($_GET['find_phone'])) {

		$phone = preg_replace('/([^0-9])/', '', ltrim($_GET['find_phone'], '1'));

		$search .= " AND REPLACE(REPLACE(REPLACE(REPLACE(TRIM(c.customers_telephone),' ',''), '(',''), ')', ''), '-','') = '". $phone ."'";

		$show_phone = 1;

    } 
?>

<td valign="top">  
	     
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
            <td colspan="3" align="right" style="padding:0 0 5px 0;">

<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
<td width="58" style="padding:0 0 0 5px"><img src="/admin/images/customers-icon.gif" width="48" height="48" alt=""></td>
<td class="pageHeading">
<?php 

	if($_GET['cust_group'] > 1) {
		echo  'Vendors';
	} elseif($_GET['cust_group'] == '1') {
		echo 'Pending Approval';
	} else {
		echo 'Customers';
	}

?>
</td>
<td align="right">

<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td class="smallText" align="right" nowrap>

<?php 

	echo tep_draw_form('customer_search', FILENAME_CUSTOMERS,  'cID=' . $cInfo->customers_id . '&action=default' . (!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '').(!empty($_GET['listing']) ? '&listing='.$_GET['listing'] : ''), 'GET');

	echo tep_draw_hidden_field('cust_group',$_GET['cust_group']);

	echo '<b>Name Search:</b> ' . tep_draw_input_field('find_name', (!empty($whole_name) ? $whole_name : 'First or Last or Both'), 'size="18" onclick="this.value==\'First or Last or Both\'?this.value=\'\':this.value;" onfocus="this.select()" onblur="this.value=!this.value?\'First or Last or Both\':this.value;"');

?>
<input type="submit" value=" &raquo; ">
</form>
</td>
</tr>
<tr>
<td class="smallText" align="right" nowrap>
<?php 
	echo tep_draw_form('phone_search', FILENAME_CUSTOMERS, 'action=default' . (isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '').(!empty($_GET['listing']) ? '&listing='.$_GET['listing'] : ''), '', 'GET');

	echo tep_draw_hidden_field('cust_group',$_GET['cust_group']);
	echo 'Phone # Search:';

	echo tep_draw_input_field('find_phone',(isset($_POST['find_phone'])?$_POST['find_phone']:''),' size="18"');
?>
<input type="submit" value=" &raquo; ">
</form>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
<table border="0" width="100%" cellspacing="0" cellpadding="5">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" valign="top"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'company-desc' ? 'company':'company-desc').(!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_CUSTOMERS_COMPANY; ?></a></td>

                <td class="dataTableHeadingContent" valign="top"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'lastname-desc' ? 'lastname':'lastname-desc').(!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_LASTNAME; ?></a></td>

                <td class="dataTableHeadingContent" valign="top"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'firstname-desc' ? 'firstname':'firstname-desc').(!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_FIRSTNAME; ?></a></td>

<?php if ($show_groups) { ?>

		<td class="dataTableHeadingContent" valign="top"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'cg_name-desc' ? 'cg_name':'cg_name-desc').(!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_CUSTOMERS_GROUPS; ?></a></td>
<?php }  ?>
 		<td class="dataTableHeadingContent" align="right" valign="top">
<a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'id-asc' ? 'id-desc':'id-asc').(!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_ACCOUNT_CREATED; ?></a></td>
<?php
	if ($show_phone) { ?>
		<td class="dataTableHeadingContent" valign="top"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'phone' ? 'phone-desc':'phone').(!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo CUSTOMERS_PHONE; ?></a></td>
<?php

} else { 
?>
<td class="dataTableHeadingContent" align="right" valign="top"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'ra' ? 'ra-desc':'ra').(!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_REFERRED_BY; ?></a>&nbsp;</td>

<?php } ?>
</tr>     
<?php 

// #### END - SORT CUSTOMERS BY ACCOUNT CREATION DATE  #### //

		$customers_query_raw = "SELECT c.customers_id, 
									   c.customers_lastname, 
									   c.customers_firstname, 
									   c.customers_email_address, 
									   c.customers_group_id, 
									   c.customers_group_ra, 
									   c.customers_telephone, 
									   a.entry_country_id, 
									   a.entry_company, 
									   cg.customers_group_name,
									   ci.customers_info_date_account_created AS date_account_created, 
									   ci.customers_info_date_account_last_modified AS date_account_last_modified, 
									   ci.customers_info_date_of_last_logon AS date_last_logon, 
									   ci.customers_info_number_of_logons AS number_of_logons,
									   cn.countries_name,
									   (SELECT COUNT(reviews_id) FROM ". TABLE_REVIEWS. " WHERE customers_id = '".$cID."') AS number_of_reviews
								FROM " . TABLE_CUSTOMERS . " c 
								LEFT JOIN " . TABLE_ADDRESS_BOOK . " a ON (a.customers_id = c.customers_id AND a.address_book_id = c.customers_default_address_id)
								LEFT JOIN customers_groups cg ON cg.customers_group_id = c.customers_group_id
								LEFT JOIN ". TABLE_CUSTOMERS_INFO." ci ON ci.customers_info_id = c.customers_id	
								LEFT JOIN " . TABLE_COUNTRIES . " cn ON cn.countries_id = a.entry_country_id
								". (!empty($search) ? "WHERE " . $search : "") . " 
								ORDER BY $order";

    $customers_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $customers_query_raw, $customers_query_numrows);

    $customers_query = tep_db_query($customers_query_raw);


    while ($customers = tep_db_fetch_array($customers_query)) {

		if((!isset($_GET['cID']) || (isset($_GET['cID']) && ($_GET['cID'] == $customers['customers_id']))) && !isset($cInfo)) {

    	    $cInfo = new objectInfo((array)$customers);

		}

		if (isset($cInfo) && is_object($cInfo) && ($customers['customers_id'] == $cInfo->customers_id)) {

			echo '<tr id="defaultSelected" class="dataTableRowSelected" onclick="document.location.href=\'' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=edit').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '') . '\'" style="cursor:pointer;">' . "\n";
      } else {
        echo ' <tr class="'.($ct++&1 ? 'tabEven' : 'tabOdd').' dataTableRow" onclick="document.location.href=\'' . tep_href_link(FILENAME_CUSTOMERS, 'cID=' . $customers['customers_id'].'&action=default' .  (!empty($whole_name) ? '&find_name='.$whole_name : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : ''))  . '\'" style="cursor:pointer;">' . "\n";
      }

      $source_query = tep_db_query("select customers_info_source_id  FROM " . TABLE_CUSTOMERS_INFO . " WHERE customers_info_id = '" . $customers['customers_id'] . "'");
      $source = tep_db_fetch_array($source_query);

      $entry_referral = tep_get_sources_name($source['customers_info_source_id'], $customers['customers_id']);
?>
                <td class="dataTableContent" width="27%">
<?php 
		echo '<span title="'.$customers['entry_company'].'">';
		if (strlen($customers['entry_company']) > 26 ) {
			echo substr($customers['entry_company'], 0, 26);
		} else {
			echo $customers['entry_company']; 
		} 
?>
		</span>
		</td>
		<td class="dataTableContent">
<?php 
		if (strlen($customers['customers_lastname']) > 15 ) {
			echo '<acronym title="'.$customers['customers_lastname'].'">'.substr($customers['customers_lastname'], 0, 15).'&#160;</acronym>';
		} else {
			echo $customers['customers_lastname']; 
		} 
?>
			</td>
			<td class="dataTableContent">
<?php
		if (strlen($customers['customers_firstname']) > 15 ) {
			echo '<acronym title="'.$customers['customers_firstname'].'">'.substr($customers['customers_firstname'], 0, 15).'&#160;</acronym>';
		} else {
			echo $customers['customers_firstname']; 
		} ?>
			</td>
<?php
	if ($show_groups) { ?>
		<td class="dataTableContent"><?php
                if (strlen($customers['customers_group_name']) > 17 ) {
             print ("<acronym title=\"".$customers['customers_group_name']."\"> ".substr($customers['customers_group_name'], 0, 17)."&#160;</acronym>");
             } else {
                echo $customers['customers_group_name'] ;
                }  
?></td>
<?php } ?>

            <td class="dataTableContent" align="right"><?php echo tep_date_short($customers['date_account_created']); ?></td>
<?php
	if ($show_phone) { 
?>
		<td class="dataTableContent">
<?php
			if (strlen($customers['customers_telephone']) > 17 ) {
            	 print ("<acronym title=\"".$customers['customers_telephone']."\"> ".substr($customers['customers_telephone'], 0, 17)."&#160;</acronym>");
			} else {
            	echo $customers['customers_telephone'] ;
            }  
?></td>
<?php
	} else {
?>
	    <td class="dataTableContent" align="right"><?php echo $entry_referral; //rmh referral ?></td>
<?php
	}
?>

<!--
<td class="dataTableContent"><?php echo $customers['customers_advertiser']; ?></td>
<td class="dataTableContent"><?php echo $customers['customers_referer_url']; ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($cInfo) && is_object($cInfo) && ($customers['customers_id'] == $cInfo->customers_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID')) . 'cID=' . $customers['customers_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>-->
              </tr>
<?php
    }
?>

              <tr>
                <td colspan="7">

					<table border="0" width="100%" cellspacing="0" cellpadding="2">
		                  <tr>
        		            <td class="smallText" valign="top"><?php echo $customers_split->display_count($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_CUSTOMERS); ?></td>
                		    <td class="smallText" align="right"><?php echo $customers_split->display_links($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'info', 'x', 'y', 'cID'))); ?></td>
		                  </tr>
<?php
    if (isset($_GET['search']) && tep_not_null($_GET['search'])) {
?>
        		          <tr>
                		    <td align="right" colspan="2"><?php echo '<a href="' . tep_href_link(FILENAME_CUSTOMERS) . '">' . tep_image_button('button_reset.gif', IMAGE_RESET) . '</a>'; ?></td>
		                  </tr>
<?php
    }
?>
        			</table>
				</td>
			</tr>
		</table>
<?php
   if (!isset($_GET['search'])) {

		$customers_groups_query = tep_db_query("SELECT customers_group_id, customers_group_name 
												FROM " . TABLE_CUSTOMERS_GROUPS . " 
												ORDER BY customers_group_id
											   ");
		while ($existing_customers_groups =  tep_db_fetch_array($customers_groups_query)) {

			$existing_customers_groups_array[] = array("id" => $existing_customers_groups['customers_group_id'], 
													   "text" => $existing_customers_groups['customers_group_name']
													  );
		}

		$grp_select = array(array('id'=>'','text'=>'ALL'));

		$count_groups_query = tep_db_query("SELECT customers_group_id, 
											COUNT(*) AS count 
											FROM " . TABLE_CUSTOMERS . " 
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
?>
	<table width="100%">
		<tr>
			<td style="padding-top: 10px;" align="center">
				<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td align="left">
							<table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #c9c9c9">
								 <tr class="dataTableHeadingRow">
									 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS_GROUPS ?></td>
									 <td class="dataTableHeadingContent" align="right">No.</td>
								 </tr>
<?php 

	for ($z = 0; $z < sizeof($count_groups_array); $z++) { 
		
		echo '					<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'">
									<td class="dataTableContent">'. $count_groups_array[$z]['name'].'</td>
									<td class="dataTableContent" align="right">'. $count_groups_array[$z]['number_in_group'] .'</td>
								</tr>';
	}

	$customers_group = (!empty($_GET['cust_group']) ? $_GET['cust_group'] : 'all');
?>
							</table>
					
							<table>
								<tr>
									<td valign="top" style="padding-top:20px;">
										<img src="/admin/images/icons/excel_csv.png"> <a href="customers.php?action=export&cust_group=<?php echo $customers_group ?>"><b>Export Customers</b></a>
									</td>
								</tr>
							</table>
						</td>

						<td class="smallText" align="right" valign="top">
<?php 

	echo tep_draw_form('cust_groups', FILENAME_CUSTOMERS, 'action=default' . (!empty($cInfo->customers_id) ? '&cID='.$cInfo->customers_id : '') . (!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '').(!empty($_GET['listing']) ? '&listing='.$_GET['listing'] : ''), 'GET');

	echo 'Show Customer Group:<br>' .
	tep_draw_hidden_field('find_name', $_GET['find_name']) .
	tep_draw_hidden_field('listing', $_GET['listing']) . 
	tep_draw_hidden_field('cID', $cInfo->customers_id) . 	

	tep_draw_pull_down_menu('cust_group', $grp_select, $_GET['cust_group'], ' onchange="this.form.submit()"');

	echo '</form>';

?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
<?php
  } // # end if
?>
</table>
</td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'confirm':

      $heading[] = array('text' => ''. tep_draw_separator('pixel_trans.gif', '11', '12') .'&nbsp;<br><b>' . TEXT_INFO_HEADING_DELETE_CUSTOMER . '</b>');


      $contents = array('form' => tep_draw_form('customers', FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=deleteconfirm' . (!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(!empty($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '').(!empty($_GET['listing']) ? '&listing='.$_GET['listing'] : '')));
      $contents[] = array('text' => TEXT_DELETE_INTRO . '<br><br><b>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</b>');
      if (isset($cInfo->number_of_reviews) && ($cInfo->number_of_reviews) > 0) $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_reviews', 'on', true) . ' ' . sprintf(TEXT_DELETE_REVIEWS, $cInfo->number_of_reviews));
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;


	default:

      if (isset($cInfo) && is_object($cInfo)) {

        $heading[] = array('text' => ''. tep_draw_separator('pixel_trans.gif', '11', '12') .'&nbsp;<br><b>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CUSTOMERS, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=confirm') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS, 'cID=' . $cInfo->customers_id) . '">' . tep_image_button('button_orders.gif', IMAGE_ORDERS) . '</a> <a href="' . tep_href_link(FILENAME_MAIL, 'selected_box=tools&customer=' . $cInfo->customers_email_address) . '">' . tep_image_button('button_email.gif', IMAGE_EMAIL) . '</a>');

		$contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CUSTOMERS, 'action=send_password&cID='.$cInfo->customers_id . (!empty($_GET['find_name']) ? '&find_name='.$_GET['find_name'] : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '').(!empty($_GET['listing']) ? '&listing='.$_GET['listing'] : '')) . '"><b>'.RESET_CUSTOMER_PASSWORD.'</b></a>');

        $contents[] = array('text' => '<br>' . CUSTOMERS_NAME . ' ' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname); 

		if(!empty($cInfo->entry_company)) { 
      		$contents[] = array('text' => '<br>' . CUSTOMERS_COMPANY . ' ' . $cInfo->entry_company);
		}

		$phone = preg_replace('/([^0-9,.])/', '', ltrim($cInfo->customers_telephone, '1'));

        $contents[] = array('text' => '<br>' . CUSTOMERS_EMAIL . ' ' . $cInfo->customers_email_address);
        $contents[] = array('text' => '<br>' . CUSTOMERS_PHONE . ' ('.substr($phone, 0, 3).') '.substr($phone, 3, 3).'-'.substr($phone,6));
        $contents[] = array('text' => '<br>' . TEXT_INFO_COUNTRY . ' ' . $cInfo->countries_name);
        $contents[] = array('text' => '<br>' . TEXT_DATE_ACCOUNT_CREATED . ' ' . tep_date_short($cInfo->date_account_created));
        $contents[] = array('text' => '<br>' . TEXT_DATE_ACCOUNT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->date_account_last_modified));
        $contents[] = array('text' => '<br>' . TEXT_INFO_DATE_LAST_LOGON . ' '  . tep_date_short($cInfo->date_last_logon));
        $contents[] = array('text' => '<br>' . TEXT_INFO_NUMBER_OF_LOGONS . ' ' . $cInfo->number_of_logons);
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
  }
?>
    </table></td>

  </tr>
</table>

<?php
/* 
	// # not sure why we have this. seems like a patch to correct customer address_book_id inside customers table.
	$customersrecords = tep_db_query("SELECT customers_id FROM customers");

	while($customerrows = tep_db_fetch_array($customersrecords)) {

		$address_book_query = tep_db_query("SELECT address_book_id FROM address_book WHERE customers_id ='".$customerrows['customers_id']."'");
		$real = tep_db_fetch_array($address_book_query);

		$updatedefaultaddress = tep_db_query("UPDATE customers 
											  SET customers_default_address_id = '".$real[address_book_id]."' 
											  WHERE customers_id = '".$customerrows[customers_id]."'
											");
	}
*/
?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>

