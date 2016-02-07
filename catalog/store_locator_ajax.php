<?php

  $no_sts=1;
  require('includes/application_top.php');
  
  if (isset($_GET['stID'])) {
    $st_row=tep_db_fetch_array(tep_db_query("SELECT * FROM stores s LEFT JOIN address_book a ON s.address_book_id=a.address_book_id LEFT JOIN countries c ON a.entry_country_id=c.countries_id LEFT JOIN zones z ON a.entry_zone_id=z.zone_id WHERE s.stores_id='".$_GET['stID']."'"));
    if ($st_row) {
?>
<table width="250" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
<tr><td><b><?=$st_row['stores_title']?></b></td></tr>
<tr><td><?=$st_row['stores_description']?></td></tr>
<tr><td><?=$st_row['entry_street_address']?>, <?=$st_row['entry_city']?> <?=$st_row['zone_code']?>, <?=$st_row['entry_postcode']?></td></tr>
<tr><td>tel <?=$st_row['stores_phone']?></td></tr>
</table>
<?
    } else echo 'No Info on store '.$_GET['stID'];
  } else {
    $cond=Array();
    $ordr=Array();
    $xflds='';
    $zoom=13;
    $maps=tep_module('geomaps');
    $map=$maps->getFirstModule();
    if (isset($_GET['zip']) && $_GET['zip']) {
      $area=$_GET['zip'];
      $geo=$map->getGeoCoords($_GET['zip']);
      $zoom=10;
      if ($geo) {
        $xflds.=", (g.geo_lat-".$geo['lat'].")*(g.geo_lat-".$geo['lat'].")+(g.geo_lng-".$geo['lng'].")*(g.geo_lng-".$geo['lng'].") AS dst";
        $cond[]="((g.geo_lat-".$geo['lat'].")*(g.geo_lat-".$geo['lat'].")+(g.geo_lng-".$geo['lng'].")*(g.geo_lng-".$geo['lng'].")<0.125)";
        $ordr[]='dst';
      }
    }
    if (isset($_GET['state']) && preg_match('/^\w\w$/',$_GET['state'])) {
      $area=strtoupper($_GET['state']);
      $cond[]="(z.zone_code='".$_GET['state']."')";
      $geo=$map->getGeoCoords($_GET['state'].',US');
      $zoom=6;
    }

?>
<h3>Search Results for <?=$area?></h3>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td><b><u>Store</u></b></td>
<td><b><u>Address</u></b></td>
<td><b><u>City</u></b></td>
<td><b><u>State</u></b></td>
<td padding-left:10px;"><b><u>Zip</u></b></td>
<td padding-left:10px;"><b><u>Telephone</u></b></td>
</tr>
<tr><td style="height:5px;"></td></tr>
<?
    $st_qry=tep_db_query("SELECT s.*,g.*,a.*,c.*,z.* $xflds FROM stores s LEFT JOIN address_book a ON s.address_book_id=a.address_book_id LEFT JOIN address_geo_coords g ON s.address_book_id=g.address_book_id LEFT JOIN countries c ON a.entry_country_id=c.countries_id LEFT JOIN zones z ON a.entry_zone_id=z.zone_id WHERE ".($cond?join(' AND ',$cond):0).($ordr?" ORDER BY ".join(',',$ordr):''));
    while ($st_row=tep_db_fetch_array($st_qry)) {
?>
<tr>
<td><a href="javascript:mapShowMarker(<?=$st_row['stores_id']?>)"><?=$st_row['stores_title']?></a></td>
<td><?=$st_row['entry_street_address']?></td>
<td><?=$st_row['entry_city']?></td>
<td><?=$st_row['zone_code']?></td>
<td style="padding-left:10px;"><?=$st_row['entry_postcode']?></td>
<td style="padding-left:10px;"><?=$st_row['stores_phone']?></td>
</tr>
<?
    }
?>
</table>
<?
    if (isset($geo) && $geo) {
?>
<script language="javascript">
  mapGoToLatLng(<?=$geo['lat']?>,<?=$geo['lng']?>,<?=$zoom?>);
</script>
<?
    }
  }
?>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
