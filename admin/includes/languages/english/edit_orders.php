<?php

define('HEADING_TITLE', 'Edit Order');
define('HEADING_TITLE_NUMBER', '#');
define('HEADING_TITLE_DATE', 'of');
define('HEADING_SUBTITLE', 'Please edit all parts as desired and click on the "Update" button below.');
define('HEADING_TITLE_SEARCH', 'Order ID:');
define('HEADING_TITLE_STATUS', 'Status:');
define('ADDING_TITLE', 'Add a product to this order');

define('HINT_UPDATE_TO_CC', '<font color="#FF0000">Hint: </font>Set payment to "Credit Card" to show some additional fields.');
define('HINT_DELETE_POSITION', '<font color="#FF0000">Hint: </font>To delete a product set its quantity to "0".');
define('HINT_TOTALS', '<font color="#FF0000">Hint: </font>Feel free to give discounts by adding negative amounts to the list.<br>Fields with "0" values are deleted when updating the order (exception: shipping).');
define('HINT_PRESS_UPDATE', 'Please click on "Update" to save all changes made above.');

define('TABLE_HEADING_COMMENTS', 'Comment');
define('TABLE_HEADING_CUSTOMERS', 'Customers');
define('TABLE_HEADING_ORDER_TOTAL', 'Orders total');
define('TABLE_HEADING_DATE_PURCHASED', 'Order date');
define('TABLE_HEADING_STATUS', 'New Status');
define('TABLE_HEADING_ACTION', 'Action');
define('TABLE_HEADING_QUANTITY', 'Qty');
define('TABLE_HEADING_PRODUCTS_MODEL', 'Model');
define('TABLE_HEADING_PRODUCTS', 'Products');
define('TABLE_HEADING_TAX', 'Tax %');
define('TABLE_HEADING_TOTAL', 'Total');
define('TABLE_HEADING_UNIT_PRICE', 'Unit Price');
define('TABLE_HEADING_UNIT_PRICE_TAXED', 'Unit Price');
define('TABLE_HEADING_TOTAL_PRICE', 'Total (excl.)');
define('TABLE_HEADING_TOTAL_PRICE_TAXED', 'Total');
define('TABLE_HEADING_TOTAL_MODULE', 'Total Price Component');
define('TABLE_HEADING_TOTAL_AMOUNT', 'Amount');
define('TABLE_HEADING_FREE_SHIPPING', 'Free Ship');

define('TABLE_HEADING_CUSTOMER_NOTIFIED', 'Customer notified');
define('TABLE_HEADING_DATE_ADDED', 'Entry date');

define('ENTRY_CUSTOMER', 'Customer general');
define('ENTRY_CUSTOMER_NAME', 'Name');
define('ENTRY_CUSTOMER_COMPANY', 'Company');
define('ENTRY_CUSTOMER_ADDRESS', 'Address');
define('ENTRY_CUSTOMER_SUBURB', 'Apt. / Suite / Floor');
define('ENTRY_CUSTOMER_CITY', 'City');
define('ENTRY_CUSTOMER_STATE', 'State');
define('ENTRY_CUSTOMER_POSTCODE', 'Postcode');
define('ENTRY_CUSTOMER_COUNTRY', 'Country');
define('ENTRY_CUSTOMER_PHONE', 'Phone');
define('ENTRY_CUSTOMER_EMAIL', 'E-Mail');

define('ENTRY_SOLD_TO', 'Sold to:');
define('ENTRY_DELIVERY_TO', 'Delivery to:');
define('ENTRY_SHIP_TO', 'Shipping to:');
define('ENTRY_SHIPPING_ADDRESS', 'Shipping Address');
define('ENTRY_BILLING_ADDRESS', 'Billing Address');
define('ENTRY_PAYMENT_METHOD', 'Payment Method:');
define('ENTRY_CURRENCY', 'Order Currency:');
define('ENTRY_CREDIT_CARD_TYPE', 'Card Type:');
define('ENTRY_CREDIT_CARD_OWNER', 'Card Owner:');
define('ENTRY_CREDIT_CARD_NUMBER', 'Card Number:');
define('ENTRY_CREDIT_CARD_EXPIRES', 'Card Expires:');
define('ENTRY_CREDIT_CARD_CVV2', 'CVV2 Security Code:');
define('ENTRY_SUB_TOTAL', 'Sub Total:');
define('ENTRY_TAX', 'Tax:');
define('ENTRY_SHIPPING', 'Shipping:');
define('ENTRY_TOTAL', 'Total:');
define('ENTRY_DATE_PURCHASED', 'Date Purchased:');
define('ENTRY_STATUS', 'Order Status:');
define('ENTRY_DATE_LAST_UPDATED', 'last updated:');
define('ENTRY_NOTIFY_CUSTOMER', 'Notify customer:');
define('ENTRY_NOTIFY_COMMENTS', 'Send comments:');
define('ENTRY_PRINTABLE', 'Print Invoice');
define('ENTRY_SEND_CONFIRMATION','Send Confirmation');

