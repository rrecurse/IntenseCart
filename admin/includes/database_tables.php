<?php

// define the database table names used in the project
	define('TABLE_ADDRESS_BOOK', 'address_book');
	define('TABLE_ADDRESS_FORMAT', 'address_format');
	define('TABLE_BANNERS', 'banners');
	define('TABLE_BANNERS_HISTORY', 'banners_history');
	define('TABLE_CATEGORIES', 'categories');
	define('TABLE_CATEGORIES_DESCRIPTION', 'categories_description');
	define('TABLE_CONFIGURATION', 'configuration');
	define('TABLE_CONFIGURATION_GROUP', 'IXcore.configuration_group');
	define('TABLE_CONFIGURATION_DATA', 'configuration_data');
	define('TABLE_CORE_CONFIGURATION', 'IXcore.configuration');
	define('TABLE_COUNTRIES', 'countries');
	define('TABLE_CURRENCIES', 'currencies');
	define('TABLE_CUSTOMERS', 'customers');
	define('TABLE_CUSTOMERS_BASKET', 'customers_basket');
	define('TABLE_CUSTOMERS_BASKET_ATTRIBUTES', 'customers_basket_attributes');
	define('TABLE_CUSTOMERS_INFO', 'customers_info');
	define('TABLE_LANGUAGES', 'languages');
	define('TABLE_MANUFACTURERS', 'manufacturers');
	define('TABLE_MANUFACTURERS_INFO', 'manufacturers_info');
	define('TABLE_NEWSLETTERS', 'newsletters');
	define('TABLE_NEWSLETTERS_QUEUE', 'newsletter_queue');
	define('TABLE_ORDERS', 'orders');
	define('TABLE_ORDERS_ITEMS_REFS', 'orders_items_refs');
	define('TABLE_ORDERS_PRODUCTS', 'orders_products');
	define('TABLE_ORDERS_PRODUCTS_ATTRIBUTES', 'orders_products_attributes');
	define('TABLE_ORDERS_PRODUCTS_DOWNLOAD', 'orders_products_download');
	define('TABLE_ORDERS_STATUS', 'orders_status');
	define('TABLE_ORDERS_STATUS_HISTORY', 'orders_status_history');
	define('TABLE_ORDERS_SHIPPED', 'orders_shipped');
	define('TABLE_ORDERS_TOTAL', 'orders_total');
	define('TABLE_PRODUCTS', 'products');
	define('TABLE_PRODUCTS_ATTRIBUTES', 'products_attributes');
	define('TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD', 'products_attributes_download');
	define('TABLE_PRODUCTS_DESCRIPTION', 'products_description');
	define('TABLE_PRODUCTS_NOTIFICATIONS', 'products_notifications');
	define('TABLE_PRODUCTS_OPTIONS', 'products_options');
	define('TABLE_PRODUCTS_OPTIONS_VALUES', 'products_options_values');
	define('TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS', 'products_options_values_to_products_options');
	define('TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS', 'products_options_values_to_products');
	define('TABLE_PRODUCTS_TO_CATEGORIES', 'products_to_categories');
	define('TABLE_PRODUCTS_IMAGES', 'products_images');
	define('TABLE_PRODUCTS_DISCOUNTS', 'products_discount');
	define('TABLE_REVIEWS', 'reviews');
	define('TABLE_REVIEWS_DESCRIPTION', 'reviews_description');
	define('TABLE_SESSIONS', 'sessions');
	define('TABLE_SPECIALS', 'specials');
	define('TABLE_SPECIALS_RETAIL_PRICES', 'specials_retail_prices');
	define('TABLE_TAX_CLASS', 'tax_class');
	define('TABLE_TAX_RATES', 'tax_rates');
	define('TABLE_GEO_ZONES', 'geo_zones');
	define('TABLE_ZONES_TO_GEO_ZONES', 'zones_to_geo_zones');
	define('TABLE_WHOS_ONLINE', 'whos_online');
	define('TABLE_ZONES', 'zones');

	// # Separate Pricing per Customer
	define('TABLE_PRODUCTS_GROUPS', 'products_groups');
	define('TABLE_CUSTOMERS_GROUPS', 'customers_groups');

	//	# Xsell / Cross Sell
	define('TABLE_PRODUCTS_XSELL', 'products_xsell');

	define('TABLE_FEATURED', 'featured');

	define('TABLE_INFORMATION', 'information');


	// # supply_request start
	define('TABLE_SUPPLY_REQUEST', 'supply_request');
	define('TABLE_SUPPLY_REQUEST_PRODUCTS', 'supply_request_products');
	define('TABLE_SUPPLY_REQUEST_PRODUCTS_ATTRIBUTES', 'supply_request_products_attributes');
	define('TABLE_SUPPLY_REQUEST_PRODUCTS_DOWNLOAD', 'supply_request_products_download');
	define('TABLE_SUPPLY_REQUEST_STATUS', 'supply_request_status');
	define('TABLE_SUPPLY_REQUEST_STATUS_HISTORY', 'supply_request_status_history');
	define('TABLE_SUPPLY_REQUEST_TOTAL', 'supply_request_total');

	define('TABLE_SUPPLIERS', 'suppliers');
	define('TABLE_SUPPLIERS_INFO', 'suppliers_info');
	define('TABLE_SUPPLIERS_PRODUCTS_GROUPS', 'suppliers_products_groups');
	define('TABLE_COUNTER', 'counter');
	define('TABLE_CATEGORIES_TO_SUPPLIERS', 'categories_to_suppliers');

	define('TABLE_SUPPLIER', 'supplier');
	define('TABLE_SUPPLIER_BASKET', 'supplier_basket');
	define('TABLE_SUPPLIER_BASKET_ATTRIBUTES', 'supplier_basket_attributes');
	define('TABLE_SUPPLIER_INFO', 'supplier_info');

	// # supply_request end 

	// # warehouse manager
	define('TABLE_PRODUCTS_WAREHOUSE', 'products_warehouse_profiles');
	define('TABLE_PRODUCTS_WAREHOUSE_INVENTORY', 'products_warehouse_inventory');
	// # end warehouse manager

	define('TABLE_WISHLIST', 'customers_wishlist');
	define('TABLE_WISHLIST_ATTRIBUTES', 'customers_wishlist_attributes');

	define('TABLE_SOURCES', 'sources'); // # rmh referrals
	define('TABLE_SOURCES_OTHER', 'sources_other'); // # rmh referrals

	define('TABLE_PRODUCTS_STOCK', 'products_stock');

	define('TABLE_SUBSCRIBERS', 'subscribers');
	define('TABLE_SUBSCRIBERS_DEFAULT', 'subscribers_default');
	define('TABLE_SUBSCRIBERS_UPDATE', 'subscribers_update');
	define('TABLE_SUBSCRIBERS_INFOS', 'subscribers_infos');
	define('TABLE_POPUP_HELP', 'popup_help');

	// # START QBI [2847]
	define('TABLE_QBI_CONFIG', 'qbi_config');
	define('TABLE_QBI_DISC', 'qbi_disc');
	define('TABLE_QBI_GROUPS', 'qbi_groups');
	define('TABLE_QBI_GROUPS_ITEMS', 'qbi_groups_items');
	define('TABLE_QBI_ITEMS', 'qbi_items');
	define('TABLE_QBI_OT', 'qbi_ot');
	define('TABLE_QBI_OT_DISC', 'qbi_ot_disc');
	define('TABLE_QBI_PAYOSC', 'qbi_payosc');
	define('TABLE_QBI_PAYOSC_PAYQB', 'qbi_payosc_payqb');
	define('TABLE_QBI_PAYQB', 'qbi_payqb');
	define('TABLE_QBI_PRODUCTS_ITEMS', 'qbi_products_items');
	define('TABLE_QBI_SHIPOSC', 'qbi_shiposc');
	define('TABLE_QBI_SHIPQB', 'qbi_shipqb');
	define('TABLE_QBI_SHIPOSC_SHIPQB', 'qbi_shiposc_shipqb');
	define('TABLE_QBI_TAXES', 'qbi_taxes');
	// # END QBI

	define('TABLE_RETURN_REASONS', 'return_reasons');
	define('TABLE_RETURNS', 'returned_products');
	define('TABLE_RETURNS_STATUS', 'returns_status');
	define('TABLE_RETURNS_TEXT', 'return_text');
	define('TABLE_RETURNS_TOTAL', 'returns_total');
	define('TABLE_RETURNS_PRODUCTS_DATA', 'returns_products_data');
	define('TABLE_PAYMENT_OPTIONS', 'payment_options');
	define('TABLE_RETURN_PAYMENTS', 'refund_payments');
	define('TABLE_REFUND_METHOD', 'refund_method');
	define('TABLE_RETURNS_STATUS_HISTORY', 'returns_status_history');

