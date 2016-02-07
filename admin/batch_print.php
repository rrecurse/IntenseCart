<?php

require('includes/application_top.php');

if ($HTTP_GET_VARS['mkey']) {

$key = $HTTP_GET_VARS['mkey']; 
$message = $error[$key]; 
$HTTP_GET_VARS['act'] = 0; 

}

if ($HTTP_GET_VARS['act'] == '') { $HTTP_GET_VARS['act'] = 0; }

if (strlen($HTTP_GET_VARS['act']) == 1 && is_numeric($HTTP_GET_VARS['act']))
{

switch ($HTTP_GET_VARS['act']) {

case 1:

// CHECK DATE ENTERED, GRAB ALL ORDERS FROM THAT DATE, AND CREATE PDF FOR ORDERS
if (!isset($HTTP_POST_VARS['startdate'])) { message_handler(); }
if ((strlen($HTTP_POST_VARS['startdate']) != 10) || verify_start_date($HTTP_POST_VARS['startdate'])) { message_handler('ERROR_BAD_DATE'); }
if (!is_writeable(BATCH_PDF_DIR)) { message_handler('SET_PERMISSIONS'); }
$time0   = time();
$startdate = tep_db_prepare_input($HTTP_POST_VARS['startdate']);

if (!isset($HTTP_POST_VARS['enddate'])) { message_handler(); }
if ((strlen($HTTP_POST_VARS['enddate']) != 10) || verify_end_date($HTTP_POST_VARS['enddate'])) { message_handler('ERROR_BAD_DATE'); }
if (!is_writeable(BATCH_PDF_DIR)) { message_handler('SET_PERMISSIONS'); }
$time0   = time();
$enddate = tep_db_prepare_input($HTTP_POST_VARS['enddate']);

require(DIR_WS_CLASSES . 'currencies.php');
require(BATCH_PRINT_INC . 'class.ezpdf.php');
require(DIR_WS_CLASSES . 'order.php');

$pdf = new Cezpdf(PAGE);
$currencies = new currencies();

$pdf->selectFont(BATCH_PDF_DIR . 'Helvetica.afm');
$pdf->setFontFamily(BATCH_PDF_DIR . 'Helvetica.afm');
if ($HTTP_POST_VARS['show_comments']) { $get_customer_comments = ' and h.orders_status_id = ' . DEFAULT_ORDERS_STATUS_ID; }
if ($HTTP_POST_VARS['pull_status']){ $pull_w_status = " and o.orders_status = ". $HTTP_POST_VARS['pull_status']; }

$orders_query = tep_db_query("select o.orders_id,h.comments,MIN(h.date_added) from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS_HISTORY . " h where o.date_purchased between '" . tep_db_input($startdate) . "' and '" . tep_db_input($enddate) . " 23:59:59'  and h.orders_id = o.orders_id" . $pull_w_status . $get_customer_comments . ' group by o.orders_id');

if (!tep_db_num_rows($orders_query) > 0) { message_handler('NO_ORDERS'); }
 $num = 0;
 
 while ($orders = tep_db_fetch_array($orders_query)) {

  $order = new order($orders['orders_id']);
  
if ($num != 0) { $pdf->EzNewPage(); }

$y = $pdf->ezText(STORE_NAME_ADDRESS,COMPANY_HEADER_FONT_SIZE);
$y -= 10; 

$pdf->setLineStyle(1);
$pdf->line(LEFT_MARGIN,$y,LINE_LENGTH,$y);
$pdf->ezSetY($y);
$dup_y = $y;

$y = $pdf->ezText("<b>" . TEXT_ORDER_NUMBER . " </b>" . $orders['orders_id'] ."\n\n",SUB_HEADING_FONT_SIZE);

if ($HTTP_POST_VARS['show_order_date']) { 
	$pdf->ezSetY($dup_y);
	$pdf->ezText("<b>" . TEXT_ORDER_DATE . " </b>" . date(TEXT_ORDER_FORMAT, strtotime($order->info['date_purchased'])) ."\n\n",SUB_HEADING_FONT_SIZE,array('justification'=>'right'));
	}
	 

$pdf->addText(LEFT_MARGIN,$y,SUB_HEADING_FONT_SIZE,"<b>" . ENTRY_SOLD_TO . "</b>");

$pos = $y;
$indent = LEFT_MARGIN + TEXT_BLOCK_INDENT;

$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->billing['name']);
if ($order->billing['company'] && $order->billing['company'] != 'NULL') {
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->billing['company']);
}
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->billing['street_address']);

if ($order->billing['suburb'] && $order->billing['suburb'] != 'NULL') {
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->billing['suburb']);
}

