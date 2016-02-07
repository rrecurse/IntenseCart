<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	define ('LOCAL_TIMEZONE', date_default_timezone_get ());
	date_default_timezone_set ('UTC');


class sales_report {

    function __construct($status='', $dateMode='', $date_from='', $date_to='', $channel='') {

		$this->channel = $channel;
		$this->dateMode = $dateMode;

		// # date_from and date_to have to be a unix timestamp. Use mktime !
		// # if set then both have to be valid date_from and date_to
	
		$this->date_from = (!empty($date_from) ? $date_from : date('01/01/Y 00:00:01'));
		$this->date_to = (!empty($date_to) ? $date_to : date('m/d/Y 23:59:59'));

		$this->date_from = 	new DateTime($this->date_from);
		$this->date_to = new DateTime($this->date_to);

		$this->previous = '';
		$this->next = '';
		$this->status = $status;
		$this->info = array(array());

      // # get date of first sale
		$this->globalStartDate = date("01/01/Y 00:00:01", strtotime(tep_db_result(tep_db_query("SELECT MIN(date_purchased) FROM " . TABLE_ORDERS),0)));

		// # start our loop value.
		$i = 0;


		if($this->status === '0' || $this->status > 0) {
			$this->filter_sql = " AND o.orders_status='".$this->status."'";
		} elseif($this->status === 'all') {
			$this->filter_sql = "";
		} else {	
			$this->filter_sql = "";
		}

		// # switch through date modes
        switch($this->dateMode) {

			// # hourly
			case 'H':

				$this->size = 24;

				for ($i = 0; $i < $this->size; $i++) {

					$this->dates_from[$i] = mktime(0, 0, 0, $this->date_from->format('m'), $this->date_from->format('d'), $this->date_from->format('Y'));

					$this->dates_to[$i] = new DateTime(date('m/d/Y H:i:s',$this->dates_from[$i]));

					$this->dates_to[$i] = $this->dates_to[$i]->modify('+ '.($i + 1) .' hours');
	
					$this->dates_from[$i] = new DateTime(date('m/d/Y H:i:s',$this->dates_from[$i]));

					$this->dates_from[$i] = $this->dates_from[$i]->modify('+ '.$i.' hours');

					$this->info[$i]['text'] = $this->dates_from[$i]->format('ga') . ' - ' . $this->dates_to[$i]->format('ga');

					$this->info[$i]['link'] = 'datemode=H&date_from='.$this->dates_from[$i]->format('m/d/Y').'&date_to='.$this->dates_to[$i]->format('m/d/Y').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');

				}


				$prevStart = date('m/d/Y',$this->date_from->getTimestamp() - $this->date_to->format('d'));
    		    $prevEnd = date('m/d/Y',$this->date_from->getTimestamp() - $this->date_to->format('d'));

        		$nextStart = $this->date_from->modify('+1 day');
				$nextStart = $nextStart->format('m/d/Y');

    	    	$nextEnd = date('m/d/Y',$this->date_from->getTimestamp() + 1);
				
				if (date('Y',strtotime($prevStart)) >= date('Y', $this->globalStartDate)) {
    	    	  $this->previous = "datemode=" . $this->dateMode . "&date_from=" . $prevStart . "&date_to=" . $prevEnd;
    		    }
	
    		    if (date('Y',strtotime($nextEnd)) <= date('Y')) {
					$this->next = "datemode=" . $this->dateMode . "&date_from=" . $nextStart . "&date_to=" . $nextEnd;
				} 

			break;

			// # daily
			case 'd':

				$this->size = date('t', $this->date_to->getTimestamp());

				for ($i = 0; $i < $this->size; $i++) {
					$this->dates_from[$i] = mktime(0, 0, 0, $this->date_from->format('m'), $this->date_from->format('01') +  $i, $this->date_from->format('Y'));

					$this->dates_to[$i] = new DateTime(date('m/d/Y',$this->dates_from[$i]));

					$this->dates_to[$i] = $this->dates_to[$i]->modify( '+ 23 hours' );
	
					$this->dates_from[$i] = new DateTime(date('m/d/Y',$this->dates_from[$i]));

					$this->info[$i]['text'] = $this->dates_from[$i]->format('M j');

					$this->info[$i]['link'] = 'datemode=H&date_from='.$this->dates_from[$i]->format('m/d/Y').'&date_to='.$this->dates_to[$i]->format('m/d/Y').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');

				}

				$prevStart = date('m/01/Y',$this->date_from->getTimestamp() - $this->date_to->format('t'));
    		    $prevEnd = date('m/t/Y',$this->date_from->getTimestamp() - $this->date_to->format('t'));

        		$nextStart = $this->date_from->modify('+1 month');
				$nextStart = $nextStart->format('m/01/Y');

    	    	$nextEnd = date('m/t/Y',$this->date_from->getTimestamp() + 364);
				
				if (date('Y',strtotime($prevStart)) >= date('Y', $this->globalStartDate)) {
    	    	  $this->previous = "datemode=" . $this->dateMode . "&date_from=" . $prevStart . "&date_to=" . $prevEnd;
    		    }
	
    		    if (date('Y',strtotime($nextEnd)) <= date('Y')) {
					$this->next = "datemode=" . $this->dateMode . "&date_from=" . $nextStart . "&date_to=" . $nextEnd;
				} 

			break;

			// # weekly
			case 'w':

				$this->size = ceil($this->date_to->format('t') / 7);

				$tmpMonth = $this->date_from->format('m');
        		$tmpYear = $this->date_from->format('Y');

				for ($i = 0; $i < $this->size; $i++) {

            	$this->dates_from[$i] = mktime(0, 0, 0, $this->date_from->format('m'), $this->date_from->format('d') +  $i * 7, $this->date_from->format('Y'));
    			
					$this->dates_to[$i] = new DateTime(date('m/d/Y',$this->dates_from[$i]));

					$this->dates_to[$i] = $this->dates_to[$i]->modify( '+ 6 days' );
	
					$this->dates_from[$i] = new DateTime(date('m/d/Y',$this->dates_from[$i]));


					if($this->dates_to[$i]->getTimestamp() + 6 > $this->dates_to[$i]->getTimestamp()) { 

   						if ($i == $this->size - 1) { // last
					
							$this->dates_to[$i] = $this->dates_to[$i]->modify($this->dates_from[$i]->format('m/t/Y'));
						}
					}

					$this->info[$i]['text'] = $this->dates_from[$i]->format('m/d/Y') . ' - ' . $this->dates_to[$i]->format('m/d/Y');

					$this->info[$i]['link'] = 'datemode=d&date_from='.$this->dates_from[$i]->format('m/d/Y').'&date_to='.$this->dates_to[$i]->format('m/d/Y').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');

				}

				$prevStart = date('m/01/Y',$this->date_from->getTimestamp() - $this->date_to->format('t'));
    		    $prevEnd = date('m/t/Y',$this->date_from->getTimestamp() - $this->date_to->format('t'));

        		$nextStart = $this->date_from->modify('+1 month');
				$nextStart = $nextStart->format('m/01/Y');

    	    	$nextEnd = date('m/t/Y',$this->date_from->getTimestamp() + 364);
				
				if (date('Y',strtotime($prevStart)) >= date('Y', $this->globalStartDate)) {
    	    	  $this->previous = "datemode=" . $this->dateMode . "&date_from=" . $prevStart . "&date_to=" . $prevEnd;
    		    }
	
    		    if (date('Y',strtotime($nextEnd)) <= date('Y')) {
					$this->next = "datemode=" . $this->dateMode . "&date_from=" . $nextStart . "&date_to=" . $nextEnd;
				} 

			break;

			// # monthly
			case 'm':
			case 'default':

				$this->size = 12;

		        $tmpMonth = $this->date_from->format('m');
        		$tmpYear = $this->date_from->format('Y');

				for ($i = 0; $i < $this->size; $i++) {

					// # the first of the $tmpMonth + $i
					$this->dates_from[$i] = mktime(0, 0, 0, $tmpMonth + $i, 1, $tmpYear);
					// # the last of the $tmpMonth

					$this->dates_to[$i] = new DateTime(date('m/d/Y',$this->dates_from[$i]));

					$this->dates_to[$i] = $this->dates_to[$i]->modify( 'last day of this month' );
	
					$this->dates_from[$i] = new DateTime(date('m/d/Y',$this->dates_from[$i]));


					$this->info[$i]['text'] = $this->dates_from[$i]->format('M y');

					$this->info[$i]['link'] = 'datemode=w&date_from='.$this->dates_from[$i]->format('m/d/Y').'&date_to='.$this->dates_to[$i]->format('m/d/Y').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');

				}

				$prevStart = date('01/01/Y',$this->date_from->getTimestamp() - 364);
    		    $prevEnd = date('12/31/Y',$this->date_from->getTimestamp() - 364);

        		$nextStart = $this->date_from->modify('+1 year');
				$nextStart = $nextStart->format('m/d/Y');

    	    	$nextEnd = date('12/31/Y',$this->date_from->getTimestamp() + 364);
				
				if (date('Y',strtotime($prevStart)) >= date('Y', $this->globalStartDate)) {
    	    	  $this->previous = "datemode=" . $this->dateMode . "&date_from=" . $prevStart . "&date_to=" . $prevEnd;
    		    }
	
    		    if (date('Y',strtotime($nextEnd)) <= date('Y')) {
					$this->next = "datemode=" . $this->dateMode . "&date_from=" . $nextStart . "&date_to=" . $nextEnd;
				} 

			break;

			// # yearly
			case 'Y':

		        $tmpMonth = $this->date_from->format('m');
        		$tmpYear = $this->date_from->format('Y');
        		
				$this->size = date('Y',$this->date_to->getTimestamp()) + 1 - date('Y',$this->date_from->getTimestamp());

				for ($i = 0; $i < $this->size; $i++) {

					// # the first of the $tmpMonth + $i
					$this->dates_from[$i] = mktime(0, 0, 0, 1, 1, $tmpYear + $i);
					// # the last of the $tmpMonth

					$this->dates_to[$i] = new DateTime(date('12/31/Y',$this->dates_from[$i]));

					$this->dates_to[$i] = $this->dates_to[$i]->modify('last day of this year');
	
					$this->dates_from[$i] = new DateTime(date('m/d/Y',$this->dates_from[$i]));

					$this->info[$i]['text'] = $this->dates_from[$i]->format('Y');

					$this->info[$i]['link'] = 'datemode=m&date_from='.$this->dates_from[$i]->format('m/d/Y').'&date_to='.$this->dates_to[$i]->format('m/d/Y').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');

				}
	
			break;
        }

		// # Channel Source

        switch($this->channel) {

          // # Amazon
          case 'amazon':
            $this->channel = " AND (o.orders_source = 'dbfeed_amazon_us' OR o.orders_source LIKE '%amazon%' OR o.customers_name LIKE 'Amazon%') ";
            break;

          // # eBay
          case 'ebay':
            $this->channel = " AND o.orders_source LIKE '%ebay%' ";
            break;

          // # E-Mail
          case 'email':
            $this->channel = " AND o.orders_source LIKE 'email%' ";
            break;

         // # Retail sales
          case 'retail':
            $this->channel = " AND o.customers_name NOT LIKE 'Amazon%' AND o.orders_source != 'vendor' AND o.orders_source NOT LIKE '%amazon%' ";
            break;

         // # Vendor sales
          case 'vendor':
            $this->channel = " AND o.orders_source LIKE 'vendor' ";
            break;

         // # all
          case 'default':
            $this->channel =  '';
            break;

        }

		// # now execute the main query function below
		$this->query();

	}


