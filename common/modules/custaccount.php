<?
class custaccount extends IXmoduleSet {
  function getName() {
    return 'Customer Account Extensions';
  }
  function getAllModules() {
//  echo "In the Class <br/>";
    $lst=tep_list_modules('custaccount');
//	echo "The List ".$lst;
    return $lst;
  }
}
?>
