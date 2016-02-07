<?php
/*
$Id: print_catalog.php,v 1.2 2004/09/17 Stephen Walker SNJ Computers$







*/
?>
<table class="products"  width="100%" border="0" cellspacing="1" cellpadding="1">
<?php
 if (sizeof($print_catalog_array) == '0') {
?>
 <tr>
  <td><?php echo TEXT_NO_NEW_PRODUCTS; ?></td>
 </tr>
<?php
 } else {
    for($i=0; $i<sizeof($print_catalog_array); $i++) {
       if ($print_catalog_array[$i]['specials_price']) {
          $products_price = '<s>' .  $currencies->display_price($print_catalog_array[$i]['price'], tep_get_tax_rate($print_catalog_array[$i]['tax_class_id'])) . '</s>&nbsp;&nbsp;<span class="productSpecialPrice">' . $currencies->display_price($print_catalog_array[$i]['specials_price'], tep_get_tax_rate($print_catalog_array[$i]['tax_class_id'])) . '</span>';
       } else {
          $products_price = $currencies->display_price($print_catalog_array[$i]['price'], tep_get_tax_rate($print_catalog_array[$i]['tax_class_id']));
       }
?>
 <tr>
<!-- product name -->
  <td class="main" align="center"><hr width="95%" align="center">
<?php 
 echo '<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $print_catalog_array[$i]['id'], 'NONSSL') . '"><b> ' . $print_catalog_array[$i]['name'] . ' </b></a> '; 
?>	 
  </td>
 </tr>    
 <tr>
<!-- image & product description -->
  <td><table  width="100%" border="0" cellspacing="5" cellpadding="5">
   <tr>
<!-- product image -->
    <td width="" align="center" valign="top">
<?php 
 echo '<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $print_catalog_array[$i]['id'], 'NONSSL') . '">' . tep_image(DIR_WS_IMAGES . $print_catalog_array[$i]['image'], $print_catalog_array[$i]['name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</a>'; 
?>
    </td>
<!-- product description -->
    <td class="products"><?php echo $print_catalog_array[$i]['description']; ?></td>
   </tr>
  </table></td>
 </tr>
 <tr>
<!-- attributes -->
  <td><!-- product attributes when I figure out the code to get them --></td>
 </tr>
 <tr>
<!-- other information -->
  <td><table class="headerContents" width="80%" align="right" border="0" cellspacing="2" cellpadding="2">
   <tr>
<!-- product price -->
    <td><?php echo TEXT_PRICE . $products_price; ?></td>
<!-- manufacturer -->
    <td><?php echo TEXT_MANUFACTURER . $print_catalog_array[$i]['manufacturer']; ?></td>
   </tr>
   <tr>
<!-- model number -->
    <td><?php echo TEXT_MODEL . $print_catalog_array[$i]['model']; ?></td>
<!-- added to inventory -->
    <td><?php echo TEXT_DATE_ADDED . $print_catalog_array[$i]['date_added']; ?></td>
   </tr>
<?php
 if (($i+1) != sizeof($print_catalog_array)) {
?>
  </table></td>
 </tr>
<?php
}
}
}
?>
</table>