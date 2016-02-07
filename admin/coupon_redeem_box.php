<?php
  require('includes/application_top.php');

	define('ERROR_NO_INVALID_REDEEM_COUPON', 'Invalid Coupon Code');
	define('ERROR_INVALID_STARTDATE_COUPON', 'This coupon is not available yet');

  $success_message = '';
  $error_message = '';
  if ($_GET['code']) {

  // # get some info from the coupon table
  	$coupon_query=tep_db_query("select c.coupon_id, c.coupon_amount, c.coupon_type, c.coupon_minimum_order, c.uses_per_coupon, c.uses_per_user, c.restrict_to_products, c.restrict_to_categories, c.coupon_start_date<=now() AS coupon_started, c.coupon_expire_date<now() AS coupon_expired, cd.coupon_name from " . TABLE_COUPONS . " c LEFT JOIN ".TABLE_COUPONS_DESCRIPTION." cd ON c.coupon_id=cd.coupon_id AND cd.language_id='$languages_id' where c.coupon_code='".tep_db_prepare_input($_GET['code'])."' and c.coupon_active='Y'");
  	$coupon_result=tep_db_fetch_array($coupon_query);
    //Coupon
  	if ($coupon_result['coupon_type'] != 'G') {
  		if (!$coupon_result) {
  			$error_message .= ERROR_NO_INVALID_REDEEM_COUPON . '<br>';
  		}

  		if (!$coupon_result['coupon_started']) {
  			$error_message .= ERROR_INVALID_STARTDATE_COUPON . '<br>';
  		}

                if ($coupon_result['coupon_expired']) {
  			$error_message .= ERROR_INVALID_FINISDATE_COUPON . '<br>';
  		}

  		$coupon_count = tep_db_query("select coupon_id from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $coupon_result['coupon_id']."'");
  		$coupon_count_customer = tep_db_query("select coupon_id from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . tep_db_prepare_input($coupon_result['coupon_id'])."' and customer_id = '" . (int)$_GET['cID'] . "'");

  		if (tep_db_num_rows($coupon_count)>=$coupon_result['uses_per_coupon'] && $coupon_result['uses_per_coupon'] > 0) {
  			$error_message .= ERROR_INVALID_USES_COUPON . $coupon_result['uses_per_coupon'] . TIMES . '<br>';
  		}

  		if (tep_db_num_rows($coupon_count_customer)>=$coupon_result['uses_per_user'] && $coupon_result['uses_per_user'] > 0) {
  			$error_message .= ERROR_INVALID_USES_USER_COUPON . $coupon_result['uses_per_user'] . TIMES . '<br>';
  		}
       }
      if (!$error_message) {
?>
  <eval code="setCoupon('<?php echo $coupon_result['coupon_id']?>','<?php echo $coupon_result['coupon_name']?>','<?php echo $coupon_result['coupon_type']?>','<?php echo $coupon_result['coupon_amount']?>')">
  Coupon: <?php echo $coupon_result['coupon_name']?>
<?php
      } else {
?>
  Error: <?php echo $error_message?>
<?php
      }
  }
