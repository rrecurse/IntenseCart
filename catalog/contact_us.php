<?php


  require('includes/application_top.php');


  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CONTACT_US);

	if((empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') || $_SERVER['SERVER_PORT'] != 443) {
	    header("HTTP/1.1 301 Moved Permanently");
	    header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	    exit();
	}

  $error = false;

  if (isset($_GET['action']) && ($_GET['action'] == 'send')) {
    $name = tep_db_prepare_input($_POST['name']);

	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$email_address = '';
        $error = true;
	} else {
		$email_address = preg_replace("/[^,;a-zA-Z0-9_.@-]/i",'', $_POST['email']);
	}

    $phone = tep_db_prepare_input($_POST['phone']);
    $enquiry = tep_db_prepare_input($_POST['enquiry']);
    $emailsubject = tep_db_prepare_input($_POST['reason']) . ' ' . EMAIL_SUBJECT;
    $source_id = $_POST['source'];

    if ($source_id > 0 && $source_id != '9999') {
      $sources = tep_db_query("select sources_name from " . TABLE_SOURCES . " where sources_id = '" . (int)$source_id . "'");

      if (tep_db_num_rows($sources) > 0) {
        $sources_values = tep_db_fetch_array($sources);
        $source_name = $sources_values['sources_name'];
      } else {
		$source_name = 'Unknown';
      }

    } elseif ($source_id == '9999') {
      $source_name = 'Other';
    } else {
      $source_name = 'Unknown';
    }
    
    if (isset($_POST['source_other'])) $source_other = tep_db_prepare_input($_POST['source_other']);

    if ((REFERRAL_REQUIRED == 'true') && (is_numeric($source) == false)) {
        $error = true;

        $messageStack->add('contact', ENTRY_SOURCE_ERROR);
    }

    if ((REFERRAL_REQUIRED == 'true') && (DISPLAY_REFERRAL_OTHER == 'true') && ($source == '9999') && (!tep_not_null($source_other)) ) {
        $error = true;

        $messageStack->add('contact', ENTRY_SOURCE_OTHER_ERROR);
    }

// # VISUAL VERIFY CODE start 
require('/usr/share/IXcore/catalog/includes/functions/visual_verify_code.php');
//require(DIR_WS_FUNCTIONS . 'visual_verify_code.php');
    $code_query = tep_db_query("select code from visual_verify_code where ixsid = '" . tep_session_id($_GET[tep_session_name()]) . "'");
    $code_array = tep_db_fetch_array($code_query);
    $code = $code_array['code'];

    tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE ixsid='" . $vvcode_oscsid . "'"); //remove the visual verify code associated with this session to clean database and ensure new results

    $user_entered_code = $_POST['visual_verify_code'];

// # VISUAL VERIFY CODE END 

    if (tep_not_null($name) && tep_not_null($enquiry) && tep_not_null($phone) && tep_not_null($email_address) && tep_not_null($user_entered_code)) { 


     if (strcasecmp($user_entered_code, $code) == 0 && tep_validate_email($email_address)) {
        $enquiry = 'Name: ' . $name . "\n" . 'Phone: ' . $phone . "\n" . 'Referred By: ' . $source_name . (tep_not_null($_POST['source_other']) ? ' -- ' . $_POST['source_other'] : '') . "\n\n" . $enquiry;

// # do the emailing!
      	if (CONTACT_US_LIST !='') {
      		$send_to_array=explode("," ,CONTACT_US_LIST);
      		preg_match('/\<[^>]+\>/', $send_to_array[$send_to], $send_email_array);
      		$send_to_email= preg_replace ("/>/", "", $send_email_array[0]);
      		$send_to_email= preg_replace ("/</", "", $send_to_email);
      		tep_mail(preg_replace('/\<[^*]*/', '', $send_to_array[$send_to]), $send_to_email, $emailsubject, $enquiry, $name, $email_address);
      	} else {
      	  tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $emailsubject, $enquiry, $name, $email_address);
      	}
// end do the emailing!

        tep_redirect(tep_href_link(FILENAME_CONTACT_US, 'action=success'));

      } elseif (!tep_validate_email($email_address)) {
        $error = true;
        $messageStack->add('contact', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
	  } elseif (!strcasecmp($user_entered_code, $code) == 0){
 		$error = true;
      	$messageStack->add('contact', 'Please check your Captcha code for accuracy - '.$user_entered_code.' does not match '.$code);
      }
    } else {
      $messageStack->add('contact', 'Please fill out all fields before submitting');
    }
  }
  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CONTACT_US));
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
    <td width="" valign="top"><table border="0" width="" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top">

