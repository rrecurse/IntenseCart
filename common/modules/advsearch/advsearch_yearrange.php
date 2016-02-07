<?
class advsearch_yearrange extends IXmodule {

  function getName() {
    return "Search by Year Range";
  }
  
  function searchSQLReqParts($args) {
    if (!$args) return Array();
    $ymin=$ymax=$args;
    $aid=$this->getConf('attr_id');
    return Array(
      'from'=>"LEFT JOIN products yrng_mp ON (p.products_id=yrng_mp.master_products_id AND yrng_mp.master_products_id!=yrng_mp.products_id) LEFT JOIN products_attributes yrng_a ON (yrng_a.products_id=yrng_mp.products_id AND yrng_a.options_id='$aid') LEFT JOIN products_options_values yrng_av ON (yrng_a.options_values_id=yrng_av.products_options_values_id AND yrng_av.language_id='{$GLOBALS['languages_id']}')",
//      'where'=>"yrng_av.products_options_values_name IS NULL OR ( TRIM(SUBSTR(yrng_av.products_options_values_name,1,LOCATE('-',yrng_av.products_options_values_name)-1))<='$ymax' AND TRIM(SUBSTR(yrng_av.products_options_values_name,LOCATE('-',yrng_av.products_options_values_name)+1))>='$ymin' )",
      'where'=>"( TRIM(SUBSTR(yrng_av.products_options_values_name,1,LOCATE('-',yrng_av.products_options_values_name)-1))<='$ymax' AND TRIM(SUBSTR(yrng_av.products_options_values_name,LOCATE('-',yrng_av.products_options_values_name)+1))>='$ymin' )",
    );
  }

  function isReady() {
    return true;
  }
  
  function listConf() {
    return Array(
	'attr_id'=>Array('title'=>'Year Attribute ID','desc'=>'','default'=>'1'),
    );
  }
}
?>
