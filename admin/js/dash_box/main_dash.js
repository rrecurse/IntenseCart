
	var noDefaultBreadcrumb = 1;

	var trafficSources = Array('google','yahoo','bing','aol','direct');

	//var trafficSources = Array('google','yahoo','bing','aol','other');

	var trafficPieColors = Array('ff4040','ff40ff','4040ff','40ffff','40ff40');

	var trafficPieUrl = 'pie_chart.php?width=105&height=105&bgcolor=f0f5fb&border=808080&data=';

	//var salesSections = Array('retail','corp','vendor','amazon','affiliate','banner','email','product_giftcert','total','previous');
	var salesSections = Array('retail','vendor','amazon','affiliate','banner','email','product_giftcert','total','previous');

	var ppcSources = Array('google-ppc','shopping.com','pricegrabber','amazon.com','ebay');

	var statsList = Array(
				    { interval:300, stats:{ traffic:Array('today','thisweek','thismonth') }},
				    { interval:1800, stats:{ traffic:Array('yesterday','lastweek','lastmonth') }},
				    { interval:3600, stats:{ ppc:Array('yesterday&ppc_channels=google-ppc,shopping.com,pricegrabber,amazon.com,ebay') }},
				    { interval:300, stats:{ sales:Array('today','thisweek','thismonth','thisyear') }},
				    { interval:1800, stats:{ sales:Array('yesterday','lastweek','lastmonth','lastyear') }}
				  );


function setFocus(focusme) {

  var layer = document.getElementById(focusme);
  var focusIt = layer.getElementsByTagName('a')[0];//This is an Array, get the first link.
  focusIt.focus();
}

setBreadcrumb(Array({title:'Executive Dashboard',link:document.location}));

var trafficPieCurrentUrl;


function setBox(id,val) {
  var box=$(id);
  if (!box) return false;
  box.innerHTML=val;
  return true;
}


function statsFeed(req) {
  var data = parseXMLFeed(req);
//alert(JSON.stringify(data, null, 4));
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
    	var total = {};

		for (var sub in subs) total[sub] = 0;

		for(var si=0;sec=sections[si];si++) {
			var displ = addall;

			for (var sub in subs) {
				var val = (data[range][sec] && data[range][sec][sub]) ? data[range][sec][sub] : subs[sub];

				if (setBox(prefix+range+'_'+sec+'_'+sub,val)) displ=true;
			}

			if (displ && data[range][sec] && !data[range].total) {
				for (var sub in subs) {
					var val = Number(data[range][sec][sub]);
					if (!isNaN(val)) total[sub]+=val;
				}
			}

			if (data[range]['percent_change']) {
				var p = String(data[range]['percent_change']);
				var c = null;
		
				if (p.match(/^\+/)) { // # if + sign found, color green
					c = '#00BF00'; 
				} else if (p.match(/^-\d/)) { // # if - sign found, color green
					c = '#FF0000';
				}

				setBox(prefix+range+'_percent_change',(c ? '<span style="color:'+c+'">'+p+'</span>':p));
			}
    	}

		if (!data[range].total) {
			for (var sub in subs) {
				setBox(prefix+range+'_total_'+sub,total[sub]);
			}
		}
	}
}


function trafficFeed(data) {
  populateFields('traffic_',trafficSources,{count:0},data);

//alert(JSON.stringify(data.yesterday, null, 4));

  if (data.thismonth) {
    var source;
    var total=0;
    for(var source in data.thismonth) if (!isNaN(data.thismonth[source].count)) total+=Number(data.thismonth[source].count);
    var pie=new Array();
    for(var si=0;source=trafficSources[si];si++) {
      var box=$('traffic_thismonth_'+source+'_percent');
      var val = (data.thismonth[source] ? Number(data.thismonth[source].count).toFixed(1) * 100 / total : 0);
      if (isNaN(val)) val=0;
      if (box) box.innerHTML=val.toFixed(2);
      if (val) pie[pie.length] = trafficPieColors[si]+':'+val.toFixed(2);
    }
    var img=$('traffic_pie');
    if (img) {
      pieurl = trafficPieUrl+pie.join(',');
      if (trafficPieCurrentUrl != pieurl) trafficPieCurrentUrl = img.src = pieurl;
    }
  }
}
function salesFeed(data) {
  populateFields('sales_',salesSections,{count:0,amount:0},data);
}
function ppcFeed(data) {
  for (var range in data) {
    var sec;
/*
    var total;
    for (sec in data[range]) {
      if (!total) total={clicks:0,conv:0,cost:0.0};
      for (var t in total) total[t]+=Number(data[range][sec][t]);
    }
    data[range].total=total;
*/
    for (sec in data[range]) {
      var s=data[range][sec];
      s.clickcost=Number(s.clicks)?Number(s.cost/s.clicks).toFixed(2):'-';
      s.convcost=Number(s.convs)?Number(s.cost/s.convs).toFixed(2):'-';
      s.convrate=Number(s.convs)?Number(s.convs/s.clicks*100).toFixed(1):'-';
    }
  }
  populateFields('ppc_',ppcSources,{clicks:'n/a',convs:'n/a',cost:'n/a',convrate:'n/a',convcost:'n/a',clickcost:'n/a'},data);
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
//alert(JSON.stringify(refr, null, 4));
  statsRequest(refr);
  setTimeout("refreshData()",statsCheckInterval*1000);
}
refreshData();
