<?
  require('includes/application_top.php');
  require(DIR_FS_ADMIN.'apility/apility.php');

?>
<html>
<head></head>
<body>
<?

  $stats=Array(adwords=>Array());

  $campaign_objs=APIlity_getAllCampaigns();
  foreach ($campaign_objs AS $c) {
    $camp_stats=$c->getCampaignStats(date('Y-m-d',time()-86400),date('Y-m-d',time()));
//    foreach ($camp_stats AS $k=>$v) echo "$k:$v\n";
    $stats['adwords']['clicks']+=$camp_stats['clicks'];
    $stats['adwords']['cost']+=$camp_stats['cost'];
    $stats['adwords']['conv']+=$camp_stats['conversions'];
  }
?>

<table>
<tr><td>Cost / Click</td><? foreach ($stats AS $st) { ?><td><?=$st['clicks']?sprintf("%.2f",$st['cost']/$st['clicks']):'-'?></td><? } ?></tr>
<tr><td># Clicks</td><? foreach ($stats AS $st) { ?><td><?=sprintf("%d",$st['clicks'])?></td><? } ?></tr>
<tr><td>Conv Rate</td><? foreach ($stats AS $st) { ?><td><?=$st['clicks']?sprintf("%.2f%%",$st['conv']/$st['clicks']*100):'-'?></td><? } ?></tr>
<tr><td>Cost / Conv</td><? foreach ($stats AS $st) { ?><td><?=$st['conv']?sprintf("%.2f",$st['cost']/$st['conv']):'-'?></td><? } ?></tr>
</table>

</body>
</html>