define('TEXT_INFO_HEADING_DELETE_ORDER', 'Delete Order');
define('TEXT_INFO_DELETE_INTRO', 'Do you want to delete this order permanently?');
define('TEXT_INFO_RESTOCK_PRODUCT_QUANTITY', 'Adjust Quantity');
define('TEXT_DATE_ORDER_CREATED', 'Created:');
define('TEXT_DATE_ORDER_LAST_MODIFIED', 'Last Update:');
define('TEXT_DATE_ORDER_ADDNEW', 'Add New Product');
define('TEXT_INFO_PAYMENT_METHOD', 'Payment Method:');

define('TEXT_ALL_ORDERS', 'All Orders');
define('TEXT_NO_ORDER_HISTORY', 'No order found');

define('EMAIL_SEPARATOR', '------------------------------------------------------');
define('EMAIL_TEXT_SUBJECT', 'Your order has been updated');
define('EMAIL_TEXT_SUBJECT_1', STORE_NAME . '  -- Your order has been received!');
define('EMAIL_TEXT_SUBJECT_ADMIN', 'Order Update Confirmation - Order:  #%s, has been updated.');
define('EMAIL_TEXT_SUBJECT_UPDATE', STORE_NAME . ' - Your order:  #%s, has been updated.');
define('EMAIL_TEXT_STATUS_UPDATE', 'The status of your order has been updated.' . "\n\n" . 'New status: %s' . "\n\n" . 'If you have questions, please reply to this email.' . "\n\n" . 'Kind regards,' . "\n". STORE_NAME . "\n");
define('EMAIL_TEXT_ORDER_NUMBER', 'Order Number: ');
define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice: ');

define('EMAIL_TEXT_DATE_ORDERED', 'Order date: ');
define('EMAIL_TEXT_COMMENTS_UPDATE', 'Comments:' . "\n\n".'%s'."\n\n");

define('EMAIL_TEXT_FOOTER', 'If you have further questions or comments, please reply to this email.'. "\n\n".'Thank you again for shopping with ' . STORE_NAME.'!');


define('ERROR_ORDER_DOES_NOT_EXIST', 'Error: No such order.');
define('SUCCESS_ORDER_UPDATED', 'Completed: Order has been successfully updated.');
define('WARNING_ORDER_NOT_UPDATED', 'Attention: No changes have been made.');

define('ADDPRODUCT_TEXT_CATEGORY_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_SELECT_PRODUCT', 'Choose a product');
define('ADDPRODUCT_TEXT_PRODUCT_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_SELECT_OPTIONS', 'Choose an option');
define('ADDPRODUCT_TEXT_OPTIONS_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_OPTIONS_NOTEXIST', 'Product has no options, so skipping...');
define('ADDPRODUCT_TEXT_CONFIRM_QUANTITY', 'pieces of this product');
define('ADDPRODUCT_TEXT_CONFIRM_ADDNOW', 'Add');
define('ADDPRODUCT_TEXT_STEP', 'Step');
define('ADDPRODUCT_TEXT_STEP1', ' &laquo; Choose a catalog. ');
define('ADDPRODUCT_TEXT_STEP2', ' &laquo; Choose a product. ');
define('ADDPRODUCT_TEXT_STEP3', ' &laquo; Choose an option. ');

define('MENUE_TITLE_CUSTOMER', '1. Customer Data');
define('MENUE_TITLE_PAYMENT', '2. Payment Method');
define('MENUE_TITLE_ORDER', '3. Ordered Products');
define('MENUE_TITLE_TOTAL', '4. Discount, Shipping and Total');
define('MENUE_TITLE_STATUS', '5. Status and Notification');
define('MENUE_TITLE_UPDATE', '6. Update Data');

?>
