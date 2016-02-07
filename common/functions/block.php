<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


define('TEMPLATE_THEME','default');
define('TEMPLATE_LANGUAGE','en');

$NextBlockID = 1;
$BlkRenderOnce = array();
$BlkTemplateCache = array();

function tep_block($blk) {
  if (!class_exists($blk)) @include_once(DIR_FS_COMMON.'blocks/'.$blk.'.php');
  return @new $blk;
}

class IXblock {
  var $context;
  var $args;
  
  function loadBlock($cls) {
    if (class_exists($cls)) return true;
    if ($GLOBALS['blkFileLoaded'][$cls]) return false;
    $fn=preg_match('/^blk_(\w+?)_/',$cls,$cp)?$cp[1].'/'.$cls:$cls;
    @include_once(DIR_FS_COMMON.'blocks/'.$fn.'.php');
    $GLOBALS['blkFileLoaded'][$cls]=true;
    return class_exists($cls);
  }
  function block($cls) {
    if (IXblock::loadBlock($cls)) return @new $cls;
    return NULL;
  }
  
	function parse($tmpl) {
		$rs = array();
		$stk = array();
		$sp = &$rs;

		$tags = array('div' => 100, 
					  'span' => 1,
					  'td' => 12,
					  'tr' => 13,
					  'table' => 15,
					  'select' => 10,
					  'optgroup' => 9,
					  'option' => 8
					  );

		preg_match_all('#(.*?)(<(/?)('.join('|',array_keys($tags)).')\b([^>]*)>|\$([\w\.]+)(\(([^<>\(\)]*)\))?|(<\!--.*?-->)|$)#si',$tmpl,$p);

		for ($i=0;isset($p[0][$i]);$i++) {

			if($p[1][$i]!='') {
				$sp[]=preg_replace('/\$\s/','$',$p[1][$i]);
			}

			if($p[4][$i]) {

				if ($p[3][$i]) {

					while ($stk) {
						$spr = &$stk[sizeof($stk)-1];
						$sprtag = $spr[sizeof($spr)-1]['tag'];
						if($tags[$sprtag] > $tags[$p[4][$i]]) break;
						$sp=&$spr;
						array_pop($stk);
						if($sprtag == $p[4][$i]) break;
					}
				
				} else {
					$blk = array('tag' => $p[4][$i],
								 'blocks' => array(),
								 'args' => array(),
								 'htargs' => array()
								);

					preg_match_all('#(\w+)(=(([^\s\"]|"[^"]*")*))?#si',$p[5][$i],$pp);
					$htargs = array();

					for ($j=0;isset($pp[0][$j]);$j++) {
			
						$val = str_replace('"','',$pp[3][$j]);

						switch (strtolower($pp[1][$j])) {
							
							case 'ixclass': 
								$blk['class']=$val; 
							break;
						
							case 'ixargs':
								foreach (preg_split('/,/',$val) AS $argl) {
									list($xkey,$xval) = preg_split('/=/',$argl);
									$blk['args'][$xkey]=urldecode($xval);
								}
							break;

							case 'ixsection':
								preg_match('/(([\w\-]*)\.)?(.*)/',$val,$vp);
								$blk['section'] = $vp[3];
								$blk['ref'] = $vp[2];
							break;

							case 'ixtemplate':
								$blk['template'] = $val;
							break;

							case 'id': 
								$blk['id']=$val;
							default: 
								$blk['htargs'][strtolower($pp[1][$j])] = $pp[0][$j];
						}
					}
					
					$sp[] = $blk;
					$stk[] = &$sp;
					$sp = &$sp[sizeof($sp)-1]['blocks'];
				}
			
			} else if ($p[6][$i]!='') {

				$args = array();
				if(preg_match('/\./',$p[6][$i])) list($ref,$var)=explode('.',$p[6][$i]); else {
        $var=$p[6][$i];
//	$ref=$stk?$stk[sizeof($stk)-1][sizeof($stk[sizeof($stk)-1])-1]['id']:NULL;
	$ref=NULL;
      }
      foreach (preg_split('/,/',$p[8][$i]) AS $argl) {
        list($xarg,$xval) = preg_split('/=/',$argl);
	if ($xarg!='') $args[$xarg]=urldecode($xval);
      };
      $sp[]=Array('ref'=>$ref,'var'=>$var,'args'=>$args);
    } else if ($p[9][$i]) $sp[]=$p[9][$i];
  }
  return $rs;
  }
  
