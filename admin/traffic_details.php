<?php
  require('includes/application_top.php');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<meta http-equiv="Page-Exit" content="blendTrans(Duration=0.25)">
<title>Traffic Analytics</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript"  src="js/ajaxfade.js"></script>
<script type="text/javascript" src="js/popcalendar.js"></script>
</head>
<body style="margin:0; background:transparent;">

<?php 

require(DIR_WS_INCLUDES . 'header.php'); 

$date_from=isset($_GET['date_from'])?$_GET['date_from']:date('m/d/Y',time()-86400*7*8);
$date_to=isset($_GET['date_to'])?$_GET['date_to']:date('m/d/Y',time());


$t_types=Array('search'=>'Natural Search','referral'=>'Referral Traffic','paid'=>'Paid Traffic','return'=>'Returning Customers',''=>'Direct Link');
$t_sources=Array('google'=>'Google','yahoo'=>'Yahoo','bing'=>'Bing','amazon'=>'Amazon','aol'=>'AOL');
$hdr='';
$trk_cond=1;
$qline="date_from=$date_from&date_to=$date_to&traffic_type=";

if (isset($_REQUEST['traffic_type'])) {
  $trk_cond.=" AND traffic_type='".$_REQUEST['traffic_type']."'";
  $hdr.=' '.$t_types[$_REQUEST['traffic_type']];
  $qline.=urlencode($_REQUEST['traffic_type']).'&traffic_source=';
  if (isset($_REQUEST['traffic_source'])) {
    $trk_cond.=" AND traffic_source='".$_REQUEST['traffic_source']."'";
    $sr=$_REQUEST['traffic_type']=='search'?$t_sources[$_REQUEST['traffic_source']]:NULL;
    $hdr.=$sr?" $sr":' '.$_REQUEST['traffic_source'];
    $qline.=urlencode($_REQUEST['traffic_source']).'&traffic_keywords=';
    $key_field='traffic_keywords';
  } else $key_field='traffic_source';
} else $key_field='traffic_type';


$qry=IXdb::query("SELECT * FROM supertracker WHERE traffic_type IS NULL");
while ($row=IXdb::fetch($qry)) {
  $src=Array();
  if (preg_match('/(^|&)ref=(.*?)&keyw=(.*?)(&\S+=|$)/',$row['landing_page'],$kws)) {
    $src['traffic_type']='paid';
    $src['traffic_source']=$kws[2];
    $src['traffic_keywords']=$kws[3];
  } elseif (preg_match('|^http://([\w\-]+\.)?google\.[\w\.]+/|',$row['referrer']) && preg_match('/(^|&)q=(.*?)(&\S+=|$)/',$row['referrer_query_string'],$kws)) {
    $src['traffic_type']='search';
    $src['traffic_source']='google';
    $src['traffic_keywords']=$kws[2];
  } elseif (preg_match('|^http://([\w\-]+\.)?search\.yahoo\.[\w\.]+/|',$row['referrer']) && preg_match('/(^|&)p=(.*?)(&\S+=|$)/',$row['referrer_query_string'],$kws)) {
    $src['traffic_type']='search';
    $src['traffic_source']='yahoo';
    $src['traffic_keywords']=$kws[2];
  } elseif (preg_match('|^http://([\w\-]+\.)?search\.msn\.[\w\.]+/|',$row['referrer']) && preg_match('/(^|&)q=(.*?)(&\S+=|$)/',$row['referrer_query_string'],$kws)) {
    $src['traffic_type']='search';
    $src['traffic_source']='msn';
    $src['traffic_keywords']=$kws[2];
  } elseif (preg_match('|^http://([\w\-]+\.)?search\.live\.[\w\.]+/|',$row['referrer']) && preg_match('/(^|&)q=(.*?)(&\S+=|$)/',$row['referrer_query_string'],$kws)) {
    $src['traffic_type']='search';
    $src['traffic_source']='live';
    $src['traffic_keywords']=$kws[2];
  } elseif (preg_match('|^http://([\w\-]+\.)?(aol)?search\.aol\.[\w\.]+/|',$row['referrer']) && preg_match('/(^|&)query=(.*?)(&\S+=|$)/',$row['referrer_query_string'],$kws)) {
    $src['traffic_type']='search';
    $src['traffic_source']='aol';
    $src['traffic_keywords']=$kws[2];
  } elseif (preg_match('|^https?://([\w\-\.]+)/|',$row['referrer'],$rfr)) {
    if ($rfr[1]==SITE_DOMAIN) $src['traffic_type']='return';
    else {
      $src['traffic_type']='referral';
      $src['traffic_source']=$rfr[1];
    }
  } else $src['traffic_type']='';
  
  if ($src) IXdb::store('update','supertracker',$src,'tracking_id='.$row['tracking_id']);
}

?>

<table border="0" width="100%" cellspacing="0" cellpadding="0" align="center">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td height="48"><table><tr>
<td width="58"><img src="/admin/images/piechart-icon.gif" width="48" height="48" alt=""></td>
								<td class="pageHeading" nowrap>Traffic Details</td>	
</tr></table>

	    
	    
	    </td>
            <td class="pageHeading" align="right">
					  <table border="0" cellpadding="0" cellspacing="0">
					  <?php echo tep_draw_form('date_range', 'traffic_details.php', '', 'get'); ?>
                        <tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?=$date_from?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?=$date_to?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding-right:7px;">&nbsp;<a href="javascript:document.date_range.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a>                          </td>
                        </tr>
