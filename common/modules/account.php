<?
class account extends IXmoduleSet {
  function getName() {
    return "Customer Account Functions";
  }
  function getAllModules() {
    return tep_list_modules('account');
  }
}
?>
