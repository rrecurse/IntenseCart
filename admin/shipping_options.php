<?php
  global $total_weight,$free_shipping;
  chdir('../catalog/');
  require('includes/application_top.php');

//  $total_weight = $cart->show_multi_weight_line();
//  $free_shipping = $cart->free_shipping;

  $def='';
  if (isset($_GET['d'])) $def=$_GET['d'];
  require(DIR_WS_LANGUAGES . $language . '/checkout_shipping.php');
//  require(DIR_WS_CLASSES . 'order.php');
//  $order = new order($_GET['oID']);
  tep_session_unregister('shipping');
  $_SESSION['shipping_options'] = array();
  
// By MegaJim
//  foreach ($order->delivery AS $k=>$v) $order->delivery[$k]='';

  if (isset($_GET['zip'])) {
    $order->delivery['postcode'] = tep_db_prepare_input($_GET['zip']);
    $ship_postcode=$_GET['zip'];
//    if (!tep_session_is_registered('ship_postcode')) tep_session_register('ship_postcode');
  }

  if (isset($_GET['zone'])) {
    $ship_zone_id=$_GET['zone'];
    if (!tep_session_is_registered('ship_zone_id')) tep_session_register('ship_zone_id');
  }
  
  if (isset($_GET['cnty'])) {
    $country_query = tep_db_query("select countries_id, countries_name, countries_iso_code_2, countries_iso_code_3 from " . TABLE_COUNTRIES . " where countries_name = '" . $_GET['cnty'] . "'");
    if (tep_db_num_rows($country_query) > 0) {
      $country = tep_db_fetch_array($country_query);
      $order->delivery['country'] = array('id' => $country['countries_id'], 'title' => $country['countries_name'], 'iso_code_2' => $country['countries_iso_code_2'], 'iso_code_3' => $country['countries_iso_code_3']);
    }
  }

  if (isset($_GET['state'])) {
    $order->delivery['state'] = tep_db_prepare_input($_GET['state']);
  }

  if (isset($_GET['subtotal'])) {
    $order->info['subtotal'] = tep_db_prepare_input($_GET['subtotal']);
  }
  
  if (strlen($order->delivery['postcode']) >= 5 && is_numeric($order->delivery['country']['id'])) {

/*  
    $prod_qty=Array();
    foreach(split(',',$_GET['products']) AS $prod_qty_line) {
      if (preg_match('/(\d+)x(.*)/',$prod_qty_line,$prod_qty_sp)) $prod_qty[$prod_qty_sp[2]]=$prod_qty_sp[1];
      else $prod_qty[$prod_qty_line]=1;
    }
//    foreach($order->products AS $prod) {
//      $prod_qty[$prod['id']]=$prod['qty'];
//    }
    $weight_array=Array();
    $free_shipping=1;
    $multi_weight=Array(Array(weight=>0,qty=>1));
    if (sizeof($prod_qty)) {
      $weight_query=tep_db_query("SELECT products_id,products_weight,products_free_shipping,products_separate_shipping FROM ".TABLE_PRODUCTS." WHERE products_id IN ('".join("','",array_keys($prod_qty))."')");
      while ($weight_row=tep_db_fetch_array($weight_query)) {
//        if (!$weight_row['products_free_shipping']) {
	  $free_shipping=0;
	  if ($weight_row['products_separate_shipping'])
	    $multi_weight[]=Array(weight=>$weight_row['products_weight'],qty=>$prod_qty[$weight_row['products_id']]);
	  else
	    $multi_weight[0]['weight']+=$weight_row['products_weight']*$prod_qty[$weight_row['products_id']];
//	}
      }
    }
    foreach ($multi_weight AS $w) {
      if ($w['weight']>0 && $w['qty']>0) $weight_array[]=($w['qty']!=1)?$w['qty'].'x'.$w['weight']:$w['weight'];
    }
    $total_weight = join(',',$weight_array);
*/

    $total_weight = $_GET['weights'];
    $free_shipping = $total_weight == '';
  
    require(DIR_WS_CLASSES . 'shipping.php');
    
    $shipping_modules = new shipping;
    $quotes = $shipping_modules->quote();

      $pkg_list = array();
      foreach ($shipping_modules->weight_list AS $w) {
        if ($w['weight'] > 0) {
			$pkg_list[] = ( ($w['qty'] == 1 ? $w['weight'] : $w['qty']) .'&nbsp;x&nbsp;'.$w['weight']).'&nbsp;lbs';
		}
      }
      
?>
Packages: <b><?php echo join(', ',$pkg_list)?></b><br>
<script type="text/javascript">
  shipData=new Array();
</script>

<table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding: 15px 0px 15px 0px">
<?php
    if (sizeof($quotes) > 1 && sizeof($quotes[0]) > 1) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="50%" valign="top"><?php echo TEXT_CHOOSE_SHIPPING_METHOD; ?></td>
                <td class="main" width="50%" valign="top" align="right"><?php echo '<b>' . TITLE_PLEASE_SELECT . '</b><br>' . tep_image(DIR_WS_IMAGES . 'arrow_east_south.gif'); ?></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
    } elseif ($free_shipping == false) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="100%" colspan="2"></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
    }
    if ($free_shipping == true) {
		$def=$quotes[0]['id'].'_'.$quotes[0]['methods'][0]['id'];
		$_SESSION['shipping_options'][$quotes[0]['id']] = array($quotes[0]['methods'][0]['id'] => array('name' => $quotes[0]['methods'][0]['title'], 'cost' => $free_shipping?0:$quotes[$i]['methods'][$j]['cost']));
		if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
		$shipping = array(id => $def, title => $quotes[0]['methods'][0]['title'], cost => 0);
?>
<script type="text/javascript">
  shipData['<?php echo $def;?>']={ title: <?php echo tep_js_quote($quotes[0]['methods'][0]['title'])?>, value: '0' };
</script>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td colspan="2" width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" colspan="3"><b><?php echo FREE_SHIPPING_TITLE; ?></b>&nbsp;<?php echo $quotes[$i]['icon']; ?></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, 's', 0)">
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" width="100%"><?php echo sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) . tep_draw_hidden_field('shipping_null', 'free_free'); ?></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
                </table></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
    } else {
      $radio_buttons = 0;
      for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" colspan="3" nowrap><?php if (isset($quotes[$i]['icon']) && tep_not_null($quotes[$i]['icon'])) { echo $quotes[$i]['icon']; } ?><br>
<b><?php echo $quotes[$i]['module']; ?></b></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
<?php
        if (isset($quotes[$i]['error'])) {
?>
                  <tr>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" colspan="3"><?php echo $quotes[$i]['error']; ?></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
<?php
        } else {
          for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
// set the radio button to be checked if it is the method chosen
	    $shp=$quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'];
	    if (!$def) $def=$shp;
/*
	    if (!isset($shipping['id'])) {
	      $checked = false;
	      if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
	      $shipping = array();
	      $shipping['id'] = $shp;
	      $shipping['title'] = $quotes[$i]['methods'][$j]['title'];
	      $shipping['cost'] = $quotes[$i]['methods'][$j]['cost'];
	    } else {
              $checked = (($shp == $shipping['id']) ? true : false);
	      if ($checked) $def=$shp;
	    }
*/
          $checked = (($shp == $def) ? true : false);

              echo '                  <tr class="moduleRow">' . "\n";
	    $val=tep_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0));