<?php echo tep_draw_form('contact_us', tep_href_link(FILENAME_CONTACT_US, 'action=send','SSL'),'post', ''); ?>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php
  if ($messageStack->size('contact') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('contact'); ?></td>
      </tr>
      <tr>
        <td height="10">&nbsp;</td>
      </tr>
<?php
  }
  if (isset($_GET['action']) && ($_GET['action'] == 'success')) {
?>
      <tr>
        <td class="main" align="center"><?php echo TEXT_SUCCESS; ?></td>
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
                <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>

<?php
  } else {
  if (tep_session_is_registered('customer_id')) {
    $account_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
    $account = tep_db_fetch_array($account_query);

    $name = $account['customers_firstname'] . ' ' . $account['customers_lastname'];
    $email = $account['customers_email_address'];
  }
?>  

<tr>
	<td>

		<table border="0" width="100%" cellspacing="0" cellpadding="0">
                
			<tr> 
				<td height="40" valign="top" class="main">
					<?php echo ENTRY_NAME; ?><br>
					<?php echo tep_draw_input_field('name'); ?>
				</td> 
			</tr>
			<tr> 
				<td height="4"></td>
			</tr>
			<tr> 
				<td height="40" valign="top" class="main">
					<?php echo ENTRY_EMAIL; ?><br>
					<?php echo tep_draw_input_field('email'); ?>
				</td> 
			</tr>
			<tr> 
				<td height="4"></td>
			</tr>
			<tr> 
				<td height="40" valign="top" class="main">
					<?php echo ENTRY_PHONE; ?><br>
					<?php echo tep_draw_input_field('phone'); ?>
				</td> 
			</tr>
			<tr> 
				<td height="4"></td>
			</tr>
			<tr> 
				<td height="40" valign="top" class="main">
<?php 
		  if (CONTACT_US_LIST !='') {
				echo SEND_TO_TEXT . '<br>';
				if(SEND_TO_TYPE=='radio') {
          foreach(explode("," ,CONTACT_US_LIST) as $k => $v) {
  				  if($k==0) {
              $checked=true;
  					} else {
              $checked=false;
  					}
  					echo tep_draw_radio_field('send_to', "$k", $checked). " " .preg_replace('/\<[^*]*/', '', $v);
  				}

			  } else {
				  foreach(explode("," ,CONTACT_US_LIST) as $k => $v) {
						$send_to_array[] = array('id' => $k, 'text' => preg_replace('/\<[^*]*/', '', $v));
					}
        		echo tep_draw_pull_down_menu('send_to',  $send_to_array, $_GET['dept']);
			  }
			}
?>
				</td> 
			</tr>
			<tr> 
				<td height="4"></td>
			</tr>
<?php 

	if (CONTACT_US_REASONS != '' || isset($_POST['pID'])) {

		echo '<tr> 
				<td height="40" valign="top" class="main">'.ENTRY_REASON.' <br> ';

	    if (isset($_POST['pID'])) {

			echo tep_draw_hidden_field('reason', $reason);
			echo tep_draw_hidden_field('pID', $_POST['pID']);
			echo 'Product: '.tep_get_products_name($_POST['pID']);

		} else {
        	
			$reasons = explode(",", CONTACT_US_REASONS);
			$reason_array = array();
				
			foreach($reasons as $val) $reason_array[] = array('id' => $val, 'text' => $val);

        	echo tep_draw_pull_down_menu('reason',  $reason_array, $_GET['reason']);
		}
		
		echo '</td></tr>';
	}
?>


<?php
  if (!isset($customer_id)) {
  
  if ((tep_not_null(tep_get_sources()) || DISPLAY_REFERRAL_OTHER == 'true') && (!tep_session_is_registered('referral_id') || (tep_session_is_registered('referral_id'))) ) {

    if ((tep_session_is_registered('referral_id') && tep_not_null($referral_id)) || tep_not_null($_POST['source_other'])) {
      $source_id = '9999';
    } else {
      $source_id = $_POST['source'];
    }

?>
      
      <tr>
        <td class="main"><b><?php echo CATEGORY_SOURCE; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0" class="infoBox">
          <tr>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main"><?php echo ENTRY_SOURCE; ?></td></tr>
				<tr>
                <td class="main"><?php echo tep_get_source_list('source', true, $source_id) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_TEXT . '</span>': ''); ?></td> 
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_SOURCE_OTHER; ?></td></tr>
<tr>
                <td class="main"><?php echo tep_draw_input_field('source_other', (tep_not_null($referral_id) ? $referral_id : '')) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_OTHER_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_OTHER_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  }
  
  } else {
    echo tep_draw_hidden_field('source',9999);
  }
