<?
class stock extends IXmoduleSet {
  function getAllModules() {
    return tep_list_modules('stock');
  }
  function getName() {
    return 'Stock Level Control';
  }
}
?>
