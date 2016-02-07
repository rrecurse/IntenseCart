<?
  require('includes/application_top.php');
  require(DIR_FS_ADMIN.'apility/apility.php');

?>
<html>
<head></head>
<body>
<?

  $pID=$_GET['pID'];
  $campaign_objs=APIlity_getAllCampaigns();
  $grplist=Array(363943490);
  if ($campaign_objs && sizeof($campaign_objs)) {
    $campaigns=Array();
//    $grplist=Array();


    foreach ($campaign_objs AS $c) {
      $campaigns[$c->getId()]=Array('name'=>$c->getName(),'adgroup'=>'');
//      $grps=$c->getAllAdGroups();
//      if ($grps) foreach ($grps AS $g) {
//        echo ". ".$g->getId().": ".$g->getName()."<br>\n";
//      }
    }
    
    $groups=Array();
    $keywords=Array();
    
    $kwd_query=tep_db_query("SELECT pd.products_head_keywords_tag AS kwds,l.code AS lang FROM ".TABLE_PRODUCTS_DESCRIPTION." pd LEFT JOIN ".TABLE_LANGUAGES." l ON pd.language_id=l.languages_id WHERE pd.products_id='$pID'");
    while ($kwd_row=tep_db_fetch_array($kwd_query)) {
      $keywords[$kwd_row['lang']]=Array();
      foreach (split(',',$kwd_row['kwds']) AS $kwd) {
        $keywords[$kwd_row['lang']][strtolower($kwd)]=Array(text=>$kwd);
      }
    }

    foreach ($grplist AS $grpid) {
      $group_obj=APIlity_createAdGroupObject($grpid);
      if ($group_obj) {
        $campaigns[$group_obj->getBelongsToCampaignId()]['adgroup']=$grpid;
	
	$creatives=$group_obj->getAllCreatives();
	if ($creatives && isset($creatives[0])) {
	  if (!isset($addata)) $addata=Array(head=>$creatives[0]->getHeadline(),desc1=>$creatives[0]->getDescription1(),desc2=>$creatives[0]->getDescription2(),dest_url=>$creatives[0]->getDestinationUrl(),disp_url=>$creatives[0]->getDisplayUrl());
	  
	}
	$keyw_obj=$group_obj->getAllKeywords();
	foreach ($keyw_obj AS $keyw) {
	  $keyword_lang=$keyw->getLanguage();
	  $keyword_lc=strtolower($keyw->getText());
	  if (!isset($keywords[$keyword_lang])) $keywords[$keyword_lang]=Array();
	  if (!isset($keywords[$keyword_lang][$keyword_lc])) $keywords[$keyword_lang][$keyword_lc]=Array('text'=>$keyw->getText());
	  $keywords[$keyword_lang][$keyword_lc]['active']=$keyw->getStatus();
	}
      }
    }
?>

<form method="POST" action="<?=tep_href_link(__FILE__,'pID='.$pID)?>">
<table>
<tr><td>Headline</td><td><input type="text" name="ad_head" value="<?=$addata['head']?>" maxlength="25" size="25"></td></tr>
<tr><td>Description 1</td><td><input type="text" name="ad_desc1" value="<?=$addata['desc1']?>" maxlength="35" size="35"></td></tr>
<tr><td>Description 2</td><td><input type="text" name="ad_desc1" value="<?=$addata['desc2']?>" maxlength="35" size="35"></td></tr>
<tr><td>Display URL</td><td><input type="text" name="ad_disp_url" value="<?=$addata['disp_url']?>" maxlength="35" size="35"></td></tr>
</table>

<h3>Campaigns</h3>
<table>
<? foreach ($campaigns AS $camp) { ?>
  <tr><td><input type="checkbox" name="" value=""<?=$camp['adgroup']?' checked':''?>></td><td><?=$camp['name']?></td></tr>
<? } ?>
</table>

<h3>Keywords</h3>
<table>
<? foreach ($keywords AS $lang => $kwds) {
     foreach ($kwds AS $kwd) { ?>
  <tr><td><input type="checkbox" name="" value=""<?=$kwd['active']?' checked':''?>></td><td><?=$kwd['text']?></td><td><?=$lang?></td></tr>
<?   }
   } ?>
</table>
<input type="submit" name="update" value="Update">
</form>

<?
  } else {
?><p>No Campaigns Found</p><?
  }
?>
</body>
</html>
