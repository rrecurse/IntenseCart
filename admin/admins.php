<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ADMINS);

  $CurUser=GetAdminUser();
  if (!$CurUser) {
    echo "No http auth detected\n";
    tep_exit();
  }

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="js/css.css">
</head>

<body style="margin:0; background-color:#F0F5FB;">
<?php require(DIR_WS_INCLUDES . 'header.php') ?>

<table border="0" width="99%" cellspacing="0" cellpadding="0" align="center">
  <tr>
    <td valign="top">

<table><tr><td width="53"><img src="/admin/images/icons/user-group-new.png" width="48" height="48"></td><td class="pageHeading"><?php echo HEADING_TITLE; ?></td></tr></table>
<?php

	if(is_array($_POST['admin'])) {

		foreach ($_POST['admin'] AS $admkey) {
			$admin = $admkey;

			if($admin!='' && $admin!=$CurUser) { 
				tep_db_query("DELETE FROM ".TABLE_ADMIN_PERMISSIONS." WHERE admin_user='$admkey'");
			}

			if(isset($_POST['password_'.$admkey]) && $_POST['password_'.$admkey]!='') {
				if($admkey == '') { 
					$admin = $_POST['new_admin'];
				}

				if($admin == '') continue;

				$pass = $_POST['password_'.$admkey];

				if($pass != $_POST['reenter_password_'.$admkey]) {
					echo "Password doesn't match for <B>$admin</b>\n";
				} else {
					system(FILENAME_PROG_HTPASSWD.' -b '.DIR_FS_SITE_ADMIN.FILENAME_HTPASSWD." $admin \"$pass\"");
				}

			} else if(isset($_POST['delete_'.$admkey]) && $_POST['delete_'.$admkey]) {

				system(FILENAME_PROG_HTPASSWD.' -D '.DIR_FS_SITE_ADMIN.FILENAME_HTPASSWD." $admin");
				$admin = '';
			}

			if($admin!='') {
				$ins = array();

				if(is_array($_POST['perm_'.$admkey])) {
					foreach($_POST['perm_'.$admkey] AS $p) { 
						$ins[]="('$admin','$p')";
					}
				}

				if(sizeof($ins) > 0) { 
					tep_db_query("INSERT INTO ".TABLE_ADMIN_PERMISSIONS." (admin_user,admin_group) VALUES ".join(',',$ins));
				}

				$d = addslashes($_POST["default_dash_$admkey"]);

				tep_db_query("REPLACE INTO admin_users 
							  SET admin_user = '$admin',
							  default_dash = '$d',
							  default_page = '".$_POST["default_page_$admkey"]."'
							");
			}
		}
	}

	for($i=0; isset($_POST['afile_'.$i]); $i++) {

		if (isset($_POST['afile_group_'.$i])) {
			$afile=$_POST['afile_'.$i];
			tep_db_query("DELETE FROM ".TABLE_ADMIN_FILES." WHERE admin_file = '".$afile."'");
			$ins = array();

			if($_POST['afile_group_'.$i] != '') {
				foreach(split(',',$_POST['afile_group_'.$i]) AS $grp) {
					$grp = trim($grp);
					if($grp!='') $ins[]="('$afile','$grp')";
				}
			}

			if(sizeof($ins) > 0) { 
				tep_db_query("INSERT INTO ".TABLE_ADMIN_FILES." (admin_file,admin_file_group) VALUES ".join(',',$ins));
			}
		}
  }
  

	$admins = array();
	$htpswd = fopen(DIR_FS_SITE_ADMIN.FILENAME_HTPASSWD,'r');
	//$htpswd=fopen('/home/qtbras1/public_html/admin/.htpasswd','r');

	while($line=fgets($htpswd,1024)) {
		if(preg_match('/^([\w\-]+):/',$line,$ar)) $admins[]=$ar[1];
	}

	fclose($htpswd);
	$admins[] = '';
  
	$afiles = array();
	$adir = opendir(DIR_FS_ADMIN);
	
	while($afile=readdir($adir)) {
    	if(preg_match('/^[^\.].*\.php(su)?$/',$afile)) $afiles[] = $afile;
	}
	
	closedir($adir);
	
	sort($afiles);

	$adminflds = array(''=>array('default_dash'=>''));
	$a_query = tep_db_query("SELECT * FROM admin_users");

	while($row=tep_db_fetch_array($a_query)) {
		$adminflds[$row['admin_user']] = $row;
	}
  
	$perm = array();
	$p_query = tep_db_query("SELECT * FROM ".TABLE_ADMIN_PERMISSIONS);

	while($row=tep_db_fetch_array($p_query)) {
		if(!isset($perm[$row['admin_user']])) { 
			$perm[$row['admin_user']] = array();
		}

		$perm[$row['admin_user']][] = $row['admin_group'];
	}

		$afile_group = array();

		$afile_list = array('ALL'=> array());

		$f_query = tep_db_query("SELECT * FROM ".TABLE_ADMIN_FILES);
		while($row = tep_db_fetch_array($f_query)) {

			if(!isset($afile_group[$row['admin_file']])) { 
				$afile_group[$row['admin_file']]=Array();
			}

			$afile_group[$row['admin_file']][] = $row['admin_file_group'];
			if(!isset($afile_list[$row['admin_file_group']])) { 
				$afile_list[$row['admin_file_group']] = array();
			}

			$afile_list[$row['admin_file_group']][] = $row['admin_file'];
		}
  
		$perm_menu = array();
		foreach($afile_list AS $p => $f) {
			$perm_menu[] = array(id => $p, text =>$p);
		}

		$dashlst = array();
		$dash_qry = tep_db_query("SELECT DISTINCT dash_name FROM admin_dash");

		while ($dash_row = tep_db_fetch_array($dash_qry)) {
			$dashlst[] = array('id'=>$dash_row['dash_name'],'text'=>$dash_row['dash_name']);
		}

	  $dash = array();  
?>
    <form name="admins" method="post" <?php echo 'action="' . tep_href_link(FILENAME_ADMINS, '', 'SSL') . '"'; ?>>
<?php
  if (!isset($HTTP_GET_VARS['action']) || ($HTTP_GET_VARS['action']!='admin_files')) {
?>

<?php
	if (defined('CORE_PERMISSION') && CORE_PERMISSION) { ?>
		<p>[<a href="<?php echo tep_href_link(FILENAME_ADMINS,'action=admin_files')?>">Admin Files Groups</a>]</p>
<?php } ?>

    <table width="100%" cellpadding="5" cellspacing="0" border="0" >
		<tr class="dataTableHeadingRow">
			<td class="dataTableHeadingContent">Admin</td>
			<td class="dataTableHeadingContent">Dashboard</td>
			<td class="dataTableHeadingContent">Permissions</td>
			<td class="dataTableHeadingContent">Delete</td>
		</tr>
<?php
     foreach ($admins AS $admin) {

       if(!is_array($perm[$admin])) $perm[$admin] = array();
       $perm_current = array();
       foreach($perm[$admin] AS $p) $perm_current[] = array(id => $p);
?>
	<tr class="dataTableRow <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd');?>">
		<td class="dataTableContent" valign="top" style="padding:10px 10px;">
<?php
	if ($admin=='') {
		echo '<span style="font:bold 11px arial; color:green">New User:</span> &nbsp;' . tep_draw_input_field('new_admin','', 'style="width:86px"');
	} else {
?>
		<b><span<?php echo ($admin==$CurUser ? ' style="color:red"' : '')?>><span style="font:normal 11px arial">Username:</span> <?php echo $admin?></span></b>
<?php 
	}

	echo tep_draw_hidden_field('admin[]',$admin);
?>

	<table border="0" cellspacing="0" cellpadding="2" bgcolor="#C0EFFF" style="padding:5px; text-size:small; margin-top:5px;">
		<tr>
			<td>Password:</td>
			<td><?php echo tep_draw_password_field('reenter_password_'.$admin,'','','size="12"')?></td>
		</tr>
		<tr>
			<td>Confirm:</td>
			<td><?php echo tep_draw_password_field('password_'.$admin,'','','size="12"')?></td>
		</tr>
	</table>
		</td>
		<td class="dataTableContent" style="padding:5px 10px">
			<table cellpadding="5" cellspacing="0" border="0">
				<tr>
					<td>Dashboard:</td> 
					<td><?php echo tep_draw_pull_down_menu('default_dash_'.$admin,$dashlst,isset($adminflds[$admin])?$adminflds[$admin]['default_dash']:'')?></td>
				</tr>
				<tr>
					<td>Default Page:</td>
					<td>
<?php 

	// # Added a directory scan and select option population to eliminate Default page assignment errors.
	// # optimized to exclude anything but .php files and allowed files.

	$allowed_files = array('orders.php', 
							'customers.php',
							'categories.php',
							'affiliate_affiliates.php',
							'affiliate_sales.php',
							'seo-tools.php', 
							'information_manager.php', 
							'stats_sales_report.php', 
							'stats_sales.php', 
							'competitive_pricing_report.php',
							'stats_products_purchased.php',
							'returns_report.php',
							'super_tracker.php',
							'stats_products_backordered.php',
							'stats_customers.php',
							'traffic_details.php',
							'stats_referral_sources.php',
							'banner_manager.php',
							'newsletters.php',
							'vendor_pricing_report.php',
							'whos_online.php',
							);

		echo '<select name="default_page_'.$admin.'">
				<option value="" '.((empty($adminflds[$admin]['default_page'])) ? ' selected="selected"' : '').'">Default</option>';

		foreach (glob("*.php") as $file) {

	        if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'php' && in_array($file, $allowed_files)) {
		        echo '<option value="'.$file.'" '.(($file == $adminflds[$admin]['default_page']) ? ' selected="selected"' : '').'">'.$file.'</option>';	
			}
	    }

		echo '</select>';
?>

</td></tr></table>
      </td>
		<td class="dataTableContent">

<?php 
	echo ($admin==$CurUser) ? join(',',$perm[$admin]) : tep_draw_mselect_menu('perm_'.$admin.'[]',$perm_menu,$perm_current,'style="width:100%"');
?>
		</td>
		<td class="dataTableContent" align="center" style="width:25px">
			<?php echo ($admin==$CurUser || $admin=='')?'&nbsp;':tep_draw_checkbox_field('delete_'.$admin,1)?>
		</td>
    </tr>

<?php } ?>
    </table>
<?php
  } else {
?>

    <h2>Admin Files</h2>
    <p>[<a href="<?php echo tep_href_link(FILENAME_ADMINS,'')?>">Manage Admins Permissions</a>]</p>
    <table border="1">
<?php
	foreach ($afiles AS $akey => $afile) {
     
		if(!is_array($afile_group[$afile])) {  
			$afile_group[$afile] = array();
		}
		
		echo '<tr>
				<td>'. tep_output_string($afile).tep_draw_hidden_field('afile_'.$akey,$afile).'</td>
				<td>'. tep_draw_input_field('afile_group_'.$akey,join(',',$afile_group[$afile])).'</td>
    </tr>';

	}
?>
    </table>
<?php
  }

	echo '<table width="100%" cellpadding="10"><tr><td align="right">' . tep_image_submit('button_update.gif', IMAGE_BUTTON_UPDATE) . '</td></tr></table>';
?>
    </form></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
