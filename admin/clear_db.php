<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


require('includes/application_top.php');
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body style="margin:0; background-color:transparent">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td  width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
          <table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php
if(!isset($_GET['action'])) {
echo '<br><br>'.TEXT_CLEAR_DB.'';
} 
if(isset($_GET['action']) && $_GET['action'] =='sess1month') {
		echo '<br><br><b>'.TEXT_CLEAR_DB_PROGRESS_7.'</b>';
		echo '<br><br><b>'.TEXT_CLEAR_DB_COMPLETE.'</b>';
}
if(isset($_GET['action']) && $_GET['action'] =='other') {
		echo '<br><br><b>'.TEXT_CLEAR_DB_PROGRESS_8.'</b>';
		echo '<br><br><b>'.TEXT_CLEAR_DB_COMPLETE.'</b>';
}

	if ($_GET['action']) {
      switch ($_GET['action']) {

		case 'sess1month':

		tep_db_query("DELETE FROM " . TABLE_SESSIONS . " 
					  WHERE FROM_UNIXTIME(expiry, '%Y-%m-%d %T') < DATE_SUB(NOW(), INTERVAL 1 MONTH)
					  AND sesskey != 'BloodyIXadminID'
					");

		tep_db_query("DELETE FROM ".TABLE_CUSTOMERS_BASKET." 
					  WHERE customers_basket_date_added < DATE_SUB(NOW(), INTERVAL 1 MONTH)
					 ");

		tep_db_query("DELETE FROM ".TABLE_CUSTOMERS_BASKET_ATTRIBUTES." 
					  WHERE customers_basket_id NOT IN(SELECT customers_basket_id FROM ".TABLE_CUSTOMERS_BASKET.")
					");


		/*tep_db_query("DELETE cb, cba 
						FROM " . TABLE_CUSTOMERS_BASKET . " AS cb, " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " AS cba 
						WHERE cb.customers_id = cba.customers_id 
						AND (datediff(NOW(),cb.customers_basket_date_added) > 31)
					   "); */

		tep_db_query("OPTIMIZE TABLE " . TABLE_SESSIONS);
		tep_db_query("OPTIMIZE TABLE " . TABLE_CUSTOMERS_BASKET);
		tep_db_query("OPTIMIZE TABLE " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES);

		break;

		case 'other':

		//tep_db_query("truncate " . TABLE_SESSIONS);
		//tep_db_query("truncate " . TABLE_WHOS_ONLINE);
		break;
		}

	} else {

?>
           <tr>
<br><br>
            <td align="left"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>

            <td class="Main"><?php echo TEXT_CLEAR_DB_QUESTION_3; ?></td>
           <td><?php
				echo '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CLEAR_DB, 'action=sess1month'). '">' . tep_image_button('button_confirm.gif', IMAGE_CONFIRM) . '</a>';?>
			</td>
           </tr>
		   <tr><br><br>
            <td align="left"><?php echo tep_draw_separator('pixel_trans.gif', '1', '50'); ?></td>

            <td class="Main"><?php echo TEXT_CLEAR_DB_QUESTION_4; ?></td>
           <td><?php
				echo '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CLEAR_DB,'action=other'). '">' . tep_image_button('button_confirm.gif', IMAGE_CONFIRM) . '</a>';?>
			</td>
           </tr>
<?php } ?>

          </table>
        </td>
      </tr>
    </table></td>
  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php');
?>