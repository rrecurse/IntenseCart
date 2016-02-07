<?

class blk_product_modelboxcat extends IXblock {

  function render(&$body) {
    $this->renderBody($body);
  }

  function preRenderSection($sec,&$body,$args) {
    $cats=$this->args['cat_id']==''?Array() : preg_split('/_/',$this->args['cat_id']);
    switch ($sec) {
      case 'cat_select':
      case 'cat_select_ok':
        return ($args['depth']<=1 || $cats[$args['depth']-2]);
      case 'no_cat_select':
        return ($args['depth']>1 && !$cats[$args['depth']-2]);
      case 'mfr_selected':
        return $this->args['mfr_id'];
      case 'no_mfr_selected':
        return !$this->args['mfr_id'];
      default: return true;
    }
  }
  
  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'cat_select':
        $this->renderBody($body);
	$cats = preg_split('/_/',$this->args['cat_id']);
	$depth=$args['depth']-1;
	$parent=$depth>0?$cats[$depth-1]:0;
	if ($this->args['mfr_id']) {
	  if (!isset($this->catlst)) {
	    $this->catlst=Array();
	    $catrows=IXdb::read("SELECT c.categories_id,c.parent_id,cd.categories_name FROM products p, products_to_categories p2c, categories c, categories_description cd WHERE p2c.products_id=p.products_id AND c.categories_id=cd.categories_id AND cd.language_id='{$GLOBALS['languages_id']}' AND p2c.categories_id=c.categories_id AND p.manufacturers_id='".addslashes($this->args['mfr_id'])."' GROUP BY c.categories_id",'categories_id');
	    foreach ($catrows AS $cid=>$c) $this->catlst[$cid]=Array($c);
	    while (1) {
	      $parents=Array();
	      foreach ($this->catlst AS $cid=>$cl) if ($cl[0]['parent_id']) $parents[$cl[0]['parent_id']]=$cl[0]['parent_id'];
	      if (!$parents) break;
	      $catrows=IXdb::read("SELECT c.categories_id,c.parent_id,cd.categories_name FROM categories c LEFT JOIN categories_description cd ON (c.categories_id=cd.categories_id AND cd.language_id='{$GLOBALS['languages_id']}') WHERE c.categories_id IN (".join(',',$parents).")",'categories_id');
	      foreach ($this->catlst AS $cid=>$cl) if ($cl[0]['parent_id'] && $catrows[$cl[0]['parent_id']]) array_unshift($this->catlst[$cid],$catrows[$cl[0]['parent_id']]);
	    }
	  }
	  $catnames=Array();
	  list($lmin,$lmax) = preg_split('/-/',$args['depth']);
	  if (!isset($lmax)) $lmax=$lmin;
	  foreach ($this->catlst AS $cid=>$cl) {
	    $cn=Array();
	    $lcid=Array();
	    $mx=sizeof($cl);
	    if ($mx<$lmin) continue;
	    if ($lmax && $lmax<$mx) $mx=$lmax;
	    $f=true;
	    for ($cidx=0;$cidx<$lmin-1;$cidx++) if ($cl[$cidx]['categories_id']!=$cats[$cidx]) $f=false;
	    if (!$f) continue;
	    for ($cidx=$lmin-1;$cidx<$mx;$cidx++) {
	      $cn[]=$cl[$cidx]['categories_name'];
	      $lcid[]=$cl[$cidx]['categories_id'];
	    }
	    $catnames[join('_',$lcid)]=join($args['separator'],$cn);
	  }
	  asort($catnames);
	  $this->showOptions($catnames,$cats[$depth]);
	} else $this->showOptions(IXdb::read("SELECT cd.categories_id,cd.categories_name FROM categories c,categories_description cd WHERE c.parent_id='$parent' AND c.categories_status='1' AND c.categories_id=cd.categories_id AND cd.language_id='{$GLOBALS['languages_id']}' GROUP BY cd.categories_id ORDER BY c.sort_order, cd.categories_name",'categories_id','categories_name'),$cats[$depth]);
	break;
      case 'mfr_select':
        $this->renderBody($body);
        $this->showOptions(IXdb::read("SELECT * FROM manufacturers ORDER BY manufacturers_name",'manufacturers_id','manufacturers_name'),$this->args['mfr_id']);

	break;
      default: $this->renderBody($body);
    }
  }
  
  function HTMLParamsSection($sec,$htargs,$args) {
    switch ($sec) {
      case 'cat_select':
        $htargs['onChange']='onChange="'.htmlspecialchars($this->ajaxLoad(Array('mfr_id'=>$this->args['mfr_id']),Array('cat_id'=>($args['depth']>1?tep_js_quote(join('_',array_slice(preg_split('/_/',$this->args['cat_id']),0,$args['depth']-1)).'_').'+':'').'this.value'))).'"';
	break;
      case 'mfr_select':
        $htargs['onChange']='onChange="'.htmlspecialchars($this->ajaxLoad(Array(),Array('mfr_id'=>'this.value'))).'"';
	break;
      default:
    }
   return $htargs;
  }
  
  function showOptions($optns,$actv) {
    foreach ($optns AS $k=>$v) echo '<option value="'.htmlspecialchars($k).'"'.($k==$actv?' selected':'').'>'.htmlspecialchars($v).'</option>';
  }
    
  function exportContext() {
    $ctxt=$this->context;
    if ($this->args['prod_id']) {
      $this->product_obj=$this->block('blk_product_main');
      $this->product_obj->setContext($this->context,Array());
      $this->product_obj->setData(IXdb::read("SELECT * FROM products WHERE products_id='{$this->args['prod_id']}'"));
      $ctxt['main_product']=&$ctxt['product'];
      $ctxt['product']=&$this->product_obj;
    }
    return $ctxt;
  }
  function getVar($var,$args) {
    switch ($var) {
      default:
        return NULL;
    }
  }
}
?>
