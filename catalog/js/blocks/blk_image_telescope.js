function imageScalerObj(obj) {
  for (var f in obj) this[f]=obj[f];
  if (!this.x) this.x=0.5;
  if (!this.y) this.y=0.5;
  if (!this.zoom) this.zoom=1.0;
  if (!this.targetZoom) this.targetZoom=1.0;
  if (!this.telFrameMin) this.telFrameMin=50;
  this.currSlot=0;
  this.ptrElements={0:{}};
  this.slotImages=[];
}

imageScalerObj.prototype.moveTo=function(dx,dy,zoom,zr) {
  this.telescopeOff();
  this.div.src=this.images[this.currImage][0].src;
/*
  zoom=1;
  if (!zr) zr=1;
  var rzoom=zoom/zr;
  if (rzoom<1) rzoom=1;
  if (zoom>1) {
    this.x+=dx/this.width/(zoom-1);
    this.y+=dy/this.height/(zoom-1);
  }
  if (this.x>1) this.x=1;
  if (this.y>1) this.y=1;
  if (this.x<0) this.x=0;
  if (this.y<0) this.y=0;
  var x=Math.floor(this.x*this.width*(zoom-1));
  var y=Math.floor(this.y*this.height*(zoom-1));
  var im;
  for (var i=0;im=this.images[this.currImage][i];i++) {
    if (im.zoom<=rzoom) {
      var x1=Math.floor(this.width*im.x1*zoom);
      var x2=Math.floor(this.width*im.x2*zoom);
      var y1=Math.floor(this.height*im.y1*zoom);
      var y2=Math.floor(this.height*im.y2*zoom);
      if ((x2>x || x1<x+this.width) && (y2>y || y1<y+this.height)) {
        if (!im.element) {
  	  im.element=document.createElement('img');
	  im.element.style.position='absolute';
	  im.element.src=im.src;
	  im.element.style.zIndex=10;
	  this.div.insertBefore(im.element,null);
	}
	im.element.width=x2-x1;
	im.element.height=y2-y1;
	im.element.style.left=x1+'px';
	im.element.style.top=y1+'px';
	im.element.style.display='';
      }
    } else if (im.element) im.element.style.display='none';
  }
  this.div.style.left=-x+'px';
  this.div.style.top=-y+'px';
  if (this.ptrdiv && this.ptrImages[this.currImage]) {
    this.ptrdiv.style.left=Math.floor(x/this.width/zoom*this.pwidth)+'px';
    this.ptrdiv.style.top=Math.floor(y/this.height/zoom*this.pheight)+'px';
    this.ptrdiv.style.width=Math.floor(this.pwidth/zoom)+'px';
    this.ptrdiv.style.height=Math.floor(this.pheight/zoom)+'px';
  }
*/
};

imageScalerObj.prototype.mouseDown=function(event,wh) {
    this.drag=wh;
    this.lastX=event.screenX;
    this.lastY=event.screenY;
    event.cancelBubble=true;
//    event.stopPropagation();
};

imageScalerObj.prototype.mouseMove=function(event,wh) {
    if (this.telescopeOn()) {
      var scrollX=window.scrollX!=undefined?window.scrollX:document.documentElement.scrollLeft+document.body.scrollLeft;
      var scrollY=window.scrollY!=undefined?window.scrollY:document.documentElement.scrollTop+document.body.scrollTop;
      var x=event.clientX+scrollX;
      var y=event.clientY+scrollY;
      for (var p=this.div;p;p=p.offsetParent) {
        x-=p.offsetLeft;
        y-=p.offsetTop;
      }
      this.telescopeMove(x,y);
    }
    if (this.drag!=wh) return;
    switch (wh) {
    case 1:
      this.moveTo(this.lastX-event.screenX,this.lastY-event.screenY);
      break;
    case 2:
      this.moveTo((event.screenX-this.lastX)*this.width/this.pwidth*this.zoom,(event.screenY-this.lastY)*this.height/this.pheight*this.zoom);
      break;
    }
    this.lastX=event.screenX;
    this.lastY=event.screenY;
};

