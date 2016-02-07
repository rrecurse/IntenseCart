<?php
/*
  $Id: header.php,v 1.19 2002/04/13 16:11:52 hpdl Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/

  if ($messageStack->size > 0) {
    echo $messageStack->output();
  }
?>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td><?php echo tep_image(DIR_WS_IMAGES . 'logo.gif', 'IntenseCart eCommerce', '204', '50'); ?></td>
    <td align="right"><?php echo '<script language="javascript" src="http://www.ideal-handling.com/cslive/livehelp_js.php?department=1&amp;pingtimes=60"></script>'?></td>
  </tr>
  <tr class="headerBar">
    <td class="headerBarContent">&nbsp;&nbsp;<?php echo '<a href="' . tep_href_link('supplier_area.php', '', 'NONSSL') . '" class="headerLink">Supplier\'s Area</a>'; ?></td>
    <td class="headerBarContent" align="right"><?php echo '<a href="' . tep_catalog_href_link() . '" class="headerLink">' . HEADER_TITLE_ONLINE_CATALOG . '</a> &nbsp;|&nbsp; <a href="' . tep_href_link('supplier_area.php', '', 'NONSSL') . '" class="headerLink">Suppliers\'s Area</a>'; ?>&nbsp;&nbsp;</td>
  </tr>
<?php
// BOF: WebMakers.com Added: Quick Return to Catalog
//  if (!tep_session_is_registered('go_back_to')) {
//    tep_session_register('go_back_to');
//    $go_back_to=$REQUEST_URI;
//  }

 // if (!strstr($PHP_SELF,FILENAME_CATEGORIES)) {
?>
<tr>
  <td class="main" colspan="2" align="right"><?php //echo '<FONT COLOR="FF0000"><b>Go Back to:</b></FONT>&nbsp;<a href="supplier_s_statistic.php">' . 'Suppliers\'s Satistic' . '</a>&nbsp; | &nbsp;<a href="supplier_s_categories_products.php?selectbox=supplier">' . 'Suppliers\'s Products' . '</a>&nbsp;&nbsp;'; ?></td>
</tr>
</table>
<?php
//}
// EOF: WebMakers.com Added: Quick Return to Catalog
?>
