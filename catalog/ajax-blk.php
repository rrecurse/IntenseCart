<?
  $no_sts=1;
  require('includes/application_top.php');
  
  $blk=$_REQUEST['blk'];
  $args=Array();
  $ctxl=Array();
  foreach ($_REQUEST AS $k=>$v) {
    if (preg_match('/^arg_(.*)/',$k,$kp)) $args[$kp[1]]=$v;
    else if (preg_match('/^ctx_(.*)/',$k,$kp)) $ctxl[$kp[1]]=$v;
  }
  $GLOBALS['BlockIDPrefix']=$_REQUEST['blkid'];
  $b=IXblock::block($blk);
  if (isset($b)) {
    $root=IXblock::block('blk_ajax');
    $ctxt=Array();
    $ctxt['ajax']=&$b;
    foreach ($ctxl AS $ctx=>$ctxs) {
      $cxv=explode(',',$ctxs);
      $cls=array_shift($cxv);
      $ctxt[$ctx]=IXblock::block($cls);
      if (isset($ctxt[$ctx])) {
        $cxargs=Array();
	foreach ($cxv AS $cxl) {
	  list($k,$v)=explode(':',$cxl);
	  $cxargs[urldecode($k)]=urldecode($v);
	}
	$ctxt[$ctx]->setContext(Array(),$cxargs);
      }
    }
    $ctxt['root']=&$root;
    $ctxt['ajax']=&$root;
//    print_r($ctxt['product']);
    $b->setContext($ctxt,$args,$_REQUEST['blkid']);
    $body=&$b->blockTemplate($_REQUEST['templ'],$_REQUEST['section']);
    if ($_REQUEST['section']) {
      $b->renderSection($_REQUEST['section'],$body,Array());
    } else {
      $b->render($body);
    }
  }
?>