<?php

require('includes/application_top.php');
require(DIR_WS_LANGUAGES . $language . '/qbi_general.php');
require(DIR_WS_INCLUDES . 'qbi_version.php');
require(DIR_WS_INCLUDES . 'qbi_definitions.php');
require(DIR_WS_INCLUDES . 'qbi_page_top.php');
require(DIR_WS_INCLUDES . 'qbi_menu_tabs.php');

$filenames=array(DIR_FS_SHARE."qbi_input/items.iif",DIR_FS_SHARE."qbi_input/items.IIF",DIR_FS_SHARE."qbi_input/lists.iif",DIR_FS_SHARE."qbi_input/lists.IIF");
if (!isset($stage)) { 
  foreach($filenames as $filename) {
    if (file_exists($filename)) {
      $filefound=1;
	  break;
    }
  }
  if ($filefound==1) { ?>
    <form action="<?php echo $_SERVER[PHP_SELF] ?>" method="post" name="additems">
    <input name="file_name" type="hidden" value="<?php echo $filename ?>" />	
    <input name="stage" type="hidden" value="processfile" /> <?php
    echo SETUP_FILE_FOUND1." $filename".SETUP_FILE_FOUND2; ?>
    <input name="submitfile" type="submit" id="submitfile" value="<?php echo SETUP_FILE_BUTTON ?>" />
    </form><br /><br /> <?php
  } else {
    echo SETUP_FILE_MISSING;
  }
  item_group_list();
} elseif (isset($stage) AND $stage=="processfile") {

// Open, read, and parse iif to import QB items
  $handle = fopen($file_name, "rb");
  unset($iif_refnum);
  echo '<table class="lists">';
  while (($iifread=fgetcsv($handle, 512, "\t"))!==FALSE) {
    if ($iifread[0]=="!INVITEM") {
      $iifheader=$iifread;
      echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
      echo '<tr><th class="colhead">'.PROD_ITEMS."</th><th></th><th></th><th></th></tr>";
      echo '<tr><th class="colhead">'.SETUP_NAME.'</th><th class="colhead">'.SETUP_DESC.'</th><th class="colhead">'.SETUP_ACCT.'</th><th class="colhead">'.SETUP_ACTION."</th></tr>";
    } elseif ($iifread[0]=="INVITEM") {
      $iifdetail=$iifread;
      $iifitem=arraycombine($iifheader,$iifdetail);
      if (($iifitem["INVITEMTYPE"]=="INVENTORY" OR $iifitem["INVITEMTYPE"]=="SERV" OR $iifitem["INVITEMTYPE"]=="PART" OR $iifitem["INVITEMTYPE"]=="DISC" OR $iifitem["INVITEMTYPE"]=="OTHC") AND ($iifitem["HIDDEN"]=="N")) {
        $iif_refnum[]=$iifitem["REFNUM"];
        item_process($iifitem["NAME"],$iifitem["REFNUM"],$iifitem["DESC"],$iifitem["ACCNT"],$iifitem["PRICE"],$iifitem["INVITEMTYPE"]);
      } elseif (($iifitem["INVITEMTYPE"]=="STAX") AND ($iifitem["HIDDEN"]=="N")) {
        tax_group_process($handle);
      } elseif (($iifitem["INVITEMTYPE"]=="GRP") AND ($iifitem["HIDDEN"]=="N")) {
        group_process($iifitem["NAME"],$iifitem["REFNUM"],$iifitem["DESC"],$iifitem["TOPRINT"],$handle,$iifheader);
      }
    }
  }
  if (isset($iif_refnum) AND count($iif_refnum)>=1) {
    item_delete($iif_refnum);
	echo SETUP_SUCCESS."<br />";
  } else {
	echo SETUP_FAIL."<br />";
  }
  echo "</table>";
  fclose($handle);
}
require(DIR_WS_INCLUDES . 'qbi_page_bot.php');
?>