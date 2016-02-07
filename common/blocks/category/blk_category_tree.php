<?

class blk_category_tree extends IXblock {

  function jsObjectName() {
    return 'catTree_'.$this->makeID();
  }

  function render(&$body) {
    global $languages_id;
    $max=$this->args['max'];
    $clst=tep_db_read("SELECT * FROM categories c LEFT JOIN categories_description cd ON (c.categories_id=cd.categories_id AND cd.language_id='$languages_id') WHERE c.categories_status=1",'categories_id');
    $this->cats=Array();
    if (isset($this->args['cid'])) $cid=$this->args['cid'];
    else if (isset($this->context['category'])) $cid=$this->context['category']->cid;
    else $cid=0;
    $this->_collate($this->cats,$clst,$cid);
    $this->renderBody($body);
  }
  
  function _collate(&$rs,$cats,$cid=0) {
    foreach ($cats AS $cidx=>$cat) if ($cat['parent_id']==$cid) {
      $rs[]=&$cats[$cidx];
      $this->_collate($rs,$cats,$cidx);
    }
  }

  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'table':
        $this->renderListing($this->cats,$body,$args['cols'],$args['max']);
	break;
      default: $this->renderBody($body);
    }
  }
  
  function setListingElement(&$row) {
    $this->cat_row=&$row;
    $this->cid=$row['categories_id'];
  }
  
  function renderListing($lst,&$body,$cols=NULL,$max=NULL) {
    $idx=0;
?>
<table border="0" cellspacing="0" cellpadding="0">
<?
    foreach ($lst AS $cell) {
      if ($max && $idx>=$max) break;
      if (!($cols?($idx%$cols):$idx)) echo '<tr>';
      echo '<td id="'.$this->jsObjectName().'_'.$idx.'">';
      $this->setListingElement($cell);
      $this->renderBody($body);
      $idx++;
      echo '</td>';
      if ($cols && !($idx%$cols)) echo '</tr>';
    }
    if ($idx && (!$cols || $idx%$cols)) {
      if ($cols) for (;$idx%$cols;$idx++) echo '<td>&nbsp;</td>';
      echo '</tr>';
    }
?>
</table>
<?
  }
  
  function exportContext() {
    $ctxt=$this->context;
    $ctxt['category']=&$this;
    return $ctxt;
  }
  function getVar($var,$args) {
    switch ($var) {
      default:
        if (isset($this->cat_row[$var])) return $this->cat_row[$var];
    }
    return NULL;
  }
}
?>