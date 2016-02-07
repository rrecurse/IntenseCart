<?php
/*
  $Id: attributeManagerInstant.class.php,v 1.0 21/02/06 Sam West$

  
  

  
  
  Copyright © 2006 Kangaroo Partners
  http://kangaroopartners.com
  osc@kangaroopartners.com
*/

class attributeManagerInstant extends attributeManager {
	
	/**
	 * @access private
	 */
	var $intPID;
	
	/**
	 * __construct() assigns pid, calls the parent construct, registers page actions
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $intPID int
	 * @return void
	 */
	function attributeManagerInstant($intPID) {
		
		parent::attributeManager();
		
		$this->intPID = (int)$intPID;
		
		$this->registerPageAction('addAttributeToProduct','addAttributeToProduct');
		$this->registerPageAction('addOptionValueToProduct','addOptionValueToProduct');
		$this->registerPageAction('addNewOptionValueToProduct','addNewOptionValueToProduct');
		$this->registerPageAction('removeOptionFromProduct','removeOptionFromProduct');
		$this->registerPageAction('removeOptionValueFromProduct','removeOptionValueFromProduct');
		$this->registerPageAction('update','update');
		if(AM_USE_SORT_ORDER) {
			$this->registerPageAction('moveOptionUp','moveOptionUp');
			$this->registerPageAction('moveOptionDown','moveOptionDown');
			$this->registerPageAction('moveOptionValueUp','moveOptionValueUp');
			$this->registerPageAction('moveOptionValueDown','moveOptionValueDown');
		}
	}
	
	//----------------------------------------------- page actions

	/**
	 * Adds the selected attribute to the current product
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function addAttributeToProduct($get) {
		
		$this->getAndPrepare('option_id', $get, $optionId);
		$this->getAndPrepare('option_value_id', $get, $optionValueId);
		$this->getAndPrepare('price', $get, $price);
		$this->getAndPrepare('prefix', $get, $prefix);
		
		$data = array(
			'products_id' => $this->intPID,
			'options_id' => $optionId,
			'options_values_id' => $optionValueId,
			'options_values_price' => $price,
			'price_prefix' => $prefix
		);
		
		amDB::perform(TABLE_PRODUCTS_ATTRIBUTES, $data);
	}
	
	/**
	 * Adds an existing option value to a product
	 * @see addAttributeToProduct()
	 */
	function addOptionValueToProduct($get) {
		$this->addAttributeToProduct($get);
	}
	
	/**
	 * Adds a new option value to the database then assigns it to the product
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function addNewOptionValueToProduct($get) {
		$returnInfo = $this->addOptionValue($get);
		$get['option_value_id'] = $returnInfo['selectedOptionValue'];
		$this->addAttributeToProduct($get);
	}
	
	/**
	 * Removes a specific option and its option values from the current product
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function removeOptionFromProduct($get) {
		$this->getAndPrepare('option_id',$get,$optionId);
		amDB::query("delete from ".TABLE_PRODUCTS_ATTRIBUTES." where options_id = '$optionId' and products_id = '$this->intPID'");
	}
	
	/**
	 * Removes a specific option value from a the current product
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function removeOptionValueFromProduct($get) {
		$this->getAndPrepare('option_id',$get,$optionId);
		$this->getAndPrepare('option_value_id',$get,$optionValueId);
		amDB::query("delete from ".TABLE_PRODUCTS_ATTRIBUTES." where options_id = '$optionId' and options_values_id = '$optionValueId' and products_id = '$this->intPID'");
	}
	
	/**
	 * Updates the price and prefix in the products attribute table
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function update($get) {
		
		$this->getAndPrepare('option_id', $get, $optionId);
		$this->getAndPrepare('option_value_id', $get, $optionValueId);
		$this->getAndPrepare('price', $get, $price);
		$this->getAndPrepare('prefix', $get, $prefix);
		
		$data = array( 
			'options_values_price' => $price,
			'price_prefix' => $prefix
		);
		amDB::perform(TABLE_PRODUCTS_ATTRIBUTES,$data, 'update',"products_id='$this->intPID' and options_id='$optionId' and options_values_id='$optionValueId'");

	}
	
	//----------------------------------------------- page actions end
	
	/**
	 * Returns all or the options and values in the database
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @return array
	 */
	function getAllProductOptionsAndValues($reset = false) {
		if(0 === count($this->arrAllProductOptionsAndValues)|| true === $reset) {
			$this->arrAllProductOptionsAndValues = array();
			
			$allOptionsAndValues = $this->getAllOptionsAndValues();
			
			$queryString = "select * from ".TABLE_PRODUCTS_ATTRIBUTES." where products_id = '$this->intPID' order by ";
			$queryString .= !AM_USE_SORT_ORDER ?  "options_id" : AM_FIELD_OPTION_SORT_ORDER.", ".AM_FIELD_OPTION_VALUE_SORT_ORDER;
			$query = amDB::query($queryString);
			
			$optionsId = null;
			while($res = amDB::fetchArray($query)) {
				if($res['options_id'] != $optionsId) {
					$optionsId = $res['options_id'];
					$this->arrAllProductOptionsAndValues[$optionsId]['name'] = $allOptionsAndValues[$optionsId]['name'];
					$this->arrAllProductOptionsAndValues[$optionsId]['track_stock'] = $allOptionsAndValues[$optionsId]['track_stock']; // Trial Rigadin QTPro
					if(AM_USE_SORT_ORDER)
						$this->arrAllProductOptionsAndValues[$optionsId]['sort'] = $res[AM_FIELD_OPTION_SORT_ORDER];
				}
				$this->arrAllProductOptionsAndValues[$optionsId]['values'][$res['options_values_id']]['name'] = $allOptionsAndValues[$optionsId]['values'][$res['options_values_id']];
				$this->arrAllProductOptionsAndValues[$optionsId]['values'][$res['options_values_id']]['price'] = $res['options_values_price'];
				$this->arrAllProductOptionsAndValues[$optionsId]['values'][$res['options_values_id']]['prefix'] = $res['price_prefix'];
				if(AM_USE_SORT_ORDER)
					$this->arrAllProductOptionsAndValues[$optionsId]['values'][$res['options_values_id']]['sort'] = $res[AM_FIELD_OPTION_VALUE_SORT_ORDER];
			}
		}
		return $this->arrAllProductOptionsAndValues;
	}
	
	function moveOptionUp() {
		$this->moveOption();
	}
	
	function moveOptionDown() {
		$this->moveOption('down');
	}
	
	function moveOption($direc = 'up') {
		
	}
	
	function moveOptionValueUp() {
		$this->moveOptionValueUp();
	}
	
	function moveOptionValueDown() {
		$this->moveOptionValueDown();
	}
	
	function moveOptionValue($direc = 'up') {
		
	}
	
	
	
}

?>