</form>
</table>
	    </td>
          </tr>
	  <tr>
	  <td colspan="2" style="padding:0 10px 0 10px">
<div style="font:bold 12px arial;">Results for<span style="color:#CC6600"><?=$hdr?':&nbsp; '.$hdr:''?></span><br></div>
<img id="chart_image" width="775" height="400">
	  </td>
	  </tr>
	  <tr>
	  <td align="left" class="dataTableContent"><img src="images/sales_chart_icon_bar.gif"> Hits</td>
	  <td align="right" class="dataTableContent">(Conv Rate %) &nbsp; Gross Sales $ <img src="images/sales_chart_icon_line.gif"></td>
        </table>
<script language="javascript">
  var chartSalesColors=['7F7FFF','007F00'];
  var chartViewsColors=['3F3FDF50','003F0050'];
  var chartPids=[];
  function showChart(pid) {
    if (chartPids.length==1 && chartPids[0]==pid) return true;
    var fadd=true;
    for (var i=0;chartPids[i]!=null;i++) if (chartPids[i]==pid || chartSalesColors[i+1]==null) {
      var icn=$('chart_icon_'+escape(chartPids[i]));
      if (icn) icn.innerHTML='';
      if (chartPids[i]==pid) fadd=false;
      chartPids.splice(i--,1);
    }
    if (fadd) chartPids.push(pid);
    var img=$('chart_image');
//    var url='sales_chart.php?width='+img.width+'&height='+img.height+'&start_date=<?=$date_from?>&end_date=<?=$date_to?>';
    var url='traffic_details_chart.php?width=780&height=400&start_date=<?=$date_from?>&end_date=<?=$date_to?>';
    for (var i=0;chartPids[i]!=null;i++) {
      url+='&traffic[]=1&<?=str_replace('=','[]=',$qline)?>'+escape(chartPids[i])+'&sales_color[]='+chartSalesColors[i]+'&views_color[]='+chartViewsColors[i];
      var icn=$('chart_icon_'+escape(chartPids[i]));
      if (icn) icn.innerHTML='<img src="images/sales_chart_icon_'+i+'.gif" border="0">';
    }
//    img.src=url;
    ajaxFade(img,1,url);
  }
</script>	
	
	</td>
      </tr>
      <tr>
        <td>
	
	<table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" width="50%"><? if ($key_field=='traffic_keywords') { ?>Keyword<? } else { ?>Source<? } ?></td>
                <td class="dataTableHeadingContent" align="center">Hits</td>
                <td class="dataTableHeadingContent" align="center">Orders</td>
                <td class="dataTableHeadingContent" align="center">Total</td>
                <td class="dataTableHeadingContent" align="center">Conv. %</td>
                <td class="dataTableHeadingContent" width="72" align="center">Compare</td>
              </tr>
<?php


  if (isset($_GET['page']) && ($_GET['page'] > 1)) $rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS;
//  $products_query_raw = "select p.products_id, p.products_ordered, pd.products_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pd.language_id = '" . $languages_id. "' and p.products_ordered > 0 group by pd.products_id order by p.products_ordered DESC, pd.products_name";
  
  
    $products_query_raw = "select $key_field AS key_field, COUNT(0) AS hits, COUNT(o.orders_id) AS sales,SUM(ot.value) AS total,traffic_source,traffic_keywords from supertracker t LEFT JOIN orders o ON (t.order_id=o.orders_id) LEFT JOIN orders_total ot ON (o.orders_id=ot.orders_id AND ot.class='ot_total') WHERE time_arrived>='".date('Y-m-d',strtotime($date_from))."' AND time_arrived<'".date('Y-m-d',strtotime($date_to)+86400)."' AND $trk_cond GROUP BY $key_field order by hits DESC";

  
  $products_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $products_query_raw, $products_query_numrows);


 $key0=NULL;
  $rows = 0;
  $products_query = tep_db_query($products_query_raw);
  while ($row = tep_db_fetch_array($products_query)) {
    if (!$key0) $key0=$row['key_field'];
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
?>
              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
                <td class="dataTableContent">
		<? if (($key_field=='traffic_type' && isset($row['traffic_source'])) || ($key_field=='traffic_source' && isset($row['traffic_keywords']))) { ?><a href="traffic_details.php?<?=$qline?><?=urlencode($row['key_field'])?>"><?=$key_field=='traffic_type'?$t_types[$row['key_field']]:$row['key_field']?></a><? } else { ?>
		<?=$key_field=='traffic_type'?$t_types[$row['key_field']]:$row['key_field']?><? } ?></td>
                <td class="dataTableContent"><?=$row['hits']?></td>
                <td class="dataTableContent" style="padding:0 0 0 10px"><?=$row['sales']?></td>
                <td class="dataTableContent"><?=sprintf('$%.2f',$row['total'])?></td>
                <td class="dataTableContent"><?=$row['hits']>0?sprintf('%.1f%%',$row['sales']/$row['hits']*100):'&nbsp;'?></td>
		<td class="dataTableContent"><img src="images/graph-icon.jpg" border="0" style="cursor:pointer;" alt="Sales/Views Chart" onClick="showChart('<?=$row['key_field']?>');"><span id="chart_icon_<?=str_replace('+','%20',urlencode(stripslashes($row['key_field'])))?>"></span></td>
              </tr>
<?php
  }
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<? if ($key0) { ?>
<script language="javascript">
  showChart('<?=$key0?>');
</script>
<? } ?>

  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
