<?
class product_giftcert extends IXproduct {

  function getName() {
    return 'Gift Certificate';
  }

  function approvePurchase($qty,$op,&$order) {
    $qry=tep_db_query("SELECT * FROM coupons WHERE coupon_active='Y' AND purchase_ref='$op'");
    $ac_list=Array();
    while ($row=tep_db_fetch_array($qry)) {
      if ($qty>0) {
        if ($row['coupon_active']!='Y') $ac_list[]=$row['coupon_id'];
        $qty--;
      } else tep_db_query("UPDATE coupons SET coupon_active='N',date_modified=NOW() WHERE coupon_id='".$row['coupon_id']."'");
    }
    $expdays=730;
    if ($ac_list) tep_db_query("UPDATE coupons SET coupon_active='Y',coupon_expire_date=DATE_ADD(NOW(),INTERVAL $expdays DAY),date_modified=NOW() WHERE coupon_id IN ('".join("','",$ac_list)."')");
    while ($qty>0) {
      do {
        $cou_code='';
        for ($i=0;$i<10;$i++) $cou_code.=chr(mt_rand(1,10)>5?mt_rand(0x61,0x7a):mt_rand(0x30,0x39));
      } while (preg_match('/l/',$cou_code));
      tep_db_query("INSERT INTO coupons (coupon_type,coupon_code,coupon_amount,coupon_start_date,coupon_expire_date,date_created,purchase_ref) VALUES ('G','$cou_code','".addslashes($this->getExtraField('gift_cert_value'))."',NOW(),DATE_ADD(NOW(),INTERVAL $expdays DAY),NOW(),'$op')");
      $qty--;
    }
    return true;
  }

  function getPurchaseInfo($op,&$order) {
    $rs=Array();
    $qry=tep_db_query("SELECT * FROM coupons WHERE purchase_ref='$op' AND coupon_active='Y'");
    while ($row=tep_db_fetch_array($qry)) $rs[]=sprintf('Gift Certificate Code: <b>%s</b> ($%.2f value)',$row['coupon_code'],$row['coupon_amount']);
    return $rs;
  }

  function getProductFields() {
    return Array(
      'gift_cert_value'=>Array('title'=>'Gift Certificate Value','type'=>'text','default'=>0),
    );
  }

  function disableModelFields() {
    return Array('price_sign');
  }

  function productEditSectionAllowed($sec) {
    switch ($sec) {
      case 'marketing': case 'auctions': return false;
      default: return true;
    }
  }

}
?>