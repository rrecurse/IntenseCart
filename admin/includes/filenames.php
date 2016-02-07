<?php


// # define the filenames used in the project
  define('FILENAME_BACKUP', 'backup.php');
  define('FILENAME_BANNER_MANAGER', 'banner_manager.php');
  define('FILENAME_BANNER_STATISTICS', 'banner_statistics.php');
  define('FILENAME_CACHE', 'cache.php');
  define('FILENAME_CATALOG_ACCOUNT_HISTORY_INFO', 'account_history_info.php');
  define('FILENAME_CATEGORIES', 'categories.php');
  define('FILENAME_CONFIGURATION', 'configuration.php');
  define('FILENAME_COUNTRIES', 'countries.php');
  define('FILENAME_CURRENCIES', 'currencies.php');
  define('FILENAME_CUSTOMERS', 'customers.php');
  define('FILENAME_DEFAULT', 'index.php');
  define('FILENAME_DEFINE_LANGUAGE', 'define_language.php');
  define('FILENAME_FILE_MANAGER', 'file_manager.php');
  define('FILENAME_GEO_ZONES', 'geo_zones.php');
  define('FILENAME_LANGUAGES', 'languages.php');
  define('FILENAME_MAIL', 'mail.php');
  define('FILENAME_MANUFACTURERS', 'manufacturers.php');
  define('FILENAME_MODULES', 'modules.php');
  define('FILENAME_NEWSLETTERS', 'newsletters.php');
  define('FILENAME_ORDERS', 'orders.php');
  define('FILENAME_ORDERS_VIEW', 'orders_view.php');
  define('FILENAME_ORDERS_INVOICE', 'invoice.php');
  define('FILENAME_ORDERS_PACKINGSLIP', 'packingslip.php');
  define('FILENAME_ORDERS_STATUS', 'orders_status.php');
  define('FILENAME_POPUP_IMAGE', 'popup_image.php');
  define('FILENAME_PRODUCTS_ATTRIBUTES', 'products_attributes.php');
  define('FILENAME_PRODUCTS_EXPECTED', 'products_expected.php');
  define('FILENAME_REVIEWS', 'reviews.php');
  define('FILENAME_SERVER_INFO', 'server_info.php');
  define('FILENAME_SHIPPING_MODULES', 'shipping_modules.php');
  define('FILENAME_SPECIALS', 'specials.php');
  define('FILENAME_STATS_CUSTOMERS', 'stats_customers.php');
  define('FILENAME_STATS_PRODUCTS_PURCHASED', 'stats_products_purchased.php');
  define('FILENAME_STATS_PRODUCTS_VIEWED', 'stats_products_viewed.php');
  define('FILENAME_TAX_CLASSES', 'tax_classes.php');
  define('FILENAME_TAX_RATES', 'tax_rates.php');
  define('FILENAME_WHOS_ONLINE', 'whos_online.php');
  define('FILENAME_ZONES', 'zones.php');
  define('FILENAME_BATCH_PRINT', 'batch_print.php'); // Batch Order Center [1235]
  define('FILENAME_XSELL_PRODUCTS', 'xsell.php');
  
  define('FILENAME_FEATURED', 'featured.php');
  define('FILENAME_FEATURED_PRODUCTS', 'featured_products.php');
  
// BOF: MaxiDVD - Information pages unlimited, MainPage
define('FILENAME_INFORMATION_MANAGER', 'information_manager.php');
// EOF: MaxiDVD - Information pages unlimited, MainPage

  define('FILENAME_CATALOG_TRACKING_NUMBER', 'tracking.php');
define('FILENAME_HEADER_TAGS_CONTROLLER', 'header_tags_controller.php');
define('FILENAME_HEADER_TAGS_ENGLISH', 'header_tags_english.php');
define('FILENAME_HEADER_TAGS_FILL_TAGS', 'header_tags_fill_tags.php');
define('FILENAME_HEADER_TAGS_INCLUDES', 'header_tags_includes.php');

// Easy polulate //
define('FILENAME_IMP_EXP_CATALOG', 'easypopulate.php'); 
// END
define('FILENAME_STATS_SALES_REPORT', 'stats_sales_report.php');
define('FILENAME_STATS_SALES', 'stats_sales.php');
  define('FILENAME_REFERRALS', 'referrals.php'); //rmh referrals
  define('FILENAME_STATS_REFERRAL_SOURCES', 'stats_referral_sources.php'); //rmh referrals
define('FILENAME_STATS_AD_RESULTS', 'stats_ad_results.php');
//++++ QT Pro: Begin Changed code
  define('FILENAME_STATS_LOW_STOCK_ATTRIB', 'stats_low_stock_attrib.php');
  define('FILENAME_STOCK', 'stock.php');
//++++ QT Pro: End Changed Code
// ################# Contribution Newsletter v050 ##############
  define('FILENAME_NEWSLETTERS_SUBSCRIBERS', 'newsletters_subscribers.php');
  define('FILENAME_NEWSLETTERS_UNSUBSCRIBE', 'newsletters_unsubscribe.php');
  define('FILENAME_NEWSLETTERS_EXTRA_INFOS', 'newsletters_extra_infos.php');
  define('FILENAME_NEWSLETTERS_UPDATE', 'newsletters_update.php');	
  define('FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW', 'newsletters_subscribers_view.php');	
  define('FILENAME_POPUP_HELP', 'popup_help.php');	
  define('FILENAME_MAILS', 'mails.php');		
  define('FILENAME_NEWSLETTERS_EXTRA_DEFAULT', 'newsletters_extra_default.php');
  define('FILENAME_UNSUBSCRIBE', 'unsubscribe.php');