$cty_st_zip = $order->billing['city'] . " " . $order->billing['state'] . ", " . $order->billing['postcode'];
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$cty_st_zip);


$pdf->addText(SHIP_TO_COLUMN_START,$y,SUB_HEADING_FONT_SIZE,"<b>" . ENTRY_SHIP_TO . "</b>");

$pos = $y;
$indent = SHIP_TO_COLUMN_START + TEXT_BLOCK_INDENT;

$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->delivery['name']);
if ($order->delivery['company'] && $order->delivery['company'] != 'NULL') {
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->delivery['company']);
}
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->delivery['street_address']);

if ($order->delivery['suburb'] && $order->delivery['suburb'] != 'NULL') {
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$order->delivery['suburb']);
}

$cty_st_zip = $order->delivery['city'] . " " . $order->delivery['state'] . ", " . $order->delivery['postcode'];
$pdf->addText($indent,$pos -= GENERAL_LEADING,GENERAL_FONT_SIZE,$cty_st_zip);

if ($HTTP_POST_VARS['show_phone'] || $HTTP_POST_VARS['show_email'] ) {

$pos -= SECTION_DIVIDER;
$pdf->ezSetY($pos);

if ($HTTP_POST_VARS['show_phone']) {
$pos = $pdf->ezText("<b>" . ENTRY_PHONE . "</b> " . $order->customer['telephone'],GENERAL_FONT_SIZE);

}  if ($HTTP_POST_VARS['show_email']) {
$pos = $pdf->ezText("<b>" . ENTRY_EMAIL . "</b> " .$order->customer['email_address'],GENERAL_FONT_SIZE);

 }
} 
 
 $pos -= SECTION_DIVIDER;
 $pdf->ezSetY($pos);
 
if ($HTTP_POST_VARS['show_pay_method']) {
$pos = $pdf->ezText("<b>" . ENTRY_PAYMENT_METHOD . "</b> " . $order->info['payment_method'],GENERAL_FONT_SIZE);

	if ($order->info['payment_method'] == PAYMENT_TYPE) {
$pos = $pdf->ezText("<b>" . ENTRY_PAYMENT_TYPE . "</b> " . $order->info['cc_type'],GENERAL_FONT_SIZE);
$pos = $pdf->ezText("<b>" . ENTRY_CC_OWNER . "</b> " . $order->info['cc_owner'],GENERAL_FONT_SIZE);
		if ($HTTP_POST_VARS['show_cc']) {
		$pos = $pdf->ezText("<b>" . ENTRY_CC_NUMBER . "</b> " . $order->info['cc_number'],GENERAL_FONT_SIZE);
		}
		
		$pos = $pdf->ezText("<b>" . ENTRY_CC_EXP . "</b> " . $order->info['cc_expires'],GENERAL_FONT_SIZE);
	}

}
$pos -= SECTION_DIVIDER;
 
change_color(TABLE_HEADER_BKGD_COLOR);
$pdf->filledRectangle(LEFT_MARGIN,$pos-PRODUCT_TABLE_ROW_HEIGHT,PRODUCT_TABLE_HEADER_WIDTH,PRODUCT_TABLE_ROW_HEIGHT);

$x = LEFT_MARGIN + PRODUCT_TABLE_LEFT_MARGIN;
$pos = ($pos-PRODUCT_TABLE_ROW_HEIGHT) + PRODUCT_TABLE_BOTTOM_MARGIN;

change_color(GENERAL_FONT_COLOR);

$pdf->addText($x,$pos,TABLE_HEADER_FONT_SIZE,TABLE_HEADING_PRODUCTS);
$pdf->addText($x += PRODUCTS_COLUMN_SIZE,$pos,TABLE_HEADER_FONT_SIZE,TABLE_HEADING_PRODUCTS_MODEL);
$pdf->addText($x += MODEL_COLUMN_SIZE,$pos,TABLE_HEADER_FONT_SIZE,TABLE_HEADING_TAX);
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,TABLE_HEADING_PRICE_EXCLUDING_TAX);
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,TABLE_HEADING_PRICE_INCLUDING_TAX);
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,TABLE_HEADING_TOTAL_EXCLUDING_TAX);
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,TABLE_HEADING_TOTAL_INCLUDING_TAX);

