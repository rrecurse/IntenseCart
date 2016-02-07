<?

class edi_850 extends IXmodule {

// $ID|$TRADING_PARTNER_ID|$TRANSACTION_SET_ID|$RECORD_ID|$ITEM_NO|$TRANS_TYPE|$ORDER_NO|$ORDER_LINE|$REQ_NAME|$AGGREGATION_CODE|$ORDER_DATE|$NEED_DATE|$PRIORITY|$QTY|$UOM|$UNIT_PRICE|$CUST_NAME|$CUST_ADDR1|$CUST_ADDR2|$CUST_ADDR3|$CUST_CITY|$CUST_STATE|$CUST_ZIP9|$REQUISITIONER_PHONE|$REQUISITIONER_FAX|$REQUISITIONER_EMAIL|$ALTERNATE_ADDR_IND|$REQUISITIONER_NAME|$CUST_ID|$PAYMENT_METHOD|$PAYMENT_NO|$CONTRACT_NO|$CONTRACT_LINE|$ATTACHMENT_FILE_NAME|$ORDER_NOTES|$LINE_NOTES|$REVIEWED|$ModifyTime|$ModifiedBy

$EDI_GnuPG=NULL;

if (EDI_ENABLE=='True' && !is_dir(DIR_FS_EDI)) mkdir(DIR_FS_EDI);

function edi_gpg_encrypt($data,$g_uid,$g_rcpt,$g_pass) {
  global $EDI_GnuPG;
  if (!$EDI_GnuPG) {
    require_once(DIR_FS_CATALOG_CLASSES.'gnuPG.php');
    $EDI_GnuPG=new gnuPG('/usr/bin/gpg',DIR_FS_CATALOG_LOCAL.'gnupg/');
  }
  return $EDI_GnuPG->Encrypt('"'.addslashes($g_uid).'"',$g_pass,'"'.addslashes($g_rcpt).'"',$data,false);
}

function edi_format_feed($fmtid,$rows) {
  $fr=tep_db_fetch_array(tep_db_query("SELECT * FROM ".TABLE_CONFIGURATION_DATA." WHERE configuration_key='$fmtid'"));
  preg_match_all('/(.*?)(\<\<(.*?)\>\>|$)/',preg_replace('/\r?\n/',"\r",$fr['configuration_data']),$fr_loops);
  for ($i=0;isset($fr_loops[0][$i]);$i++) {
    $fmt.=edi_format_feed_r($fr_loops[1][$i],$rows[sizeof($rows)-1]);
    if ($fr_loops[3][$i]) for ($j=0;isset($rows[$j]);$j++) $fmt.=edi_format_feed_r($fr_loops[3][$i],$rows[$j]);
  }
  return str_replace("\r","\n",$fmt);
}

function edi_format_feed_r($fmt,$vars) {
  preg_match_all('/\{(.*?)\}/',$fmt,$fr_parse);
  $fmt=preg_replace('/%([0-9A-F][0-9A-F])/e','chr(current(sscanf("$1","%02X")))',join('',$fr_parse[1]));
  if (preg_match_all('/(.*?)(\$(\w+)|$)/',$fmt,$fmt_parse)) {
    $fmt='';
    for ($i=0;isset($fmt_parse[0][$i]);$i++) {
      $fmt.=$fmt_parse[1][$i];
      if ($fmt_parse[3][$i]) {
	$fld=$fmt_parse[3][$i];
        if (isset($vars[$fld])) $fmt.=$vars[$fld];
	else if (preg_match('/^(\d+)_(\d+)/',$fld,$num_p)) $fmt.=$num_p[1]+$num_p[2]*($vars['line_num']-1);
	else $fmt.=$fmt_parse[2][$i];
      } else $fmt.=$fmt_parse[2][$i];
    }
  }
  return $fmt;
}

function edi_post_file($data,$subdir,$file) {
  $dir=DIR_FS_EDI.$subdir;
  if (!is_dir($dir)) mkdir($dir);
  
  if (!mkdir($dir)) {
  error_log("Could not make directory ".$dir.", 0);
  }
  
  $now=time();
  while (1) {
//    $fn=$dir.'/'.str_replace('/','',str_replace('*',date('YmdHis',$now),$file));
    $fn=$dir.'/'.str_replace('/','',str_replace('*',date('His',$now),preg_replace('/%(.)/e','date("\1");',$file)));
    if (!file_exists($fn)) break;
    $now++;
  }
  $fd=fopen($fn,'x');
  if (!fopen($fn)) {
error_log("Could not open directory ".$fn.", 0);
}
  fwrite($fd,$data);
  if (!fwrite($fd,$data)) {
error_log("Could not WRITE TO FILE ".$data.", 0);
}
  fclose($fd);
}

function edi_push_ftp($subdir,$dpath) {
  $ftp=ftp_connect(EDI_FTP_HOST);
  if (!$ftp || !ftp_login($ftp,EDI_FTP_USER,EDI_FTP_PASS)) return false;
  $dir=DIR_FS_EDI.$subdir;
  $dd=opendir($dir);
  while (($f=readdir($dd))!='') {
    if ($f[0]=='.') continue;
    if (ftp_put($ftp,"$dpath/$f","$dir/$f",FTP_BINARY)) unlink("$dir/$f");
  }
  ftp_close($ftp);  
}

function sendOrder(&$order) {
  if (!(EDI_ENABLE=='True')) return true;
  $qry=tep_db_query("SELECT o.*,op.*,p.products_model,p.products_sku,p.products_price_myself,p2c.categories_id,dcy.countries_iso_code_2 AS d_countries_iso_code_2,ds.zone_code AS d_zone_code,bcy.countries_iso_code_2 AS b_countries_iso_code_2,bs.zone_code AS b_zone_code,shp.value AS shipping_cost FROM ".TABLE_ORDERS." o LEFT JOIN ".TABLE_ORDERS_PRODUCTS." op ON o.orders_id=op.orders_id LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id=op.products_id LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p.master_products_id=p2c.products_id LEFT JOIN ".TABLE_COUNTRIES." dcy ON o.delivery_country=dcy.countries_name LEFT JOIN ".TABLE_ZONES." ds ON ds.zone_country_id=dcy.countries_id AND ds.zone_name=o.delivery_state LEFT JOIN ".TABLE_COUNTRIES." bcy ON o.billing_country=bcy.countries_name LEFT JOIN ".TABLE_ZONES." bs ON bs.zone_country_id=bcy.countries_id AND bs.zone_name=o.billing_state LEFT JOIN ".TABLE_ORDERS_TOTAL." shp ON shp.orders_id=o.orders_id AND shp.class='ot_shipping' WHERE o.orders_id='".$order->orderid."' GROUP BY op.products_id");
  $rows=Array();
  $line_num=0;
  while ($row=tep_db_fetch_array($qry)) {
    $row['line_num']=++$line_num;
    preg_match('/(\d+)-(\d+)-(\d+)( (\d+):(\d+):(\d+))?/',$row['date_purchased'],$d_p);
    $row['date_purchased']=$d_p[1].$d_p[2].$d_p[3];
    $row['date_purchased_6']=substr($row['date_purchased'],2,6);
    $row['time_purchased']=$d_p[5].$d_p[6];
    $row['date_to_ship']=date('Ymd',time()+86400*EDI_ORDER_SHIP_DAYS);
    $row['date_to_cancel']=date('Ymd',time()+86400*EDI_ORDER_CANCEL_DAYS);
    $row['final_price']=sprintf("%.2f",$row['final_price']);
    $row['shipping_cost']=sprintf("%.2f",$row['shipping_cost']);
    $row['shipping_method']=preg_replace('/^\w+_/','',$row['shipping_method']);
    $row['products_price_myself']=sprintf("%.2f",$row['products_price_myself']);
    $row['orders_id']=sprintf("%08d",$row['orders_id']+EDI_ORDER_ID_OFFSET);
    $row['sender_id']=sprintf("%-15s",EDI_SENDER_ID);
    $row['sender_id_qual']=EDI_SENDER_ID_QUAL;
    $row['rcpt_id']=sprintf("%-15s",EDI_RCPT_ID);
    $row['rcpt_id_qual']=EDI_RCPT_ID_QUAL;
    $row['sender_dept']=EDI_SENDER_DEPT;
    if (isset($row['d_countries_iso_code_2'])) $row['delivery_country']=$row['d_countries_iso_code_2'];
    if (isset($row['d_zone_code'])) $row['delivery_state']=$row['d_zone_code'];
    if (isset($row['b_countries_iso_code_2'])) $row['billing_country']=$row['b_countries_iso_code_2'];
    if (isset($row['b_zone_code'])) $row['billing_state']=$row['b_zone_code'];
    $rows[]=$row;
  }
  if (!$rows) return false;
  $fmt='EDI_850_FORMAT';
  $data=edi_format_feed($fmt,$rows);
  if (!$data) return false;
  if (EDI_850_GPG_ENABLE=='True') $data=edi_gpg_encrypt($data,EDI_850_GPG_UID,EDI_850_GPG_RCPT,EDI_850_GPG_PASS);
  $rs=NULL;
  if ($data) $rs=edi_post_file($data,'850',EDI_850_FILENAME);
  edi_push_ftp('850',EDI_FTP_850_PATH);
  return $rs;
}
?>
