<?

function tep_search_suggest() {
  global $languages_id;
  $rs=Array();
if(tep_session_is_registered('sppc_customer_group_id')) { 
  $suggest_query=tep_db_query("SELECT p.master_products_id, p.products_image, pd.products_name, GROUP_CONCAT(pm.products_sku SEPARATOR ' ') AS code 
FROM ".TABLE_PRODUCTS." p 
LEFT JOIN products pm ON (p.master_products_id=pm.master_products_id) 
LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON (p.master_products_id=p2c.products_id) 
LEFT JOIN ".TABLE_CATEGORIES." c ON (p2c.categories_id=c.categories_id) 
LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON pd.products_id=p.master_products_id AND pd.language_id='$languages_id' 
WHERE p.master_products_id=p.products_id 
AND p.products_status=1 
AND (p2c.categories_id=0 OR c.categories_status=1)
GROUP BY p.master_products_id 
ORDER BY p.products_sort_order");
} else {
$suggest_query=tep_db_query("SELECT p.master_products_id, p.products_image, pd.products_name, GROUP_CONCAT(pm.products_sku SEPARATOR ' ') AS code 
FROM ".TABLE_PRODUCTS." p 
LEFT JOIN products pm ON (p.master_products_id=pm.master_products_id) 
LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON (p.master_products_id=p2c.products_id) 
LEFT JOIN ".TABLE_CATEGORIES." c ON (p2c.categories_id=c.categories_id) 
LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON pd.products_id=p.master_products_id AND pd.language_id='$languages_id' 
WHERE p.master_products_id=p.products_id 
AND p.products_status=1 
AND (p2c.categories_id=0 OR c.categories_status=1)
AND p.products_price BETWEEN 0.01 AND 999999
GROUP BY p.master_products_id 
ORDER BY p.products_sort_order");
}
  while ($row=tep_db_fetch_array($suggest_query)) {
    
$rs[]=Array('name'=>$row['products_name'],'code'=>$row['code'],'url'=>tep_href_link(FILENAME_PRODUCT_INFO,'products_id='.$row['master_products_id']),'img'=>$row['products_image']?tep_image_src(DIR_WS_HTTP_CATALOG.DIR_WS_IMAGES.$row['products_image'],AUTOSUGGEST_THUMB_WIDTH,AUTOSUGGEST_THUMB_HEIGHT):'');
  }
  return $rs;
}


?>