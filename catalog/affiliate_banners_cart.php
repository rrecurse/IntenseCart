<?php

  require('includes/application_top.php');

  if (!tep_session_is_registered('affiliate_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_AFFILIATE, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_AFFILIATE_BANNERS_CART);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_AFFILIATE_SUMMARY));
  $breadcrumb->add(NAVBAR_TITLE_AFFILIATE_BANNER_CART, tep_href_link(FILENAME_AFFILIATE_BANNERS_CART));

  $affiliate_banners_values = tep_db_query("select * from " . TABLE_AFFILIATE_BANNERS . " order by affiliate_banners_title");
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td height="28" valign="top"><table border="0" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td align="right"><?php echo tep_image(DIR_WS_IMAGES . 'cart.png', HEADING_TITLE); ?></td>
          </tr>
	  <tr>
            <td colspan=2 class="main"><?php echo TEXT_INFORMATION; ?></td>
          </tr>
        </table>
	</td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table width="100%" align="center" border="0" cellpadding="0" cellspacing="0"><td>
<?php
if (tep_db_num_rows($affiliate_banners_values)) {
/*
   while ($affiliate_banners = tep_db_fetch_array($affiliate_banners_values)) {
$prod_id=$affiliate_banners['affiliate_products_id'];
$prod_name=$affiliate_banners['affiliate_banners_title'];
$ban_id=$affiliate_banners['affiliate_banners_id'];
    switch (AFFILIATE_KIND_OF_BANNERS) {
     case 1:
   // Link to Products
   if ($prod_id>0) {

    $link= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_PRODUCT_INFO . '?ref=' . $affiliate_id . '&products_id=' . $prod_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank"><img src="' . HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $affiliate_banners['affiliate_banners_image'] . '" border="0"></a>';
    $link2= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_PRODUCT_INFO . '?ref=' . $affiliate_id . '&products_id=' . $prod_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank">' . $prod_name . '</a>';
   }
   // generic_link
   else {
    $link= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_DEFAULT . '?ref=' . $affiliate_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank"><img src="' . HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $affiliate_banners['affiliate_banners_image'] . '" border="0"></a>';
    $link2= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_DEFAULT . '?ref=' . $affiliate_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank">' . $prod_name . '</a>';
             }
   break;
  case 2:
   // Link to Products
   if ($prod_id>0) {

    $link= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_PRODUCT_INFO . '?ref=' . $affiliate_id . '&products_id=' . $prod_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank"><img src="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_AFFILIATE_SHOW_BANNER . '?ref=' . $affiliate_id . '&affiliate_banner_id=' . $ban_id . '" border="0"></a>';
    $link2= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_PRODUCT_INFO . '?ref=' . $affiliate_id . '&products_id=' . $prod_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank">' . $prod_name . '</a>';
   }
   // generic_link
   else {
    $link= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_DEFAULT . '?ref=' . $affiliate_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank"><img src="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_AFFILIATE_SHOW_BANNER . '?ref=' . $affiliate_id . '&affiliate_banner_id=' . $ban_id . '" border="0"></a>';
    $link2= '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_DEFAULT . '?ref=' . $affiliate_id . '&affiliate_banner_id=' . $ban_id . '" target="_blank">' . $prod_name . '</a>';
             }
   break;
     }

?>
        <table width="95%" align="center" border="0" cellpadding="4" cellspacing="0" class="infoBoxContents">
          <tr>
            <td class="infoBoxHeading" align="center"><?php echo TEXT_AFFILIATE_NAME; ?>&nbsp;<?php echo $affiliate_banners['affiliate_banners_title']; ?></td>
          </tr>
          <tr>
            <td class="smallText" align="center"><b>Text Version:</b> <?php echo $link2; ?></td>
          </tr>
          <tr>
            <td class="smallText" align="center"><?php echo TEXT_AFFILIATE_INFO; ?></td>
          </tr>
          <tr>
            <td class="smallText" align="center">
             <textarea cols="50" rows="3" class="boxText" onClick="javascript:this.select();"><?php echo $link2; ?></textarea>
   </td>
          </tr>
          </table>
<?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?>
<?
   }
*/
}
?>
          </table>
<?php
$embed = '<iframe id="cat_frame" width="100%" scrolling="no" frameborder="0" ALLOWTRANSPARENCY="true" src="' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_DEFAULT . '?cPath=0&template=cartanywhere&cols=3&items_per_page=9&ref=' . $affiliate_id . '&width=220&spec=1"></iframe>';

$embed_helper = htmlentities('<html>
<head>
<script language=\"javascript\">
      // Tell the parent iframe what height the iframe needs to be
      function parentIframeResize()
      {
         var height = getParam(\'height\');
         // This works as our parent\'s parent is on our domain..
         parent.parent.document.getElementById(\'cat_frame\').height = parseInt(height);
      }
      // Helper function, parse param from request string
      function getParam( name )
      {
        name = name.replace(/[\\[]/,\"\\\\\\[\").replace(/[\\]]/,\"\\\\\\]\");
        var regexS = \"[\\\\?&]\"+name+\"=([^&#]*)\";
        var regex = new RegExp( regexS );
        var results = regex.exec( window.location.href );
        if( results == null )
          return \"\";
        else
          return results[1];
      }
</script>
</head>
<body onload="parentIframeResize()">
</body>
</html>');

?>
<textarea style="width:100%; padding:5px;" cols="75" rows="3" class="boxText" onClick="javascript:this.select();"><?php echo $embed; ?></textarea>
<br><br>
<?php echo TEXT_INFORMATION2 ?><br><br>
<?php echo TEXT_AFFILIATE_INFO; ?><br>
<textarea style="width:100%; padding:5px;" cols="75" rows="26" class="boxText" onClick="javascript:this.select();"><?php echo html_entity_decode(stripslashes($embed_helper)); ?></textarea>

<br><br>
<a href="/affiliate_summary.php"><img src="/layout/img/buttons/english/button_back.gif" title="Return to Affiliate Summary"></a>

	 </td>
      </tr>
     </table>

</td>

    <td valign="top"><table border="0" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