// ################# END - Contribution Newsletter v050 ##############
// START PDF catalog [908]
  define('FILENAME_PDF_CATALOGUE', 'pdf_catalogue.php'); // PDF Catalog
  define('FILENAME_PDF_DEFINE_INTRO', 'pdf_define_intro.php'); // PDF Catalog
  define('FILENAME_PDF_LINK', '../catalogues/catalog_en.pdf'); // PDF Catalog v. 1.58
// END PDF catalog
// ########## START Manual Order Entry ##########
// Create Order & customers
  define('FILENAME_CREATE_ACCOUNT', 'create_account.php');
  define('FILENAME_CREATE_ACCOUNT_PROCESS', 'create_account_process.php');
  define('FILENAME_CREATE_ACCOUNT_SUCCESS', 'create_account_success.php');
  define('FILENAME_CREATE_ORDER_PROCESS', 'create_order_process.php');
  define('FILENAME_CREATE_ORDER', 'create_order.php');
  define('FILENAME_EDIT_ORDERS', 'edit_orders.php'); 
// ########## END - Manual Order Entry ##########
  define('FILENAME_QBI', 'qbi_create.php'); // ADDED for QBI [2847]

// ###### Marketing Panel ##### //

define('FILENAME_MARKETING', 'marketing.php');
define('FILENAME_PAYMENT', 'payment.php');
define('FILENAME_SHIPPING', 'shipping.php');

// ###### END - Marketing Panel ##### //

define('FILENAME_KEYWORDS', 'stats_keywords.php');

// BOF Separate Pricing - added by MegaJim
define('FILENAME_CUSTOMERS_GROUPS','customers_groups.php');
//EOF Separate Pricing

// supply_request start

	define('FILENAME_SUPPLY_REQUEST', 'supply_request.php');
	define('FILENAME_SUPPLY_REQUEST_INVOICE', 'supply_request_invoice.php');
 	define('FILENAME_SUPPLY_REQUEST_PACKINGSLIP', 'supply_request_packingslip.php');
	define('FILENAME_SUPPLY_REQUEST_STATUS', 'supply_request_status.php');
	define('FILENAME_EDIT_SUPPLY_REQUEST', 'edit_supply_request.php'); 
	define('FILENAME_CREATE_SUPPLY_REQUEST', 'create_supply_request.php');
	define('FILENAME_CREATE_SUPPLY_REQUEST_PROCESS', 'create_supply_request_process.php');

	define('FILENAME_SUPPLIER', 'supplier.php');
	define('FILENAME_CREATE_SUPPLIER', 'create_supplier.php');
	define('FILENAME_CREATE_SUPPLIER_PROCESS', 'create_supplier_process.php');
	define('FILENAME_CREATE_SUPPLIER_SUCCESS', 'create_supplier_success.php');
	//define('FILENAME_SUPPLIER','supplier_area.php');
  
	define('FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS','supplier_s_categories_products.php');
  
	define('FILENAME_SUPPLIER_STATISTIC','supplier_s_statistic.php');
	define('FILENAME_SUPPLIERS', 'suppliers.php');

	// supply_request end

	// # warehouse manager
	define('FILENAME_WAREHOUSE_MANAGER', 'warehouse_manager.php');
	// # end warehouse manager

	// # Sales / Customer Report  
	define('FILENAME_STATS_AVERAGE', 'stats_average.php');

	define('FILENAME_STATS_PRODUCTS_BACKORDERED', 'stats_products_backordered.php');
	// # RMA Returns System
  define('FILENAME_RETURNS', 'returns.php');
  define('FILENAME_RETURN', 'return_product.php');
  define('FILENAME_RETURN_CONFIRM', 'return_confirm.php');
  define('FILENAME_RETURN_EMAILS', 'return_emails.php');
  define('FILENAME_RETURNS_REASONS', 'returns_reasons.php');
  define('FILENAME_RETURNS_TEXT', 'return_text.php');
  define('FILENAME_RETURNS_STATUS', 'returns_status.php');
  define('FILENAME_REFUND_METHODS', 'refund_methods.php');
  define('FILENAME_RETURNS_INVOICE', 'returns_invoice.php');

// # MultiAdmin
  define('FILENAME_HTPASSWD', '.htpasswd');
  define('FILENAME_PROG_HTPASSWD', '/usr/bin/htpasswd');
  define('FILENAME_ADMINS', 'admins.php');

  define('FILENAME_COMMENT_BAR', 'comment_bar.php');

  //+++AUCTIONBLOX.COM
  define('FILENAME_AUCTION_SALES', 'auction_sales.php');
  define('FILENAME_AUCTION_SALE_ITEM', 'auction_sale_item.php');
  define('FILENAME_AUCTION_LISTING', 'auction_listing.php');
  define('FILENAME_AUCTION_PRODUCT_MAPPING', 'auction_product_mapping.php');
  define('FILENAME_AUCTION_CHECKOUT_BUTTON', 'auction_checkout_button.php');
  define('FILENAME_AUCTION_PRODUCT_MAPPING_ITEM', 'auction_product_mapping_item.php');
  define('FILENAME_AUCTION_TEST_MAPPING', 'auction_test_mapping.php');
  //+++AUCTIONBLOX.COM

  define('FILENAME_CONFIG_CACHE',DIR_FS_CACHE.'config_cache.php');
  define('FILENAME_CLEAR_DB','clear_db.php');


?>
