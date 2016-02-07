<?php
/*
  
  

  

  

  Featured Products V1.1
  Expiry Functions
*/

////
// Sets the status of a featured product
  function tep_set_featured_status($featured_id, $status) {
    return tep_db_query("update " . TABLE_FEATURED . " set status = '" . $status . "', date_status_change = now() where featured_id = '" . $featured_id . "'");
  }

////
// Auto expire featured products
  function tep_expire_featured() {
    $featured_query = tep_db_query("select featured_id from " . TABLE_FEATURED . " where status = '1' and now() >= expires_date and expires_date > 0");
    if (tep_db_num_rows($featured_query)) {
      while ($featured = tep_db_fetch_array($featured_query)) {
        tep_set_featured_status($featured['featured_id'], '0');
      }
    }
  }
?>