imageScalerObj.prototype.mouseUp=function(event,wh) {
    this.drag=0;
};

imageScalerObj.prototype.mouseOut=function(event) {
    this.telescopeOff();
};

imageScalerObj.prototype.mouseDownPtr=function(event,sl) {
    if (!this.slotSwap(sl)) return false;
    var x=event.offsetX!=null?event.offsetX:event.layerX;
    var y=event.offsetY!=null?event.offsetY:event.layerY;
    if (this.zoom>1) {
      this.x=0.5+(x-this.pwidth/2)*this.zoom/(this.zoom-1)/this.pwidth;
      this.y=0.5+(y-this.pheight/2)*this.zoom/(this.zoom-1)/this.pheight;
    }
    this.moveTo(0,0);
    this.mouseDown(event,2);
};

imageScalerObj.prototype.goScale=function(zoom,steps) {
    this.targetZoom=zoom;
    var i=0;
    var sc=this;
    var intv=window.setInterval(function() {if (i<steps) sc.moveTo(0,0,sc.zoom*Math.exp(1/(steps-i++)*Math.log(zoom/sc.zoom)),3); else { sc.moveTo(0,0,zoom); clearInterval(intv);} },30);
};

imageScalerObj.prototype.goScaleUp=function(steps) {
    var z=null;
    for (var i=0;this.images[this.currImage][i];i++) if (this.images[this.currImage][i].zoom>this.zoom+0.1 && (!z || this.images[this.currImage][i].zoom<z)) z=this.images[this.currImage][i].zoom;
    if (z) this.goScale(z,steps);
};

imageScalerObj.prototype.goScaleDown=function(steps) {
    var z=null;
    for (var i=0;this.images[this.currImage][i];i++) if (this.images[this.currImage][i].zoom<this.zoom-0.1 && (!z || this.images[this.currImage][i].zoom>z)) z=this.images[this.currImage][i].zoom;
    if (z) this.goScale(z,steps);
};

imageScalerObj.prototype.slotSwap=function(sl) {
    if (sl==this.currSlot) return true;
    this.telImg=null;
    if (this.slotImages[sl] && this.ptrdiv) {
      var blk=this.ptrSlot[sl];
      if (!blk) return false;
      blk.insertBefore(this.ptrdiv,null);
      this.zoom=this.targetZoom=1;
      this.flushImages();
      this.imageSelect(this.slotImages[sl]);
      this.currSlot=sl;
      if (!this.ptrElements[sl]) this.ptrElements[sl]={};
      return true;
    }
    return false;
};

imageScalerObj.prototype.imageSelect=function(img) {
    this.telImg=null;
    if (!this.images[img]) return;
    for (var im in this.images) if (im!=img) {
      for (var i=0;this.images[im][i];i++) if (this.images[im][i].element) this.images[im][i].element.style.display='none';
    }
    this.currImage=img;
    var zoom=this.targetZoom;
    var lz=Math.log(zoom);
    var zdev=10;
    for (var i=0;this.images[img][i];i++) {
      var zd=Math.abs(Math.log(this.images[img][i].zoom)-lz);
      if (zd<zdev) {
        zdev=zd;
        zoom=this.images[img][i].zoom;
      }
    }
    this.moveTo(0,0,zoom);
  };

imageScalerObj.prototype.flushImages=function() {
    for (var im in this.images) for (var i=0;this.images[im][i];i++) if (this.images[im][i].zoom>1 && this.images[im][i].element) this.images[im][i].element=(this.images[im][i].element.parentNode.removeChild(this.images[im][i].element),null);
  };

