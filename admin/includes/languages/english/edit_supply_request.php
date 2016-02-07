<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


define('HEADING_TITLE', 'Edit Supply Request');
define('HEADING_TITLE_NUMBER', 'P/O: ');
define('HEADING_TITLE_DATE', 'of');
define('HEADING_SUBTITLE', 'Please edit all parts as desired and click on the "Update" button below.');
define('HEADING_TITLE_SEARCH', 'Supply Request ID:');
define('HEADING_TITLE_STATUS', 'Status:');
define('ADDING_TITLE', 'Add a product to this request');

define('HINT_UPDATE_TO_CC', '<font color="#FF0000">Hint: </font>Set payment to "Credit Card" to show some additional fields.');
define('HINT_DELETE_POSITION', '<font color="#FF0000">Note:</font> To delete a product set its quantity to "0".');
define('HINT_TOTALS', '<font color="#FF0000">Note:</font>&nbsp; Discounts require you to use a negative amount (using a minus sign) e.g. -50.00.');
define('HINT_PRESS_UPDATE', 'Please click on "Update" to save all changes made above.');

define('TABLE_HEADING_COMMENTS', 'Comment');
define('TABLE_HEADING_CUSTOMERS', 'Suppliers');
define('TABLE_HEADING_ORDER_TOTAL', 'Total');
define('TABLE_HEADING_DATE_PURCHASED', 'Request date');
define('TABLE_HEADING_STATUS', 'New Status');
define('TABLE_HEADING_ACTION', 'Action');
define('TABLE_HEADING_QUANTITY', 'Qty');
define('TABLE_HEADING_PRODUCTS_MODEL', 'Model.');
define('TABLE_HEADING_PRODUCTS', 'Products');
define('TABLE_HEADING_TAX', 'Tax');
define('TABLE_HEADING_TOTAL', 'Total');
define('TABLE_HEADING_UNIT_COST', 'Cost (excl.)');
define('TABLE_HEADING_UNIT_COST_TAXED', 'Cost (incl.)');
define('TABLE_HEADING_TOTAL_COST', 'Total');
define('TABLE_HEADING_TOTAL_MODULE', 'Charge Type');
define('TABLE_HEADING_TOTAL_AMOUNT', 'Amount');

define('TABLE_HEADING_CUSTOMER_NOTIFIED', 'Supplier notified');
define('TABLE_HEADING_DATE_ADDED', 'Entry date');

define('ENTRY_SUPPLIER', 'Supplier general');
define('ENTRY_SUPPLIER_NAME', 'Name');
define('ENTRY_SUPPLIER_COMPANY', 'Company');
define('ENTRY_SUPPLIER_ADDRESS', 'Address');
define('ENTRY_SUPPLIER_ADDRESS2', 'Department / Floor');
define('ENTRY_SUPPLIER_CITY', 'City');
define('ENTRY_SUPPLIER_STATE', 'State');
define('ENTRY_SUPPLIER_POSTCODE', 'Postcode');
define('ENTRY_SUPPLIER_COUNTRY', 'Country');
define('ENTRY_SUPPLIER_PHONE', 'Phone');
define('ENTRY_SUPPLIER_EMAIL', 'E-Mail');

define('ENTRY_SOLD_TO', 'Sold to:');
define('ENTRY_DELIVERY_TO', 'Delivery to:');
define('ENTRY_SHIP_TO', 'Shipping to:');
define('ENTRY_SHIPPING_ADDRESS', 'Shipping To:');
define('ENTRY_SHIPFROM_ADDRESS', 'Ship From:');
define('ENTRY_PAYMENT_METHOD', 'Payment Method:');
define('ENTRY_CREDIT_CARD_TYPE', 'Card Type:');
define('ENTRY_CREDIT_CARD_OWNER', 'Card Owner:');
define('ENTRY_CREDIT_CARD_NUMBER', 'Card Number:');
define('ENTRY_CREDIT_CARD_EXPIRES', 'Card Expires:');
define('ENTRY_SUB_TOTAL', 'Sub Total:');
define('ENTRY_TAX', 'Tax:');
define('ENTRY_SHIPPING', 'Shipping:');
define('ENTRY_TOTAL', 'Total:');
define('ENTRY_DATE_PURCHASED', 'Date Requested:');
define('ENTRY_STATUS', 'Supply Request Status:');
define('ENTRY_DATE_LAST_UPDATED', 'last updated:');
define('ENTRY_NOTIFY_SUPPLIER', 'Send comments to supplier?');
define('ENTRY_NOTIFY_COMMENTS', 'Send comments:');
define('ENTRY_PRINTABLE', 'Print Invoice');

