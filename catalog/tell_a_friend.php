<?php

require('includes/application_top.php');

if (!tep_session_is_registered('customer_id') && (ALLOW_GUEST_TO_TELL_A_FRIEND == 'false')) {
    $navigation -> set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
}

$valid_product = false;
if (isset($_GET['pid'])) $HTTP_POST_VARS['products_id']=$_GET['pid'];
if (isset($HTTP_POST_VARS['products_id'])) {
    if ($HTTP_POST_VARS['products_id'] == false ) {
        $valid_product = true;
    } else {
        $product_info_query = tep_db_query("select p.products_image,pd.products_name,pd.products_info from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_status = '1' and p.products_id = '" . preg_replace('/\{.*/','',$HTTP_POST_VARS['products_id']) . "' and p.master_products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");
        if (tep_db_num_rows($product_info_query)) {
            $valid_product = true;
            $product_info = tep_db_fetch_array($product_info_query);
        }
    }
}

if ($valid_product == false && isset($HTTP_POST_VARS['products_id'])) {
    tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $HTTP_POST_VARS['products_id']));
}

require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_TELL_A_FRIEND);

if (isset($_GET['action']) && ($_GET['action'] == 'process')) {
    $error = false;

// MESSAGE STACK FIX for new variable $tellAboutWhat
if (!$valid_product) {
    $tellAboutWhat = STORE_NAME;
} else {
    $tellAboutWhat = $product_info['products_name'];
}
// END MESSAGE STACK FIX for $tellAboutWhat

    $to_emails = array_map('trim',explode(',',tep_db_prepare_input($_POST['to_email_address'])));
    $to_names = array_map('trim',explode(',',tep_db_prepare_input($_POST['to_name'])));
    $from_email_address = tep_db_prepare_input($_POST['from_email_address']);
    $from_name = tep_db_prepare_input($_POST['from_name']);

	// # scrub the message contents and run some spam checks:

    $message = strip_tags(tep_db_prepare_input($_POST['message']));

	$disallowedText = array('http://','https://','www.','.com','.net','.org','.co.uk', '.nl','.cn','.html','.php','.htm','.swf','.exe','zoloft','nolvadex','Medicare','prescription','fluoxetine');
	$message = str_replace($disallowedText,'',$message);

  foreach ($to_emails AS $idx=>$to_email_address) {
    $to_name = isset($to_names[$idx]) ? $to_names[$idx] : $to_names[sizeof($to_names)-1];


    if (empty($from_name)) {
        $error = true;

        $messageStack -> add('friend', ERROR_FROM_NAME);
    }

    if (!tep_validate_email($from_email_address)) {
        $error = true;

        $messageStack -> add('friend', ERROR_FROM_ADDRESS);
    }

    if (empty($to_name)) {
        $error = true;

        $messageStack -> add('friend', ERROR_TO_NAME);
    }

    if (!tep_validate_email($to_email_address)) {
        $error = true;

        $messageStack -> add('friend', ERROR_TO_ADDRESS);
    }

	$flag = false;
	foreach ($disallowedText as $spam) {
   		if (strpos($message, $spam) !== false)  $isSpam = true;
	}

	if ($isSpam === true) {
		$error = true;
		$messageStack -> add('friend', 'Your message contains text that is not allowed!');
	}

// # VISUAL VERIFY CODE start
require(DIR_WS_FUNCTIONS . 'visual_verify_code.php');
    $code_query = tep_db_query("select code from visual_verify_code where ixsid = '" . tep_session_id($_GET[tep_session_name()]) . "'");
    $code_array = tep_db_fetch_array($code_query);
    $code = $code_array['code'];

    tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE ixsid='" . $vvcode_oscsid . "'"); //remove the visual verify code associated with this session to clean database and ensure new results

    $user_entered_code = $HTTP_POST_VARS['visual_verify_code'];
    if (!(strcasecmp($user_entered_code, $code) == 0)) {    //make the check case insensitive
        $error = true;
        $messageStack->add('friend', VISUAL_VERIFY_CODE_ENTRY_ERROR);
    }
// # VISUAL VERIFY CODE stop

    if ($error == false) {
      if (IXdb::read("SELECT COUNT(0) AS ct FROM email_now_templates WHERE email_template_key='tell_a_friend'",NULL,'ct')>0) {
        $tpl=Array(
	  'sender'=>Array('name'=>$to_name,'email'=>$to_email_address),
	  'recipient'=>Array('name'=>$from_name,'email'=>$from_email_address),
	  'message'=>$message,
	);
	$tpl['product']=IXdb::read("SELECT * FROM products p LEFT JOIN products_description pd ON (p.master_products_id=pd.products_id AND pd.language_id='$languages_id') WHERE p.products_id='{$_POST['products_id']}'");
	$tpl['product']['products_href']=tep_href_link('/index.php?products_id='.$_POST['products_id']);
	$tpl['product']['products_image']='<img src="'.HTTP_SERVER.tep_image_src(DIR_WS_IMAGES.$tpl['product']['products_image'],160,200).'" border="0" alt="">';
	require_once(DIR_WS_FUNCTIONS . 'email_now.php');
	email_now('tell_a_friend',$tpl,NULL);
      } else {
        // ADDED THIS ALT SUBJECT FOR NON PRODUCT RECOMMENDATION
        if ($HTTP_POST_VARS['products_id'] == false) {
            $myEmailSubject = 'Your friend %s would like you to visit %s';
            $email_subject = sprintf($myEmailSubject, $from_name, STORE_NAME);
            $myEmailIntro = 'Hello %s,' . "\n\n" . 'Your friend %s found a great site called %s and thought you\'d like to check it out!';
            $email_body = sprintf($myEmailIntro, $to_name, $from_name, STORE_NAME) . "\n\n";
            if (tep_not_null($message)) {
                $email_body .= $message . "\n\n";
            }
            $email_body .= 'Check out the site below, or click the second link to go directly to the catalog!' . "\n\n" . HTTP_SERVER . "\n\n" .
            sprintf(TEXT_EMAIL_SIGNATURE, STORE_NAME . "\n" . HTTP_SERVER . DIR_WS_CATALOG . "\n");
        } else {
            $email_subject = sprintf(TEXT_EMAIL_SUBJECT, $from_name, STORE_NAME);
            $email_body = sprintf(TEXT_EMAIL_INTRO, $to_name, $from_name, $product_info['products_name'], STORE_NAME) . "\n\n";
            if (tep_not_null($message)) {
                $email_body .= $message . "\n\n";
            }
            $email_body .= sprintf(TEXT_EMAIL_LINK, tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $HTTP_POST_VARS['products_id'])) . "\n\n" .
            sprintf(TEXT_EMAIL_SIGNATURE, STORE_NAME . "\n" . HTTP_SERVER . DIR_WS_CATALOG . "\n");
        }
	tep_mail($to_name, $to_email_address, $email_subject, $email_body, $from_name, $from_email_address);
      }

      if (tep_session_is_registered('customer_id')) {
        $from_id=$customer_id;
      } else {
        $from_id=0;
      }

      tep_db_query("INSERT INTO tell_a_friend_sent ( customers_id, products_id, email_sent_to, email_sent_to_name, email_from, email_from_name, date_sent) VALUES ( " . $from_id . ", '" . $HTTP_POST_VARS['products_id'] . "', '" . $to_email_address . "', '" . $to_name . "', '" . $from_email_address . "', '" . $from_name . "', Now())");

    }
  }
 
 if (!$error) {

    // UNCOMMENT NEXT LINE ONLY IF YOU WANT THE ADDED FEATURE OF HAVING THE MAIL SENT TO YOU! CHANGE THE yourname@yourstore.com
    // tep_mail($to_name, 'yourname@yourstore.com', 'Tell A Friend', $email_body, $from_name, $from_email_address);
    $messageStack -> add_session('header', sprintf(TEXT_EMAIL_SUCCESSFUL_SENT, $tellAboutWhat, tep_output_string_protected($to_name)), 'success');
    // Replaced above $HTTP_POST_VARS['products_name'] with $tellAboutWhat
    // If prduct id is in the url then redirect back to product, otherwise redirect to home page
    if ($HTTP_POST_VARS['products_id'] == false) {
        tep_redirect(tep_href_link(FILENAME_DEFAULT));
    } else {
        tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $HTTP_POST_VARS['products_id']));
    } //END CHANGE IN LINK
    }
} elseif (tep_session_is_registered('customer_id')) {
    $account_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
    $account = tep_db_fetch_array($account_query);

    $from_name = $account['customers_firstname'] . ' ' . $account['customers_lastname'];
    $from_email_address = $account['customers_email_address'];
}

