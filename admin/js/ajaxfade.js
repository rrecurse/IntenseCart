function ajaxFade(blk,spd,url,args,stby) {
  if (!blk.ajaxFadeData) blk.ajaxFadeData={opacity:1,ct:0};
  var fdata=blk.ajaxFadeData;
  fdata.fade=true;
  fdata.req=null;
  fdata.loaded=false;
  var dt=50;
  if (fdata.intv) window.clearInterval(fdata.intv);
  fdata.intv=window.setInterval(function() {
//    $('theComments').innerHTML=fdata.ct++;
    if (fdata.fade) {
      if (fdata.opacity==0) {
        if (fdata.req) {
	  if (blk.tagName=='IMG') blk.src=fdata.req.src;
	  else blk.innerHTML=fdata.req.responseText;
	  fdata.fade=false;
	  var rq=fdata.req;
	  fdata.req=null;
	  fdata.loaded=true;
	  var scrs=blk.getElementsByTagName('script');
	  for (var i=0;scrs[i];i++) window.eval(scrs[i].innerHTML);
	  if (fdata.onComplete) fdata.onComplete(rq);
	} else if (stby) {
	  blk.innerHTML=stby;
	  fdata.fade=false;
	}
      } else {
        fdata.opacity-=spd*dt/1000;
        if (fdata.opacity<0) fdata.opacity=0;
      }
    } else {
      fdata.opacity+=spd*dt/1000;
      if (fdata.opacity>=1) {
        fdata.opacity=1;
	if (fdata.loaded) { 
	  window.clearInterval(fdata.intv);
	  fdata.intv=null;
	}
      }
    }
    blk.style.opacity=fdata.opacity.toFixed(3);
    blk.style.filter=fdata.opacity==1?'':'alpha(opacity='+(fdata.opacity*100).toFixed(0)+')';
  },dt);
//  else alert(fdata.fade+' '+fdata.opacity+' '+fdata.loaded+' '+fdata.req);
  if (!args) args={};
  var cf=args.onComplete;
  if (blk.tagName=='IMG') {
    fdata.image=new Image;
    fdata.image.onload=function() {
      fdata.req=this;
      fdata.onComplete=cf;
      fdata.fade=true;
    };
    fdata.image.onerror=function() {
      fdata.req={};
      fdata.fade=true;
    };
    fdata.image.src=url;
  } else {
    args.onComplete=function(req) {
      fdata.req=req;
      fdata.onComplete=cf;
      fdata.fade=true;
    };
    new ajax(url,args);
  }
}
