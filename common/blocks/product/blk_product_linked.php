<?

class blk_product_linked extends IXblock {


  function render(&$body) {
    $this->blkid=$this->MakeID();
    $this->renderBody($body);
  }
  
  function renderSection($sec,&$body) {
    $objid=$this->context['models']->makeID();
    $pid=$this->context['product']->getProductField('products_id');
    switch ($sec) {
      case 'checkbox':
        echo '<input type="checkbox" id="products_id_'.$objid.'" name="products_id[]" value="'.$pid.'" onClick="$(\''.$this->blkid.'_content\').style.display=this.checked?\'\':\'none\'; '.$this->context['models']->jsObjectName().'.selectAttr();"><input type="hidden" name="attrs['.$pid.']" id="products_attrs_'.$objid.'" value="">';
	break;
      case 'radio':
        echo '<input type="radio" id="products_id_'.$objid.'" name="products_id[]" value="'.$pid.'" onChange="if (this.form.lnkBlkOff) this.form.lnkBlkOff.style.display=\'none\'; (this.form.lnkBlkOff=$(\''.$this->blkid.'_content\')).style.display=\'\'; '.$this->context['models']->jsObjectName().'.selectAttr();"><input type="hidden" name="attrs['.$pid.']" id="products_attrs_'.$objid.'" value="">';
	break;
      default:
    }
    return $this->renderBody($body);
  }
  
  function HTMLParamsSection($sec,$par) {
    if ($sec=='content') {
      $par['id']='id="'.$this->blkid.'_content"';
      $par['style']=isset($par['style'])?preg_replace('|"$|',';display:none"',$par['style']):'style="display:none;"';
    }
    return $par;
  }
  
}
?>