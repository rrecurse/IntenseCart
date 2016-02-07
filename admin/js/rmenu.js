// Copyright 2002 Eddie Traversa, etraversa@dhtmlnirvana.com, http://dhtmlnirvana.com
// Free to use as long as this copyright notice stays intact.
// Courtesy of SimplytheBest.net - http://simplythebest.net/scripts/

var rmenuFadeIn=false;
var rmenuDivId='menu';
var ie5 = (document.getElementById&&document.all); 
var n6 	= (document.getElementById&&!document.all); 
if (n6)  document.addEventListener("mouseup",showMenu,false);
if (ie5) document.attachEvent("oncontextmenu",showMenu); 
if (ie5) document.attachEvent("onclick",showMenu); 
var rmenuOffsets={myframe: { x: 196, y: 224 } };

function showMenu(event,frameid) {
if (document.getElementById) {
var x,y,pageW,pageH;
if (ie5) {
  pageW   = document.body.scrollWidth + document.body.scrollLeft;
  pageH   = document.body.offsetHeight + document.body.scrollTop;
  scrl=frameid?document.getElementById(frameid):document.body;
  x	= event.clientX + scrl.scrollLeft;
  y	= event.clientY + scrl.scrollTop;
} else {
  pageW   = window.innerWidth + window.scrollX;
  pageH   = window.innerHeight + window.scrollY;
  x	= event.clientX + event.view.scrollX;
  y     = event.clientY + event.view.scrollY;
}
if (frameid && rmenuOffsets[frameid]) {
  x += rmenuOffsets[frameid].x;
  y += rmenuOffsets[frameid].y;
}
var el 		= document.getElementById(rmenuDivId);
if ((ie5&&event.type=="contextmenu")||(n6 && event.which>1)) {
if ((x+parseInt(el.offsetWidth))>=pageW) x -= parseInt(el.offsetWidth);
if ((y+parseInt(el.offsetHeight))>=pageH) y -= parseInt(el.offsetHeight);
el.style.top=y+"px";
el.style.left=x+"px";
fadeIn(rmenuDivId);
return false;
}
if ((ie5&&event.type=="click")||(n6 && event.which==1)) {
el.style.visibility="hidden";
fade_index = 0;
}
}
}
document.oncontextmenu=new Function("return false") ;
fade_index = 0;

function fadeIn(id) {
el=document.getElementById(id);
if(ie5 || n6) {
	el.style.visibility = 'visible';
if (rmenuFadeIn) {
	if(ie5) {
	el.filters.alpha.opacity = fade_index;
	}
	if(n6) {
	el.style.MozOpacity = fade_index/100; 
	}
	fade_index += 3;
	goIn = setTimeout("fadeIn(rmenuDivId)", 50);
	if(fade_index >= 100) 
	clearTimeout(goIn);
} else {
	if(ie5) {
	el.filters.alpha.opacity = 100;
	}
	if(n6) {
	el.style.MozOpacity = 1; 
	}
}
}
}
