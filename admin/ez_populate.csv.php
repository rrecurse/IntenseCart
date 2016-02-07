<?php

ini_set('session.cache_limiter','private');
include('includes/application_top.php');
$date = date('d-m-Y');
header("Content-Type: text/csv; charset=utf8");
header('Content-Disposition: attachment; filename="products_'.$date.'.csv"');
header('Cache-Control: public');

$cols = array();
$attr_qry = tep_db_query("SELECT * from products_options WHERE language_id='$languages_id' AND products_options_name!=''");
while($attr_row = tep_db_fetch_array($attr_qry)) $cols[$attr_row['products_options_id']] = 'attr_'.strtolower(str_replace(' ','_',$attr_row['products_options_name']));

?>
categories_name,products_name,products_model,products_sku,products_price,products_quantity,products_info,products_description,products_head_keywords_tag,manufacturers_name<?=$cols?','.join(',',$cols):''?>,xsell
<?php if($_GET['data']) {
	$qry = IXdb::query("SELECT m.*,pd.*,p.* 
			    FROM products p 
			    LEFT JOIN products_description pd ON (p.master_products_id = pd.products_id AND language_id='$languages_id') 
			    LEFT JOIN manufacturers m ON (m.manufacturers_id = p.manufacturers_id) 
			    ORDER BY p.master_products_id,p.products_id = p.master_products_id");
  $pf = array();
  while ($row=IXdb::fetch($qry)) {
    if ($pf[$row['master_products_id']] && $row['products_id']==$row['master_products_id']) continue;
    $pf[$row['master_products_id']]++;
    $cat=Array();
    $cid=IXdb::read("SELECT categories_id FROM products_to_categories WHERE products_id='{$row['master_products_id']}'",NULL,'categories_id');
    while ($cid) {
      $cinfo=IXdb::read("SELECT * FROM categories c LEFT JOIN categories_description cd ON (c.categories_id=cd.categories_id AND cd.language_id='$languages_id') WHERE c.categories_id='$cid'");
      $cat[]=$cinfo['categories_name'];
      $cid=$cinfo['parent_id'];
    }
    $csv=Array(
      join(' >> ',array_reverse($cat)),
      $row['products_name'],
      $row['products_model'],
      $row['products_sku'],
      $row['products_price'],
      $row['products_quantity'],
      $row['products_info'],
      $row['products_description'],
      $row['products_head_keywords_tag'],
      $row['manufacturers_name']
    );
    $attrs=IXdb::read("SELECT * FROM products_attributes pa LEFT JOIN products_options_values pov ON (pa.options_values_id=pov.products_options_values_id AND pov.language_id='$languages_id') WHERE pa.products_id='{$row['products_id']}'",'options_id','products_options_values_name');
    foreach ($cols AS $attr=>$attrn) $csv[]=$attrs[$attr];
    $csv[]=join(',',IXdb::read("SELECT p.products_model FROM products_xsell x LEFT JOIN products p ON (x.xsell_id=p.products_id) WHERE x.products_id='{$row['products_id']}' AND p.products_model!=''",Array(NULL),'products_model'));
    foreach ($csv AS $idx=>$c) {
      if (preg_match('/[^\w \.\-]/',$c)) $csv[$idx]='"'.str_replace('"','""',$c).'"';
    }
    echo join(',',$csv)."\n";
  }
} 
?>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
