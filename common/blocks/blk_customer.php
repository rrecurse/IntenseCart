<?
// This shit is to be removed asap

class blk_customer extends IXblock {

  function render($body) {
    
  }
  
  function requireContext() {
    return Array();
  }
  
  function getNumSlots() {
    return 4;
  }
  
  function exportContext() {
    $ctxt=$this->context;
    $ctxt['customer']=&$this;
    return $ctxt;
  }

}
?>