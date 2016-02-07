<?

IXblock::block('blk_category_main');

class blk_category_parent extends blk_category_main {
  function initContext() {
    $this->setData(IXdb::read("SELECT parent_id FROM categories WHERE categories_id='".$this->context['category']->cid."'",NULL,'parent_id'));
    return true;
  }
}

?>