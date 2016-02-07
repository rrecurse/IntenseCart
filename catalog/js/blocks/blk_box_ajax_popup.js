function ajaxPopupTipObj(flds) {
  this.offsetfromcursorX=7;
  this.offsetfromcursorY=-5;
  this.offsetdivfrompointerX=21;
  this.offsetdivfrompointerY=-10;
  this.ie=!!document.all;
  this.ns6=(document.getElementById && !document.all);
  this.popups=[];
  this.actions=[];
  if (flds) for (var f in flds) this[f]=flds[f];
  return this;
}

ajaxPopupTipObj.prototype.show=function(event) {
  for (var i=0;this.popups[i];i++) {
    this.popups[i].style.display='';
  }
  for (var i=0;this.actions[i];i++) {
    if (this.actions[i](this,event)) this.actions[i]=null;
  }
  if (this.tipobj) {
    if (!this.detached) {
      document.getElementsByTagName('body')[0].insertBefore(this.tipobj,null);
      if (this.pointerobj) document.getElementsByTagName('body')[0].insertBefore(this.pointerobj,null);
      this.detached=1;
    }
    this.position(this.tipobj,this.pointerobj,event);
  }
}

ajaxPopupTipObj.prototype.move=function(event) {
  if (this.tipobj) this.position(this.tipobj,this.pointerobj,event);
};

ajaxPopupTipObj.prototype.hide=function(event) {
  for (var i=0;this.popups[i];i++) this.popups[i].style.display='none';
};

ajaxPopupTipObj.prototype.handleGrab=function(event) {
  this.handleOffs=[];
  for (var i=0;this.popups[i];i++) this.handleOffs[i]={x:this.popups[i].offsetLeft-event.clientX,y:this.popups[i].offsetTop-event.clientY};
};

ajaxPopupTipObj.prototype.handleMove=function(event) {
  if (!this.handleOffs) return;
  for (var i=0;this.popups[i];i++) {
    this.popups[i].style.left=(this.handleOffs[i].x+event.clientX)+'px';
    this.popups[i].style.top=(this.handleOffs[i].y+event.clientY)+'px';
  }
};

ajaxPopupTipObj.prototype.handleRelease=function(event) {
  this.handleOffs=null;
}

ajaxPopupTipObj.prototype.position=function(tipobj,pointerobj,e) {
  var nondefaultpos=false;
  var curX=(this.ns6)?e.pageX : event.clientX+this.ietruebody().scrollLeft;
  var curY=(this.ns6)?e.pageY : event.clientY+this.ietruebody().scrollTop;

  var winwidth=this.ie&&!window.opera? this.ietruebody().clientWidth : window.innerWidth-20;
  var winheight=this.ie&&!window.opera? this.ietruebody().clientHeight : window.innerHeight-20;

  var rightedge=this.ie&&!window.opera? winwidth-event.clientX-this.offsetfromcursorX : winwidth-e.clientX-this.offsetfromcursorX;
  var bottomedge=this.ie&&!window.opera? winheight-event.clientY-this.offsetfromcursorY : winheight-e.clientY-this.offsetfromcursorY;
  var topedge=this.ie&&!window.opera? event.clientY+this.offsetfromcursorY : e.clientY+this.offsetfromcursorY;

  var leftedge=(this.offsetfromcursorX<0)? this.offsetfromcursorX*(-1) : -1000;

  if (rightedge<tipobj.offsetWidth){

    tipobj.style.left=curX-tipobj.offsetWidth-this.offsetfromcursorX+"px";
    nondefaultpos=true;
  }
  else if (curX<leftedge)
    tipobj.style.left="5px";
  else{

    tipobj.style.left=curX+this.offsetfromcursorX+this.offsetdivfrompointerX+"px";
    if (pointerobj) pointerobj.style.left=curX+this.offsetfromcursorX+"px";
  }

  if (bottomedge<tipobj.offsetHeight){
    tipobj.style.top=curY-(topedge<tipobj.offsetHeight?topedge:tipobj.offsetHeight)-this.offsetfromcursorY+"px";
    //tipobj.style.top=curY-bottomedge-this.offsetfromcursorY+"px";
    if (!nondefaultpos) tipobj.style.left=curX+this.offsetfromcursorX+"px";
    nondefaultpos=true;
  }
  else{
    tipobj.style.top=curY+this.offsetfromcursorY+this.offsetdivfrompointerY+"px";
    if (pointerobj) pointerobj.style.top=curY+this.offsetfromcursorY+"px";
  }
//  tipobj.style.visibility="visible";
  if (pointerobj) pointerobj.style.display=nondefaultpos?"none":"block";
}

ajaxPopupTipObj.prototype.ietruebody=function() {
  return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body;
}
