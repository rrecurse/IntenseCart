<?php
/*
  $Id: database.php,v 1.23 2003/06/20 00:18:30 hpdl Exp $

   Copyright (c) 2003 IntenseCart eCommerce

*/

  function tep_db_connect($server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD, $database = DB_DATABASE, $link = 'db_link') {
    global $$link;

    if (USE_PCONNECT == 'true') {
      $$link = mysql_pconnect($server, $username, $password);

		// # added to correct corrupted latin and other character sets	
		mysql_set_charset('utf8', $$link);

    } else {
      $$link = mysql_connect($server, $username, $password);

		// # added to correct corrupted latin and other character sets	
		mysql_set_charset('utf8', $$link);
    }

    if ($$link) mysql_select_db($database);

    return $$link;
  }

  function tep_db_close($link = 'db_link') {
    global $$link;

    return mysql_close($$link);
  }

  function tep_db_error($query, $errno, $error) {

//error_log(print_r($query,1));

    die('<font color="#000000"><b>' . $errno . ' - ' . $error . '<br><br>' . $query . '<br><br><small><font color="#ff0000">[TEP STOP]</font></small><br><br></b></font>');
  }

  function tep_db_query($query, $link='db_link') {
    global $$link, $logger;

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
      if (!is_object($logger)) $logger = new logger;
      $logger->write($query, 'QUERY');
    }

	$query = preg_replace('/\s+/', ' ', $query);
	//$start_time = microtime(true);
    $result = mysql_query($query, $$link) or tep_db_error($query, mysql_errno(), mysql_error());
	//$stop_time = microtime(true);
	//echo '<br><br>Total Records: '.mysql_num_rows($result);
	//echo '<br>Time taken: '.number_format($stop_time-$start_time,4);

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
      if (mysql_error()) $logger->write(mysql_error(), 'ERROR');
    }

    return $result;
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
    return mysql_fetch_array($db_query, MYSQL_ASSOC);
  }

  function tep_db_result($result, $row='0', $field='') {

	if(!empty($field)) {
	    return mysql_result($result, $row, $field);
	} else {
		return mysql_result($result, $row);
	}
  }

  function tep_db_num_rows($db_query=NULL) {

	if(!is_null($db_query) || !empty($db_query)) { 
		$db_query = mysql_num_rows($db_query);
	} else {
		$db_query = mysql_affected_rows();
	}
    return $db_query;
  }

  function tep_db_data_seek($db_query, $row_number) {
    return mysql_data_seek($db_query, $row_number);
  }

  function tep_db_insert_id() {
    return mysql_insert_id();
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

  // # Changed from addslashes() to MRES()
  function tep_db_input($string) {

	if (is_string($string)) {

  	  return mysql_real_escape_string($string);

    } elseif (is_array($string)) {

      foreach($string as $key => $value) {
        $string[$key] = mysql_real_escape_string($value);
      }

      return $string;

    } else {
      return $string;
    }

    //return mysql_real_escape_string($string);
  }

  function tep_db_prepare_input($string) {
    if (is_string($string)) {
      return trim(stripslashes($string));
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
?>
