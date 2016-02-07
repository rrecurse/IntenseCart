<?php

	$products_id = (int)$HTTP_GET_VARS['products_id'];

	$pi_query = tep_db_query("SELECT products_image,products_image_xl_1, products_image_xl_2, products_image_xl_3, products_image_xl_4, products_image_xl_5,  products_image_xl_6, products_image_xl_7, products_image_xl_8 
							  FROM " . TABLE_PRODUCTS . " 
							  WHERE products_id = '" . $products_id . "' 
							  LIMIT 1
							");

	global $altImages;

	$altImages = array();

	if (tep_db_num_rows($pi_query) > 0) {

		$pi = tep_db_fetch_array($pi_query);

		$image_array = array();

		for ($x = 1; $x <= 8; $x++) {

			if ($pi['products_image_xl_' . $x] != NULL) {

				$altImages[]=DIR_WS_IMAGES.($image_array[] = $pi['products_image_xl_' . $x]);
			}
		}

		if (sizeof($image_array) > 0) {
 
			echo '<table border="0" cellspacing="0" cellpadding="0" class="inc_mod_productimages_table">';

			$cur_row = 0;
			$max_row = PRODUCT_TN_DISPLAY_ROWS;
			$cur_col = 0;
			$max_col = PRODUCT_TN_DISPLAY_COLS;
			$new_row = true;

			foreach($image_array as $img) {

				if ($new_row) {
					echo '<tr class="inc_mod_productimages_tr">' . "\n";
					$new_row = false;
					$cur_row = 0;
					$cur_col = 0;
				}
	
				$img_resized = preg_replace('/.*?src="(.*?)".*/','$1',tep_image(DIR_WS_IMAGES.$img,'',LARGE_IMAGE_WIDTH,LARGE_IMAGE_HEIGHT));

				echo '<td class="inc_mod_productimages_td">
					<table>
						<tr>
							<td><a href="javascript:popupWindow(\''.$products_id.'\','.LARGE_IMAGE_WIDTH+ULT_THUMB_IMAGE_WIDTH+POPUP_ADJUST_WIDTH.'\',\''.LARGE_IMAGE_HEIGHT+POPUP_ADJUST_HEIGHT.'\');" onMouseOver="swapImage(\'mainimage\',\'\',\''.$img_resized.'\',1)" onMouseOut="swapImgRestore()">'.tep_image(DIR_WS_IMAGES.$img,'',ULT_THUMB_IMAGE_WIDTH,ULT_THUMB_IMAGE_HEIGHT, 'id="image1" border=0 class="inc_mod_productimages_img" alt=""').'</a></td></tr></table></td>';

				$cur_col++;


				if ($cur_col >= $max_col || ($cur_col == 1 && $max_col == 1)) {
					$new_row = true;
					echo '</tr>' . "\n";
					if ($cur_row++>=$max_row) break;
				 }
			}

			if ($cur_col <= $max_col && ($cur_col != 1 && $max_col != 1)) {
				echo '</tr>' . "\n";
			}
		
			echo '</table>';
		
			return;
    
		} else {
			echo "-";
		}

	} else {
		echo "-";
	}
?>