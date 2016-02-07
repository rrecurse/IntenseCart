<?php
/*
  $Id: account.php,v 1.61 2003/06/09 23:03:52 hpdl Exp $

  
  

  

  
*/

  require('includes/application_top.php');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
</head>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td valign="top"><?php require(DIR_WS_INCLUDES . 'column_left.php'); ?></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td>
<?

//  print_r($map->getGeoCoords(Array('street'=>'33-17 Crescent st','postcode'=>'11106','state'=>'NY','country'=>'United States')));

?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td>Store</td>
<td>Address</td>
<td>City</td>
<td>State</td>
<td>Zip</td>
<td>Telephone</td>
</tr>
<?

  $cond=Array();
  $ordr=Array();
  $xflds='';
  if (isset($_GET['zip']) && $_GET['zip']) {
    $maps=tep_module('geomaps');
    $map=$maps->getFirstModule();
    $geo=$map->getGeoCoords($_GET['zip']);
    if ($geo) {
      $xflds.=", (g.geo_lat-".$geo['lat'].")*(g.geo_lat-".$geo['lat'].")+(g.geo_lng-".$geo['lng'].")*(g.geo_lng-".$geo['lng'].") AS dst";
      $cond[]="((g.geo_lat-".$geo['lat'].")*(g.geo_lat-".$geo['lat'].")+(g.geo_lng-".$geo['lng'].")*(g.geo_lng-".$geo['lng'].")<0.125)";
      $ordr[]='dst';
    }
  }
  if (isset($_GET['state']) && $_GET['state']) {
    $cond[]="(z.zone_code='".$_GET['state']."')";
  }

  $st_qry=tep_db_query("SELECT s.*,g.*,a.*,c.*,z.* $xflds FROM stores s LEFT JOIN address_book a ON s.address_book_id=a.address_book_id LEFT JOIN address_geo_coords g ON s.address_book_id=g.address_book_id LEFT JOIN countries c ON a.entry_country_id=c.countries_id LEFT JOIN zones z ON a.entry_zone_id=z.zone_id WHERE ".($cond?join(' AND ',$cond):0).($ordr?" ORDER BY ".join(',',$ordr):''));
  while ($st_row=tep_db_fetch_array($st_qry)) {
?>
<tr>
<td><a href="store_locator.php?lat=<?=$st_row['geo_lat']?>&lng=<?=$st_row['geo_lng']?>"><?=$st_row['stores_title']?></a></td>
<td><?=$st_row['entry_street_address']?></td>
<td><?=$st_row['entry_city']?></td>
<td><?=$st_row['zone_code']?></td>
<td><?=$st_row['entry_postcode']?></td>
<td><?=$st_row['stores_phone']?></td>
</tr>
<?
  }
?>
</table>
	</td>
      </tr></table>
    </td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top">
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
