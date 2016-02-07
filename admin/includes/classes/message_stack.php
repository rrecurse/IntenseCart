<?php
/*

  Example usage:

  $messageStack = new messageStack();
  $messageStack->add('Error: Error 1', 'error');
  $messageStack->add('Error: Error 2', 'warning');
  if ($messageStack->size > 0) echo $messageStack->output();
*/

  class messageStack extends tableBlock {
    var $size = 0;

    function __construct() {
      global $messageToStack;
      $this->errors = array();

      if (tep_session_is_registered('messageToStack')) {
        for ($i = 0, $n = sizeof($messageToStack); $i < $n; $i++) {
          $this->add($messageToStack[$i]['text'], $messageToStack[$i]['type']);
        }
        tep_session_unregister('messageToStack');
      }
    }

    function add($message, $type = 'error') {

      if ($type == 'error') {
        $this->errors[] = array('params' => 'class="messageStackError"', 'text' => '<img src="/admin/images/icons/error.gif" title="'.ICON_ERROR.'" width="16" height="16">&nbsp; ' . $message);
      } elseif($type == 'warning') {
        $this->errors[] = array('params' => 'class="messageStackWarning"', 'text' => '<img src="/admin/images/icons/warning.gif" title="'.ICON_WARNING.'" width="16" height="16">&nbsp; ' . $message);
      } elseif($type == 'success') {
        $this->errors[] = array('params' => 'class="messageStackSuccess"', 'text' => '<img src="/admin/images/icons/success.gif" title="'.ICON_SUCCESS.'" width="16" height="16">&nbsp; ' . $message);
      } else {
        $this->errors[] = array('params' => 'class="messageStackWarning"', 'text' => $message);
      }

		$this->size++;
    }

    function add_session($message, $type = 'error') {
      global $messageToStack;

      if (!tep_session_is_registered('messageToStack')) {
        tep_session_register('messageToStack');
        $messageToStack = array();
      }

      $messageToStack[] = array('text' => $message, 'type' => $type);
		$this->size++;
    }

    function reset() {
      $this->errors = array();
      $this->size = 0;
    }

	function output() {
		$this->table_data_parameters = 'class="messageStack"';
		return $this->tableBlock($this->errors, 'class="messageStack"');
    }
  }
?>