<?php
  
// Customer Account Extn
  class custaccount_edi extends IXmodule {
    function getName() {
      return 'EDI Settings';
    }
    function getAdminFields($cus_id) {
      if (!IXdb::read("SELECT customers_group_id FROM customers WHERE customers_id='$cus_id'",NULL,'customers_group_id')) return NULL;
      $qual_lst=Array(
        Array('id'=>'','text'=>'N/A'),
        Array('id'=>'01','text'=>'01 - DUNS Number'),
        Array('id'=>'02','text'=>'02 - SCAC Code'),
        Array('id'=>'08','text'=>'08 - UCC EDI Comm ID'),
        Array('id'=>'12','text'=>'12 - Telephone Number'),
        Array('id'=>'13','text'=>'13 - UCS Code'),
        Array('id'=>'14','text'=>'14 - DUNS + Suffix'),
        Array('id'=>'16','text'=>'16 - DUNS + 4 char Suffix'),
        Array('id'=>'ZZ','text'=>'ZZ - Mutually Defined'),
      );
      $flds=IXdb::read("SELECT * FROM customers_extra WHERE customers_id='$cus_id'",'customers_extra_key','customers_extra_value');
      return Array(
        Array('title'=>'EDI Sender Qualifier','html'=>tep_draw_pull_down_menu("extra[edi_sender_qual]",$qual_lst,$flds['edi_sender_qual'])),
        Array('title'=>'EDI Sender Code','html'=>'<input type="text" name="extra[edi_sender]" value="'.htmlspecialchars($flds['edi_sender']).'">'),
        Array('title'=>'EDI Sender Department','html'=>'<input type="text" name="extra[edi_sender_dept]" value="'.htmlspecialchars($flds['edi_sender_dept']).'">'),
      );
    }
    function isReady() {
      return true;
    }
  }
?>
