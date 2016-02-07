<?
  require('includes/application_top.php');

  $engineset=tep_module('ppc_ads');
  $engines=$engineset->getModules();
  $engine=isset($_GET['engine'])?$_GET['engine']:'';
  if (!isset($engines[$engine])) list($engine)=array_keys($engines);
  if (!$engine) {
?>
<html><head></head><body>
<p>No PPC modules configured</p>
<p><a href="module_config.php?set=ppc_ads">Click here</a> to configure</p>
</body>
</html>
<?
//    header('HTTP 302 Redirect');
//    header('Location: module_config.php?set=ppc_ads');
    exit;
  }

  $adobj=$engines[$engine];
  if (isset($_GET['show_del']) && $_GET['show_del']) $adobj->showDeleted(true);
  $adkey=NULL;
  $selflinkarg='view_all=1';
  $defaultAd=Array('head'=>'','text'=>'','url'=>SITE_DOMAIN,'keyword'=>'','maxcpc'=>0.20);
  if (isset($_GET['pID'])) {
    $pID=$_GET['pID'];
    $adkey='products_id='.$pID;
    $selflinkarg='pID='.$pID;
    $pinfo=tep_db_fetch_array(tep_db_query("SELECT * FROM ".TABLE_PRODUCTS." p LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON pd.products_id=p.master_products_id WHERE p.products_id='".addslashes($pID)."'"));
    $defaultAd['head']=$defaultAd['keyword']=$pinfo['products_name'];
    $dsc=($pinfo['products_free_shipping']?'Free Shipping ':'').preg_replace('/[\s\r\n]+/s',' ',$pinfo['products_info']);
    if (preg_match('/(.{1,35})( (.{1,35}))?( |$)/',$dsc,$dscparse)) $defaultAd['text']=$dscparse[1]."\n".$dscparse[3];
  }
  $selflink=tep_href_link('product_ads.php',$selflinkarg);

  function postArray($name) {
    if (!isset($_POST[$name])) return Array();
    if (is_array($_POST[$name])) return $_POST[$name];
    return split("\n",$_POST[$name]);
  }
  
  if (isset($_GET['adID'])) {
    $new_ad=NULL;
    $aid=$_GET['adID'];
    if (preg_match('/^new/',$aid)) {
      $camp=$adobj->getCampaign($_GET['campID']);
      $new_ad=$ad=$camp->newAd();
      $ad->setKey($adkey);
    } else $ad=$adobj->getAd($_GET['campID'],$aid);
    if (isset($_GET['action'])) {
      header("Content-Type: text/xml");
      if ($_GET['action']=='update') {
        $error=false;
        $ok=true;
?><update>
<ad>
<aid><?=$aid?></aid>
<campid><?=$_GET['campID']?></campid>
<?
// Order DOES matter!

        $active=$ad->getActive();
        if (!isset($active) || isset($_POST['active'])!=$active) $ad->setActive(isset($_POST['active']));
	if (isset($_POST['desturl'])) $ad->setUrl($_POST['desturl']);
        $limits=Array();
        if (isset($_POST['maxcpc'])) $limits['maxcpc']=$_POST['maxcpc'];
        if (isset($_POST['maxcontentcpc'])) $limits['maxcontentcpc']=$_POST['maxcontentcpc'];
        if (sizeof($limits)) $ad->setLimits($limits);
        if (isset($_POST['pkeyw'])) {
          $pkeyw=$_POST['pkeyw'];
  	  if (trim($pkeyw)=='') $error="Primary keyword is not specified";
	  else {
	    $kwds=Array($pkeyw);
	    $kwmaxcpc=Array();
	    if (isset($_POST['keyw'])) foreach ($_POST['keyw'] AS $kidx=>$kw) if (trim($kw)!='') {
	      $kwds[]=$kw;
	      if (isset($_POST['keyw_maxcpc'])) $kwmaxcpc[$kw]=$_POST['keyw_maxcpc'][$kidx];
	    }
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
            if ($ok) $ok=$ad->setName($pkeyw) && $ad->setKeywords($kwds,Array('maxcpc'=>$kwmaxcpc));
 	  }
        }

        if ($ok && isset($_POST['text']) && is_array($_POST['text'])) {
          $cr=Array();
	  for ($i=0;isset($_POST['text'][$i]);$i++) $cr[]=Array('head'=>$_POST['head'][$i],'text'=>$_POST['text'][$i],'url'=>$_POST['url'][$i]);
	  $ad->setAds($cr);
        }
      
        if ($ok) $ok=$ad->finishUpdate();
      
        if ($error) { ?>
<error><?=$error?></error><?
          $ok=false;
        }
        foreach ($adobj->getErrors() AS $er) { ?>
<error><?=htmlspecialchars($er)?></error><?
          $ok=false;
        }
        if ($ok) {
          if ($new_ad) {
 ?>
<new_aid><?=$ad->getId()?></new_aid>
     <?   } ?>
<success>ok</success><?
        }
?></ad>
</update><?
      } else if ($_GET['action']=='delete') {
?><delete>
<ad>
<aid><?=$aid?></aid>
<?
        if ($ad->remove()) {
?><success>ok</success><?
	} else {
?><error>error deletind ad</error><?
	}
?>
</ad>
</delete><?
      }
    } else {
      $pkeyw=$ad->getName();
      $keyw=Array();
      foreach ($ad->getKeywords() AS $kw) if ($kw!=$pkeyw) $keyw[]=$kw;
      $adsinfo=$ad->getAds();
      $adstats=$ad->getAdStats(date('Y-m-d',time()-86400),date('Y-m-d'));
      $adstotal=Array();
      foreach ($adsinfo AS $adid=>$adinfo) {
        foreach ($adstats[$adid] AS $adsk=>$adsv) {
	  if (!isset($adstotal[$adsk])) $adstotal[$adsk]=0;
	  $adstotal[$adsk]+=$adsv;
	}
      }
      $kwlimits=$ad->getKeywordLimits();
      $kwmaxcpc=isset($kwlimits['maxcpc'])?$kwlimits['maxcpc']:Array();
      if (!sizeof($adsinfo)) $adsinfo[]=Array('head'=>'','text'=>'','url'=>SITE_DOMAIN);
?><form id="ad_form_<?=$aid?>" onSubmit="updateAd('<?=$aid?>','<?=$campID?>',this); return false;"><table>
<tr><td>Primary Keyword:</td><td> <input type="text" name="pkeyw" value="<?=$pkeyw?>"></td></tr>
<tr><td valign="top" style="padding-top:4px;">Keywords Variations:</td><td>
<table id="keyw_section">
<? foreach ($keyw AS $kw) { ?>
<tr><td><input type="text" name="keyw[]" value="<?=$kw?>" onChange="adjustKeywSection($('keyw_section'),'<?=$campID?>')"></td><td><input type="text" name="keyw_maxcpc[]" size="7" value="<?=(isset($kwmaxcpc[$kw])&&($kwmaxcpc[$kw]))?sprintf('%.2f',$kwmaxcpc[$kw]):''?>"></td></tr>
<? } ?>
<tr><td><input type="text" name="keyw[]" onChange="adjustKeywSection($('keyw_section'),'<?=$campID?>')"></td><td><input type="text" name="keyw_maxcpc[]" size="7"></td></tr>
<tr><td><input type="text" name="keyw[]" onChange="adjustKeywSection($('keyw_section'),'<?=$campID?>')"></td><td><input type="text" name="keyw_maxcpc[]" size="7"></td></tr>
</table>
<!--textarea name="keyw" cols="40" rows="8"><?=join("\n",$keyw)?></textarea--></td></tr>
</table>
<div id="ad_variations_<?=$aid?>_<?=$campID?>">

<? foreach($adsinfo AS $adidx=>$adinfo) { ?>
<div>
<table width="99%">
<tr><td>
<table>
<tr><td>Ad Headline:</td><td><input type="text" name="head[]" value="<?=$adinfo['head']?>" size="25" maxlength="25" style="font-size:8pt; font-family:verdana;"></td></tr>
<tr><td>Ad Text:</td><td><textarea name="text[]" cols="35" rows="2" maxlength="100" style="font-size:8pt; font-family:verdana;"><?=$adinfo['text']?></textarea></td></tr>
<tr><td>Display URL:</td><td><input type="text" name="url[]" value="<?=$adinfo['url']?>" size="35" maxlength="35" style="font-size:8pt; font-family:verdana;"></td></tr>
</table>
[<a href="javascript:void(0)" onClick="delAdVariation(this,'<?=$campID?>')">Remove Ad</a>]
</td>
<td valign="top">
<table cellpadding="4" cellspacing="1" width="100%" style="border:solid 1px #333333; background-color:#FFFFFF;">
<tr style="background-color:#6295FD; font:bold 10px tahoma; color:#FFFFFF;"> <td align="center" nowrap>Shown %</td><td align="center" nowrap>Impr.:</td> <td align="center" nowrap>Clicks:</td> <td align="center" nowrap>CTR</td> <td align="center" nowrap>Conv.:</td> <td align="center" nowrap>Conv. %:</td>
</tr>
<tr>
<td align="center"><? if ($adstotal['imprs']>0) echo sprintf('(%.1f%%)',$adstats[$adidx]['imprs']*100/$adstotal['imprs']); else echo '-' ?></td>
<td align="center"><?=$adstats[$adidx]['imprs']?> </td>
<td align="center"> <?=$adstats[$adidx]['clicks']?></td>
<td align="center"><? if ($adstats[$adidx]['imprs']>0) echo sprintf('(CTR: %.1f%%)',$adstats[$adidx]['clicks']*100/$adstats[$adidx]['imprs']); else echo '-' ?></td>
<td align="center"><?=$adstats[$adidx]['convs']?> </td>
<td align="center"><? if ($adstats[$adidx]['clicks']>0) echo sprintf('(Conv Rate: %.1f%%)',$adstats[$adidx]['convs']*100/$adstats[$adidx]['clicks']); else echo '-' ?></td>
</tr></table>
</td>
</tr></table>
</div>
<? } ?>
</div>
[<a href="javascript:addAdVariation('<?=$aid?>','<?=$campID?>')">Add New Ad</a>]
<table>
<?
  $limits=$ad->getLimits();
  if (!$adkey) {
?>
<tr><td>Destination / Tracking URL:</td><td><input type="text" name="desturl" value="<?=$ad->getUrl()?>"></td></tr>
<?
  }
  if (isset($limits['maxcpc'])) {
?>
<tr><td>Max Cost per Click:</td><td>$<input type="text" name="maxcpc" value="<?=$limits['maxcpc']>0?sprintf('%.2f',$limits['maxcpc']):''?>" size="5"></td></tr>
<?
  }
  if (isset($limits['maxcontentcpc'])) {
?>
<tr><td>Max Cost per Content Click:</td><td>$<input type="text" name="maxcontentcpc" value="<?=$limits['maxcontentcpc']?>" size="5"></td></tr>
<?
  }
?>
<tr><td>&nbsp;</td><td><input type="checkbox" name="active" value="1"<?=$ad->getActive()?' checked':''?>> Active</td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="update" value="Save"></td></tr>
</table></form><?
    }
    exit;
  }


  if (isset($_GET['action'])) {
    header("Content-Type: text/xml");
    if ($_GET['action']=='stats') {
      list($start,$end)=split(',',$_GET['range']);
      $campaign=$adobj->getCampaign($_GET['campID']);
?><stats>
<campid><?=$campaign->getId()?></campid>
<range><?="$start,$end"?></range>
<?    foreach($campaign->getAds($adkey) AS $aid=>$ad) {
        $stats=$ad->getStats($start,$end);
?>
<ad>
  <aid><?=$aid?></aid>
<?      foreach ($stats AS $skey=>$sval) { ?>
  <<?=$skey?>><?=$sval?></<?=$skey?>>
<?      } ?>
</ad>
<?    } ?>
</stats><?
    } else if ($_GET['action']=='update') {
      $campID=$_GET['campID'];
      $campaign=($campID=='new')?$adobj->newCampaign():$adobj->getCampaign($campID);
?>
<update>
<campaign>
<campid><?=$campID?></campid>
<?
      $ok=isset($campaign);
      $limits=Array();
      $opts=Array();
      $opts['optimize_ads']=(isset($_POST['optimize_ads']) && $_POST['optimize_ads']);
      if (isset($_POST['budget'])) $limits['budget']=$_POST['budget'];
      if (isset($_POST['end_date'])) $limits['end_date']=$_POST['end_date'];
      if ($ok && isset($_POST['name'])) $ok=$campaign->setName($_POST['name']);
      if ($ok) $ok=$campaign->setActive(isset($_POST['active']));
      if ($ok) $ok=$campaign->setTargeting(Array('network'=>postArray('network'),'languages'=>postArray('languages'),$_POST['geotargeting']=>postArray($_POST['geotargeting'])));
      if ($ok) $ok=$campaign->setOptions($opts);
      if ($ok && $limits) $ok=$campaign->setLimits($limits);
      if (isset($_POST['schedule_allow'])) {
        $schd=Array();
        foreach($_POST['schedule_allow'] AS $day=>$allow) if ($allow) $schd[]=Array('weekday'=>$day,'start_hour'=>$_POST['schedule_start'][$day],'end_hour'=>$_POST['schedule_end'][$day],'multiplier'=>(isset($_POST['schedule_multiplier'][$day])?$_POST['schedule_multiplier'][$day]:1),'timezone'=>$_POST['timezone']);
        if ($ok && $schd) $ok=$campaign->setSchedule($schd);
      }
      if ($campID=='new' && $campaign && $ok) {
?>
<new_campid><?=$campaign->getId()?></new_campid>
<?
      }
      if ($error) {
	echo "<error>".htmlspecialchars($error)."</error>\n";
	$ok=false;
      }
      foreach ($adobj->getErrors() AS $er) {
	echo "<error>".htmlspecialchars($er)."</error>\n";
	$ok=false;
      }
      if ($ok) echo "<success>ok</success>\n";
?>
</campaign>
</update>
<?
    } else if ($_GET['action']=='delete') {
      $campaign=$adobj->getCampaign($_GET['campID']);
?><delete>
<campaign>
<campid><?=$_GET['campID']?></campid>
<?
      if ($campaign->remove()) {
?><success>ok</success><?
      } else {
?><error>error deletind campaign</error><?
      }
?>
</campaign>
</delete><?
    } else {
      echo '<error>Unknown Action</error>';
    }
    exit;
  }
