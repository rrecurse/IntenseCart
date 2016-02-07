<?php
  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'IXeditList.php');

class edit_inventory extends IXEditList {
  function edit_inventory() {
    $this->cPath=split('_',$_GET['cPath']);
    foreach ($this->cPath AS $idx=>$cat) if (!$cat) unset($this->cPath[$idx]);
    $this->cat=preg_replace('/.*_/','',$_GET['cPath'])+0;
    $this->lang=$GLOBALS['languages_id'];
    $this->switchIcons=Array(
      0=>Array(text=>'Disabled',iconActive=>'icon_status_red_light.gif',iconInactive=>'icon_status_red.gif'),
      1=>Array(text=>'Enabled',iconActive=>'icon_status_green_light.gif',iconInactive=>'icon_status_green.gif'),
    );
  }
  function getItem($id) {
    if (preg_match('/^c(\d+)/',$id,$idp)) {
      return tep_db_read("SELECT * FROM categories c LEFT JOIN categories_description cd ON (c.categories_id=cd.categories_id AND cd.language_id='{$this->lang}') WHERE c.parent_id='{$this->cat}' ORDER BY sort_order",'categories_id');
    } else if (preg_match('/^p(\d+)/',$id,$idp)) {
    }
    return Array();
  }
  function getListing() {
    $cats=tep_db_read("SELECT * FROM categories c LEFT JOIN categories_description cd ON (c.categories_id=cd.categories_id AND cd.language_id='{$this->lang}') WHERE c.parent_id='{$this->cat}' ORDER BY sort_order",'categories_id');
    foreach ($cats AS $cid=>$c) $cats[$cid]['key']='c'.$cid;
    $prods=tep_db_read("SELECT * FROM products_to_categories p2c,products p LEFT JOIN products_description pd ON (p.master_products_id=pd.products_id AND pd.language_id='{$this->lang}') WHERE p2c.categories_id='{$this->cat}' AND p.products_id=p2c.products_id ORDER BY p.products_sort_order",'products_id');
    foreach ($prods AS $pid=>$p) $prods[$pid]['key']='p'.$pid;
    return array_merge($cats,$prods);
  }
  function itemHeader($item) {
    if (preg_match ('/^c/',$item['key'])) {
?>
<table width="100%" border="0">
<tr>
<td width="50"><?=tep_image(DIR_WS_CATALOG_IMAGES.$item['categories_image'],'SubCat',24,32)?></td>
<td width="50">[<a href="<?=$this->makeLink('cPath='.join('_',array_merge($this->cPath,Array($item['categories_id']))))?>">List</a>]</td>
<td align="left"><?=$item['categories_name']?></td>
<td width="50">[<a href="<?=$this->makeLink('edit='.$item['key'])?>">Edit</a>]</td>
<td width="50">[<a href="<?=$this->makeLink('delete='.$item['key'])?>" onClick="return window.confirm('Do you want to delete this entry?')">Delete</a>]</td>
</tr>
</table>
<?
    } if (preg_match ('/^p/',$item['key'])) {
?>
<table width="100%" border="0">
<tr>
<td width="50"><?=tep_image(DIR_WS_CATALOG_IMAGES.$item['products_image'],'Product',24,32)?></td>
<td align="left"><?=$item['products_name']?></td>
<td width="50">[<a href="edit_product.php?cPath=<?=join('_',$this->cPath)?>&pID=<?=$item['products_id']?>">Edit</a>]</td>
<td width="50">[<a href="<?=$this->makeLink('delete='.$item['key'])?>" onClick="return window.confirm('Do you want to delete this entry?')">Delete</a>]</td>
</tr>
</table>
<?
    }
  }
  function itemContent($item) {
    if (preg_match ('/^c/',$item['key'])) {
?>
?>
<table width="100%" border="0">
<tr>
<td><?=tep_image(DIR_WS_CATALOG_IMAGES.$item['categories_image'],'SubCat',72,96)?></td>
<td><table width="100%" border="0">
<tr><td colspan="2" style="font-size:large"><?=$item['categories_name']?></td></tr>
<tr><td>Template:</td><td><?=$item['categories_template']?></td></tr>
<tr><td>Date Added:</td><td><?=$item['date_added']?></td></tr>

</table></td></tr></table>
<?
    } if (preg_match ('/^p/',$item['key'])) {
?>
<table width="100%" border="0">
<tr>
<td><?=tep_image(DIR_WS_CATALOG_IMAGES.$item['products_image'],'Product',72,96)?></td>
<td><table width="100%" border="0">
<tr><td colspan="2" style="font-size:large"><?=$item['products_name']?></td></tr>
<tr><td colspan="2" style="font-size:small"><?=$item['products_info']?></td></tr>
<tr><td>Template:</td><td><?=$item['products_template']?></td></tr>
<tr><td>Regular Price:</td><td>$<?=sprintf("%.2f",$item['products_price'])?></td></tr>

</table></td></tr></table>
<?
    }
  }
  function itemEdit($item) {
?>
<table>
        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']', tep_get_category_name($cInfo->categories_id, $languages[$i]['id']));
          // HTC BOC
          $category_htc_title_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_title_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_title($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_desc_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_desc_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_desc($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_keywords_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_keywords_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_keywords($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_description_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_textarea_field('categories_htc_description[' . $languages[$i]['id'] . ']', 'hard', 30, 5, tep_get_category_htc_description($cInfo->categories_id, $languages[$i]['id']));
          // HTC EOC
        }

        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_NAME . $category_inputs_string);
        $contents[] = array('text' => '<br>' . tep_image(DIR_WS_CATALOG_IMAGES . $cInfo->categories_image, $cInfo->categories_name) . '<br>' . DIR_WS_CATALOG_IMAGES . '<br><b>' . $cInfo->categories_image . '</b>');
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image'));
        $contents[] = array('text' => '<br>' . 'Header Tags Category Title' . $category_htc_title_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Description' . $category_htc_desc_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Keywords' . $category_htc_keywords_string);
        $contents[] = array('text' => '<br>' . 'Categories Page Description' . $category_htc_description_string);
        // HTC EOC
        $contents[] = array('text' => '<br>' . TEXT_EDIT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', $cInfo->sort_order, 'size="2"'));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;




<tr><td>Key:</td><td><?=$item['key']?htmlspecialchars($item['key']):tep_draw_input_field('key',$item['key'])?></td><td>&nbsp;</td></tr>
<tr><td>Icon:</td><td><input type="file" name="icon"></td><td><?=$item['icon']?tep_image(DIR_WS_CATALOG_IMAGES.$item['icon'],''):'&nbsp;'?></td></tr>
<tr><td>Shadow:</td><td><input type="file" name="shadow"></td><td><?=$item['shadow']?tep_image(DIR_WS_CATALOG_IMAGES.$item['shadow'],''):'&nbsp;'?></td></tr>
</table>
<?
  }
  function itemSave($id,$item) {
    if (preg_match('/^c(.*)/',$id,$idp)) {
      if ($idp[1]>0) $categories_id = $idp[1]+0;
      $sort_order = $_POST['sort_order'];
      $sql = array('sort_order' => $_POST['sort_order'],'categories_template'=>$_POST['categories_template'],'products_template'=>$_POST['products_template'],'categories_status'=>($_POST['categories_status']?1:0));
      if ($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES))
         $sql['categories_image']=$categories_image->filename;
      if ($categories_id) {
        $sql['parent_id']=$this->cat;
	$sql['products_class']=$this->pClass;
        $sql['date_added']='now()';
        tep_db_perform('categories',$sql);
        $new_id = tep_db_insert_id();
      } elseif ($action == 'update_category') {
        $sql['last_modified']='now()';
        $sql_data_array = array_merge($sql_data_array, $update_sql_data);
        tep_db_perform('categories', $sql, 'update', "categories_id = '$categories_id'");
      }
      $languages = tep_get_languages();
      foreach ($languages AS $i=>$lng) {
        $language_id = $lng['id'];
        $sql = array();
	foreach (Array('categories_name','categories_htc_description','categories_htc_title_tag','categories_htc_desc_tag','categories_htc_keywords_tag') AS $fld)
	  $sql[$fld]=$_POST[$fld][$i];
	if (!$categories_id) {
          $sql['categories_id']=$new_id;
          $sql['language_id']=$language_id;
          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
          tep_db_perform('categories_description',$sql);
        } else {
    	  $url_rewrite->purge_item(sprintf('c%d',$categories_id),1);
          tep_db_perform('categories_description', $sql, 'update', "categories_id = '$categories_id' and language_id = '$language_id'");
        }
      }
    }
  }
  function itemDelete($id,$item) {

  }
}

$inv=new edit_inventory;
if (!$inv->preRender()) exit;

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Store Types Control</title>
<link rel="stylesheet" type="text/css" href="js/css.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<?


$inv->render();

?>


</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
