<?php
  $no_sts=1;
  require("includes/application_top.php");

  $blk=IXblock::block('blk_box_cart_popup');
  $blk->setContext(Array('cart'=>&$cart),Array('dir'=>$_REQUEST['dir']));
  $blk->render(Array());
?>
<script language="javascript">
  cartBoxObj.showBox(5);
</script>
