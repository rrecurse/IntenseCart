<?
  require('includes/application_top.php');
  
  if (isset($HTTP_GET_VARS['pID'])) {
    $attrs=Array();
    if (isset($HTTP_GET_VARS['attr'])) {
      foreach (split(',',$HTTP_GET_VARS['attr']) AS $attr) {
        list($attr_key,$attr_val)=split(':',$attr);
        $attrs[$attr_key]=$attr_val;
      }
    }
    if (isset($HTTP_GET_VARS['add_qty'])) {
      $cart->add_cart($HTTP_GET_VARS['pID'],$HTTP_GET_VARS['add_qty']+0,$attrs);
    } else if (isset($HTTP_GET_VARS['set_qty'])) {
      if ($HTTP_GET_VARS['set_qty']>0)
        $cart->update_quantity($HTTP_GET_VARS['pID'],$HTTP_GET_VARS['set_qty']+0,$attrs);
      else $cart->remove($HTTP_GET_VARS['pID']);
    }
  }
  
?>

<table>
<?
  foreach ($cart->get_products() AS $product) {
?><tr><td><?
    echo $product['name'];
    $attr_l=Array();

    if (is_array($product['attributes'])) {
      echo("\n<ul>\n");
      foreach ($product['attributes'] AS $option => $value) {
        $attributes_query = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_ATTRIBUTES . " pa
				      LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " popt ON pa.options_id = popt.products_options_id AND popt.language_id = '" . $languages_id . "'
				      LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval ON pa.options_values_id = poval.products_options_values_id AND poval.language_id = '" . $languages_id . "'
                                      WHERE pa.products_id = '" . $product['id'] . "'
                                      AND pa.options_id = '" . $option . "'
                                      AND pa.options_values_id = '" . $value . "'");
        if ($attributes=tep_db_fetch_array($attributes_query)) {
          echo("<li>".$attributes['products_options_name'].": ".$attributes['products_options_values_name']."</li>\n");
	  $attr_l[]="$option:$value";
	}
      }
      echo("</ul>");
    }
?></td>
<td><?=tep_draw_input_field('quantity_'.$product['id'],$product['quantity'],' onChange="ReloadExpressCart(\'set\',\''.$product['id'].'\',this.value,\''.join(',',$attr_l).'\')" size="5"')?></td>
<td><b><?=$currencies->display_price($product['final_price'], tep_get_tax_rate($product['tax_class_id']), $product['quantity'])?></b></td>
</tr><?
  }

?>
</table>
<eval code="reloadShipping()">
