<?
class IXblockProduct extends IXblock {
  function loadRow($pid) {
    global $languages_id;
    $this->setRow(tep_db_read("SELECT * FROM products p LEFT JOIN products_description pd ON (p.master_products_id=pd.products_id AND pd.language_id='".$languages_id."') WHERE p.products_id='".(int)$pid."'"));
  }
  function setRow($row) {
    $this->product_row = $row;
  }
  function getVar($var,$args) {
    if (isset($this->product_row[$var])) return $this->product_row[$var];
    return NULL;
  }
}
?>