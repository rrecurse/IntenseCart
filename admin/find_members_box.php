<?php

  require('includes/application_top.php');

  $found_count=0;  
  $find_str=preg_split('/\s+/',$HTTP_GET_VARS['name']);
  $find_max=isset($HTTP_GET_VARS['max'])?$HTTP_GET_VARS['max']+0:10;
  if (sizeof($find_str)) {
    $find_cond=Array();
    foreach ($find_str AS $str) {
      $find_cond[]="(customers_firstname LIKE '%$str%' OR customers_lastname LIKE '%$str%' OR customers_email_address LIKE '%$str%')";
    }
?>
<table>
<?
    $find_query=tep_db_query("SELECT * FROM ".TABLE_CUSTOMERS." WHERE ".join(' AND ',$find_cond)." LIMIT $find_max");
    while ($cust_info=tep_db_fetch_array($find_query)) {
?>
  <tr>
    <td><?=$cust_info['customers_id']?></td>
    <td><a href="javascript:void(0)" onClick="doMemberLogin('<?=$cust_info['customers_email_address']?>','<?=$cust_info['customers_password']?>'); return false;"><?=$cust_info['customers_firstname']?> <?=$cust_info['customers_lastname']?></a></td>
    <td>(<?=$cust_info['customers_email_address']?>)</td>
  </tr>
<?
      $found_count++;
    }
  }

?>
</table>
<?
  if (!$found_count) echo 'Not Found';
?>
