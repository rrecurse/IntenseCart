<?
  require('includes/application_top.php');
  require(DIR_FS_ADMIN.'apility/apility.php');

?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Executive Dashboard</title>
<link rel="STYLESHEET" href="js/css.css" type="text/css">
<script type="text/javascript" src="js/tips.js"></script>
<script type="text/javascript" src="js/iframe.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/xmlfeed.js"></script>
<script language="JavaScript">
  var noDefaultBreadcrumb=1;

  var trafficSources=Array('google','yahoo','msn','aol','other');
  var trafficPieColors=Array('ff4040','ff40ff','4040ff','40ffff','40ff40');
  var trafficPieUrl='pie_chart.php?width=95&height=95&bgcolor=f0f5fb&border=808080&data=';

  var salesSections=Array('direct','ebay','affiliate');

  var ppcSources=Array('adwords','overture','other','total');

  var statsList=Array(
    { interval:300, stats:{ traffic:Array('today','thisweek','thismonth') }},
    { interval:1800, stats:{ traffic:Array('yesterday','lastweek','lastmonth') }},
//    { interval:3600, stats:{ ppc:Array('yesterday') }},
    { interval:300, stats:{ sales:Array('today','thisweek','thismonth') }},
    { interval:1800, stats:{ sales:Array('yesterday','lastweek','lastmonth') }}
  );
  
</script>

<script language="javascript" type="text/javascript">
function setFocus(focusme) {
  var layer = document.getElementById(focusme);
  var focusIt = layer.getElementsByTagName('a')[0];//This is an array, get the first link.
  focusIt.focus();
}
</script>

</head>
<body style="background-color:transparent;" onLoad="setFocus('focusme');">
<? include(DIR_WS_INCLUDES.'header.php') ?>
<div style="overflow-x:hidden; width:571px;" id="focusme"><a id="focuschild" href="javascript:void(null)" style="cursor: default"></a><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td style="height:16px; background-color:#6295FD; font:bold 11px arial; color:#FFFFFF;">&nbsp; Dashboard Undergoing Maintenance ...</td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#19487E;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#FFFFFF;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4;"></td>
	      </tr>
                     <tr>
                       <td height="45" align="center" style="padding-top:3px;">&nbsp;Check back soon ...</td>
                     </tr>
					 <tr>
					 <td style="height:1px; background:url(images/dot-line.gif) repeat-x;"></td>
					 </tr>
					  <tr>
					    <td style="height:20px; padding-top:4px; padding-bottom:5px; padding-left:5px;">&nbsp;</td>
					  </tr>
      </table></td>
  </tr>
  <tr>
    <td style="padding-top:2px;">
</td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top">&nbsp;</td>
    <td style="width:5px;"></td>
    <td valign="top">&nbsp;</td>
  </tr>
</table>
</td>
  </tr>
  <tr>
  <td valign="top" style="height:1px; background-color:#FFFFFF;"></td>
  </tr>
  <tr>
  <td valign="top"><table width="571" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="285" valign="top">&nbsp;</td>
    <td width="2" style="width:5px;"></td>
    <td width="284" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top">&nbsp;</td>
    <td style="width:5px;"></td>
    <td valign="top">&nbsp;</td>
  </tr>
</table></td>
  </tr>
</table></div>
<script language="JavaScript">

setBreadcrumb(Array({title:'Executive Dashboard',link:document.location}));

var trafficPieCurrentUrl;

function setBox(id,val) {
  var box=$(id);
  if (!box) return false;
  box.innerHTML=val;
  return true;
}

function statsFeed(req) {
  var data=parseXMLFeed(req);
  if (!data) return;
  if (data.traffic) trafficFeed(data.traffic);
  if (data.sales) salesFeed(data.sales);
  if (data.ppc) ppcFeed(data.ppc);
}

