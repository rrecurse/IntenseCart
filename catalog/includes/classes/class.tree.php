<?php
class _tree_struct {
	// Structure table and fields
	protected $table	= "";
	protected $fields	= array(
			"id"		=> false,
			"parent_id"	=> false,
			"position"	=> false,
			"left"		=> false,
			"right"		=> false,
			"level"		=> false
		);

	// Constructor
	function __construct($table = "tree", $fields ) {
		$this->table = $table;
		global $languages_id;
		if(!count($fields)) {
			foreach($this->fields as $k => &$v) { $v = $k; }
		}
		else {
		

			foreach($fields as $key => $field) {
				switch($key) {
					case "id":
					case "parent_id":
					case "position":
					case "left":
					case "right":
					case "level":
						$this->fields[$key] = $field;
						break;
				}
			}
		}
		// Database
		$this->db = new _database;
	}

	function _get_node($id) {
		global $languages_id;
                list ($type, $numid) = explode("_", $id);

		
		if ($type=="c")  {
		 list ($cid, $parentid) = explode("-", $numid);
		 return tep_db_fetch_array(tep_db_query("select concat('c_', c.categories_id) as id, c.parent_id, c.sort_order as position, cd.categories_name as title, 'folder' as type from categories c left join categories_description cd on cd.categories_id=c.categories_id where language_id='".(int)$languages_id."' and c.categories_id=".$cid));
		}		

		if ($type=="p") { 
		 return array();
//		  list ($pid, $cid) = explode("-", $numid);
//		  return tep_db_fetch_array(tep_db_query("select concat('p_', p.products_id) as id, ptc.categories_id as parent_id, pd.products_name as title,  0 as position, 'default' as type from products p left join products_description pd on pd.products_id=p.products_id left join products_to_categories ptc on ptc.products_id=p.products_id where language_id='".(int)$languages_id."' and p.products_id=".$pid));
		}
	}
	function _get_children($id, $recursive = false) {

		global $languages_id;
		
		$children = array();

        list ($type, $catid) = explode("_", $id);
		list ($numid, $parentid) = explode("-", $catid);
		

		if ($type=='p') return $children;
		if ($type=='node') $numid=0;

		if($recursive) {
			//todo
			#$node = $this->_get_node($id);
			#$this->db->query("SELECT `".implode("` , `", $this->fields)."` FROM `".$this->table."` WHERE `".$this->fields["left"]."` >= ".(int) $node[$this->fields["left"]]." AND `".$this->fields["right"]."` <= ".(int) $node[$this->fields["right"]]." ORDER BY `".$this->fields["left"]."` ASC");
		}
		else {
			//$this->db->query("SELECT `".implode("` , `", $this->fields)."` FROM `".$this->table."` WHERE `".$this->fields["parent_id"]."` = ".(int) $id." ORDER BY `".$this->fields["position"]."` ASC");

		$dbres=tep_db_query("select concat('c_', c.categories_id) as id, c.parent_id, concat('".DIR_WS_CATALOG_IMAGES."', c.categories_image) as image, c.last_modified as mdate, c.sort_order as position, cd.categories_name as title, 'folder' as type from categories c left join categories_description cd on cd.categories_id=c.categories_id where language_id='".(int)$languages_id."' and c.parent_id=".$numid." order by position asc ");
		while ($row=tep_db_fetch_array($dbres))
		  $children[$row['id']]=$row;

//		$dbres=tep_db_query("select concat('p_', p.products_id) as id, p.products_price as price, p.products_status as pstatus, ptc.categories_id as parent_id, concat('".DIR_WS_CATALOG_IMAGES."', p.products_image) as image, products_last_modified as mdate, pd.products_name as title, 0 as position, 'default' as type from products p left join products_description pd on pd.products_id=p.products_id left join products_to_categories ptc on ptc.products_id=p.products_id where ptc.categories_id='".$numid."' and language_id='".(int)$languages_id."'  ");
//		while ($row=tep_db_fetch_array($dbres)) 
//		  $children[$row['id']]=$row;

		}
		//while($this->db->nextr()) $children[$this->db->f($this->fields["id"])] = $this->db->get_row("assoc");
		return $children;
	}
	function _get_path($id) {
		//todo wtf is this?
		$node = $this->_get_node($id);
		$path = array();
		if(!$node === false) return false;
		$this->db->query("SELECT `".implode("` , `", $this->fields)."` FROM `".$this->table."` WHERE `".$this->fields["left"]."` <= ".(int) $node[$this->fields["left"]]." AND `".$this->fields["right"]."` >= ".(int) $node[$this->fields["right"]]);
		while($this->db->nextr()) $path[$this->db->f($this->fields["id"])] = $this->db->get_row("assoc");
		return $path;
	}

