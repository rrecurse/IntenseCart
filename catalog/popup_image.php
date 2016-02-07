<?php
  require('includes/application_top.php');

  $navigation->remove_current_page();

  $products_query = tep_db_query("SELECT pd.products_name, 
										 p.products_image, 
										 p.products_image_xl_1, 
										 p.products_image_xl_2, 
										 p.products_image_xl_3, 
										 p.products_image_xl_4, 
										 p.products_image_xl_5, 
										 p.products_image_xl_6, 
										 p.products_image_xl_7, 
										 p.products_image_xl_8 
								 FROM " . TABLE_PRODUCTS . " p 
								 LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id 
								 WHERE p.products_status = '1' 
								 AND p.products_id = '" . (int)$HTTP_GET_VARS['pid'] . "' 
								 AND pd.language_id = '" . (int)$languages_id . "'
								 ");

  if (tep_db_num_rows($products_query) > 0) {
    $pi = tep_db_fetch_array($products_query);
    $page_title = 'Enlarged Image - ' . $pi['products_name'];
  } else {
    $page_title = 'Enlarged Image';
  }
?>
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?php echo $page_title; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
  <script language="JavaScript" type="text/JavaScript">
  <!--
  function preloadImages() { //v3.0
    var d=document; if(d.images){ if(!d.p) d.p=new Array();
      var i,j=d.p.length,a=preloadImages.arguments; for(i=0; i<a.length; i++)
      if (a[i].indexOf("#")!=0){ d.p[j]=new Image; d.p[j++].src=a[i];}}
  }
  
  function swapImgRestore() { //v3.0
    var i,x,a=document.sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
  }
  
  function findObj(n, d) { //v4.01
    var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
      d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
    if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
    for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=findObj(n,d.layers[i].document);
    if(!x && d.getElementById) x=d.getElementById(n); return x;
  }
  
  function swapImage() { //v3.0
    var i,j=0,x,a=swapImage.arguments; document.sr=new Array; for(i=0;i<(a.length-2);i+=3)
     if ((x=findObj(a[i]))!=null){document.sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
  }
  //-->
  </script>
<link rel="stylesheet" type="text/css" href="/layout/productInfo.css">
</head>
<body style="margin:0;">

<div class="prodinfo-pop_topbar">
<a href="javascript:window.close();" class="prodinfo-pop_topbar-link">x &nbsp;close window</a>
</div>

<?
if (tep_db_num_rows($products_query) > 0) {

  $image_array = array();

  for ($x = 1; $x <= 8; $x++) {
    if ($pi['products_image_xl_' . $x] != NULL) {
    $image_array[] = $pi['products_image_xl_' . $x];
    }
  }
  if (sizeof($image_array) > 0) {
 
?>
<table align="center" cellpadding="0" cellspacing="0" style="width:100%;">
    <tr>
        <td align="center" valign="top" style="padding-top:15px; padding-bottom:10px;">
          <?=tep_image(DIR_WS_IMAGES.$pi['products_image'],'',LARGE_IMAGE_WIDTH,LARGE_IMAGE_HEIGHT,' id="mainimage" alt="" style="border:1px solid #000000"')?></td>
      <td rowspan="2" align="center" valign="top">
        <table cellspacing=0 cellpadding=0>
  <?
  $cur_row = 0;
  $max_row = 99;
  $cur_col = 0;
  $max_col = 0;
  $new_row = true;
  foreach($image_array as $img) {
    if ($new_row) {
      echo '        <tr>' . "\r\n";
      $new_row = false;
      $cur_row = 0;
      $cur_col = 0;
    }
    $limg=preg_replace("/^.*?src=\"(.*?)\".*$/",'\1',tep_image(DIR_WS_IMAGES.$img,'',LARGE_IMAGE_WIDTH,LARGE_IMAGE_HEIGHT));
?>
            <td valign="top" style="padding-left:5px; padding-bottom:3px; padding-top:25px;"><a href="javascript:void(0);" onMouseOver="swapImage('mainimage','','<?=$limg?>',1)" onMouseOut="swapImgRestore()"><?=tep_image(DIR_WS_IMAGES.$img,'',ULT_THUMB_IMAGE_WIDTH,ULT_THUMB_IMAGE_HEIGHT,' id="image1" border="0" alt=""')?></a></td>
  <?
    $cur_col++;
    if ($cur_col > $max_col) {
      $new_row = true;
      echo '        </tr>' . "\r\n";
    }
  }
  if ($cur_col <= $max_col) {
    echo '        </tr>' . "\r\n";
  }
?>
  
      </table>
    </td>
    </tr>
</table>
<?
    
  } else {
    $img_loc = '';
    if ($pi['products_image'] != NULL) {
      $img_loc = $pi['products_image'];
    }
    if ($img_loc != '') {
      echo tep_image(DIR_WS_IMAGES . $img_loc, '', LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT, 'hspace="1" vspace="5" alt=""');
    } else {
      echo "No Images";
    }
  }
} else {
  echo "No Images";
}

?>


</body>
</html>
<?php require('includes/application_bottom.php'); ?>
