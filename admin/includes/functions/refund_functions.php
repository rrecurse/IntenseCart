<?
/*
  $id author Puddled Internet - http://www.puddled.co.uk
  email support@puddled.co.uk
   
  

  Copyright (c) 2002 IntenseCart eCommerce

  
  }
*/
 function tep_create_rma_value($length, $type = 'digits') {
    if ( ($type != 'mixed') && ($type != 'chars') && ($type != 'digits')) return false;

    $rand_value = '';
    while (strlen($rand_value)<$length) {
      if ($type == 'digits') {
        $char = tep_rand(0,9);
      } else {
        $char = chr(tep_rand(0,255));
      }
      if ($type == 'mixed') {
        if (preg_match('/^[a-z0-9]$/i', $char)) $rand_value .= $char;
      } elseif ($type == 'chars') {
        if (preg_match('/^[a-z]$/i', $char)) $rand_value .= $char;
      } else if ($type == 'digits') {
        if (preg_match('/^[0-9]$/', $char)) $rand_value .= $char;
      }
    }

    return $rand_value;
  }

  function tep_get_return_reason() {
    global $languages_id;

    $orders_status_array = array();
    $orders_status_query = tep_db_query("select return_reason_id, return_reason_name from " . TABLE_RETURN_REASONS . " where language_id = '" . $languages_id . "' order by return_reason_id");
    while ($orders_status = tep_db_fetch_array($orders_status_query)) {
      $orders_status_array[] = array('id' => $orders_status['return_reason_id'],
                                     'text' => $orders_status['return_reason_name']
                                    );
    }

    return $orders_status_array;
  }



function tep_get_return_reason_name($return_reason_id, $language_id = '') {
    global $languages_id;

    if ($return_reason_id < 1) return TEXT_DEFAULT;

    if (!is_numeric($language_id)) $language_id = $languages_id;

    $status_query = tep_db_query("select return_reason_name from " . TABLE_RETURN_REASONS . " where return_reason_id = '" . $return_reason_id . "' and language_id = '" . $language_id . "'");
    $status = tep_db_fetch_array($status_query);

    return $status['return_reason_name'];
  }



    function tep_calculate_deduct($price, $tax) {
    global $currencies;

    return (($price / 100) * $tax);
  }




function tep_get_returns_status() {
    global $languages_id;

    $orders_status_array = array();
    $orders_status_query = tep_db_query("select returns_status_id, returns_status_name from " . TABLE_RETURNS_STATUS . " where language_id = '" . $languages_id . "' order by returns_status_id");
    while ($orders_status = tep_db_fetch_array($orders_status_query)) {
      $orders_status_array[] = array('id' => $orders_status['returns_status_id'],
                                     'text' => $orders_status['returns_status_name']
                                    );
    }

    return $orders_status_array;
  }


function tep_get_returns_status_name($returns_status_id, $language_id = '') {
    global $languages_id;

    if ($returns_status_id < 1) return TEXT_DEFAULT;

    if (!is_numeric($language_id)) $language_id = $languages_id;

    $status_query = tep_db_query("select returns_status_name from " . TABLE_RETURNS_STATUS . " where returns_status_id = '" . $returns_status_id . "' and language_id = '" . $language_id . "'");
    $status = tep_db_fetch_array($status_query);

    return $status['returns_status_name'];
  }

       function tep_get_refund_method() {
    global $languages_id;

    $orders_status_array = array();
    $orders_status_query = tep_db_query("select refund_method_id, refund_method_name from " . TABLE_REFUND_METHOD . " where language_id = '" . $languages_id . "' order by refund_method_id");
    while ($orders_status = tep_db_fetch_array($orders_status_query)) {
      $orders_status_array[] = array('id' => $orders_status['refund_method_id'],
                                     'text' => $orders_status['refund_method_name']
                                    );
    }

    return $orders_status_array;
  }


function tep_get_refund_method_name($refund_method_id, $language_id = '') {
    global $languages_id;

    if ($refund_method_id < 1) return TEXT_DEFAULT;

    if (!is_numeric($language_id)) $language_id = $languages_id;

    $status_query = tep_db_query("select refund_method_name from " . TABLE_REFUND_METHOD . " where refund_method_id = '" . $refund_method_id . "' and language_id = '" . $language_id . "'");
    $status = tep_db_fetch_array($status_query);

    return $status['refund_method_name'];
  }



	function tep_remove_return($returns_id, $restock=false) {
	
		$returns_id = (int)$returns_id;

		$returns_query = tep_db_query("SELECT order_id, products_id, products_quantity 
									   FROM " . TABLE_RETURNS_PRODUCTS_DATA . " 
									   WHERE returns_id = '" . $returns_id . "'
									  ");

		while ($returns = tep_db_fetch_array($returns_query)) {

			if ($restock == 'on') {
				tep_db_query("UPDATE " . TABLE_PRODUCTS . " 
							  SET products_quantity = products_quantity + " . $returns['products_quantity'] . ", 
							  last_stock_change = NOW() 
							  WHERE products_id = '" . $returns['products_id'] . "'
							 ");

			/*	tep_db_query("UPDATE " . TABLE_PRODUCTS . " 
							  SET products_status = 1 
							  WHERE products_quantity > 0 
							  AND products_id = '".$returns['products_id']."'
							");
			*/

			} // # END if $restock=on check

			tep_db_query("UPDATE ".TABLE_ORDERS_PRODUCTS." 
						  SET products_returned = '0', 
						  products_exchanged = '0',
						  exchange_returns_id = NULL
						  WHERE exchange_returns_id = '" . $returns_id . "'
    					");

			tep_db_query("DELETE FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id = '".$returns['order_id']."' AND class = 'ot_returns'");

		} // # END while	

	
		tep_db_query("DELETE FROM " . TABLE_RETURNS . " WHERE returns_id = '" . $returns_id . "'");
		tep_db_query("DELETE FROM " . TABLE_RETURNS_STATUS_HISTORY . " WHERE returns_id = '" . $returns_id . "'");
		tep_db_query("DELETE FROM " . TABLE_RETURNS_PRODUCTS_DATA . " WHERE returns_id = '" . $returns_id . "'");
		tep_db_query("DELETE FROM " . TABLE_RETURN_PAYMENTS . " WHERE returns_id = '" . $returns_id . "'");
	}
