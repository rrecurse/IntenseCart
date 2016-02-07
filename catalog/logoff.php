<?php

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_LOGOFF);

	$cart = new shoppingCart();

	$breadcrumb->add(NAVBAR_TITLE);

	unset($_SESSION['customer_id']);
	unset($_SESSION['customer_default_address_id']);
	unset($_SESSION['customer_first_name']);
	unset($_SESSION['customer_country_id']);
	unset($_SESSION['customer_zone_id']);

	if(tep_session_is_registered('sppc_customer_group_id')) { 
		tep_session_unregister('sppc_customer_group_id');
		unset($_SESSION['sppc_customer_group_id']);
	}

	if(isset($_SESSION['sendto'])) {
		unset($_SESSION['sendto']);
	}

	if(isset($_SESSION['billto'])) {
		unset($_SESSION['billto']);
	}

	if(isset($_SESSION['shipping'])) {
		unset($_SESSION['shipping']);
	}

	if(isset($_SESSION['payment'])) {
		unset($_SESSION['payment']);
	}

	if(isset($_SESSION['comments'])) {
		unset($_SESSION['comments']);
	}

	$cart->reset(false);
  
	$hostname = $_SERVER['SERVER_NAME']; 
	$hostname = str_replace('www.', '', $hostname);
	
	$wishList->reset(false);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<?php

	$ssl = ($_SERVER['HTTPS'] != 'off' ? 'SSL' : 'NOSSL' );

	if(!empty($_GET['return'])) { 
		$url = filter_var($_GET['return'], FILTER_SANITIZE_URL);
		$url = parse_url($url, PHP_URL_PATH);
		echo '<meta http-equiv="refresh" content="5; url='.$_GET['return'].'">';
	} else { 
		$url = FILENAME_DEFAULT;
	}
?>
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
    </table></td>
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="pageHeading" align="center"><?php echo HEADING_TITLE; ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_MAIN; ?></td>
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
                <td align="right"><?php echo '<a href="' . tep_href_link($url, '', $ssl) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>

    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
