<?php
/*
  $Id: all_products.php,v 3.0 2004/02/21 by Ingo (info@gamephisto.de)

  
  

  

  
*/

  require('includes/application_top.php');

  include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ALL_PRODUCTS);

  $breadcrumb->add(HEADING_TITLE, tep_href_link(FILENAME_ALL_PRODUCTS, '', 'NONSSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<title><?php echo HEADING_TITLE; ?> :: <?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<style type="text/css" media="screen">
<!--
.catentry {font-family: Verdana, Arial, sans-serif; font-size: 10px; font-weight: bold; color: #3E5E89; }
h2 {font-family: Verdana, Arial, sans-serif; font-size: 20px; font-weight: bold; }
h1, h2{margin-bottom:0px; margin-top:0px; line-height: 1em;}
-->
</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->
<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td><h1><?php echo HEADING_TITLE; ?></h1></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table width="100%" cellspacing="0" cellpadding=0" border="0">
<?php

$language_code = (isset($HTTP_GET_VARS['language']) && tep_not_null($HTTP_GET_VARS['language'])) ? $HTTP_GET_VARS['language'] : DEFAULT_LANGUAGE;

$included_categories_query = tep_db_query("SELECT c.categories_id, c.parent_id, cd.categories_name FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd WHERE c.categories_id = cd.categories_id AND cd.language_id = FLOOR($languages_id)");

$inc_cat = array();
while ($included_categories = tep_db_fetch_array($included_categories_query)) {
  $inc_cat[] = array (
     'id' => $included_categories['categories_id'],
     'parent' => $included_categories['parent_id'],
     'name' => $included_categories['categories_name']);
  }
$cat_info = array();
for ($i=0; $i<sizeof($inc_cat); $i++)
  $cat_info[$inc_cat[$i]['id']] = array (
    'parent'=> $inc_cat[$i]['parent'],
    'name'  => $inc_cat[$i]['name'],
    'path'  => $inc_cat[$i]['id'],
    'link'  => '' );

for ($i=0; $i<sizeof($inc_cat); $i++) {
  $cat_id = $inc_cat[$i]['id'];
  while ($cat_info[$cat_id]['parent'] != 0){
    $cat_info[$inc_cat[$i]['id']]['path'] = $cat_info[$cat_id]['parent'] . '_' . $cat_info[$inc_cat[$i]['id']]['path'];
    $cat_id = $cat_info[$cat_id]['parent'];
    }
  $link_array = split('_', $cat_info[$inc_cat[$i]['id']] ['path']);
  for ($j=0; $j<sizeof($link_array); $j++) {
    $cat_info[$inc_cat[$i]['id']]['link'] .= '&nbsp;<a href="' . tep_href_link(FILENAME_DEFAULT, 'cPath=' . $cat_info[$link_array[$j]]['path']) . '"><nobr>' . $cat_info[$link_array[$j]]['name'] . '</nobr></a> - ';
    }
  }

$products_query = tep_db_query("SELECT p.products_id, pd.products_name, pc.categories_id FROM " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " pc WHERE p.products_id = pd.products_id AND p.products_id = pc.products_id AND p.products_status = 1 AND pd.language_id = FLOOR($languages_id) ORDER BY pc.categories_id, pd.products_name");

while($products = tep_db_fetch_array($products_query)) {
  echo
"          <tr>\n" .
'           <td width="33%" class="pageheading"><span class="catentry">' . (($memory == $products['categories_id'])? '': $cat_info[$products['categories_id']]['link']) . "</span></td>\n" .
'           <td class="pageheading"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $products['products_id'] . (($language_code == DEFAULT_LANGUAGE) ? '' : ('&language=' . $language_code))) . '"><span class="catentry">' . $products['products_name'] . "</span></a></td>\n" .
"          </tr>\n";
  $memory = $products['categories_id'];
  }
?>
         </table></td>
      </tr>
      <tr>
        <td align="right" class="main"><br><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT, '', 'NONSSL') . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
<!-- You are not the only one, John! -->