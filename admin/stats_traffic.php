<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################
	

	require('includes/application_top.php');

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Traffic Stats</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<script language="javascript" src="js/prototype.lite.js"></script>
<script language="javascript" src="js/ajaxfade.js"></script>
<script language="javascript" src="js/popcalendar.js"></script>
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); 

$date_from = (isset($_GET['date_from'])) ? $_GET['date_from'] : date('m/d/Y',time()-86400*7);
$date_to = (isset($_GET['date_to'])) ? $_GET['date_to'] : date('m/d/Y', time());

?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" height="36">Natural Traffic Statistics</td>
            <td class="pageHeading" align="right">
					<?php echo tep_draw_form('date_range', 'stats_traffic.php', '', 'get'); ?>
					  <table border="0" cellpadding="0" cellspacing="0">

                        <tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?=$date_from?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?=$date_to?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding-right:7px; padding-top:1px;">&nbsp;<a href="javascript:document.date_range.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a>                          </td>
                        </tr>
</table>
</form>
	    </td>
          </tr>
	  <tr>
	  <td colspan="2" align="left">
<img id="chart_image" width="100%" height="400" src="traffic_chart.php?width=750&height=400&start_date=<?=$date_from?>&end_date=<?=$date_to?>&bg_color=F0F5FB&bg_plot_color=F0F5FB&pad_right=10&pad_left=40">
	  </td>
	  </tr>
	  <tr>
	  <td colspan="2" align="center"><br><table cellspacing="0" cellpadding="2"><tr>
	  <td><table width="93" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#FF0000;"></div></td>
                              <td width="81" nowrap style="padding-left:8px;" class="dataTableContent">Google Search</td>
                            </tr>
          </table></td>
	  <td><table width="90" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#FF90FF;"></div></td>
                              <td width="78" nowrap style="padding-left:8px;" class="dataTableContent">Yahoo Search</td>
                            </tr>
                          </table></td>
	  <td><table width="81" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#5B5BFF;"></div></td>
                              <td width="69" nowrap style="padding-left:8px;" class="dataTableContent">Bing Search</td>
                            </tr>
                          </table></td>
	  <td><table width="80" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#90C6FF;"></div></td>
                                <td width="68" nowrap style="padding-left:8px;" class="dataTableContent">AOL Search</td>
                              </tr>
                          </table></td>
	  <td><table width="52" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="12" align="center"><div style="height:11px; width:12px; border:1px solid #333333; background-color:#5BFF5B;"></div></td>
                                <td width="40" nowrap style="padding-left:8px;" class="dataTableContent">Other</td>
                              </tr>
                          </table></td>
	  </tr></table>
	  </td>
	  </tr>

          <tr>
            <td colspan="2" class="pageHeading" height="36">&nbsp;</td>
	  </tr>
          <tr>
            <td class="pageHeading" height="36">Conversion Statistics for
	    <select id="traffic_source" onChange="ajaxFade($('conv_chart_image'),2,$('conv_chart_image').src.replace(/(&srcs.*)?$/,this.value))">
	    <option value="&srcs[]=google&sales_color[]=FF4040&views_color[]=BF0000">Google</option>
	    <option value="&srcs[]=yahoo&sales_color[]=FF40FF&views_color[]=BF00BF">Yahoo</option>
	    <option value="&srcs[]=bing&sales_color[]=4040FF&views_color[]=0000BF">Bing</option>
	    <option value="&srcs[]=aol&sales_color[]=4040FF&views_color[]=00BFBF">AOL</option>
	    <option value="&srcs[]=shopping.com&sales_color[]=40FF40&views_color[]=00BF00">Shopping.com</option>
	    <option value="&srcs[]=pricegrabber&sales_color[]=40FF40&views_color[]=00BF00">PriceGrabber</option>
	    <option value="&srcs[]=shopzilla&sales_color[]=40FF40&views_color[]=00BF00">ShopZilla</option>
	    <option value="&srcs[]=ebay&sales_color[]=40FF40&views_color[]=00BF00">Ebay</option>
	    <option value="&srcs[]=amazon.com&sales_color[]=40FF40&views_color[]=00BF00">Amazon USA</option>
	    <option value="&srcs[]=amazon.ca&sales_color[]=40FF40&views_color[]=00BF00">Amazon Canada</option>
	    <option value="&srcs[]=facebook&sales_color[]=40FF40&views_color[]=00BF00">Facebook</option>
	    <option value="&srcs[]=twitter&sales_color[]=40FF40&views_color[]=00BF00">Twitter</option>
	    <option value="&srcs[]=linkedin&sales_color[]=40FF40&views_color[]=00BF00">LinkedIn</option>
	    <option value="&srcs[]=google-ppc&sales_color[]=40FF40&views_color[]=00BF00">Paid - Google</option>
	    <option value="&srcs[]=direct&sales_color[]=40FF40&views_color[]=00BF00">Direct</option>
	    <option value="&srcs[]=other&sales_color[]=40FF40&views_color[]=00BF00">Other</option>
	    </select>
	    </td>
            <td class="pageHeading" align="right">
	    </td>
          </tr>
	  <tr>
	  <td colspan="2" align="left">
<img id="conv_chart_image" width="100%" height="400" src="traffic_sales_chart.php?width=750&height=400&start_date=<?=$date_from?>&end_date=<?=$date_to?>&bg_color=F0F5FB&bg_plot_color=F0F5FB&pad_right=40&pad_left=40&srcs[]=google&sales_color[]=FF4040&views_color[]=BF0000">
	  </td>
	  </tr>
	  <tr><td colspan="2"><table border="0" cellspacing="0" cellpadding="0" width="100%"><tr>
	    <td valign="bottom" align="left" class="dataTableContent"><img src="images/sales_chart_icon_bar.gif"> Hits</td>
	    <td valign="bottom" align="center" class="dataTableContent">Conversion Rate %</td>
	    <td valign="bottom" align="right" class="dataTableContent">Gross Sales $ <img src="images/sales_chart_icon_line.gif"></td>
	  </tr></table></td></tr>


        </table>
	</td>
      </tr>



     </table></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
