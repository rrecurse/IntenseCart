<?
  require('includes/application_top.php');

  $engine=isset($_GET['engine'])?$_GET['engine']:'adwords';
  $adobjname="ads_$engine";
  require(DIR_FS_MODULES."ads/$adobjname.php");
  $adobj=new $adobjname;
  $pID=$_GET['pID'];
  
  if (isset($_GET['adID'])) {
    $aid=$_GET['adID'];
    $ad=$adobj->getAd($_GET['campID'],$aid);
    if (isset($_GET['action']) && ($_GET['action']=='update')) {
      header("Content-Type: text/xml");
      $error=false;
      $ok=true;
?><update>
<ad>
<aid><?=$aid?></aid>
<?
      if (isset($_POST['pkeyw'])) {
        $pkeyw=$_POST['pkeyw'];
	if (trim($pkeyw)=='') $error="Primary keyword is not specified";
	else {
	  $kwds=Array($pkeyw);
	  if (isset($_POST['keyw'])) foreach (preg_split('/\r?\n/',$_POST['keyw']) AS $kw) if (trim($kw)!='') $kwds[]=$kw;
	  if (!isset($_GET['force'])) {
	    $camp=$adobj->getCampaign($_GET['campID']);
	    $ovr=$camp->findKeywords($kwds,$aid);
	    foreach ($ovr AS $kwd=>$ads) {
	      foreach ($ads AS $oad) {
	        $ok=false;
?>
<overlap>
<keyword><?=$kwd?></keyword>
<adname><?=$oad->getName()?></adname>
</overlap>
<?
	      }
	    }
	  }
	  if ($ok) $ad->setKeywords($kwds);
	}
      }
      if (isset($_POST['active'])!=$ad->getActive()) $ad->setActive(isset($_POST['active']));

      if ($error) { ?>
<error><?=$error?></error><?
      } else if ($ok) { ?>
<success>ok</success><?
      }
?></ad>
</update><?
    } else {
      $pkeyw=$ad->getName();
      $keyw=Array();
      foreach ($ad->getKeywords() AS $kw) if ($kw!=$pkeyw) $keyw[]=$kw;
      $adinfo=$ad->getAd();
?><form id="ad_form_<?=$aid?>" onSubmit="updateAd('<?=$aid?>',this); return false;"><table>
<tr><td>Primary Keyword:</td><td><input type="text" name="pkeyw" value="<?=$pkeyw?>"></td></tr>
<tr><td>More Keywords / Variations:</td><td><textarea name="keyw" cols="40" rows="8"><?=join("\n",$keyw)?></textarea></td></tr>
<tr><td>Ad Headline:</td><td><input type="text" name="head" value="<?=$adinfo['head']?>"></td></tr>
<tr><td>Ad Text:</td><td><textarea name="text" cols="35" rows="2"><?=$adinfo['text']?></textarea></td></tr>
<tr><td>Display URL:</td><td><input type="text" name="url" value="<?=$adinfo['url']?>"></td></tr>
<tr><td>Destination / Tracking URL:</td><td><input type="text" name="desturl" value="<?=$adinfo['desturl']?>?ref=<?=$engine?>&amp;keyw=<?=$pkeyw?>"></td></tr>
<tr><td>Max Cost per Click:</td><td>$<input type="text" name="cpc" value="<?=$adinfo['cpc']?>" size="5"></td></tr>
<tr><td>&nbsp;</td><td><input type="checkbox" name="active" value="1"<?=$ad->getActive()?' checked':''?>> Active</td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="update" value="Save"></td></tr>
</table></form><?
    }
    exit;
  }
  
?>
<html>
<head>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<link rel="stylesheet" href="js/css.css">
<style>

.AccordionPanelOpen .AccordionPanelTab tr {
	background-color: #FFFFC4;
}

.AccordionPanelOpen .AccordionPanelContent {
background-color: #FFFFC4;
}

.AccordionPanelTabHover {
background-color: #FFFFC4;
}

.AccordionPanelContent {
border:0
}
</style>
<!--/*<script type="text/javascript">

function contentChanged() {
 top.resizeIframe('adgroupframe');
}
</script>*/-->
</head>
<body style="background-color:transparent;">
<?
  $stats_start=date('Y-m-d',time()-86400*30);
  $stats_end=date('Y-m-d');
  $engine_box=Array(Array('id'=>'adwords',text=>'Google AdWords'),Array('id'=>'overture',text=>'Yahoo Overture'));
?>
<?=//tep_draw_pull_down_menu('engine',$engine_box,$engine,' onChange="document.location=\''.tep_href_link('product_ads.php',"engine='+this.value+'&pID=".$pID).'\'" style="font:8pt verdana"')?>
<?
  $campaign_box=Array();
  if (!$adobj->getCampaigns()) {
    echo "No campaigns available\n";
    exit;
  }
  foreach ($adobj->getCampaigns() AS $cid=>$c) {
    $campaign_box[]=Array('id'=>$cid,'text'=>$c->getName());
  }
  $campID=isset($_GET['campID'])?$_GET['campID']:$campaign_box[0]['id'];
  $campaign=$adobj->getCampaign($campID);

?>  <?=//tep_draw_pull_down_menu('campID',$campaign_box,$campID,' onChange="document.location=\''.tep_href_link('product_ads.php','engine='.$engine.'&pID='.$pID."&campID='+this.value+'").'\'" style="font:8pt verdana"')?>


