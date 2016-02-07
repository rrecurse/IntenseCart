<?php
/*
$Id: qbi_pay.php,v 2.10 2005/05/08 al Exp $

Quickbooks Import QBI
contribution for IntenseCart eCommerce
ver 2.10 May 8, 2005
(c) 2005 Adam Liberman
www.libermansound.com
info@libermansound.com
Please use the IX forum for support.


    This file is part of Quickbooks Import QBI.

    Quickbooks Import QBI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Quickbooks Import QBI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Quickbooks Import QBI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require('includes/application_top.php');
require(DIR_WS_LANGUAGES . $language . '/qbi_general.php');
require(DIR_WS_INCLUDES . 'qbi_version.php');
require(DIR_WS_INCLUDES . 'qbi_definitions.php');
require(DIR_WS_INCLUDES . 'qbi_page_top.php');
require(DIR_WS_INCLUDES . 'qbi_menu_tabs.php');

$filenames=array(DIR_FS_SHARE."qbi_input/payment.iif",DIR_FS_SHARE."qbi_input/payment.IIF",DIR_FS_SHARE."qbi_input/items.iif",DIR_FS_SHARE."qbi_input/items.IIF",DIR_FS_SHARE."qbi_input/lists.iif",DIR_FS_SHARE."qbi_input/lists.IIF");
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
} elseif (isset($stage) AND $stage=="processupdate") {
  pay_listupdate($pay_type);
} elseif (isset($stage) AND $stage=="processfile") {
// Open, read, and parse iif to import QB items
  $handle=fopen($file_name, "rb");
  unset($iif_refnum);
  while (($iifread=fgetcsv($handle, 512, "\t"))!==FALSE) {
    if ($iifread[0]=="!PAYMETH") {
      $iifheader=$iifread;
    } elseif ($iifread[0]=="PAYMETH") {
      $iifdetail=$iifread;
      $iifitem=arraycombine($iifheader,$iifdetail);
      $iif_refnum[]=$iifitem["REFNUM"];
      pay_process($iifitem["NAME"],$iifitem["REFNUM"],$iifitem["HIDDEN"]);
    }
  }
  if (isset($iif_refnum) AND count($iif_refnum)>=1) {
    pay_delete($iif_refnum);
	echo SETUP_SUCCESS."<br />";
  } else {
	echo SETUP_FAIL."<br />";
  }
  fclose($handle);
}
?>
<form action="<?php echo $_SERVER[PHP_SELF] ?>" method="post" name="updatetypes">
<?php pay_listshow(); ?>
<input name="stage" type="hidden" value="processupdate" />
<input name="submitfile" type="submit" id="submitfile" value="<?php echo PAY_TYPE_BUTTON ?>" /><br /><br />
</form><br /><br /> <?php
require(DIR_WS_INCLUDES . 'qbi_page_bot.php');
?>