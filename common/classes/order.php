<?php

  class order {
    var $info, $totals, $products, $customer, $delivery;

    // The country codes below have NOT been verified exhaustively!
    // Used in fromISO3166_1()
    var $cc_processed=array();
    var $cc_raw="
      AF Afghanistan
      AX Åland Islands
      AL Albania
      DZ Algeria
      AS American Samoa
      AD Andorra
      AO Angola
      AI Anguilla
      AQ Antarctica
      AG Antigua and Barbuda
      AR Argentina
      AM Armenia
      AW Aruba
      AU Australia
      AT Austria
      AZ Azerbaijan
      BS Bahamas
      BH Bahrain
      BD Bangladesh
      BB Barbados
      BY Belarus
      BE Belgium
      BZ Belize
      BJ Benin
      BM Bermuda
      BT Bhutan
      BO Bolivia
      BA Bosnia and Herzegovina
      BW Botswana
      BV Bouvet Island
      BR Brazil
      IO British Indian Ocean Territory
      BN Brunei Darussalam
      BG Bulgaria
      BF Burkina Faso
      BI Burundi
      KH Cambodia
      CM Cameroon
      CA Canada
      CV Cape Verde
      KY Cayman Islands
      CF Central African Republic
      TD Chad
      CL Chile
      CN China
      CX Christmas Island
      CC Cocos (Keeling) Islands 
      CO Colombia
      KM Comoros
      CG Congo
      CD Congo, Democratic Republic of the
      CK Cook Islands
      CR Costa Rica
      CI Côte d'Ivoire
      HR Croatia
      CU Cuba
      CY Cyprus
      CZ Czech Republic
      DK Denmark
      DJ Djibouti
      DM Dominica
      DO Dominican Republic
      EC Ecuador
      EG Egypt
      SV El Salvador
      GQ Equatorial Guinea
      ER Eritrea
      EE Estonia
      ET Ethiopia
      FK Falkland Islands
      FO Faroe Islands
      FJ Fiji
      FI Finland
      FR France
      GF French Guiana
      PF French Polynesia
      TF French Southern Territories
      GA Gabon
      GM Gambia
      GE Georgia
      DE Germany
      GH Ghana
      GI Gibraltar
      GR Greece
      GL Greenland
      GD Grenada
      GP Guadeloupe
      GU Guam
      GT Guatemala
      GG Guernsey
      GN Guinea
      GW Guinea-Bissau
      GY Guyana
      HT Haiti
      HM Heard Island and McDonald Islands
      VA Vatican City
      HN Honduras
      HK Hong Kong
      HU Hungary
      IS Iceland
      IN India
      ID Indonesia
      IR Iran
      IQ Iraq
      IE Ireland
      IM Isle of Man
      IL Israel
      IT Italy
      JM Jamaica
      JP Japan
      JE Jersey 
      JO Jordan
      KZ Kazakhstan
      KE Kenya 
      KI Kiribati
      KP North Korea
      KR South Korea
      KW Kuwait
      KG Kyrgyzstan
      LA Laos
      LV Latvia
      LB Lebanon
      LS Lesotho
      LR Liberia
      LY Libya
      LI Liechtenstein
      LT Lithuania
      LU Luxembourg
      MO Macao
      MK Republic of Macedonia
      MG Madagascar
      MW Malawi
      MY Malaysia
      MV Maldives
      ML Mali
      MT Malta
      MH Marshall Islands
      MQ Martinique
      MR Mauritania
      MU Mauritius
      YT Mayotte
      MX Mexico
      FM Federated States of Micronesia
      MD Moldova
      MC Monaco
      MN Mongolia
      ME Montenegro
      MS Montserrat
      MA Morocco
      MZ Mozambique
      MM Myanmar
      NA Namibia
      NR Nauru
      NP Nepal
      NL Netherlands
      AN Netherlands Antilles
      NC New Caledonia
      NZ New Zealand
      NI Nicaragua
      NE Niger
      NG Nigeria
      NU Niue
      NF Norfolk Island
      MP Northern Mariana Islands
      NO Norway
      OM Oman
      PK Pakistan
      PW Palau
      PS Palestinian territories
      PA Panama
      PG Papua New Guinea
      PY Paraguay
      PE Peru
      PH Philippines
      PN Pitcairn
      PL Poland
      PT Portugal
      PR Puerto Rico
      QA Qatar
      RE Réunion
      RO Romania
      RU Russian Federation
      RW Rwanda
      BL Saint Barthélemy
      SH Saint Helena
      KN Saint Kitts and Nevis
      LC Saint Lucia
      MF Saint Martin
      PM Saint Pierre and Miquelon
      VC Saint Vincent and the Grenadines
      WS Samoa
      SM San Marino
      ST Sao Tome and Principe
      SA Saudi Arabia
      SN Senegal
      RS Serbia
      SC Seychelles
      SL Sierra Leone
      SG Singapore
      SK Slovakia
      SI Slovenia
      SB Solomon Islands
      SO Somalia
      ZA South Africa
      GS South Georgia and the South Sandwich Islands
      ES Spain
      LK Sri Lanka
      SD Sudan
      SR Suriname
      SJ Svalbard and Jan Mayen
      SZ Swaziland
      SE Sweden
      CH Switzerland
      SY Syria
      TW Republic of China
      TJ Tajikistan
      TZ Tanzania
      TH Thailand
      TL Timor-Leste
      TG Togo
      TK Tokelau
      TO Tonga
      TT Trinidad and Tobago
      TN Tunisia
      TR Turkey
      TM Turkmenistan
      TC Turks and Caicos Islands
      TV Tuvalu
      UG Uganda
      UA Ukraine
      AE United Arab Emirates
      GB United Kingdom
      US United States
      UM United States
      UY Uruguay 
      UZ Uzbekistan
      VU Vanuatu
      VE Venezuela 
      VN Viet Nam
      VG British Virgin Islands
      VI United States Virgin Islands
      WF Wallis and Futuna
      EH Western Sahara
      YE Yemen
      ZM Zambia
      ZW Zimbabwe
    ";
    function order($order_ref=NULL)
    {
      $this->info = array();
      $this->totals = array();
      $this->products = array();
      $this->customer = array();
      $this->delivery = array();
      $this->orderid=NULL;
      $this->message=Array();
      $this->error=Array();

      if ($order_ref) {
        if (!is_object($order_ref)) {
          $this->query($order_ref);
        } else {
          $this->cart($order_ref);
        }
      } else {
        $this->blank();
      }
    }

    function query($order_id)
    {
      $order_query = tep_db_query("
        SELECT *
        FROM " . TABLE_ORDERS . "
        WHERE orders_id = '" . tep_db_input($order_id) . "'
      ");
      $order = tep_db_fetch_array($order_query);

      $totals_query = tep_db_query("
        SELECT *
        FROM " . TABLE_ORDERS_TOTAL . "
        WHERE orders_id = '" . (int)$order_id . "'
        ORDER BY sort_order
      ");
      while ($totals = tep_db_fetch_array($totals_query)) {
        if ($totals['class']=='ot_total') {
          $total=$totals['value'];
        }
        $this->totals[] = array(
          'title' => $totals['title'],
          'text' => $totals['text'],
          'class' => $totals['class'],
          'value' => $totals['value'],
          'id' =>  $totals['orders_total_id']
        );
      }

      $this->orderid=$order['orders_id'];

      $this->info = array(
        'currency' => $order['currency'],
        'currency_value' => $order['currency_value'],
        'shipping_method' => $order['shipping_method'],
        'payment_method' => $order['payment_method'],
        'cc_type' => $order['cc_type'],
        'cc_owner' => $order['cc_owner'],
        'cc_number' => $order['cc_number'],
        'cc_expires' => $order['cc_expires'],
        'date_purchased' => $order['date_purchased'],
        'local_time_purchased' => $order['local_time_purchased'],
        'local_timezone' => $order['local_timezone'],
        'orders_status' => $order['orders_status'],
        'requested_status' => $order['requested_status'],
//-- Tracking contribution begin -->
        'ups_track_num' => $order['ups_track_num'],
        'usps_track_num' => $order['usps_track_num'],
        'fedex_track_num' => $order['fedex_track_num'],
        'dhl_track_num' => $order['dhl_track_num'],
//-- Tracking contribution end -->
        'comments' => $order['comments'],
        'last_modified' => $order['last_modified'],
        'tax_groups' => array(),
        'total' => $total
      );

      $this->customer = array(
        'name' => $order['customers_name'],
        'company' => $order['customers_company'],
        'id' => $order['customers_id'],
        'street_address' => $order['customers_street_address'],
        'suburb' => $order['customers_suburb'],
        'city' => $order['customers_city'],
        'postcode' => $order['customers_postcode'],
        'state' => $order['customers_state'],
        'country' => $order['customers_country'],
        'format_id' => $order['customers_address_format_id'],
        'telephone' => $order['customers_telephone'],
        'fax' => $order['customers_fax'],
        'email_address' => $order['customers_email_address']
      );

      $this->delivery = array(
        'name' => $order['delivery_name'],
        'company' => $order['delivery_company'],
        'street_address' => $order['delivery_street_address'],
        'suburb' => $order['delivery_suburb'],
        'city' => $order['delivery_city'],
        'postcode' => $order['delivery_postcode'],
        'state' => $order['delivery_state'],
        'country' => $order['delivery_country'],
        'format_id' => $order['delivery_address_format_id']
      );

      $this->billing = array(
        'name' => $order['billing_name'],
        'company' => $order['billing_company'],
        'street_address' => $order['billing_street_address'],
        'suburb' => $order['billing_suburb'],
        'city' => $order['billing_city'],
        'postcode' => $order['billing_postcode'],
        'state' => $order['billing_state'],
        'country' => $order['billing_country'],
        'format_id' => $order['billing_address_format_id']
      );

      $index = 0;
      //$orders_products_query = tep_db_query("select orders_products_id, products_name, products_model, products_price, products_tax, products_quantity, final_price from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'");
      $orders_products_query = tep_db_query("
        SELECT *
        FROM " . TABLE_ORDERS_PRODUCTS . "
        WHERE orders_id = '" . (int)$order_id . "'
      ");
      while ($orders_products = tep_db_fetch_array($orders_products_query)) {
        $this->products[$index] = array(
          'qty' => $orders_products['products_quantity'],
          'name' => $orders_products['products_name'],
          'id' => $orders_products['products_id'],
          'return' => $orders_products['products_returned'],
          'model' => $orders_products['products_model'],
          'tax' => $orders_products['products_tax'],
          'price' => $orders_products['products_price'],
          'weight' => $orders_products['products_weight'],
          'final_price' => $orders_products['final_price'],
          'stock_qty' => $orders_products['products_stock_quantity'],
          'free_shipping' => $orders_products['free_shipping'],
          'separate_shipping' => $orders_products['separate_shipping'],
          'exchange' => $orders_products['products_exchanged'],
          'exchange_id' => $orders_products['products_exchanged_id'],
          'orders_products_id' => $orders_products['orders_products_id'],
          'exchange_returns_id' => $orders_products['exchange_returns_id']
        );
        if (!isset($this->info['tax_groups']['Tax'])) {
          $this->info['tax_groups']['Tax']=0;
        }
        $this->info['tax_groups']['Tax']+=$orders_products['products_tax'];
        $subindex = 0;
        $attributes_query = tep_db_query("
          SELECT
            products_options,
            products_options_values,
            options_values_price,
            price_prefix
          FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
          WHERE
            orders_id = '" . (int)$order_id . "' AND
            orders_products_id = '" . (int)$orders_products['orders_products_id'] . "'
        ");
        if (tep_db_num_rows($attributes_query)) {
          while ($attributes = tep_db_fetch_array($attributes_query)) {
            $this->products[$index]['attributes'][$subindex] = array(
              'option' => $attributes['products_options'],
              'value' => $attributes['products_options_values'],
              'prefix' => $attributes['price_prefix'],
              'price' => $attributes['options_values_price']
            );

            $subindex++;
          }
        }
        $index++;
      }
      $this->returns=Array();
      $r_qry=tep_db_query("
        SELECT * FROM ".TABLE_RETURNS_PRODUCTS_DATA." rp
        LEFT JOIN ".TABLE_RETURNS." r ON rp.returns_id=r.returns_id
        LEFT JOIN refund_payments rf ON rf.returns_id=r.returns_id
        WHERE rp.order_id='" . (int)$order_id . "'
      ");
      while ($r_row=tep_db_fetch_array($r_qry)) {
        $this->returns[]=Array(
          'returns_products_id'=>$r_row['returns_products_id'],
          'returns_id'=>$r_row['returns_id'],
          'id'=>$r_row['products_id'],
          'qty'=>$r_row['products_quantity'],
          'rma'=>$r_row['rma_value'],
          'restock'=>$r_row['restock_quantity'],
          'refund_amount'=>$r_row['refund_amount'],
          'exchange_amount'=>$r_row['exchange_amount'],
          'refund_shipping_amount'=>$r_row['refund_shipping_amount'],
          'refund_shipping'=>$r_row['refund_shipping'],
        );
      }
    }
    
    function blank()
    {
      global $HTTP_POST_VARS;
      foreach (Array('name','company','street_address','suburb','city','postcode','state','country','format_id') AS $field)
        $this->customer[$field]=$this->delivery[$field]=$this->billing[$field]=isset($HTTP_POST_VARS[$field])?$HTTP_POST_VARS[$field]:'';
      if (isset($HTTP_POST_VARS['firstname']) && isset($HTTP_POST_VARS['lastname']))
        $this->customer['name']=$this->delivery['name']=$this->billing['name']=$HTTP_POST_VARS['firstname'].' '.$HTTP_POST_VARS['lastname'];
      foreach (Array('telephone','email_address') AS $field)
        $this->customer[$field]=isset($HTTP_POST_VARS[$field])?$HTTP_POST_VARS[$field]:'';
      $this->info = Array(
        'currency' => '',
        'currency_value' => '',
        'payment_method' => '',
        'cc_type' => '',
        'cc_owner' => '',
        'cc_number' => '',
        'cc_expires' => '',
        'date_purchased' => '',
        'orders_status' => 1,
        'ups_track_num' => '',
        'usps_track_num' => '',
        'fedex_track_num' => '',
        'last_modified' => '',
        'total'=>0
      );
      $this->totals = Array(
        Array('class'=>'ot_subtotal',title=>'Subtotal:',text=>'0.00',value=>0,order_total_id=>0),
        Array('class'=>'ot_shipping',title=>'Shipping:',text=>'0.00',value=>0,order_total_id=>0),
        Array('class'=>'ot_coupon',title=>'Discount Coupons:',text=>'0.00',value=>0,order_total_id=>0),
        Array('class'=>'ot_tax',title=>'Tax:',text=>'0.00',value=>0,order_total_id=>0),
        Array('class'=>'ot_total',title=>'Total:',text=>'0.00',value=>0,order_total_id=>0)
      );
      $this->products=Array();
      $this->returns=Array();
    }
    
    function getShippingWeights()
    {
      $rt=Array();
      foreach ($this->returns AS $r)
        if ($r['refund_shipping'])
          $rt[$r['id']]+=$r['qty'];
      $wt=Array(0);
      foreach ($this->products AS $p) {
        if (!$p['free_shipping']) {
          $q=$p['qty'];
          if (isset($rt[$p['id']])) {
            $q-=$rt[$p['id']];
            if ($q>=0) $rt[$p['id']]=0; else {
              $q=0;
              $rt[$p['id']]-=$p['qty'];
            }
          }
          if ($p['separate_shipping']) {
            for ($i=0;$i<$q;$i++) $wt[]=$p['weight'];
          } else if ($q>0) $wt[0]=max($wt[0],1)+$p['weight']*$q;
        }
      }
      if ($wt[0]==0)
        array_shift($wt);
      return $wt;
    }
    
    function getShippingRefund($cost)
    {
      if (!$this->getShippingWeights())
        return $cost;
      $rf=0;
      foreach ($this->returns AS $ret)
        if ($ret['refund_shipping'])
          $rf+=$ret['refund_shipping_amount'];
      return min($rf,$cost);
    }
    
    function getSubTotal()
    {
      $a=0;
      foreach ($this->products AS $p) $a+=$p['final_price']*$p['qty'];
//      foreach ($this->returns AS $r) $a-=$r['refund_amount'];
      return $a;
    }

    function getPayments()
    {
      include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
      if (!isset($this->payments)) {
        $this->payments=Array();
        if (isset($this->orderid)) {
          $pay_qry=tep_db_query("
            SELECT *
            FROM ".TABLE_PAYMENTS."
            WHERE orders_id='".$this->orderid."'
          ");
          while ($pay_row=tep_db_fetch_array($pay_qry)) {
            $pay=IXpayment::loadPaymentFromRow($pay_row);
            if (isset($pay)) $this->payments[]=$pay;
          }
        }
      }
      return $this->payments;
    }

    function cart(&$cart)
    {
      global $customer_id, $sendto, $billto, $languages_id, $currency, $currencies, $shipping, $payment;

      $this->content_type = $cart->get_content_type();

      $customer_address_query = tep_db_query("
        SELECT
          c.customers_firstname,
          c.customers_lastname,
          c.customers_telephone,
          c.customers_fax,
          c.customers_email_address,
          ab.entry_company,
          ab.entry_street_address,
          ab.entry_suburb,
          ab.entry_postcode,
          ab.entry_city,
          ab.entry_zone_id,
          z.zone_name,
          co.countries_id,
          co.countries_name,
          co.countries_iso_code_2,
          co.countries_iso_code_3,
          co.address_format_id,
          ab.entry_state
        FROM ".
          TABLE_CUSTOMERS." c, ".
          TABLE_ADDRESS_BOOK." ab
        LEFT JOIN " . TABLE_ZONES . " z ON (ab.entry_zone_id = z.zone_id)
        LEFT JOIN " . TABLE_COUNTRIES . " co on (ab.entry_country_id = co.countries_id)
        WHERE
          c.customers_id = '" . (int)$customer_id . "' AND
          ab.customers_id = '" . (int)$customer_id . "' AND
          c.customers_default_address_id = ab.address_book_id
      ");
      $customer_address = tep_db_fetch_array($customer_address_query);

      $shipping_address_query = tep_db_query("select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) left join " . TABLE_COUNTRIES . " c on (ab.entry_country_id = c.countries_id) where ab.customers_id = '" . (int)$customer_id . "' and ab.address_book_id = '" . (int)$sendto . "'");
      $shipping_address = tep_db_fetch_array($shipping_address_query);
      
      $billing_address_query = tep_db_query("select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) left join " . TABLE_COUNTRIES . " c on (ab.entry_country_id = c.countries_id) where ab.customers_id = '" . (int)$customer_id . "' and ab.address_book_id = '" . (int)$billto . "'");
      $billing_address = tep_db_fetch_array($billing_address_query);

      $tax_address_query = tep_db_query("select ab.entry_country_id, ab.entry_zone_id from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) where ab.customers_id = '" . (int)$customer_id . "' and ab.address_book_id = '" . (int)($this->content_type == 'virtual' ? $billto : $sendto) . "'");
      $tax_address = tep_db_fetch_array($tax_address_query);

      $this->info = array(
        'orders_status' => NULL,
        'currency' => $currency,
        'currency_value' => $currencies->currencies[$currency]['value'],
        'payment_method' => $payment,
        'cc_type' => (isset($GLOBALS['cc_type']) ? $GLOBALS['cc_type'] : ''),
        'cc_owner' => (isset($GLOBALS['cc_owner']) ? $GLOBALS['cc_owner'] : ''),
        'cc_number' => '****************',
        'cc_expires' => 'MMYY',
        'shipping_method' => $shipping['title'],
        'shipping_cost' => $shipping['cost'],
        'subtotal' => 0,
        'tax' => 0,
        'tax_groups' => array(),

        'comments' => (isset($GLOBALS['comments']) ? $GLOBALS['comments'] : '')
      );


      $this->customer = array(
        'customers_id'=>$customer_id,
        'firstname' => $customer_address['customers_firstname'],
        'lastname' => $customer_address['customers_lastname'],
        'company' => $customer_address['entry_company'],
        'street_address' => $customer_address['entry_street_address'],
        'suburb' => $customer_address['entry_suburb'],
        'city' => $customer_address['entry_city'],
        'postcode' => $customer_address['entry_postcode'],
        'state' => ((tep_not_null($customer_address['entry_state'])) ? $customer_address['entry_state'] : $customer_address['zone_name']),
        'zone_id' => $customer_address['entry_zone_id'],
        'country' => $customer_address['countries_name'],
        'format_id' => $customer_address['address_format_id'],
        'telephone' => $customer_address['customers_telephone'],
        'fax' => $customer_address['customers_fax'],
        'email_address' => $customer_address['customers_email_address']
      );

      $this->delivery = array(
        'firstname' => $shipping_address['entry_firstname'],
        'lastname' => $shipping_address['entry_lastname'],
        'company' => $shipping_address['entry_company'],
        'street_address' => $shipping_address['entry_street_address'],
        'suburb' => $shipping_address['entry_suburb'],
        'city' => $shipping_address['entry_city'],
        'postcode' => $shipping_address['entry_postcode'],
        'state' => ((tep_not_null($shipping_address['entry_state'])) ? $shipping_address['entry_state'] : $shipping_address['zone_name']),
        'zone_id' => $shipping_address['entry_zone_id'],
        'country' => $shipping_address['countries_name'],
        'country_id' => $shipping_address['entry_country_id'],
        'format_id' => $shipping_address['address_format_id']
      );

      $this->billing = array(
        'firstname' => $billing_address['entry_firstname'],
        'lastname' => $billing_address['entry_lastname'],
        'company' => $billing_address['entry_company'],
        'street_address' => $billing_address['entry_street_address'],
        'suburb' => $billing_address['entry_suburb'],
        'city' => $billing_address['entry_city'],
        'postcode' => $billing_address['entry_postcode'],
        'state' => ((tep_not_null($billing_address['entry_state'])) ? $billing_address['entry_state'] : $billing_address['zone_name']),
        'zone_id' => $billing_address['entry_zone_id'],
        'country' => $billing_address['countries_name'],
        'country_id' => $billing_address['entry_country_id'],
        'format_id' => $billing_address['address_format_id']
      );

      $index = 0;
      $this->returns=Array();
      $products = $cart->get_products();
      for ($i=0, $n=sizeof($products); $i<$n; $i++) {
        $this->products[$index] = array(
          'qty' => $products[$i]['quantity'],
          'name' => $products[$i]['name'],
          'model' => $products[$i]['model'],
          'tax' => tep_get_tax_rate($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
          'tax_description' => tep_get_tax_description($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
          'price' => $products[$i]['price'],
          'final_price' => $products[$i]['price'],
          'weight' => $products[$i]['weight'],
          'free_shipping' => $products[$i]['products_free_shipping'],
          'separate_shipping' => $products[$i]['products_separate_shipping'],
          'id' => $products[$i]['id'],
          'orders_products_id' => NULL
        );

// BOF Separate Pricing Per Customer
        if(!tep_session_is_registered('sppc_customer_group_id')) {
          $customer_group_id = '0';
        } else {
          $customer_group_id = $sppc_customer_group_id;
        }
        if ($customer_group_id != '0') {
          $orders_customers_price = tep_db_query("select customers_group_price from " . TABLE_PRODUCTS_GROUPS . " where customers_group_id = '". $customer_group_id . "' and products_id = '" . $products[$i]['id'] . "'");
          $orders_customers = tep_db_fetch_array($orders_customers_price);
          if ($orders_customers = tep_db_fetch_array($orders_customers_price)) {
            $this->products[$index] = array(
              'price' => $orders_customers['customers_group_price'],
              'final_price' => $orders_customers['customers_group_price'] + $cart->attributes_price($products[$i]['id'])
            );
          }
        }

// EOF Separate Pricing Per Customer
        if ($products[$i]['attributes']) {
          $subindex = 0;
          reset($products[$i]['attributes']);
          while (list($option, $value) = each($products[$i]['attributes'])) {
//++++ QT Pro: Begin Changed code
//            $attributes_query = tep_db_query("select popt.products_options_name, popt.products_options_track_stock, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pov2p.products_id from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS . " pov2p ON poval.products_options_values_id=pov2p.products_options_values_id where pa.products_id = '" . (int)$products[$i]['id'] . "' and pa.options_id = '" . (int)$option . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . (int)$value . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . (int)$languages_id . "' and poval.language_id = '" . (int)$languages_id . "'");
//++++ QT Pro: End Changed Code
//            $attributes = tep_db_fetch_array($attributes_query);

//++++ QT Pro: Begin Changed code
/*
            $this->products[$index]['attributes'][$subindex] = array('option' => $attributes['products_options_name'],
                                                                     'value' => $attributes['products_options_values_name'],
                                                                     'option_id' => $option,
                                                                     'value_id' => $value,
                                                                     'prefix' => $attributes['price_prefix'],
                                                                     'price' => $attributes['options_values_price'],
                                                                     'products_id' => $attributes['products_id'],
                                                                     'track_stock' => $attributes['products_options_track_stock']);
*/
            $this->products[$index]['attributes'][$subindex] = array(
              'option' => $option,
              'value' => $value,
              'option_id' => $option,
              'value_id' => $value,
              'prefix' => '',
              'price' => 0,
              'products_id' => 0,
              'track_stock' => 0
            );
//++++ QT Pro: End Changed Code

            $subindex++;
          }
        }

        $shown_price = tep_add_tax($this->products[$index]['final_price'], $this->products[$index]['tax']) * $this->products[$index]['qty'];
        $this->info['subtotal'] += $shown_price;

        $products_tax = $this->products[$index]['tax'];
        $products_tax_description = $this->products[$index]['tax_description'];


// BOF Separate Pricing Per Customer, show_tax modification
// next line was original code
//      if (DISPLAY_PRICE_WITH_TAX == 'true') {
        global $sppc_customer_group_show_tax;
        if(!tep_session_is_registered('sppc_customer_group_show_tax')) { 
          $customer_group_show_tax = '1';
        } else {
          $customer_group_show_tax = $sppc_customer_group_show_tax;
        }		
        if (DISPLAY_PRICE_WITH_TAX == 'true' && $customer_group_show_tax == '1') {
// EOF Separate Pricing Per Customer, show_tax modification		
          $this->info['tax'] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          if (isset($this->info['tax_groups']["$products_tax_description"])) {
            $this->info['tax_groups']["$products_tax_description"] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          } else {
            $this->info['tax_groups']["$products_tax_description"] = $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          }
        } else {
          $this->info['tax'] += ($products_tax / 100) * $shown_price;
          if (isset($this->info['tax_groups']["$products_tax_description"])) {
            $this->info['tax_groups']["$products_tax_description"] += ($products_tax / 100) * $shown_price;
          } else {
            $this->info['tax_groups']["$products_tax_description"] = ($products_tax / 100) * $shown_price;
          }
        }

        $index++;
      }

// BOF Separate Pricing Per Customer, show_tax modification
// next line was original code
//      if (DISPLAY_PRICE_WITH_TAX == 'true') {
      global $sppc_customer_group_show_tax;
      if(!tep_session_is_registered('sppc_customer_group_show_tax')) {
        $customer_group_show_tax = '1';
      } else {
        $customer_group_show_tax = $sppc_customer_group_show_tax;
      }
      if ((DISPLAY_PRICE_WITH_TAX == 'true') && ($customer_group_show_tax == '1')) {
// EOF Separate Pricing Per Customer, show_tax modification		

        $this->info['total'] = $this->info['subtotal'] + $this->info['shipping_cost'];
      } else {
        $this->info['total'] = $this->info['subtotal'] + $this->info['tax'] + $this->info['shipping_cost'];
      }
    }

// ^^^^^^^^^ i'll review this mess later ^^^^^^^^^^


    function prepareStatus($st)
    {
    }

    function setStatus($st=NULL)
    {
      if (!isset($st))
        $st=$this->info[isset($this->info['requested_status'])?'requested_status':'orders_status'];
      $this->info['requested_status']=$st;
      if ($this->orderid) IXdb::store('update','orders',Array('requested_status'=>$st),"orders_id='{$this->orderid}'");
      $this->getPayments();
      $run=$this->info['total'];
      foreach ($this->payments AS $idx=>$p) {
        $pay=&$this->payments[$idx];
        $pay->inhibitAsynchronousAPIcalls();
        echo "ST=$st<br>"; //@@debug
        switch ($st) {
          case 0:
            $pay->cancelPayment($this);
            break;
          case 1:
            if ($run<=0) break;
            $r=$pay->authorizePayment($run,$this);
	    if ($r===false) return 'async';
            if ($r) $run-=$r;
//          if ($run<-0.005) $this->error[]=get_class($pay).": Refund Failed";
            break;
          default:
            if ($run<=0) $run=0;
            echo "run(before)=$run<br>"; //@@debug
            $r=$pay->settlePayment($run,$this);
            if ($r===false) {
              echo "Async!"; //@@debug
              return 'async';
            }
            if ($r) $run-=$r;
            echo "run(after)=$run<br>"; //@@debug
            if ($run<-0.005) $this->error[]=get_class($pay).": Refund Failed";
        }
      }

      if ($st>0 && $run>0.005) {
        if (isset($_POST['pay_method']) && $_POST['pay_method']) {
          if (preg_match('/^(\d+)(:(.*))?/',$_POST['pay_method'],$pp)) {
            foreach ($this->payments AS $p)
              if ($p->payid==$pp[1]) {
                $pay=$p->recurPayment(
                  isset($_POST['payment_on_file'])?$_POST['payment_on_file']:($pp[3]?$pp[3]:NULL)
                );
                if (isset($pay))
                  break;
              }
          } else {
            $pay=tep_module($_POST['pay_method'],'payment');
          }
        } else {
          foreach ($this->payments AS $p) {
            $pay=$p->recurPayment();
            if (isset($pay)) break;
          }
        }
        if (isset($pay) && $pay->checkConf()) {
          $pay->initPayment($run,$this);
          $this->payments[]=&$pay;
          $r=$st==1?$pay->authorizePayment($run,$this):$pay->settlePayment($run,$this);
          if ($r>0) {
            $pay->finishPayment($this);
            $run-=$r;
            if ($run>0.005) {
              $this->error[]=get_class($pay).": Bad Surcharge Amount (".sprintf("$%.2f",$run)." more to charge)";
            } else {
              $this->message[]=get_class($pay).": Surcharged ".sprintf("$%.2f",$r);
            }
          } else {
            $this->error[]=get_class($pay).": Surcharge Failed: ".$pay->getError();
          }
        }
      }
      if ($st>0 && $run>0.005) return NULL;
      if ($st>1 && $run<-0.005) return NULL;
      if ($st>1) $this->approvePurchase();
      tep_db_query("
        UPDATE ".TABLE_ORDERS."
        SET orders_status='".addslashes($st)."'
        WHERE orders_id='".$this->orderid."'
      ");
      $this->info['orders_status']=$st;
      $this->adjustStock();
      return true;
    }

    function approvePurchase()
    {
      $objs=$this->getProducts();
      foreach ($objs AS $idx=>$obj) {
        $obj->approvePurchase($this->products[$idx]['qty'],$this->products[$idx]['orders_products_id'],$this);
      }
      $this->adjustStock();
    }
    
    function adjustStock()
    {
      $rt=Array();
      foreach ($this->returns AS $r) {
        $rt[$r['id']]+=$r['restock'];
      }
      foreach ($this->products AS $idx=>$p) {
        if ($p['orders_products_id']) {
          if ($this->info['orders_status']>0) {
            $q=$p['qty'];
            if (isset($rt[$p['id']])) {
              $q-=($dq=min($q,$rt[$p['id']]));
              $rt[$p['id']]-=$dq;
            }
          } else $q=0;
          if ($q!=$p['stock_qty']) {
            if (!isset($prods))
              $prods=$this->getProducts();
//@@            $adj=isset($prods[$idx])?$prods[$idx]->adjustStock($p['stock_qty']-$q)+0:0;
            $p['stock_qty']-=$adj;
            $this->products[$idx]['stock_qty']-=$adj;
            if ($adj) tep_db_query("
              UPDATE orders_products
              SET products_stock_quantity=products_stock_quantity-($adj)
              WHERE orders_products_id='".$p['orders_products_id']."'
            ");
          }
        }
      }
    }
    
    function getPurchaseInfo()
    {
      $rs=Array();
      $objs=$this->getProducts();
      foreach ($objs AS $idx=>$obj) {
        $rs=array_merge($rs,$obj->getPurchaseInfo($this->products[$idx]['orders_products_id'],$this));
      }
      return $rs;
    }
    
    function getProducts() {
      $rs=Array();
      foreach ($this->products AS $idx=>$pr) {
        $obj=IXproduct::load($pr['id']);
        if ($obj)
          $rs[$idx]=$obj;
      }
      return $rs;
    }
    
    function updateTotals() {
      $mod=tep_module('order_total');
      $this->totals=$mod->calculateTotal((isset($this->totals)?$this->totals:Array()),$this);
      $this->saveTotals();
    }

    function saveOrder()
    {
      $data=Array(
        'currency' => $this->info['currency'],
        'currency_value' => $this->info['currency_value'],

        'shipping_method' => $this->info['shipping_method'],
        'payment_method' => $this->info['payment_method'],
        'cc_type' => $this->info['cc_type'],
        'cc_owner' => $this->info['cc_owner'],
        'cc_number' => $this->info['cc_number'],
        'cc_expires' => $this->info['cc_expires'],

        'date_purchased' => $this->info['date_purchased'],
        'local_time_purchased' => $this->info['local_time_purchased'],
        'local_timezone' => $this->info['local_timezone'],
        'orders_status' => $this->info['orders_status'],

        'ups_track_num' => $this->info['ups_track_num'],
        'usps_track_num' => $this->info['usps_track_num'],
        'fedex_track_num' => $this->info['fedex_track_num'],
        'dhl_track_num' => $this->info['dhl_track_num'],

        'comments' => $this->info['comments'],
        'last_modified' => date('Y-m-d H:i:s'),
        'customers_id' => $this->customer['id'],
        'customers_telephone' => $this->customer['telephone'],
        'customers_fax' => $this->customer['fax'],
        'customers_email_address' => $this->customer['email_address'],
      );
      foreach (Array(
        'customers'=>$this->customer,
        'delivery'=>$this->delivery,
        'billing'=>$this->billing
      ) AS $sec=>$lst) {
        foreach (Array(
          'name'=>'name',
          'company'=>'company',
          'street_address'=>'street_address',
          'suburb'=>'suburb',
          'city'=>'city',
          'postcode'=>'postcode',
          'state'=>'state',
          'country'=>'country',
          'format_id'=>'address_format_id'
        ) AS $f=>$dbf) {
          $data[$sec.'_'.$dbf]=$lst[$f];
        }
      }
      foreach ($data AS $f=>$v)
        if (!isset($v))
          $data[$f]='';
      if ($this->orderid) {
        IXdb::store('update','orders',$data,"orders_id='{$this->orderid}'");
      } else {
        IXdb::store('insert','orders',$data);
        $this->orderid=IXdb::insert_id();
      }
      $this->saveProducts();
      $this->updateTotals();
    }
    
    function saveProducts()
    {
      $this->adjustStock();
      foreach ($this->products AS $idx=>$pr) {
        $pdata=Array(
          'products_quantity'=>$pr['qty'],
          'products_name'=>$pr['name'],
          'products_id'=>$pr['id'],
          'products_model'=>$pr['model'],
          'products_tax'=>$pr['tax'],
          'products_price'=>$pr['price'],
          'final_price'=>$pr['final_price'],
          'free_shipping'=>$pr['free_shipping'],
          'separate_shipping'=>$pr['separate_shipping']
        );
        if ($pr['orders_products_id']) {
          if ($pr['qty']>0) {
            IXdb::store('update','orders_products',$pdata,"orders_products_id");
          } else {
            IXdb::query("DELETE FROM orders_products WHERE orders_products_id='{$pr['orders_products_id']}");
            IXdb::query("DELETE FROM orders_products_attributes WHERE orders_products_id='{$pr['orders_products_id']}");
            unset($this->products[$idx]);
            continue;
          }
        } else {
          $pdata['orders_id']=$this->orderid;
          IXdb::store('insert','orders_products',$pdata);
          $this->products[$idx]['orders_products_id']=$pr['orders_products_id']=IXdb::insert_id();
//      IXdb::write('orders_products',$pr['attrs'],'products_options','products_options_values',Array('orders_id'=>$this->orderid,'orders_products_id'=>$pr['orders_products_id']));
          if ($pr['attributes']) {
            foreach ($pr['attributes'] AS $at) {
              IXdb::store('insert','orders_products_attributes',Array('products_options'=>$at['option'],'products_options_values'=>$at['value'],'orders_id'=>$this->orderid,'orders_products_id'=>$pr['orders_products_id']));
            }
          }
        }
      }
      $this->adjustStock();
    }

    function create($cus_id,$ship_addr=NULL,$bill_addr=NULL,$cus_addr=NULL)
    {
      if ($cus_id) {
        $customer_address_query = tep_db_query("
          SELECT
            c.customers_firstname,
            c.customers_lastname,
            c.customers_telephone,
            c.customers_fax,
            c.customers_email_address,
            ab.entry_company,
            ab.entry_street_address,
            ab.entry_suburb,
            ab.entry_postcode,
            ab.entry_city,
            ab.entry_zone_id,
            z.zone_name,
            co.countries_id,
            co.countries_name,
            co.countries_iso_code_2,
            co.countries_iso_code_3,
            co.address_format_id,
            ab.entry_state
          FROM ".
            TABLE_CUSTOMERS." c, ".
            TABLE_ADDRESS_BOOK." ab
          LEFT JOIN ".TABLE_ZONES." z ON (ab.entry_zone_id = z.zone_id)
          LEFT JOIN ".TABLE_COUNTRIES." co ON (ab.entry_country_id = co.countries_id)
          WHERE
            c.customers_id = '" . (int)$customer_id . "' AND
            ab.customers_id = '$cus_id' AND
            c.customers_default_address_id = ab.address_book_id
        ");
        $customer_address = tep_db_fetch_array($customer_address_query);
        $this->customer = array(
          'customers_id'=>$cus_id,
          'firstname' => $customer_address['customers_firstname'],
          'lastname' => $customer_address['customers_lastname'],
          'company' => $customer_address['entry_company'],
          'street_address' => $customer_address['entry_street_address'],
          'suburb' => $customer_address['entry_suburb'],
          'city' => $customer_address['entry_city'],
          'postcode' => $customer_address['entry_postcode'],
          'state' => ((tep_not_null($customer_address['entry_state'])) ? $customer_address['entry_state'] : $customer_address['zone_name']),
          'zone_id' => $customer_address['entry_zone_id'],
          'country' => $customer_address['countries_name'],
          'format_id' => $customer_address['address_format_id'],
          'telephone' => $customer_address['customers_telephone'],
          'fax' => $customer_address['customers_fax'],
          'email_address' => $customer_address['customers_email_address']
        );
        if (!$ship_addr)
          $ship_addr=$this->customer;
      } elseif ($cus_addr) {
        // Hack alert! The logic is twisted here, because if you provide $cus_addr you probably
        // expect it to override $cus_id. Didn't want to risk messing up code elsewhere though,
        // so $cus_addr is still superseded by $cus_id, if the latter is present. --BS, 2008-03-07
        $this->customer=$cus_addr;
      }
      if (!$bill_addr)
        $bill_addr=$ship_addr;
      $this->delivery=$ship_addr;
      $this->billing=$bill_addr;
    }
    
    function addProduct($pid,$qty=1,$attrs=NULL,$price=NULL)
    {
      $prod=IXdb::read("
        SELECT *
        FROM products p
        LEFT JOIN products_description pd ON
          p.master_products_id=pd.products_id AND
          pd.language_id='{$GLOBALS['languages_id']}'
        WHERE
          p.products_id='$pid'
      ");
      if (!isset($price)) $price=$prod['products_price'];
      $attrlst=Array();
      if ($attrs) foreach ($attrs AS $k=>$v) $attrlst[]=Array('option' => $k,'value' => $v);
      $this->products[]=Array(
        'qty'=>$qty,
        'id'=>$pid,
        'name'=>$prod['products_name'].'',
        'price'=>$price,
        'final_price'=>$price,
        'attributes'=>$attrlst,
        'tax'=>0,
        'free_shipping'=>$prod['products_free_shipping']?1:0,
        'separate_shipping'=>$prod['products_separate_shipping']?1:0,
      );
      $this->info['subtotal'] += $price*$qty;
    }
    
    function addItem($class,$data)
    {
    }
    
    function saveTotals()
    {
//      print_r($this->totals);
//      return;
      $idlst=Array();
      foreach ($this->totals AS $idx=>$t) {
        if (isset($t['id'])) {
          $idlst[]="'".$t['id']."'";
        }
      }
      tep_db_query("
        DELETE FROM ".TABLE_ORDERS_TOTAL."
        WHERE
          orders_id='".$this->orderid."'".($idlst?" AND
          orders_total_id NOT IN (".join(',',$idlst).")":'')
      );
      foreach ($this->totals AS $idx=>$t) {
        $qry=Array(
          'class'=>$t['class'],
          'value'=>$t['value'],
          'text'=>$t['text'],
          'title'=>$t['title'],
          'sort_order'=>$idx
        );
        if (isset($t['id'])) {
          tep_db_perform(TABLE_ORDERS_TOTAL,$qry,'update',"orders_total_id='".$t['id']."'");
        } else {
          $qry['orders_id']=$this->orderid;
          tep_db_perform(TABLE_ORDERS_TOTAL,$qry);
          $this->totals[$idx]['id']=tep_db_insert_id();
        }
      }
    }
    
    function getShippingMethods()
    {
      // stub
      return array(
        array(
          'title'=>'2nd day shipping',
          'price'=>'10.00'
        ),
        array(
          'title'=>'Next day shipping',
          'price'=>'25.00'
        ),
        array(
          'title'=>'Next year shipping',
          'price'=>'1.50'
        )
      );
    }
    
    function setPromo($code)
    {
      // stub; returns the discount for this coupon, or NULL on failure
      return 3.50;
    }
    
    function getPromo()
    {
      // stub; returns the discount for this order
      return 3.50;
    }

    function getPromoList()
    {
      // stub; returns all discounts associated with this order
      return array(array('reason'=>'test_discount','amount'=>3.5));
    }
    
    function setShipping($ship_key)
    {
      // stub; sets the shipping method associated with $ship_key and returns its cost
      return 4.50;
    }
    
    function getShipping()
    {
      // stub; returns the current shipping price; must deal with defaults!
      return array('name'=>'test','cost'=>4.5);
    }

    function getProductList()
    {
      // stub; returns an array representation of the associated products
      $product=array('id'=>5,'name'=>'test_product','qty'=>30);
      $list=array($product);
      $product['name']='test_product2';
      $list[]=$product;
      return $list;
    }
    
    function getOrderTotalValue($class)
    {
      switch($class) {
        case 'ot_total':
        case 'ot_subtotal':
          $subtotal=$this->info['subtotal'];
          if ($class=='ot_subtotal') {
            return $subtotal;
          }
        case 'ot_tax':
          // stub; the actual code should be moved in a method of its own
          $tax=4.25;
          if ($class=='ot_tax') {
            return $tax;
          }
        case 'ot_shipping':
          $shipping=$this->getShipping();
          if ($class=='ot_shipping') {
            return $shipping;
          }
        case 'ot_discount':
          $discount=$this->getPromo();
          if ($class=='ot_discount') {
            return $discount;
          }
          
          // ot_total
          return $subtotal+$tax+$shipping-$discount;
        default:
          return NULL;
      }
    }

    function fromISO3166_1($country_code)
    {
      if (!$this->cc_processed) {
        $this->process_cc();
      }
      return $this->cc_processed[strtoupper($country_code)];
    }

    function process_cc()
    {
      $cc_tmp=explode("\n",$this->cc_raw);
      $cc_processed=array();
      foreach($cc_tmp as $cc_line) {
        $cc_lineX=explode(" ",trim($cc_line));
        $cc_processed[$cc_lineX[0]]=$cc_lineX[1];
      }
      $this->cc_processed=$cc_processed;
      return true;
    }
    
    function batchItemCount($ids,$class=NULL)
    {
      return array(1=>1,2=>2);
      return
        IXdb::read("
          SELECT
            orders_id,
            COUNT(0) AS ct
          FROM
            orders_products
          WHERE
            orders_id IN (".join(',',$ids).")
          GROUP BY orders_id",
          'orders_id',
          'ct'
        );
    }

    function getShipTo()
    {
      // stub
      if (!$this->orderid) {
        return false;
      }
      return new IXAddress(
        Array(
          'name'=>'....',
          'first_name'=>'....',
          'last_name'=>'....',
          'address'=>'....',
          'address2'=>'....',
          'city'=>'....',
          'postcode'=>'....',
          'zone'=>'....',
          'country'=>'....'
        )
      );
    }

    function getBillTo()
    {
      // stub
      return $this->getShipTo();
    }
  }
?>
