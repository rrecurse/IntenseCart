<?

  $db_update_query=tep_db_query("SELECT * FROM IXcore.database_updates WHERE update_id>'". IXCORE_DATABASE_UPDATE_ID."' ORDER BY update_id");
  while ($db_update=tep_db_fetch_array($db_update_query)) {
    if (tep_db_num_rows(tep_db_query("SELECT * FROM database_updates WHERE update_id='".$db_update['update_id']."' AND (date_finished IS NOT NULL OR date_started>DATE_SUB(NOW(),INTERVAL 20 MINUTE))"))>0) continue;
    tep_db_query("REPLACE INTO database_updates (update_id,date_started) VALUES ('".$db_update['update_id']."',NOW())");
    tep_db_query($db_update['update_query']);
    tep_db_query("UPDATE database_updates SET date_finished=NOW() WHERE update_id='".$db_update['update_id']."'");
    tep_db_query("UPDATE ".TABLE_CONFIGURATION." SET configuration_value='".$db_update['update_id']."' WHERE configuration_key='IXCORE_DATABASE_UPDATE_ID'");
  }
  
?>