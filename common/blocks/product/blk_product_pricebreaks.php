<?
class blk_product_pricebreaks extends IXblock {

  function render(&$body) {
    $pf=new PriceFormatter;
    $pf->loadProduct($this->product->pid);
    
  }

  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      default: $this->renderBody($body);
    }
  }
  
  function requireContext() {
    return Array('root','product');
  }
}
?>