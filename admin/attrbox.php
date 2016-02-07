<?
  include('includes/application_top.php');

  $attrs=Array();
  if (isset($_GET['attrs'])) $attrs=is_array($_GET['attrs'])?$_GET['attrs']:split(',',$_GET['attrs']);

  if (isset($_POST['add_attr'])) {
    $tep_db_query("INSERT INTO products_options (language_id,products_options_name) VALUES ('$languages_id','".addslashes($_POST['add_attr'])."')");
    $attrs[]=tep_db_insertid();
  }

  $optns;
  $optns_qry=tep_db_query("SELECT * FROM products_options WHERE language_id='$languages_id'");
  while ($row=tep_db_fetch_array($optns_qry)) $optns[$row['products_options_id']]=$row['products_options_name'];

  $opvals=Array();
  if ($attrs) {
    $opvals_qry=tep_db_query("SELECT * FROM products_options_values_to_products_options v2o LEFT JOIN products_options_values v ON v.products_options_values_id=v2o.products_options_values_id AND v.language_id='$languages_id' WHERE v2o.products_options_id IN ('".join("','",$attrs)."')");
    while ($row=tep_db_fetch_array($opvals_qry)) {
      if (!isset($opvals[$row['products_options_id']])) $opvals[$row['products_options_id']]=Array();
      $opvals[$row['products_options_id']][$row['products_options_values_id']]=$row['products_options_values_name'];
    }
  }
?>
<table><tr>
<td>
<table>
<?
  foreach ($attrs AS $attr) {
    $attr_sel=Array();
    foreach ($opvals[$attr] AS $opvid=>$opv) $attr_sel[]=Array('id'=>$opvid,'text'=>$opv);
?><tr>
  <td><?=$optns[$attr]?>:</td>
  <td><?=tep_draw_pull_down_menu("attr_select_new[$attr]",$attr_sel)?></td>
</tr><?
    unset($optns[$attr]);
  }
  $optn_sel=Array();
  foreach ($optns AS $opid=>$op) $optn_sel[]=Array('id'=>$opid,'text'=>$op);
?>
<tr>
<td><?=tep_draw_pull_down_menu("attr_add_option",$optn_sel)?></td>
<td>
</td>
</table>
</td>

<td>
</td>
</tr></table>
