<?
  require('includes/application_top.php');

$src='test';
$r=tep_db_query("UPDATE traffic_stats SET hit_count=hit_count+1 WHERE stats_date=NOW() AND stats_source='$src'");

echo mysql_affected_rows();

?>
