<?
  require('includes/application_top.php');

  $engine=isset($_GET['engine'])?$_GET['engine']:'adwords';
  $adobjname="ads_$engine";
  require(DIR_FS_MODULES."ads/$adobjname.php");
  $adobj=new $adobjname;
  if (isset($_GET['show_del']) && $_GET['show_del']) $adobj->showDeleted(true);
  $adkey=NULL;
  $selflinkarg='view_all=1';
  if (isset($_GET['pID'])) {
    $pID=$_GET['pID'];
    $adkey='products_id='.$pID;
    $selflinkarg='pID='.$pID;
  }
  $selflink=tep_href_link('product_ads.php',$selflinkarg);
  
  if (isset($_GET['adID'])) {
    $aid=$_GET['adID'];
    if ($aid=='new') {
      $camp=$adobj->getCampaign($_GET['campID']);
      $ad=$camp->newAd();
      $ad->setKey($adkey);
    } else $ad=$adobj->getAd($_GET['campID'],$aid);
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
?><form id="ad_form_<?=$aid?>" onSubmit="updateAd('<?=$aid?>',this); return false;"></form><?
    }
    exit;
  }
  
?>
<html>
<head>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="js/tips.js"></script>
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
<script type="text/javascript" src="js/tabber.js"></script>
<link rel="stylesheet" href="js/tabber.css" TYPE="text/css" MEDIA="screen">
<script type="text/javascript">
document.write('<style type="text/css">.tabber{display:none;}<\/style>');
</script>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></head>
<body><form style="margin:0;">
<table width="571" cellpadding="0" cellspacing="0">
<tr>
<td valign="top" style="padding-top:10px;">
<?
  $stats_start=date('Y-m-d',time()-86400*30);
  $stats_end=date('Y-m-d');
  $engine_box=Array(Array('id'=>'adwords',text=>'Google AdWords'),Array('id'=>'overture',text=>'Yahoo Overture'));
?>
 &nbsp; Channel: 
<?=tep_draw_pull_down_menu('engine',$engine_box,$engine,' onChange="document.location=\''.$selflink."&engine='+this.value".'" style="font:8pt verdana"')?>

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

?> </td><td valign="top" style="padding-top:5px; padding-bottom:5px;">
<table cellpadding="0" cellspacing="0" style="border:solid 1px #333333; background:#FFFFFF; padding:5px;">
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
<input type="text" name="date_from" id="date_from" style="font:bold 9px arial;" onClick="popUpCalendar(this,this,'mm-dd-yyyy',document);" value="" size="12" maxlength="11" textfield></td>
  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="popUpCalendar($('date_from'),$('date_from'),'mm-dd-yyyy',document);" style="cursor:pointer"></td>
  <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
  <td align="right" style="padding-top:2px;"><input type="text" name="date_to" id="date_to" onClick="popUpCalendar($('date_from'),this,'mm-dd-yyyy',document);" style="font:bold 9px arial;" value="" size="12" maxlength="11" textfield></td>
  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="popUpCalendar($('date_from'),$('date_to'),'mm-dd-yyyy',document);" style="cursor:pointer"></td>
</tr>
</table>
</td></tr></table></td></tr></table>

</form>

<div id="response_box"></div>
<table width="571" cellpadding=0 cellspacing=0>
<tr>
<td colspan="8" style="background-color:#6295FD; height:20px;"><table width="571" border="0" cellpadding="0" cellspacing="0" >
  <tr>
<td width="30" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14"></td>
<td width="119" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:4px;">Campaign</td>
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
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Max
      CPC </td>
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
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Impr.</td>
      <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
    </tr>
    <tr>
      <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
    </tr>
  </table></td>
<td width="70" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">CTR</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Avg.
      CPC</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent">    <table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Cost</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table>
  </td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Avg.
      Po </td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">Conv.
      %</td>
    <td style="padding-top:2px;"><a href="#"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
  </tr>
  <tr>
    <td valign="top"><a href="#"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
  </tr>
