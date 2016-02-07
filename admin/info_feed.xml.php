<?
  require('includes/application_top.php');
  header("Content-Type: ".(isset($_GET['ctype'])?$_GET['ctype']:'text/xml'));
  
  function ids_list($lst) {
    $ids=split(',',$lst);
    $qids=Array();
    foreach ($ids AS $id) $qids[]="'$id'";
    return join(',',$qids);
  }
  
  function build_feed($sql,&$feed,$idflds) {
    $db_query=tep_db_query($sql);
    while($db_row=tep_db_fetch_array($db_query)) {
      $f=&$feed;
      foreach ($idflds AS $key => $idf) {
        if (!isset($f[$key])) $f[$key]=Array();
	$f=&$f[$key];
	$id=$key.'_'.$db_row[$idf];
        if (!isset($f[$id])) $f[$id]=Array();
	$f=&$f[$id];
      }
      foreach ($db_row AS $key => $val) $f[$key]=$val;
    }
  }
  
  function print_xml($feed) {
    echo "\n";
    foreach ($feed AS $key=>$val) {
      echo "<$key>";
      if (is_array($val)) print_xml($val); else echo str_replace('"','&quot;',str_replace('<','&lt;',str_replace('>','&gt;',str_replace('&','&amp;',$val))));
      echo "</$key>\n";
    }
  }

  $feed=Array();

  if (isset($_GET['orders'])) {
    $ids=ids_list($_GET['orders']);
    build_feed("SELECT * FROM orders WHERE orders_id IN ($ids)",$feed,Array(orders=>'orders_id'));
    build_feed("SELECT * FROM orders_total WHERE orders_id IN ($ids)",$feed,Array(orders=>'orders_id',orders_total=>'orders_total_id'));
  }
  
  echo "<xml>";
  print_xml($feed);
?>
</xml>