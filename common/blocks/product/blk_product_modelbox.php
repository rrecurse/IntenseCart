<?

class blk_product_modelbox extends IXblock {

  function render(&$body) {

    $this->renderBody($body);
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'make_select':
        return $this->args['mfr_id'];
      case 'attr_select':
        return $this->args['make_id'];
      case 'no_make_select':
        return !$this->args['mfr_id'];
      case 'no_attr_select':
        return !$this->args['make_id'];
      default: return true;
    }
  }
  
  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'mfr_select':
        $this->renderBody($body);
        $this->showOptions(IXdb::read("SELECT * FROM manufacturers ORDER BY manufacturers_name",'manufacturers_id','manufacturers_name'),$this->args['mfr_id']);
	break;
      case 'make_select':
        $this->renderBody($body);
        $this->showOptions(IXdb::read("SELECT DISTINCT products_make FROM products WHERE manufacturers_id='{$this->args['mfr_id']}' AND products_make!='' ORDER BY products_make",'products_make','products_make'),$this->args['make_id']);
	break;
      case 'attr_select':
        $attrid=$args['attr_id'];
        $this->renderBody($body);
	$attrs=IXdb::read("SELECT pov.* FROM products p,products_attributes pa,products_options_values pov WHERE p.manufacturers_id='{$this->args['mfr_id']}' AND p.products_make='{$this->args['make_id']}' AND pa.products_id=p.products_id AND pov.products_options_values_id=pa.options_values_id",'products_options_values_id','products_options_values_name');
//	echo '</select>';
//	echo "SELECT pov.* FROM products p,products_attributes pa,products_options_values pov WHERE p.manufacturers_id='{$this->args['mfr_id']}' AND p.products_make='{$this->args['make_id']}' AND pa.products_id=p.products_id AND pov.products_options_values_id=pa.options_values_id";
//	print_r($attrs);
//	exit;
	$vals=Array();
	foreach ($attrs AS $at) {
	  if (preg_match('|(\d+)\s*(-\s*(\d+))?|',$at,$atp)) {
	    if ($atp[2]) for ($vidx=$atp[1];$vidx<=$atp[3];$vidx++) $vals[$vidx]=$vidx;
	    else $vals[$atp[1]]=$atp[1];
	  }
	}
	asort($vals);
        $this->showOptions($vals);
	break;
      default: $this->renderBody($body);
    }
  }
  
  function HTMLParamsSection($sec,$htargs,$args) {
    switch ($sec) {
      case 'mfr_select':
        $htargs['onChange']='onChange="'.$this->ajaxLoad(NULL,Array('mfr_id'=>'this.value')).'"';
	break;
      case 'make_select':
        $htargs['onChange']='onChange="'.$this->ajaxLoad(Array('mfr_id'=>$this->args['mfr_id']),Array('make_id'=>'this.value')).'"';
	break;
      default:
    }
    return $htargs;
  }
  
  function showOptions($optns,$actv=NULL) {
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