<?php

class blk_checkout extends IXblock {


  function render($body) {
    global $customer;
    if ($_REQUEST['use_module']) {
      $paymod=tep_module($_REQUEST['use_module'],'payment');
      if (isset($paymod) && $paymod->prepareCheckout()) {
        $this->payment_method=$_REQUEST['use_module'];
//        $paymods[$_REQUEST['use_module']]=&$paymod;
        $addr=$paymod->getPayerAddress();
        if ($addr) {
          if ($addr->getEmail()) {
            $cid=IXdb::read("SELECT customers_id FROM customers WHERE customers_email_address = '" . addslashes($addr->getEmail()) . "'",NULL,'customers_id');
	    if ($cid) $_SESSION['customer_id']=$cid;
	  }
	  $customer['customers_email_address']=$addr->getEmail();
	  $customer['entry_street_address']=$addr->getAddress();
	  $customer['entry_suburb']=$addr->getAddress2();
	  $customer['customers_firstname']=$addr->getFirstName();
	  $customer['customers_lastname']=$addr->getLastName();
	  if ($addr->getPhone()) $customer['customers_telephone']=$addr->getPhone();
	  $customer['entry_city']=$addr->getCity();
	  $customer['entry_postcode']=$addr->getPostCode();
	  $customer['entry_country_id']=$addr->getCountryID();
	  $customer['entry_zone_id']=$addr->getZoneID();
        }
      }
    }
?>
<form name="checkout" action="<?=tep_href_link('checkout.php', tep_get_all_get_params(Array('action')), 'SSL')?>" method="post" id="checkout" onsubmit="return check_form(checkout);" autocomplete="off">
<input type="hidden" name="action" value="checkout">
<?
    $this->renderBody($body);
?>
</form>
<?
  }

