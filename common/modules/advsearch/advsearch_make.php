<?
class advsearch_make extends IXmodule {

  function getName() {
    return "Search by Manufacturer Make";
  }
  
  function searchSQLReqParts($args) {
    if (!$args) return Array();
    $make=addslashes($args);
    return Array(
      'where'=>"p.products_make='$make'",
    );
  }

  function isReady() {
    return true;
  }
  
  function listConf() {
    return Array(
//	'attr_id'=>Array('title'=>'Year Attribute ID','desc'=>'','default'=>'1'),
    );
  }
}
?>
