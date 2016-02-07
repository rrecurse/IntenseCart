<?php

	require('includes/application_top.php');

	if ($HTTP_GET_VARS['acID'] > 0) {

		$affiliate_clickthroughs_raw = "SELECT ac.*, 
											   pd.products_name, 
											   a.affiliate_firstname, 
											   a.affiliate_lastname 
										FROM " . TABLE_AFFILIATE_CLICKTHROUGHS . " ac 
									    LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = ac.affiliate_products_id
									    LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = p.products_id AND pd.language_id = '" . $languages_id . "') 
									    LEFT JOIN " . TABLE_AFFILIATE . " a ON a.affiliate_id = ac.affiliate_id
									    WHERE a.affiliate_id = '" . $_GET['acID'] . "' 
									    ORDER BY ac.affiliate_clientdate desc";
	} else {

		$affiliate_clickthroughs_raw = "SELECT ac.*, 
											   pd.products_name, 
											   a.affiliate_firstname, 
											   a.affiliate_lastname 
										FROM " . TABLE_AFFILIATE_CLICKTHROUGHS . " ac 
									   LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = ac.affiliate_products_id
									   LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = p.products_id AND pd.language_id = '" . $languages_id . "') 
									   LEFT JOIN " . TABLE_AFFILIATE . " a ON a.affiliate_id = ac.affiliate_id
									   ORDER BY ac.affiliate_clientdate desc";

	}

	$affiliate_clickthroughs_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $affiliate_clickthroughs_raw, $affiliate_clickthroughs_numrows);
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body style="background-color:transparent; margin:0;">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2" align="center">
  <tr>
  
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
           <td width="58"><img src="images/handshake-icon.gif" width="48" height="48"></td>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
<?php 
  if ($HTTP_GET_VARS['acID'] > 0) {
?>
            <td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_STATISTICS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
<?php
  } else {
?>
            <td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_SUMMARY, '') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
<?php
  }
?>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="5">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" width="50%"><?php echo TABLE_HEADING_AFFILIATE_USERNAME;?> / <?php echo TABLE_HEADING_ENTRY_DATE?> / <?=TABLE_HEADING_IPADDRESS; ?> / <?php echo TABLE_HEADING_CLICKED_PRODUCT; ?></td>
                <td class="dataTableHeadingContent"><?=TABLE_HEADING_REFERRAL_URL;?> / <?php echo TABLE_HEADING_BROWSER; ?></td>
              </tr>
<tr><td colspan="4" height="8"></td></tr>
<?php
	if ($affiliate_clickthroughs_numrows > 0) {

		$affiliate_clickthroughs_values = tep_db_query($affiliate_clickthroughs_raw);
		$number_of_clickthroughs = '0';

		while ($affiliate_clickthroughs = tep_db_fetch_array($affiliate_clickthroughs_values)) {

			$number_of_clickthroughs++;

			if(($number_of_clickthroughs / 2) == floor($number_of_clickthroughs / 2) ) {
				echo '                  <tr class="productListing-even">';
			} else {
				echo '                  <tr class="productListing-odd">';
			}
?>
<?php
	if ($affiliate_clickthroughs['affiliate_products_id'] > 0) {
		$link_to = '<a href="' . tep_catalog_href_link(FILENAME_CATALOG_PRODUCT_INFO, 'products_id=' . $affiliate_clickthroughs['affiliate_products_id']) . '" target="_blank">' . $affiliate_clickthroughs['products_name'] . '</a>';
	} else {
		$link_to = "Home Page";
	}
?>
                <td class="dataTableContent" nowrap valign="top">
<?php echo $affiliate_clickthroughs['affiliate_firstname'] . " " . $affiliate_clickthroughs['affiliate_lastname']; ?>
<br>
<?php echo tep_date_short($affiliate_clickthroughs['affiliate_clientdate']); ?><br>
<?php echo $affiliate_clickthroughs['affiliate_clientip']; ?><br>
<?php echo $link_to; ?>
</td>
                <td class="dataTableContent"><a href="<?php echo $affiliate_clickthroughs['affiliate_clientreferer'];?>" target="_blank"><b><?php echo $affiliate_clickthroughs['affiliate_clientreferer'];?></b></a><br><br>
<?php echo $affiliate_clickthroughs['affiliate_clientbrowser']; ?></td>
</tr>
              <tr>
                <td class="dataTableContent" colspan="4"><?php echo tep_draw_separator('pixel_black.gif', '100%', '1'); ?></td>
              </tr>
<?php
    }
  } else {
?>
              <tr class="productListing-odd">
                <td colspan="7" class="smallText"><?php echo TEXT_NO_CLICKS; ?></td>
              </tr>
<?php
  }
?>
              <tr>
                <td class="smallText" colspan="7"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $affiliate_clickthroughs_split->display_count($affiliate_clickthroughs_numrows,  MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_CLICKS); ?></td>
                    <td class="smallText" align="right"><?php echo $affiliate_clickthroughs_split->display_links($affiliate_clickthroughs_numrows,  MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_all_get_params(array('page', 'oID', 'action'))); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php');?>