	function _create($parent, $position, $type, $title) {
	return false;	
	}

	function _remove($id) {

	return false;
	}
	
	function _product_move ($products_id, $old_parent_id, $new_parent_id)
	{
	return false;
	}

	function _product_copy_link($products_id, $current_category_id, $categories_id)
	{
	return false;
	}
	function _product_copy_duplicate ($products_id, $categories_id)
	{
	return false;
	}
	function _category_move ($categories_id, $new_parent_id, $position)
	{
	return false;
	}

  function _category_copy_duplicate($parent_id = '0', $new_parent_id, $position='') {
	return false;
  }

	function _fix_copy($id, $position) {
	return false;
	}

	function _reconstruct() {
	return false;
	}

	function _analyze() {
	return false;
	}

	function _dump($output = false) {
	return false;
	}
	function _drop() {
	return false;
	}

}
class json_tree extends _tree_struct {
	function __construct($table = "tree", $fields = array(), $add_fields = array("title" => "title", "type" => "type")) {
		parent::__construct($table, $fields);
		$this->fields = array_merge($this->fields, $add_fields);
		$this->add_fields = $add_fields;
	}

	function create_node($data) {
	return false;
	}
	function set_data($data) {
	return false;
	}
	function rename_node($data) { 
	return false;
	}

	function move_node($data) { 
	return false;
	}
	function remove_node($data) {
	return false;
	}
	function get_children($data) {
		
		global $currencies;
		$result = array();
		
		if($data["id"] == "node_1") die ('[{"attr":{"id":"node_0","rel":"drive"},"data":"Catalog","state":"closed"}]');//todo fix dirty code

		$tmp = $this->_get_children($data["id"]);
		foreach($tmp as $k => $v) {
			list ($vtype, $vnumid) = explode("_", $v['id']);
			$state="";
			if ($vtype!="p" && tep_has_category_subcategories($vnumid)) $state="closed";

			if ($vtype=="p" || $vtype=="c") $id=$k."-".$v['parent_id'];
			else $id=$k;
			if ($v["pstatus"]==1) $pstatus="Available";
			if ($v["pstatus"]==0) $pstatus="Not Available";
			if (!is_numeric($v["pstatus"])) $pstatus = "";

			$result[] = array(
				"attr" => array("id" => $id, "rel" => $v[$this->fields["type"]]),
				"data" => $v[$this->fields["title"]],
				"mdate" => $v["mdate"],
				"image" => $v["image"],
				"pstatus" => $pstatus,
				"price" => (strlen($v["price"])) ? $currencies->format($v["price"]) : "",
				"state" => $state
			);
		}
		return json_encode($result);
	}
	function search($data) {
		global $languages_id;
		$result=array();
//		$dbres=tep_db_query("select pd.products_id, ptc.categories_id from products_description pd left join products_to_categories ptc on ptc.products_id=pd.products_id where pd.products_name like '%".$data["search_str"]."%' and language_id=$languages_id");
	
		$dbres=tep_db_query("select pd.products_id from products_description pd where pd.products_name like '%".$data["search_str"]."%' and language_id=$languages_id");
		while ($row=tep_db_fetch_array($dbres)) {
	         $tmp=tep_generate_category_path2($row['products_id'], "product");
		 foreach ($tmp as $k=>$v)
		 {
		  foreach ($v as $ar)
		  if (!in_array("#c_".$ar['id']."-".$ar['pid'], $result)) $result[]="#c_".$ar['id']."-".$ar['pid'];
		 }
		} 
		 $tmp=array();
		
	$dbres=tep_db_query("select cd.categories_id from categories_description cd where cd.categories_name like '%".$data["search_str"]."%' and language_id=$languages_id"); 
		while ($row=tep_db_fetch_array($dbres)) {
	         $tmp=tep_generate_category_path2($row['categories_id'], "category");
		 foreach ($tmp as $k=>$v)
		 {
		  foreach ($v as $ar)
		  if (!in_array("#c_".$ar['id']."-".$ar['pid'], $result)) $result[]="#c_".$ar['id']."-".$ar['pid'];
		 }

		} 

		if (count($result)==0) return "[]"; //todo: clean
		array_unshift($result, '#node_0');
		return json_encode($result);
	}

	function _create_default() {
		$this->_drop();
		$this->create_node(array(
			"id" => 1,
			"position" => 0,
			"title" => "Catalog",
			"type" => "drive"
		));

	}
}

?>