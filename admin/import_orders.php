<?php

$fd=fopen('/tmp/fffffffff','a');
fwrite($fd,serialize($_POST)."\n".serialize($_SERVER)."\n".serialize($_FILES)."\n\n");
fclose($fd);


/*
Also - need to create admin-end interface - call it say import_orders.php
It will do the following:
The starting screen is file upload field and selection - import file using profile [select profile - use getProfiles() - see below] - or create a new profile [text field for new profile name]
If import is chosen - do importOrders()
if new profile - do editProfile()
when editProfile() form is submitted - call saveProfile()
That's basically it.
*/

  require('includes/application_top.php');

  $mod=tep_module('orderfeed_csv','orderfeed');

  $page='';

  if ($_POST['importorders_csv']) {
    $result=$mod->editProfile($mod->passValues['edit_filename'],$mod->passValues['edit_profile']);
    if ($result===true) {
      unlink($mod->passValues['edit_filename']);
      $mod->saveProfile();
      $page.="<b>The profile has been successfully saved.</b><hr>";
    } elseif ($result===false) {
      unlink($mod->passValues['edit_filename']);
      $page.="<b>Errors have been encountered:</b><br>".$mod->getErrors()."<hr>";
    } else {
      echo $result;
      exit;
    }
  }

  if (!$_POST['imp_action']) {
    $profiles=$mod->getProfiles();
    $profilesHTML=array();
    foreach($profiles as $profile) {
      $profilesHTML[]=array('display'=>$profile,'value'=>$profile);
    }


    $page.="<link rel='STYLESHEET' href='js/css.css' type='text/css'>\n";
    $page.="<script type='text/javascript' src='/js/prototype.lite.js'></script>\n";
    $page.="<form method='POST' action='".$_SERVER['PHP_SELF']."' enctype='multipart/form-data' style='margin:0'>\n";
    $page.="<blockquote><b>Warning</b>: You must specify UPC or SKU codes for all the inventory items before performing import or export<br><br><b>Step 1: </b>Import comma seperated or tab delimited text or csv file on the left.<br><b>Step 2:</b> Pair the imported values to your shop's relevant feilds.<br><b>Step 3:</b> To edit profile, select a new file on your local computer, then select the profile you'd like to apply a new map too.</blockquote>\n";
    $page.="<table style='width:551px'>";
    $page.="<tr><td style='vertical-align:top; width:50%'>";
    $page.="<table style='border:1px solid #CCCCCC; padding:5px; margin-left:0'>\n";
    $page.="<tr><td><b>Import CSV</b></td></tr>\n";
    $page.="<tr><td>";
    $page.="<table>\n";
    $page.="<tr>";
    $page.="<td>File:</td>";
    $page.="<td><input type='file' name='csv_file' style='width:220px'></td>";
    $page.="</tr>";
    $page.="<tr>";
    $page.="<td>Profile:</td>";
    $page.="<td>".$mod->renderSelect('profile_import',$profilesHTML)."</td>";
    $page.="</tr>";
    $page.="<tr>";
    $page.="<td>&nbsp;</td>";
    $page.="<td><input type=submit name='imp_action' value='Import'></td>";
    $page.="</tr>";
    $page.="</table>";
    $page.="</td></tr>\n";
    $page.="</table>\n";
    $page.="</td><td style='vertical-align:top; width:50%'>";
    $page.="<table style='border:1px solid #CCCCCC; padding:5px; margin-left:5px'>\n";
    $page.="<tr><td><b>Manage Profiles</b></td></tr>\n";
    $page.="<tr><td>";
    $page.="<table>\n";
    $page.="<tr>";
    $page.="<td>CSV file template:</td>";
    $page.="<td colspan=2><input type='file' name='csv_file_edit' style='width:220px'></td>";
    $page.="</tr>";
    $page.="<tr>";
    $page.="<td>Edit existing profile:</td>";
    $page.="<td>".$mod->renderSelect('profile_edit',$profilesHTML)."</td>";
    $page.="<td><input type=submit name='imp_action' value='Edit'></td>";
    $page.="</tr>";
    $page.="<tr>";
    $page.="<td>Create new profile:</td>";
    $page.="<td><input type='text' name='profile_create'></td>";
    $page.="<td><input type=submit name='imp_action' value='Create'></td>";
    $page.="</tr>";
    $page.="</table>";
    $page.="</td></tr>\n";
    $page.="</table>\n";
    $page.="</td></tr></table>";
    $page.="</form>\n";
    echo $mod->renderPage($page,"CSV Import");
    exit;
  }

  if ($_POST['imp_action']=='Import' || $_POST['imp_action']=='ImportBatch') {
    $result=$mod->importOrders($_FILES['csv_file']['tmp_name'],$_POST['profile_import']);
    if ($_POST['imp_action']=='ImportBatch') {
      if ($result) {
        echo "OK\n";
      } else {
        echo "ERROR\n".$mod->getErrors();
      }
    } else {
      if ($result) {
        echo $mod->renderPage("Orders successfully imported!","CSV Import");
      } else {
        echo $mod->renderPage("<b>Errors have been encountered:</b><br>".$mod->getErrors(),"CSV Import");
      }
    }
    exit;
  }

  $profile=NULL;

  if ($_POST['imp_action']=='Edit') {
    $profile=$_POST['profile_edit'];
  }
  if ($_POST['imp_action']=='Create') {
    $profile=$_POST['profile_create'];
  }
  if (get_magic_quotes_gpc()) {
    $profile=stripslashes($profile);
  }

//$filename = tempnam("non-existing-directory", "CSV_import_tmp_");
$filename = tempnam($_SERVER['DOCUMENT_ROOT'].'/admin/tmp/', 'CSV_import_tmp_');
//var_dump($filename);
 if (!$filename) {
    echo $mod->renderPage("Failed creating temporary file!");
    exit;
  }
  if (@!copy($_FILES['csv_file_edit']['tmp_name'],$filename)) {
	echo $mod->renderPage("Failed copying to temporary file $filename <br><br> You must first Browse for your locally saved profile file to edit existing profile saved to the database. Please click back and reload page and correct your issues.");
   	exit;

  }
  $mod->addPassValue("edit_filename",$filename);
  $mod->addPassValue("edit_profile",$profile);
  echo $mod->editProfile($filename,$profile);

?>
