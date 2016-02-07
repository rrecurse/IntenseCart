<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


  class splitPageResults {
  
    var $current_page_number, $max_rows_per_page, $query_num_rows;
	
    function __construct(&$current_page_number, $max_rows_per_page, &$sql_query, &$query_num_rows) {

    	if (empty($current_page_number)) $current_page_number = 1;

		// # scrub any white space found in incoming queries!
		if($sql_query) $sql_query = preg_replace("/\s+/", " ", $sql_query);
		//error_log(print_r($sql_query,1));

		$pos_to = strlen($sql_query);
		$pos_from = strripos($sql_query, ' from', 0);

      $pos_order_by = strripos($sql_query, ' order by', $pos_from);
      if (($pos_order_by < $pos_to) && ($pos_order_by != false)) $pos_to = $pos_order_by;

      $pos_limit = strripos($sql_query, ' limit', $pos_from);
      if (($pos_limit < $pos_to) && ($pos_limit != false)) $pos_to = $pos_limit;

      $pos_procedure = strripos($sql_query, ' procedure', $pos_from);
      if (($pos_procedure < $pos_to) && ($pos_procedure != false)) $pos_to = $pos_procedure;

      $offset = ($max_rows_per_page * ($current_page_number - 1));
      $sql_query .= " limit " . $offset . ", " . $max_rows_per_page;

      $reviews_count_query = tep_db_query("select 0 " . substr($sql_query, $pos_from, ($pos_to - $pos_from)));
	  //$reviews_count = tep_db_fetch_array($reviews_count_query);
      $query_num_rows = (int)tep_db_num_rows($reviews_count_query);

	  $this->current_page_number = $current_page_number;
	  $this->max_rows_per_page = $max_rows_per_page;
	  $this->query_num_rows = $query_num_rows;

    }
	
    function display_links_ex($max_page_links, $parameters = '', $page_name = 'page') {
      return $this->display_links($this->query_num_rows, $this->max_rows_per_page, $max_page_links, $this->current_page_number, $parameters, $page_name);
    }

    function display_links($query_numrows, $max_rows_per_page, $max_page_links, $current_page_number, $parameters = '', $page_name = 'page') {
      global $PHP_SELF;

      if ( tep_not_null($parameters) && (substr($parameters, -1) != '&') ) $parameters .= '&';

// calculate number of pages needing links
      $num_pages = intval($query_numrows / $max_rows_per_page);

// $num_pages now contains int of pages needed unless there is a remainder from division
      if ($query_numrows % $max_rows_per_page) $num_pages++; // has remainder so add one page

      $pages_array = array();
      for ($i=1; $i<=$num_pages; $i++) {
        $pages_array[] = array('id' => $i, 'text' => $i);
      }

      if ($num_pages > 1) {
        $display_links = tep_draw_form('pages', basename($PHP_SELF), '', 'get');

        if ($current_page_number > 1) {
          $display_links .= '<a href="' . tep_href_link(basename($PHP_SELF), $parameters . $page_name . '=' . ($current_page_number - 1), 'NONSSL') . '" class="splitPageLink">' . PREVNEXT_BUTTON_PREV . '</a>&nbsp;&nbsp;';
        } else {
          $display_links .= PREVNEXT_BUTTON_PREV . '&nbsp;&nbsp;';
        }

        $display_links .= sprintf(TEXT_RESULT_PAGE, tep_draw_pull_down_menu($page_name, $pages_array, '', 'onChange="this.form.submit();"'), $num_pages);

        if (($current_page_number < $num_pages) && ($num_pages != 1)) {
          $display_links .= '&nbsp;&nbsp;<a href="' . tep_href_link(basename($PHP_SELF), $parameters . $page_name . '=' . ($current_page_number + 1), 'NONSSL') . '" class="splitPageLink">' . PREVNEXT_BUTTON_NEXT . '</a>';
        } else {
          $display_links .= '&nbsp;&nbsp;' . PREVNEXT_BUTTON_NEXT;
        }

        if ($parameters != '') {
          if (substr($parameters, -1) == '&') $parameters = substr($parameters, 0, -1);
          $pairs = explode('&', $parameters);
          while (list(, $pair) = each($pairs)) {
            list($key,$value) = explode('=', $pair);
            $display_links .= tep_draw_hidden_field(rawurldecode($key), rawurldecode($value));
          }
        }

        if (SID) $display_links .= tep_draw_hidden_field(tep_session_name(), tep_session_id());

        $display_links .= '</form>';
      } else {
        $display_links = sprintf(TEXT_RESULT_PAGE, $num_pages, $num_pages);
      }

      return $display_links;
    }

    function display_count_ex($text_output) {
      return $this->display_count($this->query_num_rows, $this->max_rows_per_page, $this->current_page_number, $text_output);
    }
	
    function display_count($query_numrows, $max_rows_per_page, $current_page_number, $text_output) {
      $to_num = ($max_rows_per_page * $current_page_number);
      if ($to_num > $query_numrows) $to_num = $query_numrows;
      $from_num = ($max_rows_per_page * ($current_page_number - 1));
      if ($to_num == 0) {
        $from_num = 0;
      } else {
        $from_num++;
      }

      return sprintf($text_output, $from_num, $to_num, $query_numrows);
    }
  }
?>