<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	// # The following code is a sample of how to add new boxes easily.
	// # Just uncomment block below and tweak for your needs! 
	// # Use as many blocks as you need and just change the block names.

	// # $sts_block_name = 'newthingbox';
	// # require(STS_START_CAPTURE);
	// # require(DIR_WS_BOXES . 'new_thing_box.php');
	// # require(STS_STOP_CAPTURE);
	// # $template[$sts_block_name] = strip_unwanted_tags($sts_block[$sts_block_name], $sts_block_name);


    $sts_block_name = 'catmenu';
    require(STS_START_CAPTURE);

    echo tep_draw_form('goto', FILENAME_DEFAULT, 'get', '');
    echo tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onchange="this.form.submit();" style="width:135px; font:normal 11px arial;"');
    echo "</form>\n";
    require(STS_STOP_CAPTURE);
    $template[$sts_block_name] = $sts_block[$sts_block_name];

function tep_get_category_tree($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = '', $include_itself = false) {
    global $languages_id;


    $parent_cat=tep_get_category_info($parent_id);

    if (!is_array($category_tree_array)) $category_tree_array = array();
    if ( (sizeof($category_tree_array) < 1) && ($exclude != '0') ) $category_tree_array[] = array('id' => '0', 'text' => "Catalog");

    if ($include_itself) {
      $category_tree_array[] = array('id' => $parent_id, 'text' => $parent_cat['name']);
    }

    if (is_array($parent_cat['tree'])) foreach ($parent_cat['tree'] AS $categories) {
      if ($exclude != $categories['id']) $category_tree_array[] = array('id' => $categories['id'], 'text' => $spacing . $categories['name']);
      $category_tree_array = tep_get_category_tree($categories['id'], $spacing . '&nbsp;&nbsp;&nbsp;', $exclude, $category_tree_array);
    }

    return $category_tree_array;
  }


	// # Start the "Add to Cart" form
	$template['startform'] = tep_draw_form('cart_quantity', tep_href_link(FILENAME_SHOPPING_CART, 'action=add_product'),'POST',' onSubmit="return (window.checkFormBuyNow?window.checkFormBuyNow(this):true);"');

	// # Add the hidden form variable for the Product_ID
	//$template['startform'] .= tep_draw_hidden_field('products_id', $product_info['products_id']);

	$template['endform'] = "</form>";

	$template['wishlistbutton'] = tep_image_submit('wishlist.gif', 'Remember this Item for later', 'name="wishlist" value="wishlist" onClick="return this.form.wishListClicked=true;"');

	$template['wishlistbox'] = tep_image_submit('wishlist.gif', 'Remember this Item', 'name="wishlist" value="wishlist"');

	if (!tep_session_is_registered('customer_id')) {
		$template['login_header_text'] = '<div class="login_header_text">Welcome! <a href="' . tep_href_link('login.php',(isset($_GET['return']) ? 'return='.$_GET['return'] : ''), 'SSL') . '">Login</a> or <a href="' . tep_href_link('create_account.php','', 'SSL') . '">Create Account</a>.</div>';
	
	} else {

		$template['login_header_text'] = '<div class="login_header_text">Welcome back ' . $_SESSION['customer_first_name'] . ', you are now logged in. &nbsp; <b><a href="'.tep_href_link('account.php').'">view account</a></b> &nbsp; | &nbsp; <a href="' . tep_href_link('logoff.php?return='.ltrim($_SERVER['REQUEST_URI'], '/')) . '"><i>(Not ' . $_SESSION['customer_first_name'] . '?)</i></a> - <a href="' . tep_href_link('logoff.php?return='.ltrim($_SERVER['REQUEST_URI'], '/')) . '">Log off</a></div>';
	}

	$sts_blocks = array();

	$sts_blocks = array(				
                    array('name' => 'feature', 'include' => DIR_WS_MODULES . 'featured_products.php'),
                    array('name' => 'bookmarkme', 'include' => DIR_WS_BOXES . 'bookmark.php'),
					array('name' => 'shopbyprice', 'include' => DIR_WS_BOXES . 'shop_by_price.php'),
                    array('name' => 'main_categories', 'include' => DIR_WS_MODULES . 'main_categories.php'),
					array('name' => 'catlist', 'include' => DIR_WS_MODULES . 'catmenu_css.php'),
		  			array('name' => 'filterlist', 'include' => DIR_WS_MODULES . 'filter_box.php'),
					//array('name' => 'modelselect', 'include' => DIR_WS_BOXES . 'modelselect.php'),
                    //array('name' => 'wishlistbox', 'include' => DIR_WS_BOXES . 'wishlist.php'),
                    //array('name' => 'wishlistbutton', 'include' => DIR_WS_BOXES . 'wishlist.php'),      
					//array('name' => 'modelselect_js', 'include' => DIR_WS_INCLUDES . 'modelselect_js.php')
                    );
foreach ($sts_blocks as $block) {
  $sts_block_name = $block['name'];
  require(STS_START_CAPTURE);
  include($block['include']);
  require(STS_STOP_CAPTURE);
  $template[$sts_block_name] = strip_unwanted_tags($sts_block[$sts_block_name], $sts_block_name);
}


// # ^^^^ All above this line to be gone ^^^^

function tmpl_tag_inc($tag) {
  $sql=tep_db_query("SELECT * FROM ".TABLE_INFORMATION." WHERE info_title='".addslashes($tag)."'");
  if ($row=tep_db_fetch_array($sql)) {
    $sts_block_name = $tag;
    require(STS_START_CAPTURE);
    $tp=IXblock::parse($row['description']);
    $GLOBALS['STSblk']->render($tp);
    require(STS_STOP_CAPTURE);

    return $sts_block[$tag];
  }
  return NULL;
}

function tmpl_tag_shopping_cart_popup($tag) {
  global $cart,$sts_block,$sts_block_name;
  $blk=tep_block('blk_shopping_cart_popup');
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $blk->setContext(Array('cart'=>&$cart),Array());
  $blk->render(Array());
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}


?>