</table></td>
<td width="80" align="center" class="dataTableHeadingContent"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td rowspan="2" align="right" class="dataTableHeadingContent" style="padding-right:5px;">CPC </td>
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
<div class="Accordion" id="adsAccordion" tabindex="0">
<? foreach ($campaign->getAds('products_id='.$pID) AS $aid=>$ad) {
     $st=$ad->getStats($stats_start,$stats_end);
?>    
<div class="AccordionPanel">
  <div class="AccordionPanelTab" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover=""><table width=571 cellpadding=0 cellspacing=0>
<tr><td colspan="12"></td></tr>
<tr>
<td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
<td width="118" align="center" class="tableinfo_right-btm"><?=$c->getName()?></td>
<td width="65" align="center" class="tableinfo_right-btm">
    <? if ($ad->getActive()) { ?> <font style="color:#00FF00">Active</font> <? } else { ?> <font style="color:#FF0000">Inactive</font> <? } ?></td>
<td width="65" align="center" class="tableinfo_right-btm">&nbsp;</td>
<td width="70" align="center" class="tableinfo_right-btm"><?=$st['clicks']?></td>
<td width="70" align="center" class="tableinfo_right-btm"><?=$st['imprs']?></td><td width="70" align="center" class="tableinfo_right-btm">&nbsp;</td><td width="80" align="center" class="tableinfo_right-btm">&nbsp;</td>
<td width="80" align="center" class="tableinfo_right-btm">$
  <?=$st['cost']?></td>
<td width="80" align="center" class="tableinfo_right-btm"><?=$st['avgpos']?></td>
<td width="80" align="center" class="tableinfo_right-btm"><?=$st['convs']?></td>
<td width="80" align="center" class="tableinfo_right-btm">&nbsp;</td>
</tr></table>
<span id="ad_action_<?=$aid?>"></span>
  </div>
  <div class="AccordionPanelContent" onExpanderOpenPanel="viewAd('<?=$aid?>')" style="width:571px; background:#FFFFC4; border:solid 1px #333333; border-top:0;"><table width="500" cellpadding="0" cellspacing="0">
<tr>
<td valign="top" style="padding-left:36px;">
<div class="tabber" onClick="contentChanged();" style="text-align:left;">

     <div class="tabbertab" style="background:#FFFFFF;">
	  <h2>General Settings</h2>

<script language="JavaScript">
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
</script>

<form name="theForm" style="margin:0;"><table>
<tr><td>
<input type="text" name="myText" maxlength="30" value="<?=$c->getName()?>" style="font-size:8pt;" onKeyUp="textCounter(theForm.myText,theForm.remChars,30,1,100);"></td>
<td>
<input name="remChars" type="text" value="25" maxlength=3 style="width:10px; background:transparent; border:0px; font-size:10px; font-family:Tahoma; margin-bottom:1px;" readonly> <span style="color:#000000; font-size:10px; font-family:Tahoma;">/ 25</span></td><td> &nbsp; Daily Budget $<input name="" type="text" value="<?=$dailybudget?>" size="6" style="font-size:8pt;"></td><td> &nbsp; Status: <select name="" value="<?=$status?>" style="font-size:8pt;">
<option>Active</option>
<option>Paused</option>
</select></td>
</tr>
</table>
</form>
</div>

<div class="tabbertab" style="background:#FFFFFF;">
	  <h2>Ad Targeting</h2>
<table width="490" cellspacing="0" cellpadding="0">
<tr>
<td width="219" align="top" valign="top" style="padding:5px;">
Network Targeting:<br>
<input name="" type="checkbox" value=""> Google Search<br>
<input name="" type="checkbox" value=""> Search Network<br>
<input name="" type="checkbox" value=""> Content Network<br>
<br>
Ad Serving: &nbsp; <span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font>Ad serving determines how often we deliver your active ads in relation to one another within an Ad Group: <br><br>Optimize: The system will favor ads with a combination of a high clickthrough rate -CTR- and Quality Score. These ads will enter the ad auction more often. <br><br>Rotate: Each of your ads will enter the ad auction an equal number of times regardless of performance. Since ads with lower Quality Scores are then able to show as often as your better performing ads, choosing this option might lower your average position and result in less relevant clicks.<br>')" onMouseout="hideddrivetip()"> </span><br>
<input type="radio" value="radio"> Optimized Ad Delivery
<br>
<input type="radio" value="radio" checked> Standard Ad Delivery<br><br>
Target Languages:
<select name="language" multiple size="5">
<option value="*"> All Languages </option>
<option value="------0"> ------ </option>
<option value="en" selected> English </option>
<option value="zh_CN"> Chinese (simplified) </option>    <option value="zh_TW"> Chinese (traditional) </option>    <option value="da"> Danish </option>    <option value="nl"> Dutch </option>            <option value="fi"> Finnish </option>    <option value="fr"> French </option>    <option value="de"> German </option>    <option value="iw"> Hebrew </option>    <option value="it"> Italian </option>    <option value="ja"> Japanese </option>    <option value="ko"> Korean </option>    <option value="no"> Norwegian </option>    <option value="pl"> Polish </option>        <option value="ru"> Russian </option>    <option value="es"> Spanish </option>    <option value="sv"> Swedish </option>    <option value="tr"> Turkish </option>   <option value="------1"> ------ </option>    <option value="ar"> Arabic </option>    <option value="bg"> Bulgarian </option>    <option value="ca"> Catalan </option>    <option value="zh_CN"> Chinese (simplified) </option>    <option value="zh_TW"> Chinese (traditional) </option>    <option value="hr"> Croatian </option>    <option value="cs"> Czech </option>    <option value="da"> Danish </option>    <option value="nl"> Dutch </option>    <option value="en"> English </option>          <option value="et"> Estonian </option>    <option value="fi"> Finnish </option>    <option value="fr"> French </option>    <option value="de"> German </option>    <option value="el"> Greek </option>    <option value="iw"> Hebrew </option>    <option value="hi"> Hindi </option>    <option value="hu"> Hungarian </option>    <option value="is"> Icelandic </option>    <option value="id"> Indonesian </option>    <option value="it"> Italian </option>    <option value="ja"> Japanese </option>    <option value="ko"> Korean </option>    <option value="lv"> Latvian </option>    <option value="lt"> Lithuanian </option>    <option value="no"> Norwegian </option>    <option value="pl"> Polish </option>    <option value="pt"> Portuguese </option>        <option value="ro"> Romanian </option>    <option value="ru"> Russian </option>    <option value="sr"> Serbian </option>    <option value="sk"> Slovak </option>    <option value="sl"> Slovenian </option>    <option value="es"> Spanish </option>    <option value="sv"> Swedish </option>    <option value="tl"> Tagalog </option>    <option value="th"> Thai </option>    <option value="tr"> Turkish </option>    <option value="uk"> Ukrainian </option>    <option value="ur"> Urdu </option>    <option value="vi"> Vietnamese </option>     </select><br>
 <font style="font-size:9px;"> Hold down the <b>control</b> key to select multiple languages.</font> 
</td>
<td width="269" valign="top" style="padding:5px;"><form style="margin:0">Geo Targeting: &nbsp; <?=$campaignCurrentGeo?> <?=$campaignCurrentLanguage?><br>

<script type="text/javascript">

if (document.getElementById){
document.write('<style type="text/css">')
document.write('.multiparts, #formnavigation{display:none;}')
document.write('</style>')
}

var curpart=0

function getElementbyClass(classname){
partscollect=new Array()

var inc=0
var alltags=document.all? document.all : document.getElementsByTagName("*")
for (i=0; i<alltags.length; i++){
if (alltags[i].className==classname)

partscollect[inc++]=alltags[i]
}
}

function cycleforward(){
partscollect[curpart].style.display="none"
curpart=(curpart<partscollect.length-1)? curpart+1 : 0
partscollect[curpart].style.display="block"
updatenav()
}

function cycleback(){
partscollect[curpart].style.display="none"
curpart=(curpart>0)? curpart-1 : partscollect.length-1
partscollect[curpart].style.display="block"
updatenav()
}


function updatenav(){
document.getElementById("backbutton").style.visibility=(curpart==0)? "hidden" : "visible"
document.getElementById("forwardbutton").style.visibility=(curpart==partscollect.length-1)? "hidden" : "visible"
}

function onloadfunct(){
getElementbyClass("multiparts")
partscollect[0].style.display="block"
document.getElementById("formnavigation").style.display="block"
updatenav()
}

if (window.addEventListener)
window.addEventListener("load", onloadfunct, false)
else if (window.attachEvent)
window.attachEvent("onload", onloadfunct)
else if (document.getElementById)
window.onload=onloadfunct

</script>
<div id="formnavigation">

<input type="radio" name="geocity" value="2" onClick="cycleback()"><b>Regions and cities</b> &nbsp; <span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font>Your ads will only appear to searchers located in the regional areas/cities you choose. (Only available in some locations.)<br>')" onMouseout="hideddrivetip()"> </span> <br>
 <input type="radio" name="geocountry" value="1" checked onClick="cycleback()"><b>Countries and territories</b> &nbsp; <span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font>Your ads will appear to searchers anywhere in the countries and/or territories you select.<br>')" onMouseout="hideddrivetip()"> </span>
<br>

<div class="multiparts">
<select name="country" size="7" multiple onchange="" id="countryList" tabindex="1" style="font:8pt verdana; width:200px;"> 
<option value="*" selected> All Countries and Territories </option> 
<option value="------0">------</option>                        
<option value="AU"> Australia  </option>                       
<option value="AT"> Austria  </option>                       
<option value="BE"> Belgium  </option>                       
<option value="BR"> Brazil  </option>                       
<option value="CA"> Canada  </option>                       
<option value="CN"> China  </option>                       
<option value="DK"> Denmark  </option>                       
<option value="FI"> Finland  </option>                       
<option value="FR"> France  </option>                       
<option value="DE"> Germany  </option>                      
<option value="HK"> Hong Kong  </option>                       
<option value="IT"> Italy  </option>                    
<option value="JP"> Japan  </option>                       
<option value="NL"> Netherlands  </option>                       
<option value="NO"> Norway  </option>                       
<option value="PT"> Portugal  </option>                       
<option value="SG"> Singapore  </option>                       
<option value="KR"> South Korea  </option>                       
<option value="ES"> Spain  </option>                       
<option value="SE"> Sweden  </option>                       
<option value="CH"> Switzerland  </option>                       
<option value="TW"> Taiwan  </option>                       
<option value="GB"> United Kingdom  </option>                       
<option value="US"> United States  </option>          
<option value="------">------</option>              
<option value="AF"> Afghanistan  </option>
<option value="AL"> Albania  </option>
<option value="DZ"> Algeria  </option>
<option value="AS"> American Samoa  </option>
<option value="AD"> Andorra  </option>
<option value="AO"> Angola  </option>
<option value="AI"> Anguilla  </option>
<option value="AQ"> Antarctica  </option>
<option value="AG"> Antigua and Barbuda  </option>
<option value="AR"> Argentina  </option>
<option value="AM"> Armenia  </option>
<option value="AW"> Aruba  </option>
<option value="AU"> Australia  </option>
<option value="AT"> Austria  </option>
<option value="AZ"> Azerbaijan  </option>
<option value="BS"> Bahamas  </option>
<option value="BH"> Bahrain  </option>
<option value="BD"> Bangladesh  </option>
<option value="BB"> Barbados  </option>
<option value="BY"> Belarus  </option>
<option value="BE"> Belgium  </option>
<option value="BZ"> Belize  </option>
<option value="BJ"> Benin  </option>
<option value="BM"> Bermuda  </option>
<option value="BT"> Bhutan  </option>
<option value="BO"> Bolivia  </option>
<option value="BA"> Bosnia and Herzegovina  </option>
<option value="BW"> Botswana  </option>
<option value="BV"> Bouvet Island  </option>
<option value="BR"> Brazil  </option>
<option value="IO"> British Indian Ocean Territory  </option>
<option value="BN"> Brunei Darussalam  </option>
<option value="BG"> Bulgaria  </option>
<option value="BF"> Burkina Faso  </option>
<option value="BI"> Burundi  </option>
<option value="KH"> Cambodia  </option>
<option value="CM"> Cameroon  </option>
<option value="CA"> Canada  </option>
<option value="CV"> Cape Verde  </option>
<option value="KY"> Cayman Islands  </option>
<option value="CF"> Central African Republic  </option>
<option value="TD"> Chad  </option>
<option value="CL"> Chile  </option>
<option value="CN"> China  </option>
<option value="CX"> Christmas Island  </option>
<option value="CC"> Cocos (Keeling) Islands  </option>
<option value="CO"> Colombia  </option>
<option value="KM"> Comoros  </option>
<option value="CG"> Congo  </option>
<option value="CD"> Congo, Democratic Republic  </option>
<option value="CK"> Cook Islands  </option>
<option value="CR"> Costa Rica  </option>
<option value="CI"> Cote d'Ivoire  </option>
<option value="HR"> Croatia  </option>
<option value="CU"> Cuba  </option>
<option value="CY"> Cyprus  </option>
<option value="CZ"> Czech Republic  </option>
<option value="DK"> Denmark  </option>
<option value="DJ"> Djibouti  </option>
<option value="DM"> Dominica  </option>
<option value="DO"> Dominican Republic  </option>
<option value="TL"> East Timor  </option>
<option value="EC"> Ecuador  </option>
<option value="EG"> Egypt  </option>
<option value="SV"> El Salvador  </option>
<option value="GQ"> Equatorial Guinea  </option>
<option value="ER"> Eritrea  </option>
<option value="EE"> Estonia  </option>
<option value="ET"> Ethiopia  </option>
<option value="FK"> Falkland Islands (Malvinas)  </option>
<option value="FO"> Faroe Islands  </option>
<option value="FJ"> Fiji  </option>
<option value="FI"> Finland  </option>
<option value="FR"> France  </option>                       
<option value="GF"> French Guiana  </option>
<option value="PF"> French Polynesia  </option>
<option value="TF"> French Southern Territories  </option>
<option value="GA"> Gabon  </option>
<option value="GM"> Gambia  </option>
<option value="GE"> Georgia  </option>
<option value="DE"> Germany  </option>
<option value="GH"> Ghana  </option>
<option value="GI"> Gibraltar  </option>
<option value="GR"> Greece  </option>
<option value="GL"> Greenland  </option>
<option value="GD"> Grenada  </option>
<option value="GP"> Guadeloupe  </option>
<option value="GU"> Guam  </option>
<option value="GT"> Guatemala  </option>
<option value="GN"> Guinea  </option>
<option value="GW"> Guinea-Bissau  </option>
<option value="GY"> Guyana  </option>
<option value="HT"> Haiti  </option>
<option value="HM"> Heard and McDonald Islands  </option>
<option value="HN"> Honduras  </option>
<option value="HK"> Hong Kong  </option>
<option value="HU"> Hungary  </option>
<option value="IS"> Iceland  </option>
<option value="IN"> India  </option>
<option value="ID"> Indonesia  </option>
<option value="IR"> Iran  </option>
<option value="IQ"> Iraq  </option>
<option value="IE"> Ireland  </option>
<option value="IL"> Israel  </option>
<option value="IT"> Italy  </option>
<option value="JM"> Jamaica  </option>
<option value="JP"> Japan  </option>
<option value="JO"> Jordan  </option>
<option value="KZ"> Kazakhstan  </option>
<option value="KE"> Kenya  </option>
<option value="KI"> Kiribati  </option>
<option value="KW"> Kuwait  </option>
<option value="KG"> Kyrgyzstan  </option>

<option value="LA"> Lao People's Democratic Republic  </option>
<option value="LV"> Latvia  </option>
<option value="LB"> Lebanon  </option>
<option value="LS"> Lesotho  </option>
<option value="LR"> Liberia  </option>
<option value="LY"> Libya  </option>
<option value="LI"> Liechtenstein  </option>
<option value="LT"> Lithuania  </option>
<option value="LU"> Luxembourg  </option>
<option value="MO"> Macau  </option>
<option value="MK"> Macedonia  </option>
<option value="MG"> Madagascar  </option>
<option value="MW"> Malawi  </option>
<option value="MY"> Malaysia  </option>
<option value="MV"> Maldives  </option>
<option value="ML"> Mali  </option>
<option value="MT"> Malta  </option>
<option value="MH"> Marshall Islands  </option>
<option value="MQ"> Martinique  </option>
<option value="MR"> Mauritania  </option>
<option value="MU"> Mauritius  </option>
<option value="YT"> Mayotte  </option>
<option value="MX"> Mexico  </option>
<option value="FM"> Micronesia  </option>
<option value="MD"> Moldova  </option>
<option value="MC"> Monaco  </option>
<option value="MN"> Mongolia  </option>
<option value="MS"> Montserrat  </option>
<option value="MA"> Morocco  </option>
<option value="MZ"> Mozambique  </option>
<option value="MM"> Myanmar  </option>
<option value="NA"> Namibia  </option>
<option value="NR"> Nauru  </option>
<option value="NP"> Nepal  </option>
<option value="NL"> Netherlands  </option>
<option value="AN"> Netherlands Antilles  </option>
<option value="NC"> New Caledonia  </option>
<option value="NZ"> New Zealand  </option>
<option value="NI"> Nicaragua  </option>
<option value="NE"> Niger  </option>
<option value="NG"> Nigeria  </option>
<option value="NU"> Niue  </option>
<option value="NF"> Norfolk Island  </option>
<option value="MP"> Northern Mariana Islands  </option>
<option value="KP"> North Korea  </option>
<option value="NO"> Norway  </option>
<option value="OM"> Oman  </option>
<option value="PK"> Pakistan  </option>
<option value="PW"> Palau  </option>
<option value="PS"> Palestinian Territory  </option>
<option value="PA"> Panama  </option>
<option value="PG"> Papua New Guinea  </option>
<option value="PY"> Paraguay  </option>
<option value="PE"> Peru  </option>
<option value="PH"> Philippines  </option>
<option value="PN"> Pitcairn  </option>
<option value="PL"> Poland  </option>
<option value="PT"> Portugal  </option>
<option value="PR"> Puerto Rico  </option>
<option value="QA"> Qatar  </option>
<option value="RE"> Reunion  </option>
<option value="RO"> Romania  </option>
<option value="RU"> Russian Federation  </option>
<option value="RW"> Rwanda  </option>
<option value="KN"> Saint Kitts and Nevis  </option>
<option value="LC"> Saint Lucia  </option>
<option value="VC"> Saint Vincent and the Grenadines  </option>
<option value="WS"> Samoa  </option>
<option value="SM"> San Marino  </option>
<option value="ST"> Sao Tome and Principe  </option>
<option value="SA"> Saudi Arabia  </option>
<option value="SN"> Senegal  </option>
<option value="CS"> Serbia and Montenegro  </option>
<option value="SC"> Seychelles  </option>
<option value="SL"> Sierra Leone  </option>
<option value="SG"> Singapore  </option>
<option value="SK"> Slovakia  </option>
<option value="SI"> Slovenia  </option>
<option value="SB"> Solomon Islands  </option>
<option value="SO"> Somalia  </option>
<option value="ZA"> South Africa  </option>
<option value="GS"> South Georgia and The South Sandwich Islands  </option>
<option value="KR"> South Korea  </option>
<option value="ES"> Spain  </option>
<option value="LK"> Sri Lanka  </option>
<option value="SH"> St. Helena  </option>
<option value="PM"> St. Pierre and Miquelon  </option>
<option value="SD"> Sudan  </option>
<option value="SR"> Suriname  </option>
<option value="SJ"> Svalbard and Jan Mayen Islands  </option>
<option value="SZ"> Swaziland  </option>
<option value="SE"> Sweden  </option>
<option value="CH"> Switzerland  </option>
<option value="SY"> Syria  </option>
<option value="TW"> Taiwan  </option>
<option value="TJ"> Tajikistan  </option>
<option value="TZ"> Tanzania  </option>
<option value="TH"> Thailand  </option>
<option value="TG"> Togo  </option>
<option value="TK"> Tokelau  </option>
<option value="TO"> Tonga  </option>
<option value="TT"> Trinidad and Tobago  </option>
<option value="TN"> Tunisia  </option>
<option value="TR"> Turkey  </option>
<option value="TM"> Turkmenistan  </option>
<option value="TC"> Turks and Caicos Islands  </option>
<option value="TV"> Tuvalu  </option>
<option value="UG"> Uganda  </option>
<option value="UA"> Ukraine  </option>
<option value="AE"> United Arab Emirates  </option>
<option value="GB"> United Kingdom  </option>
<option value="US"> United States  </option>
<option value="UM"> United States Minor Outlying Islands  </option>
<option value="UY"> Uruguay  </option>
<option value="UZ"> Uzbekistan  </option>
<option value="VU"> Vanuatu  </option>
<option value="VA"> Vatican  </option>
<option value="VE"> Venezuela  </option>
<option value="VN"> Viet Nam  </option>
<option value="VG"> Virgin Islands (British)  </option>
<option value="VI"> Virgin Islands (U.S.)  </option>
<option value="WF"> Wallis and Futuna Islands  </option>
<option value="EH"> Western Sahara  </option>
<option value="YE"> Yemen  </option>
<option value="ZM"> Zambia  </option>
<option value="ZW"> Zimbabwe  </option>          
</select><br>
 <font style="font-size:9px;"> Hold down the <b>control</b> key to select multiple territories.</font> 
</div>
<div class="multiparts"><br>
Country:   <select name="country" id="country" style="font:8pt verdana; margin-bottom:2px;">   <option value="AU"> Australia </option>  <option value="AT"> Austria </option>  <option value="BE"> Belgium </option>  <option value="BR"> Brazil </option>  <option value="CA"> Canada </option>  <option value="CN"> China </option>  <option value="DK"> Denmark </option>  <option value="FI"> Finland </option>  <option value="FR"> France </option>  <option value="DE"> Germany </option>  <option value="IN"> India </option>  <option value="IE"> Ireland </option>  <option value="IT"> Italy </option>  <option value="JP"> Japan </option>  <option value="MX"> Mexico </option>  <option value="NL"> Netherlands </option>  <option value="NZ"> New Zealand </option>  <option value="NO"> Norway </option>  <option value="PL"> Poland </option>  <option value="RU"> Russian Federation </option>  <option value="KR"> South Korea </option>  <option value="ES"> Spain </option>  <option value="SE"> Sweden </option>  <option value="CH"> Switzerland </option>  <option value="TR"> Turkey </option>  <option value="GB"> United Kingdom </option>  <option value="US" selected> United States </option>  </select>   <br>
<select name="regionsmetros" style="font:8pt verdana; width:200px;" tabindex="2" multiple id="regionsmetroslist" size="7"> 
  <option value="US-AL"> Alabama AL </option>
  <option value="US-AL:630"> ---Birmingham AL </option>
  <option value="US-AL:606"> ---Dothan AL </option>
  <option value="US-AL:691"> ---Huntsville-Decatur (Florence) AL </option> 
  <option value="US-AL:686"> ---Mobile AL-Pensacola (Ft. Walton Beach) FL </option>
  <option value="US-AL:698"> ---Montgomery (Selma) AL </option>
  <option value="US-AK"> Alaska AK </option>
  <option value="US-AK:743"> ---Anchorage AK </option>
  <option value="US-AK:745"> ---Fairbanks AK </option>
  <option value="US-AK:747"> ---Juneau AK </option>
  <option value="US-AZ"> Arizona AZ </option>
  <option value="US-AZ:753"> ---Phoenix AZ </option>
  <option value="US-AZ:789"> ---Tucson (Sierra Vista) AZ </option>
  <option value="US-AZ:771"> ---Yuma AZ-El Centro CA </option>
  <option value="US-AR"> Arkansas AR </option> 
  <option value="US-AR:670"> ---Ft. Smith-Fayetteville-Springdale-Rogers AR </option>
  <option value="US-AR:734"> ---Jonesboro AR </option>
  <option value="US-AR:693"> ---Little Rock-Pine Bluff AR </option>
  <option value="US-AR:628"> ---Monroe LA-El Dorado AR </option>
  <option value="US-CA"> California CA </option>
  <option value="US-CA:800"> ---Bakersfield CA </option>
  <option value="US-CA:868"> ---Chico-Redding CA </option>
  <option value="US-CA:802"> ---Eureka CA </option>
  <option value="US-CA:866"> ---Fresno-Visalia CA </option>
  <option value="US-CA:803"> ---Los Angeles CA </option>
  <option value="US-CA:828"> ---Monterey-Salinas CA </option>
  <option value="US-CA:804"> ---Palm Springs CA </option>
  <option value="US-CA:862"> ---Sacramento-Stockton-Modesto CA </option>
  <option value="US-CA:825"> ---San Diego CA </option>
  <option value="US-CA:807"> ---San Francisco-Oakland-San Jose CA </option>
  <option value="US-CA:855"> ---Santa Barbara-Santa Maria-San Luis Obispo CA </option> 
  <option value="US-CA:771"> ---Yuma AZ-El Centro CA </option>
  <option value="US-CO"> Colorado CO </option>
<option value="US-CO:752"> ---Colorado Springs-Pueblo CO </option>
<option value="US-CO:751"> ---Denver CO </option>
<option value="US-CO:773"> ---Grand Junction-Montrose CO </option>
<option value="US-CT"> Connecticut CT </option>
<option value="US-CT:533"> ---Hartford &amp; New Haven CT </option>
<option value="US-DE"> Delaware DE </option>
<option value="US-DC"> District of Columbia DC </option>
<option value="US-DC:511"> ---Washington DC (Hagerstown MD) </option>
<option value="US-FL"> Florida FL </option>
<option value="US-FL:571"> ---Ft. Myers-Naples FL </option>
<option value="US-FL:592"> ---Gainesville FL </option>
<option value="US-FL:561"> ---Jacksonville FL </option>
<option value="US-FL:528"> ---Miami-Ft. Lauderdale FL </option>
<option value="US-FL:686"> ---Mobile AL-Pensacola (Ft. Walton Beach) FL </option>
<option value="US-FL:534"> ---Orlando-Daytona Beach-Melbourne FL </option>
<option value="US-FL:656"> ---Panama City FL </option>
<option value="US-FL:530"> ---Tallahassee FL-Thomasville GA </option>
<option value="US-FL:539"> ---Tampa-St. Petersburg (Sarasota) FL </option>
<option value="US-FL:548"> ---West Palm Beach-Ft. Pierce FL </option>
<option value="US-GA"> Georgia GA </option>
<option value="US-GA:525"> ---Albany GA </option>
<option value="US-GA:524"> ---Atlanta GA </option>
<option value="US-GA:520"> ---Augusta GA </option>
<option value="US-GA:522"> ---Columbus GA </option>
<option value="US-GA:503"> ---Macon GA </option>
<option value="US-GA:507"> ---Savannah GA </option>
<option value="US-GA:530"> ---Tallahassee FL-Thomasville GA </option>
<option value="US-HI"> Hawaii HI </option>
<option value="US-HI:744"> ---Honolulu HI </option>
<option value="US-ID"> Idaho ID </option>
<option value="US-ID:757"> ---Boise ID </option>
<option value="US-ID:758"> ---Idaho Falls-Pocatello ID </option>
<option value="US-ID:760"> ---Twin Falls ID </option>
<option value="US-IL"> Illinois IL </option>
<option value="US-IL:648"> ---Champaign &amp; Springfield-Decatur,IL </option>
<option value="US-IL:602"> ---Chicago IL </option>
<option value="US-IL:682"> ---Davenport IA-Rock Island-Moline IL </option>
<option value="US-IL:632"> ---Paducah KY-Cape Girardeau MO-Harrisburg-Mount Vernon IL </option>
<option value="US-IL:675"> ---Peoria-Bloomington IL </option>
<option value="US-IL:717"> ---Quincy IL-Hannibal MO-Keokuk IA </option>
<option value="US-IL:610"> ---Rockford IL </option>
<option value="US-IN"> Indiana IN </option>
<option value="US-IN:649"> ---Evansville IN </option>
<option value="US-IN:509"> ---Ft. Wayne IN </option>
<option value="US-IN:527"> ---Indianapolis IN </option>
<option value="US-IN:642"> ---Lafayette IN </option>
<option value="US-IN:588"> ---South Bend-Elkhart IN </option>
<option value="US-IN:581"> ---Terre Haute IN </option>
<option value="US-IA"> Iowa IA </option>
<option value="US-IA:637"> ---Cedar Rapids-Waterloo-Iowa City &amp; Dubuque IA </option>
<option value="US-IA:682"> ---Davenport IA-Rock Island-Moline IL </option>
<option value="US-IA:679"> ---Des Moines-Ames IA </option>
<option value="US-IA:631"> ---Ottumwa IA-Kirksville MO </option>
<option value="US-IA:717"> ---Quincy IL-Hannibal MO-Keokuk IA </option>
<option value="US-IA:611"> ---Rochester MN-Mason City IA-Austin MN </option>
<option value="US-IA:624"> ---Sioux City IA </option>
<option value="US-KS"> Kansas KS </option>
<option value="US-KS:603"> ---Joplin MO-Pittsburg KS </option>
<option value="US-KS:605"> ---Topeka KS </option>
<option value="US-KS:678"> ---Wichita-Hutchinson KS </option>
<option value="US-KY"> Kentucky KY </option>
<option value="US-KY:736"> ---Bowling Green KY </option>
<option value="US-KY:541"> ---Lexington KY </option>
<option value="US-KY:529"> ---Louisville KY </option>
<option value="US-KY:632"> ---Paducah KY-Cape Girardeau MO-Harrisburg-Mount Vernon IL </option>
<option value="US-KY:531"> ---Tri-Cities TN-VA </option>
<option value="US-LA"> Louisiana LA </option>
<option value="US-LA:644"> ---Alexandria LA </option>
<option value="US-LA:716"> ---Baton Rouge LA </option>
<option value="US-LA:582"> ---Lafayette LA </option>
<option value="US-LA:643"> ---Lake Charles LA </option>
<option value="US-LA:628"> ---Monroe LA-El Dorado AR </option>
<option value="US-LA:622"> ---New Orleans LA </option>
<option value="US-LA:612"> ---Shreveport LA </option>
<option value="US-ME"> Maine ME </option>
<option value="US-ME:537"> ---Bangor ME </option>
<option value="US-ME:500"> ---Portland-Auburn ME </option>
<option value="US-ME:552"> ---Presque Isle ME </option>
<option value="US-MD"> Maryland MD </option>
<option value="US-MD:512"> ---Baltimore MD </option>
<option value="US-MD:576"> ---Salisbury MD </option>
<option value="US-MD:511"> ---Washington DC (Hagerstown MD) </option>
<option value="US-MA"> Massachusetts MA </option>
<option value="US-MA:506"> ---Boston MA-Manchester NH </option>
<option value="US-MA:521"> ---Providence RI-New Bedford MA </option>
<option value="US-MA:543"> ---Springfield-Holyoke MA </option>
<option value="US-MI"> Michigan MI </option>
<option value="US-MI:583"> ---Alpena MI </option>
<option value="US-MI:505"> ---Detroit MI </option>
<option value="US-MI:513"> ---Flint-Saginaw-Bay City MI </option>
<option value="US-MI:563"> ---Grand Rapids-Kalamazoo-Battle Creek MI </option>
<option value="US-MI:551"> ---Lansing MI </option>
<option value="US-MI:553"> ---Marquette MI </option>
<option value="US-MI:540"> ---Traverse City-Cadillac MI </option>
<option value="US-MN"> Minnesota MN </option>
<option value="US-MN:676"> ---Duluth MN-Superior WI </option>
<option value="US-MN:737"> ---Mankato MN </option>
<option value="US-MN:613"> ---Minneapolis-St. Paul MN </option>
<option value="US-MN:611"> ---Rochester MN-Mason City IA-Austin MN </option>
<option value="US-MS"> Mississippi MS </option>
<option value="US-MS:746"> ---Biloxi-Gulfport MS </option>
<option value="US-MS:673"> ---Columbus-Tupelo-West Point MS </option>
<option value="US-MS:647"> ---Greenwood-Greenville MS </option>
<option value="US-MS:710"> ---Hattiesburg-Laurel MS </option>
<option value="US-MS:639"> ---Jackson MS </option>
<option value="US-MS:711"> ---Meridian MS </option>
<option value="US-MO"> Missouri MO </option>
<option value="US-MO:604"> ---Columbia-Jefferson City MO </option>
<option value="US-MO:603"> ---Joplin MO-Pittsburg KS </option> 
<option value="US-MO:616"> ---Kansas City MO </option>
<option value="US-MO:631"> ---Ottumwa IA-Kirksville MO </option>
<option value="US-MO:632"> ---Paducah KY-Cape Girardeau MO-Harrisburg-Mount Vernon IL </option>
<option value="US-MO:717"> ---Quincy IL-Hannibal MO-Keokuk IA </option>
<option value="US-MO:619"> ---Springfield MO </option>
<option value="US-MO:638"> ---St. Joseph MO </option>
<option value="US-MO:609"> ---St. Louis MO </option>
<option value="US-MT"> Montana MT </option>
<option value="US-MT:756"> ---Billings MT </option>
<option value="US-MT:754"> ---Butte-Bozeman MT </option>
<option value="US-MT:798"> ---Glendive MT </option>
<option value="US-MT:755"> ---Great Falls MT </option>
<option value="US-MT:766"> ---Helena MT </option>
<option value="US-MT:762"> ---Missoula MT </option>
<option value="US-NE"> Nebraska NE </option>
<option value="US-NE:759"> ---Cheyenne WY-Scottsbluff NE </option>
<option value="US-NE:722"> ---Lincoln &amp; Hastings-Kearney NE </option>
<option value="US-NE:740"> ---North Platte NE </option>
<option value="US-NE:652"> ---Omaha NE </option>
<option value="US-NV"> Nevada NV </option>
<option value="US-NV:839"> ---Las Vegas NV </option>
<option value="US-NV:811"> ---Reno NV </option>
<option value="US-NH"> New Hampshire NH </option>
<option value="US-NH:506"> ---Boston MA-Manchester NH </option>
<option value="US-NJ"> New Jersey NJ </option>
<option value="US-NM"> New Mexico NM </option>
<option value="US-NM:790"> ---Albuquerque-Santa Fe NM </option>
<option value="US-NY:532"> ---Albany-Schenectady-Troy NY </option>
<option value="US-NY:502"> ---Binghamton NY </option>
<option value="US-NY:514"> ---Buffalo NY </option>
<option value="US-NY:523"> ---Burlington VT-Plattsburgh NY </option>
<option value="US-NY:565"> ---Elmira NY </option>
<option value="US-NY:501"> ---New York NY </option>
<option value="US-NY:538"> ---Rochester NY </option>
<option value="US-NY:555"> ---Syracuse NY </option>
<option value="US-NY:526"> ---Utica NY </option>
<option value="US-NY:549"> ---Watertown NY </option>
<option value="US-NC"> North Carolina NC </option>
<option value="US-NC:517"> ---Charlotte NC </option>
<option value="US-NC:518"> ---Greensboro-High Point-Winston Salem NC </option>
<option value="US-NC:545"> ---Greenville-New Bern-Washington NC </option>
<option value="US-NC:567"> ---Greenville-Spartanburg SC-Asheville NC-Anderson SC </option>
<option value="US-NC:560"> ---Raleigh-Durham (Fayetteville) NC </option>
<option value="US-NC:550"> ---Wilmington NC </option>
<option value="US-ND"> North Dakota ND </option>
<option value="US-ND:724"> ---Fargo-Valley City ND </option>
<option value="US-ND:687"> ---Minot-Bismarck-Dickinson(Williston) ND </option>
<option value="US-OH"> Ohio OH </option>
<option value="US-OH:515"> ---Cincinnati OH </option>
<option value="US-OH:510"> ---Cleveland-Akron (Canton) OH </option>
<option value="US-OH:535"> ---Columbus OH </option>
<option value="US-OH:542"> ---Dayton OH </option>
<option value="US-OH:558"> ---Lima OH </option>
<option value="US-OH:547"> ---Toledo OH </option>
<option value="US-OH:554"> ---Wheeling WV-Steubenville OH </option>
<option value="US-OH:536"> ---Youngstown OH </option>
<option value="US-OH:596"> ---Zanesville OH </option>
<option value="US-OK"> Oklahoma OK </option>
<option value="US-OK:650"> ---Oklahoma City OK </option>
<option value="US-OK:657"> ---Sherman TX-Ada OK </option>
<option value="US-OK:671"> ---Tulsa OK </option>
<option value="US-OK:627"> ---Wichita Falls TX &amp; Lawton OK </option>
<option value="US-OR"> Oregon OR </option>
<option value="US-OR:821"> ---Bend OR </option>
<option value="US-OR:801"> ---Eugene OR </option>
<option value="US-OR:813"> ---Medford-Klamath Falls OR </option>
<option value="US-OR:820"> ---Portland OR </option>
<option value="US-PA"> Pennsylvania PA </option>
<option value="US-PA:516"> ---Erie PA </option>
<option value="US-PA:566"> ---Harrisburg-Lancaster-Lebanon-York PA </option>
<option value="US-PA:574"> ---Johnstown-Altoona PA </option>
<option value="US-PA:504"> ---Philadelphia PA </option>
<option value="US-PA:508"> ---Pittsburgh PA </option>
<option value="US-PA:577"> ---Wilkes Barre-Scranton PA </option>
<option value="US-RI"> Rhode Island RI </option>
<option value="US-RI:521"> ---Providence RI-New Bedford MA </option>
<option value="US-SC"> South Carolina SC </option>
<option value="US-SC:519"> ---Charleston SC </option>
<option value="US-SC:546"> ---Columbia SC </option>
<option value="US-SC:570"> ---Florence-Myrtle Beach SC </option>
<option value="US-SC:567"> ---Greenville-Spartanburg SC-Asheville NC-Anderson SC </option>
<option value="US-SD"> South Dakota SD </option>
<option value="US-SD:764"> ---Rapid City SD </option>
<option value="US-SD:725"> ---Sioux Falls(Mitchell) SD </option>
<option value="US-TN"> Tennessee TN </option>
<option value="US-TN:575"> ---Chattanooga TN </option>
<option value="US-TN:718"> ---Jackson TN </option>
<option value="US-TN:557"> ---Knoxville TN </option>
<option value="US-TN:640"> ---Memphis TN </option>
<option value="US-TN:659"> ---Nashville TN </option>
<option value="US-TN:531"> ---Tri-Cities TN-VA </option>
<option value="US-TX"> Texas TX </option>
<option value="US-TX:662"> ---Abilene-Sweetwater TX </option>
<option value="US-TX:634"> ---Amarillo TX </option>
<option value="US-TX:635"> ---Austin TX </option>
<option value="US-TX:692"> ---Beaumont-Port Arthur TX </option>
<option value="US-TX:600"> ---Corpus Christi TX </option>
<option value="US-TX:623"> ---Dallas-Ft. Worth TX </option>
<option value="US-TX:765"> ---El Paso TX </option>
<option value="US-TX:636"> ---Harlingen-Weslaco-Brownsville-McAllen TX </option>
<option value="US-TX:618"> ---Houston TX </option>
<option value="US-TX:749"> ---Laredo TX </option>
<option value="US-TX:651"> ---Lubbock TX </option>
<option value="US-TX:633"> ---Odessa-Midland TX </option>
<option value="US-TX:661"> ---San Angelo TX </option>
<option value="US-TX:641"> ---San Antonio TX </option>
<option value="US-TX:657"> ---Sherman TX-Ada OK </option>
<option value="US-TX:709"> ---Tyler-Longview(Lufkin &amp; Nacogdoches) TX </option>
<option value="US-TX:626"> ---Victoria TX </option>
<option value="US-TX:625"> ---Waco-Temple-Bryan TX </option>
<option value="US-TX:627"> ---Wichita Falls TX &amp; Lawton OK </option>
<option value="US-UT"> Utah UT </option>
<option value="US-UT:770"> ---Salt Lake City UT </option>
<option value="US-VT"> Vermont VT </option>
<option value="US-VT:523"> ---Burlington VT-Plattsburgh NY </option>
<option value="US-VA"> Virginia VA </option>
<option value="US-VA:584"> ---Charlottesville VA </option>
<option value="US-VA:569"> ---Harrisonburg VA </option>
<option value="US-VA:544"> ---Norfolk-Portsmouth-Newport News VA </option>
<option value="US-VA:556"> ---Richmond-Petersburg VA </option>
<option value="US-VA:573"> ---Roanoke-Lynchburg VA </option>
<option value="US-VA:531"> ---Tri-Cities TN-VA </option>
<option value="US-WA"> Washington WA </option>
<option value="US-WA:819"> ---Seattle-Tacoma WA </option>
<option value="US-WA:881"> ---Spokane WA </option>
<option value="US-WA:810"> ---Yakima-Pasco-Richland-Kennewick WA </option>
<option value="US-WV"> West Virginia WV </option>
<option value="US-WV:559"> ---Bluefield-Beckley-Oak Hill WV </option>
<option value="US-WV:564"> ---Charleston-Huntington WV </option>
<option value="US-WV:598"> ---Clarksburg-Weston WV </option>
<option value="US-WV:597"> ---Parkersburg WV </option>
<option value="US-WV:554"> ---Wheeling WV-Steubenville OH </option>
<option value="US-WI"> Wisconsin WI </option>
<option value="US-WI:676"> ---Duluth MN-Superior WI </option>
<option value="US-WI:658"> ---Green Bay-Appleton WI </option>
<option value="US-WI:702"> ---La Crosse-Eau Claire WI </option>
<option value="US-WI:669"> ---Madison WI </option>
<option value="US-WI:617"> ---Milwaukee WI </option>
<option value="US-WI:705"> ---Wausau-Rhinelander WI </option>
<option value="US-WY"> Wyoming WY </option>
<option value="US-WY:767"> ---Casper-Riverton WY </option>
<option value="US-WY:759"> ---Cheyenne WY-Scottsbluff NE </option>
</select><br>
			  <font style="font-size:9px;"> Hold down the <b>control</b> key to select multiple territories.</font> 
</div>
</div>
<input type="submit" name="changecountry" value="Change" id="changeCountryBtnId" style="font:8pt verdana; width:55px;">
</form>
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
<span style="background:#FFFF00; width:100px;"><?=$campaignStartdate?></span></td>
          <td>End Date: <br>
<input name="" type="text" value="<?=$campaignEnddate?>" size="6" style="font-size:8pt;"><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="popUpCalendar($('date_from'),$('date_from'),'mm-dd-yyyy',document);" style="cursor:pointer">
</td>
        </tr>
      </table></td>
    </tr>
  <tr>
    <td colspan="4" valign="top">Ad Time Zone: <select name="timezone" style="font:8pt; verdana;"><option value="-12">GMT - 12 Hours</option>
<option value="-11">GMT - 11 Hours</option>
<option value="-10">GMT - 10 Hours</option>
<option value="-9">GMT - 9 Hours</option>
<option value="-8">GMT - 8 Hours</option>
<option value="-7">GMT - 7 Hours</option>
<option value="-6">GMT - 6 Hours</option>
<option value="-5" selected="selected">GMT - 5 Hours (Eastern Standard)</option>
<option value="-4">GMT - 4 Hours</option>
<option value="-3.5">GMT - 3.5 Hours</option>
<option value="-3">GMT - 3 Hours</option>
<option value="-2">GMT - 2 Hours</option>
<option value="-1">GMT - 1 Hours</option>
<option value="0" >GMT</option>
<option value="1">GMT + 1 Hour</option>
<option value="2">GMT + 2 Hours</option>
<option value="3">GMT + 3 Hours</option>
<option value="3.5">GMT + 3.5 Hours</option>
<option value="4">GMT + 4 Hours</option>
<option value="4.5">GMT + 4.5 Hours</option>
<option value="5">GMT + 5 Hours</option>
<option value="5.5">GMT + 5.5 Hours</option>
<option value="6">GMT + 6 Hours</option>
<option value="6.5">GMT + 6.5 Hours</option>
<option value="7">GMT + 7 Hours</option>
<option value="8">GMT + 8 Hours</option>
<option value="9">GMT + 9 Hours</option>
<option value="9.5">GMT + 9.5 Hours</option>
<option value="10">GMT + 10 Hours</option>
<option value="11">GMT + 11 Hours</option>
<option value="12">GMT + 12 Hours</option>
<option value="13">GMT + 13 Hours</option></select> <span class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName></font><b>Ad Scheduling</b><br><br>By enabling ad scheduling in your campaign, you can choose which days in the week and which hours in the day that you\'d like your ads to run.<br>')" onMouseout="hideddrivetip()"> </span>
<br>
<font style="color:red; font-size:9px;">(This is NOT Geo-targeting - For Ad scheduling only)</font></td>
    </tr>
  <tr>
    <td valign="top"></td>
    <td><b>Time On</b></td>
    <td width="5" rowspan="8">&nbsp;</td>
    <td><b>Time Off</b></td>
  </tr>
  
  <tr>
    <td valign="top" nowrap style="padding-right:5px;"><label>
      <input name="checkbox" type="checkbox" value="checkbox" checked>
    Sunday</label></td>
    <td height="19" nowrap>Hour: <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
    </select></td>
    <td nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
  </tr>
  <tr>
    <td valign="top" style="padding-right:5px;"><label>
    <input name="checkbox" type="checkbox" value="checkbox" checked> 
    Monday
