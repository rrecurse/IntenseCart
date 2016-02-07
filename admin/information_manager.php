<?php

// ############################################
/*  Copyright (c) 2006 - 2015 IntenseCart eCommerce  */
// ############################################


require('includes/application_top.php');
require(DIR_WS_LANGUAGES . $language . '/' . 'information.php');

require(DIR_WS_CLASSES . 'url_rewrite.php');
$url_rewrite = new url_rewrite;

	$confirm = (int)$_REQUEST['info_title'];
	$information_id = (int)$_REQUEST['information_id'];


	function browse_information () {
		global $languages_id;

        	$query = tep_db_query("SELECT * FROM " . TABLE_INFORMATION . " WHERE languages_id = $languages_id AND SUBSTR(info_title,1,4) != 'inc_' ORDER BY v_order");

			$c = 0;

    	    while ($buffer = mysql_fetch_array($query)) {
				$result[$c] = $buffer;
				$c++;
			}
	
		return $result;
	}

	function browse_includes () {
		global $languages_id;

        	$query = tep_db_query("SELECT * FROM " . TABLE_INFORMATION . " WHERE languages_id = $languages_id AND SUBSTR(info_title,1,4) = 'inc_' ORDER BY v_order");

			$c = 0;

    	    while ($buffer = tep_db_fetch_array($query)) {
				$result[$c] = $buffer;
				$c++;
			}
	
		return $result;
	}


	function read_data ($information_id) {
		$result = tep_db_fetch_array(tep_db_query("SELECT * FROM " . TABLE_INFORMATION . " WHERE information_id='". $information_id ."'"));
		return $result;
	}

	$warning = tep_image(DIR_WS_ICONS . 'warning.gif', WARNING_INFORMATION); 
	$page_updated = false;

	function error_message($error) {

        global $warning;

        switch ($error) {
                case '20':
					return '<tr class="messageStackError"><td>'. $warning . ' ' . ERROR_20_INFORMATION . '</td></tr>';
					$page_updated = false;
				break;

                case '80':
					return '<tr class="messageStackError"><td>'. $warning . ' '. ERROR_80_INFORMATION . '</td></tr>';
					$page_updated = false;
				break;

                default:
					return $error;
					$page_updated = false;
        }
	}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<title>Page Control</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script src="js/jquery-2.0.3.min.js"></script>
</head>

<body style="margin:0; background:transparent;">

<?php include(DIR_FS_CORE . 'admin/' . DIR_WS_INCLUDES . 'header.php');?>


<table border="0" width="100%" cellspacing="0" cellpadding="0">

<tr>
	<td width="100%" valign="top" colspan="2" style="padding:0 10px">
		<table border="0" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td align="right" class="mainheading" colspan="2"><?php echo $language; ?>&nbsp;&nbsp;</td>
			</tr>
