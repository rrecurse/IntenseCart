<?
  require('includes/application_top.php');

  $tab_query=tep_db_query("SHOW TABLES");
  while($tab_row=tep_db_fetch_array($tab_query)) {
    foreach($tab_row AS $v) $tab=$v;
    $fld_query=tep_db_query("SHOW COLUMNS FROM $tab");
    while ($fld_row=tep_db_fetch_array($fld_query)) {
      if (preg_match('/auto_increment/i',$fld_row['Extra'])) {
        $fld=$fld_row['Field'];
        $ct_query=tep_db_query("SELECT MAX($fld) AS m FROM $tab");
        $ct_row=tep_db_fetch_array($ct_query);
        $up_query="ALTER TABLE $tab auto_increment=".(0+$ct_row['m']);
	echo "$up_query<br>\n";
        if ($_GET['go']) tep_db_query($up_query);
      }
    }
  }
?>