<div id="response_box"></div>
<table width="571" cellpadding=0 cellspacing=0>
<tr>
<td colspan="8" style="background-color:#6295FD; height:20px;"><table width="571" border="0" cellpadding="0" cellspacing="0">
  <tr>
<td width="30" align="center"></td>
<td width="119" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:4px;">Adgroup</td>
    <td valign="bottom" style="padding-bottom:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top" style="padding-bottom:1px;"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="65" class="dataTableHeadingContent"><table border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="47" rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Status</td>
    <td width="10" style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="65" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Av.
      Po.</td>
     <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Impr.</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Clicks</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">CPC</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Cost</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
</tr></table></td>
</tr>
<tr>
<td colspan="8">
<div class="Accordion" id="adsAccordion" tabindex="0">
<? foreach ($campaign->getAds('products_id='.$pID) AS $aid=>$ad) {
     $st=$ad->getStats($stats_start,$stats_end);
?>    
<div class="AccordionPanel">
  <div class="AccordionPanelTab" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover=""><table width=571 cellpadding=0 cellspacing=0>
<tr><td colspan="8"></td></tr>
<tr>
<td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
<td width="118" align="center" class="tableinfo_right-btm"><?=$ad->getName()?></td>
<td width="65" align="center" class="tableinfo_right-btm">
    <? if ($ad->getActive()) { ?> <font style="color:#00FF00">Active</font> <? } else { ?> <font style="color:#FF0000">Inactive</font> <? } ?></td>
<td width="65" align="center" class="tableinfo_right-btm"><?=$st['avgpos']?></td>
<td width="70" align="center" class="tableinfo_right-btm"><?=$st['imprs']?></td><td width="70" align="center" class="tableinfo_right-btm"><?=$st['clicks']?></td><td width="70" align="center" class="tableinfo_right-btm"><?=$st['convs']?></td><td width="80" align="center" class="tableinfo_right-btm">$<?=$st['cost']?></td>
</tr></table>
<span id="ad_action_<?=$aid?>"></span>
  </div>
  <div class="AccordionPanelContent" onExpanderOpenPanel="viewAd('<?=$aid?>')" style="width:571px; background:#FFFFC4; border:solid 1px #333333; border-top:0;">
<div id="view_ad_<?=$aid?>"></div>
  </div>
</div>
<? } ?>

<!--<div class="AccordionPanel">
  <div class="AccordionPanelTab" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover="">
    New Ad
  </div>
  <div class="AccordionPanelContent">
    <div id="view_ad_">
      new ad
    </div>
  </div>
</div>-->



</div>
</td></tr></table>
<script language="JavaScript" type="text/javascript">

function adLoadComplete(req,aid) {
  var box=$('view_ad_'+aid);
  if (box) box.innerHTML=req.responseText;
  adsLoaded[aid]=true;
  adsAccordion.openPendingPanel();
}

var adsLoaded={};
function viewAd(aid) {
  if (adsLoaded[aid]==undefined) {
    adsLoaded[aid]=false;
    new ajax('<?=tep_href_link('product_ads.php','engine='.$engine.'&campID='.$campID.'&pID='.$pID."&adID='+aid+'")?>',{ onComplete:adLoadComplete, onCompleteArg:aid });
  }
  return adsLoaded[aid];
}

function updateAd(aid,frm,force) {
  var post=new Array();
  var e;
  for (var i=0;e=frm.elements[i];i++) {
    if (e.type=='text' || e.type=='textarea' || ((e.type=='radio' || e.type=='checkbox') && e.checked)) post.push(e.name+'='+escape(e.value));
  }
  adAction(aid,'<span align="center" style="padding:5px;">Processing... <img src="images/loading_bar.gif" width="163" height="15"></span>');
  new ajax('<?=tep_href_link('product_ads.php','engine='.$engine.'&campID='.$campID.'&pID='.$pID."&adID='+aid+'&action=update")?>'+(force?'&force=1':''),{ postBody:post.join('&'), onComplete:updateAdComplete, onCompleteArg:aid });
}

function updateAdComplete(req) {
  var xml=req.responseXML;
  if (xml) xml=xml.getElementsByTagName('update')[0];
  if (!xml) {
    window.alert('Bad Response');
    $('response_box').innerHTML=req.responseText;
    return;
  }
  var ads=xml.getElementsByTagName('ad');
  for (var i=0;ads[i];i++) {
    aid=ads[i].getElementsByTagName('aid')[0].firstChild.nodeValue;
    var er=ads[i].getElementsByTagName('error')[0];
    var suc=ads[i].getElementsByTagName('success')[0];
    var ov=ads[i].getElementsByTagName('overlap');
    if (er) {
      window.alert(er.firstChild.nodeValue);
      adAction(aid,'Update Failed');
    } else if (suc) {
      adAction(aid,'Update Successful');
    } else if (ov.length) {
      var kwtext=new Array();
      for (var i=0;ov[i];i++) {
        kwtext[kwtext.length]='Keyword: "'+ov[i].getElementsByTagName('keyword')[0].firstChild.nodeValue+'" already exists within AdGroup: "'+ov[i].getElementsByTagName('adname')[0].firstChild.nodeValue+"'  - We recommend you use a different keyword.";
      }
      if (window.confirm(kwtext.join('\n'))) updateAd(aid,$('ad_form_'+aid),true);
      else adAction(aid,'Update Cancelled');
    }
  }
}

function adAction(aid,st) {
  var box=$('ad_action_'+aid);
  if (box) box.innerHTML=st;
}

var adsAccordion = new Spry.Widget.Accordion("adsAccordion",{enableClose:true});
</script>

</body>
</html>
