<?php

  require('includes/application_top.php');
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_WISHLIST);

  $wish_box=(isset($_GET['wish_box']) && $_GET['wish_box']!='')?$_GET['wish_box']:NULL;

/*******************************************************************
******* ADD PRODUCT TO WISHLIST IF PRODUCT ID IS REGISTERED ********
*******************************************************************/
  
  if (isset($_GET['pid'])) {
    $wishlist_id=$_GET['pid'];
    tep_session_register('wishlist_id');
  }
  if(tep_session_is_registered('wishlist_id')) {
	$rs=$wishList->add_wishlist($wishlist_id, $attributes_id);
	$wish_added=$wishlist_id;

	if(WISHLIST_REDIRECT == 'Yes') {
		tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $wishlist_id));
	} else {
		tep_session_unregister('wishlist_id');
		if (!$rs && isset($wishList->wishID[$wishlist_id])) tep_redirect('wishlist.php?wish_box='.$wishList->wishID[$wishlist_id]['wish_box_id']);
	}
  }


/*******************************************************************
****************** ADD PRODUCT TO SHOPPING CART ********************
*******************************************************************/

  if (isset($HTTP_POST_VARS['add_wishprod'])) {
	if(isset($HTTP_POST_VARS['add_prod_x'])) {
		foreach ($HTTP_POST_VARS['add_wishprod'] as $value) {
			$product_id = tep_get_prid($value);
			$cart->add_cart($product_id, $cart->get_quantity(tep_get_uprid($product_id, $HTTP_POST_VARS['id'][$value]))+1, $HTTP_POST_VARS['id'][$value]);
		}
	}
  }


/*******************************************************************
****************** DELETE PRODUCT FROM WISHLIST ********************
*******************************************************************/

  if (isset($HTTP_POST_VARS['add_wishprod'])) {
	if(isset($HTTP_POST_VARS['delete_prod_x'])) {
		foreach ($HTTP_POST_VARS['add_wishprod'] as $value) {
			$wishList->remove($value);
		}
	}
  }


/*******************************************************************
****************** MOVE TO DIFFERENT BOX ***************************
*******************************************************************/

  if (isset($HTTP_POST_VARS['add_wishprod']) && $customer_id) {
	if(isset($HTTP_POST_VARS['move_prod_x'])) {
		$newbox=$_POST['move_to'];
		if ($newbox=='') $newbox=NULL;
		else if ($newbox=='+') {
		  $boxname=trim($_POST['new_box_name']);
		  if ($boxname!='') {
		    $bx_row=tep_db_fetch_array(tep_db_query("SELECT * FROM customers_wishlist_boxes WHERE wish_box_name='".addslashes($boxname)."' AND customers_id='$customer_id'"));
		    if ($bx_row) $newbox=$bx_row['wish_box_id'];
		    else {
		      tep_db_query("INSERT INTO customers_wishlist_boxes (customers_id,wish_box_name) VALUES ('$customer_id','".addslashes($boxname)."')");
		      $newbox=tep_db_insert_id();
		    }
		  } else $newbox=NULL;
		}
		$wishList->move_items($HTTP_POST_VARS['add_wishprod'],$newbox);
	}
  }


/*******************************************************************
****************** RENAME/DELETE BOX *******************************
*******************************************************************/

  if (isset($_POST['box_rename']) || isset($_POST['box_delete'])) {
    $newname=isset($_POST['box_rename'])?trim($_POST['box_name']):'';
    $move=false;
    if ($newname!='') {
      $bx_row=tep_db_fetch_array(tep_db_query("SELECT * FROM customers_wishlist_boxes WHERE wish_box_name='".addslashes($newname)."' AND customers_id='$customer_id'"));
      if ($bx_row) {
	$move=true;
	$moveto=$bx_row['wish_box_id'];
      }
    } else {
      $move=true;
      $moveto=NULL;
    }
    if ($move) {
      $wishList->move_items($wishList->get_items($wish_box),$moveto);
      tep_db_query("DELETE FROM customers_wishlist_boxes WHERE wish_box_id='$wish_box' AND customers_id='$customer_id'");
      tep_redirect('wishlist.php?wish_box='.$moveto);
    } else tep_db_query("UPDATE customers_wishlist_boxes SET wish_box_name='".addslashes($newname)."' WHERE wish_box_id='$wish_box' AND customers_id='$customer_id'");
  }


