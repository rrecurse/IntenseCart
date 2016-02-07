<?php

// (C) 2009 CommerceByte

require_once(DIR_FS_COMMON.'nusoap/lib/nusoap.php');

  class orderfeed_v2k extends IXmodule
  {
    function orderfeed_v2k()
    {
    }
    
    function getName() {
      return 'Visual2K ERP Feed';
    }
    
    function SoapCall($func,$args) {
      if (!$this->soap) $this->soap=new nusoap_client($this->getConf('soap_url'), true, false, false, false, false, 900, 900);
      return $this->soap->call($func,Array('securityToken'=>$this->getConf('security_token'))+$args);
    }
    
    function mapShipVia($method) {
      $via=NULL;
      if ($this->getConf('ship_via')) {
        foreach (split(',',$this->getConf('ship_via')) AS $s) {
	  $sh=split(':',$s);
	  if ($sh[1]==$method) $via=$sh[0];
	}
      }
      if ($via=='') $via=$this->getConf('default_ship_via');
      return $via;
    }

    function mapTax($order,$map,$dflt) {
      $tax=NULL;
      $zones=IXdb::read("SELECT t.tax_zone_id FROM orders_total ot,tax_rates t WHERE ot.orders_id='{$order->orderid}' AND ot.class='ot_tax' AND ot.title LIKE CONCAT('%',t.tax_description,'%')",Array(NULL),'tax_zone_id');
      if ($map) {
        foreach (split(',',$map) AS $s) {
	  $sh=split(':',$s);
	  if (in_array($sh[0],$zones)) $tax=$sh[1];
	}
      }
      if ($tax=='') $tax=$dflt;
      if ($tax=='') return NULL;
      return $tax;
    }
    
    function webErpCustomerData($addr,$order) {
      return Array(
	'Address'=>$addr->getAddress(),
	'Address2'=>$addr->getAddress2(),
	'City'=>$addr->getCity(),
//	'Country'=>$addr->getCountryName(),
	'Country'=>$addr->getCountryCode(),
	'Email'=>$addr->getEmail(),
	'FaxNumber'=>$addr->getFax(),
	'FirstName'=>$addr->getFirstName(),
	'LastName'=>$addr->getLastName(),
	'PhoneNumber'=>$addr->getPhone(),
	'State'=>$addr->getZoneCode(),
        'StoreCode'=>str_replace('*',$order->customer['id'],$this->getConf('store_code')),
	'Zip'=>$addr->getPostCode(),
      );
    }
    
    function sendOrder(&$order) {
      $webOrder=Array(
        'CustomerPO'=>$this->getConf('po_prefix').$order->orderid,
	'DateCancelBy'=>date('Y-m-d\\TH:i:s+00:00',time()+$this->getConf('cancel_by_days')*86400),
	'DateShipBy'=>date('Y-m-d\\TH:i:s+00:00',time()+$this->getConf('ship_by_days')*86400),
	'Freight'=>0,
	'Notes'=>$order->info['comments'],
	'OrderState'=>'Open',
//	'Reference'=>'test',
//	'SONumber'=>1234,
//	'SalesTypeID'=>0,
//	'ShipToID'=>0,
	'ShipViaID'=>$this->mapShipVia($order->info['shipping_method']),
      );
      $webOrder['Tax1ID']=$this->mapTax($order,$this->getConf('tax1_map'),$this->getConf('tax1_id'));
      $webOrder['Tax2ID']=$this->mapTax($order,$this->getConf('tax2_map'),$this->getConf('tax2_id'));
      if ($this->getConf('term_id')!='') $webOrder['TermID']=$this->getConf('term_id');
      
      
      
      $items=Array();
      $wt=0;
      foreach ($order->getProducts() AS $idx=>$pr) {
        $ipc=$pr->product['products_sku'];
	if ($ipc) $items[]=Array(
	  'CategoryID'=>$this->getConf('category_id'),
	  'IPCCode'=>$ipc,
	  'Quantity'=>$order->products[$idx]['qty'],
	);
	$wt+=$order->products[$idx]['qty']*$order->products[$idx]['weight'];
      }
      $webOrder['Freight']=sprintf('%.2d',$wt*0.454);
      $args=Array(
        'regionID'=>$this->getConf('region_id'),
        'webOrder'=>$webOrder,
        'webBillTo'=>$this->webErpCustomerData($order->getBillTo(),$order),
        'webShipTo'=>$this->webErpCustomerData($order->getShipTo(),$order),
        'language'=>'English',
	'items'=>Array('WebErpOrderItemData'=>$items),
      );


//      print_r($args);
      $rs=$this->soapCall('CreateOrderShipToBillToWithItems',$args);
//      print_r($rs);
      if ($rs['CreateOrderShipToBillToWithItemsResult']['DataObject']['ID']) $order->addInfoRef($this->getClass(),'export',$rs['CreateOrderShipToBillToWithItemsResult']['DataObject']['ID']);
      return $rs;
    }

    
    function isReady() {
      return true;
    }
    
    function listConf() {
      $prfs=Array();
      return Array(
        'soap_url'=>Array('title'=>'SOAP URL','type'=>'text'),
        'security_token'=>Array('title'=>'Security Token','type'=>'text'),
        'warehouse_id'=>Array('title'=>'V2K ERP Warehouse ID','type'=>'text','default'=>1),
        'ship_by_days'=>Array('title'=>'Ship By, # days','type'=>'text','default'=>7),
        'cancel_by_days'=>Array('title'=>'Cancel By, # days','type'=>'text','default'=>30),
        'ship_via'=>Array('title'=>'Ship Via mapping, V2K Ship Via ID:IntenseCart Ship Method, ....','type'=>'text'),
        'default_ship_via'=>Array('title'=>'Default Ship Via ID, if the mapping didnt match','type'=>'text','default'=>'0'),
        'tax1_map'=>Array('title'=>'Tax1 ID Map, IntenseCart Tax Zone ID:V2K Tax1 ID,...','type'=>'text','default'=>''),
        'tax1_id'=>Array('title'=>'Default Tax1 ID','type'=>'text','default'=>0),
        'tax2_map'=>Array('title'=>'Tax2 ID Map, IntenseCart Tax Zone ID:V2K Tax2 ID,...','type'=>'text','default'=>''),
        'tax2_id'=>Array('title'=>'Default Tax2 ID','type'=>'text','default'=>1),
        'term_id'=>Array('title'=>'V2K ERP Terms ID','type'=>'text','default'=>0),
        'region_id'=>Array('title'=>'V2K ERP Region ID','type'=>'text','default'=>0),
        'category_id'=>Array('title'=>'V2K ERP Category ID','type'=>'text','default'=>6),
        'store_code'=>Array('title'=>'V2K ERP Store Code, "*" will be replaced with unique customer ID','type'=>'text','default'=>''),
        'po_prefix'=>Array('title'=>'V2K ERP PO Prefix','type'=>'text','default'=>''),
      );
    }
    
    function actionList() {
//      return Array('do_export'=>'Export New Orders');
    }
    function actionPerform($ac) {
      switch ($ac) {
        case 'do_export':
	  $rs=$this->exportNewOrders();
	  if (!$rs) return 'An error occurred';
	  if ($rs['error']) return 'Error: '.$rs['error'];
	  if ($rs['count']==0) return 'No orders to export';
	  return Array("{$rs['count']} orders exported","File: {$rs['file']}");
      }
    }
    
  }

?>