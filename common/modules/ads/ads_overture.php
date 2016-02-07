<?
class ads_overture extends IXmodule {
  function ads_overture() {
  }
  function getName() {
    return 'Yahoo Overture';
  }
  function listConf() {
    return Array(
    );
  }


  function getParams() {
    return Array('active'=>false);
  }
  function getCampaigns() {
    return Array();
  }
  function getStats($from,$to) {
    return Array('clicks'=>0,'convs'=>0,'imprs'=>0,'cost'=>0);
  }
  function getUsageStats($start,$end) {
    return Array();
  }
}

?>