?>
<!-- //rmh referral end -->

			<tr> 
				<td height="4"></td>
			</tr>
			<tr> 
				<td valign="top" class="main">
			<b>		<?php echo ENTRY_ENQUIRY; ?></b><br>
					
	                <?php echo tep_draw_textarea_field('enquiry', 'soft', 50, 15, tep_sanitize_string($_POST['enquiry']), '', false); ?>
				
				</td>
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
            <td>

<table border="0" cellspacing="2" cellpadding="2" width="100%">
              <tr>
                <td>
<?php 
			echo VISUAL_VERIFY_CODE_TEXT_INSTRUCTIONS . '<br>';

			//echo tep_draw_input_field('visual_verify_code','','id="visual_verify_code"','text');
			echo '<input id="visual_verify_code" type="text" value="" name="visual_verify_code">';
			echo '&nbsp;' . '<span class="inputRequirement">' . VISUAL_VERIFY_CODE_ENTRY_TEXT . '</span>'; 
?>
				</td>

                <td>
                  
                  <?php
                  // ----- begin garbage collection --------
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

  if ($timediff > 5) {	// 5+ hours should be enough to fill in a form
    tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE code='" .$included_code['code'] . "' AND dt='" .$included_code['dt'] . "'");
  }  
}
// ----- end garbage collection --------

 //can replace the following loop with $visual_verify_code = substr(str_shuffle (VISUAL_VERIFY_CODE_CHARACTER_POOL), 0, rand(3,6)); if you have PHP 4.3
                    $visual_verify_code = "";
                    for ($i = 1; $i <= rand(3,6); $i++){
                          $visual_verify_code = $visual_verify_code . substr(VISUAL_VERIFY_CODE_CHARACTER_POOL, rand(0, strlen(VISUAL_VERIFY_CODE_CHARACTER_POOL)-1), 1);
                     }
                     $vvcode_oscsid = tep_session_id($_GET[tep_session_name()]);
                     tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE ixsid='" . $vvcode_oscsid . "'");
                     $sql_data_array = array('ixsid' => $vvcode_oscsid, 'code' => $visual_verify_code);
                     tep_db_perform(TABLE_VISUAL_VERIFY_CODE, $sql_data_array);
//                     $visual_verify_code = "";
$vvc = $visual_verify_code;


echo'<img src="/CaptchaSecurityImages.php?vvc='.$vvc.'" alt="" />';
?>
                </td>
                <td class="main"><?php echo VISUAL_VERIFY_CODE_BOX_IDENTIFIER; ?></td>
              </tr>
            </table>

</td>
          </tr>
        </table></td>
      </tr>
			<tr> 
				<td valign="top" style="padding-top:10px;"><?php echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); ?>
            </td>
          </tr>	
				</table>
				</td>
			</tr>	
<?php
  }
?>

    </table></form>
</td>

    <td width="" valign="top"><table border="0" width="" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>