$pos -= PRODUCT_TABLE_BOTTOM_MARGIN;

// Sort through the products

for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {

$prod_str = $order->products[$i]['qty'] . " x " . $order->products[$i]['name'];

change_color(PRODUCT_LISTING_BKGD_COLOR);
$pdf->filledRectangle(LEFT_MARGIN,$pos-PRODUCT_TABLE_ROW_HEIGHT,PRODUCT_TABLE_HEADER_WIDTH,PRODUCT_TABLE_ROW_HEIGHT);

$x = LEFT_MARGIN + PRODUCT_TABLE_LEFT_MARGIN;
$pos = ($pos-PRODUCT_TABLE_ROW_HEIGHT) + PRODUCT_TABLE_BOTTOM_MARGIN;

change_color(GENERAL_FONT_COLOR);
$truncated_str = $pdf->addTextWrap($x,$pos,PRODUCTS_COLUMN_SIZE,TABLE_HEADER_FONT_SIZE,$prod_str);
$pdf->addText($x += PRODUCTS_COLUMN_SIZE,$pos,TABLE_HEADER_FONT_SIZE,$order->products[$i]['model']);
$pdf->addText($x += MODEL_COLUMN_SIZE,$pos,TABLE_HEADER_FONT_SIZE,$order->products[$i]['tax']);
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,$currencies->format($order->products[$i]['final_price'], true, $order->info['currency'], $order->info['currency_value']));
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,$currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']));
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,$currencies->format($order->products[$i]['final_price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']));
$pdf->addText($x += PRICING_COLUMN_SIZES,$pos,TABLE_HEADER_FONT_SIZE,$currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']));
$pos -= PRODUCT_TABLE_BOTTOM_MARGIN;
if ($truncated_str) { 
	
	change_color(PRODUCT_LISTING_BKGD_COLOR);
	$pdf->filledRectangle(LEFT_MARGIN,$pos-PRODUCT_TABLE_ROW_HEIGHT,PRODUCT_TABLE_HEADER_WIDTH,PRODUCT_TABLE_ROW_HEIGHT);
	$pos = ($pos-PRODUCT_TABLE_ROW_HEIGHT) + PRODUCT_TABLE_BOTTOM_MARGIN;
	change_color(GENERAL_FONT_COLOR);
	$reset_x = LEFT_MARGIN + PRODUCT_TABLE_LEFT_MARGIN;
	$pdf->addText($reset_x,$pos,TABLE_HEADER_FONT_SIZE,$truncated_str);
	$pos -= PRODUCT_TABLE_BOTTOM_MARGIN;
	
	}
	
	if ( ($k = sizeof($order->products[$i]['attributes'])) > 0) {
        for ($j = 0; $j < $k; $j++) {
		$attrib_string = '<i> - ' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'];
		if ($order->products[$i]['attributes'][$j]['price'] != '0') { 
		$attrib_string .= ' (' . $order->products[$i]['attributes'][$j]['prefix'] . 
		$currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ')'; 
		
		}
		
		$attrib_string .= '</i>';
		change_color(PRODUCT_LISTING_BKGD_COLOR);
		$pdf->filledRectangle(LEFT_MARGIN,$pos-PRODUCT_TABLE_ROW_HEIGHT,PRODUCT_TABLE_HEADER_WIDTH,PRODUCT_TABLE_ROW_HEIGHT);
		$pos = ($pos-PRODUCT_TABLE_ROW_HEIGHT) + PRODUCT_TABLE_BOTTOM_MARGIN;
		change_color(GENERAL_FONT_COLOR);
		$reset_x = LEFT_MARGIN + PRODUCT_TABLE_LEFT_MARGIN;
		if (PRODUCT_ATTRIBUTES_TEXT_WRAP) {
		$wrapped_str = $pdf->addTextWrap($reset_x,$pos,PRODUCTS_COLUMN_SIZE,PRODUCT_ATTRIBUTES_FONT_SIZE,$attrib_string);
		} else { 
		$pdf->addText($reset_x,$pos,PRODUCT_ATTRIBUTES_FONT_SIZE,$attrib_string);
		}
		$pos -= PRODUCT_TABLE_BOTTOM_MARGIN;
	  			
					if ($wrapped_str) { 
					change_color(PRODUCT_LISTING_BKGD_COLOR);
					$pdf->filledRectangle(LEFT_MARGIN,$pos-PRODUCT_TABLE_ROW_HEIGHT,PRODUCT_TABLE_HEADER_WIDTH,PRODUCT_TABLE_ROW_HEIGHT);
					$pos = ($pos-PRODUCT_TABLE_ROW_HEIGHT) + PRODUCT_TABLE_BOTTOM_MARGIN;
					change_color(GENERAL_FONT_COLOR);
					$pdf->addText($reset_x,$pos,PRODUCT_ATTRIBUTES_FONT_SIZE,$wrapped_str);
					$pos -= PRODUCT_TABLE_BOTTOM_MARGIN;
					}
				}
			}
  } //EOFOR

