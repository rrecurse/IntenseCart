<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	include('includes/application_top.php');
	header('Content-Type: text/xml');

	function chk_products($optn,$attr,$pid) {

	    global $languages_id;
    	$qry = tep_db_query("SELECT p.master_products_id,
									pd.products_name,
									COUNT(0) AS model_count 
							 FROM ".TABLE_PRODUCTS_ATTRIBUTES." pa,".TABLE_PRODUCTS." p 
							 LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON pd.products_id=p.master_products_id 
								AND pd.language_id='$languages_id' 
							 WHERE pa.options_id = '$optn'".($attr?" AND pa.options_values_id='$attr'":'')." 
							 AND pa.products_id = p.products_id
							 ".($pid ? " AND p.master_products_id != '$pid'" : '')." 
							 AND p.master_products_id !=0 
							 GROUP BY p.master_products_id
							");
		$rs = true;

		while ($row=tep_db_fetch_array($qry)) {
			echo '<product>
					<id>'.$row['master_products_id'].'</id>
					<name>'.htmlspecialchars($row['products_name']).'</name>
					<count>'.$row['model_count'].'</count>
				 </product>';

			$rs = false;
		}

		return $rs;
	}
?>

<attrctl>
<?php 

	$pID = isset($_GET['pID']) ? ($_GET['pID'] + 0) :NULL;

	if(isset($_POST['add_attr']) && preg_match('/^(\d+) : (.*)/',$_POST['add_attr'],$at_parse)) {

		$optn = $at_parse[1]; 
		$atn = $at_parse[2];

		echo "<attribute>
				<attr_name>".htmlspecialchars($atn)."</attr_name>
				<optn_id>$optn</optn_id>";

		$maxqry = tep_db_query("SELECT MAX(products_options_values_id)AS maxid FROM ".TABLE_PRODUCTS_OPTIONS_VALUES);

		$maxrow = tep_db_fetch_array($maxqry);
		$attr = $maxrow['maxid'] + 1;

		if($attr && tep_db_query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS_VALUES." (products_options_values_id,products_options_values_name,language_id) VALUES ('$attr','".addslashes($atn)."','$languages_id')")) {
		
			tep_db_query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS."
						  SET products_options_id = '".$optn."',
						  products_options_values_id = '".$attr."'
						 ");
			echo "<result>added</result>
				  <attr_id>$attr</attr_id>";
		} else {
			echo '<result>error</result>';
		}

		echo '</attribute>';
	}

	if(isset($_POST['del_attr'])) {

		list($optn,$attr) = split(':',$_POST['del_attr']);

		echo "<attribute><attr_id>$attr</attr_id>
			  <optn_id>$optn</optn_id>";

		if(chk_products($optn,$attr,$pID)) {

			tep_db_query("DELETE FROM ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." 
						  WHERE products_options_id = '".$optn."' 
						  AND products_options_values_id = '".$attr."'
						 ");

			$prod_opts_vals_to_prod_opts = tep_db_query("SELECT * FROM ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." 
						  								 WHERE products_options_values_id='$attr'
						 								");

			if(tep_db_num_rows($prod_opts_vals_to_prod_opts) < 1) {
				tep_db_query("DELETE FROM ". TABLE_PRODUCTS_OPTIONS_VALUES ." WHERE products_options_values_id='$attr'");
				echo '<result>deleted</result>';
		    } else {
				echo '<result>error</result>';
			}
		}
		
		echo '</attribute>';

	if(isset($_POST['add_optn'])) {

		$optname = $_POST['add_optn'];

		echo "<option>
				<optn_name>".htmlspecialchars($optname)."</optn_name>";

		$maxqry = tep_db_query("SELECT MAX(products_options_id)AS maxid FROM ".TABLE_PRODUCTS_OPTIONS);

		$maxrow = tep_db_fetch_array($maxqry);

		$optn = $maxrow['maxid']+1;

		$trk = 1;

		if($optn && tep_db_query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS." (products_options_id,products_options_name,language_id,products_options_track_stock) VALUES ('$optn','".addslashes($optname)."','$languages_id','$trk')")) {
      echo "<result>added</result><optn_id>$optn</optn_id>";
    } else echo '<result>error</result>';
    echo '</option>';
  }
  if (isset($_POST['del_optn'])) {
    $optn=$_POST['del_optn'];
    echo "<option><optn_id>$optn</optn_id>";
    if (chk_products($optn,NULL,$pID)) {
      tep_db_query("DELETE FROM ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." WHERE products_options_id='$optn'");
      tep_db_query("DELETE FROM ".TABLE_PRODUCTS_OPTIONS." WHERE products_options_id='$optn'");
      echo '<result>deleted</result>';
    } else echo '<result>error</result>';
    echo '</option>';
  }
  if (isset($_POST['get_attr_imgs']) && preg_match('/^(\d+):(\d+)/',$_POST['get_attr_imgs'],$at_parse)) {
    $optn=$at_parse[1]; $attr=$at_parse[2];
    $img_wd=isset($_GET['img_wd'])?$_GET['img_wd']:24;
    $img_ht=isset($_GET['img_ht'])?$_GET['img_ht']:16;
    echo "<attribute_images><attr_id>$attr</attr_id><optn_id>$optn</optn_id>";
    $imgqry=tep_db_query("SELECT DISTINCT options_image FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE options_id='$optn' AND options_values_id='$attr' AND options_image!=''");
    while ($imgrow=tep_db_fetch_array($imgqry)) echo '<image><name>'.htmlspecialchars($imgrow['options_image']).'</name><tag>'.htmlspecialchars(tep_image(DIR_WS_CATALOG_IMAGES.$imgrow['options_image'],$imgrow['options_image'],$img_wd,$img_ht)).'</tag></image>';
    echo '</attribute_images>';
  }

?>
</attrctl>