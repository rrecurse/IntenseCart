//Input the IDs of the IFRAMES you wish to dynamically resize to match its content height:
//Separate each ID with a comma. Examples: ["myframe1", "myframe2"] or ["myframe"] or [] for none:
var iframeids=["myframe"]

//Should script hide iframe from browsers that don't support this script (non IE5+/NS6+ browsers. Recommended):
var iframehide="yes"

var getFFVersion=navigator.userAgent.substring(navigator.userAgent.indexOf("Firefox")).split("/")[1]
var FFextraHeight=parseFloat(getFFVersion)>=0.1? 16 : 0 //extra height in px to add to iframe in FireFox 1.0+ browsers

function resizeCaller() {

	var dyniframe = new Array()

	for (i=0; i < iframeids.length; i++){

		if (document.getElementById) resizeIframe(iframeids[i])
	
		// # reveal iframe for lower end browsers? (see var above):
		if ((document.all || document.getElementById) && iframehide=="no"){
			var tempobj=document.all? document.all[iframeids[i]] : document.getElementById(iframeids[i])
			tempobj.style.display="block";
		}
	}
}

function resizeIframe(frameid){

	var currentfr = document.getElementById(frameid)

	if (currentfr && !window.opera){

		currentfr.style.display="block"

		var ht;

		if (currentfr.contentDocument && currentfr.contentDocument.body.offsetHeight) //ns6 syntax
			ht = currentfr.contentDocument.body.offsetHeight+FFextraHeight; 
		else if (currentfr.Document && currentfr.Document.body.scrollHeight) //ie5+ syntax
			ht = currentfr.Document.body.scrollHeight;
			currentfr.height=ht;
			//alert(ht);
			if (currentfr.addEventListener)
			currentfr.addEventListener("load", readjustIframe, false)
		else if (currentfr.attachEvent){
			currentfr.detachEvent("onload", readjustIframe) // Bug fix line
			currentfr.attachEvent("onload", readjustIframe)
		}
	}
}

function readjustIframe(loadevt) {
var crossevt=(window.event)? event : loadevt
var iframeroot=(crossevt.currentTarget)? crossevt.currentTarget : crossevt.srcElement
if (iframeroot) {
  resizeIframe(iframeroot.id);
  var src;
  if (iframeroot.contentDocument) src=iframeroot.contentDocument.location;
  else if (iframeroot.Document) src=iframeroot.Document.location;
  if (src) document.cookie="iframe_src_"+iframeroot.id+"="+src;
}
}

function loadintoIframe(iframeid, url){
  if (!url.match(/^\w+:/)) url=document.location.toString().replace(/[^\/]*$/,url);
  if (document.getElementById)
    document.getElementById(iframeid).src=url
}

if (window.addEventListener)
window.addEventListener("load", resizeCaller, false)
else if (window.attachEvent)
window.attachEvent("onload", resizeCaller)
else
window.onload=resizeCaller