?>
<html>
<head>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="js/tabber.js"></script>
<script type="text/javascript" src="js/popcalendar.js"></script>

<link rel="stylesheet" href="js/css.css">
<link rel="stylesheet" href="js/tabber.css">


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
</head>
<body style="background-color:transparent;">
<?
  include(DIR_WS_INCLUDES.'header.php');

if (isset($_GET['campID'])) $campID=$_GET['campID'];
else list($campID)=array_keys($adobj->getCampaigns());
$campaign=$adobj->getCampaign($campID=='new'?NULL:$campID);

if (!isset($_GET['hide_header']) || !$_GET['hide_header']) {
  $stats_start=date('Y-m-d',time()-86400*30);
  $stats_end=date('Y-m-d');
  $engine_box=Array();
  foreach ($engines AS $key=>$eng) $engine_box[]=Array('id'=>$key,text=>$eng->getName());
?>
<div id="ppc_header">
<form style="margin:0;">
<table width="571" cellpadding="0" cellspacing="0">
<tr>
<td valign="top" style="padding-top:10px;">
<table><tr><td>
 &nbsp; Channel: 
<?=tep_draw_pull_down_menu('engine',$engine_box,$engine,' onChange="document.location=\''.$selflink."&engine='+this.value".'" style="font:8pt verdana"')?>
</td></tr><tr><td>
<?
  $campaign_box=Array();
  foreach ($adobj->getCampaigns() AS $cid=>$c) {
    $campaign_box[]=Array('id'=>$cid,'text'=>$c->getName());
  }
  $campID=isset($_GET['campID'])?$_GET['campID']:($campaign_box?$campaign_box[0]['id']:NULL);
  $campaign=$adobj->getCampaign($campID=='new'?NULL:$campID);
  if (!isset($_GET['campaigns']) || !$_GET['campaigns']) {
    if (!$adobj->getCampaigns()) {
      echo "No campaigns available\n";
      exit;
    }
?>
  &nbsp; Campaign:
<?=tep_draw_pull_down_menu('campID',$campaign_box,$campID,' onChange="document.location=\''.$selflink.'&engine='.$engine.(isset($_GET['show_del'])?'&show_del='.$_GET['show_del']:'')."&campID='+this.value".'" style="font:8pt verdana"')?>
<?
  } else {
?>

<?
  }
?></td></tr></table>
</td><td valign="top" style="padding-top:5px; padding-bottom:5px;" width="50%">
<table align="right" cellpadding="0" cellspacing="0" style="border:solid 1px #333333; background:#FFFFFF; padding:5px;">
<tr>
<td valign="top"><select name="stats_range" id="stats_range" onChange="showStats(this.value)" style="font-size:8pt; font-family:verdana;">
<option value="">select one</option>
<option value="<?=date('Y-m-d',time()-86400).','.date('Y-m-d')?>">yesterday</option>
<option value="<?=date('Y-m-d',time()-86400*date('w')).','.date('Y-m-d')?>">this week</option>
<option value="<?=date('Y-m-d',time()-86400*(date('w')+7)).','.date('Y-m-d',time()-86400*date('w'))?>">last week</option>
<option value="<?=date('Y-m-1').','.date('Y-m-d')?>">this month</option>
<option value="<?=date('Y-m-1',time()-86400*date('d')).','.date('Y-m-1')?>">last month</option>
</select> <input type="checkbox" name="show_del" value="1"<?=(isset($_GET['show_del'])&&$_GET['show_del'])?' checked':''?> onChange="document.location='<?=$selflink?>&engine=<?=$engine?>'+(this.checked?'&show_del=1':'')">Show Deleted Ads.

<table border="0" cellpadding="0" cellspacing="0">
<tr>
  <td align="right" style="padding-top:2px;">
<input type="text" name="date_from" id="date_from" style="font:bold 9px arial;" onClick="popUpCalendar(this,this,'mm/dd/yyyy',document);" value="" size="12" maxlength="11" textfield></td>
  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="popUpCalendar($('date_from'),$('date_from'),'mm/dd/yyyy',document);" style="cursor:pointer"></td>
  <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
  <td align="right" style="padding-top:2px;"><input type="text" name="date_to" id="date_to" onClick="popUpCalendar($('date_from'),this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="" size="12" maxlength="11" textfield></td>
  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="popUpCalendar($('date_from'),$('date_to'),'mm/dd/yyyy',document);" style="cursor:pointer"></td>
</tr>
</table>
</td></tr></table></td></tr></table>

</form>

<script language="JavaScript" type="text/javascript">

var adsAccordions=new Array();
var adList=new Array();

function adLoadComplete(req,v) {
  var box=$('view_ad_'+v.aid);
  if (box) box.innerHTML=req.responseText;
  adsLoaded[v.aid]=true;
  adsAccordions[v.cid].openPendingPanel();
}

var adsLoaded={};
function viewAd(aid,cid) {
  if (adsLoaded[aid]==undefined) {
    adsLoaded[aid]=false;
    new ajax('<?=$selflink.'&engine='.$engine."&campID='+cid+'&adID='+aid+'"?>',{ onComplete:adLoadComplete, onCompleteArg:{aid:aid,cid:cid} });
  }
  return adsLoaded[aid];
}

function encodeForm(frm) {
  var post=new Array();
  var e;
  for (var i=0;e=frm.elements[i];i++) {
    if (e.type=='text' || e.type=='textarea' || e.type=='password' || ((e.type=='radio' || e.type=='checkbox') && e.checked)) post.push(escape(e.name)+'='+escape(e.value));
    else if (e.type.match(/^select/)) for (var j=0;e.options[j];j++) if (e.options[j].selected) post.push(escape(e.name)+'='+escape(e.options[j].value));
  }
  return post.join('&');
}

function updateAd(aid,cid,frm,force) {
  adAction(aid,'<span align="center" style="padding:5px;">Processing... <img src="images/loading_bar.gif" width="163" height="15"></span>');
  new ajax('<?=$selflink.'&engine='.$engine."&campID='+cid+'&adID='+aid+'&action=update"?>'+(force?'&force=1':''),{ postBody:encodeForm(frm), onComplete:updateAdComplete, onCompleteArg:aid });
}

function deleteAd(aid,cid) {
  if (!window.confirm('Delete ad '+aid+'?')) return false;
  adAction(aid,'Deleting...');
  new ajax('<?=$selflink.'&engine='.$engine."&campID='+cid+'&adID='+aid+'&action=delete"?>',{ onComplete:deleteAdComplete, onCompleteArg:aid });
}

function deleteAdComplete(req,aid) {
  location.reload();
}

function updateAdComplete(req,aid) {
  var xml=req.responseXML;
  if (xml) xml=xml.getElementsByTagName('update')[0];
  if (!xml) {
    window.alert('Bad Response');
    adAction(aid,'Bad Response');
    $('response_box').innerHTML=req.responseText.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    return;
  }
  var ads=xml.getElementsByTagName('ad');
  for (var i=0;ads[i];i++) {
    aid=ads[i].getElementsByTagName('aid')[0].firstChild.nodeValue;
    cid=ads[i].getElementsByTagName('campid')[0].firstChild.nodeValue;
    var ers=ads[i].getElementsByTagName('error');
    var suc=ads[i].getElementsByTagName('success')[0];
    var ov=ads[i].getElementsByTagName('overlap');
    if (ers[0]) {
      var e=new Array();
      for (var i=0;ers[i];i++) e[i]=ers[i].firstChild.nodeValue;
      window.alert(e.join('\n'));
      adAction(aid,'Update Failed');
    } else if (suc) {
      adAction(aid,'Update Successful');
      if (ads[i].getElementsByTagName('new_aid')[0]) reloadCampaignContent(cid);
    } else if (ov.length) {
      var kwtext=new Array();
      for (var i=0;ov[i];i++) {
        kwtext[kwtext.length]='Keyword: "'+ov[i].getElementsByTagName('keyword')[0].firstChild.nodeValue+'" already exists within AdGroup: "'+ov[i].getElementsByTagName('adname')[0].firstChild.nodeValue+"'  - We recommend you use a different keyword.";
      }
      if (window.confirm(kwtext.join('\n'))) updateAd(aid,cid,$('ad_form_'+aid),true);
      else adAction(aid,'Update Cancelled');
    }
 else adAction(aid,'Error');
  }
}

function reloadCampaignContent(cid) {
  if (window.campsLoaded) {
    campsLoaded[cid]=undefined;
    viewCamp(cid);
  } else document.location.reload();
}

function adAction(aid,st) {
  var box=$('ad_action_'+aid);
  if (box) box.innerHTML=st;
}

function addAdVariation(aid,cid) {
  blk=$('ad_variations_'+aid+'_'+cid);
  var div=blk.getElementsByTagName('div')[0];
  blk.appendChild(div.cloneNode(true));
  adsAccordions[cid].adjustPanelHeight();
}

function delAdVariation(blk,cid) {
  var div=blk.parentNode;
  for(;div.tagName!='DIV';div=div.parentNode);
  var main=div.parentNode;
  if (main.getElementsByTagName('div').length>1)
    if (window.confirm('Remove the ad variation?')) main.removeChild(div);
  adsAccordions[cid].adjustPanelHeight();
}

var emptyMin=2;
var emptyMax=3;
function adjustKeywSection(sec,cid) {
  var trs=sec.getElementsByTagName('TR');
  var cempty=0;
  var empty=new Array();  
  var e;
  var first;
  for (var i=0;e=trs[i];i++) {
    if (!e.tagName) continue;
    var inp=e.getElementsByTagName('INPUT')[0];
    if (inp) {
      if (!first) first=e;
      if (inp.value.match(/^\s*$/)) {
        if (cempty++>=emptyMax) empty[empty.length]=e;
      } else {
        cempty=0;
	empty=new Array();
      }
    }
  }
  for (var i=0;i<empty.length;i++) first.parentNode.removeChild(empty[i]);
  for (;cempty<emptyMin;cempty++) {
    var n=first.cloneNode(true);
    var inps=n.getElementsByTagName('INPUT');
    for (var i=0;inps[i];i++) if (inps[i].type=='text') inps[i].value='';
    first.parentNode.appendChild(n);
  }
  adsAccordions[cid].adjustPanelHeight();
}

var statsCache={};

function setField(fld,obj,keys,pre,df) {
  for (var i=0;keys[i]!=undefined && obj!=undefined;i++) obj=obj[keys[i]];
  var v;
  if (pre!=null) {
    v=Number(obj).toFixed(pre);
    if (isNaN(v)) v=df;
  } else v=obj==null?bf:obj;
  if (fld!=null) blk=$(fld+keys.join('_'));
  if (blk) blk.innerHTML=v;
  return v;
}

function showStats(range) {
  var st=statsCache[range];
  for (var i=0;adList[i];i++) {
    setField('ad_stats_',st,new Array(adList[i],'avgpos'),1,'');
    setField('ad_stats_',st,new Array(adList[i],'clicks'),0,'');
    setField('ad_stats_',st,new Array(adList[i],'imprs'),0,'');
    setField('ad_stats_',st,new Array(adList[i],'convs'),0,'');
    setField('ad_stats_',st,new Array(adList[i],'cost'),2,'');
  }
  if (!st && range) {
    statsCache[range]={};
    new ajax('<?=$selflink.'&engine='.$engine.'&campID='.$campID."&range='+range+'&action=stats"?>',{ onComplete:receiveStats });
  }
}

function receiveStats(req) {
  var st=req.responseXML;
  if (st) st=st.getElementsByTagName('stats')[0];
  if (!st) return;
  var range=st.getElementsByTagName('range')[0].firstChild.nodeValue;
  if (!range) return;
  statsCache[range]={};
  var ads=st.getElementsByTagName('ad');
  for (var i=0;ads[i];i++) {
    var ad={};
    for (var e=ads[i].firstChild;e;e=e.nextSibling) if (e.firstChild) ad[e.tagName]=e.firstChild.nodeValue;
    statsCache[range][ad['aid']]=ad;
  }
  if (range==$('stats_range').value) showStats(range);
}

function switchFields(frm,fld,fid) {
  var e;
  for (var i=0;e=frm.elements[i];i++) if (e.name==fld) e.style.display=e.id==fid?'':'none';
}

</script>

<div id="response_box"></div>

<?
}
if (isset($_GET['campaigns']) && $_GET['campaigns']) {
?>

<script language="JavaScript" type="text/javascript">

var campsList=new Array();

function campLoadComplete(req,cid) {
  var box=$('view_camp_'+cid);
  if (box) {
    box.innerHTML=req.responseText;
    var scrs=box.getElementsByTagName('script');
    for (var i=0;scrs[i];i++) eval(scrs[i].innerHTML);
  }
  campsLoaded[cid]=true;
  campsAccordion.openPendingPanel();
}

var campsLoaded={};
function viewCamp(cid) {
  if (campsLoaded[cid]==undefined) {
    campsLoaded[cid]=false;
    new ajax('<?=$selflink.'&engine='.$engine."&campID='+cid+'&edit_campaign=1&hide_header=1"?>',{ onComplete:campLoadComplete, onCompleteArg:cid });
  }
  return campsLoaded[cid];
}

function updateCamp(cid,frm) {
  campAction(cid,'<span align="center" style="padding:5px;">Processing... <img src="images/loading_bar.gif" width="163" height="15"></span>');
  new ajax('<?=$selflink.'&engine='.$engine."&campID='+cid+'&action=update"?>',{ postBody:encodeForm(frm), onComplete:updateCampComplete, onCompleteArg:cid });
}

function deleteCamp(cid) {
  if (!window.confirm('Do you want to delete the ad campaign '+cid+'?')) return;
  campAction(cid,'Deleting...');
  new ajax('<?=$selflink.'&engine='.$engine."&campID='+cid+'&action=delete"?>',{ onComplete:deleteCampComplete, onCompleteArg:cid });
}

function deleteCampComplete(req,cid) {
  location.reload();
}

function updateCampComplete(req,cid) {
  var xml=req.responseXML;
  if (xml) xml=xml.getElementsByTagName('update')[0];
  if (!xml) {
    window.alert('Bad Response');
    campAction(cid,'Bad Response');
    $('response_box').innerHTML=req.responseText.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    return;
  }
  var camps=xml.getElementsByTagName('campaign');
  for (var i=0;camps[i];i++) {
    cid=camps[i].getElementsByTagName('campid')[0].firstChild.nodeValue;
    var ers=camps[i].getElementsByTagName('error');
    var suc=camps[i].getElementsByTagName('success')[0];
    if (ers[0]) {
      var e=new Array();
      for (var i=0;ers[i];i++) e[i]=ers[i].firstChild.nodeValue;
      window.alert(e.join('\n'));
      campAction(cid,'Update Failed');
    } else if (suc) {
      campAction(cid,'Update Successful');
      if (camps[i].getElementsByTagName('new_campid')[0]) location.reload();
    } else campAction(cid,'Error');
  }
}

function campAction(cid,st) {
  var box=$('camp_action_'+cid);
  if (box) box.innerHTML=st;
}

var contentChangedCampSv=window.contentChanged;
window.contentChanged=function() {
  campsAccordion.adjustPanelHeight();
  if (contentChangedCampSv) contentChangedCampSv();
}

</script>

<table width="571" cellpadding=0 cellspacing=0>
<tr>
<td colspan="8" style="background-color:#6295FD; height:20px;"><table width="571" border="0" cellpadding="0" cellspacing="0" >
  <tr>
<td width="30" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14"></td>
<td width="119" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF;" style="padding-right:4px;">Campaign</td>
    <td valign="bottom" style="padding-bottom:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top" style="padding-bottom:1px;"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="65" class="dataTableHeadingContent"><table border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="47" rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Status</td>
    <td width="10" style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="65" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Max
      CPC </td>
     <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Clicks</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Impr.</td>
      <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
    </tr>
    <tr>
      <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
    </tr>
  </table></td>
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">CTR</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Avg.
      CPC</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent">    <table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Cost</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table>
  </td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Avg.
      Po </td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">Conv.
      %</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" style="font:bold 9px verdana; color:#FFFFFF; padding-right:5px;">CPC </td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
  </tr></table></td>
</tr>
<tr>
<td colspan="12">
<div class="Accordion" id="campsAccordion" tabindex="0">
<? foreach ($adobj->getCampaigns() AS $cid=>$camp) {
?>    
<div class="AccordionPanel">
  <div class="AccordionPanelTab" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover=""><table width=571 cellpadding=0 cellspacing=0>
<tr><td colspan="12"></td></tr>
<tr>
<td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
<td width="118" align="center" class="tableinfo_right-btm"><?=$camp->getName()?></td>
<td width="65" align="center" class="tableinfo_right-btm">
<? if ($camp->getDeleted()) { ?>  <font style="color:#FF0000">Deleted</font>  <? } else if ($camp->getActive()) { ?> <font style="color:#00FF00">Active</font> <? } else { ?> <font style="color:#FF0000">Inactive</font> <? } ?></td>
<td width="65" align="center" class="tableinfo_right-btm">&nbsp;</td>
<td width="70" align="center" class="tableinfo_right-btm"></td>
<td width="70" align="center" class="tableinfo_right-btm"></td><td width="70" align="center" class="tableinfo_right-btm">&nbsp;</td><td width="80" align="center" class="tableinfo_right-btm">&nbsp;</td>
<td width="80" align="center" class="tableinfo_right-btm"></td>
<td width="80" align="center" class="tableinfo_right-btm"></td>
<td width="80" align="center" class="tableinfo_right-btm"></td>
<td width="80" align="center" class="tableinfo_right-btm">&nbsp;</td>
<td>[<a href="javascript:void(0)" onClick="deleteCamp('<?=$cid?>');">x</a>]</td>
</tr></table>
<span id="camp_action_<?=$cid?>"></span>
  </div>
  <div class="AccordionPanelContent" onExpanderOpenPanel="viewCamp('<?=$cid?>')" style="width:571px; background:#FFFFC4; border:solid 1px #333333; border-top:0;">
<div id="view_camp_<?=$cid?>"></div>
  </div>
</div>
<? } ?>

<div class="AccordionPanel">
  <div class="AccordionPanelTab" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover=""><table width=571 cellpadding=0 cellspacing=0>
<tr><td colspan="8"></td></tr>
<tr>
<td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
<td width="118" align="center" class="tableinfo_right-btm">Create Campaign</td>
<td width="65" align="center" class="tableinfo_right-btm"></td>
<td width="65" align="center" class="tableinfo_right-btm"></td>
<td width="70" align="center" class="tableinfo_right-btm"></td>
<td width="70" align="center" class="tableinfo_right-btm"></td>
<td width="70" align="center" class="tableinfo_right-btm"></td>
<td width="80" align="center" class="tableinfo_right-btm"></td>
</tr></table>
<span id="camp_action_new"></span>
  </div>
  <div class="AccordionPanelContent" style="width:571px; background:#FFFFC4; border:solid 1px #333333; border-top:0;" onExpanderOpenPanel="viewCamp('new')">
<div id="view_camp_new">

<form id="camp_form_new" onSubmit="updateCamp('new',this); return false;">



</form>


</div>
  </div>
</div>

</div>
</td></tr></table>

<script language="javascript">
var campsAccordion = new Spry.Widget.Accordion("campsAccordion",{enableClose:true});
</script>


<?
} else {
  if (isset($_GET['edit_campaign']) && $_GET['edit_campaign']) {
    $targ=$campaign->getTargeting();
    $tlst=$campaign->getTargetingList();
    $limits=$campaign->getLimits();
    $opts=$campaign->getOptions();
    $schd=$campaign->getSchedule();
    $is_targ_regions=$targ['regions']?true:false;
    $is_targ_countries=!$is_targ_regions;
?>

<form id="camp_form_<?=$campID?>" onSubmit="updateCamp('<?=$campID?>',this); return false;">
<table width="500" cellpadding="0" cellspacing="0">
<tr>
<td valign="top" style="padding-left:36px;">
<div class="tabber" onClick="contentChanged();" style="text-align:left;">

     <div class="tabbertab" style="background:#FFFFFF;">
	  <h2>General Settings</h2>

<!--script language="JavaScript">
function textCounter(theField,theCharCounter,maxChars,maxLines,maxPerLine)
{
var strTemp = "";
var strCharCounter = 0;
for (var i = 0; i < theField.value.length; i++)
{
var strChar = theField.value.substring(i, i + 1);

if (strChar == '\n')
{
strTemp += strChar;
strCharCounter = 1;
}
else if (strCharCounter == maxPerLine)
{
strTemp += '\n' + strChar;
strCharCounter = 1;
}
else
{
strTemp += strChar;
strCharCounter ++;
}
}
theCharCounter.value = maxChars - strTemp.length;
}
</script-->

<table>
<tr><td>
<input type="text" name="name" maxlength="<?=$limits['maxchars']['name']?>" value="<?=$campaign->getName()?>" style="font-size:8pt;" onKeyUp="if ($('camp_name_remaining_<?=$campID?>')) $('camp_name_remaining_<?=$campID?>').innerHTML=<?=$limits['maxchars']['name']?>-this.value.length;"></td>
<td style="font-size:xx-small">(remaining chars: <span id="camp_name_remaining_<?=$campID?>"><?=$limits['maxchars']['name']-strlen($campaign->getName())?></span>)</td>
<!--input name="remChars" type="text" value="25" maxlength=3 style="width:10px; background:transparent; border:0px; font-size:10px; font-family:Tahoma; margin-bottom:1px;" readonly> <span style="color:#000000; font-size:10px; font-family:Tahoma;">/ 25</span--></td>
<td> &nbsp; Daily Budget $<input name="budget" type="text" value="<?=$limits['budget']?>" size="6" style="font-size:8pt;"></td><td> &nbsp; <input type="checkbox" name="active" value="1"<?=$campaign->getActive()?' checked':''?>> Active
</td>
</tr>
</table>
</div>

<div class="tabbertab" style="background:#FFFFFF;">
	  <h2>Ad Targeting</h2>
<table width="490" cellspacing="0" cellpadding="0">
<tr>
<td width="219" align="top" valign="top" style="padding:5px;">
Network Targeting:<br>
<? foreach ($tlst['network'] AS $ntg) { ?>
<input name="network[]" type="checkbox" value="<?=$ntg['id']?>"<?=in_array($ntg['id'],$targ['network'])?' checked':''?>> <?=$ntg['text']?><br>
<? } ?>
Ad Serving: &nbsp; <span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font>Ad serving determines how often we deliver your active ads in relation to one another within an Ad Group: <br><br>Optimize: The system will favor ads with a combination of a high clickthrough rate -CTR- and Quality Score. These ads will enter the ad auction more often. <br><br>Rotate: Each of your ads will enter the ad auction an equal number of times regardless of performance. Since ads with lower Quality Scores are then able to show as often as your better performing ads, choosing this option might lower your average position and result in less relevant clicks.<br>')" onMouseout="hideddrivetip()"> </span><br>
<input type="radio" name="optimize_ads" value="1"<?=$opts['optimize_ads']?' checked':''?>> Optimized Ad Delivery
<br>
<input type="radio" name="optimize_ads" value="0"<?=$opts['optimize_ads']?'':' checked'?>> Standard Ad Delivery<br><br>
Target Languages:
<?=tep_draw_mselect_menu('languages[]',$tlst['languages'],$targ['languages'],'size="5"')?>
<font style="font-size:9px;"> Hold down the <b>control</b> key to select multiple languages.</font> 
</td>
<td width="269" valign="top" style="padding:5px;">Geo Targeting: &nbsp; <?=$campaignCurrentGeo?> <?=$campaignCurrentLanguage?><br>

<div id="formnavigation">

<input type="radio" name="geotargeting" value="regions"<?=$is_targ_regions?' checked':''?> onChange="$('geo_countries_<?=$campID?>').style.display='none'; $('geo_regions_<?=$campID?>').style.display=''; "><b>Regions and cities</b> &nbsp; <span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font>Your ads will only appear to searchers located in the regional areas/cities you choose. (Only available in some locations.)<br>')" onMouseout="hideddrivetip()"> </span> <br>
<input type="radio" name="geotargeting" value="countries"<?=$is_targ_countries?' checked':''?> onChange="$('geo_countries_<?=$campID?>').style.display=''; $('geo_regions_<?=$campID?>').style.display='none'; "><b>Countries and territories</b> &nbsp; <span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font>Your ads will appear to searchers anywhere in the countries and/or territories you select.<br>')" onMouseout="hideddrivetip()"> </span>
<br>

<div class="multiparts" id="geo_countries_<?=$campID?>" style="<?=$is_targ_countries?'':'display:none; '?>">
<?=tep_draw_mselect_menu('countries[]',$tlst['countries'],$targ['countries'],'size="7" tabindex="1" style="font:8pt verdana; width:200px;"')?>
<br>
 <font style="font-size:9px;"> Hold down the <b>control</b> key to select multiple territories.</font> 
</div>
<div class="multiparts" id="geo_regions_<?=$campID?>" style="<?=$is_targ_regions?'':'display:none; '?>"><br>
Country:
<?
  $rctrys=Array();
  foreach ($tlst['regions'] AS $ctry=>$rgns) {
    foreach ($tlst['countries'] AS $c) if ($c['id']==$ctry) { $rctrys[]=$c; break; }
  }
  echo tep_draw_pull_down_menu('regions_country',$rctrys,'','onChange="switchFields(this.form,\'regions[]\',\'regions_'.$campID.'_\'+this.value)" style="font:8pt verdana;"')."\n";
  $ctryct=0;
  foreach ($tlst['regions'] AS $ctry=>$rgns) {
    echo tep_draw_mselect_menu('regions[]',$rgns,$targ['regions'],'size="7" tabindex="2" style="font:8pt verdana; width:200px;'.($ctryct++?' display:none;':'').'" id="regions_'.$campID.'_'.$ctry.'"')."\n";
  }
?>
<br>
			  <font style="font-size:9px;"> Hold down the <b>control</b> key to select multiple territories.</font> 
</div>
</div>
<br>
</td></tr></table>
</div>

<div class="tabbertab" style="background:#FFFFFF;">
	  <h2>Ad Scheduling</h2>
<table width="490" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td colspan="4" valign="top">
      <table width="406" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
          <td>Start Date:<br>
<span style="background:#FFFF00; width:100px;"><?=$limits['start_date']?></span></td>
          <td>End Date: <br>
<input name="end_date" id="camp_end_date_<?=$campID?>" type="text" value="<?=$limits['end_date']?>" size="8" style="font-size:8pt;" onClick="popUpCalendar(this,this,'mm/dd/yyyy',document);"><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="popUpCalendar($('camp_end_date_<?=$campID?>'),$('camp_end_date_<?=$campID?>'),'mm/dd/yyyy',document);" style="cursor:pointer">
</td>
        </tr>
      </table></td>
    </tr>
  <tr>
    <td colspan="4" valign="top">Ad Time Zone:
<?
$tzList=Array(
Array('id'=>-12,'text'=>"GMT - 12 Hours"),
Array('id'=>-11,'text'=>"GMT - 11 Hours"),
Array('id'=>-10,'text'=>"GMT - 10 Hours"),
Array('id'=>-9,'text'=>"GMT - 9 Hours"),
Array('id'=>-8,'text'=>"GMT - 8 Hours"),
Array('id'=>-7,'text'=>"GMT - 7 Hours"),
Array('id'=>-6,'text'=>"GMT - 6 Hours"),
Array('id'=>-5,'text'=>"GMT - 5 Hours (Eastern Standard)"),
Array('id'=>-4,'text'=>"GMT - 4 Hours"),
Array('id'=>-3.5,'text'=>"GMT - 3.5 Hours"),
Array('id'=>-3,'text'=>"GMT - 3 Hours"),
Array('id'=>-2,'text'=>"GMT - 2 Hours"),
Array('id'=>-1,'text'=>"GMT - 1 Hours"),
Array('id'=>0,'text'=>"GMT"),
Array('id'=>1,'text'=>"GMT + 1 Hour"),
Array('id'=>2,'text'=>"GMT + 2 Hours"),
Array('id'=>3,'text'=>"GMT + 3 Hours"),
Array('id'=>3.5,'text'=>"GMT + 3.5 Hours"),
Array('id'=>4,'text'=>"GMT + 4 Hours"),
Array('id'=>4.5,'text'=>"GMT + 4.5 Hours"),
Array('id'=>5,'text'=>"GMT + 5 Hours"),
Array('id'=>5.5,'text'=>"GMT + 5.5 Hours"),
Array('id'=>6,'text'=>"GMT + 6 Hours"),
Array('id'=>6.5,'text'=>"GMT + 6.5 Hours"),
Array('id'=>7,'text'=>"GMT + 7 Hours"),
Array('id'=>8,'text'=>"GMT + 8 Hours"),
Array('id'=>9,'text'=>"GMT + 9 Hours"),
Array('id'=>9.5,'text'=>"GMT + 9.5 Hours"),
Array('id'=>10,'text'=>"GMT + 10 Hours"),
Array('id'=>11,'text'=>"GMT + 11 Hours"),
Array('id'=>12,'text'=>"GMT + 12 Hours"),
Array('id'=>13,'text'=>"GMT + 13 Hours"));
?>
<?=tep_draw_pull_down_menu('timezone',$tzList,$schd[0]['timezone'])?>
<span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font><b>Ad Scheduling</b><br><br>By enabling ad scheduling in your campaign, you can choose which days in the week and which hours in the day that you\'d like your ads to run.<br>')" onMouseout="hideddrivetip()"> </span>
<br>
<font style="color:red; font-size:9px;">(This is NOT Geo-targeting - For Ad scheduling only)</font></td>
    </tr>
  <tr>
    <td valign="top"></td>
    <td><b>Time On</b></td>
    <td width="5" rowspan="8">&nbsp;</td>
    <td><b>Time Off</b></td>
  </tr>


<?
  $schdays=Array();
  foreach ($schd AS $sch) {
    if (!isset($schdays[$sch['weekday']])) $schdays[$sch['weekday']]=$sch;
  }
  $weekdays=Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
  $sel_hours=Array();
  for ($i=0;$i<24;$i++) $sel_hours[]=Array('id'=>$i,'text'=>sprintf('%02d:00 / %d:00%s',$i,($i+11)%12+1,($i>=12?'pm':'am')));
  foreach ($weekdays AS $day=>$dayname) {
?>  
  <tr>
    <td valign="top" nowrap style="padding-right:5px;"><label>
      <input name="schedule_allow[<?=$day?>]" type="checkbox" value="1"<?=isset($schdays[$day])?' checked':''?>>
    <?=$dayname?></label></td>
    <td height="19" nowrap>Hour: <?=tep_draw_pull_down_menu('schedule_start['.$day.']',$sel_hours,(isset($schdays[$day])?$schdays[$day]['start_hour']:0),'style="font:8pt; verdana;"')?>
    </td>
    <td nowrap>Hour: <?=tep_draw_pull_down_menu('schedule_end['.$day.']',$sel_hours,(isset($schdays[$day])?$schdays[$day]['end_hour']:0),'style="font:8pt; verdana;"')?>
    </td>
  </tr>
<?
  }
?>
</table>
</div>

</div>
</td></tr>
</table>
<input type="submit" name="update" value="Save Campaign">
</form>
<script language="javascript">
 tabberAutomatic();
</script>
<?
  }
  if ($campID!='new') {
?>
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
<div class="Accordion" id="adsAccordion_<?=$campID?>" tabindex="0">
<?
   $adlst=Array();
   foreach ($campaign->getAds($adkey) AS $aid=>$ad) {
     $adlst[]=$aid;
//     $st=$ad->getStats($stats_start,$stats_end);
?>    
<div class="AccordionPanel">
  <div class="AccordionPanelTab" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover=""><table width=571 cellpadding=0 cellspacing=0>
<tr><td colspan="8"></td></tr>
<tr>
<td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
<td width="118" align="center" class="tableinfo_right-btm"><?=$ad->getName()?></td>
<td width="65" align="center" class="tableinfo_right-btm">
    <? if ($ad->getDeleted()) { ?> <font style="color:#FF0000">Deleted</font> <? } else if ($ad->getActive()) { ?> <font style="color:#00FF00">Active</font> <? } else { ?> <font style="color:#FF0000">Inactive</font> <? } ?></td>
<td width="65" align="center" class="tableinfo_right-btm"><span id="ad_stats_<?=$aid?>_avgpos">-</span></td>
<td width="70" align="center" class="tableinfo_right-btm"><span id="ad_stats_<?=$aid?>_imprs">-</span></td>
<td width="70" align="center" class="tableinfo_right-btm"><span id="ad_stats_<?=$aid?>_clicks">-</span></td>
<td width="70" align="center" class="tableinfo_right-btm"><span id="ad_stats_<?=$aid?>_convs">-</span></td>
<td width="80" align="center" class="tableinfo_right-btm">$<span id="ad_stats_<?=$aid?>_cost">-</span></td>
<td>[<a href="javascript:void(0)" onClick="deleteAd('<?=$aid?>','<?=$campID?>'); return false;">x</a>]</td>
</tr></table>
<span id="ad_action_<?=$aid?>"></span>
  </div>
  <div class="AccordionPanelContent" onExpanderOpenPanel="viewAd('<?=$aid?>','<?=$campID?>')" style="width:571px; background:#FFFFC4; border:solid 1px #333333; border-top:0;">
<div id="view_ad_<?=$aid?>"></div>
  </div>
</div>
<? } ?>

<div class="AccordionPanel">
  <div class="AccordionPanelTab" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover=""><table width=571 cellpadding=0 cellspacing=0>
<tr><td colspan="8"></td></tr>
<tr>
<td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
<td width="118" align="center" class="tableinfo_right-btm">New Ad</td>
<td width="65" align="center" class="tableinfo_right-btm"></td>
<td width="65" align="center" class="tableinfo_right-btm"></td>
<td width="70" align="center" class="tableinfo_right-btm"></td>
<td width="70" align="center" class="tableinfo_right-btm"></td>
<td width="70" align="center" class="tableinfo_right-btm"></td>
<td width="80" align="center" class="tableinfo_right-btm"></td>
</tr></table>
<span id="ad_action_new_<?=$campID?>"></span>
  </div>
  <div class="AccordionPanelContent" style="width:571px; background:#FFFFC4; border:solid 1px #333333; border-top:0;">
<div>


<form id="ad_form_new_<?=$campID?>" onSubmit="updateAd('new_<?=$campID?>','<?=$campID?>',this); return false;"><table>
<tr><td>Primary Keyword:</td><td> <input type="text" name="pkeyw" value="<?=htmlspecialchars($defaultAd['keyword'])?>"></td></tr>
<tr><td valign="top" style="padding-top:4px;">Keywords Variations:</td><td>
<table id="keyw_section">
<tr><td><input type="text" name="keyw[]" onChange="adjustKeywSection($('keyw_section'),'<?=$campID?>')"></td><td><input type="text" name="keyw_maxcpc[]" size="7"></td></tr>
<tr><td><input type="text" name="keyw[]" onChange="adjustKeywSection($('keyw_section'),'<?=$campID?>')"></td><td><input type="text" name="keyw_maxcpc[]" size="7"></td></tr>
</table>
</table>
<div id="ad_variations_new_<?=$campID?>">

<div>
<table width="99%">
<tr><td>
<table>
<tr><td>Ad Headline:</td><td><input type="text" name="head[]" value="<?=htmlspecialchars($defaultAd['head'])?>" size="25" maxlength="25" style="font-size:8pt; font-family:verdana;"></td></tr>
<tr><td>Ad Text:</td><td><textarea name="text[]" cols="35" rows="2" maxlength="100" style="font-size:8pt; font-family:verdana;"><?=htmlspecialchars($defaultAd['text'])?></textarea></td></tr>
<tr><td>Display URL:</td><td><input type="text" name="url[]" value="<?=$defaultAd['url']?>" size="35" maxlength="35" style="font-size:8pt; font-family:verdana;"></td></tr>
</table>
[<a href="javascript:void(0)" onClick="delAdVariation(this,'<?=$campID?>')">Remove Ad</a>]
</td>
<td valign="top">
</td>
</tr></table>
</div>
</div>
[<a href="javascript:addAdVariation('new','<?=$campID?>')">Add New Ad</a>]
<table>
<tr><td>Max Cost per Click:</td><td>$<input type="text" name="maxcpc" value="<?=$defaultAd['maxcpc']?>" size="5"></td></tr>
<!--<tr><td>Max Cost per Content Click:</td><td>$<input type="text" name="maxcontentcpc" value="" size="5"></td></tr>-->
<tr><td>&nbsp;</td><td><input type="checkbox" name="active" value="1" checked> Active</td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="update" value="Save"></td></tr>
</table></form>


</div>
  </div>
</div>

</div>
</td></tr></table>

<script language="javascript">
adsAccordions['<?=$campID?>'] = new Spry.Widget.Accordion("adsAccordion_<?=$campID?>",{enableClose:true});
</script>
<?
  }
}
?>

</body>
</html>