$breadcrumb -> add(NAVBAR_TITLE, tep_href_link(FILENAME_TELL_A_FRIEND, 'products_id=' . $HTTP_POST_VARS['products_id']));

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td valign="top"><table border="0" cellspacing="0" cellpadding="0">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top"><?php echo tep_draw_form('email_friend', tep_href_link(FILENAME_TELL_A_FRIEND, 'action=process'), 'post') . (is_numeric($HTTP_POST_VARS['products_id']) ? tep_draw_hidden_field('products_id', $HTTP_POST_VARS['products_id']) : ''); ?>
	<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        
            <td class="pageHeading">
<?php // NOTE THIS VAR USED SEVERAL TIMES BELOW
if ($HTTP_POST_VARS['products_id'] == false) {
    $tellAboutWhat = STORE_NAME;
} else {
    $tellAboutWhat = $product_info['products_name'];
}
echo sprintf(HEADING_TITLE, $tellAboutWhat);

?>
</td>
      </tr>
<?php if ($messageStack -> size('friend') > 0) {

    ?>
      <tr>
        <td><?php echo $messageStack -> output('friend'); ?></td>
      </tr>

<?php
}
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo FORM_TITLE_CUSTOMER_DETAILS; ?></b></td>
                <td align="right"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
              </tr>
            </table></td>
