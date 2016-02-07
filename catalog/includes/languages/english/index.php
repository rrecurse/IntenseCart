<?php

define('TEXT_MAIN', 'This is a default setup of the IntenseCart project, products shown are for demonstrational purposes, <b>any products purchased will not be delivered nor will the customer be billed</b>. Any information seen on these products is to be treated as fictional.<br><br><table border="0" width="100%" cellspacing="5" cellpadding="2"><tr><td class="main" valign="top"></td><td class="main" valign="top"><b>Error Messages</b><br><br>If there are any error or warning messages shown above, please correct them first before proceeding.<br><br>Error messages are displayed at the very top of the page with a complete <span class="messageStackError">background</span> color.<br><br>Several checks are performed to ensure a healthy setup of your online store - these checks can be disabled by editing the appropriate parameters at the bottom of the includes/application_top.php file.</td></tr><td class="main" valign="top"></td><td class="main" valign="top"><b>Editing Page Texts</b><br><br>The text shown here can be modified in the following file, on each language basis:<br><br><nobr class="messageStackSuccess">[path to catalog]/includes/languages/' . $language . '/' . FILENAME_DEFAULT . '</nobr><br><br>That file can be edited manually, or via the Administration Tool with the <nobr class="messageStackSuccess">Languages->' . ucfirst($language) . '->Define</nobr> or <nobr class="messageStackSuccess">Tools->File Manager</nobr> modules.<br><br>The text is set in the following manner:<br><br><nobr>define(\'TEXT_MAIN\', \'<span class="messageStackSuccess">This is a default setup of the IntenseCart project...</span>\');</nobr><br><br>The text highlighted in green may be modified - it is important to keep the define() of the TEXT_MAIN keyword. To remove the text for TEXT_MAIN completely, the following example is used where only two single quote characters exist:<br><br><nobr>define(\'TEXT_MAIN\', \'\');</nobr><br><br>More information concerning the PHP define() function can be read <a href="http://www.php.net/define" target="_blank"><u>here</u></a>.</td></tr><tr><td class="main" valign="top"></td><td class="main" valign="top"><b>Securing The Administration Tool</b><br><br>It is important to secure the Administration Tool as there is currently no security implementation available.</td></tr><tr><td class="main" valign="top"></td><td class="main" valign="top"><b>Online Documentation</b><br><br>Online documentation can be read at the <a href="http://wiki.intensecart.com" target="_blank"><u>IntenseCart Wiki Documentation Effort</u></a> site.<br><br>Community support is available at the <a href="http://forums.IntenseCart.com" target="_blank"><u>IntenseCart Community Support Forums</u></a> site.</td></tr></table><br>If you wish to download the solution powering this shop, or if you wish to contribute to the IntenseCart project, please visit the <a href="http://www.IntenseCart.com" target="_blank"><u>support site of IntenseCart</u></a>. This shop is running on IntenseCart version <font color="#f0000"><b>' . PROJECT_VERSION . '</b></font>.');
define('TABLE_HEADING_NEW_PRODUCTS', 'New Products For %s');
define('TABLE_HEADING_UPCOMING_PRODUCTS', 'Upcoming Products');
define('TABLE_HEADING_DATE_EXPECTED', 'Date Expected');

define('TEXT_PRODUCT_NOT_FOUND', 'Product not found!'); 
define('TEXT_CURRENT_REVIEWS', 'Current Reviews:'); 
define('TEXT_MORE_INFORMATION', 'For more information, please visit this products <a href="%s" target="_blank"><u>webpage</u></a>.'); 
define('TEXT_DATE_ADDED', 'This product was added to our catalog on %s.'); 
define('TEXT_DATE_AVAILABLE', '<font color="#ff0000">This product will be in stock on %s.</font>'); 
define('TEXT_ALSO_PURCHASED_PRODUCTS', 'Customers who bought this product also purchased'); 
define('TEXT_PRODUCT_OPTIONS', 'Available Options:'); 
define('TEXT_CLICK_TO_ENLARGE', 'Click to enlarge');
   
if ( ($category_depth == 'products') || (isset($HTTP_GET_VARS['manufacturers_id'])) ) {
  define('HEADING_TITLE', '');
  define('TABLE_HEADING_IMAGE', '');
  define('TABLE_HEADING_MODEL', 'Model');
  define('TABLE_HEADING_PRODUCTS', 'Product Name');
  define('TABLE_HEADING_INFO', 'Short Description');
  define('TABLE_HEADING_MANUFACTURER', 'Manufacturer');
  define('TABLE_HEADING_QUANTITY', 'Quantity');
  define('TABLE_HEADING_PRICE', 'Price');
  define('TABLE_HEADING_WEIGHT', 'Weight');
  define('TABLE_HEADING_BUY_NOW', 'Buy Now');
  define('TEXT_NO_PRODUCTS', 'There are no products to list in this category.');
  define('TEXT_NO_PRODUCTS2', 'There is no product available from this manufacturer.');
  define('TEXT_NUMBER_OF_PRODUCTS', 'Number of Products: ');
  define('TEXT_SHOW', '<b>Show:</b>');
  define('TEXT_BUY', 'Buy 1 \'');
  define('TEXT_NOW', '\' now');
  define('TEXT_ALL_CATEGORIES', 'All Categories');
  define('TEXT_ALL_MANUFACTURERS', 'All Manufacturers');
} elseif ($category_depth == 'top') {
  define('HEADING_TITLE', 'What\'s New Here?');
} elseif ($category_depth == 'nested') {
  define('HEADING_TITLE', 'Categories');
}

define('TABLE_HEADING_FEATURED_PRODUCTS', 'Featured Products');
define('TABLE_HEADING_FEATURED_PRODUCTS_CATEGORY', 'Featured Products in %s');
define('TABLE_HEADING_BEST_SELLERS', 'Best Sellers'); 
define('TABLE_HEADING_MULTIPLE', 'Qty.');
?>
