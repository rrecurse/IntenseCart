<?php

  require('includes/application_top.php');


  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');
  if ($action == 'display_other') {

  $referrals_query_raw = "select count(ci.customers_info_source_id) as no_referrals, so.sources_other_name as sources_name from " . TABLE_CUSTOMERS_INFO . " ci, " . TABLE_SOURCES_OTHER . " so where ci.customers_info_source_id = '9999' and so.customers_id = ci.customers_info_id group by so.sources_other_name order by so.sources_other_name DESC"; 
//echo "if";echo  $referrals_query_raw;  
  } else {

 /* $referrals_query_raw = "SELECT ci.customers_info_date_account_created AS date_created, COUNT( ci.customers_info_source_id ) AS no_referrals, s.sources_name, s.sources_id
FROM customers_info ci
LEFT JOIN sources s ON s.sources_id = ci.customers_info_source_id
WHERE DATE( ci.customers_info_date_account_created ) >= $date_from
AND DATE( ci.customers_info_date_account_created ) >= DATE_ADD( $date_to, INTERVAL 1
DAY )
GROUP BY s.sources_id
ORDER BY no_referrals DESC";*/

//print "<pre>"; print_r($_REQUEST); print "</pre>";

//$date_from=isset($_GET['date_from'])?$_GET['date_from']:date('m-d-Y');
//$date_to=isset($_GET['date_to'])?$_GET['date_to']:date('m-d-Y');
extract($_REQUEST);
 // $referrals_query_raw = "select count(ci.customers_info_source_id) as no_referrals, s.sources_name, s.sources_id from " . TABLE_CUSTOMERS_INFO . " ci LEFT JOIN " . TABLE_SOURCES . " s ON s.sources_id = ci.customers_info_source_id group by s.sources_id order by no_referrals DESC";
 if (isset($_REQUEST['hid']))
 {
$referrals_query_raw = "select count(ci.customers_info_source_id) as no_referrals, s.sources_name, s.sources_id from " . TABLE_CUSTOMERS_INFO . " ci LEFT JOIN " . TABLE_SOURCES . " s ON s.sources_id = ci.customers_info_source_id where  DATE_FORMAT(ci.customers_info_date_account_created, '%m/%d/%Y') between '".$date_from."' and '".$date_to."'  group by s.sources_id order by no_referrals DESC";
 }
 else
  {
$referrals_query_raw = "select count(ci.customers_info_source_id) as no_referrals, s.sources_name, s.sources_id from " . TABLE_CUSTOMERS_INFO . " ci LEFT JOIN " . TABLE_SOURCES . " s ON s.sources_id = ci.customers_info_source_id group by s.sources_id order by no_referrals DESC";
  }  
   
//echo "ELSE ";echo  $referrals_query_raw;  

  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/popcalendar.js"></script>
</head>
<body style="background-color:transparent; margin:0;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td style="padding:10px 0 15px 0"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right">

<table border="0" cellpadding="0" cellspacing="0">
<?

//$date_from=isset($_GET['date_from'])?$_GET['date_from']:date('m-d-Y');
//$date_to=isset($_GET['date_to'])?$_GET['date_to']:date('m-d-Y');
//$date_from=isset($_GET['date_from'])?$_GET['date_from']:date('m/d/Y');
//$date_to=isset($_GET['date_to'])?$_GET['date_to']:date('m/d/Y');

?>
					  <?php 

echo tep_draw_form('date_range', 'stats_referral_sources.php', '', 'post'); ?>

                        <tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);"  <?php if(isset($_POST['date_from']) ) { ?> value="<?php echo $date_from; ?>" <?php } else {?> value="" <?php  } ?> size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);"  style="font:bold 9px arial;" <?php if(isset($_POST['date_to']) ) { ?> value="<?php echo $date_to; ?>" <?php } else {?> value="" <?php  } ?>  size="12" maxlength="11" textfield></td>
						  <input type="hidden" name="hid" value="1" />
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding-right:7px; padding-top:1px;">&nbsp;<a href="javascript:document.date_range.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a>                          </td>
                        </tr>
</form>
</table>

</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_REFERRALS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_VIEWED; ?>&nbsp;</td>
              </tr>
<?php
  if (isset($HTTP_GET_VARS['page']) && ($HTTP_GET_VARS['page'] > 1)) $rows = $HTTP_GET_VARS['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS;
  $rows = 0;
  $presplit_query = tep_db_query($referrals_query_raw);
  $presplit_query_numrows = tep_db_num_rows($presplit_query);
  $referrals_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $referrals_query_raw, $referrals_query_numrows);
  $referrals_query_numrows = $presplit_query_numrows;
  $referrals_query = tep_db_query($referrals_query_raw);
  while ($referrals = tep_db_fetch_array($referrals_query)) {
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
    if ( tep_not_null($referrals['sources_name']) ) {
?>
              <tr class="dataTableRow" onMouseOver="rowOverEffect(this)" onMouseOut="rowOutEffect(this)">
<?php
    } else {
?>
              <tr class="dataTableRow" onMouseOver="rowOverEffect(this)" onMouseOut="rowOutEffect(this)" onClick="document.location.href='<?php echo tep_href_link(FILENAME_STATS_REFERRAL_SOURCES, 'action=display_other'); ?>'">
<?php
    }
?>
                <td class="dataTableContent"><?php echo $rows; ?>.</td>
                <td class="dataTableContent"><?php echo (tep_not_null($referrals['sources_name']) ? $referrals['sources_name'] : '<b style="cursor:pointer">' . TEXT_OTHER . '</b>');?>&nbsp;</td>
                <td class="dataTableContent" align="center"><?php echo $referrals['no_referrals']; ?>&nbsp;</td>
              </tr>
<?php
  }
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $referrals_split->display_count($referrals_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_REFERRALS); ?></td>
                <td class="smallText" align="right"><?php echo $referrals_split->display_links($referrals_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_all_get_params(array('page')) ); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
