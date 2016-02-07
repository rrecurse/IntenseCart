<?php

  class shoppingCart {
    var $contents, $total, $weight, $cartID, $content_type;

	function __construct() {
		global $customer_id;
		
		if($customer_id) $this->customer_id = $customer_id;
		$this->reset();
    }

    function restore_contents() {
      global $customer_id, $gv_id, $REMOTE_ADDR;

		if (!isset($_SESSION['customer_id'])) return false;

		$bask = array();

		$customer_group_id = (tep_session_is_registered ('sppc_customer_group_id') ? $_SESSION['sppc_customer_group_id'] : '0');

		$products_query = tep_db_query("SELECT p.products_id, 
											   p.products_status,
											   p.products_price,
											   cb.customers_basket_quantity,
											   cb.customers_basket_id,
											   cb.final_price,
											   pg.customers_group_price
										FROM " . TABLE_CUSTOMERS_BASKET . " cb 
										LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = cb.products_id 
										LEFT JOIN " . TABLE_PRODUCTS_GROUPS . " pg ON (pg.products_id = cb.products_id AND pg.customers_group_id = '". $customer_group_id ."')
										WHERE cb.customers_id = '" . (int)$customer_id . "'
										");

		if(tep_db_num_rows($products_query) > 0) { 

			while ($products = tep_db_fetch_array($products_query)) {

				// # Eliminate disabled items from customers basket
				if($products['products_status'] == 0 || ($products['products_price'] == 0 && $products['customers_group_price'] == 0)) {

					tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_BASKET . " 
								  WHERE customers_id = '" . (int)$customer_id . "' 
								  AND products_id = '" . $products['products_id'] . "'
								 ");

					tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " 
								  WHERE customers_id = '" . (int)$customer_id . "' 
								  AND products_id = '" . $products['products_id'] . "'
								 ");

					$this->remove($products['products_id']);
				}


	    	    $this->contents[] = array('id' => $products['products_id'],
										  'qty' => $products['customers_basket_quantity'],
										  'bask_qty' => $products['customers_basket_quantity'],
										  'bask_id' => $products['customers_basket_id'],
										  'attributes' => array(),
										  'final_price' => $products['final_price']
										  );

				$bask[$products['customers_basket_id']] = &$this->contents[sizeof($this->contents)-1];

			}

		}

		$attributes_query = tep_db_query("SELECT * FROM " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " WHERE customers_id = '" . $customer_id . "'");
	
		if(tep_db_num_rows($attributes_query) > 0) { 

			while ($attributes = tep_db_fetch_array($attributes_query)) {

				if (isset($bask[$attributes['customers_basket_id']])) {

					$bask[$attributes['customers_basket_id']]['attributes'][$attributes['products_options_id']] = $attributes['products_options_value_id'];
				}
			}
		}

		// # insert current cart contents in database
		if(is_array($this->contents)) {

			foreach(array_keys($this->contents) as $pid) {
				$qty = $this->contents[$pid]['qty'];
				$products_id = $this->contents[$pid]['id'];
				$final_price = $this->contents[$pid]['final_price'];

				$product_query = tep_db_query("SELECT products_id 
											   FROM " . TABLE_CUSTOMERS_BASKET . " 
											   WHERE customers_id = '" . (int)$customer_id . "' 
											   AND products_id = '" . tep_db_input($products_id) . "'
											  ");

				if(tep_db_num_rows($product_query) < 1) {

					tep_db_query("INSERT INTO " . TABLE_CUSTOMERS_BASKET . " 
								  SET customers_id = '" . (int)$customer_id . "', 
								  products_id = '" . tep_db_input($products_id) . "', 
								  customers_basket_quantity = '" . tep_db_input($qty) . "', 
								  final_price = '" . tep_db_input($final_price) . "', 
								  customers_basket_date_added = NOW()
								");

					if (isset($this->contents[$pid]['attributes'])) {
						foreach ($this->contents[$pid]['attributes'] as $option => $value) {
							tep_db_query("insert into " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " (customers_id, products_id, products_options_id, products_options_value_id) values ('" . (int)$customer_id . "', '" . tep_db_input($products_id) . "', '" . (int)$option . "', '" . (int)$value . "')");
						}
					}

				} else {

					tep_db_query("UPDATE " . TABLE_CUSTOMERS_BASKET . " 
							 	  SET customers_basket_quantity = '" . tep_db_input($qty) . "' 
								  WHERE customers_id = '" . (int)$customer_id . "' 
								  AND products_id = '" . tep_db_input($products_id) . "'
								 ");
				}
			}
		}


      $this->cleanup();
    }

   	function reset($reset_database = false) {
		global $customer_id;

		$this->contents = array();
		$this->total = 0;
		$this->weight = 0;
		$this->content_type = false;

		if (isset($_SESSION['customer_id']) && ($reset_database == true)) {
			tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "'");
			tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id . "'");
		}
		
		unset($this->cartID);
		if (isset($_SESSION['cartID'])) unset($_SESSION['cartID']);
	}


    function add_cart($products_id, $qty = 1, $attributes = NULL, $notify = true) {
      global $customer_id, $languages_id;

		 // # scrub any characters that arent numbers.
		$products_id = preg_replace('/[^0-9]/', '',$products_id);
		$qty = preg_replace('/[^0-9]/', '',$qty);

		// # ensure we have a result in the database and it is active and not zero priced prior to adding to cart.
		$product_check = tep_db_query("SELECT p.products_id 
									   FROM ". TABLE_PRODUCTS ." p 
									   LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON pg.products_id = p.products_id 
									   WHERE p.products_id = ". (int)$products_id ." 
									   AND p.products_status = '1'
									   AND (p.products_price > 0 OR pg.customers_group_price > 0)
									  ");

		if(tep_db_num_rows($product_check) > 0) {

    		$pf = new PriceFormatter;
			$pf->loadProduct($products_id, $languages_id);
			$qty = $pf->adjustQty($qty);

			if ($in_q=$this->get_quantity($products_id,$attributes)) {
				$this->update_quantity($products_id, $qty+$in_q, $attributes);
			} else {
		 
				$this->contents[] = array('id'=>$products_id,'qty'=>$qty,'attributes'=>is_array($attributes)?$attributes:array());
  
				$this->cleanup();

				// # assign a temporary unique ID to the order contents to prevent hack attempts during the checkout procedure
		        $this->cartID = $this->generate_cart_id();
			}
		
		} // # END num row check
		
		$this->last_added = $products_id;
	}


	function update_quantity($products_id, $quantity='', $attributes = NULL) {

		// #  nothing needs to be updated if theres no quantity, so we return true.
		if (!is_numeric($quantity)) return true;

		if ($quantity>0) {
			$pf = new PriceFormatter;
			$pf->loadProduct($products_id);
			$qunatity = $pf->adjustQty($quantity);
		}

		$q = $quantity;

		$bask = NULL;
		//$this->contents[$products_id] = array('qty' => $quantity);

		foreach ($this->contents AS $idx=>$pr) {
        	if ($pr['id']==$products_id && (!isset($attributes) || $attributes=='' || $attributes==$pr['cart_id'] || $attributes==$pr['attributes'])) {
				$this->contents[$idx]['qty']=$q;
				$q=0;
				$bask=$this->contents[$idx]['bask_id'];
			}
		}

		if(!$q) {
			$this->cleanup();
			return true;
		}

		return false;
	}


	function count_ship_contents() {

		// # get total number of items in cart

		$total_items = 0;

		if (is_array($this->contents)) {

			reset($this->contents);

			while (list($products_id, ) = each($this->contents)) {

			//foreach($this->contents AS $key => $products_id) {

				$check_free_ship_query = tep_db_query("SELECT products_free_shipping FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . (int)$products_id . "'");
				$check_free_ship = (tep_db_num_rows($check_free_ship_query) > 0 ? tep_db_result($check_free_ship_query,0) : 0);

				if($check_free_ship == 0) {
					$total_items += $this->get_quantity($products_id);
				}

			}
		}

		return $total_items;

	}


	function count_contents() {  // # get total number of items in cart
	    $total_items = 0;

		foreach ($this->contents AS $pr) {
			$total_items+=$pr['qty'];

			// # Scrub cart for inactive products.
			$products_id = $pr['id'];
			if(!$this->active($products_id)) $this->remove($products_id); 
			// # END scrub cart for inactive products.
		}
		return $total_items;
	}

	// # New function for checking active status of product.
	// # if product is disabled, then remove from visitors cart.
	function active($products_id) {
		global $sppc_customer_group_id;
		$isActive = true;

		if($sppc_customer_group_id > 1) {
			// # ensure we have a result in the database and it is active and not zero priced prior to adding to cart.
			$product_check = tep_db_query("SELECT p.products_id 
										   FROM ". TABLE_PRODUCTS ." p 
										   LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON pg.products_id = p.products_id 
										   WHERE p.products_id = ". (int)$products_id ." 
										   AND p.products_status = '1'
										   AND (p.products_price > 0 OR pg.customers_group_price > 0)
										  ");
		} else { 	
	
			// # ensure we have a result in the database and it is active and not zero priced prior to adding to cart.
			$product_check = tep_db_query("SELECT p.products_id 
										   FROM ". TABLE_PRODUCTS ." p 
										   LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON pg.products_id = p.products_id 
										   WHERE p.products_id = ". (int)$products_id ." 
										   AND p.products_status = '1'
										   AND p.products_price > 0
										  ");
		}

		if(tep_db_num_rows($product_check) < 1) $isActive = false;
		return $isActive;
	}


    function get_quantity($products_id,$attrs=NULL) {
      $qty=0;
      foreach ($this->contents AS $pr) if ($pr['id']==$products_id && (!isset($attrs) || $attrs==$pr['attributes'])) $qty+=$pr['qty'];
      return $qty;
    }


    function in_cart($products_id) {
      foreach ($this->contents AS $pr) if ($pr['id']==$products_id) return true;
      return false;
    }


    function findProduct($products_id,$attrs=NULL) {
      $pidx=NULL;
      foreach ($this->contents AS $idx=>$pr) if ($pr['id']==$products_id && (!isset($attrs) || $attrs==$pr['attributes'])) $pidx=$idx;
      return $pidx;
    }


    function remove($products_id,$attrs=NULL) {
      $this->update_quantity($products_id,0,$attrs);
      $this->cartID = $this->generate_cart_id();
    }

    function remove_all() {
      $this->reset();
    }

    function get_product_id_list() {
      $pids=Array();
      foreach ($this->contents AS $pr) if (isset($pr['id'])) $pids[]=$pr['id'];
      return join(', ',$pids);
    }

	function calculate() {

		$this->total_virtual = 0; // CCGV
		$this->total = 0;
		$this->weight = 0;
		$this->weight_paid = 0;
		$this->free_shipping = 1;
		$this->multi_weight = array(array(qty => 1, weight => 0));

		if (!is_array($this->contents)) return 0;
		$pf = new PriceFormatter;

		foreach ($this->contents AS $idx => $pr) {

			$qty = $pr['qty'];

			if ($product = $pf->loadProduct($pr['id'], $languages_id, NULL, $pr['xsell'])) {

				$no_count = 1;

				$pd_query = tep_db_query("SELECT products_model,
												 products_free_shipping,
												 products_separate_shipping 
										  FROM " . TABLE_PRODUCTS . " 
										  WHERE products_id = '" . (int)$product['products_id'] . "'
										 ");

				$pd_result = tep_db_fetch_array($pd_query);

				if ($pd_result['products_free_shipping'] < 1 && $qty > 0) {
					$this->free_shipping = 0;
				}

    	    	if (preg_match('/^GIFT/', $pd_result['products_model'])) {
        	    	$no_count = 0;
	        	}

	    		$prid = $product['products_id'];
				$products_tax = tep_get_tax_rate($product['products_tax_class_id']);
				$products_price = $pf->computePrice($this->countProductQty($prid,$qty));

				$products_weight = str_replace(',', '', $product['products_weight']);
				$products_weight = (float)$products_weight;

				//$products_price=$pf->computePrice($qty);

				$this->total_virtual += tep_add_tax($products_price, $products_tax) * $qty * $no_count; // # CCGV
    			$this->weight_virtual += number_format(($qty * $products_weight) * $no_count, 2, '.', ''); // # CCGV
        		$this->total += tep_add_tax($products_price, $products_tax) * $qty;

				$this->weight += number_format(($qty * $products_weight), 2, '.', '');
        

				if (!$gv_result['products_free_shipping']) {
	    			$this->weight_paid += number_format(($qty * $products_weight), 2, '.', '');
		    		if ($gv_result['products_separate_shipping']) {
						$this->multi_weight[] = array(qty => $qty, weight => $products_weight);
			    	} else {
						$this->multi_weight[0]['weight'] += number_format(($qty * $products_weight), 2, '.', '');
					}
				}
			}
		}
	}

    function attributes_price($products_id) {
      return 0;
    }

	function get_product($idx) {
		// # global variable (session) $sppc_customer_group_id -> class variable cg_id
		
		global $sppc_customer_group_id, $languages_id;

		if(!tep_session_is_registered('sppc_customer_group_id')) {
			$this->cg_id = '0';
		} else {
			$this->cg_id = $sppc_customer_group_id;
		}

		$pf = new PriceFormatter;

		$products_id=$this->contents[$idx]['id'];

		if ($products = $pf->loadProduct($products_id, $languages_id,NULL,$this->contents[$idx]['xsell'])) {

        	$products_price = $pf->computePrice($this->countProductQty($this->contents[$idx]['id'],$this->contents[$idx]['qty']));

/*          
		$specials_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$prid . "' and status = '1'");
          if (tep_db_num_rows($specials_query)) {
            $specials = tep_db_fetch_array($specials_query);
            $products_price = $specials['specials_new_products_price'];
          } 
*/
	
			$specials_price = tep_get_products_special_price($prid);

			if (tep_not_null($specials_price)) {
	
				$products_price = $specials_price;
	
			} elseif ($this->cg_id != 0) {
	
				$customer_group_price_query = tep_db_query("SELECT customers_group_price FROM " . TABLE_PRODUCTS_GROUPS . " WHERE products_id = '" . (int)$products_id . "' AND customers_group_id =  '" . $this->cg_id . "'");
				if ($customer_group_price = tep_db_fetch_array($customer_group_price_query)) {
					$products_price = $customer_group_price['customers_group_price'];
				}
			}

			$products['id'] = $products_id;
			$products['cart_id'] = $this->contents[$idx]['cart_id'];
			$products['master_id'] = $products['master_products_id'];
    	    $products['name'] = $products['products_name'];
			$products['model'] = $products['products_model'];
			$products['image'] = $products['products_image'];
			$products['price'] = $products_price;
			$products['price_obj'] = $pf;
	
			if ($this->contents[$idx]['xsell']) $products['xsell'] = $this->contents[$idx]['xsell'];
    	    $products['quantity'] = $this->contents[$idx]['qty'];
			$products['weight'] =  number_format($products['products_weight'], 2, '.', '');
			$products['final_price'] = $products_price;
			$products['tax_class_id'] = $products['products_tax_class_id'];
			$products['attributes'] = (isset($this->contents[$idx]['attributes']) ? $this->contents[$idx]['attributes'] : '');
			$products['backorder'] = $this->contents[$idx]['backorder'];

			$products['warehouse_id'] = (!is_null($products['warehouse_id']) || $products['warehouse_id'] > 0 ? $products['warehouse_id'] : 1);

			return $products;
		}

		return NULL;
	}


	function get_products() {
  
      if (!is_array($this->contents)) return false;
	  // print "<pre>"; print_r($this->contents); print "</pre>";  
      $products_array = array();
      foreach ($this->contents AS $idx=>$pr) if ($products=$this->get_product($idx)) 
	 //print "<pre>"; print_r($this->get_product($idx)); print "</pre>"; 
	  //$this->get_product($idx);
	  $products_array[]=$products;
	// print "<pre>"; print_r($products_array); print "</pre>";  //die ("DEBUG");
      return $products_array;
    }

  function get_last_product() {
    return isset($this->last_added)?$this->get_product($this->findProduct($this->last_added)):NULL;
  }

    function show_total() {
      $this->calculate();

      return $this->total;
    }

    function show_weight() {
      $this->calculate();

      return $this->weight;
    }
    

	function show_multi_weight_line() {

		if (!is_array($this->multi_weight)) {
			$this->calculate();
		}

		$wt = array();

		foreach ($this->multi_weight AS $w) {
			if ($w['weight'] > 0 && $w['qty'] > 0) {
				$wt[] = ($w['qty'] != 1 ? $w['qty'].'x'.$w['weight'] : $w['weight']);
			}
		}

		return join(',', $wt);
	}

    function show_weight_paid() {
      $this->calculate();

      return $this->weight_paid;
    }


    function show_total_virtual() {
      $this->calculate();

      return $this->total_virtual;
    }

    function show_weight_virtual() {
      $this->calculate();

      return $this->weight_virtual;
    }

    function generate_cart_id($length = 5) {
      return tep_create_random_value($length, 'digits');
    }

    function get_content_type() {
      $this->content_type = false;

      if ( (DOWNLOAD_ENABLED == 'true') && ($this->count_contents() > 0) ) {
        reset($this->contents);
        while (list($products_id, ) = each($this->contents)) {
          if (isset($this->contents[$products_id]['attributes'])) {
            reset($this->contents[$products_id]['attributes']);
            while (list(, $value) = each($this->contents[$products_id]['attributes'])) {
              $virtual_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad where pa.products_id = '" . (int)$products_id . "' and pa.options_values_id = '" . (int)$value . "' and pa.products_attributes_id = pad.products_attributes_id");
              $virtual_check = tep_db_fetch_array($virtual_check_query);

              if ($virtual_check['total'] > 0) {
                switch ($this->content_type) {
                  case 'physical':
                    $this->content_type = 'mixed';

                    return $this->content_type;
                    break;
                  default:
                    $this->content_type = 'virtual';
                    break;
                }
              } else {
                switch ($this->content_type) {
                  case 'virtual':
                    $this->content_type = 'mixed';

                    return $this->content_type;
                    break;
                  default:
                    $this->content_type = 'physical';
                    break;
                }
              }
            }

          } elseif ($this->show_weight() == 0) {
            reset($this->contents);
            while (list($products_id, ) = each($this->contents)) {
              $virtual_check_query = tep_db_query("select products_weight from " . TABLE_PRODUCTS . " where products_id = '" . $products_id . "'");
              $virtual_check = tep_db_fetch_array($virtual_check_query);
              if ($virtual_check['products_weight'] == 0) {
                switch ($this->content_type) {
                  case 'physical':
                    $this->content_type = 'mixed';

                    return $this->content_type;
                    break;
                  default:
                    $this->content_type = 'virtual_weight';
                    break;
                }
              } else {
                switch ($this->content_type) {
                  case 'virtual':
                    $this->content_type = 'mixed';

                    return $this->content_type;
                    break;
                  default:
                    $this->content_type = 'physical';
                    break;
                }
              }
            }

          } else {
            switch ($this->content_type) {
              case 'virtual':
                $this->content_type = 'mixed';

                return $this->content_type;
                break;
              default:
                $this->content_type = 'physical';
                break;
            }
          }
        }
      } else {
        $this->content_type = 'physical';
      }

      return $this->content_type;
    }

    function unserialize($broken) {
      for(reset($broken);$kv=each($broken);) {
        $key=$kv['key'];
        if (gettype($this->$key)!="user function")
        $this->$key=$kv['value'];
      }
    }
	
	// # CCGV ADDED - START

    function count_contents_virtual() {  // get total number of items in cart disregard gift vouchers
      $total_items = 0;
      if (is_array($this->contents)) {
        reset($this->contents);
        while (list($products_id, ) = each($this->contents)) {

          $no_count = false;
          $gv_query = tep_db_query("select products_model from " . TABLE_PRODUCTS . " where products_id = '" . $products_id . "'");
          $gv_result = tep_db_fetch_array($gv_query);
          if (preg_match('/^GIFT/', $gv_result['products_model'])) {
            $no_count=true;
          }
          if (NO_COUNT_ZERO_WEIGHT == 1) {
            $gv_query = tep_db_query("select products_weight from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($products_id) . "'");
            $gv_result=tep_db_fetch_array($gv_query);
            if ($gv_result['products_weight']<=MINIMUM_WEIGHT) {
              $no_count=true;
            }
          }
          if (!$no_count) $total_items += $this->get_quantity($products_id);
        }
      }
      return $total_items;
    }
// CCGV ADDED - END

	function productMerge(&$dst,&$src) {
		if (isset($src['id']) && isset($dst['id']) && $src['id']==$dst['id'] && $src['attributes']==$dst['attributes']) {
			if (isset($this->customer_id) && isset($src['bask_id'])) {
				if (isset($dst['bask_id'])) {
				    if ($dst['bask_id']!=$src['bask_id']) return false;
				} else $dst['bask_id']=$src['bask_id'];
			}
		
			$dst['qty']+=$src['qty'];
			
			return true;
		}
		
		return false;
	}

	function cleanup() {
		global $customer_id;

		$xsell = array();
		$cont = array();

		foreach ($this->contents AS $pr) {
			$mf=0;

			if(isset($pr['id'])) {
				if(!isset($xsell[$pr['id']])) {
					$xsell[$pr['id']] = array();
				}

				for ($i=0;$i < $pr['qty']; $i++ ) $xsell[$pr['id']][] = array();

				foreach ($cont AS $cidx=>$pc) {
					if ($this->productMerge($cont[$cidx],$pr)) { 
						$mf=1; 
						break; 
					}
				}

				if(!isset($pr['cart_id'])) {

					if (!isset($nextid)) {
						$nextid=0;
						foreach ($this->contents AS $prnext) {
							$nextid = max($nextid, $prnext['cart_id']);
						}
					}
	
					$pr['cart_id']=++$nextid;
				}
	
			}
	
			if(!$mf) $cont[]=$pr;

    	  }
		
		// # Sync basket

		if(isset($customer_id)) { 

            // # retrieve customer group ID
            $customer_group_id = (tep_session_is_registered ('sppc_customer_group_id') ? $_SESSION['sppc_customer_group_id'] : '0');

			foreach ($cont AS $idx=>$pr) {
				$q = $pr['qty'];

				if($q != $pr['bask_qty']) {

					if(isset($pr['bask_id'])) {

						if($q > 0) {
							tep_db_query("UPDATE customers_basket SET customers_basket_quantity='".$q."' WHERE customers_basket_id='".$pr['bask_id']."' AND customers_id='".$customer_id."'");
						} else {
							tep_db_query("DELETE FROM customers_basket WHERE customers_basket_id='".$pr['bask_id']."' AND customers_id='".$customer_id."'");
							tep_db_query("DELETE FROM customers_basket_attributes WHERE customers_basket_id='".$pr['bask_id']."' AND customers_id='".$customer_id."'");
						}
					} else {

						$customers_price_query = tep_db_query ("SELECT pg.customers_group_price
																FROM " . TABLE_PRODUCTS_GROUPS . " pg 
																WHERE pg.customers_group_id = '" . $customer_group_id . "' 
																AND pg.products_id = '" . $pr['id'] . "'
															   ");

						if(tep_db_num_rows($customers_price_query) > 0) {

							$products_price =  tep_db_result($customers_price_query,0);

						} else { 

							$old_price_query = tep_db_query("SELECT p.products_price FROM ". TABLE_PRODUCTS ." p WHERE p.products_id = '".$pr['id']."'");
							$products_price = (tep_db_num_rows($old_price_query) > 0 ? tep_db_result($old_price_query,0) : 0);
						}

	
						if($products_price > 0) { 

							tep_db_query("INSERT INTO customers_basket
										   SET customers_id = '".$customer_id."',
										   products_id = '".$pr['id']."',
										   customers_basket_quantity = '".$q."',
										   final_price = $products_price,
										   customers_basket_date_added = NOW()
										");

							$bask = $cont[$idx]['bask_id'] = tep_db_insert_id();

							foreach ($pr['attributes'] AS $optn=>$val) {

								tep_db_query("INSERT INTO customers_basket_attributes
											  SET customers_id='".$customer_id."',
											  customers_basket_id='".$bask."',
											  products_id='".$pr['id']."',
											  products_options_id='".addslashes($optn)."',
											  products_options_value_id='".addslashes($val)."'
											");
							}
						}

					}
	
			$cont[$idx]['bask_qty']=$q;
		}
	}
}
	
      
      $xcont = array();
      foreach($cont AS $pr) {

        $q = $pr['qty'];

        if ($q>0 && isset($pr['id'])) {
          
			$qry = tep_db_query("SELECT IFNULL(p.products_id,x.products_id) AS products_id,x.price_percent,x.price_diff,x.price_limit FROM (products_xsell x,products mp) LEFT JOIN products p ON (p.master_products_id=x.products_id) WHERE (x.xsell_id=mp.master_products_id OR x.xsell_id=mp.products_id) AND mp.products_id='".$pr['id']."' AND (x.price_percent!=0 OR x.price_diff!=0) ORDER BY x.price_percent,x.price_diff");
          
			while ($row=tep_db_fetch_array($qry)) {
	    		$xq=0;
 
	    if (isset($xsell[$row['products_id']])) for ($i=0;isset($xsell[$row['products_id']][$i]) && $q>0;$i++) {
	      $xl=&$xsell[$row['products_id']][$i];
	      if (!array_key_exists($pr['id'],$xl)) {
	        $lmt=$row['price_limit']?-$row['price_limit']:NULL;
	        $l=max($lmt,$xl?max($xl):NULL);
		if (!isset($l) || $l<-sizeof($xl)) {
		  $xq++;
		  $q--;
		  $xl[]=$lmt;
		}
	      }
	    }
	    if ($xq) {
	      $prx=$pr;
	      $prx['qty']=$xq;
	      $prx['xsell']=$row['products_id'];
	      $xcont[]=$prx;
	    }
	  }
	}
	if ($q>0) {
	  $prx=$pr;
	  $prx['qty']=$q;
	  $prx['xsell']=NULL;
	  $xcont[]=$prx;
	}
      }
 
      $this->contents=$xcont;
}

    function checkStock($force=0) {
      $dep=Array();
      return $this->checkStockDep($dep,$force);
    }
    function checkStockDep(&$dep,$force=0) {
      $qty=Array();
      $chk=true;
      foreach ($this->contents AS $idx=>$pr) if (isset($pr['id'])) $qty[$pr['id']]+=$pr['qty'];
      $prods=Array();
      $bkorder=Array();
      foreach ($qty AS $pid=>$q) {
        $prods[$pid]=IXproduct::load($pid);
	$prods[$pid]->initStock();
      }
      foreach ($prods AS $pid=>$prod) {
	if (isset($prod) && !$prods[$pid]->checkStockDep($dep,$qty[$pid],$force)) {
	  $bkorder[$pid]=1;
	  $chk=false;
	}
      }
      foreach ($this->contents AS $idx=>$pr) $this->contents[$idx]['backorder']=$bkorder[$pr['id']];
      return $chk;
    }
    
    function countProductQty($pid,$qty) {

	  $pid = preg_replace('/[^0-9]/i', '', $pid);
      $pids = IXdb::read("select products_id FROM products WHERE master_products_id=(SELECT master_products_id FROM products WHERE products_id='$pid')",Array(NULL),'products_id');
      if (!$pids) return $qty;
      $q=0;
      foreach ($this->contents AS $idx=>$pr) if (in_array($pr['id'],$pids)) $q+=$pr['qty'];
      return $q;
    }
  }
?>
