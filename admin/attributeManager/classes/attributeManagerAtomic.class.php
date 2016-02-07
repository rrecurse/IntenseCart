<?php
/*
  $Id: attributeManager.class.php,v 1.0 21/02/06 Sam West$

  
  

  
  
  Copyright © 2006 Kangaroo Partners
  http://kangaroopartners.com
  osc@kangaroopartners.com
*/

class attributeManagerAtomic extends attributeManager {
	
	/**
	 * Holder for a reference to the session variable for storing temp data
	 * @access private
	 */
	var $arrSessionVar = array();
	
	/**
	 * __constrct - Assigns the session variable and calls the parent construct registers page actions
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $arrSessionVar array - passed by Ref
	 * @return void
	 */
	function attributeManagerAtomic(&$arrSessionVar) {
		
		parent::attributeManager();
		$this->arrSessionVar = &$arrSessionVar;
		
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
		
		$this->arrSessionVar[] = array(
			'options_id' => $optionId, 
			'options_values_id' => $optionValueId,
			'options_values_price' => $price,
			'price_prefix' => $prefix
		);
	}
	
	/**
	 * Adds an existing option value to a product
	 * @see addAttributeToProduct()
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function addOptionValueToProduct($get) {
		$this->addAttributeToProduct($get);
	}
	
	/**
	 * Adds a new option value to the session then to the product
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function addNewOptionValueToProduct($get) {
		$returnInfo = $this->addOptionValue($get);
		$get['option_value_id'] = $returnInfo['selectedOptionValue'];
		$this->addAttributeToProduct($get);
		return false;
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
		foreach($this->arrSessionVar as $id => $res) 
			if(($res['options_id'] == $optionId)) 
				unset($this->arrSessionVar[$id]);
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
		
		foreach($this->arrSessionVar as $id => $res) 
			if(($res['options_id'] == $optionId) && ($res['options_values_id'] == $optionValueId)) 
				unset($this->arrSessionVar[$id]);
	}
	
	/**
	 * Updates the price and prefix in the products attribute table
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @param $get $_GET
	 * @return void
	 */
	function update($get) {
		
		$this->getAndPrepare('option_id', $get, $optionId);
		$this->getAndPrepare('option_value_id', $get, $optionValueId);
		$this->getAndPrepare('price', $get, $price);
		$this->getAndPrepare('prefix', $get, $prefix);
		
		foreach($this->arrSessionVar as $id => $res) {
			if(($res['options_id'] == $optionId) && ($res['options_values_id'] == $optionValueId)) {
				$this->arrSessionVar[$id]['options_values_price'] = $price;
				$this->arrSessionVar[$id]['price_prefix'] = $prefix;
			}
		}
	}
	
	//----------------------------------------------- page actions end
	
	/**
	 * Returns all of the products options and values in the session
	 * @access public
	 * @author Sam West aka Nimmit - osc@kangaroopartners.com
	 * @return array
	 */
	function getAllProductOptionsAndValues($reset = false) {
		if(0 === count($this->arrAllProductOptionsAndValues) || true === $reset) {
			$this->arrAllProductOptionsAndValues = array();
			$allOptionsAndValues = $this->getAllOptionsAndValues();

			$optionsId = null;
			foreach($this->arrSessionVar as $id => $res) {
				if($res['options_id'] != $optionsId) {
					$optionsId = $res['options_id'];
					$this->arrAllProductOptionsAndValues[$optionsId]['name'] = $allOptionsAndValues[$optionsId]['name'];
				}
				$this->arrAllProductOptionsAndValues[$optionsId]['values'][$res['options_values_id']]['name'] = $allOptionsAndValues[$optionsId]['values'][$res['options_values_id']];
				$this->arrAllProductOptionsAndValues[$optionsId]['values'][$res['options_values_id']]['price'] = $res['options_values_price'];
				$this->arrAllProductOptionsAndValues[$optionsId]['values'][$res['options_values_id']]['prefix'] = $res['price_prefix'];
			}
		}
		return $this->arrAllProductOptionsAndValues;
	}

}

?>