<?php
/*
  By MegaJim
*/

class dbCache {

  function dbCache() {
    $this->tables=Array();
    $this->fields=Array();
    $this->cache=Array();
//    $this->logf=fopen(DIR_WS_IMAGES_CACHE.'db_cache.log','a');
  }
  
  function addtable($key,$table,$idfld,$extra='') {
    if (!isset($this->tables[$key])) $this->tables[$key]=Array('table'=>$table,'id'=>$idfld,'extra'=>$extra);
  }

  function get($id,$fld) {
    $flds=is_array($fld)?$fld:Array($fld);
    $newfld=Array();
    foreach($flds AS $f) {
      if (!isset($this->fields[$f])) {
        $newfld[]=$f;
	$this->fields[$f]=$f;
      }
    }
    if (sizeof($newfld) && sizeof($this->cache)) $this->query(array_keys($this->cache),$newfld);
    if (!isset($this->cache[$id])) $this->query($id,$this->fields);
    if (is_array($fld)) return $this->cache[$id];
    else return $this->cache[$id][$fld];
  }


  function query($ids,$flds) {
    $tabs=Array();
    $flst=Array();
    foreach ($flds AS $f) {
      list($tb,$fl)=preg_split('/[.]/',$f);
      $tabs[$tb]=$tb;
      $flst[]="$f AS `$f`";
    }
    $qry="SELECT ".join(',',$flst);
    $pri='';
    $where='0';
    foreach($this->tables AS $tkey=>$tdata) {
      if ($tabs[$tkey]) {
        if ($pri=='') {
	  $pri=$tdata['id'];
	  $qry.=",$pri AS id FROM ".$tdata['table'].' '.$tkey;
	  $where=$tdata['id'].(is_array($ids) ? " IN ('".join("','",$ids)."')" : "='$ids'").$tdata['extra'];
	} else {
	  $qry.=" LEFT JOIN ".$tdata['table'].' '.$tkey." ON ".$tdata['id']."=$pri".$tdata['extra'];
	}
      }
    }
//    if ($this->logf) fwrite($this->logf,"$qry WHERE $where\n");
    $db_query=tep_db_query("$qry WHERE $where");
    while ($db_row=tep_db_fetch_array($db_query)) {
      if (!isset($this->cache[$db_row['id']])) $this->cache[$db_row['id']]=Array();
      foreach ($db_row AS $f=>$v) $this->cache[$db_row['id']][$f]=$v;
    }
  }


}
?>
