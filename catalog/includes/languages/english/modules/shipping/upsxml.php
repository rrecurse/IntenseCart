<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	define('MODULE_SHIPPING_UPSXML_RATES_TEXT_TITLE', 'United Parcel Service');
	define('MODULE_SHIPPING_UPSXML_RATES_TEXT_DESCRIPTION', 'United Parcel Service (XML)');
	define('MODULE_SHIPPING_UPSXML_RATES_TEXT_UNKNOWN_ERROR', 'An unknown error occured with the UPS shipping calculations.');
	define('MODULE_SHIPPING_UPSXML_RATES_TEXT_IF_YOU_PREFER', 'If you prefer to use UPS as your shipping method, please contact');
	define('MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_ERROR', 'A communication error occured while attempting to contact the UPS gateway');
	define('MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_UNKNOWN_ERROR', 'An unknown error occured while attempting to contact the UPS gateway');
	define('MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_VERSION_ERROR', 'This module supports only xpci version 1.0001 of the UPS Rates Interface. Please contact the webmaster for additional assistance.');
	define('MODULE_SHIPPING_UPSXML_TIME_IN_TRANSIT_TEXT_NO_RATES','UPS returns success, but no EDDs were found');

	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_01', 'UPS Next Day Air');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_02', 'UPS 2nd Day Air');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_03', 'UPS Ground');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_07', 'UPS Worldwide Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_08', 'UPS Worldwide Expedited');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_11', 'UPS Standard');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_12', 'UPS 3 Day Select');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_13', 'UPS Next Day Air Saver');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_14', 'UPS Next Day Air Early A.M.');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_54', 'UPS Worldwide Express Plus');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_59', 'UPS 2nd Day Air A.M.');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_65', 'UPS Saver');

	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_01', 'UPS Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_02', 'UPS Expedited');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_07', 'UPS Worldwide Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_08', 'Worldwide Expedited');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_11', 'UPS Standard');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_12', 'UPS 3 Day Select');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_13', 'UPS Saver');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_14', 'UPS Express Early A.M.');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_54', 'UPS Worldwide Express Plus');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_65', 'UPS Saver');

	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_07', 'UPS Worldwide Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_08', 'UPS Worldwide Expedited');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_11', 'UPS Standard');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_54', 'UPS Worldwide Express Plus');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_65', 'UPS Saver');
	// # next five services Poland domestic only 
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_82', 'UPS Today Standard');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_83', 'UPS Today Dedicated Courier');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_84', 'UPS Today Intercity');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_85', 'UPS Today Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_86', 'UPS Today Express Saver');

	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_01', 'UPS Next Day Air');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_02', 'UPS 2nd Day Air');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_03', 'UPS Ground');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_07', 'UPS Worldwide Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_08', 'UPS Worldwide Expedited');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_14', 'UPS Next Day Air Early A.M.');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_54', 'UPS Worldwide Express Plus');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_65', 'UPS Saver');

	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_07', 'UPS Worldwide Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_08', 'UPS Worldwide Expedited');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_54', 'UPS Express Plus');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_65', 'UPS Saver');

	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_07', 'UPS Worldwide Express');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_08', 'UPS Worldwide Expedited');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_11', 'UPS Standard');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_54', 'UPS Worldwide Express Plus');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_65', 'UPS Saver');
  
	define('UPSXML_US_01', 'Next Day Air');
	define('UPSXML_US_02', '2nd Day Air');
	define('UPSXML_US_03', 'Ground');
	define('UPSXML_US_07', 'UPS Worldwide Express');
	define('UPSXML_US_08', 'UPS Worldwide Expedited');
	define('UPSXML_US_11', 'Standard');
	define('UPSXML_US_12', '3 Day Select');
	define('UPSXML_US_13', 'Next Day Air Saver');
	define('UPSXML_US_14', 'Next Day Air Early A.M.');
	define('UPSXML_US_54', 'Worldwide Express Plus');
	define('UPSXML_US_59', '2nd Day Air A.M.');
	define('UPSXML_US_65', 'Saver');
	define('UPSXML_CAN_01', 'Express');
	define('UPSXML_CAN_02', 'Expedited');
	define('UPSXML_CAN_14', 'Express Early A.M.');
	define('UPSXML_EU_82', 'Today Standard');
	define('UPSXML_EU_83', 'Today Dedicated Courier');
	define('UPSXML_EU_84', 'Today Intercity');
	define('UPSXML_EU_85', 'Today Express');
	define('UPSXML_EU_86', 'Today Express Saver');
	define('UPSXML_MEX_54', 'Express Plus');
	define('UPSXML_TEXT_BILLED_WEIGHT', 'billed dimensional weight '); 
	define('SHIPPING_DAYS_DELAY', 'Shipping Delay');

	// # Added SurePost compatibility 12/15/2014
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_92', 'UPS SurePost');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_93', 'UPS SurePost');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_94', 'UPS SurePost BPM');
	define('MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_95', 'UPS SurePost Media');

	define('UPSXML_US_92', 'SurePost&reg; Less then 1 lb.');
	define('UPSXML_US_93', 'SurePost&reg; Greater then 1 lb.');
	define('UPSXML_US_94', 'SurePost&reg; BPM');
	define('UPSXML_US_95', 'SurePost&reg; Media');

?>