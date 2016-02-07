<?php
class dbfeed extends IXmoduleSet {
  function getName() {
    return 'Product Feeds';
  }
  function getAllModules() {
    return tep_list_modules('dbfeed');
  }
  
  function adminProductEdit($pid) {

    $fd=tep_db_read("SELECT * FROM dbfeed_products WHERE products_id='$pid'",'dbfeed_class','products_id');
    $fdx=tep_db_read("SELECT * FROM dbfeed_products_extra WHERE products_id='$pid'",Array('dbfeed_class','extra_field'),'extra_value');
?> 
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<?php
    foreach ($this->getModules() AS $key=>$mod) {    
?>
<tr><td><input type="checkbox" name="dbfeed[<?=$key?>]" value="1"<?=$fd[$key]?' checked':''?> onClick="$('dbfeed_extra_<?=$key?>').style.display=this.checked?'':'none'"></td><td><?=$mod->getName()?></td></tr>
<tr id="dbfeed_extra_<?=$key?>"<?=$fd[$key]?'':' style="display:none"'?>><td>&nbsp;</td><td><? if (method_exists($mod,'adminProductEdit')) $mod->adminProductEdit($pid,$fdx[$key])?></td></tr>
<?php
    }
?>
</table>
<?php
  }

	function adminProductSave($pid) {
		tep_db_query("DELETE FROM dbfeed_products WHERE products_id='$pid'");
    	if (isset($_POST['dbfeed'])) {
			foreach ($_POST['dbfeed'] AS $k=>$flg) {
    	  		tep_db_query("INSERT INTO dbfeed_products SET dbfeed_class ='$k', products_id = '$pid'");
      			tep_db_query("DELETE FROM dbfeed_products_extra WHERE products_id='$pid' AND dbfeed_class='$k'");

				if (isset($_POST['dbfeed_extra']) && isset($_POST['dbfeed_extra'][$k])) {
					foreach ($_POST['dbfeed_extra'][$k] AS $f=>$v) {
					   tep_db_query("INSERT INTO dbfeed_products_extra (dbfeed_class,products_id,extra_field,extra_value) VALUES ('$k','$pid','$f','$v')");
					}
				}
	    	} // # END foreach $_POST
		}
	}

}
?>