    function query() {

        $tmp_query = "SELECT SUM(ot.value) AS order_sum, 
					  COUNT(ot.value) AS order_count,
					  AVG(ot.value) AS order_avg,
					  SUM(theitems) AS items
				  	  FROM " . TABLE_ORDERS . " o
				  	  LEFT JOIN " . TABLE_ORDERS_TOTAL . " ot ON ot.orders_id = o.orders_id
					  LEFT JOIN (SELECT orders_id, SUM(products_quantity) AS theitems FROM orders_products WHERE products_returned = 0 GROUP BY orders_id
								) op ON o.orders_id = op.orders_id
					  WHERE ot.class = 'ot_total' 
				 	  " . $this->filter_sql .
					  $this->channel;
//var_dump($tmp_query);
		for ($i = 0; $i < $this->size; $i++) {

			if($this->dateMode == 'H') { 
	
		        $report_query = tep_db_query($tmp_query . " AND o.date_purchased >= '" . $this->dates_from[$i]->format('Y-m-d H:i:s') . "' AND o.date_purchased <= '" . $this->dates_to[$i]->format('Y-m-d H:i:s') . "'");

			} else { 

    		    $report_query = tep_db_query($tmp_query . " AND o.date_purchased >= '" . $this->dates_from[$i]->format('Y-m-d 00:00:01') . "' AND o.date_purchased <= '" . $this->dates_to[$i]->format('Y-m-d 23:59:59') . "'");

			}

        	$report = tep_db_fetch_array($report_query);
			tep_db_free_result($report_query);

			$this->info[$i]['order_sum'] = $report['order_sum'];
        	$this->info[$i]['items'] = !empty($report['items']) ? $report['items'] : 0;
        	$this->info[$i]['order_count'] = $report['order_count'];
    	    $this->info[$i]['order_avg'] = $report['order_avg'];

		}

		$tmp_query =  "SELECT SUM(ot.value) AS shipping 
					   FROM " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS . " o 
					   WHERE ot.orders_id = o.orders_id 
					   AND ot.class = 'ot_shipping'";

		for ($i = 0; $i < $this->size; $i++) {
        
			$report_query = tep_db_query($tmp_query . " 
										 AND o.date_purchased >= '" . $this->dates_from[$i]->format('Y-m-d H:i:s') . "' 
										 AND o.date_purchased < '" . $this->dates_to[$i]->format('Y-m-d H:i:s') . "' ");

			$report = tep_db_fetch_array($report_query);
			tep_db_free_result($report_query);

			$this->info[$i]['shipping'] = $report['shipping'];

		}
	}

	
	function export($filter='', $dateMode='', $date_from='', $date_to='', $channel='') {

		$filename = SITE_DOMAIN.'_sales_stats_'.$this->date_from->format('m-d-Y').'_to_'.$this->date_to->format('m-d-Y').'.csv';
		$filename = str_replace('www.','',$filename);

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename=' . $filename);

   		$header[] = 'Date, No. of Orders, No. of Items, Avg. Order Amount, Total Amount';

		print implode(',', $header) . "\r\n";

		if($this->dateMode == 'H') { 
			$datemode = '%H:00:00';
		} elseif($this->dateMode == 'Y') {
			$datemode = '%Y';
		} elseif($this->dateMode == 'd') {
			$datemode = '%m/%d/%Y';
		} elseif($this->dateMode == 'w') {
			$datemode = 'week %v %x'; 
		} elseif($this->dateMode == 'm') { 
			$datemode = '%b - %Y';	
		}

        $export_query1 = "SELECT DATE_FORMAT(o.date_purchased, '".$datemode."') AS 'Date',
						 		 COUNT(ot.value) AS 'No. of Orders',
								 SUM(theitems) AS 'No. of Items',
						 		 CONCAT('$', FORMAT(AVG(ot.value), 2)) AS 'Avg. Order Amount',
								 CONCAT('$', FORMAT(SUM(ot.value), 2)) AS 'Total Amount'
					  	  FROM " . TABLE_ORDERS . " o
					  	  LEFT JOIN " . TABLE_ORDERS_TOTAL . " ot ON ot.orders_id = o.orders_id
						  LEFT JOIN (SELECT orders_id, SUM(products_quantity) AS theitems FROM orders_products GROUP BY orders_id
									) op ON o.orders_id = op.orders_id
						  WHERE ot.class = 'ot_total' 
					 	  " . $this->filter_sql .
						  $this->channel;

		for ($i = 0; $i < $this->size; $i++) {

    	    $export_query = tep_db_query($export_query1 . " AND o.date_purchased >= '" . $this->dates_from[$i]->format('Y-m-d 00:00:01') . "' AND o.date_purchased <= '" . $this->dates_to[$i]->format('Y-m-d 23:59:59') . "'");

			$row = tep_db_fetch_array($export_query);
			tep_db_free_result($export_query);


    		foreach ($row as $value) {
    	    	$values[] = '"' . str_replace('"', '""', html_entity_decode(strip_tags($value))) . '"';
		    }
			print implode(',', $values) . "\r\n";
		    unset($values);

		}

		exit();

	}


  } // # END class
?>