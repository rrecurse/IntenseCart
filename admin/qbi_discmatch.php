<?php
/*
$Id: qbi_discmatch.php,v 2.10 2005/05/08 al Exp $

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

if (isset($stage) AND $stage=="discupdate") {
  disc_update($disc_menu);
  echo MATCH_SUCCESS;
}
// Find and update IX payment methods
disc_methods();
?>
<table class="lists">
<form action="<?php echo $_SERVER[PHP_SELF]?>" method="post" name="qbi_disc" id="qbi_disc">
<input name="stage" id="stage" type="hidden" value="discupdate" />
<input name="search_page" id="search_page" type="hidden" value="<?php echo $search_page?>" />
<?php
$Query = "SELECT COUNT(*) AS cnt FROM ".TABLE_QBI_OT." WHERE language_id='$languages_id'"; 
$result = tep_db_query($Query) or die(mysql_error()); 
$row = tep_db_fetch_array($result); 
$count = $row['cnt'];
if($count > 0){ 
  $page = new page_class($count,QBI_PROD_ROWS,10); 
  $limit = $page->get_limit();
  $resultqbc = tep_db_query("SELECT * FROM ".TABLE_QBI_OT." WHERE language_id='$languages_id' ORDER BY qbi_ot_text ".$limit);
  $hstring = $page->make_head_string(DISCMATCH_TITLE); 
  $pstring = $page->make_page_string(); //add the other variables to pass to next page in a similar fashion 
  echo "<tr><th colspan='2' class='counter'>$hstring</th></tr>\r\n"; 
  echo "<tr><td colspan='2'>&nbsp;</td></tr>\r\n"; 
  echo "<tr><th class='colhead'>".MATCH_OSC."</th><th class='colhead'>".MATCH_QB."</th></tr>\r\n"; 
  while ($myrowqbc = tep_db_fetch_array($resultqbc)) {
    echo "<tr><td class='oscmodel'>".$myrowqbc["qbi_ot_text"]."</td>";
    disc_dropdown($myrowqbc["qbi_ot_mod"]);
  }
  echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\r\n";
  echo "<tr><td colspan=\"2\" class='pagelist'>$pstring</td></tr>\r\n";
}
?>
<tr><td><input name="submit" type="submit" id="submit" value="<?php echo MATCH_BUTTON ?>" /></td></tr>
</form>
</table> <?php
require(DIR_WS_INCLUDES . 'qbi_page_bot.php');
?>