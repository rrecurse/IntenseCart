var tmr = new Array();
function mnu(obj,menu,show)
{
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
if(show) {
document.getElementById(menu).style.left=curleft;
document.getElementById(menu).style.top=curtop+26;
document.getElementById(menu).style.display="";
tmr[menu] = 1; 
}
else { setTimeout("hde('"+menu+"')", 100); tmr[menu]=0; }
}
function hde(menu) { 
if (!tmr[menu]) { document.getElementById(menu).style.display="none"; }
}
function gurl(d) { 
document.location.href=d; return false;
}
function ief(){
var linkid=document.getElementById('bm');if(!linkid){return false;};
linkid.href=document.location.href; linkid.title=document.title;
}
function ad(){
window.external.AddFavorite(document.location.href,document.title);
}
window.onload=ief;
function ss(nm){
var sfield = new Array("web search","discussion","directory","articles","news"); var f = "";
for (var loop = 0; loop <sfield.length; loop++)
{
var nmloop = sfield[loop];
 if (nm == nmloop) { f += " | "+nmloop; }
else { f += " | <a href=\"#\" onclick=\"ss('"+nmloop+"');\" style=\"color:#ffffff;\">"+nmloop+"</a>"; }
}
document.getElementById('search').innerHTML=f.substr(3,f.length-3); var bx=document.getElementById('box');
if (nm=="web search") { bx.innerHTML="<form method=\"get\" name=\"pgfrm\" action=\"http://www.google.com/search\"><input type=\"text\" name=\"q\" size=\"45\" maxlength=\"255\" value=\"\"> <input type=\"submit\" value=\"Google Search\"></input></form>"; document.pgfrm.q.focus(); }
else if (nm=="discussion") { bx.innerHTML="<form method=\"post\" name=\"pgfrm\" action=\"/talk/search.php?do=process\"><input type=\"text\" name=\"query\" size=\"40\" maxlength=\"255\" value=\"\"> <input type=\"submit\" value=\"Search\" name=\"dosearch\"></input> <input type=\"button\" onclick=\"document.location.href='/talk/search.php?query='+document.pgfrm.query.value;\" value=\"Advanced\"></form>"; document.pgfrm.query.focus(); }
else if (nm=="directory") { bx.innerHTML="<form method=\"get\" name=\"pgfrm\" action=\"/directory/\"><input type=\"text\" name=\"q\" size=\"34\" maxlength=\"255\" value=\"\"> <select size=\"1\" name=\"s\"> <option value=\"title\">Website Title</option> <option selected=\"selected\" value=\"description\">Website Description </option> <option value=\"address\">Website Address</option> <option value=\"author\">Website Author</option> <option value=\"categories\">Categories</option> </select>	<input type=\"submit\" value=\"Search\"></input></form>"; document.pgfrm.q.focus(); }
else if (nm=="articles") { bx.innerHTML="<form method=\"get\" name=\"pgfrm\" action=\"/articles/search/\"><input type=\"text\" name=\"q\" size=\"42\" maxlength=\"255\" value=\"\"> <select size=\"1\" name=\"t\"> <option value=\"title\">Title</option> <option selected=\"selected\" value=\"description\">Description</option> </select> <input type=\"submit\" value=\"Search\" name=\"dosearch\"></input></form>"; document.pgfrm.q.focus(); }
else if (nm=="news") { bx.innerHTML="<form method=\"get\" name=\"pgfrm\" action=\"/news/\"><input type=\"text\" name=\"search\" size=\"55\" maxlength=\"255\" value=\"\"> <input type=\"submit\" value=\"Search\"></input></form>"; document.pgfrm.search.focus(); }
}