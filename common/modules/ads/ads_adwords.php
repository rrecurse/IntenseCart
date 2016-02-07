<?
    define('APILITY_SILENCE_STEALTH_MODE',true);
    define('APILITY_DISPLAY_ERROR_STYLE',"Plaintext");
    require_once(DIR_FS_ADMIN.'apility/apility.php');

class ads_adwords extends IXmodule {
  var $showAll=false;
  function ads_adwords() {
    APIlity_init($this->getConf('email'),$this->getConf('password'),$this->getConf('dev_token'),$this->getConf('client_email'),$this->getConf('app_token'));
    $this->campaigns_id=Array();
  }
  function getName() {
    return 'Google AdWords';
  }
  function listConf() {
    return Array(
	'email'=>Array('default'=>'','title'=>'Email'),
	'password'=>Array('default'=>'','title'=>'Password','js_visible'=>"val('email')!=''"),
	'dev_token'=>Array('default'=>'','title'=>'Developer Token'),
	'client_email'=>Array('default'=>'','title'=>'Client Email'),
	'app_token'=>Array('default'=>'','title'=>'Application Token')
    );
  }
  function isReady() {
    return true;
  }
  function validateConf($key,$val) {
    switch ($key) {
    case 'email':
    case 'client_email':
      if (!preg_match('/.+\@.+/',$val)) return 'Please enter valid email';
      break;
    default:
    }
    return NULL;
  }

  function getParams() {
    return Array('active'=>true);
  }
  function showDeleted($fl) {
    $this->showAll=$fl;
  }
  function getCampaigns() {
    if (!isset($this->campaigns)) {
      $this->campaigns=Array();
      $cm=APIlity_getAllCampaigns($this->showAll);
      if ($cm) foreach ($cm AS $c) $this->campaigns[$c->getId()]=new ads_adwords_campaign($this,$c);
    }
    return $this->campaigns;
  }
  function getCampaign($cid) {
    if (!isset($cid)) return new ads_adwords_campaign($this,new APIlityCampaign('New Campaign',NULL,'Active',date('Y-m-d'),date('Y-m-d',time()+86400*730),10,Array('GoogleSearch'),Array(),Array('metroTargets'=>Array('metros'=>Array()),'cityTargets'=>Array('cities'=>Array()),'countryTargets'=>Array('countries'=>Array()),'regionTargets'=>Array('regions'=>Array())),false,false,Array(),Array(),Array()));
    if (isset($this->campaigns_id[$cid])) return $this->campaigns_id[$cid];
    if (isset($this->campaigns)) return isset($this->campaigns[$cid])?$this->campaigns[$cid]:NULL;
    $cm=APIlity_createCampaignObject($cid);
    return $cm?$this->campaigns_id[$cid]=new ads_adwords_campaign($this,$cm):NULL;
  }
  function newCampaign() {
    return new ads_adwords_campaign($this,NULL);
  }
  function getAd($cid,$aid) {
    $ad=APIlity_createAdGroupObject($aid);
    return $ad?new ads_adwords_advert($this,$camp=NULL,$ad):NULL;
  }
  function getStats($start,$end,$nocache=false) {
    $start=date('Y-m-d',strtotime($start));
    $end=date('Y-m-d',strtotime($end));
    $nosave=false;
    if ($nosave=($end>date('Y-m-d'))) $nocache=true;
    if (!$nocache) {
      $ppc_cache_query=tep_db_query("SELECT * FROM ppc_stats WHERE start_date='$start' AND finish_date='$end' AND ppc_source='adwords'");
      if ($ppc_cache=tep_db_fetch_array($ppc_cache_query))
        return Array('clicks'=>$ppc_cache['ppc_clicks'],'cost'=>$ppc_cache['ppc_cost'],'conv'=>$ppc_cache['ppc_conversions']);
    }
    $st=Array('clicks'=>0,'convs'=>0,'imprs'=>0,'cost'=>0.00);
    foreach ($this->getCampaigns() AS $c) {
      $s=$c->getStats($start,$end);
      foreach ($st AS $fld=>$val) $st[$fld]+=$s[$fld];
    }
    if (!$nosave) tep_db_query("REPLACE INTO ppc_stats (ppc_source,start_date,finish_date,ppc_clicks,ppc_impressions,ppc_cost,ppc_conversions) VALUES ('adwords','$start','$end','".$st['clicks']."','".$st['imprs']."','".$st['cost']."','".$st['convs']."')");
//    echo "$start $end\n";
//    print_r($st);
    return $st;
  }
  function setError($er) {
    $this->error=$er;
    return NULL;
  }
  function getError() {
    return isset($this->error)?$this->error:NULL;
  }
  function getErrors() {
    global $APIlity_faultStack;
    $rs=Array();
    if (isset($this->error)) $rs[]=$this->error;
    if (isset($APIlity_faultStack) && is_array($APIlity_faultStack)) foreach ($APIlity_faultStack AS $f) {
      $rs[]=$f->getFault();
    }
    return $rs;
  }
  function getUsageStats($start,$end) {
    return Array('quota'=>APIlity_getUnitCount($start,$end));
  }
}

