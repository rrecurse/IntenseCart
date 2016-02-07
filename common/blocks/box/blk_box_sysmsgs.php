<?
class blk_box_sysmsgs extends IXblock {
  function requireContext() {
    return Array();
  }
  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      default: $this->renderBody($body);
    }
  }
  function getVar($var,$args) {
    switch ($var) {
      case 'dump': return $GLOBALS['messageStack']->output($args['scope']);
      default:
    }
    return NULL;
  }
}
?>