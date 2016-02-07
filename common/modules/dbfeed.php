<?php

class dbfeed extends IXmoduleSet {

	function getName() {
		return 'Product Feeds';
	}


	function getAllModules() {
	    return tep_list_modules('dbfeed');
	}
  

	function adminProductEdit($pid) {
	
	    $fd = tep_db_read("SELECT * FROM ". TABLE_DBFEED_PRODUCTS ." 
						   WHERE products_id='$pid'",'dbfeed_class','products_id'
						  );
		$fdx = tep_db_read("SELECT * FROM ". TABLE_DBFEED_PRODUCTS_EXTRA ." 
							WHERE products_id = '$pid'", array('dbfeed_class','extra_field'),'extra_value'
							);	
	
?> 
		<table width="100%" border="0" cellspacing="3" cellpadding="0">
<?php
		$color = true;
		$i = 0;
	    foreach ($this->getModules() AS $key=>$mod) {    
		$i++;

		echo '<tr class="'. (($color = !$color) ? 'tabEven' : 'tabOdd') .'">';
?>
				<td>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" style="border: 1px dashed #e4e4e4" class="feedChannel_<?php echo $i?>">
				<tr>
					<td width="18"><input type="checkbox" name="dbfeed[<?=$key?>][]" value="1" <?php echo $fd[$key] ? 'checked' : ''?> onclick="$('dbfeed_extra_<?php echo $key?>').style.display=(this.checked ? '' : 'none'); (this.checked ? jQuery('body').height(jQuery('body').height()+jQuery('.feedChannel_<?php echo $i?>').height()) : jQuery('body').height(jQuery('body').height()-jQuery('.feedChannel_<?php echo $i?>').height())); setTimeout(contentChanged(),500);"></td>
					<td><?php echo $mod->getName()?></td>
				</tr>
				<tr id="dbfeed_extra_<?php echo $key?>" <?php echo ($fd[$key] ? '': 'style="display:none"');?>>
					<td>&nbsp;</td>
					<td><?php if (method_exists($mod,'adminProductEdit')) $mod->adminProductEdit($pid,$fdx[$key])?></td>
				</tr></table>
				</td>
			</tr>
<?php
		}
	echo '</table>';

  }


	function adminProductSave($pid, $products_sku='') {


		// # rows are deleted on function execute to create clean slate for insertion below.
		// # the main idea behind this is to delete the product from a feed unselected.

		tep_db_query("DELETE FROM dbfeed_products WHERE products_id='$pid'");


		if(isset($_POST['dbfeed'])) { 

		/*
			$dbfeed_extra_detect = tep_db_query("SELECT *
												 FROM " . TABLE_DBFEED_PRODUCTS_EXTRA . "
											     WHERE products_id='".$pid."' 
										   		 AND extra_field = 'sku'
										  		");

			$dbfeed_products_detect = tep_db_query("SELECT *
											 		FROM " . TABLE_DBFEED_PRODUCTS . "
											     	WHERE products_id='".$pid."'
										  			");

			// # next, detect if any dbfeed_amazon_* keys were posted with dbfeed:
			$amazon = preg_match_all('/(dbfeed_amazon_\w{2,})/i', implode('|', array_keys($_POST['dbfeed'])), $result);

			// # fire up a foreach loop, but skip the first key since its amazon_productAds
			foreach( array_slice($result[0], 1) as $key=>$amzFeed ) {

			// # if rows found for the sku, UPDATE it, even if there is already a sku.
				if(mysql_num_rows($dbfeed_extra_detect) > 0) { 


					tep_db_query("UPDATE " . TABLE_DBFEED_PRODUCTS_EXTRA . " 
								  SET extra_value = '".$products_sku."' 
								  WHERE products_id='".$pid."' 
								  AND extra_field = 'sku'
								 ");

				// # if no row found, Add it! (this was the doozy!).
				} else {

					tep_db_query("INSERT INTO " . TABLE_DBFEED_PRODUCTS_EXTRA . " 
								  SET dbfeed_class = '".$amzFeed."', 
								  products_id='".$pid."',
								  extra_field = 'sku', 
								  extra_value = '".$products_sku."'
								");
				}

	
			} // # END foreach
		*/

			foreach ($_POST['dbfeed'] AS $dbfeed_class => $dbfeed_class_value) {


    	  		tep_db_query("INSERT IGNORE INTO dbfeed_products SET dbfeed_class ='".$dbfeed_class."', products_id = '".$pid."'");
      			tep_db_query("DELETE FROM dbfeed_products_extra WHERE products_id='$pid' AND dbfeed_class='".$dbfeed_class."'");

				if(isset($_POST['dbfeed_extra']) && isset($_POST['dbfeed_extra'][$dbfeed_class])) {

					foreach ($_POST['dbfeed_extra'][$dbfeed_class] AS $f=>$v) {
						
						// # Marketplace sku patch for marketplaces requring sku
						if($f == 'sku') $v = $products_sku;


						tep_db_query("INSERT IGNORE INTO dbfeed_products_extra 
									  SET dbfeed_class = '".$dbfeed_class."',
									  products_id = '".$pid."',
									  extra_field = '".$f."',
									  extra_value = '".$v."'
									");
					}
				}
	    	} // # END foreach $_POST
		} // # END isset($_POST['dbfeed'] check
		
	}

}
?>
