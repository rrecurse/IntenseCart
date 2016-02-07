function SBarMove(blk,evnt,start) {
  var ovl=$(blk.getAttribute('SBarOverlay'));
  if (evnt) {
    if (blk.SBarData) {
      var dx=evnt.screenX-blk.SBarData.x;
      var dy=evnt.screenY-blk.SBarData.y;
      SBarScrollBy(blk,dx,dy);
      blk.SBarData.x=evnt.screenX;
      blk.SBarData.y=evnt.screenY;
      if (ovl) ovl.style.visibility="visible";
    } else if (start) blk.SBarData={x:evnt.screenX,y:evnt.screenY};
//    evnt.cancelBubble=true;
  } else {
    blk.SBarData=null;
    if (ovl) ovl.style.visibility="hidden";
  }
}

function SBarScrollBy(blk,dx,dy) {
  var xpos=blk.parentNode.offsetWidth==blk.offsetWidth?0:(blk.offsetLeft+dx)/(blk.parentNode.offsetWidth-blk.offsetWidth);
  var ypos=blk.parentNode.offsetHeight==blk.offsetHeight?0:(blk.offsetTop+dy)/(blk.parentNode.offsetHeight-blk.offsetHeight);
//  alert(blk.offsetLeft);
  if (xpos<0) xpos=0; if (xpos>1) xpos=1;
  if (ypos<0) ypos=0; if (ypos>1) ypos=1;
  SBarScrollTo(blk,xpos,ypos);
  sblk=$(blk.getAttribute('SBarBlock'));
  if (sblk) SBarScrollTo(sblk,xpos,ypos);
  return ((dx && (xpos>0 && xpos<1)) || (dy && (ypos>0 && ypos<1)));
}

function SBarScrollTo(blk,xpos,ypos) {
//  blk.innerHTML=blk.scrollWidth+' '+blk.scrollHeight;
  if (blk.scrollWidth>blk.offsetWidth) {
    blk.scrollLeft=Math.floor((blk.scrollWidth-blk.offsetWidth)*xpos);
    blk.scrollTop=Math.floor((blk.scrollHeight-blk.offsetHeight)*ypos);
  } else {
//  blk.innerHTML=xpos+' '+ypos;
    blk.style.left=Math.floor((blk.parentNode.offsetWidth-blk.offsetWidth)*xpos)+'px';
    blk.style.top=Math.floor((blk.parentNode.offsetHeight-blk.offsetHeight)*ypos)+'px';
  }
//  alert(blk.parentNode.offsetWidth+' '+blk.offsetWidth);
}

function SBarRun(blk,dx,dy,dl,it) {
  if (!dl) dl=250;
  if (!it) it=100;
  if (blk.SBarIntv) blk.SBarIntv=window.clearInterval(blk.SBarIntv),null;
  if (blk.SBarTmout) blk.SBarTmout=window.clearTimeout(blk.SBarTmout),null;
  if (!dx && !dy) return;
  if (SBarScrollBy(blk,dx,dy)) blk.SBarTmout=window.setTimeout(function() {
    blk.SBarIntv=window.setInterval( function() {
      if (!SBarScrollBy(blk,dx,dy)) blk.SBarIntv=window.clearInterval(blk.SBarIntv),null;
    },it);
  },dl);
}
