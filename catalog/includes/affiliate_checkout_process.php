<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	// #fetch the net total of an order
	$affiliate_total = 0;

	for($i=0, $n=sizeof($order->products); $i<$n; $i++) {
		$affiliate_total += $order->products[$i]['final_price'] * $order->products[$i]['qty'];
	}

	$totals_query = tep_db_query("select * from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$insert_id . "' order by sort_order");

	$discount='0';

	while ($totals = tep_db_fetch_array($totals_query)) {
          if($totals['class'] == "ot_coupon") {
                  $discount -= $totals['value'];
          }
	}

	$affiliate_total -=  $discount;  

	if($affiliate_total < 0) {
		$affiliate_total = 0;
	}
	
	// # end subtract discount

	$affiliate_total = tep_round($affiliate_total, 2);

	$affiliate_percent = 0;

	$com_query = tep_db_query("SELECT c.customers_referred_by,
									  cg.group_affiliate_com_first,
									  cg.group_affiliate_com_next 
								FROM ".TABLE_CUSTOMERS." c
								LEFT JOIN ".TABLE_CUSTOMERS_GROUPS." cg ON c.customers_group_id = cg.customers_group_id
								WHERE c.customers_id = '$customer_id'
							  ");

	if($com_row = tep_db_fetch_array($com_query)) {

		if(!empty($com_row['customers_referred_by'])) {
			$aff_com_id = (int)$com_row['customers_referred_by'];
			$aff_returned = ($com_row['customers_referred_by'] != $affiliate_ref);
    } else {
      $aff_com_id=$affiliate_ref;
      $aff_returned=0;
    }

	$affiliate_percent = $aff_com_id?$com_row[$aff_returned?'group_affiliate_com_next':'group_affiliate_com_first']:0;
  }
  
// Check for individual commission
  if (AFFILATE_INDIVIDUAL_PERCENTAGE == 'true') {
    $affiliate_commission_query = tep_db_query ("select affiliate_commission_percent from " . TABLE_AFFILIATE . " where affiliate_id = '" . $aff_com_id . "'");
    $affiliate_commission = tep_db_fetch_array($affiliate_commission_query);
  //  $affiliate_percent *= 1+$affiliate_commission['affiliate_commission_percent']/100;
	$affiliate_percent = $affiliate_commission['affiliate_commission_percent'];

  }
//  if ($affiliate_percent < AFFILIATE_PERCENT) $affiliate_percent = AFFILIATE_PERCENT;
  $affiliate_payment = tep_round(($affiliate_total * $affiliate_percent / 100), 2);
  
  if ($affiliate_payment>0.005) {
    $sql_data_array = array('affiliate_id' => $aff_com_id,
                            'affiliate_date' => $affiliate_clientdate,
                            'affiliate_browser' => $affiliate_clientbrowser,
                            'affiliate_ipaddress' => $affiliate_clientip,
                            'affiliate_value' => $affiliate_total,
                            'affiliate_payment' => $affiliate_payment,
                            'affiliate_orders_id' => $insert_id,
                            'affiliate_clickthroughs_id' => $affiliate_clickthroughs_id,
                            'affiliate_percent' => $affiliate_percent,
                            'affiliate_salesman' => $affiliate_ref);
    tep_db_perform(TABLE_AFFILIATE_SALES, $sql_data_array);

    if (AFFILATE_USE_TIER == 'true') {
      $affiliate_tiers_query = tep_db_query ("SELECT aa2.affiliate_id, (aa2.affiliate_rgt - aa2.affiliate_lft) as height
                                                      FROM affiliate_affiliate AS aa1, affiliate_affiliate AS aa2
                                                      WHERE  aa1.affiliate_root = aa2.affiliate_root 
                                                            AND aa1.affiliate_lft BETWEEN aa2.affiliate_lft AND aa2.affiliate_rgt
                                                            AND aa1.affiliate_rgt BETWEEN aa2.affiliate_lft AND aa2.affiliate_rgt
                                                            AND aa1.affiliate_id =  '" . $affiliate_ref . "'
                                                      ORDER by height asc limit 1, " . AFFILIATE_TIER_LEVELS . " 
                                              ");
      $affiliate_tier_percentage = split("[;]" , AFFILIATE_TIER_PERCENTAGE);
      $i=0;
      while ($affiliate_tiers_array = tep_db_fetch_array($affiliate_tiers_query)) {

        $affiliate_tier_percent = $affiliate_tier_percentage[$i]*$affiliate_percent;
        $affiliate_payment = tep_round(($affiliate_total * $affiliate_tier_percent / 100), 2);
        if ($affiliate_payment > 0.005) {
          $sql_data_array = array('affiliate_id' => $affiliate_tiers_array['affiliate_id'],
                                  'affiliate_date' => $affiliate_clientdate,
                                  'affiliate_browser' => $affiliate_clientbrowser,
                                  'affiliate_ipaddress' => $affiliate_clientip,
                                  'affiliate_value' => $affiliate_total,
                                  'affiliate_payment' => $affiliate_payment,
                                  'affiliate_orders_id' => $insert_id,
                                  'affiliate_clickthroughs_id' => $affiliate_clickthroughs_id,
                                  'affiliate_tier_percent' => $affiliate_percent,
                                  'affiliate_salesman' => $affiliate_ref);
          tep_db_perform(TABLE_AFFILIATE_SALES, $sql_data_array);
        }
        $i++;
      }
    }
  }
?>
