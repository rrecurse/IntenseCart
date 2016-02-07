<?

class account_upload extends IXmodule {
  var $customer_name=NULL;
  function getName() {
    return "Customer Uploads";
  }
  function getUploadDir() {
    $root=DIR_FS_SHARE.'/customer_uploads';
    if (!is_dir($root)) @mkdir($root);
    return isset($this->customer_name)?$root.'/'.$this->customer_name:NULL;
  }
  function getUploadSize() {
    $dir=$this->getUploadDir();
    $d=@opendir($dir);
    if (!$d) return 0;
    $s=0;
    while ($f=readdir($d)) if (!preg_match('/^\./',$f)) $s+=filesize("$dir/$f");
    closedir($d);
    return $s/1048576;
  }
  function renderCustomerBox($custid) {
    $cust_row=tep_db_fetch_array(tep_db_query("SELECT customers_email_address FROM customers WHERE customers_id='".addslashes($custid)."'"));
    $this->customer_name=$cust_row['customers_email_address'];
    $upsize=$this->getUploadSize();
    if ($upsize<$this->getConf('customer_quota')) {
      if (isset($_FILES['cust_upload']) && isset($_FILES['cust_upload']['tmp_name'])) {
        $dir=$this->getUploadDir();
        if (!is_dir($dir)) {
	  umask(0);
	  @mkdir($dir);
	}
	$fpath=$dir.'/'.($fname=preg_replace('|^.*[/\\\\]|','',$_FILES['cust_upload']['name']));
        if (rename($_FILES['cust_upload']['tmp_name'],$fpath)) {
	  chmod($fpath,0666);
	  $result='Upload Successful';
	  tep_db_query("INSERT INTO customers_upload_history (customers_id,file_name,file_size,upload_date) VALUES ('".addslashes($custid)."','".addslashes($fname)."','".@filesize($fpath)."',NOW())");
          $upsize=$this->getUploadSize();
        } else $result='Upload Failed';
      }
    }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b>Customer Uploads</b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td width="60">&nbsp;</td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<? if (isset($result)) { ?>
                  <tr>
                    <td class="main"><b><?=$result?></b></td>
                  </tr>
<? }
   if ($upsize<$this->getConf('customer_quota')) {
?>
                  <tr>
                    <td class="main"><?=tep_draw_form('cust_upload_form','account.php','POST','enctype="multipart/form-data"')?><input type="file" name="cust_upload"><input type="submit" name="cust_upload_submit" value="Upload"></form></td>
                  </tr>
<? } ?>
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link('account_upload_history.php', '', 'SSL') . '">View Upload History</a>'; ?></td>
                  </tr>
                </table></td>
                <td width="10" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?
  }


  function validateConf($key,$val) {
    switch ($key) {
      default: break;
    }
    return NULL;
  }

  function isReady() {
    return true;
  }

  function listConf() {
    return Array(
      'customer_quota'=>Array('title'=>'Max total megabytes per client','desc'=>'','default'=>'100'),
    );
  }
}

?>