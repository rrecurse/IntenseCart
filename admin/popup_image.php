<?php
/*
  $Id: popup_image.php,v 1.7 2003/06/20 00:40:23 hpdl Exp $

  Copyright (c) 2003 IntenseCart eCommerce
  
*/

  require('includes/application_top.php');

if ($HTTP_GET_VARS['image']) {

     $big_image = tep_image(DIR_WS_CATALOG_IMAGES . $HTTP_GET_VARS['image'], $HTTP_GET_VARS['image']);

} else {
  reset($HTTP_GET_VARS);
  while (list($key, ) = each($HTTP_GET_VARS)) {
    switch ($key) {
      case 'banner':
        $banners_id = tep_db_prepare_input($HTTP_GET_VARS['banner']);

        $banner_query = tep_db_query("select banners_title, banners_image, banners_html_text from " . TABLE_BANNERS . " where banners_id = '" . (int)$banners_id . "'");
        $banner = tep_db_fetch_array($banner_query);

        $page_title = $banner['banners_title'];

        if ($banner['banners_html_text']) {
          $image_source = $banner['banners_html_text'];
        } elseif ($banner['banners_image']) {
          $image_source = tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $banner['banners_image'], $page_title);
        }
        break;
    }
  }
 }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<title><?php echo $page_title; ?></title>
<script type="text/javascript">
<!--

function resize() {
  window.resizeTo(document.images[0].width + 30, document.images[0].height + 100);
}
//--></script>
</head>

<body onload="resize();">

<?php
   if ($HTTP_GET_VARS['image']) {
         echo $big_image;
         } else {
         echo $image_source;
   }
 ?>

</body>

</html>
