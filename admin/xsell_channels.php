<?
  include('includes/application_top.php');
  if (isset($_POST['xsell_channel'])) {
    $xsql=Array();
    foreach ($_POST['xsell_channel'] AS $idx=>$chv) {
      $ch=trim($chv);
      $cht=trim($_POST['xsell_title'][$idx]);
      if ($cht=='') $cht=$ch;
      if ($ch!='') $xsql[$ch]="('$ch','$cht')";
    }
    if ($xsql) {
      tep_db_query("DELETE FROM xsell_channels");
      tep_db_query("INSERT INTO xsell_channels (xsell_channel,xsell_title) VALUES ".join(',',$xsql));
    }
  }
  $xused=tep_db_read("SELECT xsell_channel,COUNT(0) AS ct FROM products_xsell GROUP BY xsell_channel",'xsell_channel','ct');
  $xch=tep_db_read("SELECT * FROM xsell_channels",'xsell_channel','xsell_title');
  foreach ($xused AS $ch=>$ct) if (!isset($xch[$ch])) $xch[$ch]=$ch;
?>
<html>
<head></head>
<body>
<? include(DIR_WS_INCLUDES.'header.php') ?>
<form method="post" action="xsell_channels.php">
<table>
<? foreach ($xch AS $ch=>$cht) { ?>
<tr>
<td><input type="text" name="xsell_channel[]" value="<?=htmlspecialchars($ch)?>"<? if (isset($xused[$ch])) { ?> readonly<? } ?>></td>
<td><input type="text" name="xsell_title[]" value="<?=htmlspecialchars($cht)?>"></td>
</tr>
<? } ?>
<? for ($i=0;$i<3;$i++) { ?>
<tr>
<td><input type="text" name="xsell_channel[]" value=""></td>
<td><input type="text" name="xsell_title[]" value=""></td>
</tr>
<? } ?>
</table>
<input type="submit" name="update" value="Update">
</form>
</body>
</html>
