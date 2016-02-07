<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


class NeweggMarketplace_Client {

  /** @var array */
  public $config = array('ServiceURL' => null,
                         'UserAgent' => 'PHP Client Library/2015-07-15 (Language=PHP5)',
                         'SignatureVersion' => 2,
                         'SignatureMethod' => 'HmacSHA256',
                         'ProxyHost' => null,
                         'ProxyPort' => -1,
                         'MaxErrorRetry' => 3,
						 );

	function __construct($AuthKey, $SecretKey, $config, $applicationName, $applicationVersion, $attributes = null) {

    	$this->AuthKey = $AuthKey;
	    $this->SecretKey = $SecretKey;
		
		if(!is_null($config)) $this->config = array_merge($this->config, $config);

	}

}

?>