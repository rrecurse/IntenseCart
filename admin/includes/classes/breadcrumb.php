<?php

  class breadcrumb {
    var $_trail;

    function breadcrumb() {
      $this->reset();
    }

    function reset() {
      $this->_trail = array();
    }

    function add($title, $link = '') {
      $this->_trail[] = array('title' => $title, 'link' => $link);
    }

    function quote($str) {
      return "'".str_replace("'","\\'",str_replace("\\","\\\\",$str))."'";
    }

    function trail($offset=0) {
      $trail_array=Array();

      for ($i=0; $i<$offset; $i++) $trail_array[]='{}';
      for ($i=0, $n=sizeof($this->_trail); $i<$n; $i++) {
        $trail_string = '{title:'.$this->quote($this->_trail[$i]['title']);
        if (isset($this->_trail[$i]['link']) && tep_not_null($this->_trail[$i]['link'])) {
	    $trail_string.=',link:'.$this->quote($this->_trail[$i]['link']);
        }
	$trail_array[]=$trail_string.'}';

      }
      return '<script type="text/javascript"> setBreadcrumb(new Array('.join(',',$trail_array).')); </script>';
    }
  }
?>
