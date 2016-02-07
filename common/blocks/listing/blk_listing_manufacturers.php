<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

class blk_listing_manufacturers extends IXblock {

  function render(&$body) {
    $sort='manufacturers_name';

	// # Detect current pricing group
	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');


	$mfrs_query = "SELECT * FROM manufacturers m 
				   JOIN (
					SELECT DISTINCT p.manufacturers_id 
					FROM products p 
					LEFT JOIN products_groups pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '".$customer_group_id."')
					WHERE p.products_status = 1 
					AND pg.customers_group_price > 0
				   ) AS foo ON foo.manufacturers_id = m.manufacturers_id ORDER BY ". $sort;



  	  $this->mfrs = IXdb::read($mfrs_query,'manufacturers_id');

    $this->renderBody($body);
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'loop':
      case 'content': return !!$this->mfrs;
      case 'nocontent': return !$this->mfrs;
      default: return true;
    }
  }

  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'loop':
        foreach ($this->mfrs AS $mfrid=>$this->mfr) $this->renderBody($body);
	return;
      default: break;
    }
    $this->renderBody($body);
  }
  
  function getVar($var,$args) {
    switch ($var) {
      case 'manufacturers_image':
        return IXimage::tag($this->mfr['manufacturers_image'],$this->mfr['manufacturers_name'],$args['width'],$args['height']);
      case 'manufacturers_href':
        $argls=Array();
	if ($args) foreach ($args AS $k=>$v) $argls[]=urlencode($k).'='.urlencode($v);
        return tep_href_link('index.php','manufacturers_id='.$this->mfr['manufacturers_id'].($argls?'&'.join('&',$argls):''));
      default: if (isset($this->mfr[$var])) return $this->mfr[$var];
    }
    return NULL;
  }
}
?>