<? if (isset($product_info['products_image'])) { ?>
	    <td rowspan="8" align="right"><table cellspacing="0" cellpadding="0" border="0">
		<tr><td><?=tep_image(DIR_WS_IMAGES.$product_info['products_image'],$product_info['products_name'],MEDIUM_IMAGE_WIDTH,MEDIUM_IMAGE_HEIGHT)?></td></tr>
		<tr><td><?=$product_info['products_info']?></td></tr>
	    </table></td>
<? } ?>
          </tr>
          <tr>
            <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
              <tr class="infoBoxContents">
                <td><table border="0" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main"><?php echo FORM_FIELD_CUSTOMER_NAME; ?></td>
                    <td class="main"><?php echo tep_draw_input_field('from_name') . '&nbsp;<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>'; ?></td>
                  </tr>
                  <tr>
                    <td class="main"><?php echo FORM_FIELD_CUSTOMER_EMAIL; ?></td>
                    <td class="main"><?php echo tep_draw_input_field('from_email_address') . '&nbsp;<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>'; ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><b><?php echo FORM_TITLE_FRIEND_DETAILS; ?></b></td>
          </tr>
          <tr>
            <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
              <tr class="infoBoxContents">
                <td><table border="0" cellspacing="0" cellpadding="2">
<tr><td colspan="2"><?php echo TEXT_FRIEND_INSTRUCT; ?><br></td></tr>
                  <tr>
                    <td class="main"><?php echo FORM_FIELD_FRIEND_NAME; ?></td>
                    <td class="main"><?php echo tep_draw_input_field('to_name') . '&nbsp;<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>'; ?></td>
                  </tr>
                  <tr>
                    <td class="main"><?php echo FORM_FIELD_FRIEND_EMAIL; ?></td>
                    <td class="main"><?php echo tep_draw_input_field('to_email_address') . '&nbsp;<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>'; ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><b><?php echo FORM_TITLE_FRIEND_MESSAGE; ?></b></td>
          </tr>
          <tr>
            <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
              <tr class="infoBoxContents">
                <td><?php echo tep_draw_textarea_field('message', 'soft', 40, 8); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>

  <tr>
        <td class="main"><b><?php echo VISUAL_VERIFY_CODE_CATEGORY; ?></b></td>
      </tr>
      <tr>
        <td class="main"><?php echo VISUAL_VERIFY_CODE_TEXT_INTRO; ?><br></td>
      </tr>
  
	  <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
          <td class="main"><?php echo VISUAL_VERIFY_CODE_TEXT_INSTRUCTIONS; ?></td>
                <td>
