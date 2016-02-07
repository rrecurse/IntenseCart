<?

function display_model_selection($mid,$sel=NULL,$fld='products_make',$css=NULL) {
  $mdl_qry=tep_db_query("SELECT * FROM manufacturers_makes WHERE manufacturers_id='$mid' ORDER BY sort_order");
  $mdlst=Array();
  while ($mdl_row=tep_db_fetch_array($mdl_qry)) $mdlst[]=Array('id'=>$mdl_row['products_make'],'text'=>$mdl_row['products_make']);
  echo tep_draw_pull_down_menu($fld,$mdlst,$sel,($css?'class="'.$css.'"':NULL));
}
?>