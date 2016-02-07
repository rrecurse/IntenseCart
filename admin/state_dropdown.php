<?
  require('includes/application_top.php');
  
  $sec=isset($_GET['sec'])?$_GET['sec']:'ship';
  $country = $_GET['country'];
  $postal = $_GET['postal'];
//  if ($country == '') die('Error');
  if (isset($_GET['d'])) $def0 = $_GET['d'];
  $zones_array = array();
  $zones_query=NULL;
  if ($postal) {
    $zones_query = tep_db_query("select z.zone_id, z.zone_name from countries c,zones_to_postal zp,zones z where zp.zone_country_id = c.countries_id AND c.countries_name='$country' AND zp.postal_min<='$postal' AND zp.postal_max>='$postal' AND zp.zone_id=z.zone_id AND zp.zone_country_id=z.zone_country_id order by z.zone_name");
  }
  if (!$zones_query || (tep_db_num_rows($zones_query)<1))
    $zones_query = tep_db_query("select z.zone_id, z.zone_name from countries c,zones z where z.zone_country_id = c.countries_id AND c.countries_name='$country' order by zone_name");
  $def='';
  while ($zones_values = tep_db_fetch_array($zones_query)) {
    if ($def=='' || $def0==$zones_values['zone_name']) $def=$zones_values['zone_name'];
    $zones_array[] = array('id' => $zones_values['zone_name'], 'text' => $zones_values['zone_name']);
  }
  if ( sizeof($zones_array)>0 ) {
    if (sizeof($zones_array)>1) echo tep_draw_pull_down_menu($sec . '_state_null', $zones_array, $def, 'onChange="setState(\''.$sec.'\',this.value)"');
    else echo '<b>'.$zones_array[0]['text'].'</b>';
  } else {
    echo tep_draw_input_field($sec. '_state_null', $def0, 'style="width:150px onChange="setState(\''.$sec.'\',this.value)" maxlength=32');
    $def=$def0;
  }
?>
<eval code="setState('<?=$sec?>','<?=$def?>')">