  function expandVars($str) {
    return preg_replace('|\$((\w+)\.)?(\w+)(\((.*?)\))?|se','$this->expandVarValue("\3","\2","\5")',$str);
  }
  function expandVarValue($var,$scp,$argl) {
    if ($scp) {
      if (isset($this->context[$scp])) $obj=&$this->context[$scp];
    } else $obj=&$this;
    if (isset($obj)) {
      $args=Array();
      foreach (preg_split('/,/',$argl) AS $al) {
        list ($k,$v) = preg_split('/=/',$al);
	if ($k!='') $args[$k]=urldecode($v);
      }
      return $obj->getVar($var,$args);
    }
    return '$'.$var;
  }

  function setContext($context,$args,$id=NULL) {
    $this->context=&$context;
    $this->args=&$args;
    $this->html_id=$id;
    foreach ($this->requireContext() AS $rf) {
      if (isset($context[$rf])) $this->$rf=&$context[$rf]; else return false;
    }
    return $this->initContext();
  }
  function initContext() {
    return true;
  }

  function makeID() {
    global $NextBlockID,$BlockIDPrefix;
    if (!isset($this->id)) {
		$this->id = (isset($BlockIDPrefix) ? $BlockIDPrefix.'_' : '').$NextBlockID++;	
	}
    return $this->id;
  }

  function renderBody(&$body) {
	global $listing;

    if (isset($body)) foreach ($body AS $blk) {
      if (is_array($blk)) {
	unset($obj);
	$context=$this->exportContext();
	if (isset($blk['class'])) {
	  $obj=$this->block($blk['class']);

	  if (isset($obj) && !$obj->setContext($context,$blk['args'],$blk['id'])) continue;
        } else if (isset($blk['ref'])) {
	  if ($blk['ref']=='') $obj=&$this;
	  else if (isset($context[$blk['ref']])) $obj=&$context[$blk['ref']];
	}
        if (isset($blk['tag'])) {
	  $htargs=Array();
		if (isset($blk['htargs'])){
	  foreach ($blk['htargs'] AS $hk=>$ht) $htargs[$hk]=$this->expandVars($ht);
}
	  $sec=isset($blk['section'])?$blk['section']:NULL;
	  if (!isset($obj) || ($sec?$obj->preRenderSection($sec,$blk['blocks'],$blk['args']):$obj->preRender($blk['blocks']))) {
	    if (isset($blk['template'])) {
	      if ($obj) $body=&$obj->blockTemplate($blk['template'],$blk['section']);
	      else $body=&$this->incTemplate($blk['template']);
	    } else $body=&$blk['blocks'];
	    if (isset($obj)) {
	      $ctag = $sec ? $obj->renderTagSection($sec,$blk['tag'],$htargs,$blk['args']) : $obj->renderTag($blk['tag'],$htargs);
	      if($sec) $obj->renderSection($sec,$body,$blk['args']);
	      else {
	        $obj->renderOnceCall();
	        $obj->render($body);
	      }
	      echo $ctag;
	    } else {
	      echo '<'.$blk['tag'];
	      if ($htargs) echo ' '.join(' ',$htargs);
	      echo '>';
	      $this->renderBody($body);
	      echo '</'.$blk['tag'].'>';
	    }
	  }
	} else {
	  if (!isset($obj) && !isset($blk['ref'])) $obj=&$this;
	  $var=isset($obj)?$obj->getVar($blk['var'],$blk['args']):NULL;
	  if (isset($var)) echo $var;
	  else echo '$'.(isset($blk['ref'])?$blk['ref'].'.':'').$blk['var'];
//	  else print_r($blk);
	}
      } else echo $blk;
    }
  }
  
  function render(&$body) {
    return $this->renderBody($body);
  }
  
  function renderSection($sec,&$body) {
    return $this->renderBody($body);
  }