<?php
	switch($a_information) {

	case 'Added':
		$data = browse_information();
		$data_inc = browse_includes();
        $title="" . ADD_QUEUE_INFORMATION . " #$no";
		echo tep_draw_form('',FILENAME_INFORMATION_MANAGER, 'a_information=AddSure');
		include('information_form.php');

	break;

	case 'AddSure':
		
		function add_information ($data) {

			global $languages_id;

			if(!empty($data)) { 

				tep_db_query("INSERT INTO " . TABLE_INFORMATION . "
							  SET visible = '".$data[visible]."',
							  v_order = '".$data[v_order]."',
							  info_title = '".$data[info_title]."',
							  description = '".$data[description]."',
							  languages_id = '".$languages_id."'
							 ");
			}

			return mysql_insert_id();
		}


		if ($v_order && $info_title && $description) {
			if((int)$v_order) {

				$information_id = add_information($HTTP_POST_VARS);

                $edit = read_data($information_id);

                $data = browse_information();
				$data_inc = browse_includes();

                $button=array("Update");
                $title="Page Updated: ".$_POST['info_title'];
                //echo form("$PHP_SELF?_information=Update", $hidden);
                echo '<tr class="pageHeading"><td align="center">';
                echo tep_draw_form('updateinfo',FILENAME_INFORMATION_MANAGER, 'a_information=Update');
                echo tep_draw_hidden_field('information_id', "$information_id");
                include('information_form.php');

				$page_updated = true;


			} else {

				$error = '20';
				$page_updated = false;
			}

		} else {
			$error = '80';
			$page_updated = false;
		}

	break;

	case 'Edit':

		if ($information_id) {

			$edit=read_data($information_id);

			$data = browse_information();
			$data_inc = browse_includes();

			$button = array("Update");
			$title="" . EDIT_ID_INFORMATION . " $information_id";
            //echo form("$PHP_SELF?_information=Update", $hidden);

			echo '<tr class="pageHeading"><td align="center">';
			echo tep_draw_form('updateinfo',FILENAME_INFORMATION_MANAGER, 'a_information=Update');

			echo tep_draw_hidden_field('information_id', "$information_id");

			include('information_form.php');

		} else {
			$error = '80';
			$page_updated = false;
		}

        break;

	case 'Update':

		function update_information ($data='', $data_inc='') {

			global $url_rewrite;
	

			// # Add $data[page_title] check so no purging is done unless page name changes

	
			if( ($data[page_title] != $_POST['page_title']) && substr($data[page_title],0 ,4) != 'inc_') { 
				$url_rewrite->purge_item(sprintf('i%d',$data['information_id']));
			}

			if(!empty($data)) { 

		        tep_db_query("UPDATE " . TABLE_INFORMATION . " 
							  SET info_title = '".$data[info_title]."', 
							  description = '".$data[description]."', 
							  page_title = '".$data[page_title]."',
							  htc_description = '".$data[htc_description]."',
							  htc_keywords = '".$data[htc_keywords]."', 
							  visible = '".$data[visible]."',
							  v_order = '".$data[v_order]."' 
							  WHERE information_id = $data[information_id]
							  ");
			}

			if(!empty($data_inc)) { 

		        tep_db_query("UPDATE " . TABLE_INFORMATION . " 
							  SET info_title = '".$data_inc[info_title]."', 
							  description = '".$data_inc[description]."', 
							  page_title = '".$data_inc[page_title]."',
							  visible = '".$data_inc[visible]."',
							  v_order = '".$data_inc[v_order]."' 
							  WHERE information_id = $data_inc[information_id]
							  ");
			}
		}

		if ($information_id && $description && $v_order) {
			if ((int)$v_order) {

				update_information($HTTP_POST_VARS);
		
				$edit = read_data($_POST['information_id']);
	
				$data = browse_information();
				$data_inc = browse_includes();
	
				$button = array("Update");
	
				$title = "Page Updated: ".$_POST['info_title'];
	
				echo '<tr class="pageHeading"><td align="center">';
				echo tep_draw_form('updateinfo',FILENAME_INFORMATION_MANAGER, 'a_information=Update');
				echo tep_draw_hidden_field('information_id', "$information_id");
	
				include('information_form.php');

				$page_updated = true;

				//header('Location: information_manager.php?a_information=Edit&information_id='.$_POST['information_id']);
		
				//exit();

			} else {

				$error="20";
			} 

		} else {
			$error="80";
		}

	break;

	case 'Visible':

		function tep_set_information_visible($information_id, $visible) {

			if ($visible == '1') {
				return tep_db_query("UPDATE " . TABLE_INFORMATION . " SET visible = '0' WHERE information_id = '" . $information_id . "'");
			} else {
				return tep_db_query("UPDATE " . TABLE_INFORMATION . " SET visible = '1' WHERE information_id = '" . $information_id . "'");
			}
		}
	
		tep_set_information_visible($information_id, $visible);

		$data = browse_information();
		$data_inc = browse_includes();

		if ($visible == '1') {
			$vivod = DEACTIVATION_ID_INFORMATION;
		} else {
			$vivod = ACTIVATION_ID_INFORMATION;
		}
    	
		$title="ID: $information_id $confirm $vivod";

		include('information_list.php');
		$page_updated = true;

	break;

	case 'Delete':

		if ($information_id) {
	
			$delete = read_data($information_id);
		
			$data = browse_information();
			$data_inc = browse_includes();
	
			$title = DELETE_CONFITMATION_ID_INFORMATION .  ' ' . $information_id;
	
			echo '<tr class="pageHeading"><td align="center"><br><br>'. $title .'</td></tr>';
			echo '<tr><td></td></tr><tr><td align="center"><br><br>';
	
			echo tep_draw_form('',FILENAME_INFORMATION_MANAGER, 'a_information=DelSure&information_id='.$val[information_id], 'POST');
	
				echo tep_draw_hidden_field('information_id', "$information_id");

				echo tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, '', 'NONSSL') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
	
			echo '</form>

				</td>
			</tr>';

			$page_updated = true;

		} else {

			$error = '80';
			$page_updated = false;

		}

	break;

	case 'DelSure':

		function delete_information ($information_id) {
			mysql_query("DELETE FROM " . TABLE_INFORMATION . " WHERE information_id=$information_id");
		}
    
		if ($information_id) {
			delete_information($information_id);

			$data = browse_information();
			$data_inc = browse_includes();
	
			$url_rewrite->purge_item(sprintf('i%d',$information_id));
		
			$title = "$confirm " . DELETED_ID_INFORMATION . " $information_id " . SUCCED_INFORMATION . "";
			include('information_list.php');
			$page_updated = true;

		} else {

			$error = '80';
			$page_updated = false;
		}
	
	break;

	default:

		$data = browse_information();
		$data_inc = browse_includes();
		$title = "" . MANAGER_INFORMATION . "";
		include('information_list.php');
	}	


	if ($page_updated == true) {
		$messageStack->add_session('Page Updated', 'success');
		$messageStack->add('Page Updated', 'success');
	} elseif($error) {
		$messageStack->add_session(error_message($error), 'error');
		$messageStack->add('Page Not Updated', 'error');
	}

	if($error) {
		$content = error_message($error);
		echo $content;

		$data = browse_information();
		$data_inc = browse_includes();
	
		$title="" . ADD_QUEUE_INFORMATION;
		echo tep_draw_form('',FILENAME_INFORMATION_MANAGER, 'a_information=AddSure');
	
		include('information_form.php');
	}
?>
  </script>
</table>
</td>
</tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