define('TEXT_INFO_HEADING_DELETE_ORDER', 'Delete Supply Request');
define('TEXT_INFO_DELETE_INTRO', 'Shall this supply really be deleted?');
define('TEXT_INFO_RESTOCK_PRODUCT_QUANTITY', 'Adjust Quantity');
define('TEXT_DATE_ORDER_CREATED', 'Created:');
define('TEXT_DATE_ORDER_LAST_MODIFIED', 'Last Update:');
define('TEXT_DATE_ORDER_ADDNEW', 'Add New Product');
define('TEXT_INFO_PAYMENT_METHOD', 'Payment Method:');

define('TEXT_ALL_SUPPLY_REQUESTS', 'All Requests');
define('TEXT_NO_SUPPLY_REQUEST_HISTORY', 'No history found');

define('EMAIL_SEPARATOR', '------------------------------------------------------');
define('EMAIL_TEXT_SUBJECT', 'Your supply request has been updated');
define('EMAIL_TEXT_ORDER_NUMBER', 'Supply Request number:');
define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice URL:');
define('EMAIL_TEXT_DATE_ORDERED', 'Request date:');
define('EMAIL_TEXT_STATUS_UPDATE', 'The status of your supply request has been updated.' . "\n\n" . 'New status: %s' . "\n\n" . 'If you have questions, please reply to this email.' . "\n\n" . 'Kind regards,' . "\n". 'Your Onlineshop-Team' . "\n");
define('EMAIL_TEXT_COMMENTS_UPDATE', 'Comments' . "\n\n%s\n\n");

define('ERROR_SUPPLY_REQUEST_DOES_NOT_EXIST', 'Error: Supply request %s not found.');
define('SUCCESS_SUPPLY_REQUEST_UPDATED', 'Completed: Supply request has been successfully updated.');
define('WARNING_SUPPLY_REQUEST_NOT_UPDATED', 'Attention: No changes have been made.');

define('ADDPRODUCT_TEXT_CATEGORY_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_SELECT_PRODUCT', 'Choose a product');
define('ADDPRODUCT_TEXT_PRODUCT_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_SELECT_OPTIONS', 'Choose an option');
define('ADDPRODUCT_TEXT_OPTIONS_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_OPTIONS_NOTEXIST', 'Product has no options, so skipping...');
define('ADDPRODUCT_TEXT_CONFIRM_QUANTITY', 'pieces of this product');
define('ADDPRODUCT_TEXT_CONFIRM_ADDNOW', 'Add');
define('ADDPRODUCT_TEXT_STEP', 'Step');
define('ADDPRODUCT_TEXT_STEP1', ' &laquo; Choose a category. ');
define('ADDPRODUCT_TEXT_STEP2', ' &laquo; Choose a product. ');
define('ADDPRODUCT_TEXT_STEP3', ' &laquo; Choose an option. ');

define('MENUE_TITLE_CUSTOMER', '1. Supplier Data');
define('MENUE_TITLE_PAYMENT', '2. Payment Method');
define('MENUE_TITLE_ORDER', '3. Requested Products');
define('MENUE_TITLE_TOTAL', '4. Discount, Shipping and Total');
define('MENUE_TITLE_STATUS', '5. Status and Notification');
define('MENUE_TITLE_UPDATE', '6. Update Data');
?>
