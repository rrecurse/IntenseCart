<script language="javascript" type="text/javascript">
<!--
var cats = new Array();
var cats_id = new Array();
var prod = new Array();
var prod_id = new Array();
<?
  $cat_query = tep_db_query("SELECT c.categories_id as id, cd.categories_name as name FROM categories c LEFT JOIN categories_description cd ON (c.categories_id = cd.categories_id) WHERE cd.language_id = '".$languages_id."' AND c.parent_id = '0' ORDER BY cd.categories_name");
  
  $x = 0;

  //$dd1 = '';

  while ($cat = tep_db_fetch_array($cat_query)) {
    $y = 0;
    $z = 0;
    $dd2 = '';
    $dd_id2 = '';

    //$dd1 .= '<option value="' . $cat['id'] . '">' . $cat['name'] . "</option>\r\n";
    
    $cat2_query = tep_db_query("SELECT c.categories_id as id, cd.categories_name as name FROM categories c LEFT JOIN categories_description cd ON (c.categories_id = cd.categories_id) WHERE cd.language_id = '".$languages_id."' AND c.parent_id = '" . $cat['id'] . "' ORDER BY cd.categories_name");
    while ($cat2 = tep_db_fetch_array($cat2_query)) {
      $dd3 = '';
      $dd_id3 = '';
      if ($dd2 != '') {
        $dd2 .= ', ';
        $dd_id2 .= ', ';
      }
      $dd2 .= '"' . $cat2['name'] . '"';
      $dd_id2 .= '"' . $cat2['id'] . '"';
      
      $prod_query = tep_db_query("SELECT c.categories_id as id, cd.categories_name as name FROM categories c LEFT JOIN categories_description cd ON (c.categories_id = cd.categories_id) WHERE cd.language_id = '".$languages_id."' AND c.parent_id = '" . $cat2['id'] . "' ORDER BY cd.categories_name");
      
      if (@mysql_num_rows($prod_query) <= 0) {
        $prod_query = tep_db_query("SELECT pd.products_id as id, pd.products_name as name FROM products_description pd LEFT JOIN products_to_categories p2c ON (pd.products_id = p2c.products_id) WHERE p2c.categories_id = '".$cat2['id']."' AND pd.language_id = '".$languages_id."'");
        $prod_type = 'p';
      } else {
        $prod_type = 'c';
      }
      while ($prod = tep_db_fetch_array($prod_query)) {
        if ($dd3 != '') {
          $dd3 .= ', ';
          $dd_id3 .= ', ';
        }
        $dd3 .= '"' . $prod['name'] . '"';
        $dd_id3 .= '"' . $prod_type . ($prod_type == 'c' ? $cat['id'] . '_' . $cat2['id'] . '_' : '') . $prod['id'] . '"';
      }
      if (tep_db_num_rows($prod_query) > 0) {
        echo "  prod[" . $cat2['id'] . "] = new Array(" . $dd3 . ");\r\n";
        echo "  prod_id[" . $cat2['id'] . "] = new Array(" . $dd_id3 . ");\r\n";
      } else {
        echo "  prod[" . $cat2['id'] . "] = new Array();\r\n";
        echo "  prod_id[" . $cat2['id'] . "] = new Array();\r\n";
      }
        
    }
    
    if (tep_db_num_rows($cat2_query) > 0) {
      echo "  cats[" . $cat['id'] . "] = new Array(" . $dd2 . ");\r\n";
      echo "  cats_id[" . $cat['id'] . "] = new Array(" . $dd_id2 . ");\r\n";
      
    } else {
      echo "  cats[" . $cat['id'] . "] = new Array();\r\n";
      echo "  cats_id[" . $cat['id'] . "] = new Array();\r\n";
    }

  }

?>

function gendd2() {

  var num = document.formgoto.dd1.value;
  var boxlength = 0;

  document.formgoto.dd2.selectedIndex = 0;
  if (cats[num] !== undefined) {
    for ( ctr=0;ctr<cats[num].length;ctr++) {
      boxlength++;
      document.formgoto.dd2.options[ctr] = new Option(cats[num][ctr], cats_id[num][ctr]);
    }
    if (cats[num].length > 0) {
      document.formgoto.dd2.disabled = false;
    } else {
      document.formgoto.dd2.disabled = true;
    }
  } else {
    document.formgoto.dd2.disabled = true;
    document.formgoto.dd2.options[0] = new Option();
    
  }
  
  document.formgoto.dd2.length = boxlength;
  document.formgoto.dd2.options.length = boxlength;

  gendd3();
}

function gendd3() {

  
  var num = document.formgoto.dd2.value;
  
  var boxlength = 0;
  
  document.formgoto.dd3.selectedIndex = 0;
  if (prod[num]) {
    for ( ctr=0;ctr<prod[num].length;ctr++) {
      boxlength++;
      document.formgoto.dd3.options[ctr] = new Option(prod[num][ctr], prod_id[num][ctr]);
    }
    if (prod[num].length > 0) {
      document.formgoto.dd3.disabled = false;
    } else {
      document.formgoto.dd3.disabled = true;
    }
  } else {
    
    document.formgoto.dd3.options[0] = new Option();
    document.formgoto.dd3.disabled = true;
  }
  
  document.formgoto.dd3.length = boxlength;
  document.formgoto.dd3.options.length = boxlength;


}
//-->
</script>