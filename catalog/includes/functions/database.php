<?php
/*
  $Id: database.php,v 1.21 2003/06/09 21:21:59 hpdl Exp $
adapted for Separate Pricing Per Customer 2005/03/04

*/

  function tep_db_connect($server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD, $database = DB_DATABASE, $link = 'db_link') {
    global $$link;

    if (USE_PCONNECT == 'true') {
      $$link = mysql_pconnect($server, $username, $password);
    } else {
      $$link = mysql_connect($server, $username, $password);
    }

	// # set default connection character set
	mysql_set_charset('utf8',$$link);

    if ($$link) mysql_select_db($database);

    return $$link;
  }

  function tep_db_close($link = 'db_link') {
    global $$link;

    return mysql_close($$link);
  }

	function tep_db_error($query, $errno, $error) {
		die('<font color="#000000"><b>' . $errno . ' - ' . $error . '<br><br>' . $query . '<br><br><small><font color="#ff0000">[IX STOP]</font></small><br><br></b></font>');
		error_log( print_r($errno.' - '.$error.' ::: '.$query, 1));
  }

  function tep_db_query($query, $link = 'db_link') {
    global $$link;

if(isset($_GET['products_id'])) {
//    global $sql_log_fd;
//    if (!isset($sql_log_fd)) $sql_log_fd=fopen(DIR_FS_CATALOG_IMAGES_CACHE.'sql.log','a');
//    foreach(debug_backtrace() AS $stk) {
//     fwrite($sql_log_fd,"[STK: ".str_replace(DIR_FS_CATALOG,'',$stk['file']).":".$stk['line'].' '.$stk['function'].'('.(is_array($stk['args'])?join(',',$stk['args']):'').")]\n");
//    }
//    fwrite($sql_log_fd,$_SERVER['REQUEST_URI'].":\t$query\n\n");
}


    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
      error_log('QUERY ' . $query . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
    }

    $result = mysql_query($query, $$link) or tep_db_error($query, mysql_errno(), mysql_error());

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
       $result_error = mysql_error();
       error_log('RESULT ' . $result . ' ' . $result_error . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
    }

    return $result;
  }