function statsRequest(lst) {
  var feeds=new Array();
  for (var f in lst) feeds[feeds.length]=f+'='+(lst[f].join?lst[f].join(','):lst[f]);
  if (!feeds.length) return;
  new ajax('stats_feed.xml.php?'+feeds.join('&'), { onComplete: statsFeed });
}

function populateFields(prefix,sections,subs,data,addall) {
  for (var range in data) if (data[range]) {
    var sec;
    var total={};
    for (var sub in subs) total[sub]=0;
    for(var si=0;sec=sections[si];si++) {
      var displ=addall;
      for (var sub in subs) {
        var val=(data[range][sec] && data[range][sec][sub])?data[range][sec][sub]:subs[sub];
        if (setBox(prefix+range+'_'+sec+'_'+sub,val)) displ=true;
      }
      if (displ && data[range][sec] && !data[range].total) {
        for (var sub in subs) {
          var val=Number(data[range][sec][sub]);
          if (!isNaN(val)) total[sub]+=val;
	}
      }
    }
    if (!data[range].total) for (var sub in subs) setBox(prefix+range+'_total_'+sub,total[sub]);
  }
}

function trafficFeed(data) {
  populateFields('traffic_',trafficSources,{count:0},data);
  if (data.thismonth) {
    var source;
    var total=0;
    for(var source in data.thismonth) if (!isNaN(data.thismonth[source].count)) total+=Number(data.thismonth[source].count);
    var pie=new Array();
    for(var si=0;source=trafficSources[si];si++) {
      var box=$('traffic_thismonth_'+source+'_percent');
      var val=data.thismonth[source]?Number(data.thismonth[source].count)*100/total:0;
      if (isNaN(val)) val=0;
      if (box) box.innerHTML=val.toFixed(1);
      if (val) pie[pie.length]=trafficPieColors[si]+':'+Math.floor(val*10);
    }
    var img=$('traffic_pie');
    if (img) {
      pieurl=trafficPieUrl+pie.join(',');
      if (trafficPieCurrentUrl!=pieurl) trafficPieCurrentUrl=img.src=pieurl;
    }
  }
}

function salesFeed(data) {
  populateFields('sales_',salesSections,{count:0,amount:0},data);
}

function ppcFeed(data) {
  for (var range in data) {
    var sec;
    var total;
    for (sec in data[range]) {
      if (!total) total={clicks:0,conv:0,cost:0.0};
      for (var t in total) total[t]+=Number(data[range][sec][t]);
    }
    data[range].total=total;
    for (sec in data[range]) {
      var s=data[range][sec];
      s.clickcost=Number(s.clicks)?Number(s.cost/s.clicks).toFixed(2):'-';
      s.convcost=Number(s.conv)?Number(s.cost/s.conv).toFixed(2):'-';
      s.convrate=Number(s.clicks)?Number(s.conv/s.clicks*100).toFixed(1):'-';
    }
  }
  populateFields('ppc_',ppcSources,{clicks:'n/a',conv:'n/a',cost:'n/a',convrate:'n/a',convcost:'n/a',clickcost:'n/a'},data);
}


var statsCheckInterval=10;
var statsRefreshed=new Array();

function refreshData() {
  var refr={};
  var st;
  for(var i=0;st=statsList[i];i++) {
    var rf=false;
    if (statsRefreshed[i]==undefined) {
      rf=true;
      statsRefreshed[i]=st.interval;
    } else if (statsRefreshed[i]<=0) {
      rf=st.interval>0;
      statsRefreshed[i]+=st.interval;
    }
    statsRefreshed[i]-=statsCheckInterval;
    if (rf) {
      for (f in st.stats) {
        if (!refr[f]) refr[f]=new Array();
	for (var j=0;st.stats[f][j];j++) refr[f][refr[f].length]=st.stats[f][j];
      }
    }
  }
  statsRequest(refr);
  setTimeout("refreshData()",statsCheckInterval*1000);
}

refreshData();
</script>
</body>
</html>
