<?php
/*
  $Id: shopping_cart.php,v 1.73 2003/06/09 23:03:56 hpdl Exp $

  
  

  

  
*/

  require("includes/application_top.php");
  require("includes/supplier_area_top.php");

  include(DIR_WS_LANGUAGES . $language . '/view_supply_request.php');

  require_once(DIR_WS_CLASSES . FILENAME_SUPPLY_REQUEST);
  $supprq = new supply_request($HTTP_GET_VARS['poID']);

  require_once(DIR_FS_CATALOG_CLASSES . 'boxes.php');
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="/includes/general.js"></script>
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr>

    <td width="100%" valign="top"align=center><?php echo tep_draw_form('cart_quantity', tep_href_link(FILENAME_SHOPPING_CART, 'action=update_product')); ?>
      <table border="0" width="100%" cellspacing="0" cellpadding="0" >
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  if (sizeof($supprq->products) > 0) {
?>
<tr><td class=cart_borde//r>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center" style="padding-left:0px">
<table border="0" width="100%" cellspacing="0" cellpadding="2" class="productListing">
  <tr>
    <td>
<?php
    $info_box_contents = array();
    $info_box_contents[0][] = array('params' => 'class="productListing-heading2"',
                                    'text' => TABLE_HEADING_PRODUCTS);

    $info_box_contents[0][] = array('align' => 'center',
                                    'params' => 'class="productListing-heading3"',
                                    'text' => TABLE_HEADING_QUANTITY);

    $info_box_contents[0][] = array('align' => 'right',
                                    'params' => 'class="productListing-heading4"',
                                    'text' => TABLE_HEADING_TOTAL);

    $any_out_of_stock = 0;
    $products = $supprq->products;
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
// Push all attributes information in an array
      if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        while (list($option, $value) = each($products[$i]['attributes'])) {
          echo tep_draw_hidden_field('id[' . $products[$i]['id'] . '][' . $option . ']', $value);
          $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $products[$i]['id'] . "'
                                       and pa.options_id = '" . $option . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $value . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'");
          $attributes_values = tep_db_fetch_array($attributes);

          $products[$i][$option]['products_options_name'] = $attributes_values['products_options_name'];
          $products[$i][$option]['options_values_id'] = $value;
          $products[$i][$option]['products_options_values_name'] = $attributes_values['products_options_values_name'];
          $products[$i][$option]['options_values_price'] = $attributes_values['options_values_price'];
          $products[$i][$option]['price_prefix'] = $attributes_values['price_prefix'];
        }
      }
    }

    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (($i/2) == floor($i/2)) {
        $info_box_contents[] = array('params' => 'class="productListing-even"');
      } else {
        $info_box_contents[] = array('params' => 'class="productListing-odd"');
      }
      
      $pr_query=tep_db_query("SELECT * FROM products WHERE products_id='".$products[$i]['id']."'");
      $pr=tep_db_fetch_array($pr_query);

      $cur_row = sizeof($info_box_contents) - 1;

      $products_name = '<table border=0 cellspacing=0 cellpadding=0 width="100%" style="border-right:1px solid #FFFFFF; border-bottom:1px solid #FFFFFF;"><tr><td class="ZZZproductListing-data" style="padding:5px" align="center" width=90><a href="' . tep_href_link('../'.FILENAME_PRODUCT_INFO, 'products_id=' . $products[$i]['id']) . '">' . tep_image(DIR_WS_CATALOG_IMAGES . $pr['products_image'], $products[$i]['name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</a></td>' .
                       '    <td class="ZZZproductListing-data" valign="top" style=-"border-right:1px solid #FFFFFF;"><div style="color:#676767; font-size:11px; padding-left:10px; padding-top:5px;"><a font-size:11px" href="' . tep_href_link('../'.FILENAME_PRODUCT_INFO, 'products_id=' . $products[$i]['id']) . '"><b>' . $products[$i]['name'] . '</b></a>';


      if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        reset($products[$i]['attributes']);
        while (list($option, $value) = each($products[$i]['attributes'])) {
          $products_name .= '<br><small><i> - ' . $products[$i][$option]['products_options_name'] . ' ' . $products[$i][$option]['products_options_values_name'] . '</i></small>';
        }
      }

      $products_name .= ' </div></td></tr></table>';

     $info_box_contents[$cur_row][] = array('text' => $products_name);

      $info_box_contents[$cur_row][] = array('align' => 'center',
                                             'params' => 'class="productListing-data3" valign="top" style="padding-top:5px;"',
                                             'text' => $products[$i]['qty']);

      $info_box_contents[$cur_row][] = array('align' => 'right',
                                             'params' => 'class="productListing-data4" valign="top"',
                                             'text' => '<b>' . '</b>');
    }

    new productListingBox($info_box_contents);
?>
</td>
  </tr>
</table>
</td>
  </tr><tr>

        <td style="background:#EFEFEF; border-right:3px solid #FFFFFF; border-left:2px solid #FFFFFF;"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td align="right" style="color:#363636; background: #EFEFEF; font-size:12px; border-right:3px solid #FFFFFF; border-left:2px solid #FFFFFF;"><div style="padding-right:8px;"><?php echo SUB_TITLE_SUB_TOTAL; ?> &nbsp;<span style="color:#DC1400; font-size:12px"><b><?php echo 0; ?></b></span></div></td>
      </tr>
<?php
    if ($any_out_of_stock == 1) {
      if (STOCK_ALLOW_CHECKOUT == 'true') {
?>
      <tr>
        <td class="stockWarning" align="center"><br><?php echo OUT_OF_STOCK_CAN_CHECKOUT; ?></td>
      </tr>
<?php
      } else {
?>
      <tr>
        <td class="stockWarning" align="center"><br><?php echo OUT_OF_STOCK_CANT_CHECKOUT; ?></td>
      </tr>
<?php
      }
    }
?>
      <tr>
        <td style="background: #EFEFEF;  border-right:3px solid #FFFFFF; border-left:2px solid #FFFFFF;"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td style="padding:0px; border-left:1px solid #FFFFFF; border-right:2px solid #FFFFFF;"><table border="0" width="100%" cellspacing="0" cellpadding="1" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
            </table></td>
          </tr>
        </table>
</td>
      </tr></table>
</td></tr>


<?php
  } else {
?>
      <tr>
        <td align="center" class="main"><?php new infoBox(array(array('text' => TEXT_CART_EMPTY))); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td align="right" class="main"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  }
?>
  </table></form></td>
</tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
