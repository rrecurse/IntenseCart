<?
/*
  IntenseCart, E-Commerce and Internet Marketing Solutions
  http://www.intensecart.com

  Copyrights (c) 2007 IntenseGroup Inc.

  Redistribution without explicit written consent is forbidden under US and International Copyright Laws
*/


error_reporting(E_ALL & ~E_NOTICE);
class IXeditList {

  function preRender() {
    if (isset($_POST['edit'])) {
      if ($this->itemSave($_POST['edit'],$this->getItem($_POST['edit']))) {
        tep_redirect($this->makeLink());
	return false;
      }
    } else if (isset($_GET['delete'])) {
      $this->itemDelete($_GET['delete'],$this->getItem($_GET['delete']));
      tep_redirect($this->makeLink());
      return false;
    }
    return true;
  }
  
  function render() {
    if (isset($_POST['edit'])) {
      $this->renderEdit($_POST['edit']);
    } else if (isset($_GET['edit'])) {
      $this->renderEdit($_GET['edit']);
    } else $this->renderList();
  }
  
  function makeLink($arg=NULL) {
    global $PHP_SELF;
    $args=Array();
    if (isset($_GET['page'])) $args[]='page='.$_GET['page'];
    if ($arg) $args[]=$arg;
    return $PHP_SELF.'?'.join('&',$args);
  }

  function renderEdit($id) {
?>
<form action="<?=$this->makeLink()?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="edit" value="<?=$id?>">
<?
      $this->itemEdit($this->getItem($id));
?>
<input type="submit" value="Update">
</form>
<?
  }
  
  function getItem($id) {
    return tep_db_fetch_array(tep_db_query($this->getItemQuery($id)));
  }

  function renderList() {
?>
	<div class="Accordion" id="listAccordion" tabindex="0">
<?
    foreach ($this->getListing() AS $rec) {
?>
	<div class="AccordionPanel">
	  <div class="AccordionPanelTab <?=$ct++&1?'tabEven':'tabOdd'?>" style="width:571px; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;">
	    <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="30" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
            <td><? $this->itemHeader($rec); ?></td>
          </tr>
        </table>
	  </div>
	  <div class="AccordionPanelContent"><div><? $this->itemContent($rec); ?></div></div>
    </div>
<?
    }
?>
    </div>
	<script language="javascript">
	    var listAccordion = new Spry.Widget.Accordion("listAccordion",{enableClose:true});
	</script>
<?
    $this->renderPageLinks();
?>
[<a href="<?=$this->makeLink('edit=')?>">New Item</a>]
<?
  }
  
  function renderPageLinks() {
    if (!isset($this->split)) return;
    echo $this->split->display_count($this->num_rows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS);
    echo $this->split->display_links($this->num_rows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'oID', 'action')));
  }
  
  function getListing() {
    $qry=$this->getListQuery();
    $this->split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $qry, $this->num_rows);
    $query = tep_db_query($qry);
    $recs=Array();
    while ($row=tep_db_fetch_array($query)) $recs[]=$row;
    return $recs;
  }

}
?>