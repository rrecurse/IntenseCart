var bustcachevar=0 //bust potential caching of external pages after initial request? (1=yes, 0=no)
var loadstatustext="<br><br><br><div align=center> <b>Requesting content...</b><br><img src='../images/loading_bar.gif' /></div><br><br>"

////NO NEED TO EDIT BELOW////////////////////////
var loadedobjects=""
var defaultcontentarray=new Object()
var bustcacheparameter=""

function ajaxpage(url, containerid, targetobj){
var page_request = false
if (window.XMLHttpRequest) // if Mozilla, Safari etc
page_request = new XMLHttpRequest()
else if (window.ActiveXObject){ // if IE
try {
page_request = new ActiveXObject("Msxml2.XMLHTTP")
} 
catch (e){
try{
page_request = new ActiveXObject("Microsoft.XMLHTTP")
}
catch (e){}
}
}
else
return false
var ullist=targetobj.parentNode.parentNode.getElementsByTagName("li")
for (var i=0; i<ullist.length; i++)
ullist[i].className=""  //deselect all tabs
targetobj.parentNode.className="selected"  //highlight currently clicked on tab
if (url.indexOf("#default")!=-1){ //if simply show default content within container (verus fetch it via ajax)
document.getElementById(containerid).innerHTML=defaultcontentarray[containerid]
if (window.contentChanged) window.contentChanged()
return true;
}
document.getElementById(containerid).innerHTML=loadstatustext
if (window.contentChanged) window.contentChanged()
page_request.onreadystatechange=function(){
loadpage(page_request, containerid)
}
if (bustcachevar) //if bust caching of external page
bustcacheparameter=(url.indexOf("?")!=-1)? "&"+new Date().getTime() : "?"+new Date().getTime()
page_request.open('GET', url+bustcacheparameter, true)
page_request.send(null)
}

function loadpage(page_request, containerid){
 if (page_request.readyState == 4 && (page_request.status==200 || window.location.href.indexOf("http")==-1))
 {
  var html=page_request.responseText.replace(/<script[^\0]*?<\/script.*?>/ig,'');
  document.getElementById(containerid).innerHTML=html;
  var scripts=page_request.responseText.match(/<script[^\0]*?<\/script.*?>/ig);
  if (scripts) for (var i=0;scripts[i];i++) {
//    alert(scripts[i]);
    var parse=scripts[i].match(/<script[^>]*?\ssrc="?([^\s">]+)/i);
    if (parse) {
//      var inc=parse[1];
//      var js=document.createElement('script');
//      js.setAttribute('language','javascript');
//      js.setAttribute('type','text/javascript');
//      js.setAttribute('src',inc);
//      var r=document.getElementsByTagName('head').item(0).appendChild(js);
//      alert('include: '+inc+': '+r);
    } else {
      var script=scripts[i].match(/<script.*?>([^\0]*?)<\/script/i)[1];
      var r=eval(script);
//      alert('eval: '+script+': '+r);
    }
  }
//  document.getElementById(containerid).innerHTML=page_request.responseText
  if (window.contentChanged) window.contentChanged()
  return
 }
}

function loadobjs(revattribute){
if (revattribute!=null && revattribute!=""){ //if "rev" attribute is defined (load external .js or .css files)
var objectlist=revattribute.split(/\s*,\s*/) //split the files and store as array
for (var i=0; i<objectlist.length; i++){
var file=objectlist[i]
var fileref=""
if (loadedobjects.indexOf(file)==-1){ //Check to see if this object has not already been added to page before proceeding
if (file.indexOf(".js")!=-1){ //If object is a js file
fileref=document.createElement('script')
fileref.setAttribute("type","text/javascript");
fileref.setAttribute("src", file);
}
else if (file.indexOf(".css")!=-1){ //If object is a css file
fileref=document.createElement("link")
fileref.setAttribute("rel", "stylesheet");
fileref.setAttribute("type", "text/css");
fileref.setAttribute("href", file);
}
}
if (fileref!=""){
document.getElementsByTagName("head").item(0).appendChild(fileref)
loadedobjects+=file+" " //Remember this object as being already added to page
}
}
}
}

function savedefaultcontent(contentid){// save default ajax tab content
if (typeof defaultcontentarray[contentid]=="undefined") //if default content hasn't already been saved
defaultcontentarray[contentid]=document.getElementById(contentid).innerHTML
}

function startajaxtabs(){
for (var i=0; i<arguments.length; i++){ //loop through passed UL ids
var ulobj=document.getElementById(arguments[i])
var ulist=ulobj.getElementsByTagName("li") //array containing the LI elements within UL
for (var x=0; x<ulist.length; x++){ //loop through each LI element
var ulistlink=ulist[x].getElementsByTagName("a")[0]
if (ulistlink.getAttribute("rel")){
var modifiedurl=ulistlink.getAttribute("href").replace(/^http:\/\/[^\/]+\//i, "http://"+window.location.hostname+"/")
ulistlink.setAttribute("href", modifiedurl) //replace URL's root domain with dynamic root domain, for ajax security sake
savedefaultcontent(ulistlink.getAttribute("rel")) //save default ajax tab content
ulistlink.onclick=function(){
ajaxpage(this.getAttribute("href"), this.getAttribute("rel"), this)
loadobjs(this.getAttribute("rev"))
return false
}
if (ulist[x].className=="selected"){
ajaxpage(ulistlink.getAttribute("href"), ulistlink.getAttribute("rel"), ulistlink) //auto load currenly selected tab content
loadobjs(ulistlink.getAttribute("rev")) //auto load any accompanying .js and .css files
}
}
}
}
}