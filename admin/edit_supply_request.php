<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	// # Ajax suggest search 
	if(isset($_GET['search']) && !empty($_GET['search'])) {

		$thestr = (strlen($_GET['search']) > 2) ? tep_db_prepare_input($_GET['search']) : '';

		$query = tep_db_query("SELECT p2c.categories_id, spg.products_id, cd.categories_name 
								FROM categories_description cd
								JOIN products_to_categories p2c ON p2c.categories_id = cd.categories_id
								JOIN products_description pd ON pd.products_id = p2c.products_id
								JOIN products p ON p.products_id = p2c.products_id
								JOIN suppliers_products_groups spg ON spg.products_id = p2c.products_id
								WHERE (pd.products_name LIKE '%".$thestr."%' 
										OR p.products_model LIKE '%".$thestr."%' 
										OR p.products_upc LIKE '%".$thestr."%' 
										OR p.products_sku LIKE '%".$thestr."%' 
										OR spg.suppliers_sku LIKE '%".$thestr."%')
							");

		if(tep_db_num_rows($query) > 0) {

			$thejson = array();

			while($row = tep_db_fetch_array($query)) { 
	
				//error_log(print_r($row['products_id'],1));

				$thejson[] = array($row['categories_id'], $row['products_id'], $row['categories_name']);

			}

			echo json_encode($thejson);
		}

		exit();
    }

	// # END Ajax suggest search 

	$countries_pull_down = array();
	$countries_query=tep_db_query("SELECT countries_name FROM countries ORDER BY countries_id");
		
	while ($countries_row = tep_db_fetch_array($countries_query)) {
		$countries_pull_down[] = array(id => $countries_row['countries_name'], text => $countries_row['countries_name']);
	}

	require(DIR_WS_CLASSES . 'currencies.php');
	$currencies = new currencies();

	$sID = (int)$_GET['sID'];

	require(DIR_FS_CLASSES . 'supply_request.php');

	// # "Status History"

	$OldNewStatusValues = (tep_field_exists(TABLE_SUPPLY_REQUEST_STATUS_HISTORY, "old_value") && tep_field_exists(TABLE_SUPPLY_REQUEST_STATUS_HISTORY, "new_value"));

	$CommentsWithStatus = tep_field_exists(TABLE_SUPPLY_REQUEST_STATUS_HISTORY, "comments");
  
	$supply_request_statuses = array();
	$supply_request_status_array = array();

	$supply_request_status_query = tep_db_query("SELECT supply_request_status_id, supply_request_status_name 
												  FROM " . TABLE_SUPPLY_REQUEST_STATUS . " 
												  WHERE language_id = '" . (int)$languages_id . "'
												");

	while($supply_request_status = tep_db_fetch_array($supply_request_status_query)) {

		$supply_request_statuses[] = array('id' => $supply_request_status['supply_request_status_id'], 
										   'text' => $supply_request_status['supply_request_status_name']
											);
		$supply_request_status_array[$supply_request_status['supply_request_status_id']] = $supply_request_status['supply_request_status_name'];
	}

	$action = (isset($_GET['action']) ? $_GET['action'] : 'edit');

	// # Update Inventory Quantity
	$supply_request_query = tep_db_query("SELECT products_id, products_quantity 
								 FROM " . TABLE_SUPPLY_REQUEST_PRODUCTS . " 
								 WHERE supply_request_id = '" . (int)$sID . "'
								");

	if(tep_not_null($action)) {
		switch ($action) {
    	
	// # 1. UPDATE ORDER #####
	case 'update_request':
		

		$sID = tep_db_prepare_input($_GET['sID']);
		$supply_request = new supply_request($sID);
		$status = tep_db_prepare_input($_POST['status']);
		
		// # 1.1 UPDATE ORDER INFO #####



    $supplier_info_array = array('suppliers_name' => tep_db_prepare_input($_POST['update_supplier_name']),
							'suppliers_company' => tep_db_prepare_input($_POST['update_supplier_company']),
                            'suppliers_street_address' => tep_db_prepare_input($_POST['update_supplier_street_address']),
							'suppliers_suburb' => tep_db_prepare_input($_POST['update_supplier_suburb']),
							'suppliers_city' => tep_db_prepare_input($_POST['update_supplier_city']),
							'suppliers_postcode' => tep_db_prepare_input($_POST['update_supplier_postcode']),
							'suppliers_state' => tep_db_prepare_input($_POST['update_supplier_state']),
							'suppliers_country' => tep_db_prepare_input($_POST['update_supplier_country']),
							'suppliers_telephone' => tep_db_prepare_input($_POST['update_supplier_telephone']),
                            'suppliers_email_address' => tep_db_prepare_input($_POST['update_supplier_email_address']),
						
							'delivery_name' => tep_db_prepare_input($_POST['update_delivery_name']),
							'delivery_company' => tep_db_prepare_input($_POST['update_delivery_company']),
                            'delivery_street_address' => tep_db_prepare_input($_POST['update_delivery_street_address']),
							'delivery_suburb' => tep_db_prepare_input($_POST['update_delivery_street_suburb']),
							'delivery_city' => tep_db_prepare_input($_POST['update_delivery_city']),
							'delivery_postcode' => tep_db_prepare_input($_POST['update_delivery_postcode']),
							'delivery_state' => tep_db_prepare_input($_POST['update_delivery_state']),
							'delivery_country' => tep_db_prepare_input($_POST['update_delivery_country']),

							'payment_method' => tep_db_prepare_input($_POST['update_info_payment_method']),
							'cc_type' => tep_db_prepare_input($_POST['update_info_cc_type']),
							'cc_owner' => tep_db_prepare_input($_POST['update_info_cc_owner']),
							'cc_number' =>  tep_db_prepare_input($_POST['update_info_cc_number']),
							'comments' => tep_db_prepare_input($comments) 
							); 


		tep_db_perform(TABLE_SUPPLY_REQUEST, $supplier_info_array, 'update', "supply_request_id = '" . $sID . "'");


	// # 1.3 UPDATE PRODUCTS #####
		
		$RunningSubTotal = 0;
		$RunningTax = 0;

    // # Do pre-check for subtotal field existence (CWS)
		$ot_subtotal_found = false;
		
	foreach($_POST['update_totals'] as $total_details) {

		extract($total_details,EXTR_PREFIX_ALL,"ot");
			if($ot_class == "ot_subtotal") {
				$ot_subtotal_found = true;
				break;
			}
	}

	if(isset($_POST['supply_request_products_data']) && $_POST['supply_request_products_data'] != '') {

		$update_list = array();

		foreach(explode("\n",$_POST['supply_request_products_data']) AS $prod_data) {

			if (preg_match('/^(.+?):(.*?)\r?$/',$prod_data,$prod_data_ar)) {
		
				$update_ptr=&$update_list;

				foreach (explode(".",$prod_data_ar[1]) AS $update_idx) {
					if(!isset($update_ptr[$update_idx])) $update_ptr[$update_idx]=NULL;
					$update_ptr=&$update_ptr[$update_idx];
				}

				$update_ptr=$prod_data_ar[2];
			}
		}

		foreach ($update_list AS $update_prod) {

			$RunningSubTotal += $update_prod["qty"] * $update_prod["cost"];
			$RunningTax += (($update_prod["tax"]/100) * ($update_prod["qty"] * $update_prod["cost"]));

			$update_fields = array();

			if(isset($update_prod['supply_request_products_id']) && $update_prod['supply_request_products_id']) { 
				$update_cond = " supply_request_products_id='".$update_prod['supply_request_products_id']."' AND supply_request_id='$sID'";
			} else { 
				$update_cond='';
			}

			if ($update_prod['qty'] <= 0) {

				if($update_cond) {
					tep_db_query("DELETE FROM ".TABLE_SUPPLY_REQUEST_PRODUCTS." WHERE $update_cond");
					tep_db_query("DELETE FROM ".TABLE_SUPPLY_REQUEST_PRODUCTS_ATTRIBUTES." WHERE $update_cond");
				}

			} else {

				foreach(array(
					id=>'products_id',
					name=>'products_name',
					model=>'products_model',
					cost=>'cost_price',
					tax=>'products_tax',
					free_shipping=>'free_shipping',
					separate_shipping=>'separate_shipping',
					weight=>'products_weight',
					qty=>'products_quantity',
					comments=>'products_comments'
					) AS $post_key => $db_key)

				if(isset($update_prod[$post_key])) $update_fields[$db_key]=$update_prod[$post_key];
				
				if ($update_cond) {
	    		    tep_db_perform(TABLE_SUPPLY_REQUEST_PRODUCTS,$update_fields,'update',$update_cond);
				} else {
			        $update_fields['supply_request_id'] = $sID;
			        tep_db_perform(TABLE_SUPPLY_REQUEST_PRODUCTS,$update_fields);
					$update_prod['supply_request_products_id'] = tep_db_insert_id();
				}

				if (isset($update_prod['attr'])) foreach ($update_prod['attr'] AS $attr) {
					$update_attr_fields = array(products_options=>$attr['option'],
												products_options_values=>$attr['value'],
												options_values_price=>abs($attr['price']),
												price_prefix=>($attr['price']<0?'-':'+')
												);

					if (isset($attr['supply_request_products_attributes_id']) && $attr['supply_request_products_attributes_id']) {
					tep_db_perform(TABLE_SUPPLY_REQUEST_PRODUCTS_ATTRIBUTES,$update_attr_fields,'update',"supply_request_products_attributes_id='".$attr['supply_request_products_attributes_id']."'");
					} else {
						$update_attr_fields['supply_request_id'] = $sID;
						$update_attr_fields['supply_request_products_id']=$update_prod['supply_request_products_id'];
						tep_db_perform(TABLE_SUPPLY_REQUEST_PRODUCTS_ATTRIBUTES,$update_attr_fields);
					}
				}
			}
		}
	}


	// # 1.5 UPDATE TOTALS #####
		
		$RunningTotal = 0;
		$sort_order = 0;

			// # 1.5.1 Do pre-check for Tax field existence
			$ot_tax_found = 0;
			foreach($update_totals as $total_details)	{
				extract($total_details,EXTR_PREFIX_ALL,"ot");
				if($ot_class == "ot_tax") {
					$ot_tax_found = 1;
					break;
				}
			}
			
			// # 1.5.2. Summing up total
			foreach($update_totals as $total_index => $total_details) {

			 	// # 1.5.2.1 Prepare Tax Insertion			
				extract($total_details,EXTR_PREFIX_ALL,"ot");
			
				// # add the tax if it does not find
				if (trim(strtolower($ot_title)) == "iva" || trim(strtolower($ot_title)) == "iva:") {
						if ($ot_class != "ot_tax" && $ot_tax_found == 0) {
							// # Inserting Tax
							$ot_class = "ot_tax";
							$ot_value = "0.0000"; // # This gets updated in the next step
							$ot_tax_found = 1;
						}
				}
					
				// # 1.5.2.2 Update ot_subtotal, ot_tax, and ot_total classes
	
				if (trim($ot_title) && trim($ot_value)!='')	{
	
					$sort_order++;
	
					if($ot_class == "ot_subtotal") {
						$ot_value = $RunningSubTotal;
					}

					if($ot_class == "ot_tax") {
						$ot_value = $RunningTax;
					}

				    // # Check for existence of subtotals (CWS)                      
					if($ot_class == "ot_total") {
				        $ot_value = $RunningTotal;
				         		          
						if(!$ot_subtotal_found) {
							// # There was no subtotal on this order, lets add the running subtotal in.
				            $ot_value =  $RunningSubTotal;
						}
	
							$ot_text = "<b>" . $ot_text . "</b>";
					}
										
					$ot_text = $currencies->format($ot_value, true, $supply_request->info['currency'], $supply_request->info['currency_value']);
	
					tep_db_query("UPDATE " . TABLE_SUPPLY_REQUEST_TOTAL . "
									SET	supply_request_id = '".(int)$sID."',
									title = '" . $ot_title ."',
									text = '" . $ot_text ."',
									value = '" . $ot_value ."',
									class = '" . $ot_class ."',
									sort_order = '" . $sort_order ."'
								WHERE supply_request_total_id = '".$ot_total_id."'
								");
//error_log(print_r($ot_text,1));

					if ($ot_class == "ot_shipping" || $ot_class == "ot_lev_discount" || $ot_class == "ot_customer_discount" || $ot_class == "ot_custom" || $ot_class == "ot_cod_fee") {

						// # Again, because products are calculated in terms of default currency, 
						// # we need to align shipping, custom etc. values with default currency
	
						$RunningTotal += $ot_value / $supply_request->info['currency_value'];

					} elseif ($ot_class!='ot_total') {

						$RunningTotal += $ot_value;
					}

				} elseif (($ot_total_id > 0) && ($ot_class != "ot_shipping")) { // # Delete Total Piece
				
					tep_db_query("DELETE FROM " . TABLE_SUPPLY_REQUEST_TOTAL . " WHERE supply_request_total_id = '".$ot_total_id."'");
				}

			} // # END 1.5.2. foreach loop

			$supply_request->info['total'] = $RunningTotal;


			$order_ok = $supply_request->setStatus($status);
			
			if ($order_ok && $supply_request->info['payment_method']) {
				
				$cfields = array();

				foreach(array('payment_method','cc_type','cc_owner','cc_number') AS $cfld) {
					$cfields[$cfld] = $supply_request->info[$cfld];
				}
	
				tep_db_perform('supply_request',$cfields,'update',"supply_request_id='$sID' AND (payment_method='' OR payment_method IS NULL)");
	
				$supply_request_updated	 = true;
			}

			
			$check_status = mysql_result(tep_db_query("SELECT supply_request_status_id FROM ". TABLE_SUPPLY_REQUEST ." WHERE  supply_request_id='".$sID."'"), 0);

			// # 1.2 UPDATE STATUS HISTORY & SEND EMAIL TO CUSTOMER IF NECESSARY #####


			// # Notify Customer
			$supplier_notified = '0';

			if($_POST['notify'] == 'on') {

				if(!empty($comments)) {

					$notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n";

	
					$email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $sID . "\n" . EMAIL_TEXT_INVOICE_URL . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($supply_request->info['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $supply_request_status_array[$status]);

					tep_mail($supply_request->info['customers_name'], $supply_request->info['customers_email_address'], EMAIL_TEXT_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

					$supplier_notified = '1';	
				}

				$supply_request_updated	 = true;

			} // # END $_POST['notify'] == 'on'

			tep_db_query("UPDATE " . TABLE_SUPPLY_REQUEST . " 
						  SET last_modified = NOW() 
						  WHERE supply_request_id = '" . (int)$sID . "'
						");


			tep_db_query("INSERT INTO " . TABLE_SUPPLY_REQUEST_STATUS_HISTORY . "
						  SET supply_request_id =  '" . (int)$sID . "',
						  supply_request_status_id = '" . (int)$status . "',
						  date_added =  NOW(),
						  supplier_notified = '". $supplier_notified ."',
						  comments = '". (!empty($comments) ? tep_db_prepare_input($comments)  : '')."'
						");
// # end update

			// # 1.6 SUCCESS MESSAGE #####

			if($supply_request->error) {
				foreach ($supply_request->error AS $msg) {
					$messageStack->add_session($msg, 'error');
					$messageStack->add($msg, 'error');
				}
			} elseif($supply_request->message) {
	
				foreach ($supply_request->message AS $msg) {
					$messageStack->add_session($msg, 'success');
					$messageStack->add($msg, 'success');
				}
   			}

			if($order_ok) { 
				$supply_request_updated	 = true;
			} else {
				$supply_request_updated	 = false;
				$messageStack->add_session('Save Failed', 'error');
				$messageStack->add_session(WARNING_SUPPLY_REQUEST_NOT_UPDATED, 'error');
			}

			if($supply_request_updated)	{
				$messageStack->add_session(SUCCESS_SUPPLY_REQUEST_UPDATED, 'success');
				$messageStack->add(SUCCESS_SUPPLY_REQUEST_UPDATED, 'success');
			}

			//tep_redirect(tep_href_link('edit_supply_request.php?action=edit&sID='.$sID));
		
		break;
  
	// # 2. ADD A PRODUCT #####
	}
}

	if (!empty($_GET['sID'])) {

		$sID = tep_db_prepare_input($_GET['sID']);

		$supply_request_exists = true;
	
		$supply_request_query = tep_db_query("SELECT supply_request_id 
											  FROM " . TABLE_SUPPLY_REQUEST . " 
											  WHERE supply_request_id = '" . (int)$sID . "'
											 ");
	  	  
		if(tep_db_num_rows($supply_request_query) < 1) {
			$supply_request_exists = false;
			$messageStack->add_session(ERROR_SUPPLY_REQUEST_DOES_NOT_EXIST, 'error');
			$messageStack->add(sprintf(ERROR_SUPPLY_REQUEST_DOES_NOT_EXIST, $sID), 'error');

		}
	}
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>

<script type="text/javascript">

var supply_requestTotalValues={};

function ReloadAddProduct(cat,pid) {
jQuery.noConflict();

  var url='/admin/includes/supply_request_add_product.php?sID=<?php echo $sID?>';
  if (cat) {
    url+='&add_category_id='+cat;
    if (pid) url+='&add_product_id='+pid;
  }

	jQuery('#add_product_box').load(url, function(){
		contentChanged();
	}); 
}


function HTMLescape(s) {
  s+='';
  return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/>/g,'&gt;').replace(/</g,'&lt;');
}

var supply_requestProducts=new Array();

function AddToSupplyRequest(prod,qty,attr) {
  if (qty>0) {

    var idx = supply_requestProducts.length;

    supply_requestProducts[idx]=prod;
    supply_requestProducts[idx].qty=qty;
    supply_requestProducts[idx].tax=0;
    supply_requestProducts[idx].attributes=attr;
    supply_requestProducts[idx].cost=Number(prod.cost);
    supply_requestProducts[idx].comments='';
    renderProducts();
  }
  ReloadAddProduct();
}


function currencyFormat(val) {
  return '$'+Number(val).toFixed(2);
}

function removeAddProductItem(item, name) {
jQuery.noConflict();
	
	if(!name) var name = '';
	var confirm1 = confirm('Are you sure you want to remove \''+name+'\' from this P/O?');
	if(confirm1) {
		jQuery('[name=supply_request_products_qty_'+item+']').val(0);
		supply_requestProducts[item].qty=0;
		productsCleanup(1);
	}
}

function renderProducts(flg) {

jQuery.noConflict();

  var subtotal=0.0;
  var tax=0.0;

	var html= '<table border="0" width="100%" cellspacing="0" cellpadding="2">'
			  +'	<tr class="dataTableHeadingRow">'
			  +'	  <td class="dataTableHeadingContent" width="20" align="center"> &nbsp; </td>'
			  +'	  <td class="dataTableHeadingContent" width="20" align="center">Qty</td>'
			  +'	  <td class="dataTableHeadingContent" style="padding:10px;">Product &nbsp; <input type="text" id="prodSearch" name="prodSearch" style="font:normal 11px arial; width:150px; border: 1px solid #FFF" value="Catagory Search"></td>'
			  +'	  <td class="dataTableHeadingContent" align="center">Model</td>'
			  +'	  <td class="dataTableHeadingContent" align="center">Unit Cost</td>'
			  +'	  <td class="dataTableHeadingContent" align="right" style="padding:0 10px 0 0">Total</td>'
			  +'	  <td class="dataTableHeadingContent" align="center" style="padding:0 10px 0 0">Notes</td>'
			  +'	</tr>\n';

	for (var idx=0;supply_requestProducts[idx];idx++) {
		if (supply_requestProducts[idx].qty<=0) continue;

		html += '<tr class="dataTableRow">';
		html += '  <td class="dataTableContent" style="padding:2px 5px;"><img src="images/remove-icon.gif" style="cursor:pointer" onclick="removeAddProductItem('+idx+',\''+HTMLescape(supply_requestProducts[idx].name)+'\');"></td>';
		html += '  <td class="dataTableContent"><input type="text" name="supply_request_products_qty_'+idx+'" value="'+HTMLescape(supply_requestProducts[idx].qty)+'" size="1" onChange="supply_requestProducts['+idx+'].qty=this.value; productsCleanup(1);" style="text-align:center"></td>';
		html += '  <td class="dataTableContent">'+HTMLescape(supply_requestProducts[idx].name);

		if (supply_requestProducts[idx].attributes && supply_requestProducts[idx].attributes[0]) {

			html+='\n<table>\n';

			for (var aidx=0;supply_requestProducts[idx].attributes[aidx];aidx++) {
				html += '<tr><td>&bull;</td>\n';
				html += ' <td class="main">'+HTMLescape(supply_requestProducts[idx].attributes[aidx].option)+':&nbsp;</td>';
				html += ' <td class="main"><b>'+HTMLescape(supply_requestProducts[idx].attributes[aidx].value)+'</b></td>';
				html += '</tr>';
			}

			html+='</table>';
		}

			html += '  </td>\n';
			html += '  <td class="dataTableContent" align="center">'+(supply_requestProducts[idx].model ? HTMLescape(supply_requestProducts[idx].model) : '-')+'</td>\n';

			var theCost = (supply_requestProducts[idx].cost * supply_requestProducts[idx].qty);
			subtotal += theCost;
			tax += supply_requestProducts[idx].tax * theCost/100;



			html += '  <td class="dataTableContent" align="center">$'+parseFloat(supply_requestProducts[idx].cost).toFixed(2)+'</td>';

			html += '  <td class="dataTableContent" align="right" style="padding:0 10px 0 0">$'+ parseFloat(theCost).toFixed(2);

			html += '<input type="hidden" name="add_product_products_id[]" value="'+HTMLescape(supply_requestProducts[idx].id)+'"></td>';

			html += '  <td class="dataTableContent" align="center"><input type="text" name="add_product_products_comments_'+idx+'" value="'+(supply_requestProducts[idx].comments ? HTMLescape(supply_requestProducts[idx].comments) : '')+'" size="10" onChange="supply_requestProducts['+idx+'].comments=this.value; productsCleanup(1);"></td>';

    		html += '</tr>\n';
		}

		html += '</table>\n';
		jQuery('#products_box').empty().append(html);

	  setSupplyRequestTotal('ot_subtotal',parseFloat(subtotal).toFixed(2));
	  setSupplyRequestTotal('ot_tax',parseFloat(tax).toFixed(2));
	}


function fmtCurr(num) {

	num = Number(num);

	if(isNaN(num)) {
		num = 0;
	}

	var rs = num.toFixed(2);

	if(num<0) {
		rs='<span style="color:red">'+rs+'</span>';
	}

	return rs;
}


function productsCleanup(rf) {

	var j = 0;
	for(var i=0; supply_requestProducts[i]; i++) {
		if((supply_requestProducts[i].qty > 0) || supply_requestProducts[i].supply_request_products_id) { 
			supply_requestProducts[j++]=supply_requestProducts[i];
		} else {
			rf=1;
		}
	}

	if(rf) {
		supply_requestProducts.length=j;
		renderProducts();
	}
}


function implodeList(lst,pref) {
  var rs='';
  for (var idx in lst) {
    if (typeof(lst[idx])=='object') { 
		rs += implodeList(lst[idx],pref+idx+'.');
	} else if ((typeof(lst[idx])=='string') || (typeof(lst[idx])=='number')) { 
		rs += pref+idx+':'+lst[idx]+'\n';
	}
  }
  return rs;
}

function prepareProductsData() {

jQuery.noConflict();

	jQuery('#supply_request_products_data').val(implodeList(supply_requestProducts,''));
<?php 

	if($sID) {
		echo "return true;";
	} else {
		echo "return window.confirm('This action will create a new supply request\nPlease confirm the request is filled out correctly.');";
	}
?>

}

function setSupplyRequestTotal(cl,val,title) {
	val = isNaN(val) ? 0 : Number(val);
	supply_requestTotalValues[cl] = val;

	if(jQuery('#'+cl+'_value')) {
		jQuery('#'+cl+'_value').val(val.toFixed(2));
	}

	if(jQuery('#'+cl+'_value_txt')) {
		jQuery('#'+	cl+'_value_txt').html(currencyFormat(val));
	}

	if(title) {
    	if(jQuery('#'+cl+'_title')) {
			jQuery('#'+cl+'_title').val(title);
		}

		if(jQuery('#'+cl+'_title_txt')) {
			jQuery('#'+cl+'_title_txt').html(title);
		}
	}

	if(cl != 'ot_total') {
		var total=0.0;
		for (var c in supply_requestTotalValues) {

//alert(c.toSource());
			if(c != 'ot_total') { 
				if(total < 0) { 
					total = Number(0);
				} else {
					total += Number(supply_requestTotalValues[c]);
				}
			}
		}
    	
		setSupplyRequestTotal('ot_total',total.toFixed(2));
	}
}



jQuery(document).ready(function(e){
jQuery.noConflict();
	jQuery('.infoTable tr:even').css("background-color","#EBF1F5");
	jQuery('.completeTable tr:even').css("background-color","#EBF1F5");

});


function updateComment(obj) {
jQuery.noConflict();

	var mytextarea = jQuery('[name="comments"]'); 
    var thedate = '<?php echo date('l, F jS Y')?>'; 
    
	if(!obj.length){
		jQuery("textarea").empty();
	}

	if (!mytextarea.length || mytextarea.val() == "") {
		//mytextarea.append(thedate+':');
		mytextarea.append("\n");
		mytextarea.append(obj);
    } else { 
        mytextarea.empty();
        mytextarea.append(thedate+':');
		mytextarea.append("\n");
        mytextarea.append(obj);
    }
}

</script>

<style type="text/css">
.SubTitle {
	font: bold 11px arial;
	color: #FF6600;
	padding: 15px 0 0 0;
}

.commentsTable {
border-collapse:collapse;
margin: 10px 0;
}

.commentsTable th {
border:1px solid #CCC;
background-color: rgb(98, 149, 253);
color:white;
font:bold 11px arial;
}

.commentsTable td {
border:1px solid #CCC;
padding:10px;
}

</style>

</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="99%" cellspacing="2" cellpadding="0" align="center">
  <tr>
    <td colspan="2" width="100%" valign="top">


<table border="0" width="100%" cellspacing="0" cellpadding="2">
		
<?php
	if ($supply_request_exists == true) {

		$supply_request = new supply_request($sID);

		echo tep_draw_form('edit_request', 'edit_supply_request.php', tep_get_all_get_params(array('action','paycc')) . 'action=update_request&sID='.$sID,'POST',' id="edit_request" onSubmit="return prepareProductsData()"'); 

?>
      	<tr>
        	<td width="100%">
				<table border="0" width="100%" cellspacing="0" cellpadding="0">
          			<tr>
			            <td class="pageHeading" width="55"><img src="/admin/images/icons/supply-icon.png" width="48" height="48"></td>
            			<td class="pageHeading"><?php echo HEADING_TITLE ?></td>
			            <td align="right"><div style="padding:10px 0 0 0; font:bold 11px arial"><?php echo HEADING_TITLE_NUMBER . ' ' . $sID . '&nbsp; -  &nbsp;' . date('m/d/Y - h:ia', strtotime($supply_request->info['date_requested']));?> <br><br> <a href="supply_request.php?status=<?php echo '5';?>&sID=<?php echo $sID?>&action=delete"><img alt="Delete" src="includes/languages/english/images/buttons/button_cancel.gif"></a>

<?php 
if($_SESSION['origin'] == FILENAME_STATS_PRODUCTS_BACKORDERED) {

	echo '<a href="' . tep_href_link(FILENAME_STATS_PRODUCTS_BACKORDERED, '', 'SSL') . '">'; 

} else {

	echo '<a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('action'))) . '">';
}

	echo tep_image_button('button_back.gif', IMAGE_BACK) . '</a>';
?>
</div>
</td>
          </tr>
          <tr>
            <td class="main" colspan="3"><?php echo HEADING_SUBTITLE; ?></td>
          </tr>
        </table></td>
      </tr>	
      <tr>
	    <td class="SubTitle"><?php echo MENUE_TITLE_CUSTOMER; ?></td>
	  </tr>
	<tr>
			  <td>
<table width="100%" border="0" class="dataTableRow" cellpadding="2" cellspacing="0">
  <tr class="dataTableHeadingRow">
    <td class="dataTableHeadingContent" width="80"></td>
    <td class="dataTableHeadingContent" width="150"><?php echo ENTRY_SHIPFROM_ADDRESS; ?></td>
    <td class="dataTableHeadingContent" width="6">&nbsp;</td>
    <td class="dataTableHeadingContent" width="150"><?php echo ENTRY_SHIPPING_ADDRESS; ?></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_COMPANY; ?>: </b></td>
    <td><span class="main"><input name="update_supplier_company" size="25" value="<?php echo tep_html_quotes($supply_request->supplier['company']); ?>"></span></td>
		<td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_company" size="25" value="<?php echo tep_html_quotes($supply_request->delivery['company']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_NAME; ?>: </b></td>
    <td><span class="main"><input name="update_supplier_name" size="25" value="<?php echo tep_html_quotes($supply_request->supplier['suppliers_name']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_name" size="25" value="<?php echo tep_html_quotes($supply_request->delivery['name']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_ADDRESS; ?>: </b></td>
    <td><span class="main"><input name="update_supplier_street_address" size="25" value="<?php echo tep_html_quotes($supply_request->supplier['street_address']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_street_address" size="25" value="<?php echo tep_html_quotes($supply_request->delivery['street_address']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_ADDRESS2; ?>: </b></td>
    <td><span class="main"><input name="update_supplier_address2" size="25" value="<?php echo tep_html_quotes($supply_request->supplier['address2']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_" size="25" value="<?php echo tep_html_quotes($supply_request->delivery['']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_CITY; ?>: </b></td>
    <td><span class="main"><input name="update_supplier_city" size="25" value="<?php echo tep_html_quotes($supply_request->supplier['city']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_city" size="25" value="<?php echo tep_html_quotes($supply_request->delivery['city']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_STATE; ?>: </b></td>



    <td><span class="main">
    <input type="hidden" name="update_supplier_state" value="<?php echo tep_html_quotes($supply_request->supplier['state'])?>" id="bill_state">
    <div id="bill_state_box"></div>
    <input name="update_supplier_state" size="25" value="<?php echo tep_html_quotes($supply_request->supplier['state']); ?>">
    </span></td>
    <td>&nbsp;</td>
    <td><span class="main">
    <input type="hidden" name="update_delivery_state" value="<?php echo tep_html_quotes($supply_request->delivery['state'])?>" id="ship_state">
    <div id="ship_state_box"></div>
    <input name="update_delivery_state" size="25" value="<?php echo tep_html_quotes($supply_request->delivery['state']); ?>" id="ship_state" onChange="reloadShipping()">
    </span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_POSTCODE; ?>: </b></td>
    <td><span class="main"><input name="update_supplier_postcode" size="25" value="<?php echo $supply_request->supplier['postcode']; ?>" id="bill_postcode" onChange="reloadState('bill')"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_postcode" size="25" value="<?php echo $supply_request->delivery['postcode']; ?>" id="ship_postcode" onChange="reloadState('ship')"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_COUNTRY; ?>: </b></td>
    <td><span class="main"><?php echo tep_draw_pull_down_menu('update_supplier_country',$countries_pull_down,$supply_request->supplier['country'],' id="bill_country" onChange="reloadState(\'bill\')" style="width:200px"')?></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><?php echo tep_draw_pull_down_menu('update_delivery_country',$countries_pull_down,$supply_request->delivery['country'],' id="ship_country" onChange="reloadState(\'ship\')" style="width:200px"')?></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_PHONE; ?>: </b></td>
    <td colspan="3"><span class="main"><input name="update_supplier_telephone" size="25" value="<?php echo $supply_request->supplier['telephone']; ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_SUPPLIER_EMAIL; ?>: </b></td>
    <td colspan="3"><span class="main"><input name="update_supplier_email_address" size="25" value="<?php echo $supply_request->supplier['email_address']; ?>"></span></td>
  </tr>
</table>

				</td>
			</tr>     
      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_PAYMENT; ?></td>
			</tr> 
      <tr>
	      <td>
				
<table border="0" cellspacing="0" cellpadding="2" width="100%">
  <tr class="dataTableHeadingRow">
    <td colspan="4" class="dataTableHeadingContent"><?php echo ENTRY_PAYMENT_METHOD; ?></td>
	</tr>
	<tr>
		<td colspan="4" style="padding:1px 0 0 0">&nbsp;</td>
	</tr>
  <tr>
	<td class="main">
<?php 

	$current_pay = $supply_request->info['payment_method'];
	echo '<select name="update_info_payment_method">';
	$payment_method_array = array('Credit Card', 'Check', 'Terms', 'Bank Wire');
	asort($payment_method_array);

	foreach($payment_method_array as $payment_methods) {
		echo '<option value="'.$payment_methods.'" '.($payment_methods == $current_pay ? 'selected="selected"' : '').'>'.$payment_methods.'</option>';

	}

	echo '</select>';

if ($supply_request->info['payment_method'] != "CreditCard" || $supply_request->info['payment_method'] != "Kreditkarte") {

}

?>


	</td>

<td class="main">Account Type: <input name="update_info_cc_type" size="20" value="<?php echo $supply_request->info['cc_type']; ?>"></td>
<td class="main">Account Name: <input name="update_info_cc_owner" size="20" value="<?php echo $supply_request->info['cc_owner']; ?>"></td>
<td class="main">Account Number: <input name="update_info_cc_number" size="20" value="<?php echo $supply_request->info['cc_number']; ?>"></td>
	</tr>

</table>

        </td>
      </tr>
	    

      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_ORDER; ?></td>
			</tr>  
      <tr>
	      <td>
				  
<?php
		$index = 0;
		$supply_request->products = array();
		$supply_request_products_query = tep_db_query("SELECT * FROM " . TABLE_SUPPLY_REQUEST_PRODUCTS . " WHERE supply_request_id = '" . (int)$sID . "'");
		while ($supply_request_products = tep_db_fetch_array($supply_request_products_query)) {

			$supply_request->products[$index] = array('qty' => $supply_request_products['products_quantity'],
            				                         'name' => str_replace("'", "&#39;", $supply_request_products['products_name']),
                            				         'model' => $supply_request_products['products_model'],
				                                     'tax' => $supply_request_products['products_tax'],
                				                     'price' => $supply_request_products['products_price'],
                                				     'cost_price' => $supply_request_products['cost_price'],
				                                     'supply_request_products_id' => $supply_request_products['supply_request_products_id']
													);

		$subindex = 0;
		$attributes_query_string = "select * from " . TABLE_SUPPLY_REQUEST_PRODUCTS_ATTRIBUTES . " where supply_request_id = '" . (int)$sID . "' and supply_request_products_id = '" . (int)$supply_request_products['supply_request_products_id'] . "'";
		$attributes_query = tep_db_query($attributes_query_string);

		if (tep_db_num_rows($attributes_query)) {
		while ($attributes = tep_db_fetch_array($attributes_query)) {
		  $supply_request->products[$index]['attributes'][$subindex] = array('option' => $attributes['products_options'],
		                                                            'value' => $attributes['products_options_values'],
		                                                            'prefix' => $attributes['price_prefix'],
		                                                            'price' => $attributes['options_values_price'],
		                                                            'supply_request_products_attributes_id' => $attributes['supply_request_products_attributes_id']);
		  $subindex++;
		  }
		}
		$index++;
	}
	
?>


        </td>
</tr>
      <tr>
	      <td>
			<div id="div_add_product" style="display:block; border:1px dashed #CCC; background-color:#FFF">
				<table width="100%" border="0" cellspacing="0" cellpadding="4">
		          <tr>
        		    <td class="main" valign="top"><div id="products_box"></div></td>
		          </tr>
        		  <tr>
		            <td class="main" valign="top"><div id="add_product_box"></div></td>
        		  </tr>
		          <tr>	
	    	  </table>

<input type="hidden" name="supply_request_products_data" id="supply_request_products_data" value="">

<script type="text/javascript">
<?php
  foreach($supply_request->add_product AS $prod) {

    $attr_js_data = array();

    if (isset($prod['attributes'])) {
		foreach($prod['attributes'] AS $attr) {
			$attr_js_data[]="{option: '".addslashes($attr['option'])."',"
					        ." value: '".addslashes($attr['value'])."',"
							." orders_products_attributes_id: '".addslashes($attr['orders_products_attributes_id'])."',"
							." price: '".($attr['price_prefix']=='-'?-$attr['price']:$attr['price'])."' "
							."}";
		}
    }
?>
 	supply_requestProducts[supply_requestProducts.length]={
    supply_request_products_id: '<?php echo addslashes($prod['supply_request_products_id'])?>',
    id: '<?php echo addslashes($prod['products_id'])?>',
    name: '<?php echo addslashes($prod['name'])?>',
    model: '<?php echo addslashes($prod['model'])?>',
    cost: '<?php echo addslashes($prod['cost'])?>',
    tax: '<?php echo addslashes($prod['tax'])?>',
    weight: '<?php echo addslashes($prod['weight'])?>',
    qty: '<?php echo addslashes($prod['qty'])?>',
    comments: '<?php echo addslashes($prod['comments'])?>',
    attr: <?php echo tep_js_quote_array($prod['attributes'])?>
  };
<?php
  }
?>
</script>

	<script type="text/javascript">
		supply_requestProducts = <?php echo tep_js_quote_array($supply_request->add_product)?>;
		renderProducts();
		ReloadAddProduct();
	</script>
		</div>			  </td>

      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_TOTAL; ?></td>
	</tr> 
      <tr>
	      <td>

<table border="0" cellspacing="0" cellpadding="5">
	<tr class="dataTableHeadingRow">
	  <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_TOTAL_MODULE; ?></td>
	  <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_TOTAL_AMOUNT; ?></td>
	  <td class="dataTableHeadingContent"width="1"><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
	</tr>
<?php

	$totals_query = tep_db_query("SELECT * FROM " . TABLE_SUPPLY_REQUEST_TOTAL . " 
								  WHERE supply_request_id = '".(int)$sID."' 
								  ORDER BY sort_order
							     ");

	$supply_request->totals = array();

	while ($totals = tep_db_fetch_array($totals_query)) {

		$supply_request->totals[] = array('title' => $totals['title'], 'text' => $totals['text'], 'class' => $totals['class'], 'value' => $totals['value'], 'supply_request_total_id' => $totals['supply_request_total_id']); 
	}

	// # START - MAKE ALL INPUT FIELDS THE SAME LENGTH 
	$max_length = 0;
	$TotalsLengthArray = array();
	for ($i=0; $i<sizeof($supply_request->totals); $i++) {
		$TotalsLengthArray[] = array("Name" => $supply_request->totals[$i]['title']);
	}
	reset($TotalsLengthArray);
	foreach($TotalsLengthArray as $TotalIndex => $TotalDetails) {
		if (strlen($TotalDetails["Name"]) > $max_length) {
			$max_length = strlen($TotalDetails["Name"]);
		}
	}
	// # END - MAKE ALL INPUT FIELDS THE SAME LENGTH 

	$TotalsArray = array();
	$ct_custom = 0;

	for($i=0; $i<sizeof($supply_request->totals); $i++) {
	
		$TotalsArray[] = array("Name" => htmlspecialchars($supply_request->totals[$i]['title']), 
		   	"Price" => number_format($supply_request->totals[$i]['value'], 2, '.', ''), 
			"Class" => $supply_request->totals[$i]['class'], 
			"TotalID" => $supply_request->totals[$i]['supply_request_total_id'],Negative=>(strpos('discount',$supply_request->totals[$i]['class'])));

		if ($supply_request->totals[$i]['class']=='ot_custom') { 
			$ct_custom++;
		}
	}
	
	$total_value = array();

	foreach($TotalsArray as $TotalIndex => $TotalDetails) {

		$total_id = $TotalDetails["Class"];

		while(isset($total_value[$total_id])) { 
			$total_id.='_';
		}

		$total_value[$total_id] = $TotalDetails["Price"];
		$hide = false;

		switch ($TotalDetails["Class"]) {
			case "ot_total":
			case "ot_subtotal":
			case "ot_tax":
				$hide = true;
		}

		echo '<tr>
				<td align="right" class="smallText">';

		if($hide) { 
				echo '<div id="'.$total_id.'_title_txt"><b>' . $TotalDetails["Name"] . '</b></div>';
		}
			
		echo '<input name="update_totals['.$TotalIndex.'][title]" id="'.$total_id.'_title" type="'.($hide ? 'hidden' : 'text').'" value="' . trim($TotalDetails["Name"]) . '" size="24" ></td>

		<td align="right" class="smallText">';

		if($hide) { 
			echo '<div id="'.$total_id.'_value_txt"><b>' . $currencies->format($TotalDetails["Price"], true, $supply_request->info['currency'], $supply_request->info['currency_value']) . '</b></div>';
		}
		
		echo '<input name="update_totals['.$TotalIndex.'][value]" id="'.$total_id.'_value" type="'.($hide ? 'hidden' : 'text').'" value="' . $TotalDetails["Price"] . '" size="6" onchange="setSupplyRequestTotal(\''.$total_id.'\',this.value'.($TotalDetails["Negative"]?'.replace(/^(\d)/,\'-$1\')':'').')">
				<input name="update_totals['.$TotalIndex.'][class]" type="hidden" value="' . $TotalDetails["Class"] . '">
				<input type="hidden" name="update_totals['.$TotalIndex.'][total_id]" value="' . $TotalDetails["TotalID"] . '"></b>
			</td>
		</tr>';
	}
?>
</table>
<script type="text/javascript">
<?php
    foreach ($total_value AS $ot_class => $ot_value) {
		$ot_value+=0;
		echo "  supply_requestTotalValues['$ot_class']=$ot_value;\n";
    }
?>
</script>

	      </td>
</tr>
      <tr>
			  <td class="smalltext" style="font:bold 11px arial"><?php echo HINT_TOTALS; ?></td>
      </tr>
      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_STATUS; ?></td>
			</tr>
      <tr>
        <td class="main">
				  
<table border="0" cellspacing="0" cellpadding="2" width="100%" class="commentsTable">
                          <tr>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_STATUS; ?></b></th>
                            <th width="50%" class="smallText" align="center"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></th>
                          </tr>
<?php

	$supply_request_history_query = tep_db_query("SELECT * FROM " . TABLE_SUPPLY_REQUEST_STATUS_HISTORY . "
												  WHERE supply_request_id = '" . (int)$sID ."' 
												  ORDER BY date_added
												");

	if (tep_db_num_rows($supply_request_history_query)) {
		
		while ($supply_request_history = tep_db_fetch_array($supply_request_history_query)) {
			echo '<tr>
					<td class="smallText" align="center">' . tep_datetime_short($supply_request_history['date_added']) . '</td>
					<td class="smallText" align="center">';

			if ($supply_request_history['supplier_notified'] == '1') {
				echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK);
			} else {
				echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS);
			}

			echo '	</td>
					<td class="smallText" align="left">' . $supply_request_status_array[$supply_request_history['supply_request_status_id']] . '</td>
					<td class="smallText" align="left" width="50%">' . nl2br(tep_db_output($supply_request_history['comments'])) . '&nbsp;</td>
				  </tr>';
		}
	
	} else {
		echo ' <tr><td class="smallText" colspan="5">' . TEXT_NO_SUPPLY_REQUEST_HISTORY . '</td></tr>';
	}
?>
</table>
</td>
</tr>
<tr>
	<td>
		<table border="0" cellspacing="0" cellpadding="2" width="100%">
			<tr class="dataTableHeadingRow">
			    <td class="dataTableHeadingContent" align="left"><?php echo TABLE_HEADING_STATUS; ?></td>
			    <td class="dataTableHeadingContent" align="left" width="50%"><?php echo TABLE_HEADING_COMMENTS; ?></td>
		  </tr>
			<tr>
			  <td>
				  <table border="0" cellspacing="0" cellpadding="2">
			        <tr>
			          <td class="main"><b><?php echo ENTRY_STATUS; ?></b></td>
			          <td class="main" align="right"><?php echo tep_draw_pull_down_menu('status', $supply_request_statuses, $supply_request->info['supply_request_status_id']); ?></td>
			        </tr>

		      </table>
			</td>
    <td class="main">
  	  <?php echo tep_draw_textarea_field('comments', 'soft', '40', '5', $supply_request->info['comments'], 'style="width:99%;"'); ?>
	<br>
	 <?php echo tep_draw_checkbox_field('notify', '', false) . tep_draw_hidden_field('notify_comments', '', true); ?> <b><?php echo ENTRY_NOTIFY_SUPPLIER; ?></b>
    </td>
  </tr>
</table>

			  </td>
			</tr>
      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_UPDATE; ?></td>
			</tr> 
      <tr>
	      <td>
          <table width="100%" border="0" cellpadding="2" cellspacing="1">
            <tr>
              <td class="main" bgcolor="#FAEDDE"><?php echo HINT_PRESS_UPDATE; ?></td>
              <td class="main" bgcolor="#FBE2C8" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FFCC99" width="10">&nbsp;</td>
              <td class="main" bgcolor="#F8B061" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FF9933" width="120" align="center"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
	          </tr>
          </table>
				</td>
      </tr>
      </form>

<script type="text/javascript">
jQuery.noConflict();
jQuery(document).ready(function(){

	jQuery("#edit_request").on('submit', function() {
        //e.preventDefault();

		//jQuery.post("edit_supply_request.php?action=update_request&sID=<?php echo (int)$sID ?>", jQuery("#edit_request").serialize()).done(function(data) {
		//	location.reload();
        //});
        //return false;
	});

	jQuery("#prodSearch").one('focus', function(){
	    this.value = '';
	});

	jQuery("#prodSearch").on('keyup paste', function(e) {

		if(jQuery(this).val().length > 0 ) {

			var thestr = jQuery(this).val().trim();

			jQuery.get('<?php echo $_SERVER['PHP_SELF'] ?>?search', { search : thestr }, function(data) {
			
				if(data.length > 0) { 
					var dataarray = data.toString().split(',');
    	            //alert(dataarray[2]);
					jQuery('[name=add_category_id]').val(dataarray[0]).change();

					setTimeout(function(){jQuery('[name=add_product_select]').val(dataarray[1]).change()},500);
				}

   	        }, 'json');
	    }
	
	});
});

</script>

<?php
}
?>

    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php');?>