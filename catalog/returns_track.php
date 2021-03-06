<?php
/*
$id author Puddled Internet - http://www.puddled.co.uk
  email support@puddled.co.uk
   
  

  

  
*/

  require('includes/application_top.php');
      if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

  if (!$HTTP_GET_VARS['action']){
      $HTTP_GET_VARS['action'] = 'returns_track';
  }
  if ($HTTP_GET_VARS['action']) {
    switch ($HTTP_GET_VARS['action']) {
    case 'returns_show':

       // first carry out a query on the database to see if there are any matching tickets
       $database_returns_query = tep_db_query("SELECT returns_id, returns_status FROM " . TABLE_RETURNS . " where customers_id = '" . $customer_id . "' and rma_value = '" . $HTTP_POST_VARS['rma'] . "' or rma_value = '" . $HTTP_GET_VARS['rma'] . "'");
       if (!tep_db_num_rows($database_returns_query)) {
           tep_redirect(tep_href_link('returns_track.php?error=yes'));
       } else {
          $returns_query = tep_db_fetch_array($database_returns_query);
          $returns_id = $returns_query['returns_id'];
          $returns_status_id = $returns_query['returns_status'];
          $returns_status_query = tep_db_query("SELECT returns_status_name FROM " . TABLE_RETURNS_STATUS . " where returns_status_id = " . $returns_status_id . " and language_id = '" . (int)$languages_id . "'");
          $returns_status_array = tep_db_fetch_array($returns_status_query);
          $returns_status = $returns_status_array['returns_status_name'];
          $returned_products_query = tep_db_query("SELECT * FROM " . TABLE_RETURNS_PRODUCTS_DATA . " op, " . TABLE_RETURNS . " o where o.returns_id = op.returns_id and op.returns_id = '" . $returns_id . "'");
          $returned_products = tep_db_fetch_array($returned_products_query);

              require(DIR_WS_CLASSES . 'order.php');
           $order = new order($returned_products['order_id']);

       }

    break;

}
}

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_RETURNS_TRACK);
  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_RETURNS_TRACK, '', 'NONSSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>   
<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php if ($HTTP_GET_VARS['action'] == 'returns_show') { echo TEXT_SUPPORT_STATUS . ': ' . $returns_status; } else { echo HEADING_TITLE; } ; ?></td>
            <td align="right">
</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>

		<td width="100%" valign="top">
			<table border="0" width="100%" cellspacing="0" cellpadding="0">

          <?php
      if ($HTTP_GET_VARS['action'] == 'returns_show') {
          include(DIR_WS_MODULES . 'returns_track.php');
     // }

      ?>
<?php
 //
?>
		<tr>
			<td><?php echo tep_draw_separator('pixel_trans.gif', '20', '20'); ?><br /></td>
		</tr>

<?php
//}
?>

	   <table></td>
      <!-- if RMA number doesn't exist, show error message //-->
    <?php
} else {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
        	<td width="100%" valign="top">
			  <table border="0" width="100%" cellspacing="0" cellpadding="0">
                 <?php
                  if (isset($error)=='yes') {
                   $error_message = '<tr>
                                     <td width="10">&nbsp;</td>
                                     <td colspan="2" class="main">' . TEXT_TRACK_DETAILS_1 . '</td>
                                     </tr>
                                     <tr>';
                    new infoBox(array(array('text' => $error_message)));
                  // }
                    echo '<br /><br />';
              }
                    $returns = '<form action="' . $PHP_SELF . '?action=returns_show" method=post>
                             <tr>
                             <td colspan="2" class="main"><center>' . TEXT_TRACK_DETAILS_2 . '</center><br /></td>
                             </tr>
                             <tr>
                             <td width="45%" height="30" align="right" class="main">' . TEXT_YOUR_RMA_NUMBER . '&nbsp;</td>
     <td width="50%" height="30" align="left" class="main" colspan="2"><font color="CC0000"><input type="text" name="rma" value="" size="20"></font></td>
   
                             </tr>
                             <tr>
                             <td width="100%" colspan="2" class="main">&nbsp;</td>
                             </tr>
                             <tr>
                             <td width="100%" colspan="2" align="right"><input type=submit name="submit" value="' . TEXT_FIND_RETURN . '">&nbsp;&nbsp;&nbsp;</td>
                             </tr>
                             </form>


                             ';



                 new infoBox(array(array('text' => $returns)));



                 ?>

             <!--  </table></td></tr>-->
            </table></td></tr>
           </table></td></tr>
<?
}

?>


            </td>
          </tr>
        </table></td>
      </tr>

    </table></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