?>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" nowrap><?php echo $quotes[$i]['methods'][$j]['title']; ?></td>
<script language="javascript">
  shipData['<?=$shp?>']={ title: <?=tep_js_quote($quotes[$i]['methods'][$j]['title'])?>, value: '<?=sprintf('%.2f',$val)?>' };
</script>
<?php
            if ( ($n > 1) || ($n2 > 1) ) {
?>
                    <td class="main"><?php echo $currencies->format($val); ?></td>
                    <td class="main" align="right"><?php echo tep_draw_radio_field('shipping_null', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'], $checked, 'onClick="setShipping(this.value,shipData[this.value].title,shipData[this.value].value);"'); ?></td>
<?php
            } else {
?>
                    <td class="main" align="right" colspan="2"><?php echo $currencies->format($val) . tep_draw_hidden_field('shipping_null', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']); ?></td>
<?php
            }
?>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
<?php
	    if (!isset($_SESSION['shipping_options'][$quotes[$i]['id']])) $_SESSION['shipping_options'][$quotes[$i]['id']] = array();
	    //$_SESSION['shipping_options'][$quotes[$i]['id']][$quotes[$i]['methods'][$j]['id']] = $currencies->format(tep_add_tax($quotes[$i]['methods'][$j]['cost'], $quotes[$i]['tax']));
	    $_SESSION['shipping_options'][$quotes[$i]['id']][$quotes[$i]['methods'][$j]['id']] = array('name' => $quotes[$i]['methods'][$j]['title'], 'cost' => $quotes[$i]['methods'][$j]['cost']);
            $radio_buttons++;
          }
        }
?>
                </table></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
      }
    }
?>
</table>
<?
  } else {
?>
<table border="0" width="100%" height="80" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center" valign="middle" class="main" style="font-weight: bold">Please select your shipping country and zip code above to view shipping rates.</td>
  </tr>
</table>
<?
  }
?>
<script language="javascript">
  setShipping('<?=$def?>',shipData['<?=$def?>'].title,shipData['<?=$def?>'].value);
</script>
