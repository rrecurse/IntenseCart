<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	if(!tep_session_is_registered('customer_id')) {
    	$navigation->set_snapshot();
	    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
	} 

	$products_id = (int)$_GET['products_id'];

	$product_info_query = tep_db_query("SELECT p.products_id, 
											   p.products_model, 
											   p.products_image, 
											   p.products_price, 
											   p.products_tax_class_id, 
											   pd.products_name 
										FROM " . TABLE_PRODUCTS . " p
										LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id
										WHERE  p.products_id = '". $products_id ."' 
										AND p.products_status = '1' 
										AND pd.language_id = '" . (int)$languages_id . "'
										");

	if(tep_db_num_rows($product_info_query) > 0) {
		$product_info = tep_db_fetch_array($product_info_query);
	} else {
	    tep_redirect(tep_href_link(FILENAME_PRODUCT_REVIEWS, tep_get_all_get_params(array('action'))));
	}

	$customer_query = tep_db_query("SELECT customers_firstname, customers_lastname, customers_email_address
									FROM " . TABLE_CUSTOMERS . " 
									WHERE customers_id = '". $customer_id ."'
								   ");
	$customer = tep_db_fetch_array($customer_query);

	if(isset($_GET['action']) && ($_GET['action'] == 'process')) {

	    $rating = (isset($_POST['rating'])) ? tep_db_prepare_input($_POST['rating']) : tep_db_prepare_input($_GET['rating']);
    	$review = (isset($_POST['review'])) ? tep_db_prepare_input($_POST['review']) : tep_db_prepare_input(strip_tags($_GET['review'], '<br>'));

		$error = false;

		$review_check_query = tep_db_query("SELECT rd.reviews_id, r.customers_id, rd.reviews_text
											FROM reviews r 
											LEFT JOIN reviews_description rd ON r.reviews_id = rd.reviews_id
											WHERE products_id = ". $products_id ." AND customers_id = '". $customer_id."'
										   ");

		if(tep_db_num_rows($review_check_query) > 0) { 

			$review_check = tep_db_fetch_array($review_check_query);

		} else { 

			$review_check = 0;
		}

		if(isset($_GET['delete']) && $_GET['delete'] == '1') { 
			tep_db_query("DELETE FROM " . TABLE_REVIEWS_DESCRIPTION . " WHERE reviews_id = '".$_GET['reviews_id']."'");
			tep_db_query("DELETE FROM " . TABLE_REVIEWS . " WHERE reviews_id = '".$_GET['reviews_id']."'");
			exit();
		}


		$customers_name = (isset($_GET['customers_name'])) ? tep_db_prepare_input($_GET['customers_name']) : tep_db_prepare_input($_POST['customers_name']);

		$cust = (empty($customers_name)) ? $customer['customers_firstname'] .' '. $customer['customers_lastname'] :  $customers_name; 

		if($review_check['customers_id'] == $customer_id) {

			tep_db_query("UPDATE " . TABLE_REVIEWS_DESCRIPTION . " SET reviews_text = '". $review ."' WHERE reviews_id = '".$review_check['reviews_id']."'");

			tep_db_query("UPDATE " . TABLE_REVIEWS . " SET reviews_rating = '". $rating ."', customers_name = '". $cust ."' WHERE reviews_id = '".$review_check['reviews_id']."'");

			echo '<div id="review_response" style="color:green"><b>Review updated.</b></div>';

			$review_mail_subject = 'Review for '. $product_info['products_name'] . ' has been updated.';

			$review_mail_body = 'Customer:' . "\n" . $customer['customers_firstname'] .' '. $customer['customers_lastname'] . "\n\n" . 'Rating:'. "\n" . $rating . ' of 5' . "\n\n" . 'Review:' . "\n" . $review . "\n\n\n" . 'You can review and delete this product review by visiting:'."\n".'<a href="https://'.$_SERVER['HTTP_HOST'].'/admin/reviews.php?page=1&rID='.$review_check['reviews_id'].'">https://'.$_SERVER['HTTP_HOST'].'/admin/reviews.php?page=1&rID='.$review_check['reviews_id'].'</a>';


			tep_mail(STORE_OWNER, 'support@zwaveproducts.com', $review_mail_subject, $review_mail_body, 	$cust, $customer['customers_email_address']);

			$error = true;
			//$messageStack->add('review', 'You have already reviewed this product.');

	if(isset($_GET['delete']) && ($_GET['delete'] == '1')) { 

error_log(print_r($_GET['delete'],TRUE));

			tep_db_query("DELETE FROM " . TABLE_REVIEWS_DESCRIPTION . " WHERE reviews_id = '".$review_check['reviews_id']."'");

			tep_db_query("DELETE FROM " . TABLE_REVIEWS . " WHERE reviews_id = '".$review_check['reviews_id']."'");
		}

			exit();
		}
	
    	if (strlen($review) < REVIEW_TEXT_MIN_LENGTH) {
	      $error = true;
    	  echo $messageStack->add('review', JS_REVIEW_TEXT);
    	}

	    if (($rating < 1) || ($rating > 5)) {
    	  $error = true;
	      echo $messageStack->add('review', JS_REVIEW_RATING);
    	}	


    	if (!$error) {

    		tep_db_query("INSERT INTO " . TABLE_REVIEWS . " 
						  SET products_id = '". (int)$_GET['products_id'] ."', 
						  customers_id = '". (int)$customer_id ."',
						  customers_name = '". $cust ."',
						  reviews_rating = '". $rating ."', 
						  date_added = NOW()
						");
			$insert_id = tep_db_insert_id();


			tep_db_query("INSERT INTO " . TABLE_REVIEWS_DESCRIPTION . " 
						  SET reviews_id = '". $insert_id ."', 
						  languages_id = '". $languages_id ."',
						  reviews_text = '". $review ."'
						");

			$review_mail_subject = 'Product review left for '. $product_info['products_name'];

			$review_mail_body = 'Customer:' . "\n" . $customer['customers_firstname'] .' '. $customer['customers_lastname'] . "\n\n" . 'Rating:'. "\n" .  $rating . ' of 5' . "\n\n" . 'Review:' . "\n" . $review . "\n\n\n" . 'You can review and delete this product review by visiting:'."\n".'<a href="https://'.$_SERVER['HTTP_HOST'].'/admin/reviews.php?page=1&rID='.$insert_id.'">https://'.$_SERVER['HTTP_HOST'].'/admin/reviews.php?page=1&rID='.$insert_id.'</a>';
			
			tep_mail(STORE_OWNER, 'support@zwaveproducts.com', $review_mail_subject, $review_mail_body, 	$cust, $customer['customers_email_address']);

			//tep_redirect(tep_href_link(FILENAME_PRODUCT_REVIEWS, tep_get_all_get_params(array('action'))));

			echo '<div id="review_response" style="color:green"><b>Review Sent Successfully.</b></div>';
			exit();
		} // # END if(!error)

	} // # END $_GET['action'] == 'process')



// # Separate Pricing Per Customer

  if(!tep_session_is_registered('sppc_customer_group_id')) {
  $customer_group_id = '0';
  } else {
   $customer_group_id = $sppc_customer_group_id;
  }

     if ($customer_group_id > 0) {
	$customer_group_price_query = tep_db_query("SELECT customers_group_price FROM " . TABLE_PRODUCTS_GROUPS . " where products_id = '" . (int)$_GET['products_id'] . "' AND customers_group_id =  '" . $customer_group_id . "'");
	  if ($customer_group_price = tep_db_fetch_array($customer_group_price_query)) {
	    $product_info['products_price'] = $customer_group_price['customers_group_price'];
	  }
     }

// # END  Separate Pricing Per Customer

  if ($new_price = tep_get_products_special_price($product_info['products_id'])) {
    $products_price = '<s>' . $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id'])) . '</s> <span class="productSpecialPrice">' . $currencies->display_price($new_price, tep_get_tax_rate($product_info['products_tax_class_id'])) . '</span>';
  } else {
    $products_price = $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
  }

  if (tep_not_null($product_info['products_model'])) {
    $products_name = $product_info['products_name'] . '<br><span class="smallText">[' . $product_info['products_model'] . ']</span>';
  } else {
    $products_name = $product_info['products_name'];
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PRODUCT_REVIEWS_WRITE);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_PRODUCT_REVIEWS, tep_get_all_get_params()));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">

<script type="text/javascript">
<!--
function checkForm() {
  var error = 0;
  var error_message = "<?php echo JS_ERROR; ?>";

  var review = document.product_reviews_write.review.value;

  if (review.length < <?php echo REVIEW_TEXT_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_REVIEW_TEXT; ?>";
    error = 1;
  }

  if ((document.product_reviews_write.rating[0].checked) || (document.product_reviews_write.rating[1].checked) || (document.product_reviews_write.rating[2].checked) || (document.product_reviews_write.rating[3].checked) || (document.product_reviews_write.rating[4].checked)) {
  } else {
    error_message = error_message + "<?php echo JS_REVIEW_RATING; ?>";
    error = 1;
  }

  if (error == 1) {
    alert(error_message);
    return false;
  } else {
    return true;
  }
}

function popupWindow(url) {
  window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,width=100,height=100,screenX=150,screenY=150,top=150,left=150')
}
//-->
</script>
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top"><?php echo tep_draw_form('product_reviews_write', tep_href_link(FILENAME_PRODUCT_REVIEWS_WRITE, 'action=process&products_id=' . $HTTP_GET_VARS['products_id']), 'post', 'onSubmit="return checkForm();"'); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" valign="top"><?php echo $products_name; ?></td>
            <td class="pageHeading" align="right" valign="top"><?php echo $products_price; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  if ($messageStack->size('review') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('review'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  }
?>
      <tr>
        <td><table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><?php echo '<b>' . SUB_TITLE_FROM . '</b> ' . tep_output_string_protected($customer['customers_firstname'] . ' ' . $customer['customers_lastname']); ?></td>
              </tr>
              <tr>
                <td class="main"><b><?php echo SUB_TITLE_REVIEW; ?></b></td>
              </tr>
              <tr>
                <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
                  <tr class="infoBoxContents">
                    <td><table border="0" width="100%" cellspacing="2" cellpadding="2">
                      <tr>
                        <td class="main"><?php echo tep_draw_textarea_field('review', 'soft', 60, 15); ?></td>
                      </tr>
                      <tr>
                        <td class="smallText" align="right"><?php echo TEXT_NO_HTML; ?></td>
                      </tr>
                      <tr>
                        <td class="main"><?php echo '<b>' . SUB_TITLE_RATING . '</b> ' . TEXT_BAD . ' ' . tep_draw_radio_field('rating', '1') . ' ' . tep_draw_radio_field('rating', '2') . ' ' . tep_draw_radio_field('rating', '3') . ' ' . tep_draw_radio_field('rating', '4') . ' ' . tep_draw_radio_field('rating', '5') . ' ' . TEXT_GOOD; ?></td>
                      </tr>
                    </table></td>
                  </tr>
                </table></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
              </tr>
              <tr>
                <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
                  <tr class="infoBoxContents">
                    <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                      <tr>
                        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                        <td class="main"><?php echo '<a href="' . tep_href_link(FILENAME_PRODUCT_REVIEWS, tep_get_all_get_params(array('reviews_id', 'action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_BACK) . '</a>'; ?></td>
                        <td class="main" align="right"><?php echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); ?></td>
                        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                      </tr>
                    </table></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
            <td width="<?php echo SMALL_IMAGE_WIDTH + 10; ?>" align="right" valign="top"><table border="0" cellspacing="0" cellpadding="2">
              <tr>
                <td align="center" class="smallText">
<?php
  if (tep_not_null($product_info['products_image'])) {
?>
<script type="text/javascript">
<!--
document.write('<?php echo '<a href="javascript:popupWindow(\\\'' . tep_href_link(FILENAME_POPUP_IMAGE, 'pID=' . $product_info['products_id']) . '\\\')">' . tep_image(DIR_WS_IMAGES . $product_info['products_image'], addslashes($product_info['products_name']), SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'hspace="5" vspace="5"') . '<br>' . TEXT_CLICK_TO_ENLARGE . '</a>'; ?>');
//-->
</script>
<noscript>
<?php echo '<a href="' . tep_href_link(DIR_WS_IMAGES . $product_info['products_image']) . '" target="_blank">' . tep_image(DIR_WS_IMAGES . $product_info['products_image'], $product_info['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'hspace="5" vspace="5"') . '<br>' . TEXT_CLICK_TO_ENLARGE . '</a>'; ?>
</noscript>
<?php
  }

  echo '<p><a href="' . tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action')) . 'action=buy_now') . '">' . tep_image_button('button_in_cart.gif', IMAGE_BUTTON_IN_CART) . '</a></p>';
?>
                </td>
              </tr>
            </table>
          </td>
        </table></td>
      </tr>
    </table></form></td>

    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>


<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>