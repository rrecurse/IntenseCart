<?php
class blk_category_main extends IXblock {

	function setData($row) {
		$this->cat_obj=_IXcategory::load($row);
		$this->cid=$this->cat_obj->getID();
	}

	function initContext() {
		if ($this->args['cid']) $this->setData($this->args['cid']);
		return true;
	}

	function getVar($var,$args) {

		switch ($var) {
			case 'categories_image':

		    	return IXimage::tag($this->cat_obj->getImage(),$this->cat_obj->getName(),$args['width'],$args['height']);

			case 'categories_href':

				$argls = array();

				if($args) {
					foreach ($args AS $k => $v) {
						$argls[] = urlencode($k).'='.urlencode($v);
					}
				}

				//if($argls) return HTTP_SERVER.DIR_WS_CATALOG.'index.php?products_id='.$this->product_row['products_id'].'&'.join('&',$argls);

				$href = tep_href_link('index.php','cPath='.join('_',$this->getCPath()).($argls ? '&'.join('&',$argls) : ''));

//if($_SERVER['REMOTE_ADDR'] == '104.162.19.65') error_log(print_r($href,1));

				return $href;

			default:

				return $this->cat_obj->getField($var);
		}

		return NULL;
	}

	function preRenderSection($sec,&$body,$args) {
		if (preg_match('/^if_(.*)/',$sec,$scp)) return isset($this->product_row[$scp[1]]) && $this->product_row[$scp[1]];
		switch ($sec) {
			case 'oldprice': return $this->getOldPrice()!='';
		  default: return NULL;
		}
	}

	function getProductField($fld) {
		return $this->product_row[$fld];
	}

	function exportContext() {
		$ctxt=$this->context;
		$ctxt['category']=&$this;
		return $ctxt;
	}

	function requireContext() {
		return Array();
	}

	function getAjaxContext() {
		return Array('pid'=>$this->pid);
	}

	function getCPath() {
		return $this->cat_obj->getCPath();
  }
}

class _IXcategory {

	function load($row) {

		global $languages_id;

		$obj = new _IXcategory;

		if (is_array($row)) {

	  		$obj->cat_row = $row;

		} else {

	  		$obj->cat_row = tep_db_read("SELECT cd.*, c.* 
										 FROM ". TABLE_CATEGORIES ." c 
										 LEFT JOIN ". TABLE_CATEGORIES_DESCRIPTION." cd ON (cd.categories_id = c.categories_id AND cd.language_id = '". $languages_id ."') 
										 WHERE c.categories_id = '". $row ."'
									   ");
		}

		return $obj;
	}

	function getID() {
		return $this->cat_row['categories_id'];
	}

	function getName() {
		return $this->cat_row['categories_name'];
	}

	function getImage() {
		return $this->cat_row['categories_image'];
	}

	function getField($fld) {
		return $this->cat_row[$fld];
	}

	function getCPath() {
		if ($this->cPath) return $this->cPath;
		$cp=Array();
		for ($cid=$this->getID();$cid;$cid=IXdb::read("SELECT parent_id FROM categories WHERE categories_id='$cid'",NULL,'parent_id')) $cp[]=$cid;
		return $this->cPath=array_reverse($cp);
  
  
		if ($this->context['category']) $cpath=$this->context['category']->getCPath();
		else $cpath=$GLOBALS['cPath']=='0'?Array():split('_',$GLOBALS['cPath']);
		$cpath[]=$this->getID();
		return $cpath;
  }
}
?>