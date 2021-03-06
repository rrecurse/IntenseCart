<?

// $ID|$TRADING_PARTNER_ID|$TRANSACTION_SET_ID|$RECORD_ID|$ITEM_NO|$TRANS_TYPE|$ORDER_NO|$ORDER_LINE|$REQ_NAME|$AGGREGATION_CODE|$ORDER_DATE|$NEED_DATE|$PRIORITY|$QTY|$UOM|$UNIT_PRICE|$CUST_NAME|$CUST_ADDR1|$CUST_ADDR2|$CUST_ADDR3|$CUST_CITY|$CUST_STATE|$CUST_ZIP9|$REQUISITIONER_PHONE|$REQUISITIONER_FAX|$REQUISITIONER_EMAIL|$ALTERNATE_ADDR_IND|$REQUISITIONER_NAME|$CUST_ID|$PAYMENT_METHOD|$PAYMENT_NO|$CONTRACT_NO|$CONTRACT_LINE|$ATTACHMENT_FILE_NAME|$ORDER_NOTES|$LINE_NOTES|$REVIEWED|$ModifyTime|$ModifiedBy

$EDI_GnuPG=NULL;

if (EDI_ENABLE=='True' && !is_dir(DIR_FS_EDI)){ 
mkdir(DIR_FS_EDI);
chmod(DIR_FS_EDI,0777);
}

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
        if (isset($vars[$fld])) $fmt.=edi_escape_field($vars[$fld]);
	else if (preg_match('/^(\d+)_(\d+)/',$fld,$num_p)) $fmt.=$num_p[1]+$num_p[2]*($vars['line_num']-1);
	else $fmt.=$fmt_parse[2][$i];
      } else $fmt.=$fmt_parse[2][$i];
    }
  }
  return $fmt;
}

function edi_escape_field($v) {
  $v=preg_replace('/\r?\n/s',' // ',str_replace('~','*',$v));
  if (strlen($v)>50) $v=substr($v,0,47).'...';
  return $v;
}

function edi_post_file($data,$subdir,$file,$oID) {
//echo "Data ".$data."<br/>Subdir ".$subdir."<br/>File ".$file."<br/>oID ".$oID;die();//JJRJR Debug
 $dir=DIR_FS_EDI.$subdir;
  if (!is_dir($dir)){
  mkdir($dir);
  chmod($dir,0777);  
  }
if(!mkdir($dir)){
error_log("couldnt make subdirectory 850 - no idea why - maybe permissions", 0);
}
  $now=time();
  $e_row=tep_db_fetch_array(tep_db_query("SELECT * FROM orders_edi WHERE orders_id='$oID'"));
  if ($e_row) $fn=$e_row['edi_file'];
  else {
    while (1) {
//    $fn=$dir.'/'.str_replace('/','',str_replace('*',date('YmdHis',$now),$file));
      $fn=str_replace('/','',str_replace('*',date('His',$now),preg_replace('/%(.)/e','date("\1",$now);',$file)));
      if (!file_exists($dir.'/'.$fn)) {
        tep_db_query("INSERT IGNORE INTO orders_edi (orders_id,edi_file,date_posted) VALUES ('$oID','$fn',NOW())");
        if (tep_db_num_rows()) break;
      }
      $now+=60;
    }
  }
  $fd=fopen($dir.'/'.$fn,'x');
 if(!fopen($dir.'/'.$fn)){
error_log("stinkin error! - coudnt open filename", 0);
} 
fwrite($fd,$data);
if(!fwrite($fd)){
error_log("stinkin error! - coudnt write to file", 0);
}
if($data=NULL){
error_log("stinkin error - no data to write to file!", 0);
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

function edi_send_850($order_id) {
  if (!(EDI_ENABLE=='True')) return true;
//  if ((EDI_ENABLE=='True')) return true;
  $qry=tep_db_query("SELECT o.*,op.*,p.products_model,p.products_sku,p.products_price_myself,p2c.categories_id,dcy.countries_iso_code_2 AS d_countries_iso_code_2,ds.zone_code AS d_zone_code,bcy.countries_iso_code_2 AS b_countries_iso_code_2,bs.zone_code AS b_zone_code,shp.value AS shipping_cost,(SELECT customers_extra_value FROM customers_extra WHERE customers_id=o.customers_id AND customers_extra_key='edi_sender') AS snd_code,(SELECT customers_extra_value FROM customers_extra WHERE customers_id=o.customers_id AND customers_extra_key='edi_sender_qual') AS snd_qual,(SELECT customers_extra_value FROM customers_extra WHERE customers_id=o.customers_id AND customers_extra_key='edi_sender_dept') AS snd_dept FROM ".TABLE_ORDERS." o LEFT JOIN ".TABLE_ORDERS_PRODUCTS." op ON o.orders_id=op.orders_id LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id=op.products_id LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p.master_products_id=p2c.products_id LEFT JOIN ".TABLE_COUNTRIES." dcy ON o.delivery_country=dcy.countries_name LEFT JOIN ".TABLE_ZONES." ds ON ds.zone_country_id=dcy.countries_id AND ds.zone_name=o.delivery_state LEFT JOIN ".TABLE_COUNTRIES." bcy ON o.billing_country=bcy.countries_name LEFT JOIN ".TABLE_ZONES." bs ON bs.zone_country_id=bcy.countries_id AND bs.zone_name=o.billing_state LEFT JOIN ".TABLE_ORDERS_TOTAL." shp ON shp.orders_id=o.orders_id AND shp.class='ot_shipping' WHERE o.orders_id='".$order_id."' AND o.orders_id=op.orders_id AND p.products_class='product_default' GROUP BY op.products_id");
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
    $row['edi_orders_id']=sprintf("%08d",$row['orders_id']+EDI_ORDER_ID_OFFSET);
    $row['sender_id']=sprintf("%-15s",$row['snd_qual']?$row['snd_code']:EDI_SENDER_ID);
    $row['sender_id_qual']=$row['snd_qual']?$row['snd_qual']:EDI_SENDER_ID_QUAL;
    $row['rcpt_id']=sprintf("%-15s",EDI_RCPT_ID);
    $row['rcpt_id_qual']=EDI_RCPT_ID_QUAL;
    $row['sender_dept']=$row['snd_qual']?$row['snd_dept']:EDI_SENDER_DEPT;
    if (isset($row['d_countries_iso_code_2'])) $row['delivery_country']=$row['d_countries_iso_code_2'];
    if (isset($row['d_zone_code'])) $row['delivery_state']=$row['d_zone_code'];
    if (isset($row['b_countries_iso_code_2'])) $row['billing_country']=$row['b_countries_iso_code_2'];
    if (isset($row['b_zone_code'])) $row['billing_state']=$row['b_zone_code'];
    $rows[]=$row;
  }
  if (!$rows){
error_log("stinkin error - no rows!", 0);
 return false;
}
  $fmt='EDI_850_FORMAT';
  $data=edi_format_feed($fmt,$rows);
  if (!$data) {
error_log("stinkin error - no data!", 0);
return false;
}
  if (EDI_850_GPG_ENABLE=='True') $data=edi_gpg_encrypt($data,EDI_850_GPG_UID,EDI_850_GPG_RCPT,EDI_850_GPG_PASS);
  $rs=NULL;
  if ($data) $rs=edi_post_file($data,'850',EDI_850_FILENAME,$order_id);
  edi_push_ftp('850',EDI_FTP_850_PATH);
  return $rs;
}

?>
