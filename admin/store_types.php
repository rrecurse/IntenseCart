<?php
  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'IXeditList.php');

class edit_store_types extends IXEditList {
  function edit_store_types() {
    $this->modl=tep_module('geomaps_google','geomaps');
  }
  function getItem($id) {
    $item=$this->modl->getExtra('icons',$id);
    $item['key']=$id;
    return $item;
  }
  function getListing() {
    $lst=$this->modl->getExtra('icons');
    if ($lst) {
      foreach ($lst AS $k=>$l) $lst[$k]['key']=$k;
    } else $lst=Array();
    return $lst;
  }
  function itemHeader($item) {
?>
<table width="100%" border="0">
<tr><td><?=$item['key']?></td><td align="right"><?=$item['key']?></td><td width="50">[<a href="<?=$this->makeLink('edit='.$item['key'])?>">Edit</a>]</td><td width="50">[<a href="<?=$this->makeLink('delete='.$item['key'])?>" onClick="return window.confirm('Do you want to delete this entry?')">Delete</a>]</td></tr>
</table>
<?
  }
  function itemContent($item) {
?>
<table><tr><td>
</td></tr></table>
<?
  }
  function itemEdit($item) {
    $clst=tep_db_read("SELECT countries_id AS id,countries_name AS text FROM countries",Array(NULL),Array('id'=>'id','text'=>'text'));
    $maps=tep_module('geomaps');
    $map=$maps->getFirstModule();
    $types=Array();
    $tlst=$map->getExtra('icons');
    if ($tlst) foreach ($tlst AS $type=>$tinfo) $types[]=Array('id'=>$type,'text'=>$type);
?>
<table>
<tr><td>Key:</td><td><?=$item['key']?htmlspecialchars($item['key']):tep_draw_input_field('key',$item['key'])?></td><td>&nbsp;</td></tr>
<tr><td>Icon:</td><td><input type="file" name="icon"></td><td><?=$item['icon']?tep_image(DIR_WS_CATALOG_IMAGES.$item['icon'],''):'&nbsp;'?></td></tr>
<tr><td>Shadow:</td><td><input type="file" name="shadow"></td><td><?=$item['shadow']?tep_image(DIR_WS_CATALOG_IMAGES.$item['shadow'],''):'&nbsp;'?></td></tr>
</table>
<?
  }
  function _saveUp($fld,$dir) {
    if (isset($_FILES[$fld]) && !$_FILES[$fld]['error'] && $_FILES[$fld]['size']) {
      $name=preg_replace('|.*[\/\\\\]|','',$_FILES[$fld]['name']);
      if (@rename($_FILES[$fld]['tmp_name'],$dir.$name)) return $name;
    }
    return NULL;
  }
  function itemSave($id,$item) {
    if (!$id) $id=$_POST['key'];
    if (!$id) return false;
    $dir=DIR_FS_CATALOG_IMAGES.'geo_icons/';
    if (!is_dir($dir)) @mkdir($dir,0777);
    if ($icf=$this->_saveUp('icon',$dir)) $this->modl->setExtra('icons',$id,'icon','geo_icons/'.$icf);
    if ($shf=$this->_saveUp('shadow',$dir)) $this->modl->setExtra('icons',$id,'shadow','geo_icons/'.$shf);
    return true;
  }
  function itemDelete($id,$item) {
    $this->modl->setExtra('icons',$id,NULL);
  }
}

$stores=new edit_store_types;
if (!$stores->preRender()) exit;

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


$stores->render();

?>


</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