class ads_adwords_campaign {
  var $campaign;
  function ads_adwords_campaign(&$adobj,$c) {
    $this->adobj=&$adobj;
    $this->campaign=$c;
    $this->adgroups_key=Array();
  }
  function flushCache() {
    tep_db_query("DELETE FROM adwords_cache WHERE obj_parent='".$this->campaign->getId()."'");
    $this->adgroups_key=Array();
    $this->adgroups_id=Array();
  }
  function getAd($aid) {
    if (isset($this->adgroups)) return isset($this->adgroups[$aid])?$this->adgroups[$aid]:NULL;
    if (isset($this->adgroups_id[$aid])) return $this->adgroups_id[$aid];
    $adg=APIlity_createAdGroupObject($aid);
    if (!$adg) return NULL;
    return $this->adgroups_id[$aid]=new ads_adwords_advert($this->adobj,$this,$adg);
  }
  function getAds($key=NULL) {
    $cid=$this->campaign->getId();
    if ($key) {
      if (isset($this->adgroups_key[$key])) return $this->adgroups_key[$key];
      if (!isset($this->adgroups)) {
        $rescan=0;
	$adgs=Array();
        $qry=tep_db_query("SELECT obj_id FROM adwords_cache WHERE obj_type='G' AND obj_parent='$cid' AND obj_key='$key'");
        while ($row=tep_db_fetch_array($qry)) {
	  $ad=$this->getAd($row['obj_id']);
          if (!$ad) {
	    $rescan=1;
	    $this->flushCache();
	    break;
	  }
	  $adgs[$row['obj_id']]=$ad;
        }
	if (!sizeof($adgs) && !tep_db_num_rows(tep_db_query("SELECT obj_id FROM adwords_cache WHERE obj_type='G' AND obj_parent='$cid'"))) $rescan=1;
	if (!$rescan) return $this->adgroups_key[$key]=$adgs;
      }
    }
    if (!isset($this->adgroups)) {
      $this->adgroups=Array();
      $grps=$this->campaign->getAllAdGroups($this->adobj->showAll);
      if ($grps) foreach ($grps AS $g) {
        $gid=$g->getId();
        $this->adgroups[$gid]=$gobj=new ads_adwords_advert($this->adobj,$this,$g);
        $gobj->updateCache();
      }
    }
    if ($key) {
      $rs=Array();
      foreach ($this->adgroups AS $aid=>$ad) if ($ad->getKey()==$key) $rs[$aid]=$ad;
      return $this->adgroups_key[$key]=$rs;
    } else return $this->adgroups;
  }
  function findKeywords($keyw,$excl) {
    $kwl=Array();
    if (is_array($keyw)) {
      foreach ($keyw AS $kw) $kwl[]="'".addslashes($kw)."'";
    } else $kwl[]="'".addslashes($keyw)."'";
    $cid=$this->campaign->getId();
    $qry=tep_db_query("SELECT a.obj_id,k.obj_key FROM adwords_cache k,adwords_cache a WHERE k.obj_type='K' AND k.obj_key IN (".join(',',$kwl).") AND k.obj_parent=a.obj_id AND a.obj_parent='$cid' AND a.obj_id!='$excl'");
    $rs=Array();
    while ($row=tep_db_fetch_array($qry)) {
      if (!isset($rs[$row['obj_key']])) $rs[$row['obj_key']]=Array();
      $rs[$row['obj_key']][]=new ads_adwords_advert($this->adobj,$this,APIlity_createAdGroupObject($row['obj_id']));
    }
    return $rs;
  }
  function getTargeting() {
    $geo=$this->campaign->getGeoTargets();
    $rgns=$geo['regionTargets']['regions'];
    foreach ($geo['metroTargets']['metros'] AS $r) $rgns[]="M:$r";
    foreach ($geo['cityTargets']['cities'] AS $r) $rgns[]="C:$r";
    $ctrys=(!$rgns && !$geo['countryTargets']['countries'])?Array('*'):$geo['countryTargets']['countries'];
    return Array('network'=>$this->campaign->getNetworkTargeting(),'countries'=>$ctrys,'regions'=>$rgns,'languages'=>$this->campaign->getLanguages());
  }
  function getTargetingList() {
    require(DIR_FS_COMMON.'modules/ads/adwords_targets.php');
    return $adWordsTargetingList;
  }
  function setTargeting($tg) {
    if (isset($tg['network'])) $this->campaign->setNetworkTargeting($tg['network']);
    if (isset($tg['languages'])) $this->campaign->setLanguages($tg['languages']);
    if (isset($tg['countries']) || isset($tg['regions'])) {
      if (isset($tg['countries'])) foreach ($tg['countries'] AS $cidx=>$ctry) if ($ctry=='' || $ctry=='*') unset ($tg['countries'][$cidx]);
      $geo=Array('countries'=>$tg['countries'],'regions'=>Array(),'metros'=>Array(),'cities'=>Array());
      foreach($tg['regions'] AS $t) {
        preg_match('/^([MC]):(.*)/',$t,$p);
        $geo[$p?($p[1]=='M'?'metros':'cities'):'regions'][]=$p?$p[2]:$t;
      }
      $this->campaign->setGeoTargets(Array('countryTargets'=>Array('countries'=>$geo['countries']),'regionTargets'=>Array('regions'=>$geo['regions']),'metroTargets'=>Array('metros'=>$geo['metros']),'cityTargets'=>Array('cities'=>$geo['cities'])));
    }
    return true;
  }
  function getSchedule($t24h=true) {
    $rs=Array();
    $sch=$this->campaign->getAdScheduling();
    $tz_row=tep_db_fetch_array(tep_db_query("SELECT obj_key FROM adwords_cache WHERE obj_type='Z' AND obj_parent='".$this->getId()."'"));
    $tz=$tz_row?$tz_row['obj_key']:-5;
    if ($sch['status']!='Enabled') {
      for ($i=0;$i<7;$i++) $rs[]=Array('weekday'=>$i,'start_hour'=>0,'end_hour'=>24,'multiplier'=>1,'timezone'=>$tz);
    } else {
      $wknum=Array('Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6);
      foreach ($sch['intervals'] AS $intv) $rs[]=Array('multiplier'=>$intv['multiplier'],'start_hour'=>$intv['startHour']+$intv['startMinute']/60,'end_hour'=>$intv['endHour']+$intv['endMinute']/60,'weekday'=>$wknum[$intv['day']],'timezone'=>0);
      $rs=$this->adjustIntervals($rs,$tz);
    }
    return $rs;
  }
  function setSchedule($sch) {
    $tz=$sch[0]['timezone'];
    tep_db_query("REPLACE INTO adwords_cache (obj_type,obj_id,obj_parent,obj_key) VALUES ('Z','".$this->getId()."','".$this->getId()."','$tz')");
    $wdays=Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
    $wpart=$wdays;
    foreach ($sch AS $s) if ($s['start_hour']==0 & $s['end_hour']==24 && $s['multiplier']==1) unset($wpart[$s['weekday']]);
    if (!$wpart) return $this->campaign->setAdScheduling(Array('status'=>'Disabled','intervals'=>Array()));
    $intv=Array();
    foreach ($this->adjustIntervals($sch,0) AS $sc) {
      $startm=floor($sc['start_hour']*60+0.5);
      $endm=floor($sc['end_hour']*60+0.5);
      $intv[]=Array('multiplier'=>$sc['multiplier'],'startHour'=>floor($startm/60),'startMinute'=>$startm%60,'endHour'=>floor($endm/60)%24,'endMinute'=>$endm%60,'day'=>$wdays[$sc['weekday']]);
    }
//echo '<error>';
//foreach($intv AS $i) echo $i['startHour'].':'.$i['startMinute'].'-'.$i['endHour'].':'.$i['endMinute'].','.$i['day'].' ';
//echo '</error>';
    return $this->campaign->setAdScheduling(Array('status'=>'Enabled','intervals'=>$intv));
  }
  function getOptions() {
    return Array('optimize_ads'=>$this->campaign->getIsEnabledOptimizedAdServing(),'separate_bids'=>$this->campaign->getIsEnabledSeparateContentBids());
  }
  function setOptions($op) {
    if (isset($op['optimize_ads'])) $this->campaign->setIsEnabledOptimizedAdServing($op['optimize_ads']);
    if (isset($op['separate_bids'])) $this->campaign->setIsEnabledSeparateContentBids($op['separate_bids']);
    return true;
  }
  function getStats($start,$end) {
    $st=$this->campaign->getCampaignStats($start,$end);
    return Array('clicks'=>$st['clicks'],'convs'=>$st['conversions'],'imprs'=>$st['impressions'],'cost'=>$st['cost'],'avgpos'=>$st['averagePosition']);
  }
  function getId() {
    return $this->campaign->getId();
  }
  function getName() {
    return $this->campaign->getName();
  }
  function setName($name) {
    if (!$this->campaign) return $this->campaign=APIlity_addCampaign($name,'Paused',date('Y-m-d'),date('Y-m-d',time()+86400*730),10,Array('GoogleSearch'),Array('ALL'),Array('metros'=>Array(),'cities'=>Array(),'countries'=>Array(),'regions'=>Array()));
    return $this->campaign->setName($name);
  }
  function newAd() {
    return new ads_adwords_advert($this->adobj,$this);
  }
  function getRefString() {
    return 'adwords-'.$this->getId();
  }

  function adjustIntervals($schd,$tz) {
    $rs=Array();
    foreach ($schd AS $sc) {
      if ($sc['end_hour']==0) $sc['end_hour']=24;
      $sc['start_hour']+=$tz-$sc['timezone'];
      $sc['end_hour']+=$tz-$sc['timezone'];
      $sc['timezone']=$tz;
      if ($sc['start_hour']<0) {
        $sc2=$sc;
        $sc2['weekday']=($sc2['weekday']+6)%7;
        $sc2['start_hour']+=24;
        $sc2['end_hour']=$sc2['end_hour']<0?$sc2['end_hour']+24:24;
        $this->fuseInterval($rs,$sc2);
        $sc['start_hour']=0;
      }
      if ($sc['end_hour']>24) {
        $sc2=$sc;
        $sc2['weekday']=($sc2['weekday']+1)%7;
        $sc2['end_hour']-=24;
        $sc2['start_hour']=$sc2['start_hour']>24?$sc2['start_hour']-24:0;
        $this->fuseInterval($rs,$sc2);
        $sc['end_hour']=24;
      }
      if ($sc['start_hour']<$sc['end_hour']) $this->fuseInterval($rs,$sc);
    }
    $rss=Array();
    foreach ($rs AS $r) $rss[]=$r;
    return $rss;
  }
  function fuseInterval(&$schd,$sc2) {
    foreach ($schd AS $sidx=>$sc) {
      if ($sc['weekday']==$sc2['weekday'] && $sc['multiplier']==$sc2['multiplier'] && $sc['start_hour']<=$sc2['end_hour'] && $sc['end_hour']>=$sc2['start_hour']) {
	$sc2['end_hour']=max($sc['end_hour'],$sc2['end_hour']);
	$sc2['start_hour']=min($sc['start_hour'],$sc2['start_hour']);
	unset($schd[$sidx]);
        return $this->fuseInterval($schd,$sc2);
      }
    }
    $schd[]=$sc2;
    return true;
  }

  function fmtTime($h,$m,$f24=false) {
    return $f24?sprintf('%02d:%02d',$h,$m):sprintf('%2d:%02d%1s',(($h+11)%12+1),$m,($h>=12?'p':'a'));
  }
  function updateTracking() {
    $campid=$this->getId();
    $ref=$this->getRefString();
    $qry=tep_db_query("SELECT k.obj_key FROM adwords_cache k,adwords_cache g WHERE k.obj_type='K' AND k.obj_parent=g.obj_id AND g.obj_parent='$campid'");
    $kwds=Array();
    while ($row=tep_db_fetch_array($qry)) $kwds[$row['obj_key']]=$row['obj_key'];
    if (sizeof($kwds)) tep_db_query("REPLACE INTO ad_campaigns (campaign_ref,campaign_title,campaign_keywords) VALUES ('$ref','".addslashes($this->getName())."','".addslashes(join(',',$kwds))."')");
    else tep_db_query("DELETE FROM ad_campaigns WHERE campaign_ref='$ref'");
  }
  function getActive() {
    return $this->campaign->getStatus()=='Active';
  }
  function setActive($ac) {
    return $this->campaign->setStatus($ac?'Active':'Paused');
  }
  function getDeleted() {
    return $this->campaign->getStatus()=='Deleted';
  }
  function getLimits() {
    return Array('maxchars'=>Array('name'=>30),'budget'=>$this->campaign->getDailyBudget(),'start_date'=>$this->outDate($this->campaign->getStartDate()),'end_date'=>$this->outDate($this->campaign->getEndDate()));
  }
  function setLimits($limits) {
    if (isset($limits['budget'])) $this->campaign->setDailyBudget($limits['budget']);
    if (isset($limits['end_date'])) $this->campaign->setEndDate($this->inDate($limits['end_date']));
    return true;
  }
  function remove() {
    $r=APIlity_removeCampaign($this->campaign);
    $this->campaign=NULL;
    return $r;
  }
  function outDate($date) {
    list($y,$m,$d)=split('-',$date);
    return "$m/$d/$y";
  }
  function inDate($date) {
    list($m,$d,$y)=split('[-/]',$date);
    if ($y<100) $y+=2000;
    return sprintf("%4d-%02d-%02d",$y,$m,$d);
  }
}

class ads_adwords_advert {
  function ads_adwords_advert(&$adobj,&$camp,$a=NULL) {
    $this->adobj=&$adobj;
    $this->parent=&$camp;
    $this->adgroup=$a;
    if (!$a) $this->defs=Array();
  }
  function getCampaign() {
    if (!isset($this->parent)) $this->parent=$this->adobj->getCampaign($this->adgroup->getBelongsToCampaignId());
    return $this->parent;
  }
  function getCreativeObj() {
    if (!isset($this->creatives)) $this->creatives=$this->adgroup->getAllAds();
    return $this->creatives;
  }
  function getId() {
    return $this->adgroup->getId();
  }
  function getName() {
    if (!isset($this->adgroup)) return $this->defs['name'];
    return $this->adgroup->getName();
  }
  function setName($name) {
    if (!isset($this->adgroup)) { $this->defs['name']=$name; return true; }
    return $this->adgroup->getName()==$name?true:$this->adgroup->setName($name);
  }
  function getAds() {
    if (!is_array($this->getCreativeObj())) return NULL;
    $ads=Array();
    foreach ($this->creatives AS $cr) if ($cr->getStatus()=='Enabled') $ads[$cr->getId()]=Array('head'=>$cr->getHeadline(),'text'=>$cr->getDescription1()."\n".$cr->getDescription2(),'url'=>$cr->getDisplayUrl());
    return $ads;
  }
  function getAdStats($start,$end) {
    if (!is_array($this->getCreativeObj())) return NULL;
    $adst=Array();
    foreach ($this->creatives AS $cr) {
      $st=$cr->getAdStats($start,$end);
      $adst[$cr->getId()]=Array('clicks'=>$st['clicks'],'convs'=>$st['conversions'],'imprs'=>$st['impressions'],'cost'=>$st['cost'],'avgpos'=>$st['averagePosition']);
    }
    return $adst;
  }
  function setAds($ads) {
    $chkads=Array();
    foreach ($ads AS $ad) {
      if (strlen($ad['head'])>25) return $this->setError("The head line must not be longer than 25 characters");
      if (trim($ad['head'])=='') return $this->setError("The head line must not be blank");
      $lines=preg_split('/\r?\n/',$ad['text']);
      if (!isset($lines[0]) || trim($lines[0])=='') return $this->setError("The ad text first line must not be blank");
      if (!isset($lines[1])) $lines[1]='';
      if ((strlen($lines[0])>35) || (strlen($lines[1])>35) || (isset($lines[2]) && ($lines[2]!=''))) return $this->setError("The ad text must be max 2 lines long with max 35 chars in each line");
      if (strlen($ad['url'])>35) return $this->setError("The display URL must not be longer than 35 characters");
      if (trim($ad['url'])=='') return $this->setError("The display URL must not be blank");
      $match=0;
      foreach ($chkads AS $ckad) if ($ckad['head']==$ad['head'] && $ckad['line1']==$lines[0] && $ckad['line2']==$lines[1] && $ckad['url']==$ad['url']) { $match=1; break; }
      if (!$match) $chkads[]=Array('head'=>$ad['head'],'line1'=>$lines[0],'line2'=>$lines[1],'url'=>$ad['url']);
    }
    if (!$chkads || !$this->finishUpdate()) return $this->setError("Cannot start updating creatives");
    $this->getCreativeObj();
    $old=$this->creatives;
    $new=Array();
    $dest=$this->getUrl();
    foreach ($chkads AS $ad) {
      $newcr=NULL;
      foreach ($old AS $cidx=>$cr) {
        if ($ad['head']==$cr->getHeadline() && $ad['line1']==$cr->getDescription1() && $ad['line2']==$cr->getDescription2() && $ad['url']==$cr->getDisplayUrl() && $dest==$cr->getDestinationUrl()) {
	  if ($cr->getStatus()!='Enabled') $cr->setStatus('Enabled');
	  if ($cr->getDestinationUrl()!=$dest) $cr->setDestinationUrl($dest);
	  $newcr=$cr;
	  unset($old[$cidx]);
	  break;
	}
      }
      if (!$newcr) $newcr=APIlity_addTextAd($this->getId(),$ad['head'],$ad['line1'],$ad['line2'],'Enabled',$ad['url'],$dest);
      if (!$newcr) return $this->setError("Creative update failed");
      $new[]=$newcr;
    }
    foreach ($old AS $cr) {
      APIlity_removeAd($cr);
    }
    return $this->creatives=$new;
  }
  function getLimits() {
    return Array('maxcpc'=>$this->adgroup->getMaxCpc(),'maxcontentcpc'=>$this->adgroup->getMaxContentCpc());
  }
  function setLimits($limits) {
    if (!isset($this->adgroup)) {
      $this->defs['limits']=$limits;
      return true;
    }
    if (isset($limits['maxcpc'])) $this->adgroup->setMaxCpc($limits['maxcpc']);
    if (isset($limits['maxcontentcpc'])) $this->adgroup->setMaxContentCpc($limits['maxcontentcpc']);
  }
  function getUrl() {
    if (!isset($this->desturl) && $this->getCreativeObj() && isset($this->creatives[0])) return $this->creatives[0]->getDestinationUrl();
    if (!isset($this->desturl)) $this->setKey(NULL);
    return $this->desturl;
  }
  function setUrl($url) {
    return $this->desturl=$url;
  }
  function makeKeywordUrl($keyw) {
    if (preg_match('/(.*?)\?(.*)/',$this->getUrl(),$p)) {
      $base=$p[1];
      $qrys=split('&',$p[2]);
    } else {
      $base=$this->getUrl();
      $qrys=Array();
    }
    foreach ($qrys AS $i=>$q) if (preg_match('/^keyw=/',$q)) unset($qrys[$i]);
    $qrys[]='keyw='.urlencode($keyw);
    return "$base?".join('&',$qrys);
  }
  function getKeywords() {
    if (!isset($this->keywds)) {
      $this->keywds=Array();
      $cri=$this->adgroup->getAllCriteria();
      if ($cri) foreach ($cri AS $cr) {
        if ($cr->getCriterionType()=='Keyword') $this->keywds[$cr->getId()]=$cr;
      }
      $kwl=$this->keywds;
      $aid=$this->adgroup->getId();
      $kwdel=Array();
      $qry=tep_db_query("SELECT obj_id,obj_key FROM adwords_cache WHERE obj_type='K' AND obj_parent='$aid'");
      while ($row=tep_db_fetch_array($qry)) {
        if (!isset($kwl[$row['obj_id']])) $kwdel[]=$row['obj_id'];
	else if ($kwl[$row['obj_id']]->getText()==$row['obj_key']) unset($kwl[$row['obj_id']]);
      }
      if (sizeof($kwdel)) tep_db_query("DELETE FROM adwords_cache WHERE obj_type='K' AND obj_id IN (".join(',',$kwdel).") AND obj_parent='$aid'");
      foreach ($kwl AS $kid=>$kwd) tep_db_query("REPLACE INTO adwords_cache (obj_type,obj_id,obj_parent,obj_key) VALUES ('K','$kid','$aid','".addslashes($kwd->getText())."')");
    }
    $rs=Array();
    foreach ($this->keywds AS $key=>$kwd) $rs[$key]=$kwd->getText();
    return $rs;
  }
  function getKeywordLimits() {
    $kwds=$this->getKeywords();
    $maxcpc=Array();
    foreach ($kwds AS $key=>$kwd) {
      $maxcpc[$kwd]=$this->keywds[$key]->getMaxCpc();
      if ($maxcpc[$kwd]<=0) $maxcpc[$kwd]='';
    }
    return Array('maxcpc'=>$maxcpc);
  }
  function setKeywords($kwds,$limits=NULL) {
    if (!$this->finishUpdate()) return NULL;
    $maxcpc=isset($limits['maxcpc'])?$limits['maxcpc']:Array();
    $aid=$this->adgroup->getId();
    $old=$this->getKeywords();
    $newobj=Array();
    foreach ($old AS $oldk=>$oldv) {
      foreach ($kwds AS $newk=>$newv) {
        if ($oldv==$newv) {
	  unset($old[$oldk]);
	  unset($kwds[$newk]);
	  $newobj[$oldk]=$this->keywds[$oldk];
	  if (isset($maxcpc[$newv]) && $newobj[$oldk]->getMaxCpc()!=$maxcpc[$newv]) $newobj[$oldk]->setMaxCpc($maxcpc[$newv]);
	  $kurl=$this->makeKeywordUrl($newv);
	  if ($newobj[$oldk]->getDestinationUrl()!=$kurl) $newobj[$oldk]->setDestinationUrl($kurl);
	  break;
	}
      }
    }
    foreach ($kwds AS $newk=>$newv) {
      $kwobj=APIlity_addKeywordCriterion($newv,$this->adgroup->getId(),'Exact',false,(isset($maxcpc[$newv])?$maxcpc[$newv]:0),'',$this->makeKeywordUrl($newv));
      if ($kwobj) {
        $kid=$kwobj->getId();
        $newobj[$kid]=$kwobj;
	tep_db_query("REPLACE INTO adwords_cache (obj_type,obj_id,obj_parent,obj_key) VALUES ('K','$kid','$aid','$newv')");
      } else return $this->setError("Error creating keyword criterion");
    }
    foreach ($old AS $oldk=>$oldv) APIlity_removeCriterion($this->keywds[$oldk]);
    if (sizeof($old)) tep_db_query("DELETE FROM adwords_cache WHERE obj_type='K' AND obj_id IN (".join(',',array_keys($old)).") AND obj_parent='$aid'");
    $this->keywds=$newobj;
    $camp=$this->getCampaign();
    $camp->updateTracking();
    return true;
  }
  function getKey() {
    if (preg_match('/\?(.*?)(&|$)/',$this->getUrl(),$p) && !preg_match('/^(ref|keyw)=/',$p[1])) return $p[1];
    return NULL;
  }
  function setKey($key) {
    $camp=$this->getCampaign();
    $this->setUrl(HTTP_CATALOG_SERVER.DIR_WS_CATALOG.'?'.($key?$key.'&':'')."ref=".urlencode($camp->getRefString())."&keyw=".urlencode($this->getName()));
  }
  function updateCache() {
    $k=$this->getKey();
    $cid=$this->adgroup->getBelongsToCampaignId();
    $id=$this->adgroup->getId();
    tep_db_query("REPLACE INTO adwords_cache (obj_type,obj_id,obj_parent,obj_key) VALUES ('G','$id','$cid','$k')");
  }
  function getStats($start,$end) {
    $st=$this->adgroup->getAdGroupStats($start,$end);
    return Array('clicks'=>$st['clicks'],'convs'=>$st['conversions'],'imprs'=>$st['impressions'],'cost'=>$st['cost'],'avgpos'=>$st['averagePosition']);
  }
  function getActive() {
    if (!isset($this->adgroup)) return NULL;
    return $this->adgroup->getStatus()=='Active';
  }
  function setActive($f) {
    $st=$f?'Active':'Paused';
    if (!$this->adgroup) return $this->defs['status']=$st;
    $this->adgroup->setStatus($st);
  }
  function getDeleted() {
    return $this->adgroup->getStatus()=='Deleted';
  }
  function finishUpdate() {
    if (!$this->adgroup) {
      $camp=$this->getCampaign();
      $this->adgroup=APIlity_addAdGroup($this->defs['name'],$camp->getId(),$this->defs['status'],$this->defs['limits']['maxcpc'],0);
      if (!$this->adgroup) return NULL;
      if (isset($this->defs['limits']['maxcontentcpc'])) $this->setLimits(Array('maxcontentcpc'=>$this->defs['limits']['maxcontentcpc']));
    }
    $this->updateCache();
    return true;
  }
  function setError($er) {
    return $this->adobj->setError($er);
  }
  function getError() {
    return $this->adobj->getError();
  }
  function remove() {
    $r=APIlity_removeAdGroup($this->adgroup);
    $this->adgroup=NULL;
    return $r;
  }
}

?>