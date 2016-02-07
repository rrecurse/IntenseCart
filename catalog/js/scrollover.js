function ScrollOver(blk,event,vx,vy) {
  var scrollX=window.scrollX!=undefined?window.scrollX:document.documentElement.scrollLeft+document.body.scrollLeft;
  var scrollY=window.scrollY!=undefined?window.scrollY:document.documentElement.scrollTop+document.body.scrollTop;
  if (vx==null) vx=1;
  if (vy==null) vy=1;
  
  var dx=event.clientX+scrollX;
  var dy=event.clientY+scrollY;
  for (var p=blk;p.offsetParent;p=p.offsetParent) {
    dx-=p.offsetLeft;
    dy-=p.offsetTop;
  }
  
  if (vx) blk.scrollLeft=(blk.scrollWidth-blk.offsetWidth)*Math.min(1,(Math.max(0,(dx/blk.offsetWidth-0.5)*vx+0.5)));
  if (vy) blk.scrollTop=(blk.scrollHeight-blk.offsetHeight)*Math.min(1,(Math.max(0,(dy/blk.offsetHeight-0.5)*vy+0.5)));
}