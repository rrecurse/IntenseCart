<?php


function tep_parse_template($tmpl) {
  $rs=Array();
  $stk=Array();
  $sp=&$rs;
  preg_match_all('#(.*?)(<(/?)(div)\b([^>]*)>|\$([\w\.]+)(\(([^<>\(\)]*)\))?|(<\!--.*?-->)|$)#si',$tmpl,$p);
  for ($i=0;isset($p[0][$i]);$i++) {
    if ($p[1][$i]!='') $sp[]=$p[1][$i];
    if ($p[4][$i]) {
      if ($p[3][$i]) {
        while ($stk) {
	  $sp=&$stk[sizeof($stk)-1];
	  array_pop($stk);
	  if ($sp[sizeof($sp)-1]['tag']==$p[4][$i]) break;
	}
      } else {
        $blk=Array('tag'=>$p[4][$i],'blocks'=>Array(),'args'=>Array(),'htargs'=>Array());
        preg_match_all('#(\w+)(=(([^\s\"]|"[^"]*")*))?#si',$p[5][$i],$pp);
	$htargs=Array();
	for ($j=0;isset($pp[0][$j]);$j++) {
	  $val=str_replace('"','',$pp[3][$j]);
	  switch (strtolower($pp[1][$j])) {
	  case 'ixclass': $blk['class']=$val; break;
	  case 'ixargs':
	    foreach (split(',',$val) AS $argl) {
	      list($xkey,$xval)=split('=',$argl);
	      $blk['args'][$xkey]=urldecode($xval);
	    }
	    break;
	  case 'ixsection':
	    preg_match('/(([\w\-]*)\.)?(.*)/',$val,$vp);
	    $blk['section']=$vp[3];
	    $blk['ref']=$vp[2];
	    break;
	  case 'id': $blk['id']=$val;
	  default: $blk['htargs'][strtolower($pp[1][$j])]=$pp[0][$j];
	  }
	}
	$sp[]=$blk;
	$stk[]=&$sp;
	$sp=&$sp[sizeof($sp)-1]['blocks'];
      }
    } else if ($p[6][$i]!='') {
      $args=Array();
      if (preg_match('/\./',$p[6][$i])) list($ref,$var)=explode('.',$p[6][$i]); else {
        $var=$p[6][$i];
//	$ref=$stk?$stk[sizeof($stk)-1][sizeof($stk[sizeof($stk)-1])-1]['id']:NULL;
	$ref=NULL;
      }
      foreach (split(',',$p[8][$i]) AS $argl) {
        list($xarg,$xval)=split('=',$argl);
	if ($xarg!='') $args[$xarg]=urldecode($xval);
      };
      $sp[]=Array('ref'=>$ref,'var'=>$var,'args'=>$args);
    } else if ($p[9][$i]) $sp[]=$p[9][$i];
  }
  return $rs;
}



?>