<?php 
	 // # Captcha code garbage collection
	$included_code_query = tep_db_query("SELECT ixsid, code, dt FROM " . TABLE_VISUAL_VERIFY_CODE);
	$endtime = time();

	while ($included_code = tep_db_fetch_array($included_code_query)) {
  		$starttime=mktime(
			substr($included_code['dt'], 6, 2),	// hour
			substr($included_code['dt'], 8, 2),	// minute
			substr($included_code['dt'], 10, 2),// second
			substr($included_code['dt'], 2, 2),	// month
			substr($included_code['dt'], 4, 2),	// day
			substr($included_code['dt'], 0, 2)	// year
			);

	$timediff = intval(($endtime-$starttime)/3600);

	// # 5+ hours should be enough to fill in a form
	if ($timediff > 5) tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE code='" .$included_code['code'] . "' AND dt='" .$included_code['dt'] . "'");
	} // # END while
	// # END Captcha code garbage collection

	$visual_verify_code = "";
	for ($i = 1; $i <= rand(3,6); $i++){
		$visual_verify_code = $visual_verify_code . substr(VISUAL_VERIFY_CODE_CHARACTER_POOL, rand(0, strlen(VISUAL_VERIFY_CODE_CHARACTER_POOL)-1), 1);
	}

	$vvcode_oscsid = tep_session_id($_GET[tep_session_name()]);
	tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE ixsid='" . $vvcode_oscsid . "'");
	$sql_data_array = array('ixsid' => $vvcode_oscsid, 'code' => $visual_verify_code);
	tep_db_perform(TABLE_VISUAL_VERIFY_CODE, $sql_data_array);

	$vvc = $visual_verify_code;
	//$GLOBALS['thecaptcha'] = $visual_verify_code;

	echo '<input id="visual_verify_code" type="text" value="" name="visual_verify_code">';

//echo tep_draw_input_field('visual_verify_code','','id="visual_verify_code"','text'); 
	echo ' &nbsp; <span class="inputRequirement">'.VISUAL_VERIFY_CODE_ENTRY_TEXT.'</span>';
?>
</td>

                <td class="main">
                  
                  <?php echo'<img src="CaptchaSecurityImages.php?vvc='.$vvc.'" alt="" />';?>
                </td>
                <td class="main"><?php echo VISUAL_VERIFY_CODE_BOX_IDENTIFIER; ?></td>
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
                
                <td>
<?php echo tep_image_submit('button_send.gif', 'Send'); ?></td>
                <td align="right"><?php if ($HTTP_POST_VARS['products_id'] == false) {
    $pageTo = FILENAME_DEFAULT;
} else {
    $pageTo = FILENAME_PRODUCT_INFO . '?products_id=' . $HTTP_POST_VARS['products_id'];
}
echo '<a href="' . tep_href_link($pageTo) . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_BACK) . '</a>'; ?></td>
                
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></form></td>

    <td width="" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