imageScalerObj.prototype.imageSwap=function(imgs) {
    for (var i=0;imgs[i] || this.ptrSlot[i];i++) {
      this.slotImages[i]=imgs[i];
      if (!this.ptrSlot[i]) continue;
      if (!this.ptrElements[i]) this.ptrElements[i]={};
      for (var im in this.images) if (im!=imgs[i]) {
        if (this.ptrElements[i][im]) this.ptrElements[i][im].style.display='none';
      }
      if (imgs[i] && this.images[imgs[i]]) {
        if (!this.ptrElements[i][imgs[i]]) {
          e=document.createElement('img');
          e.style.position='absolute';
          e.src=this.ptrImages[imgs[i]];
          e.style.zIndex=1;
          e.style.top=e.style.left='0px';
          e.width=this.pwidth;
          e.height=this.pheight;
          this.ptrSlot[i].insertBefore(e,null);
          this.ptrElements[i][imgs[i]]=e;
        }
        this.ptrElements[i][imgs[i]].style.display='block';
      }
    }
    this.imageSelect(this.slotImages[this.currSlot]);
  };

imageScalerObj.prototype.telescopeOn=function() {
    if (!this.telDiv || this.zoom>1) return false;
    if (!this.telIntv) {
      this.telOpc=0;
      this.telIntv=window.setInterval(function() {
        this.telOpc+=this.fadeRate;
	if (this.telOpc>1) this.telOpc=1;
        this.telDiv.style.opacity=this.telOpc;
//        this.telMask.style.opacity=this.telOpc;
        this.telDiv.style.filter=this.telOpc>=1?'':'alpha(opacity='+(this.telOpc*100).toFixed(0)+')';
        if (this.telImg) this.telImg.style.filter=this.telOpc>=1?'':'alpha(opacity='+(this.telOpc*100).toFixed(0)+')';
//        this.telMask.style.filter=this.telOpc>=1?'':'alpha(opacity='+(this.telOpc*100).toFixed(0)+')';
        this.telDiv.style.display='';
        this.telMask.style.display='';
        if (this.telOpc>=1) window.clearInterval(this.telIntv);
      }.bind(this),50);
      if (!this.telImg) {
        var z=1;
        var im;
	var timg=this.telDiv.getElementsByTagName('img')[0];
        for (var i=0;im=this.images[this.currImage][i];i++) if (im.zoom/this.zoom>z) {
	  z=im.zoom/this.zoom;
	  timg.src=im.src;
	  this.telImg=timg;
          this.telImgW=Math.floor(this.width*im.zoom);
          this.telImgH=Math.floor(this.height*im.zoom);
	}
        if (this.telImg) {
          this.telFrameW=Math.floor(this.width*this.telDivW*this.zoom/this.telImgW);
          this.telFrameH=Math.floor(this.height*this.telDivH*this.zoom/this.telImgH);
	  var r=Math.min(1,this.telFrameW/this.telFrameMin,this.telFrameH/this.telFrameMin);
	  this.telFrameW/=r;
	  this.telFrameH/=r;
	  var msk=this.telMask.getElementsByTagName('div');
	  msk[1].style.width=msk[7].style.width=this.telFrameW+'px';
  	  msk[3].style.height=msk[5].style.height=this.telFrameH+'px';
	  msk[4].style.width=(this.telFrameW-2*this.telFrameOffs)+'px';
	  msk[4].style.height=(this.telFrameH-2*this.telFrameOffs)+'px';
        }
      }
      if (!this.telImg) return false;
    }
    return true;
  };

imageScalerObj.prototype.telescopeMove=function(x,y) {
    if (!this.telDiv) return;
    x=Math.min(this.width-this.telFrameW/2,Math.max(this.telFrameW/2,x));
    y=Math.min(this.height-this.telFrameH/2,Math.max(this.telFrameH/2,y));
    this.telMask.style.left=(x-this.width-this.telFrameW/2)+'px';
    this.telMask.style.top=(y-this.height-this.telFrameH/2)+'px';
    this.telImg.style.left=(-Math.floor((this.telImgW-this.telDivW)*(x-this.telFrameW/2)/(this.width-this.telFrameW)))+'px';
    this.telImg.style.top=(-Math.floor((this.telImgH-this.telDivH)*(y-this.telFrameH/2)/(this.height-this.telFrameH)))+'px';
  };

imageScalerObj.prototype.telescopeOff=function() {
    if (!this.telDiv) return false;
    clearInterval(this.telIntv);
    this.telIntv=null;
    this.telDiv.style.display='none';
    this.telMask.style.display='none';
  };