  function render_($body) {
?>


function toggleBilling() {
  if ($('billing_address').style.display == 'none') {
    $('billing_address').style.display = 'block';
  } else {
    $('billing_address').style.display = 'none';
  }
}

function reloadShipping(ignoreStates) {
  reloadOT('');
  if ($('ship_postcode').value=='') {
    $('shipping_options').innerHTML= '<P>Please select your state and postal code</P>';
  } else {
    $('shipping_options').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Loading shipping costs, please wait...<br><img src="images/loading_bar.gif" alt=""></td></tr></table>';
    new ajax ('<?=HTTPS_SERVER;?>/shipping_options.php?zip='+document.checkout.ship_postcode.value+'&cnty='+document.checkout.ship_country.value+'&zone='+document.checkout.ship_state.value+'&weight=<?=$multi_weight?>&free=<?=$free_shipping?>', {method: 'post', postBody: 'street_address='+escape(document.checkout.ship_street_address.value)+'&city='+escape(document.checkout.ship_city.value)+'&state='+escape(document.checkout.ship_state.value), update: $('shipping_options')});
  }
}

function applyCoupon(code) {
  $('coupon_code').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Verifying and applying coupon, please wait...<br><img src="images/loading_bar.gif" alt=""></td></tr></table>';
  new ajax ('<?=HTTPS_SERVER;?>/checkout_ot.php', {postBody: 'gv_redeem_code='+code, update: $('coupon_code')});
}

//function resetOT() {
//    clearTimeout(otTimer);
//  otTimer = setTimeout("reloadOT(document.checkout.shipping.value)", 500);
//}
//
//function loadZones(section) {
//  var section_country = $(section+'_country');
//  var section_state = $(section+'_state');
//  $(section+'_state').innerHTML = '<img src="images/loading_bar.gif" alt="">';
//  new ajax('<?=HTTPS_SERVER;?>/state_dropdown.php?country='+$(section+'_country').value+'&sec='+section+'&bill=<?=(isset($bill_zone_id) ? $bill_zone_id : $customer['entry_zone_id']);?>&ship=<?=(isset($ship_zone_id) ? $ship_zone_id : $customer['entry_zone_id']);?>&d='+document.checkout.ship_state.value, {method: 'get', update: $(section+'_state')});
//}
//
//var otTimer = '';

function reloadOT(v) {
  if (v==null) v=document.checkout.shipping.value;
  if (v=='') {
    $('order_total').innerHTML = 'No shipping selected';
    return;
  }
  zone=document.checkout.ship_state.value;
  $('order_total').innerHTML = '<table border="0" height="65"><tr><td align="right" valign="middle"><img src="images/loading.gif" alt=""></td></tr></table>';
  new ajax ('<?=HTTPS_SERVER;?>/order_total.php?s='+v+'&z='+zone+'&c='+document.checkout.ship_country.value, {method: 'get', update: $('order_total')});
}

function setState(sec,v) {
  document.checkout[sec+"_state"].value = v;
  if (sec=='ship') reloadShipping();
}

function setShipping(v) {
  document.checkout.shipping.value = v;
  reloadOT(v);
  return true;
}

var reloadStateBusy=Array();

function reloadState(sec) {
  if (reloadStateBusy[sec]) return;
  reloadStateBusy[sec]=1;
  window.setTimeout('reloadStateBusy[\''+sec+'\']=0',500);
  var section_country = document.checkout[sec+'_country'].value;
  var section_state = document.checkout[sec+'_state'].value;
  var section_postcode = document.checkout[sec+'_postcode'].value;
  if (section_postcode=='') {
    $(sec+'_state').innerHTML = 'Please enter postcode first';
    if (sec=='ship') reloadShipping();
    return;
  }
  $(sec+'_state').innerHTML = '<img src="images/loading_bar.gif" alt="">';
  new ajax('<?=HTTPS_SERVER;?>/state_dropdown.php?country='+section_country+'&sec='+sec+'&postal='+section_postcode+'&d='+section_state, {method: 'get', update: $(sec+'_state')});
}

function reloadPostal(sec) {
  document.checkout[sec+'_postcode'].value = '';
  reloadState(sec);
}

function processOrder(f) {
  if (f.local_time) f.local_time.value=new Date().toString();
  if (window.checkoutSubmitted || !check_form(f)) { return; }
  if (!$('overlay')) $('default_overlay').id='overlay';
  if (!$('dialog_box')) $('default_dialog_box').id='dialog_box';
  if (!$('progress_bar_status')) $('default_progress_bar_status').id='progress_bar_status';
  if (!$('checkout_response')) {
    var rsbox=document.createElement('div');
    rsbox.id='checkout_response';
    $('dialog_box').insertBefore(rsbox,null);
  }

  window.scroll(0,0);
  $('overlay').style.display = "block";
  $('overlay').style.height = "2000px";
  $('dialog_box').style.width = "300px";
  $('dialog_box').style.height = "100px";
  $('dialog_box').style.top = (((window.innerHeight?window.innerHeight:(document.documentElement.clientHeight?document.documentElement:document.body).clientHeight) / 2) - 50)+"px";
  $('dialog_box').style.left = (((window.innerWidth?window.innerWidth:(document.documentElement.clientWidth?document.documentElement:document.body).clientWidth) / 2) - 150)+"px";
  $('progress_bar_status').style.display = "block";
  $('checkout_response').style.display = "none";
  $('dialog_box').style.display = "block";
  $('dialog_box').className = "dialog_process";
  window.checkoutSubmitted=true;
  
  new ajax('checkout.php?ajax=1',{postForm:f,onComplete:function(req) {
    $('progress_bar_status').style.display = "none";
    $('checkout_response').style.display = "block";
    $('checkout_response').innerHTML=req.responseText;
    var sc=$('checkout_response').getElementsByTagName('script');
    for (var i=0;sc[i];i++) window.eval(sc[i].innerHTML);
  }});
//  f.submit();

  var oForm = f.elements;
  for(i=0; i < oForm.length; i++) { 
    if (oForm[i].type=="select-one") {
      oForm[i].disabled=true;
//      oForm[i].style.backgroundColor="#999999";
    }
  }
}

function reviewOrder() {
  window.checkoutSubmitted=false;
  $('dialog_box').style.display = "none";
  $('overlay').style.display = "none";
  var oForm=document.checkout.elements;
  for(i=0; i < oForm.length; i++) { 
    if (oForm[i].type=="select-one") {
      oForm[i].disabled=false;
    }
  }
}

//--></script>
<?php require_once('includes/form_check_checkout_1.js.php'); ?>
<!-- ?php if (is_object($payment_modules)) echo $payment_modules->javascript_validation(); ? -->
<?php require_once('includes/form_check_checkout_2.js.php'); ?>
</head>
<body>
<?php require_once(DIR_WS_INCLUDES . 'header.php'); ?>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top">
      <table border="0" cellspacing="0" cellpadding="2">
        <?php require_once(DIR_WS_INCLUDES . 'column_left.php'); ?>
      </table>
    </td>
    <td width="100%" valign="top">
      <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">

        <tr>
          <td><table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td valign="top" style="height:20px;"><div style="padding-top:10px; font:bold 15px Arial;">Enter your payment information and confirm your order ...</div></td>
          </tr>
        </table></td>
        </tr>
<?php
  if ($_GET['db'] == '1') { echo '<pre>'; print_r($order); echo '</pre>'; }
  if ($messageStack->size('checkout') > 0) {
?>
        <tr>
          <td><?php echo $messageStack->output('checkout'); ?></td>
        </tr>
        <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
        </tr>
<?php
  }
  if ($show_cart) { ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="checkout_SectionTitle" style=" padding:3px; padding-left:10px; height:23px;"><b>&#149;Shopping Cart Content</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
<? include(DIR_WS_MODULES.'express_cart.php') ?>
        </td>
      </tr>
<? }

  if ($show_find_members) {
?>
      <? echo tep_draw_form('find_members', tep_href_link(FILENAME_CHECKOUT, 'action=login&'.tep_get_all_get_params(Array('action')), 'SSL'), 'POST', '', 'style="margin:0;"'); ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="checkout_SectionTitle" style="padding:3px; padding-left:10px; height:23px;"><b>&#149; Find Member</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
         <? include(DIR_WS_MODULES.'find_members.php') ?>
         </td>
      </tr>
      </form>
<?
  } else if (!tep_session_is_registered('customer_id')) {
?>
      <tr>
        <td style="height:20px"></td>
      </tr>
<tr>
        <td style="background-color:#000000; height:1px"></td>
      </tr>


      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TEXT_RETURNING_CUSTOMER; ?></b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td height="35" style="padding-left:5px;">
	 <div id="login_form"><table width="100%" border="0" cellpadding="0" cellspacing="0">
	   <tr>
        <td style="width:14px; padding-top:2px;"><a href="/login.php" onClick="showLoginForm(); return false;"><img src="images/icons/plus.gif" border="0" alt=""></a> </td>
<td><a href="/login.php" onClick="showLoginForm(); return false;">&nbsp; <b>Returning customers, please click here to login and populate all fields below.</b></a></td>
</tr></table></div>
	 <script type="text/javascript">
<!--
  function showLoginForm() {
    $('login_form').innerHTML=
'          <? echo tep_draw_form('login', tep_href_link(FILENAME_CHECKOUT, 'action=login&'.tep_get_all_get_params(Array('action')), 'SSL'), 'POST', '', 'style="margin:0;"'); ?>\n'+
'          <table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" class="infoBox">\n'+
'            <tr class="infoBoxContents">\n'+
'              <td style="padding-top:10px;">\n'+
'                <table width="100%" border="0" align="center" cellpadding="5" cellspacing="5">\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b><?php echo ENTRY_EMAIL_ADDRESS; ?></b></td>\n'+
'                    <td class="main"><?php echo tep_draw_input_field('email_address', '', 'style="width:150px; height:20px"'); ?></td>\n'+
'                  </tr>\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b><?php echo ENTRY_PASSWORD; ?></b></td>\n'+
'                    <td class="main"><?php echo tep_draw_password_field('password', '', 'style="width:150px; height:20px"'); ?></td>\n'+
'                  </tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" class="smallText" align="center"><?php echo '<a href="' . tep_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a>'; ?></td>\n'+
'                  </tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" align="center" style="padding-right:5px;"><?php echo ($HTTP_GET_VARS['co'] == '1' ? tep_draw_hidden_field('co', '1') : '') . tep_image_submit('button_login.gif', IMAGE_BUTTON_LOGIN); ?></td>\n'+
'                  </tr>\n'+
'                </table>\n'+
'              </td>\n'+
'            </tr>\n'+
'          </table>\n'+
'          </form>\n';
  }
  //-->
  </script>
        </td>
      </tr>

<?
//    $customer = array();
  }
?>
<tr><td>
<?=tep_draw_form('checkout', tep_href_link('checkout.php', tep_get_all_get_params(Array('action')), 'SSL'), 'post', 'onSubmit="return check_form(checkout);"')?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><?=tep_draw_hidden_field('action', 'checkout'); ?></td>
      </tr>

<?
  if ($display_mode=='admin') {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="checkout_SectionTitle" style="padding:3px; padding-left:10px; height:23px;"><b>&#149; Admin Controls</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
         <? include(DIR_WS_MODULES.'checkout_admin_controls.php') ?>
         </td>
      </tr>
<?
  }
?>

      <tr>
        <td style="background-color:#000000;height:1px"></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="border-right:0; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo (!tep_session_is_registered('customer_id') ? TEXT_NEW_CUSTOMER : TEXT_RETURNING_CUSTOMER); ?></b></td>
           <td class="checkout_SectionTitle" style="border-left:0; padding:3px; font-weight:bold; color:#FF0000; padding-right:15px;" align="right"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; padding-top:15px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo CATEGORY_PERSONAL; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="250" class="inputRequirement"><?php echo ENTRY_FIRST_NAME . '<br>' . tep_draw_input_field('firstname', $customer['customers_firstname'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></td>
                    <td width="245" class="inputRequirement"><?php echo ENTRY_LAST_NAME . '<br>' . tep_draw_input_field('lastname', $customer['customers_lastname'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></td>
                  </tr>
                  <tr valign="bottom">
                    <td height="45" class="inputRequirement"><?php echo ENTRY_TELEPHONE_NUMBER . '<br>' . tep_draw_input_field('telephone', $customer['customers_telephone'], 'style="width:100px" maxlength=32') . '&nbsp;' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': ''); ?></td>
                    <td height="45" class="inputRequirement"><?php echo ENTRY_EMAIL_ADDRESS . '<br>' . tep_draw_input_field('email_address', $customer['customers_email_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>': ''); ?></td>
                  </tr>
                  <tr valign="bottom">
                    <td height="45" class="inputRequirement"><?php echo ENTRY_COMPANY . '<br>' . tep_draw_input_field('company', $customer['customers_company'], 'style="width:100px"') ?></td>
                    <td height="45" class="inputRequirement"><?php echo ENTRY_FAX_NUMBER . '<br>' . tep_draw_input_field('fax', $customer['customers_fax'], 'style="width:100px" maxlength=32') ?></td>
                  </tr>
                </table></td>
              </tr>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo TABLE_HEADING_SHIPPING_ADDRESS; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;">
                  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="250" class="main" style="width:240px"><?php echo ENTRY_STREET_ADDRESS . '<br>' . tep_draw_input_field('ship_street_address', $customer['entry_street_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
                      <td width="245" class="main"><?php echo 'Suite / Floor / Other:
<br>' . tep_draw_input_field('ship_suburb', $customer['entry_suburb'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></td>
                    </tr>
                    <tr valign="bottom">
                      <td height="45" class="main"><?php echo ENTRY_CITY . '<br>' . tep_draw_input_field('ship_city', $customer['entry_city'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>
                      <td height="45" class="main"><table><tr><td colspan="2"><?=ENTRY_STATE?></td></tr><tr><td><div id="ship_state" style="display:block;position:relative;z-index: 1"></div><input type="hidden" name="ship_state" value="<?=$customer['entry_zone_id']?>"></td><td><?php if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT . '</span>'; ?></td></tr></table></td>
                    </tr>
                    <tr valign="bottom">
                      <td colspan="2">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                          <tr>
			  <? if (!$customer['entry_postcode']) $customer['entry_postcode']=$GLOBALS['ship_postcode']; ?>
                            <td width="240" height="45"  class="inputRequirement"><?php echo ENTRY_POST_CODE . '<br>' . tep_draw_input_field('ship_postcode', $customer['entry_postcode'], 'style="width:100px" maxlength=10 id="ship_postcode" onBlur="reloadState(\'ship\')"','text',false) . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
                            <td height="45"  class="inputRequirement">&nbsp;<?php echo ENTRY_COUNTRY . '<br>' . tep_get_country_list('ship_country', (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : ($ship_country?$ship_country:STORE_COUNTRY)), 'id="ship_country" onChange="reloadPostal(\'ship\')"') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo TABLE_HEADING_BILLING_ADDRESS; ?><span style="padding-left: 50px;" ><input type="checkbox" name="bill_same" value="1" CHECKED onClick="toggleBilling();" class="bill_same"><? echo TEXT_BILLING_SAME; ?></span></td>
              </tr>
              <tr>
                <td style="padding-top:10px;">
                  <div id="billing_address" style="display:none">
                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="250" class="main" style="width:240px"><?php echo ENTRY_STREET_ADDRESS . '<br>' . tep_draw_input_field('bill_street_address', $customer['entry_street_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
                        <td width="245" class="main"><?php echo ENTRY_SUBURB . '<br>' . tep_draw_input_field('bill_suburb', $customer['entry_suburb'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></td>
                      </tr>
                      <tr valign="bottom">
                        <td height="45" class="main"><?php echo ENTRY_CITY . '<br>' . tep_draw_input_field('bill_city', $customer['entry_city'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>
                        <td height="45" class="main"><?php echo ENTRY_STATE . '<br><div id="bill_state"></div><input type="hidden" name="bill_state" value="' . $customer['entry_zone_id'] . '">'; if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT . '</span>'; ?></td>
                      </tr>
                      <tr valign="bottom">
                        <td colspan="2">
                          <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                              <td width="200" height="45" class="inputRequirement"><?php echo ENTRY_POST_CODE . '<br>' . tep_draw_input_field('bill_postcode', $customer['entry_postcode'], 'style="width:100px" maxlength=10 onChange="reloadState(\'bill\')"') . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
                              <td width="400" height="45" class="inputRequirement">&nbsp;<?php echo ENTRY_COUNTRY . '<br>' . tep_get_country_list('bill_country', (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : STORE_COUNTRY), 'id="bill_country" onChange="reloadPostal(\'bill\')"') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </div>
                </td>
              </tr>
	      <?
	       if (USE_COUPONS=='Enable') {
	      ?>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Coupon Code</td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="100%" valign="top"><div id="coupon_code"><table><tr><td>Coupon Code: </td><td class="inputRequirement"><?=tep_draw_input_field('gv_redeem_code', '', 'id="gv_redeem_code"') . '</td><td>&nbsp; <img onClick="applyCoupon($(\'gv_redeem_code\').value)" src="' . DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/' . $language . '/button_redeem.gif" border="0" alt="' . IMAGE_REDEEM_VOUCHER . '" title="' . IMAGE_REDEEM_VOUCHER . '" style="cursor:pointer">'; ?></td></tr></table>
<noscript>Please enable javascript to checkout.</noscript></td>
                  </tr>
                </table>
</td>
              </tr>

                  <?
		 }
		  
  if (!tep_session_is_registered('customer_id')) {
?>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; padding-top:20px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo CATEGORY_OPTIONS; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="100%" valign="top"><?php echo ENTRY_NEWSLETTER; ?>? &nbsp; <?php echo tep_draw_checkbox_field('newsletter', '1') . '&nbsp;' . (tep_not_null(ENTRY_NEWSLETTER_TEXT) ? '<span class="inputRequirement">' . ENTRY_NEWSLETTER_TEXT . '</span>': ''); ?></td>
                  </tr>
                </table>
</td>
              </tr>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Create New Password<?//php echo CATEGORY_PASSWORD; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr valign="top">
                    <td width="250" height="45" class="main" style="width:240px"><?php echo ENTRY_PASSWORD . '<br>' . tep_draw_password_field('password', (isset($HTTP_POST_VARS['password'])?$HTTP_POST_VARS['password']:''), 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_TEXT . '</span>': ''); ?></td>
                    <td width="245" height="45" class="main"><?php echo ENTRY_PASSWORD_CONFIRMATION . '<br>' . tep_draw_password_field('confirmation', (isset($HTTP_POST_VARS['confirmation'])?$HTTP_POST_VARS['confirmation']:''), 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>': ''); ?></td>
                  </tr>
                </table></td>
              </tr>
                  <?
  }
?>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; padding-top:15px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Additional Information</td>
              </tr>
<!-- //rmh referral start -->
<?php
// MegaJim - Fuck this box!
  if (!isset($customer_id)) {
  
  if ((tep_not_null(tep_get_sources()) || DISPLAY_REFERRAL_OTHER == 'true') && (!tep_session_is_registered('referral_id') || (tep_session_is_registered('referral_id'))) ) {
    if ((tep_session_is_registered('referral_id') && tep_not_null($referral_id)) || tep_not_null($_POST['source_other'])) {
      $source_id = '9999';
    } else {
      $source_id = $_POST['source'];
    }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="checkout_itemTitle"><b><?php echo CATEGORY_SOURCE; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="checkout_itemTitle"><?php echo ENTRY_SOURCE; ?></td>
                <td ><?php echo tep_get_source_list('source', true, $source_id) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_TEXT . '</span>': ''); ?></td> 
              </tr>
              <tr>
                <td class="checkout_itemTitle"><?php echo ENTRY_SOURCE_OTHER; ?></td>
                <td ><?php echo tep_draw_input_field('source_other', (tep_not_null($referral_id) ? $referral_id : '')) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_OTHER_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_OTHER_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?
  }
  
  } else {
    echo tep_draw_hidden_field('source',6666);
  }
?>
<!-- //rmh referral end -->
	      <tr>
	        <td class="checkout_itemTitle"><b><?php echo CATEGORY_COMMENTS; ?></b></td>
	      </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr valign="top">
                    <td width="250" height="45" class="main" style="width:240px"><?php echo tep_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
                    </tr>
                </table></td>
              </tr>
            </table>
            </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '30'); ?></td>
      </tr>
      <tr>
        <td style="background-color:#000000;height:1px"></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="border-right:0; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TABLE_HEADING_SHIPPING_METHOD; ?></b></td>
            <td align="right" class="checkout_SectionTitle" style="border-left:0; padding:3px; font-weight:bold; color:#ff0000; padding-right:15px;"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
          <div id="shipping_options"><noscript>Please enable javascript to checkout.</noscript></div>
          <input type="hidden" name="shipping" value="">
        </td>
      </tr>
      <tr>
        <td style="background-color:#000000;height:1px"></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="border-right:0; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TABLE_HEADING_PAYMENT_METHOD; ?></b></td>
            <td align="right" class="checkout_SectionTitle" style="border-left:0; padding:3px; font-weight:bold; color:#ff0000; padding-right:15px;"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" class="infoBox">
          <tr class="infoBoxContents">
            <td>
<?
    
  if (!sizeof($paymods)) echo 'No payment methods available';
  else {
    $pay_selbox=Array();
    foreach ($paymods AS $mkey=>$mod) $pay_selbox[]=Array('id'=>$mkey,'text'=>$mod->getTitle());
    $selmod=$pay_selbox[0]['id'];
    echo (sizeof($pay_selbox)>1?tep_draw_pull_down_menu('pay_method',$pay_selbox,$selmod,'id="pay_method" onChange="setPayMethod()"'):tep_draw_hidden_field('pay_method',$selmod).$pay_selbox[0]['text']);
    foreach ($paymods AS $mkey=>$mod) {
      echo '<div id="'.$mkey.'" style="'.($mkey==$selmod?'':'display:none').';">';
      echo $mod->paymentBox();
      echo "</div>\n";
    }
?>
<script type="text/javascript">
  function setPayMethod() {
    var blk=$('pay_method');
    if (!blk) return false;
    for (var i=0;blk.options[i];i++) if ($(blk.options[i].value)) $(blk.options[i].value).style.display=blk.options[i].selected?'':'none';
    return true;
  }
</script>
<?
  }
?>
            <!--/table></td>
          </tr-->
        </table></td>
      </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '15'); ?></td>
          </tr>
	  <tr>
	    <td align="right"><div id="order_total"></div>
	    
<div id="default_overlay" style="display: none; position:absolute;"></div>
<div id="default_dialog_box" style="display: none; position:absolute;">
    <div id="default_dialog_box_header">
      <div style="width: 16px"><img src="images/icons/lock_blue.gif" height="16" width="16"></div>
      <div style="position: absolute; top: 4px; left: 30px;color:#FFF"><b>Order Processing!</b></div>
    </div>
    <div id="default_progress_bar_status"><table border="0"><tr><td style="width:50px"><img src="images/loading.gif" alt=""></td><td><b>Your order is currently being processed and may take up to two minutes to complete.  Thank you for your patience!</b></td></tr></table></div>
    <div id="default_checkout_response" style="display:none"></div>
</div>
	    
	    </td>
	  </tr>
          <script type="text/javascript">reloadState('ship'); reloadState('bill');</script>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '15'); ?></td>
          </tr>
          <tr>
            <td><table border="0" width="100%" cellspacing="1" cellpadding="2"  class="infoBox">
              <tr class="infoBoxContents">
                <td align="right"><input type="hidden" name="local_time"><div id="confirm_button"><a href="javascript: void(0)" onClick="processOrder(document.checkout);return false"><?php echo tep_image(DIR_WS_CATALOG_LAYOUT_IMAGES.'buttons/'.$language.'/button_confirm_order.gif', IMAGE_BUTTON_CONFIRM_ORDER, 'border="0"'); ?></a></div></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '15'); ?></td>
          </tr>
    </table></form></td>
</tr></table></td>



<?php
  }
  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'login': return $_SESSION['customer_id'];
      case 'nologin': return !$_SESSION['customer_id'];
      default: return true;
    }
  }


function getVar($var,$args) {
	if (!tep_session_is_registered('ship_country')) { 
		$ship_country = (isset($customer['entry_country_id'])) ? $customer['entry_country_id'] : STORE_COUNTRY;
	}

    global $customer,$customer_id;
    switch ($var) {
      case 'firstname': return $customer_id?$customer['customers_firstname']:'';
      case 'lastname': return $customer_id?$customer['customers_lastname']:'';
      case 'telephone': return $customer_id?$customer['customers_telephone']:'';
      case 'email_address': return $customer_id?$customer['customers_email_address']:'';
      case 'company': return $customer_id?$customer['customers_company'].'':'';
      case 'fax': return $customer_id?$customer['customers_fax']:'';
      case 'ship_street_address': return $customer_id?$customer['entry_street_address']:'';
      case 'ship_suburb': return $customer_id?$customer['entry_suburb']:'';
      case 'ship_city': return $customer_id?$customer['entry_city']:'';
      case 'ship_postcode': return $customer_id?$customer['entry_postcode']:"{$GLOBALS['ship_postcode']}";
      case 'ship_country_select': return tep_get_country_list('ship_country', $ship_country, 'id="ship_country" onChange="reloadPostal(\'ship\')"');
      case 'bill_street_address': return $customer_id?$customer['entry_street_address']:'';
      case 'bill_suburb': return $customer_id?$customer['entry_suburb']:'';
      case 'bill_city': return $customer_id?$customer['entry_city']:'';
      case 'bill_postcode': return $customer_id?$customer['entry_postcode']:"{$GLOBALS['bill_postcode']}";
      case 'bill_country_select': return tep_get_country_list('bill_country', (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : ($bill_country?$bill_country:STORE_COUNTRY)), 'id="bill_country" onChange="reloadPostal(\'bill\')"');
      case 'comments': return "{$_POST['comments']}";
      case 'password': return "{$_POST['password']}";
      case 'payment_method': return "{$this->payment_method}";
      case 'source_select':
        if ((tep_session_is_registered('referral_id') && tep_not_null($referral_id)) || tep_not_null($_POST['source_other'])) {
          $source_id = '9999';
        } else {
          $source_id = $_POST['source'];
        }
	return tep_get_source_list('source', true, $source_id);
      
	case 'source_other': 

		if(empty($_POST['source']) && tep_session_is_registered('referral_id')) {

			$ref_query = tep_db_query("SELECT affiliate_homepage FROM affiliate_affiliate WHERE affiliate_id = '".$GLOBALS['referral_id']."'");
			$ref = (tep_db_num_rows($ref_query) > 0 ? tep_db_result($ref_query,0) : NULL);
			return "$ref";
		} else {
			return NULL;
		}
      
    default:
		return $customer[$var];
    }
  }

}
?>
