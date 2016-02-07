<?
  require('includes/application_top.php');
  header("Content-Type: ".(isset($_GET['ctype'])?$_GET['ctype']:'text/xml'));

?>
<suggest>
<?
  require(DIR_WS_FUNCTIONS.'search_suggest.php');
  foreach(tep_search_suggest() AS $sgst) { ?>
<item>
  <name><?=htmlspecialchars(preg_replace('/[\x7F-\xFF]/','',$sgst['name']))?></name>
<? if (isset($sgst['code'])) { ?>
  <code><?=htmlspecialchars(preg_replace('/[\x7F-\xFF]/','',$sgst['code']))?></code>
<? } ?>
  <url><?=htmlspecialchars($sgst['url'])?></url>
  <img><?=htmlspecialchars($sgst['img'])?></img>
</item>
<?
  }
?>
</suggest>