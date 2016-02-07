<?
class geomaps_google extends IXmodule {
  function geomaps_google() {
    $this->markers=Array();
    $this->types=Array();
  }
  function getName() {
    return 'Google Maps';
  }
  function getGeoCoords($addr) {
    $rs=Array();
    if ($f=fopen('http://maps.google.com/maps/geo?q='.urlencode(is_array($addr)?$addr['street'].' '.$addr['city'].' '.$addr['state'].' '.$addr['postcode'].' '.$addr['country']:$addr).'&key='.urlencode($this->getConf('key')).'&output=xml','r')) {
      $rsp=fread($f,65536);
      if (preg_match('|<Point>.*?<coordinates>(.*?)</coordinates>.*?</Point>|s',$rsp,$p)) list($rs['lng'],$rs['lat'],$rs['elev'])=split(',',$p[1]);
      if (preg_match('|<AddressDetails Accuracy="(.*?)"|s',$rsp,$p)) $rs['accuracy']=$p[1];
      fclose($f);
    }
    return $rs;
  }
  function addPoint($id,$lat,$lng,$df,$type) {
    $slt=min(13,max(0,floor($df*$this->getConf('df_gage')+$this->getConf('df_adj'))));
    if (!isset($this->markers[$slt])) $this->markers[$slt]=Array();
    $this->markers[$slt][$id]=Array('id'=>$id,'lat'=>$lat,'lng'=>$lng,'type'=>$type);
    if (!isset($this->types[$type])) $this->types[$type]=$type;
  }
  function render($lat=NULL,$lng=NULL,$zoom=13) {
    $mapvar='IXcoreGMap';
    $infovar=$mapvar.'Info';
    $mrkvar=$mapvar.'Mrk';
    $iconvar=$mapvar.'Icons';
?>
<div id="google_map" style="width:100%;height:300px;"></div>
<script language="javascript" src="http://maps.google.com/maps?file=api&v=2&key=<?=urlencode($this->getConf('key'))?>"></script>
<script language="javascript">
var <?=$mapvar?>;
var <?=$infovar?>={};
var <?=$mrkvar?>={};
var <?=$iconvar?>={};
  
  window.onload=function() {
    <?=$mapvar?>=new GMap2($('google_map'));
    <?=$mapvar?>.addControl(new GLargeMapControl());
    <?=$mapvar?>.addControl(new GMapTypeControl());
    <?=$mapvar?>.setCenter(new GLatLng(<?=isset($lat)?$lat+0:40.762585?>,<?=isset($lng)?$lng+0:-73.929555?>),<?=$zoom+0?>);
    var mgr=new GMarkerManager(<?=$mapvar?>);
<?  foreach ($this->types AS $type) {
      $icv=$iconvar.'['.tep_js_quote($type).']';
      $icd=$this->getExtra('icons',$type);
      echo $icv."=new GIcon(G_DEFAULT_ICON);\n";
      if (isset($icd)) {
        if (isset($icd['icon'])) echo $icv.".image=".tep_js_quote(tep_image_src(DIR_WS_CATALOG_IMAGES.$icd['icon'])).";\n";
        if (isset($icd['shadow'])) echo $icv.".shadow=".tep_js_quote(tep_image_src(DIR_WS_CATALOG_IMAGES.$icd['shadow'])).";\n";
      }
    }
    foreach ($this->markers AS $slt=>$mrks) { ?>
      <?=$mrkvar?>[<?=$slt?>]={<? $ct=0;
      foreach ($mrks AS $mrk) {
        
?>
	<?=$ct++?',':''?><?=tep_js_quote($mrk['id'])?>:new GMarker(new GLatLng(<?=$mrk['lat']+0?>,<?=$mrk['lng']+0?>),<?=$iconvar?>[<?=tep_js_quote($mrk['type'])?>])
<?    } ?>
      };
<?  } ?>
    for (var slt in <?=$mrkvar?>) {
      var mrks=[];
      for (var idx in <?=$mrkvar?>[slt]) {
        var m=<?=$mrkvar?>[slt][idx];
	m.IXcoreRef=idx;
	mrks.push(m);
      }
      mgr.addMarkers(mrks,slt);
    }
    mgr.refresh();
    GEvent.addListener(<?=$mapvar?>,'click',function(marker,point) {
      if (marker) window.mapShowGMarker(marker);
    });
  };
  window.onunload=GUnload;
  window.mapGoTo=function(l,zoom) {
    if (!l) return;
    var gc=new GClientGeocoder();
    gc.getLatLng(l,function(p) {
      <?=$mapvar?>.setCenter(p,zoom!=null?zoom:13);
    });
  };
  window.mapGoToLatLng=function(lat,lng,zoom) {
    <?=$mapvar?>.setCenter(new GLatLng(lat,lng),zoom!=null?zoom:13);
  };
  window.mapShowMarker=function(mid) {
    for (var slt in <?=$mrkvar?>) if (<?=$mrkvar?>[slt][mid]) mapShowGMarker(<?=$mrkvar?>[slt][mid],Number(slt));
  };
  window.mapShowGMarker=function(marker,zoom) {
    if (zoom><?=$mapvar?>.getZoom()) <?=$mapvar?>.setZoom(zoom);
    if (marker) {
      var rf=marker.IXcoreRef;
      if (<?=$infovar?>[rf]) marker.openInfoWindowHtml(<?=$infovar?>[rf]);
      else if (<?=$infovar?>[rf]==undefined) {
	<?=$infovar?>[rf]=false;
	new ajax('<?=$this->ajaxInfoUrl?>'+rf,{onComplete:function(req) {
	  marker.openInfoWindowHtml(<?=$infovar?>[rf]=req.responseText);
	}});
      }
    }
  };
</script>
<?
  }

  function isReady() {
    return $this->getConf('key');
  }
  function listConf() {
    return Array(
      'key'=>Array('title'=>'API Key for '.HTTP_CATALOG_SERVER),
      'df_adj'=>Array('title'=>'Density Adjustment (default 0)','default'=>4),
      'df_gage'=>Array('title'=>'Density Gage (default 3)','default'=>0.8),
    );
  }
}
?>