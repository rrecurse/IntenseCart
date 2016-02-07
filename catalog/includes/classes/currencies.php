<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


  class currencies {
    var $currencies;
	
	function __construct() {

		$this->currencies = array();
		$currencies_query = tep_db_query("SELECT code, title, symbol_left, symbol_right, decimal_point, thousands_point, decimal_places, value FROM " . TABLE_CURRENCIES);

		while ($currencies = tep_db_fetch_array($currencies_query)) {
	        $this->currencies[$currencies['code']] = array('title' => $currencies['title'],
    	                                                   'symbol_left' => $currencies['symbol_left'],
        	                                               'symbol_right' => $currencies['symbol_right'],
            	                                           'decimal_point' => $currencies['decimal_point'],
                	                                       'thousands_point' => $currencies['thousands_point'],
                    	                                   'decimal_places' => $currencies['decimal_places'],
                        	                               'value' => $currencies['value']
														   );
		}
	}


	function format($number, $calculate_currency_value = true, $currency_type = '', $currency_value = '') {
		global $currency;

		if (empty($currency_type)) { 
			$currency_type = $currency;
		}

		if($calculate_currency_value == true) {

	        $rate = (tep_not_null($currency_value)) ? $currency_value : $this->currencies[$currency_type]['value'];

			// # if symbol left exists
			$format_string .= $this->currencies[$currency_type]['symbol_left'];

			// # now wrap the actual price itself in its own seperate span with itemprop="price" (for structured results
			$format_string .= number_format(tep_round($number * $rate, $this->currencies[$currency_type]['decimal_places']), $this->currencies[$currency_type]['decimal_places'], $this->currencies[$currency_type]['decimal_point'], $this->currencies[$currency_type]['thousands_point']);

			// # if symbol right exists
			$format_string .= $this->currencies[$currency_type]['symbol_right'];

			// # if the selected currency is in the european euro-conversion and the default currency is euro,
			// # the currency will displayed in the national currency and euro currency
			if( (DEFAULT_CURRENCY == 'EUR') && ($currency_type == 'DEM' || $currency_type == 'BEF' || $currency_type == 'LUF' || $currency_type == 'ESP' || $currency_type == 'FRF' || $currency_type == 'IEP' || $currency_type == 'ITL' || $currency_type == 'NLG' || $currency_type == 'ATS' || $currency_type == 'PTE' || $currency_type == 'FIM' || $currency_type == 'GRD') ) {

				$format_string .= ' <small>[' . $this->format($number, true, 'EUR') . ']</small>';
	        }

		} else {

			// # if symbol left exists
			$format_string .= $this->currencies[$currency_type]['symbol_left'];

			$format_string .= number_format(tep_round($number, $this->currencies[$currency_type]['decimal_places']), $this->currencies[$currency_type]['decimal_places'], $this->currencies[$currency_type]['decimal_point'], $this->currencies[$currency_type]['thousands_point']);

			// # if symbol right exists
			$format_string .= $this->currencies[$currency_type]['symbol_right'];

		}
	
		return $format_string;
    }

    function is_set($code) {
      if (isset($this->currencies[$code]) && tep_not_null($this->currencies[$code])) {
        return true;
      } else {
        return false;
      }
    }

    function get_value($code) {
      return $this->currencies[$code]['value'];
    }

    function get_decimal_places($code) {
      return $this->currencies[$code]['decimal_places'];
    }

    // function display_price($products_price, $products_tax, $quantity = 1) {
   //   return $this->format(tep_add_tax($products_price, $products_tax) * $quantity);
   // }


    function display_price($products_price, $products_tax, $quantity = 1) {
			return $this->format(tep_add_tax($products_price, $products_tax) * $quantity);

	}




  }
?>
