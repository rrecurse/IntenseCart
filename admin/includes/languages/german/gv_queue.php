<?php
/*
  $Id: gv_queue.php,v 1.1.2.1 2003/05/15 23:10:55 wilt Exp $

  
  

  Copyright (c) 2002 - 2003 IntenseCart eCommerce

  Gift Voucher System v1.0
  Copyright (c) 2001,2002 Ian C Wilson
  http://www.phesis.org

  
*/

define('HEADING_TITLE', 'Gutschein Freigabe Queue');

define('TABLE_HEADING_CUSTOMERS', 'Kunde');
define('TABLE_HEADING_ORDERS_ID', 'Bestell-Nr.');
define('TABLE_HEADING_VOUCHER_VALUE', 'Gutscheinwert');
define('TABLE_HEADING_DATE_PURCHASED', 'Bestelldatum');
define('TABLE_HEADING_ACTION', 'Aktion');

define('TEXT_REDEEM_COUPON_MESSAGE_HEADER', 'Sie haben soeben einen Gutschein in unserem Webshop bestellt.' . "\n"
                                          . 'Aus Sicherheitsgründen ist dieser Gutschein nicht sofort verfügbar.' . "\n"
                                          . 'Sie können nun Ihren Gutschein verbuchen' . "\n"
                                          . 'und per E-Mail versenden' . "\n\n");

define('TEXT_REDEEM_COUPON_MESSAGE_AMOUNT', 'Der von Ihnen bestellte Gutschein hat einen Wert von %s' . "\n\n");

define('TEXT_REDEEM_COUPON_MESSAGE_BODY', '');
define('TEXT_REDEEM_COUPON_MESSAGE_FOOTER', '');
define('TEXT_REDEEM_COUPON_SUBJECT', 'Gutschein kaufen');?>
