<?php

class IXtracker {

// Called on each page load
// Records the tracking data into php session
  function track() {
    if (!isset($_SESSION['track'])) $_SESSION['track']=Array();
    if (!isset($_SESSION['track_items'])) $_SESSION['track_items']=Array();
    $trk=&$_SESSION['track'];
    $trk['ip_address']=$_SERVER['REMOTE_ADDR'];
    $trk['user_agent']=$_SERVER['HTTP_USER_AGENT'];
    $trk['num_hits']++;
    if (!isset($trk['landing_time'])) $trk['landing_time'] = time();
    $trk['exit_time'] = time();
    if (!isset($trk['landing_url'])) $trk['landing_url']=$_SERVER['REQUEST_URI'];
    if (!isset($trk['referrer_url'])) $trk['referrer_url']=$_SERVER['HTTP_REFERER'];
    $trk['exit_url']=$_SERVER['REQUEST_URI'];
    if (!$trk['customer_id'] && isset($_SESSION['customers_id'])) $trk['customer_id']=$_SESSION['customers_id'];
  }
  
// Add item tracking record to the referenced session
// Types supported:
// PV - Product Viewed
// CV - Category Viewed
// PD - Product in Dropped Cart
// PY - Payment
  function trackItemSession(&$sess,$type,$id,$ct=1) {
    $sess['track_items'][$type][$id]+=$ct;
  }
  
// Add item tracking record to the current session
  function trackItem($type,$id,$ct=1) {
    return IXtracking::trackItemSession($_SESSION,$type,$id,$ct);
  }
  
// Called when a session is deleted
// Records tracking info from the session into the database
  function disposeSession(&$sess) {
    if (!isset($sess['track'])) return NULL;
    $trk=$sess['track'];
    $trk['landing_time'] = date('Y-m-d H:i:s', strtotime($trk['landing_time']));
    $trk['exit_time'] = date('Y-m-d H:i:s',strtotime($trk['exit_time']));
    $tid = IXdb::store('INSERT','tracking',$trk);
    if ($sess['track_items']) foreach ($sess['track_items'] AS $type=>$it) foreach ($it AS $id=>$q) IXdb::store('INSERT','tracking_items',Array('tracking_id'=>$tid,'item_type'=>$type,'item_id'=>$id,'item_count'=>$q));
    return true;
  }
}
?>
