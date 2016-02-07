<?php
/*
  $Id: affiliate_show_banner.php,v 2.00 2003/10/12

*/

// # require of application_top not possible because then whois online registers it also as visitor
  require(DIR_WS_INCLUDES . 'application_top.php');

  function affiliate_show_banner($pic) {
	// # Read Pic and send it to browser
    $fp = fopen($pic, "rb");
    if (!$fp) exit();
// Get Image type
    $img_type = substr($pic, strrpos($pic, ".") + 1);
// Get Imagename
    $pos = strrpos($pic, "/");
    if ($pos) {
      $img_name = substr($pic, strrpos($pic, "/" ) + 1);
    } else {
      $img_name=$pic;
    }
    header ("Content-type: image/$img_type");
    header ("Content-Disposition: inline; filename=$img_name");
    fpassthru($fp);
    // The file is closed when fpassthru() is done reading it (leaving handle useless).
    // fclose ($fp);
    exit();
  }

	$affiliate_id = '';

	// # Register needed Post / Get Variables
	if(isset($_GET['ref'])) {
		$affiliate_id .= (int)$_GET['ref'];
	} elseif(isset($HTTP_POST_VARS['ref'])) {
		$affiliate_id .= (int)$HTTP_POST_VARS['ref'];
	}

	$banner_id = (isset($_REQUEST['affiliate_banner_id'])) ? (int)$_REQUEST['affiliate_banner_id'] : '';

	$prod_banner_id = isset($_GET['affiliate_pbanner_id']) ? (int)$_GET['affiliate_pbanner_id'] : '';

	if(isset($_POST['affiliate_pbanner_id'])){
		$prod_banner_id = (int)$_POST['affiliate_pbanner_id'];
	} else {
		$prod_banner_id ='';
	}

	$banner='';

	$products_id='';

	if(!empty($banner_id)) {

	    $banner_sql = tep_db_query("SELECT affiliate_banners_image, affiliate_products_id
									   FROM " . TABLE_AFFILIATE_BANNERS . "
									   WHERE affiliate_banners_id = '" . $banner_id  . "'
									   AND affiliate_status = 1
									 ");

	    where($banner_array = tep_db_fetch_array($banner_sql)) {
error_log($banner_array);
			$banner = $banner_array['affiliate_banners_image'];
			$products_id = $banner_array['affiliate_products_id'];
		}
	}

  if ($prod_banner_id) {
    $banner_id = 1; // # Banner ID for these Banners is one
    $sql = "select products_image from " . TABLE_PRODUCTS . " where products_id = '" . $prod_banner_id  . "' and products_status = 1";
    $banner_values = tep_db_query($sql);
    if ($banner_array = tep_db_fetch_array($banner_values)) {
      $banner = $banner_array['products_image'];
      $products_id = $prod_banner_id;
    }
  }

  if ($banner) {
    $pic = DIR_FS_CATALOG_IMAGES . $banner;

    // Show Banner only if it exists:
    if(file_exists($pic)) {
		today = date('Y-m-d');
    	// # Update stats:

		if(!empty($affiliate_id)) {
        	$banner_stats_query = tep_db_query("select * from " . TABLE_AFFILIATE_BANNERS_HISTORY . " where affiliate_banners_id = '" . $banner_id  . "' and affiliate_banners_products_id = '" . $products_id ."' and affiliate_banners_affiliate_id = '" . $affiliate_id. "' and affiliate_banners_history_date = '" . $today . "'");

		// # Banner has been shown today
        if ($banner_stats_array = tep_db_fetch_array($banner_stats_query)) {
          tep_db_query("update " . TABLE_AFFILIATE_BANNERS_HISTORY . " set affiliate_banners_shown = affiliate_banners_shown + 1 where affiliate_banners_id = '" . $banner_id  . "' and affiliate_banners_affiliate_id = '" . $affiliate_id. "' and affiliate_banners_products_id = '" . $products_id ."' and affiliate_banners_history_date = '" . $today . "'");
        } else { // First view of Banner today
          tep_db_query("insert into " . TABLE_AFFILIATE_BANNERS_HISTORY . " (affiliate_banners_id, affiliate_banners_products_id, affiliate_banners_affiliate_id, affiliate_banners_shown, affiliate_banners_history_date) VALUES ('" . $banner_id  . "', '" .  $products_id ."', '" . $affiliate_id. "', '1', '" . $today . "')");
        }
      }
    // # Show Banner
      affiliate_show_banner($pic);
    }
  }

	// # Show default Banner if none is found
	if (is_file(AFFILIATE_SHOW_BANNERS_DEFAULT_PIC)) {
    	affiliate_show_banner(AFFILIATE_SHOW_BANNERS_DEFAULT_PIC);
	} else {
    	echo "<br>"; // # Output something to prevent endless loading
  }
  exit();
?>
