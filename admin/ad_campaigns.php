<?php
/*
  $Id: admins.php,v 1 2003/08/24 23:21:27 MegaJim Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
   
  Manage admins permissions
*/

  require('includes/application_top.php');

//  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_SUPPLIER);

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
  <title><?php echo TITLE ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="JavaScript" src="<?=DIR_WS_INCLUDES?>javascript/prototype.lite.js"></script>
</head>
<body style="margin:0; background:transparent;">
<!-- header //-->
<?php
  require(DIR_WS_INCLUDES . 'header.php');
?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="0" cellpadding="2">
  <tr>
        <td width="100%" valign="top" colspan="2">
<?

  if (isset($HTTP_POST_VARS['campaign_id'])) {
    $campaign_id=$HTTP_POST_VARS['campaign_id']+0;
    $campaign=Array();
    $keywds=Array();
    foreach($HTTP_POST_VARS['keywords'] AS $keywd) if (!preg_match('/^\s*$/',$keywd)) $keywds[]=$keywd;
    $campaign=Array(
      campaign_ref=>$HTTP_POST_VARS['campaign_ref'],
      campaign_title=>$HTTP_POST_VARS['campaign_title'],
      campaign_keywords=>stripslashes(join(',',$keywds)),
    );
    if ($campaign_id==0) {
      tep_db_perform(TABLE_AD_CAMPAIGNS, $campaign);
    } else {
      tep_db_perform(TABLE_AD_CAMPAIGNS, $campaign, 'update', "campaign_id = '$campaign_id'");
    }
  }

  if (isset($HTTP_GET_VARS['campaign_id'])) {
    $c_query=tep_db_query("SELECT * FROM ".TABLE_AD_CAMPAIGNS." WHERE campaign_id='".tep_db_input($HTTP_GET_VARS['campaign_id'])."'");
    $campaign=tep_db_fetch_array($c_query);
    if (!$campaign) {
      $campaign=Array(campaign_title=>'New Ad Campaign',campaign_ref=>'campaign1');
      $c_ref=1;
      while (1) {
        $cr_query=tep_db_query("SELECT campaign_id FROM ".TABLE_AD_CAMPAIGNS." WHERE campaign_ref='".$campaign['campaign_ref']."'");
        if (tep_db_num_rows($cr_query)==0) break;
	$campaign['campaign_ref']='campaign'.++$c_ref;
      }
    }
    $keywds=Array();
    if (isset($campaign['campaign_keywords']) && $campaign['campaign_keywords']!='') $keywds=split(',',$campaign['campaign_keywords']);
    for ($i=1;$i<10;$i++) $keywds[]='';
?>
<script language="JavaScript">
function UpdateKeywords() {
  var ref=escape($('ref_input').value);
  for (var i=0;$('keyword_input_'+i);i++) {
    kw=$('keyword_input_'+i).value.replace(/[\,\s]+/g,' ').replace(/^\s+|\s+$/g,'');
    $('keyword_input_'+i).value=kw;
    if (kw!='') {
      var url='<?=HTTP_CATALOG_SERVER.DIR_WS_CATALOG.FILENAME_DEFAULT?>'+'?ref='+ref+'&keyw='+escape(kw);
      $('keyword_div_'+i).innerHTML='<a href="'+url+'">'+url+'</a>';
    } else $('keyword_div_'+i).innerHTML='';
  }
}
</script>
  <h2>Edit Campaign: <?=$campaign['campaign_title']?></h2>
  <form method="POST" action="ad_campaigns.php">
  <?=tep_draw_hidden_field('campaign_id',$campaign['campaign_id'])?>
  <table>
   <tr><td>Campaign Title:</td><td><?=tep_draw_input_field('campaign_title',$campaign['campaign_title'])?></td></tr>
   <tr><td>Campaign Code:</td><td><?=tep_draw_input_field('campaign_ref',$campaign['campaign_ref'],' id="ref_input" onChange="UpdateKeywords()"')?></td></tr>
  </table>
  <table>
  <tr><td>Keyword</td><td>URL</td></tr>
  <?
  for ($idx=0;isset($keywds[$idx]);$idx++) {
?>
   <tr><td><?=tep_draw_input_field('keywords[]',$keywds[$idx],' id="keyword_input_'.$idx.'" onChange="UpdateKeywords()"')?></td><td><div width="100%" id="keyword_div_<?=$idx?>"></div></td></tr>
<?
  }
?>
  </table>
<script language="JavaScript">
  UpdateKeywords();
</script>
    <?=tep_image_submit('button_update.gif', IMAGE_BUTTON_UPDATE)?>
    </form>
<?
  } else {
?>
  <h2>Ad Campaigns</h2>
  <table>
  <tr><td>#</td><td>RefCode</td><td>Campaign</td></tr>
<?
    $c_query=tep_db_query("SELECT * FROM ".TABLE_AD_CAMPAIGNS);
    while ($campaign=tep_db_fetch_array($c_query)) {
?>
  <tr><td><?=$campaign['campaign_id']?></td><td><a href="ad_campaigns.php?campaign_id=<?=$campaign['campaign_id']?>"><?=$campaign['campaign_ref']?></a></td><td><a href="ad_campaigns.php?campaign_id=<?=$campaign['campaign_id']?>"><?=$campaign['campaign_title']?></a></td></tr>
<?
    }
?>
  <tr><td>&nbsp;</td><td>&nbsp;</td><td>[<a href="ad_campaigns.php?campaign_id=0">Add New Campaign</a>]</td></tr>
  </table>
<?
  }
?>
</td>

    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
    </table></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