$pos -= PRODUCT_TABLE_BOTTOM_MARGIN;
	
	for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
	
$pdf->addText(LEFT_MARGIN + 200,$pos -= PRODUCT_TOTALS_LEADING,PRODUCT_TOTALS_FONT_SIZE,"<b>" . $order->totals[$i]['title'] . "</b>");
$pdf->addText($x,$pos,PRODUCT_TOTALS_FONT_SIZE,$order->totals[$i]['text']);
		
		} //EOFOR

$pos -= SECTION_DIVIDER;
if ($orders['comments']) {
$pdf->ezSetY($pos);
$pdf->ezText("<b>Comments:</b>\n" . $orders['comments'],GENERAL_FONT_SIZE);
}

if ($HTTP_POST_VARS['status'] && ($HTTP_POST_VARS['status'] != $order->info['orders_status'])){
$customer_notified = 0; 
$status = tep_db_prepare_input($HTTP_POST_VARS['status']);
$notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, BATCH_COMMENTS) . "\n\n";

if ($HTTP_POST_VARS['notify']) {
$status_query = tep_db_query("select orders_status_name as name from " . TABLE_ORDERS_STATUS . " where language_id = '" . $languages_id . "' and orders_status_id = " . tep_db_input($status));
$status_name = tep_db_fetch_array($status_query);

$email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $orders['orders_id'] . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $orders['orders_id'], 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($order->info['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $status_name['name']);
tep_mail($order->customer['name'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, nl2br($email), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
          $customer_notified = '1';
}

tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . $orders['orders_id'] . "'");
tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) 
values ('" . $orders['orders_id'] . "', '" . tep_db_input($status) . "', now(), '" . $customer_notified . "', '" . $notify_comments  . "')");
}
$num++;
	// Send fake header to avoid timeout, got this trick from phpMyAdmin
		$time1  = time();
        if ($time1 >= $time0 + 30) {
            $time0 = $time1;
            header('X-bpPing: Pong');
			}
}// EOWHILE

$pdf_code = $pdf->output();

$fname = BATCH_PDF_DIR . BATCH_PDF_FILE;
if ($fp = fopen($fname,'w')) {
fwrite($fp,$pdf_code);
fclose($fp);
} else { message_handler('FAILED_TO_OPEN'); }

$message =  'A PDF of ' . $num . ' order(s) was successful! 
<a href="'.$fname.'"><b>Click here</b></a> to download the order file.';

case 0:

require(BATCH_PRINT_INC . 'batch_print_header.php');
require(BATCH_PRINT_INC . 'batch_print_body.php');
require(BATCH_PRINT_INC . 'batch_print_footer.php');

break;
default:

message_handler();

}//EOSWITCH


} else {

message_handler('ERROR_INVALID_INPUT');

}

// FUNCTION AREA
function message_handler($message=''){

if ($message) {
header("Location: " . tep_href_link(BATCH_PRINT_FILE, 'mkey=' . $message));
} else {
header("Location: " . tep_href_link(BATCH_PRINT_FILE));
}
exit(0);
}

function change_color($color) {
global $pdf;

list($r,$g,$b) = explode(',', $color);
$pdf->setColor($r,$g,$b);
}

function verify_start_date($startdate) {
$error = 0;
list($year,$month,$day) = explode('-', $startdate);

if ((strlen($year) != 4) || !is_numeric($year)) {
$error++;
}
if ((strlen($month) != 2) || !is_numeric($month)) {
$error++;
}
if ((strlen($day) != 2) || !is_numeric($day)) {
$error++;
}

return $error;

}


function verify_end_date($enddate) {
$error = 0;
list($year,$month,$day) = explode('-', $enddate);

if ((strlen($year) != 4) || !is_numeric($year)) {
$error++;
}
if ((strlen($month) != 2) || !is_numeric($month)) {
$error++;
}
if ((strlen($day) != 2) || !is_numeric($day)) {
$error++;
}

return $error;

}
?>