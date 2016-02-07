var offsetfromcursorX=7;
var offsetfromcursorY=0;
var offsetdivfrompointerX=21;
var offsetdivfrompointerY=-10;
var Slides=Array();


function ShowQView(p_name,p_info,p_desc,p_price,p_image) {
 ddrivetip('<table width="343" border="0" cellspacing="0" cellpadding="0">'
  +'<tr><td class="qViewtop"><table align="center" cellpadding=0 cellspacing=0>'
  +'<tr><td height=19 align="center" class="qviewTopName">'
  + p_name +'</td>  </tr></table></td></tr><tr>'
  +'<td valign="top" style="background:url(../../images/qview-bg.gif) no-repeat; padding-top:15px;">'
  +'<table width="343" border="0" cellspacing="0" cellpadding="0"><tr><td align="center" valign="top" style="padding-left:10px;">'
  + p_image + '</td><td valign="top" style="padding-right:7px; padding-bottom:7px;">'
  +'<table width="160" border="0" cellspacing="0" cellpadding="0">  <tr>'
  +'<td class=qviewBigName>' + p_name + '</td>  </tr>'
  +'<tr><td class=qviewPrice>' + p_price + '</td></tr>'
  +'<tr><td valign="top">' + p_info + '</td></tr>'
  +'<tr><td valign="top" style="padding-top:5px;">' + p_desc + '</td></tr></table>'
  +'</td></tr></table></td></tr><tr><td class="qViewbottom"></td>  </tr></table>');
}








document.write('<div id="dhtmltooltip"></div>');
document.write('<img id="dhtmlpointer" src="/images/qviewtip.gif">');

var ie=document.all;
var ns6=document.getElementById && !document.all;
var enabletip=false;
if (ie||ns6)
var tipobj=(document.all && document.all["dhtmltooltip"])? document.all["dhtmltooltip"] : document.getElementById? document.getElementById("dhtmltooltip") : "";

var pointerobj=document.all? document.all["dhtmlpointer"] : document.getElementById? document.getElementById("dhtmlpointer") : "";

function ietruebody(){
return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body;
}

function ddrivetip(thetext, thewidth, thecolor){
//alert(tipobj);
if (ns6||ie){
if (typeof thewidth!="undefined") tipobj.style.width=thewidth+"px";
if (typeof thecolor!="undefined" && thecolor!="") tipobj.style.backgroundColor=thecolor;
tipobj.innerHTML=thetext.replace(/<slide.*?<\/slide>/g,'');
enabletip=true;
ParseSlides(thetext);
StartSlides();
return false;
}
}

function positiontip(e){
if (enabletip){
var nondefaultpos=false;
var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;

var winwidth=ie&&!window.opera? ietruebody().clientWidth : window.innerWidth-20;
var winheight=ie&&!window.opera? ietruebody().clientHeight : window.innerHeight-20;

var rightedge=ie&&!window.opera? winwidth-event.clientX-offsetfromcursorX : winwidth-e.clientX-offsetfromcursorX;
var bottomedge=ie&&!window.opera? winheight-event.clientY-offsetfromcursorY : winheight-e.clientY-offsetfromcursorY;
var topedge=ie&&!window.opera? event.clientY+offsetfromcursorY : e.clientY+offsetfromcursorY;

var leftedge=(offsetfromcursorX<0)? offsetfromcursorX*(-1) : -1000;

if (rightedge<tipobj.offsetWidth){

tipobj.style.left=curX-tipobj.offsetWidth-offsetfromcursorX+"px";
nondefaultpos=true;
}
else if (curX<leftedge)
tipobj.style.left="5px";
else{

tipobj.style.left=curX+offsetfromcursorX+offsetdivfrompointerX+"px";
pointerobj.style.left=curX+offsetfromcursorX+"px";
}

if (bottomedge<tipobj.offsetHeight){
tipobj.style.top=curY-(topedge<tipobj.offsetHeight?topedge:tipobj.offsetHeight)-offsetfromcursorY+"px";
//tipobj.style.top=curY-bottomedge-offsetfromcursorY+"px";
if (!nondefaultpos) tipobj.style.left=curX+offsetfromcursorX+"px";
nondefaultpos=true;
}
else{
tipobj.style.top=curY+offsetfromcursorY+offsetdivfrompointerY+"px";
pointerobj.style.top=curY+offsetfromcursorY+"px";
}
tipobj.style.visibility="visible";
if (!nondefaultpos)
pointerobj.style.visibility="visible";
else
pointerobj.style.visibility="hidden";
}
}

function hideddrivetip(){
StopSlides();
if (ns6||ie){
enabletip=false;
tipobj.style.visibility="hidden";
pointerobj.style.visibility="hidden";
tipobj.style.left="-1000px";
tipobj.style.backgroundColor='';
tipobj.style.width='';
}
}
document.onmousemove=positiontip;


var SlideTimeouts=Array();

function ParseSlides(thetext) {
  var sl=thetext.split(/<slide/);
  for (var i=1;sl[i];i++) {
    var ats=sl[i].match(/^([^>"]|"[^"]*")*/)[0].match(/([^\"\s]|\"[^\"]*\")+/g);
    var attr=Array();
    for (var j in ats) {
      attr[ats[j].match(/^\w+/)]=ats[j].replace(/.*?=/,'').replace(/"/g,'');
    }
    if (attr['target']) {
      if (!Slides[attr['target']]) {
        Slides[attr['target']]=Array();
        if (attr['over']==null) attr['over']=1;
      }
      var slide=Array();
      slide['delay']=attr['delay']?attr['delay']:1000;
      slide['over']=attr['over'];
      slide['text']=sl[i].replace(/.*?>/,'').replace(/<\/slide.*/,'');
      Slides[attr['target']][Slides[attr['target']].length]=slide;
    }
  }
}


function StartSlides() {
  for (var sec in Slides) {
    if (Slides[sec].length) ShowSlide(sec,0);
  }
}

function StopSlides() {
  Slides=Array();
  for (var sec in SlideTimeouts) window.clearTimeout(SlideTimeouts[sec]);
  SlideTimeouts=Array();
}

function ShowSlide(sec,idx) {
  if (Slides && Slides[sec]) {
    var sl=Slides[sec][idx];
    var div=ie?document.all[sec]:document.getElementById(sec);
    if (sl && div) {
      div.innerHTML=sl['over']?sl['text']:div.innerHTML+sl['text'];
      var next=idx+1;
      if (!Slides[sec][next]) next=0;
      SlideTimeouts[sec]=window.setTimeout('ShowSlide(\''+sec+'\','+next+')',Slides[sec][next]['delay']);
    }
  }
}
