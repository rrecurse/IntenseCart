<?php
/*
  IntenseCart, E-Commerce and Internet Marketing Solutions
  http://www.intensecart.com

  Copyrights (c) 2007 IntenseGroup Inc.

  Redistribution without explicit written consent is forbidden under US and International Copyright Laws
*/

  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'IXeditList.php');

class edit_stores extends IXEditList {
  function getListQuery() {
    return "select * from stores s LEFT JOIN address_book ab ON (s.address_book_id=ab.address_book_id) ORDER BY s.stores_title ASC";
  }
  function getItemQuery($id) {
    return "select * from stores s LEFT JOIN address_book ab ON (s.address_book_id=ab.address_book_id) WHERE stores_id='$id'";
  }
  function itemHeader($item) {
?>
<table width="740" border="0">
<tr>
<td width="290"><?=$item['stores_title']?></td>
<td align="right" width="150"><?=$item['entry_postcode']?> <?=$item['zone_code']?> <?=$item['countries_iso_code_2']?></td>
<td align="right" width="150">[<a href="<?=$this->makeLink('edit='.$item['stores_id'])?>">Edit</a>]</td>
<td align="right" width="150">[<a href="<?=$this->makeLink('delete='.$item['stores_id'])?>" onClick="return window.confirm('Do you want to delete this entry?')">Delete</a>]</td>
</tr>
</table>
<?
  }
  function itemContent($item) {
?>
<table width=100%><tr><td>
<p><?=$item['entry_street_address']?>, <?=$item['entry_city']?>, <?=$item['entry_state']?> <?=$item['entry_postcode']?><br>
<?=$item['stores_phone']?></p>
<blockquote><?=htmlspecialchars($item['stores_description'])?></blockquote>
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
<table width=100%>
<tr><td>Store Name:</td><td><?=tep_draw_input_field('stores_title',$item['stores_title'])?></td></tr>
<tr><td>Type:</td><td><?=tep_draw_pull_down_menu('stores_type',$types,$item['stores_type'])?></td></tr>
<tr><td>Street Address:</td><td><?=tep_draw_input_field('entry_street_address',$item['entry_street_address'])?></td></tr>
<tr><td>City:</td><td><?=tep_draw_input_field('entry_city',$item['entry_city'])?></td></tr>
<tr><td>State:</td><td><?=tep_draw_input_field('entry_state',$item['entry_state'])?></td></tr>
<tr><td>Postal Code:</td><td><?=tep_draw_input_field('entry_postcode',$item['entry_postcode'])?></td></tr>
<tr><td>Country:</td><td><?=tep_draw_pull_down_menu('entry_country_id',$clst,$item['entry_country_id']?$item['entry_country_id']:223)?></td></tr>
<tr><td>Phone:</td><td><?=tep_draw_input_field('stores_phone',$item['stores_phone'])?></td></tr>
<tr><td>Description:</td><td><?=tep_draw_textarea_field('stores_description','',80,8,$item['stores_description'])?></td></tr>
</table>
<?
  }
  function itemSave($id,$item) {
    $stn=$_POST['entry_state'];
    $state=tep_db_read("SELECT * FROM zones WHERE zone_name='$stn' OR zone_code='$stn'",NULL,Array('zone_id'=>'zone_id','zone_name'=>'zone_name'));
    $a_rec=Array(
      'entry_street_address'=>stripslashes($_POST['entry_street_address']),
      'entry_city'=>stripslashes($_POST['entry_city']),
      'entry_state'=>$state['zone_name'],
      'entry_postcode'=>$_POST['entry_postcode'],
      'entry_zone_id'=>$state['zone_id'],
      'entry_country_id'=>$_POST['entry_country_id'],
    );
    $s_rec=Array(
      'stores_title'=>stripslashes($_POST['stores_title']),
      'stores_description'=>stripslashes($_POST['stores_description']),
      'stores_phone'=>stripslashes($_POST['stores_phone']),
      'stores_type'=>stripslashes($_POST['stores_type']),
    );
    if ($id) {
      tep_db_perform('address_book',$a_rec,'update',"address_book_id='".($addrid=$item['address_book_id'])."'");
      tep_db_perform('stores',$s_rec,'update',"stores_id='$id'");
    } else {
      tep_db_perform('address_book',$a_rec);
      $addrid=$s_rec['address_book_id']=tep_db_insert_id();
      tep_db_perform('stores',$s_rec);
      $id=tep_db_insert_id();
    }
    $maps=tep_module('geomaps');
    $map=$maps->getFirstModule();
    $geo=NULL;
    if ($map) $geo=$map->getGeoCoords($_POST['entry_street_address'].','.$_POST['entry_city'].','.$_POST['entry_postcode'].','.tep_db_read("SELECT countries_name FROM countries WHERE countries_id='".$_POST['entry_country_id']."'",NULL,'countries_name'));
    if ($geo) {
      $lat=$geo['lat'];
      $lng=$geo['lng'];
      $df=tep_db_read("SELECT LOG(SUM(1/((g.geo_lat-'$lat')*(g.geo_lat-'$lat')+(g.geo_lng-'$lng')*(g.geo_lng-'$lng')))) AS df FROM stores s LEFT JOIN address_geo_coords g ON s.address_book_id=g.address_book_id WHERE stores_id<'$id'",NULL,'df');
      tep_db_query("REPLACE address_geo_coords (address_book_id,geo_lat,geo_lng,density_factor) VALUES ('$addrid','".$geo['lat']."','".$geo['lng']."','$df')");
    }
    else tep_db_query("DELETE FROM address_geo_coords WHERE address_book_id='$addrid'");
    return true;
  }
  function itemDelete($id,$item) {
    tep_db_query("DELETE FROM address_book WHERE address_book_id='".$item['address_book_id']."'");
    tep_db_query("DELETE FROM stores WHERE stores_id='$id'");
  }
}

$stores=new edit_stores;
if (!$stores->preRender()) exit;

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Store Locator Control</title>
<link rel="stylesheet" type="text/css" href="js/css.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
</head>
<body style="margin:5px; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table width="740" cellpadding="0" cellspacing="0" border="0">
<tr>
<td width="290">&nbsp; <b>Store</b> </td>
<td width="150"><b>Zip</b> </td>
<td width="150"><b>Edit</b> </td>
<td width="150"><b>Delete</b></td>
</tr>
<tr>
<td colspan="4" height=5></td>
</tr>
</table>
<?


$stores->render();

?>


</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