function tep_clean_get__recursive($get_var)
  {
  if (!is_array($get_var))
  return preg_replace("/[^ {}%a-zA-Z0-9_.-]/i", "", $get_var);
  
  // Add the preg_replace to every element.
  return array_map('tep_clean_get__recursive', $get_var);
  }


  function tep_db_perform($table, $data, $action = 'insert', $parameters = '', $link = 'db_link') {
    reset($data);
    if ($action == 'insert') {
      $query = 'insert into ' . $table . ' (';
      while (list($columns, ) = each($data)) {
        $query .= $columns . ', ';
      }
      $query = substr($query, 0, -2) . ') values (';
      reset($data);
      while (list(, $value) = each($data)) {
        switch ((string)$value) {
          case 'now()':
            $query .= 'now(), ';
            break;
          case 'null':
            $query .= 'null, ';
            break;
          default:
            $query .= '\'' . tep_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ')';
    } elseif ($action == 'update') {
      $query = 'update ' . $table . ' set ';

      while (list($columns, $value) = each($data)) {
        switch ((string)$value) {
          case 'now()':
            $query .= $columns . ' = now(), ';
            break;
          case 'null':
            $query .= $columns .= ' = null, ';
            break;
          default:
            $query .= $columns . ' = \'' . tep_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ' where ' . $parameters;
    }

    return tep_db_query($query, $link);
  }

	function tep_db_fetch_array($db_query) {

		// # check whether $db_query is valid MySQL resource before mysql_fetch_array function
		$res_type = is_resource($db_query) ? get_resource_type($db_query) : gettype($db_query);

		if(strpos($res_type, 'mysql') !== false) {
			$result = mysql_fetch_array($db_query, MYSQL_BOTH);	// # changed from MYSQL_ASSOC to MYSQL_BOTH
		    return $result;
		} else { 
			error_log(print_r('Invalid resource type in tep_db_fetch_array(): ' . $res_type . ' - ' . $db_query, 1));
		}

	}

  function tep_db_result($result, $row, $field='') {

		// # check whether $result is valid MySQL resource before mysql_result function
		$res_type = is_resource($result) ? get_resource_type($result) : gettype($result);

		if(strpos($res_type, 'mysql') !== false) {

	        if(!empty($field)) {
				$result = mysql_result($result, $row, $field);
    	    } else {
				$result = mysql_result($result, $row);
        	}

		    return $result;

		} else { 
			error_log(print_r('Invalid resource type in tep_db_result(): ' . $res_type . ' - ' . $result, 1));
		}

  }

  function tep_db_num_rows($db_query) {

	// # check whether $db_query is valid MySQL resource before mysql_fetch_array function
		$res_type = is_resource($db_query) ? get_resource_type($db_query) : gettype($db_query);

		if(strpos($res_type, 'mysql') !== false) {
			$result = mysql_num_rows($db_query);
		    return $result;
		} else { 
			error_log(print_r('Invalid resource type in tep_db_num_rows(): ' . $res_type . ' - ' . $db_query, 1));
		}
  }

  function tep_db_data_seek($db_query, $row_number) {
    return mysql_data_seek($db_query, $row_number);
  }

  function tep_db_insert_id() {
    return mysql_insert_id();
  }

  function tep_db_affected_rows() {
    return mysql_affected_rows();
  }

  function tep_db_free_result($db_query) {
    return mysql_free_result($db_query);
  }

  function tep_db_fetch_fields($db_query) {
    return mysql_fetch_field($db_query);
  }

  function tep_db_output($string) {
    return htmlspecialchars($string);
  }


function tep_db_input($string, $link = 'db_link') {
    global $$link;

    if (function_exists('mysql_real_escape_string')) {
	if(!is_string($string))	$string = (string)$string;
        return mysql_real_escape_string($string, $$link);
    } elseif (function_exists('mysql_escape_string')) {
        return mysql_escape_string($string);
    }

    return addslashes($string);
}

  function tep_db_prepare_input($string) {
    if (is_string($string)) {
      return trim(tep_sanitize_string(stripslashes($string)));
    } elseif (is_array($string)) {
      reset($string);
      while (list($key, $value) = each($string)) {
        $string[$key] = tep_db_prepare_input($value);
      }
      return $string;
    } else {
      return $string;
    }
  }

  function tep_db_table_exists($table, $link = 'db_link') {
	  $result = tep_db_query("show table status from `" . DB_DATABASE . "`");
	  while ($list_tables = tep_db_fetch_array($result)) {
	  if ($list_tables['Name'] == $table) {
		  return true;
	  }
	  }
	  return false;
  }

  function tep_db_check_age_specials_retail_table() {
	  $result = tep_db_query("show table status from `" . DB_DATABASE . "`");
	  $last_update_table_specials = "2000-01-01 12:00:00";
	  $table_srp_exists = false;
	  while ($list_tables = tep_db_fetch_array($result)) {
	  if ($list_tables['Name'] == TABLE_SPECIALS_RETAIL_PRICES) {
	  $table_srp_exists = true;
	  $last_update_table_srp = $list_tables['Update_time'];
	  }
	  if ($list_tables['Name'] == TABLE_SPECIALS) {
	  $last_update_table_specials = $list_tables['Update_time'];
	  }
	  } // end while

	  if(!$table_srp_exists || ($last_update_table_specials > $last_update_table_srp)) {
	     if ($table_srp_exists) {
		     $query1 = "truncate ".TABLE_SPECIALS_RETAIL_PRICES."";

		     if (tep_db_query($query1)) {
				$query2 = "INSERT IGNORE INTO " . TABLE_SPECIALS_RETAIL_PRICES . " (SELECT s.products_id, s.specials_new_products_price, s.status, s.customers_group_id from " . TABLE_SPECIALS . " s WHERE s.customers_group_id = '0')";
				 $result =  tep_db_query($query2);
		 	}
	     } else { 
			// # table specials_retail_prices does not exist
		     $query1 = "CREATE TABLE IF NOT EXISTS " . TABLE_SPECIALS_RETAIL_PRICES . " (
						products_id int(11) NOT NULL DEFAULT '0',
						specials_new_products_price decimal(15,4) NOT NULL DEFAULT '0.0000',
						status tinyint(4) DEFAULT NULL,
						customers_group_id smallint(6) DEFAULT NULL,
						PRIMARY KEY (products_id))";

		     $query2 = "insert into " . TABLE_SPECIALS_RETAIL_PRICES . " select s.products_id, s.specials_new_products_price, s.status, s.customers_group_id from " . TABLE_SPECIALS . " s where s.customers_group_id = '0'";
		     if( tep_db_query($query1) && tep_db_query($query2) ) {
			; // # execution succesfull
		    }
	     } // # end else
	  } // # end if(!$table_srp_exists || ($last_update_table_specials....
  }

	function tep_db_check_age_products_group_prices_cg_table($customer_group_id) {
		$result = tep_db_query("show table status from `" . DB_DATABASE . "`");
		$last_update_table_pgp = strtotime('2000-01-01 12:00:00');
		$table_pgp_exists = false;
		while ($list_tables = tep_db_fetch_array($result)) {
			if ($list_tables['Name'] == 'products_group_prices_'.$customer_group_id) {
				$table_pgp_exists = true;
	  			$last_update_table_pgp = strtotime($list_tables['Update_time']);
	
		  	} elseif($list_tables['Name'] == TABLE_SPECIALS ) {
				$last_update_table_specials = strtotime($list_tables['Update_time']);
	
			} elseif($list_tables['Name'] == TABLE_PRODUCTS ) {
				$last_update_table_products = strtotime($list_tables['Update_time']);
	
			} elseif($list_tables['Name'] == TABLE_PRODUCTS_GROUPS ) {
			  $last_update_table_products_groups = strtotime($list_tables['Update_time']);
			}
		} // end while

		if ($table_pgp_exists == false) {
			$create_table_sql = "create table " . 'products_group_prices_'.$customer_group_id . " (products_id int NOT NULL default '0', products_price decimal(15,4) NOT NULL default '0.0000', specials_new_products_price decimal(15,4) default NULL, status tinyint, primary key (products_id) )" ;
      $fill_table_sql1 = "insert into " . 'products_group_prices_'.$customer_group_id ." select p.products_id, p.products_price, NULL as specials_new_products_price, NULL as status FROM " . TABLE_PRODUCTS . " p";
      $update_table_sql1 = "update " . 'products_group_prices_'.$customer_group_id ." ppt left join " . TABLE_PRODUCTS_GROUPS . " pg using(products_id) set ppt.products_price = pg.customers_group_price where ppt.products_id = pg.products_id and pg.customers_group_id ='" . $customer_group_id . "'";
      $update_table_sql2 = "update " . 'products_group_prices_'.$customer_group_id ." ppt left join " . TABLE_SPECIALS . " s using(products_id) set ppt.specials_new_products_price = s.specials_new_products_price, ppt.status = s.status where ppt.products_id = s.products_id and s.customers_group_id = '" . $customer_group_id . "'";
      if ( tep_db_query($create_table_sql) && tep_db_query($fill_table_sql1) && tep_db_query($update_table_sql1) && tep_db_query($update_table_sql2) ) {
	       return true;
              }
   } // end if ($table_pgp_exists == false)

   if ( ($last_update_table_pgp < $last_update_table_products && (time() - $last_update_table_products > (int)MAXIMUM_DELAY_UPDATE_PG_PRICES_TABLE * 60) ) || $last_update_table_specials > $last_update_table_pgp || $last_update_table_products_groups > $last_update_table_pgp ) { // then the table should be updated
      $empty_query = "truncate " . 'products_group_prices_'.$customer_group_id . "";
      $fill_table_sql1 = "insert into " . 'products_group_prices_'.$customer_group_id ." select p.products_id, p.products_price, NULL as specials_new_products_price, NULL as status FROM " . TABLE_PRODUCTS . " p";
      $update_table_sql1 = "update " . 'products_group_prices_'.$customer_group_id ." ppt left join " . TABLE_PRODUCTS_GROUPS . " pg using(products_id) set ppt.products_price = pg.customers_group_price where ppt.products_id = pg.products_id and pg.customers_group_id ='" . $customer_group_id . "'";
      $update_table_sql2 = "update " . 'products_group_prices_'.$customer_group_id ." ppt left join " . TABLE_SPECIALS . " s using(products_id) set ppt.specials_new_products_price = s.specials_new_products_price, ppt.status = s.status where ppt.products_id = s.products_id and s.customers_group_id = '" . $customer_group_id . "'";
      if ( tep_db_query($empty_query) && tep_db_query($fill_table_sql1) && tep_db_query($update_table_sql1) && tep_db_query($update_table_sql2) ) {
	       return true;
              }
   } else { // no need to update
	   return true;
   } // end checking for update

  }

  // EOF Separate Pricing Per Customer
?>
