<?php
class blk_box_ajax_popup extends IXblock {
  function requireContext() {
    return Array();
  }

// # commented out to fix PHP Warning Missing argument 1 for renderOnce 
//function renderOnce(&$body) {

	function renderOnce() {  

	}

	function render($body) {
?>
<script type="text/javascript">
  window.<?php echo $this->jsObjectName()?> = new ajaxPopupTipObj({});
</script>
<?php
    $this->popups=Array();
    $this->actions=Array();
    $this->renderBody($body);
?>
<script type="text/javascript">
  <?php echo $this->jsObjectName()?>.popups=[<?php echo join(',',$this->popups)?>];
  <?php echo $this->jsObjectName()?>.actions=[<?php echo join(',',$this->actions)?>];
<?php if ($this->tipobj) { ?>
  <?php echo $this->jsObjectName()?>.tipobj=<?php echo $this->tipobj?>;
<?php if ($this->tipptr) { ?>
  <?php echo $this->jsObjectName()?>.pointerobj=<?=$this->tipptr?>;
<?php
   }
} 
?>
</script>
<?php
  }
  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'ajax': return !!$this->context['ajax'];
      case 'noajax':
      case 'standby': return !$this->context['ajax'];
      default:
    }
    return true;
  }
  function HTMLParams($htargs) {
    if (!$this->html_id) $htargs['id']='id="'.($this->html_id=$this->jsObjectName()).'"';
    return $htargs;
  }
  function HTMLParamsSection($sec,$htargs,$args) {
    $obj=$this->jsObjectName();
    switch ($sec) {
      case 'trigger':
        $htargs['onmouseover']='onMouseover="'.$obj.'.show(event);"';
        $htargs['onmousemove']='onMousemove="'.$obj.'.move(event);"';
        $htargs['onmouseout']='onMouseout="'.$obj.'.hide();"';
	break;
      case 'handle':
        $htargs['onmousedown']='onMousedown="'.$obj.'.handleGrab(event);"';
        $htargs['onmousemove']='onMousemove="'.$obj.'.handleMove(event);"';
        $htargs['onmouseout']='onMouseout="'.$obj.'.handleMove(event);"';
        $htargs['onmouseup']='onMouseup="'.$obj.'.handleRelease();"';
	break;
      case 'open_button':
        $htargs['onclick']='onClick="'.$obj.'.show(event);"';
	break;
      case 'close_button':
        $htargs['onclick']='onClick="'.$obj.'.hide(event);"';
	break;
      case 'content':
        $htargs['id']='id="'.$this->html_id.'_content"';
	$this->actions[]='function(){'.$this->ajaxLoad(NULL,NULL,$sec,$args['template']).';return true;}';
	break;
      case 'popup':
      case 'tip':
      case 'tippointer':
        $htargs['id']='id="'.($blkid=$this->jsObjectName().'_popup_'.sizeof($this->popups)).'"';
	$this->popups[]='$(\''.$blkid.'\')';
	if ($sec=='tip') $this->tipobj='$(\''.$blkid.'\')';
	if ($sec=='tippointer') $this->tipptr='$(\''.$blkid.'\')';
	break;
      default:
    }
    return $htargs;
  }
  function jsObjectName() {
    return 'ajaxPopup_'.$this->makeID();
  }

}
?>