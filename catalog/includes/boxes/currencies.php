<?php
/*
  $Id: currencies.php,v 1.16 2003/02/12 20:27:31 hpdl Exp $

  
  

  

  
*/

  if (isset($currencies) && is_object($currencies)) {
?>
<?php
    $info_box_contents = array();

     new infoBoxHeading($info_box_contents, false, false);

    reset($currencies->currencies);
    $currencies_array = array();
    while (list($key, $value) = each($currencies->currencies)) {
      $currencies_array[] = array('id' => $key, 'text' => $value['title']);
    }

    $hidden_get_variables = '';
    reset($HTTP_GET_VARS);
    while (list($key, $value) = each($HTTP_GET_VARS)) {
      if ( ($key != 'currency') && ($key != tep_session_name()) && ($key != 'x') && ($key != 'y') ) {
        $hidden_get_variables .= tep_draw_hidden_field($key, $value);
      }
    }

    $info_box_contents = array();
    $info_box_contents[] = array('form' => tep_draw_form('currencies', tep_href_link(basename($PHP_SELF), '', $request_type, false), 'get'),
                                 'align' => 'center',
                                 'text' => tep_draw_pull_down_menu('currency', $currencies_array, $currency, 'onChange="this.form.submit();" style="width:108px; height:17px; border: 1px solid #818181; font: 8pt Tahoma;"') . $hidden_get_variables . tep_hide_session_id());

    new infoBox($info_box_contents);
?>

<?php
  }
?>