// MultiAdmin - by MegaJim
	define('TABLE_ADMIN_PERMISSIONS', 'admin_permissions');
	define('TABLE_ADMIN_FILES', 'IXcore.admin_files');

// Ad Campaigns - by MegaJim
	define('TABLE_AD_CAMPAIGNS', 'ad_campaigns');

// UPS shit
	define('TABLE_PACKAGING', 'packaging');

// Payment Transactions
	define('TABLE_PAYMENTS', 'payments');

// Module tables.
// Added by FagSoft
	define ('TABLE_MODULE_CONFIG', 'module_config');
	define ('TABLE_DBFEEDS_PRODUCTS', 'dbfeed_products');
	define ('TABLE_DBFEEDS_PROD_EXTRA', 'dbfeed_products_extra');
	define('TABLE_NEWSLETTER_QUEUE', 'newsletter_queue'); // Bulk Mailer

// # dbfeed_products
	define('TABLE_DBFEED_PRODUCTS', 'dbfeed_products');
	define('TABLE_DBFEED_PRODUCTS_EXTRA', 'dbfeed_products_extra');

// # URL REWRITE MAP
	define('TABLE_URL_REWRITE', 'url_rewrite');
	define('TABLE_URL_REWRITE_MAP', 'url_rewrite_map');
?>
