<?
class checkout extends IXmoduleSet {
  function getName() {
    return 'Payment Processing';
  }
  function getAllModules() {
    $lst=tep_list_modules('payment');
    unset($lst['payment_manual']);
    return $lst;
  }
  function getModulesCustomer($cid) {
    $mdls=$this->getModules();
    $cmods=IXdb::read("SELECT cg.group_payment_allowed FROM customers_groups cg".($cid?",customers c WHERE c.customers_id='$cid' AND cg.customers_group_id=c.customers_group_id":" WHERE customers_group_id=0"),NULL,'group_payment_allowed');
    if ($cmods) {
      $cmodl=split(';',$cmods);
      foreach ($mdls AS $m=>$mdl) if (!in_array($m,$cmodl)) unset($mdls[$m]);
    }
    return $mdls;
  }
}
?>
