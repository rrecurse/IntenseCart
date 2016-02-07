<?php
/*
$Id: qbi_db.php,v 2.10 2005/05/08 al Exp $

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
require(DIR_WS_INCLUDES . 'qbi_page_top.php');

// Process db upgrade form
if (isset($stage) AND $stage=="process") {

// Look for sql upgrade file
// If not found, decrement version back by 0.01 until found
  for ($db_ver_i=$db_ver; $db_ver_i>=0; $db_ver_i=$db_ver_i-0.01) {
    $db_ver_f=number_format($db_ver_i,2,".","");
    $db_ver_f=str_replace(".","-",$db_ver_f);
    $qbi_ver_f=str_replace(".","-",$qbi_vers);
    $file_name="qbi_".$db_ver_f."_".$qbi_ver_f.".sql";
    if (file_exists($file_name)) {
      executeSql($file_name);
	  $db_up=1;
	  break;
	}
  }
  if ($db_up==1) {
    echo ($db_ver=="0.00") ? DB_SUCCESS_INSTALL : DB_SUCCESS_UPGRADE;
	echo '<p></p><p><a href="qbi_config.php">'.DB_GOTO_CONFIG.'</a></p>';
  } else {
    echo DB_FILE_MISSING;
  }
} elseif ($db_ver==$qbi_vers) {
echo '<p>'.DB_SUCCESS_UPGRADE.' '.DB_GOTO_CONFIG.'<br />1) '.DB_RECHECK.'<br />2) '.DB_RECHECK2.'<br />'.DB_RECHECK3.'<p>';
echo '<p><a href="qbi_config.php">'.DB_GOTO_CONFIG.'</a></p>';
} else {
  echo ($db_ver=="0.00") ? DB_MES_INSTALL." ".$qbi_vers."?<br /><br />" : DB_MES_UPGRADE." ".$db_ver." ".DB_MES_TO." ".$qbi_vers."?<br /><br />";

// Install - upgrade form
?>
<form action="<?php echo $_SERVER[PHP_SELF] ?>" method="post" name="installdb">
<input name="db_ver" type="hidden" value="<?php echo $db_ver ?>" />
<input name="qbi_vers" type="hidden" value="<?php echo $qbi_vers ?>" />
<input name="stage" type="hidden" value="process" />
<input name="submitdb" type="submit" id="submitdb" value=" <?php
echo ($db_ver=="0.00") ? DB_PROMPT_INSTALL : DB_PROMPT_UPGRADE; ?>
"/>
</form><?php
}
require(DIR_WS_INCLUDES . 'qbi_page_bot.php');
?>