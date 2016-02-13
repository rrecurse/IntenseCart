<?php
// Buffering
ob_start();

require('includes/application_top.php');


require(DIR_WS_CLASSES . 'currencies.php');
$currencies = new currencies();
include(DIR_WS_CLASSES . 'order.php');


$order_status = $select_order_status;
$display_order_status = $order_status;
if ($order_status == "")
        {
        $order_status = 1;
        }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo TITLE . ' - ' . OL_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

<style type="text/css">

.orderlist {
	font:bold 12px tahoma;
	white-space: nowrap;
	color: black;
	border-collapse: collapse;
	padding: 0.2em 0.3em;
}
</style>

</head>
<body style="margin:0; background:transparent;">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="100%" valign="top" colspan="2"><br>
<span style="font:bold 18px verdana; color:#666666;"><?php echo OL_HEAD; ?></span><br><br>

<span style="font:bold 14px verdana; color:#FF0000;">
Now Showing: <?php

$query_status = "SELECT * FROM " . TABLE_ORDERS_STATUS . " where `orders_status_id` = $order_status AND language_id = $languages_id";
$status_result = tep_db_query($query_status);
        while ($row4 = tep_db_fetch_array($status_result))
        {
                echo "(";
                     $orders_status_name =  $row4['orders_status_name'];
                echo $orders_status_name;
                echo ")";
        }


// FORM THAT LETS YOU SELECT WHICH ORDER STATUS TO DISPLAY
?>
</span><br><br>
<form name="orderstatus" method="post" action="<?php echo $PHP_SELF?>">

<?php echo OL_ENTRY_DAYS ?><input name="orderlist_days" type="text" id="orderlist_days" size="2" style="font-weight:bold;" maxlength="4">

<?php
 
  $query_status = "SELECT * FROM " . TABLE_ORDERS_STATUS . " WHERE language_id = $languages_id";
  $status_result = tep_db_query($query_status);
                echo OL_SELECT_STATUS . "&nbsp;";
                echo "<select name=\"select_order_status\">";
                echo "<option value=></option>";
  while ($row3 = tep_db_fetch_array($status_result))
                {
                echo "<option value=";
                echo $row3['orders_status_id'];
                echo ">";
                echo $row3['orders_status_name'];
                echo "</option><br>";
                }
                echo "</select>";
?>
  <input type="submit" name="Submit" value="<?php echo OL_SUBMIT; ?>">  <input type="button" value="<?php echo OL_PRINT; ?>" onClick="window.print()" />

</form>


<br><br>

<table width="100%" align="center" cellspacing="0" cellpadding="4" border="1">
  <tr class="headers" style="font:bold 14px; arial; color:#0000FF;">
    <td class="orderNr" align="center"><?php echo OL_ORDERNR; ?></td>
    <td class="date" align="center"><?php echo OL_DATE; ?></td>
    <td class="name"><?php echo OL_NAME; ?></TD>
    <td class="details"><?php echo OL_DETAILS; ?></td>
</tr>

<?php
if (!isset($orderlist_days))
 { $orderlist_days=ORDERLIST_DAYS; }
$query1 = "SELECT * FROM " . TABLE_ORDERS . " WHERE orders_status = $order_status and TO_DAYS(NOW()) - TO_DAYS(date_purchased) < '" . $orderlist_days . "' ORDER BY orders_id DESC";
$result = tep_db_query($query1);

while ($row = tep_db_fetch_array($result))    {
        $ordernummer = $row['orders_id'];
?>

           <TR>
             <TD align="center" valign="top" style="font:12px; arial;">
            <b>
            <?php echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $ordernummer . '&action=edit') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . $ordernummer; ?>
             </b></TD>
               

             <TD align="center" valign="top" style="font:12px; arial;">
               <?php echo tep_datetime_short($row['date_purchased']);?></TD>

             <TD valign="top" style="font:12px; arial;">
               <?php echo OL_CUSTOMERNAME .'&nbsp;'. $row{'delivery_name'}; ?><br>
               <?php echo OL_ADDRESS .'&nbsp;'. $row{'delivery_street_address'}; ?><br>
               <?php echo OL_CITY .'&nbsp;'. $row{'delivery_city'}; ?><br>
               <?php echo OL_ZIP .'&nbsp;'. $row{'delivery_postcode'}; ?><br>
               <?php echo OL_TEL .'&nbsp;'. $row{'customers_telephone'}; ?><br>
               <?php echo OL_EMAIL .'&nbsp;'. $row{'customers_email_address'}; ?>
             </TD>


<TD valign="top" style="font:12px; arial;">

<?php
$order = new order($ordernummer);
   for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {

   // Manufacturer Listing
	//Use the products ID# to find the proper Manufacturer of this specific product
$v_query = tep_db_query("SELECT manufacturers_id FROM ".TABLE_PRODUCTS." WHERE products_id = '".$order->products[$i]['products_id']."'");
$v = tep_db_fetch_array($v_query);
	//Select the proper Manufacturers Name via the Manufacturers ID# then display that name for a human readable output
$mfg_query = tep_db_query("SELECT manufacturers_name FROM ".TABLE_MANUFACTURERS." WHERE manufacturers_id = '".$v['manufacturers_id']."'");
$mfg = tep_db_fetch_array($mfg_query);
// End Manufacturer Listing	
?>
               <b><a href="<?php echo tep_href_link(FILENAME_CATEGORIES, 'action=new_product_preview&read=only&pID=' . $order->products[$i]['products_id'] . '&oID=' . $oID . '&origin=' . FILENAME_ORDERS . '?oID=' . $oID  . '&retOID=' . $oID . '&retAction=edit') ;?>"><?php echo $order->products[$i]['qty'] . '&nbsp;x ' . $order->products[$i]['name']; ?></a></b> <?php echo OL_MODEL . $order->products[$i]['model']; ?><br><?php echo OL_MANU . $mfg['manufacturers_name']; ?><br>
               
			<?php 
				
				//*******************************************
				//!!START MOD!!: Display Attribs
				//*******************************************
				//echo OL_MANU . '&nbsp;' . $mfg['manufacturers_name']."<br>";		//Doesn't work
				//echo OL_MODEL . '&nbsp;' . $order->products[$i]['model']."<br>";	//Doesn't work
				$j = 0;
				while ($products_options = $order->products[$i]['attributes'][$j]['option'])    {
					//$products_options = $order->products[$i]['attributes'][$j]['option'];
					$products_options_values = $order->products[$i]['attributes'][$j]['value'];
					echo "<b>$products_options</b> = $products_options_values<br>";
					$j++;
				}
				//*******************************************				
				//!!END MOD!!
				//*******************************************
			?>

<?php
 }
?>
               <?php echo '--------------------------------------'; ?><br>
               <?php echo $row{'payment_method'}; ?><br>
<?php
   for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
?>
               <?php echo '<br>' . $order->totals[$i]['title'] . '&nbsp;' . $order->totals[$i]['text']; ?>
<?php
 }
?>


  </TD>
 </TR>

<?php
      }

?>

</table></td>
</tr>
</table>

</body>
</html>
<?php

ob_end_flush();
?>