  function preview(&$body) {
    return $this->renderBody($body);
  }

  function previewSection($sec,&$body) {
    return $this->renderBody($body);
  }
  
  function getVar($var,$args) {
    return isset($this->context['root'])?$this->context['root']->getVar($var,$args):NULL;
  }
  
  function HTMLParams($args) {
    return $args;
  }

  function HTMLParamsSection($sec,$args) {
    return $args;
  }
  
  function renderTag($tag,$htargs) {
    echo '<'.$tag;
    $al=join(' ',$this->HTMLParams($htargs));
    if ($al) echo ' '.$al;
    echo '>';
    return '</'.$tag.'>';
  }

  function renderTagSection($sec,$tag,$htargs,$args) {
    echo '<'.$tag;
    $al=join(' ',$this->HTMLParamsSection($sec,$htargs,$args));
    if ($al) echo ' '.$al;
    echo '>';
    return '</'.$tag.'>';
  }

  function renderOnceCall() {
    global $BlkRenderOnce;
    if (isset($BlkRenderOnce[get_class($this)])) return true;
    $BlkRenderOnce[get_class($this)]=true;
    return $this->renderOnce();
  }
  
  function preRender(&$body) {
    return true;
  }

  function preRenderSection($sec,&$body) {
    return true;
  }
  
  function renderOnce() {
  }
  
  function exportContext() {
    return $this->context;
  }
  
  function requireContext() {
    return Array('root');
  }
  
  function getDeps() {
    return Array();
  }
  
  function getSectionInfo() {
    return Array();
  }
  
  function getVarInfo() {
    return Array();
  }
  
  function &loadTemplate($tpl,$theme=NULL,$lang=NULL) {
    if (!$theme) $theme=TEMPLATE_THEME;
    if (!$lang) $lang=TEMPLATE_LANGUAGE;
    if (is_array($tpl)) {
      unset($rs);
      foreach ($tpl AS $t) {
        $rs=&$this->loadTemplate($t,$theme,$lang);
	if (isset($rs)) break;
      }
      return $rs;
    }
    if (!isset($GLOBALS['BlkTemplateCache'][$theme][$lang][$tpl])) {
      $fname=DIR_FS_CATALOG_LAYOUT.'templates/'.$theme.'/'.$lang.'/'.$tpl.'.tp';
      if ($f=@file($fname)) {
        $GLOBALS['BlkTemplateCache'][$theme][$lang][$tpl]=$this->parse(join('',$f));
      }
    }
    return $GLOBALS['BlkTemplateCache'][$theme][$lang][$tpl];
  }
  
  function &incTemplate($tpl) {
    return $this->loadTemplate('include/'.$tpl);
  }
  function &blockTemplate($tpl,$sec) {
    return $this->loadTemplate('blocks/'.preg_replace('/^blk_(.*?)_.*/','\\1/',get_class($this)).get_class($this).'-'.($sec?$sec.'-':'').$tpl);
  }
  function getAjaxContext() {
    return NULL;
  }
  function ajaxLoad($args=NULL,$jargs=NULL,$sec=NULL,$templ=NULL) {
    if (!$templ) $templ='default';
    $post=Array('blk='.get_class($this),"templ=$templ",'blkid='.$this->html_id);
    if ($sec) $post[]='section='.urlencode($sec);
    if ($args) foreach ($args AS $k=>$v) $post[]='arg_'.$k.'='.urlencode($v);
    if ($jargs) foreach ($jargs AS $k=>$v) $post[]='arg_'.$k.'=\'+'.$v.'+\'';
    foreach ($this->context AS $ctx=>$ctxm) {
      $jctx=$this->context[$ctx]->getAjaxContext();
      if (isset($jctx)) {
        $ctxl=Array();
	foreach ($jctx AS $k=>$v) $ctxl[]=urlencode($k).':'.urlencode($v);
        $post[]='ctx_'.$ctx.'='.get_class($this->context[$ctx]).','.join(',',$ctxl);
      }
    }
    $blkid=$this->html_id.($sec?'_'.$sec:'');
    return "new ajax('/ajax-blk.php',{postBody:'".join('&',$post)."',update:$('$blkid')});";
  }
}
?>
