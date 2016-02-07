<?php

  require('includes/application_top.php');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
</head>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td valign="top"><?php require(DIR_WS_INCLUDES . 'column_left.php'); ?></td>
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td>
<!--	<div>Go To Address: <input type="text" id="map_goto"><button type="button" onClick="mapGoTo($('map_goto').value); return false;">Go</button>
	</div> -->

<script language="JavaScript">
  function loadStoreList(arg) {
    $('store_list').innerHTML='loading...';
    new ajax('<?=DIR_WS_CATALOG?>store_locator_ajax.php?'+arg,{update:$('store_list')});
  }
</script>

<?

  $maps=tep_module('geomaps');
  $map=$maps->getFirstModule();
  
  $st_qry=tep_db_query("SELECT s.stores_id,s.stores_type,g.* FROM stores s,address_geo_coords g WHERE s.address_book_id=g.address_book_id");
  while ($st_row=tep_db_fetch_array($st_qry)) {
    $map->addPoint($st_row['stores_id'],$st_row['geo_lat'],$st_row['geo_lng'],$st_row['density_factor'],$st_row['stores_type']);
  }

  $map->ajaxInfoUrl=DIR_WS_CATALOG.'store_locator_ajax.php?stID=';
  if (isset($_GET['lat']) && isset($_GET['lng'])) {
    $lat=$_GET['lat'];
    $lng=$_GET['lng'];
  } else if (isset($_GET['goto'])) {
    $geo=$map->getGeoCoords($_GET['goto']);
    $lat=$geo['lat'];
    $lng=$geo['lng'];
  }
  $map->render($lat,$lng);

?>
	<div style="padding-top:10px;">
	Zip: <input type="text" onChange="if (this.value.match(/^\d\d\d\d\d$/)) loadStoreList('zip='+this.value); this.value='';"><button type="button">Go</button> 
    &nbsp;	or State: <select onChange="loadStoreList('state='+this.value); this.options[0].selected=true;">
	<option value=""> Select State </option>
<option value="AL">Alabama</option>
<option value="AK">Alaska</option>
<option value="AZ">Arizona</option>
<option value="AR">Arkansas</option>
<option value="CA">California</option>
<option value="CO">Colorado</option>
<option value="CT">Connecticut</option>
<option value="DE">Delaware</option>
<option value="FL">Florida</option>
<option value="GA">Georgia</option>
<option value="HI">Hawaii</option>
<option value="ID">Idaho</option>
<option value="IL">Illinois</option>
<option value="IN">Indiana</option>
<option value="IA">Iowa</option>
<option value="KS">Kansas</option>
<option value="KY">Kentucky</option>
<option value="LA">Louisiana</option>
<option value="ME">Maine</option>
<option value="MD">Maryland</option>
<option value="MA">Massachusetts</option>
<option value="MI">Michigan</option>
<option value="MN">Minnesota</option>
<option value="MS">Mississippi</option>
<option value="MO">Missouri</option>
<option value="MT">Montana</option>
<option value="NE">Nebraska</option>
<option value="NV">Nevada</option>
<option value="NH">New Hampshire</option>
<option value="NJ">New Jersey</option>
<option value="NM">New Mexico</option>
<option value="NY">New York</option>
<option value="NC">North Carolina</option>
<option value="ND">North Dakota</option>
<option value="OH">Ohio</option>
<option value="OK">Oklahoma</option>
<option value="OR">Oregon</option>
<option value="PA">Pennsylvania</option>
<option value="RI">Rhode Island</option>
<option value="SC">South Carolina</option>
<option value="SD">South Dakota</option>
<option value="TN">Tennessee</option>
<option value="TX">Texas</option>
<option value="UT">Utah</option>
<option value="VT">Vermont</option>
<option value="VA">Virginia</option>
<option value="WA">Washington</option>
<option value="WV">West Virginia</option>
<option value="WI">Wisconsin</option>
<option value="WY">Wyoming</option>
</select>

	</div>

<script language="JavaScript">
  function loadStoreList(arg) {
    $('store_list').innerHTML='loading...';
    new ajax('<?=DIR_WS_CATALOG?>store_locator_ajax.php?'+arg,{update:$('store_list')});
  }
<? if (isset($_GET['zip']) || isset($_GET['state'])) { ?>
  var fp=window.onload;
  window.onload=function() {
    if (fp) fp();
    loadStoreList('zip=<?=$_GET['zip']?>&state=<?=$_GET['state']?>');
  }
<? } ?>
</script>
	<div id="store_list">
<? //include(DIR_WS_INCLUDES.'store_locator.php'); ?>
	</div>
	</td>
      </tr></table>
    </td>

    <td width="<?php echo BOX_WIDTH; ?>" valign="top">
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>


<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
