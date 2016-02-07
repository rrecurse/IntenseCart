<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


class blk_product_navigation extends IXblock {
	function exportContext() {
		if(!isset($this->prod)) switch ($this->args['dir']) {
			case 'prev':
    	    	$cpath=$this->product->getPath();
        		$this->prod = $this->_findProd($cpath,-1, $this->product->getProductField('master_products_id'), $this->product->getProductField('products_sort_order'));
	
				while (!$this->prod) {
					$cpath = $this->_prevCat($cpath);
					$this->prod = $this->_findProd($cpath,-1);
				}
			break;
	
			case 'next':
			default:
    	    
				$cpath = $this->product->getPath();
        		$this->prod = $this->_findProd($cpath,1,$this->product->getProductField('master_products_id'), $this->product->getProductField('products_sort_order'));
	
				while (!$this->prod) {
					$cpath=$this->_nextCat($cpath);
					$this->prod=$this->_findProd($cpath,1);
				}
			break;
		}

		$ctxt = $this->context;

		if($this->prod) {
			$ctxt['product'] = &$this->prod;
		}

		return $ctxt;
	}


	function _nextCat($cpath,$cat=NULL) {
		
		if($cat) $srt=IXdb::read("SELECT sort_order FROM categories WHERE categories_id='$cat'",NULL,'sort_order');

		$pcat = ($cpath) ? $cpath[sizeof($cpath)-1] : 0;

		$next = IXdb::read("SELECT categories_id 
							FROM categories 
							WHERE parent_id = '$pcat'".($cat?" AND (sort_order>'$srt' OR (sort_order='$srt' AND categories_id>'$cat'))":'')." 
							ORDER BY sort_order,categories_id 
							LIMIT 1
						   ",NULL,'categories_id');
		if($next) {
			$cpath[] = $next;
			return $cpath;
		}

		if(!$cpath) return array();

		return $this->_nextCat(array_slice($cpath,0,sizeof($cpath)-1),$pcat);
	}


	function _prevCat($cpath,$cat=NULL) {
    
		$pcat = ($cpath) ? $cpath[sizeof($cpath)-1] : NULL;

		if(!$cpath || isset($cat)) {

			if($cat){
				$srt = IXdb::read("SELECT sort_order FROM categories WHERE categories_id='$cat'",NULL,'sort_order');
			}

			$next = IXdb::read("SELECT categories_id 
								FROM categories 
								WHERE parent_id='$pcat'".($cat ? " AND (sort_order < '$srt' OR (sort_order = '$srt' AND categories_id < '$cat'))" : '')." 
								ORDER BY sort_order DESC, categories_id DESC 
								LIMIT 1
							   ",NULL,'categories_id');
			if ($next) {
				$cpath[] = $next;
	
				return $this->_prevCat($cpath,0);
			}
			
			return $cpath;
		}

    	return $this->_prevCat(array_slice($cpath,0,sizeof($cpath)-1),$pcat);
	}


	function _findProd($cpath,$dir,$mpid=NULL,$srt=NULL) {

		$sgn = ($dir > 0) ? '>' : '<';

		$asc = ($dir > 0) ? 'ASC' : 'DESC';

		$cat = ($cpath) ? $cpath[sizeof($cpath)-1] : 0;

		$pid = IXdb::read("SELECT p.products_id 
						   FROM products_to_categories p2c,products p 
						   WHERE p2c.categories_id='$cat' 
						   AND p.products_id=p2c.products_id".($mpid ? " AND (p.products_sort_order{$sgn}'{$srt}' OR (p.products_sort_order='$srt' AND p.products_id{$sgn}'$mpid'))":'')." 
						   AND p.products_status = '1'
						   AND p.products_price > 0
						   ORDER BY p.products_sort_order $asc, p.products_id $asc 
						   LIMIT 1
						  ",NULL,'products_id');

		//if ($pid) return IXproduct::load($pid);
    
		if($pid) {
			$this->product_obj=$this->block('blk_product_main');
			$this->product_obj->setContext($this->context,Array());
			$this->product_obj->setData($pid);
			$this->product_obj->cPath=$cpath;
			
			return $this->product_obj;
		}
	
		return NULL;
	}


	function requireContext() {
		return Array('root','product');
	}
}
?>