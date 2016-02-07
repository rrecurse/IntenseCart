<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	@include(DIR_FS_CATALOG_LAYOUT. "languages/$language/" . FILENAME_PRODUCT_REVIEWS);
	@include(DIR_FS_CATALOG_LAYOUT. "languages/$language/" . FILENAME_PRODUCT_REVIEWS_WRITE);
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PRODUCT_REVIEWS);
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PRODUCT_REVIEWS_WRITE);

	$products_id = (int)$HTTP_GET_VARS['products_id'];

	$reviews_query_raw = "select r.reviews_id, rd.reviews_text, r.reviews_rating, r.date_added, r.customers_name from " . TABLE_REVIEWS . " r LEFT JOIN " . TABLE_REVIEWS_DESCRIPTION . " rd ON r.reviews_id = rd.reviews_id WHERE r.products_id = '". $products_id ."'AND rd.languages_id = '" . (int)$languages_id . "' ORDER BY r.reviews_id desc";

	$reviews_split = new splitPageResults($reviews_query_raw, '20');

	$reviews_query = tep_db_query($reviews_split->sql_query);
	$count = 1;

	while ($reviews = tep_db_fetch_array($reviews_query)) {

		$review_text = (strlen($reviews['reviews_text']) > 250 ? substr($reviews['reviews_text'],0,250).' ...' : $reviews['reviews_text']);
		$count = ($count + 1) % 2;

		echo '<table border="0" cellspacing="0" cellpadding="0" class="productReviews_table" id="past_reviews_'.$reviews['reviews_id'].'">
                  <tr>
                    <td class="main">
						<table border="0" width="100%" cellspacing="0">
							<tr>
								<td class="main"><u><b>' . sprintf(TEXT_REVIEW_BY, tep_output_string_protected($reviews['customers_name'])) . '</b></u></td>
								<td class="smallText" align="right">'. sprintf(TEXT_REVIEW_DATE_ADDED, tep_date_short($reviews['date_added'])).'</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top" class="main" colspan="2">
						<div><i>' . sprintf(TEXT_REVIEW_RATING, tep_image(DIR_WS_IMAGES . 'stars_' . $reviews['reviews_rating'] . '.gif', sprintf(TEXT_OF_5_STARS, $reviews['reviews_rating'])), sprintf(TEXT_OF_5_STARS, $reviews['reviews_rating'])) . '</i></div>
					<div style="padding-top:6px;">'. $review_text . '</div></td>
				</tr>
			</table>';
	}


	if(tep_session_is_registered('customer_id')) {

		$reviews_count_query = tep_db_query("SELECT r.*, COUNT(*) AS count, rd.reviews_text
											 FROM reviews r
											 LEFT JOIN reviews_description rd ON rd.reviews_id = r.reviews_id
											 WHERE products_id = '" . $products_id . "'
											");

		$reviews_count = tep_db_fetch_array($reviews_count_query);

		$reviews_text_query = tep_db_query("SELECT r.reviews_id, rd.reviews_text 
											FROM reviews_description rd 
											LEFT JOIN reviews r ON rd.reviews_id = r.reviews_id
											WHERE r.customers_id = '".$customer_id."'
											AND r.products_id = '" . $products_id . "'
										   ");
		$reviews_text = tep_db_fetch_array($reviews_text_query);
	

	echo '<table border="0" width="100%" cellspacing="0" cellpadding="0" id="reviews">';
			if($messageStack->size('review') > 0) echo '<tr><td>'.$messageStack->output('review').'</td></tr>';
?>
			<tr>
				<td valign="top">

	<script type="text/javascript">

	function submit_review(id) {
		jQuery.noConflict();

		if(!jQuery('#review_text').val()) {
			alert('You can not submit a blank review. \nPlease check your review and try again.');
			return false;
		}
	
		if(!jQuery('input[name="rating"]:checked').val()) {
			alert('You can not submit a review without a rating. \nPlease check your rating score and try again.');
			return false;
		}
	

		jQuery('#reviews_box').prepend('<img src="/images/loading_bar.gif" id="loadbar">').fadeIn();

		jQuery("#review_button").fadeOut('fast').remove();

		jQuery('#reviews_box').load('/product_reviews_write.php #review_response', jQuery.param({
				action: 'process',
				products_id: '<?php echo $products_id ?>',
				review: jQuery('#review_text').val(),
				rating: jQuery('input[name="rating"]:checked').val(),
				customers_name: jQuery('input[name="customers_name"]').val()
				}), function(response){	

						jQuery.get('/product_info.php?products_id=<?php echo $products_id?>', function(data){
							jQuery("#reviews_box").empty();
							jQuery("#past_reviews_"+id).empty();
							jQuery("#reviews_box").html( jQuery(data).find('#reviews').html()).fadeIn('fast');
							jQuery("#reviews_box").prepend(jQuery(data).find('#past_reviews_'+id).html()).fadeIn();
						});
							jQuery("#loadbar").fadeOut('slow').remove();
			}); 
	}



	function delete_review(id) {
		jQuery.noConflict();

		var deletereview = confirm("Are you sure you want to delete your review?");

		if (deletereview == true) {

			jQuery('#reviews_box').load('/product_reviews_write.php #review_response', jQuery.param({
				action: 'process',
				delete: '1',
				reviews_id: id,
				products_id: '<?php echo $products_id ?>'
				}), function(response){	
						location.reload();
				}); 
		}
	}


	</script>
	
	<div id="reviews_box">

<?php 

	if ($reviews_count['count'] < 1) {
		echo '<br>There are currently no reviews for this product. <b>Be the first!</b><br>';
	}

	$customer_query = tep_db_query("SELECT customers_firstname, customers_lastname 
									FROM " . TABLE_CUSTOMERS . " 
									WHERE customers_id = '". $customer_id ."'
								   ");

	$customer = tep_db_fetch_array($customer_query);

	$custname = tep_output_string_protected($customer['customers_firstname'] . ' ' . $customer['customers_lastname'][0].'.');

	// # check if the customer purchased product prior to allowing review

	$previous_purchase_query = tep_db_query("SELECT o.orders_id, o.customers_id, op.products_id 
											 FROM " . TABLE_ORDERS . " o
											 LEFT JOIN ". TABLE_ORDERS_PRODUCTS ." op ON op.orders_id = o.orders_id
											 WHERE o.customers_id = '". $customer_id ."'
											 AND op.products_id = '". $products_id ."'
											");

	$previous_purchase = (tep_db_num_rows($previous_purchase_query) > 0 ) ? 1 : 0; 

	if(($customer['customers_firstname'] != '') && $previous_purchase > 0) { 

		$noreview_id = mysql_result(tep_db_query("SHOW TABLE STATUS LIKE '". TABLE_REVIEWS."'"), 0) + 1;

		$noreview_id_query = mysql_query("SHOW TABLE STATUS LIKE '". TABLE_REVIEWS."'");
		$noreview = mysql_fetch_array($noreview_id_query);
		$noreview_id = $noreview['Auto_increment']; 


		$reviews_id = ($reviews_text['reviews_id'] > 0) ? $reviews_text['reviews_id'] : $noreview_id;

		echo '<br><table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td class="main" style="text-transform:capitalize;"><b>' . SUB_TITLE_FROM . '</b> ' . $customer['customers_firstname'] . ' ' . $customer['customers_lastname'].'</td>
					</tr>
				   </table>';

		echo 'Review using alias: &nbsp;'.tep_draw_input_field('customers_name',$custname,'');

		echo '<table border="0" width="100%" cellspacing="0" cellpadding="5">
    				<tr>
						<td class="main"><b>'. SUB_TITLE_REVIEW .' </b>
							<br>'.tep_draw_textarea_field('review', 'soft', 35, 7, $reviews_text['reviews_text'],'style="width:100%;" id="review_text"',false) .'</td>
					</tr>
					<tr>
						<td class="smallText" align="right">'. TEXT_NO_HTML.'</td>
					</tr>
					<tr>
						<td class="main"><b>' . SUB_TITLE_RATING . '</b> &nbsp; ' . TEXT_BAD . ' ' . tep_draw_radio_field('rating', '1', ($reviews_count['reviews_rating'] == 1 ?  true : '')) . ' ' . tep_draw_radio_field('rating', '2', ($reviews_count['reviews_rating'] == 2 ?  1 : 0)) . ' ' . tep_draw_radio_field('rating', '3', ($reviews_count['reviews_rating'] == 3 ?  1 : 0)) . ' ' . tep_draw_radio_field('rating', '4', ($reviews_count['reviews_rating'] == 4 ? 1 : 0)) . ' ' . tep_draw_radio_field('rating', '5', ($reviews_count['reviews_rating'] == 5 ? 1 : 0)) . ' ' . TEXT_GOOD .'
						</td>
					</tr>
					<tr>
						<td align="center" style="height:37px; line-height:37px;"><img id="review_button" src="/layout/img/buttons/english/button_continue.gif" style="cursor:pointer" onclick="submit_review(\''.$reviews_id.'\');">';

		$reviews_existing_comment = tep_db_query("SELECT reviews_id FROM reviews WHERE products_id = '". $products_id ."' AND customers_id = '".$customer_id."'");

		if (tep_db_num_rows($reviews_existing_comment) > 0) echo '<a href="javascript:void(0);" onclick="delete_review(\''.$reviews_id.'\')">Delete review</a>';
			
		echo '</td></tr></table>';
	} elseif(($customer['customers_firstname'] != '') && $previous_purchase == 0) { 
		echo '<br>You can only leave reviews for products you\'ve purchased. Please checkout with this product and revisit this section to leave a review.';
	}
?>
</div>
</td>
	</tr>
</table>


<?php 

	} else { 

		$reviews_count_query = tep_db_query("SELECT reviews_id FROM reviews WHERE products_id = '". $products_id ."'");

		if (tep_db_num_rows($reviews_count_query) < 1) echo '<br>There are currently no reviews for this product. <b>Be the first!</b><br><br>';
		
		echo 'Please <a href ="/login.php?return='.$_SERVER["REQUEST_URI"].'">login</a> to leave a review.<br>';
	}
?>
