<?
class ppc_ads extends IXmoduleSet {
  function ppc_ads() {
  }
  function getName() {
    return 'Pay Per Click';
  }
  function getAllModules() {
    return tep_list_modules('ads');
  }
}
?>