/*
  if (isset($HTTP_POST_VARS['add_wishprod'])) {
	if(isset($HTTP_POST_VARS['move_prod_x'])) {
		$newbox=$_POST['move_to'];
		if ($newbox=='') $newbox=NULL;
		else if ($newbox=='+') {
		  $boxname=trim($_POST['new_box_name']);
		  if ($boxname!='') {
		    $bx_row=tep_db_fetch_array(tep_db_query("SELECT * FROM customers_wishlist_boxes WHERE wish_box_name='".addslashes($boxname)."' AND customers_id='$customer_id'"));
		    if ($bx_row) $newbox=$bx_row['wish_box_id'];
		    else {
		      tep_db_query("INSERT INTO customers_wishlist_boxes (customers_id,wish_box_name) VALUES ('$customer_id','".addslashes($boxname)."')");
		      $newbox=tep_db_insert_id();
		    }
		  } else $newbox=NULL;
		}
		$wishList->move_items($HTTP_POST_VARS['add_wishprod'],$newbox);
	}
  }
*/

/*******************************************************************
************* EMAIL THE WISHLIST TO MULTIPLE FRIENDS ***************
*******************************************************************/

  if (isset($HTTP_POST_VARS['email_prod_x'])) {

		$errors = false;
		$guest_errors = "";
		$email_errors = "";
		$message_error = "";

		if(strlen($HTTP_POST_VARS['message']) < '1') {
			$error = true;
			$message_error .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " . ERROR_MESSAGE . "</div>";
		}			

  		if(tep_session_is_registered('customer_id')) {
			$customer_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
	  		$customer = tep_db_fetch_array($customer_query);
	
			$from_name = $customer['customers_firstname'] . ' ' . $customer['customers_lastname'];
			$from_email = $customer['customers_email_address'];
			$subject = $customer['customers_firstname'] . ' ' . WISHLIST_EMAIL_SUBJECT;
			$link = HTTP_SERVER . DIR_WS_CATALOG . FILENAME_WISHLIST_PUBLIC . "?public_id=" . $customer_id;
	
		//REPLACE VARIABLES FROM DEFINE
			$arr1 = array('$from_name', '$link');
			$arr2 = array($from_name, $link);
			$replace = str_replace($arr1, $arr2, WISHLIST_EMAIL_LINK);
			$message = tep_db_prepare_input($HTTP_POST_VARS['message']);
			$body = $message . $replace;
		} else {
			if(strlen($_POST['your_name']) < '1') {
				$error = true;
				$guest_errors .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " . ERROR_YOUR_NAME . "</div>";
			}
			if(strlen($_POST['your_email']) < '1') {
				$error = true;
				$guest_errors .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " .ERROR_YOUR_EMAIL . "</div>";
			} elseif(!tep_validate_email($_POST['your_email'])) {
				$error = true;
				$guest_errors .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " . ERROR_VALID_EMAIL . "</div>";
			}

			$from_name = stripslashes($_POST['your_name']);
			$from_email = $_POST['your_email'];
			$subject = $from_name . ' ' . WISHLIST_EMAIL_SUBJECT;
			$message = stripslashes($HTTP_POST_VARS['message']);

			$z = 0;
			$prods = "";
			foreach($HTTP_POST_VARS['prod_name'] as $name) {
				$prods .= stripslashes($name) . "  " . stripslashes($HTTP_POST_VARS['prod_att'][$z]) . "\n" . $HTTP_POST_VARS['prod_link'][$z] . "\n\n";
				$z++;
			}
			$body = $message . "\n\n" . $prods . "\n\n" . WISHLIST_EMAIL_GUEST;
	  	}

		//Check each posted name => email for errors.
		$j = 0;
		foreach($HTTP_POST_VARS['friend'] as $friendx) {
			if($j == 0) {
				if($friend[0] == '' && $email[0] == '') {
					$error = true;
					$email_errors .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " . ERROR_ONE_EMAIL . "</div>";
				}
			}

			if(isset($friendx) && $friendx != '') {
				if(strlen($email[$j]) < '1') {
					$error = true;
					$email_errors .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " . ERROR_ENTER_EMAIL . "</div>";
				} elseif(!tep_validate_email($email[$j])) {
					$error = true;
					$email_errors .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " . ERROR_VALID_EMAIL . "</div>";
				}
			}

			if(isset($email[$j]) && $email[$j] != '') {
				if(strlen($friendx) < '1') {
					$error = true;
					$email_errors .= "<div class=\"messageStackError\"><img src=\"images/icons/error.gif\" /> " . ERROR_ENTER_NAME . "</div>";
				}
			}
			$j++;
		}
		if($error == false) {
			$j = 0;
			foreach($HTTP_POST_VARS['friend'] as $friendx) {
				if($friendx != '') {
					tep_mail($friendx, $email[$j], $subject, $friendx . ",\n\n" . $body, $from_name, $from_email);
				}

			//Clear Values
				$friend[$j] = "";
				$email[$j] = "";
				$message = "";

				$j++;
			}

        	$messageStack->add('wishlist', WISHLIST_SENT, 'success');
		}
  }


 $breadcrumb->add(NAVBAR_TITLE_WISHLIST, tep_href_link(FILENAME_WISHLIST, '', 'SSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td>
	  <table border="0" cellspacing="0" cellpadding="0">
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

      </table>
	</td>

    <td width="100%" valign="top"><?php echo tep_draw_form('wishlist_form', tep_href_link(FILENAME_WISHLIST).'?wish_box='.$wish_box); ?>
	  <table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><?php echo HEADING_TITLE; ?>
		</td>
      </tr>


<?php
  if ($messageStack->size('wishlist') > 0) {
?>
      <tr>
        <td></td>
      </tr>

<?php
  }

    if ($customer_id) {
	$box_sel=Array(Array('id'=>'','text'=>'Main List'));
	$boxes=Array();
	$box_qry=tep_db_query("SELECT * FROM customers_wishlist_boxes WHERE customers_id='$customer_id'");
	while ($box_row=tep_db_fetch_array($box_qry)) {
	  $boxes[$box_row['wish_box_id']]=$box_row['wish_box_name'];
	  $box_sel[]=Array('id'=>$box_row['wish_box_id'],'text'=>$box_row['wish_box_name']);
	}
    }

if (is_array($wishList->wishID) && !empty($wishList->wishID)) {
	reset($wishList->wishID);
	if ($customer_id) {
?>
	  <tr>
		<td>Wishlist: <?=tep_draw_pull_down_menu('wish_box',$box_sel,$wish_box,'onChange="document.location=\'wishlist.php?wish_box=\'+this.value"')?>
<?	    if ($wish_box) { ?>
[<a href="javascript:void(0)" onClick="$('wish_box_edit').style.display=''; return false;">Edit</a>]
<span id="wish_box_edit" style="display:none"><input type="text" name="box_name" value="<?=htmlspecialchars($boxes[$wish_box])?>"><input type="submit" name="box_rename" value="Rename"><input type="submit" name="box_delete" value="Delete"></span>
<?          } ?>
		</td>
	  </tr>
<? 	} ?>
	  <tr>
		<td style="padding:10px 0 0 0">
		<table border="0" width="100%" cellspacing="0" cellpadding="3" class="productListing">
		  <tr>
				<td class="productListing-heading1" style="text-align:left"><?php echo BOX_TEXT_IMAGE; ?></td>
				<td class="productListing-heading2"><?php echo BOX_TEXT_PRODUCT; ?></td>
				<td class="productListing-heading3">Price</td>
				<td class="productListing-heading4" style="width:25px; text-align:center"><?=BOX_TEXT_SELECT?></td>
		  </tr>

<?php

		$i = 0;
		foreach ($wishList->get_items($wish_box) AS $wishlist_id) {

			$product_id = tep_get_prid($wishlist_id);
		
		    $products_query = tep_db_query("select pd.products_id, pd.products_name, pd.products_description, p.products_image, p.products_status, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from (" . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd) left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.products_id = '" . $product_id . "' and p.master_products_id = pd.products_id and pd.language_id = '" . $languages_id . "' order by products_name");
			$products = tep_db_fetch_array($products_query);

		      if (($i/2) == floor($i/2)) {
		        $class = "productListing-even";
		      } else {
		        $class = "productListing-odd";
		      }

?>
				  <tr class="<?php echo $class; ?>">
					<td valign="top" class="productListing-data1" align="left" style="padding:2px"><a href="<?php echo tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $wishlist_id, 'NONSSL'); ?>"><?php echo tep_image(DIR_WS_IMAGES . $products['products_image'], $products['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT); ?></a></td>
					<td valign="top" class="productListing-data2" align="left"><div style="color:#676767; font-size:11px; padding:5px 5px 0 10px;"><a href="<?php echo tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $wishlist_id, 'NONSSL'); ?>"><b><?php echo $products['products_name']; ?></b></a>
					<input type="hidden" name="prod_link[]" value="<?php echo tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $wishlist_id, 'NONSSL'); ?>" />
					<input type="hidden" name="prod_name[]" value="<?php echo $products['products_name']; ?>" />
<?php



/*******************************************************************
******** THIS IS THE WISHLIST CODE FOR PRODUCT ATTRIBUTES  *********
*******************************************************************/

                  $attributes_addon_price = 0;

                  // Now get and populate product attributes
                    $wishlist_products_attributes_query = tep_db_query("select products_options_id as po, products_options_value_id as pov from " . TABLE_WISHLIST_ATTRIBUTES . " where customers_id='" . $public_id . "' and products_id = '" . $wishlist['products_id'] . "'");
                    while ($wishlist_products_attributes = tep_db_fetch_array($wishlist_products_attributes_query)) {
                      // We now populate $id[] hidden form field with product attributes
                      echo tep_draw_hidden_field('id['.$wishlist['products_id'].']['.$wishlist_products_attributes['po'].']', $wishlist_products_attributes['pov']);
                      // And Output the appropriate attribute name
                      $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $wishlist_id . "'
                                       and pa.options_id = '" . $wishlist_products_attributes['po'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $wishlist_products_attributes['pov'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'");
                       $attributes_values = tep_db_fetch_array($attributes);
                       if ($attributes_values['price_prefix'] == '+')
                         { $attributes_addon_price += $attributes_values['options_values_price']; }
                       else if ($attributes_values['price_prefix'] == '-')
                         { $attributes_addon_price -= $attributes_values['options_values_price']; }
                       echo '<br /><small><i> ' . $attributes_values['products_options_name'] . ': ' . $attributes_values['products_options_values_name'] . '</i></small>';
                    } // end while attributes for product

                    if (tep_not_null($products['specials_new_products_price'])) {
                       $products_price = '<s>' . $currencies->display_price($products['products_price']+$attributes_addon_price, tep_get_tax_rate($products['products_tax_class_id'])) . '</s> <span class="productSpecialPrice">' . $currencies->display_price($products['specials_new_products_price']+$attributes_addon_price, tep_get_tax_rate($products['products_tax_class_id'])) . '</span>';
                    } else {
                       $products_price = $currencies->display_price($products['products_price']+$attributes_addon_price, tep_get_tax_rate($products['products_tax_class_id']));
                    }

/*******************************************************************
******* CHECK TO SEE IF PRODUCT HAS BEEN ADDED TO THEIR CART *******
*******************************************************************/

			if($cart->in_cart($wishlist_id)) {
				echo '<br><div class="wishlist_itemincart">' . TEXT_ITEM_IN_CART . '</div>';
			}

/*******************************************************************
********** CHECK TO SEE IF PRODUCT IS NO LONGER AVAILABLE **********
*******************************************************************/

   			if($products['products_status'] == 0) {
   				echo '<br><div class="wishlist_itemincart"><b>' . TEXT_ITEM_NOT_AVAILABLE . '</b></div>';
  			}
	
			$i++;
?>
		</div>	</td>
			<td valign="top" class="productListing-data4" style="text-align:center; width:65px; padding:10px 5px 0 5px;"><?php echo $products_price; ?></td>
			<td valign="top" class="productListing-data4" style="text-align:center; width:25px;">
<?php

/*******************************************************************
* PREVENT THE ITEM FROM BEING ADDED TO CART IF NO LONGER AVAILABLE *
*******************************************************************/

//			if($products['products_status'] != 0) {
			echo tep_draw_checkbox_field('add_wishprod[]',$wishlist_id,($wishlist_id==$wish_added));
//echo '<a href="javascript:addToCart({quantity:1,products_id:' . $products['products_id'] . '});"><img src="/layout/img/buttons/english/button_in_cart.gif" alt="Add To Cart" width="72" height="25" border="0"></a>';
//			}
?>
		</td>
		  </tr>

<?php
		}
?>
		</table>
		</td>
	  </tr>
	  <tr>
		<td align="right" style="padding-right:5px;"><br>
<table><tr>
<?
  if ($customer_id) {
    $move_sel=Array(Array('id'=>'','text'=>'Main Wish List'));
    foreach ($boxes AS $box=>$boxname) if ($box!=$wish_box) $move_sel[]=Array('id'=>$box,'text'=>$boxname);
    $move_sel[]=Array('id'=>'+','text'=>'[new]');
?>
<td>
Move to <?=tep_draw_pull_down_menu('move_to',$move_sel,'','onChange="$(\'new_box_name\').style.display=this.value==\'+\'?\'\':\'none\'"')?><input type="text" id="new_box_name" name="new_box_name" value="New Wishlist" style="display:none"></td><td><?php echo tep_image_submit('button_move.gif', 'Move', 'name="move_prod"'); ?></td>
<? } ?>

<td> <?php echo tep_image_submit('button_delete.gif', 'Delete From Wishlist', 'name="delete_prod" value="delete_prod"'); ?></td>

<td> <?php echo tep_image_submit('button_in_cart.gif', 'Add To Cart', 'name="add_prod" value="add_prod"'); ?></td>

</tr></table></td>
 	  </tr>

</table>
<?php

/*******************************************************************
*********** CODE TO SPECIFY HOW MANY EMAILS TO DISPLAY *************
*******************************************************************/


	if(!tep_session_is_registered('customer_id')) {

?>
	<table border="0" width="100%" cellspacing="0" cellpadding="2">
	  <tr>
		<td class="wishlist_main-txt"><?php echo WISHLIST_EMAIL_TEXT_GUEST; ?></td>
	  </tr>
	  <tr>
        <td align="center" style="padding:0 0 15px 0">
		

<?php echo $guest_errors; ?>
<table width="100%" cellspacing="0" cellpadding="5" border="0" style="border:1px dashed #ccc">
				  <tr>
					<td class="wishlist_main-title"><?php echo TEXT_YOUR_NAME; ?></td>
					<td><?php echo tep_draw_input_field('your_name', $your_name); ?></td>
					<td class="wishlist_main-title"><?php echo TEXT_YOUR_EMAIL; ?></td>
					<td><?php echo tep_draw_input_field('your_email', $your_email); ?></td>
			  	  </tr>
				</table>

</td>
			  </tr> </table>

<?php 

	} else {

?>

	<table width="100%" border="0" cellspacing="0" cellpadding="5">

	  <tr>
		<td><?php echo WISHLIST_EMAIL_TEXT; ?></td>
	  </tr>

</table>

<?php

	}

?>
<?php echo $email_errors; ?>
			 <table width="100%" border="0" cellspacing="0" cellpadding="5">
<?php

	$email_counter = 0;
	while($email_counter < 3) {
?>
			  <tr>
				<td class="wishlist_emailto-title"><?php echo TEXT_NAME; ?> </td> 
                <td class="wishlist_emailto"><?php echo tep_draw_input_field('friend[]', $friend[$email_counter]); ?>
</td>
				<td class="wishlist_emailto-title"><?php echo TEXT_EMAIL; ?></td> <td class="wishlist_emailto"><?php echo tep_draw_input_field('email[]', $email[$email_counter]); ?>
</td>
			  </tr>
<?php
	$email_counter++;
	}

?>
			  <tr>
				<td colspan="4"><?php echo $message_error; ?></td>
			  </tr>
			  <tr>
				<td colspan="4">
<table border="0" width="100%" cellspacing="0" cellpadding="0"><tr><td class="wishlist_main-title"> <?php echo TEXT_MESSAGE;?></td> <td class="wishlist_main">&nbsp;<?php echo tep_draw_textarea_field('message', 'soft', 35, 3); ?></td>
			  </tr>
			  <tr>
				<td colspan="4" align="center" style="padding:10px;"><?php echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE, 'name="email_prod" value="email_prod"'); ?></td>

			  </tr>
			</table>

	</td>
	  </tr>
	</table>	
	</form>
<?php

} else { // Nothing in the customers wishlist

?>

	  <tr>
		<td class="wishlist_main"><?php echo BOX_TEXT_NO_ITEMS;?></td>
		  </tr>
		</table>	

</form>

<?php 
}
?>

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
 
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