</label></td>
    <td height="19" nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
    <td nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
  </tr>
  <tr>
    <td valign="top" style="padding-right:5px;"><label>
    <input name="checkbox" type="checkbox" value="checkbox" checked> 
    Tuesday
</label></td>
    <td height="19" nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
    <td nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
  </tr>
  <tr>
    <td valign="top" style="padding-right:5px;"><label>
    <input name="checkbox" type="checkbox" value="checkbox" checked> 
    Wednesday
</label></td>
    <td height="19" nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
    <td nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
  </tr>
  <tr>
    <td valign="top" style="padding-right:5px;"><label>
    <input name="checkbox" type="checkbox" value="checkbox" checked> 
    Thursday
</label></td>
    <td height="19" nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
    <td nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>

        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
  </tr>
  <tr>
    <td valign="top" style="padding-right:5px;"><label>
    <input name="checkbox" type="checkbox" value="checkbox" checked> 
    Friday
</label></td>
    <td height="19" nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
    <td nowrap>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
  </tr>
  <tr>
    <td valign="top" style="padding-right:5px;"><label>
    <input name="checkbox" type="checkbox" value="checkbox" checked> 
    Saturday
</label></td>
    <td height="11">Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
    <td>Hour:
      <select id="hour" name="hour" style="font:8pt; verdana;">
        <option value="0" selected="selected">00:00 / 12:00 am</option>
        <option value="1">01:00 / 1:00 am</option>
        <option value="2">02:00 / 2:00 am</option>
        <option value="3">03:00 / 3:00 am</option>
        <option value="4">04:00 / 4:00 am</option>
        <option value="5">05:00 / 5:00 am</option>
        <option value="6">06:00 / 6:00 am</option>
        <option value="7">07:00 / 7:00 am</option>
        <option value="8">08:00 / 8:00 am</option>
        <option value="9">09:00 / 9:00 am</option>
        <option value="10">10:00 / 10:00 am</option>
        <option value="11">11:00 / 11:00 am</option>
        <option value="12">12:00 / 12:00 pm</option>
        <option value="13">13:00 / 1:00 pm</option>
        <option value="14">14:00 / 2:00 pm</option>
        <option value="15">15:00 / 3:00 pm</option>
        <option value="16">16:00 / 4:00 pm</option>
        <option value="17">17:00 / 5:00 pm</option>
        <option value="18">18:00 / 6:00 pm</option>
        <option value="19">19:00 / 7:00 pm</option>
        <option value="20">20:00 / 8:00 pm</option>
        <option value="21">21:00 / 9:00 pm</option>
        <option value="22">22:00 / 10:00 pm</option>
        <option value="23">23:00 / 11:00 pm</option>
      </select></td>
  </tr>
</table>
</div>

</div>
</td></tr>
</table>

  <table width="555">
<tr><td valign="top">
<iframe id="myframe" name="contentiframe" src="product_ads.php?engine=adwords&campaign=view_all=1" width="555" marginwidth="0" marginheight="0" allowtransparency="true" frameborder="0" scrolling="no"></iframe></td></tr>
</table>
<div id="view_ad_<?=//$aid?>"></div>
  </div>
</div>
</div>
<? } ?>

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
</td></tr>
</table>

</body>
</html>
