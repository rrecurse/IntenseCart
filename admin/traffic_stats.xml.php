<?
  require('includes/application_top.php');
  header('Content-Type: text/xml');

?>
<xml>
<?
  $ranges=Array();
  if (isset($_GET['range_list'])) $ranges=split(',',$_GET['range_list']);
  if (!sizeof($ranges)) $ranges[]='today';
  foreach ($ranges AS $rline) {
    list($rtag,$start,$finish)=split(':',$rline);
    if (!$rtag) $rtag='traffic';
    if (!$start) {
      switch ($rtag) {
      case 'yesterday':
        $start=date('Y-m-d',time()-86400);
        $finish=date('Y-m-d');
	break;
      case 'thisweek':
        $start=date('Y-m-d',time()-86400*date('w'));
	break;
      case 'lastweek':
        $finish=date('Y-m-d',time()-86400*date('w'));
        $start=date('Y-m-d',time()-86400*(date('w')+7));
	break;
      case 'thismonth':
        $start=date('Y-m-01');
	break;
      case 'lastmonth':
        $finish=date('Y-m-01');
        $start=date('Y-m-01',time()-86400*date('t'));
	break;
      }
    }
    if (!$start) $start=date('Y-m-d');
    if (!$finish) $finish=date('Y-m-d',time()+86400);
    $traffic_query=tep_db_query("SELECT traffic_source,SUM(hit_count) AS hit_count FROM traffic_stats WHERE traffic_date>='$start' AND traffic_date<'$finish' GROUP BY traffic_source");
?>
<<?=$rtag?>>
<? while ($stats_row=tep_db_fetch_array($traffic_query)) { ?>
<<?=$stats_row['traffic_source']?>><?=$stats_row['hit_count']?></<?=$stats_row['traffic_source']?>><? } ?>
</<?=$rtag?>>
<?
  }
?>
</xml>
