<?
  require('includes/application_top.php');

  $add_category_id=isset($HTTP_GET_VARS['add_category_id'])?$HTTP_GET_VARS['add_category_id']+0:0;
  $add_product_id=isset($HTTP_GET_VARS['add_product_id'])?$HTTP_GET_VARS['add_product_id']+0:0;

  $cat_tree=Array();
  $cat_query=tep_db_query("SELECT c.categories_id,c.parent_id,cd.categories_name FROM ".TABLE_CATEGORIES." c, ".TABLE_CATEGORIES_DESCRIPTION." cd WHERE c.categories_id=cd.categories_id AND cd.language_id='$languages_id' ORDER BY c.sort_order");
  while ($cat_data=tep_db_fetch_array($cat_query)) {
    if (!isset($cat_tree[$cat_data['parent_id']])) $cat_tree[$cat_data['parent_id']]=Array();
    $cat_tree[$cat_data['parent_id']][]=Array(id=>$cat_data['categories_id'],text=>$cat_data['categories_name']);
  }

function build_cat_pull_down($cat_tree,$cat,$pr) {
  $rs=Array();
  foreach($cat_tree[$cat] AS $cat_info) {
    $rs[]=Array(id=>$cat_info['id'],text=>$pr.$cat_info['text']);
    $rs=array_merge($rs,build_cat_pull_down($cat_tree,$cat_info['id'],$pr.'. '));
  }
  return $rs;
}

  $cat_pull_down=array_merge(Array(Array(id=>'',text=>'--Category--')),build_cat_pull_down($cat_tree,0,''));

?>

<table><tr>
  <td>Add to Cart: </td>
  <td>
    <?=tep_draw_pull_down_menu('add_category_id', $cat_pull_down, $add_category_id, 'onChange="ReloadAddProduct(this.value);"')?>
  </td>
<?
  if ($add_category_id) { 
    $products_query=tep_db_query("SELECT pd.products_id,pd.products_name FROM ". TABLE_PRODUCTS_TO_CATEGORIES ." p2c, ". TABLE_PRODUCTS_DESCRIPTION ." pd WHERE p2c.categories_id='$add_category_id' AND p2c.products_id=pd.products_id AND pd.language_id='$languages_id' ORDER BY pd.products_name");
    $products_pull_down=Array(Array(id=>'',text=>'--Select Product--'));
    while ($products=tep_db_fetch_array($products_query)) {
      $products_pull_down[]=Array(id=>$products['products_id'],text=>$products['products_name']);
    }
?>
<td>
  <?=tep_draw_pull_down_menu('add_product_id', $products_pull_down, $add_product_id, 'onChange="ReloadAddProduct(\''.$add_category_id.'\',this.value);"')?>
</td><?
  }
?>
</tr>
</table>

<?
  if ($add_product_id) {
?><hr>
<eval code="AddProductReset()">
<?
    $attr_boxes=Array();
    $attr_query=tep_db_query("SELECT pa.options_id,pa.options_values_id,po.products_options_name,pov.products_options_values_name,pa.options_values_price,pa.price_prefix FROM ".TABLE_PRODUCTS_ATTRIBUTES." pa LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON pa.options_id=po.products_options_id AND po.language_id='$languages_id' LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pa.options_values_id=pov.products_options_values_id AND pov.language_id='$languages_id' WHERE pa.products_id='$add_product_id' ORDER BY pa.products_attributes_id");
    while($attr_data=tep_db_fetch_array($attr_query)) {
      if (!isset($attr_boxes[$attr_data['options_id']])) $attr_boxes[$attr_data['options_id']]=Array(text=>$attr_data['products_options_name'],sel=>Array());
      $attr_boxes[$attr_data['options_id']]['sel'][]=Array(id=>$attr_data['options_values_id'],text=>$attr_data['products_options_values_name'].($attr_data['options_values_price']!=0?sprintf(' (%s%.2f)',$attr_data['price_prefix'],$attr_data['options_values_price']):''));
    }
    if (sizeof($attr_boxes)) {
?><table><?
      foreach($attr_boxes AS $attr_op_id=>$attr_box) {
?><tr><td><?=$attr_box['text']?></td><td><?=tep_draw_pull_down_menu('add_product_attr_'.$attr_op_id,$attr_box['sel'],'',' onChange="AddProductSetAttr('.$attr_op_id.',this.value)"')?>
<eval code="AddProductSetAttr(<?=$attr_op_id?>,<?=$attr_box['sel'][0]['id']?>)"></td></tr>
<?
      }
?></table><hr><?
    }
?>
Quantity: <?=tep_draw_input_field('new_product_quantity',1,' size=5 onChange="AddProductSetQty(this.value)"')?>
<button name="new_product_add" onclick="AddProduct('<?=$add_product_id?>')">Add</button>
<?
  }
?>
