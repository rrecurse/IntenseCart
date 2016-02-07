<?
  require('includes/application_top.php');
  require(DIR_FS_ADMIN.'apility/apility.php');

?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Executive Dashboard</title>
<link rel="STYLESHEET" href="js/css.css" type="text/css">
<script type="text/javascript" src="js/tips.js"></script>
<script type="text/javascript" src="js/iframe.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/xmlfeed.js"></script>
<script language="JavaScript">
  var noDefaultBreadcrumb=1;

  var trafficSources=Array('google','yahoo','msn','aol','other');
  var trafficPieColors=Array('ff4040','ff40ff','4040ff','40ffff','40ff40');
  var trafficPieUrl='pie_chart.php?width=95&height=95&bgcolor=f0f5fb&border=808080&data=';

  var salesSections=Array('retail','corp','bulk','ebay','affiliate','banner','email','giftcert','total');

  var ppcSources=Array('adwords','overture','other','total');

  var statsList=Array(
    { interval:300, stats:{ traffic:Array('today','thisweek','thismonth') }},
    { interval:1800, stats:{ traffic:Array('yesterday','lastweek','lastmonth') }},
//    { interval:3600, stats:{ ppc:Array('yesterday') }},
    { interval:300, stats:{ sales:Array('today','thisweek','thismonth','thisyear') }},
    { interval:1800, stats:{ sales:Array('yesterday','lastweek','lastmonth') }}
  );
  
</script>

<script language="javascript" type="text/javascript">
function setFocus(focusme) {
  var layer = document.getElementById(focusme);
  var focusIt = layer.getElementsByTagName('a')[0];//This is an array, get the first link.
  focusIt.focus();
}
</script>

</head>
<body style="background-color:transparent;" onLoad="//setFocus('focusme');">
<? include(DIR_WS_INCLUDES.'header.php') ?>
<div style="overflow-x:hidden; width:571px;" id="focusme"><a id="focuschild" href="javascript:void(null)" style="cursor: default"></a><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <!--<tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td style="height:16px; background-color:#6295FD; font:bold 11px arial; color:#FFFFFF;">&nbsp; Today's
                         Marketing Tips:</td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#19487E;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#FFFFFF;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4;"></td>
					   </tr>
                     <tr>
                       <td style="padding-top:3px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="72" rowspan="2" align="center" valign="top" style="padding-bottom:5px;"><img src="images/founder.jpg" width="72" height="51"></td>
    <td rowspan="2" valign="top" style="padding-left:9px; padding-top:3px;"><font color="#5F6366" style="white-space:nowrap">1-Oct-06</font></td>
    <td valign="top" style="padding-top:3px; padding-left:10px; padding-right:12px;"><font style="font:bold 11px arial;">Welcome
        to IntenseCart!<br>
    </font>We are pleased to announce the official debut of IntenseCart, Ecommerce
    and Internet Marketing
    platform. Stay tuned for weekly blogs from our founder Chris DeBellis, as
    well as random advice and tips on how to make your online business excel! </td>
  </tr>
  <tr>
    <td align="right" valign="top" style="padding-bottom:4px; padding-right:35px;"><table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-bottom:3px; font:bold 11px; color:#FDA706">&raquo;&nbsp;</td>
    <td valign="top" style="padding-left:4px;"><a href="#">read more</a></td>
  </tr>
</table></td>
  </tr>
</table></td>
                     </tr>

                   </table></td>
  </tr>-->
  <tr>
    <td style="padding-top:2px;">
</td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top"><div style="width:571px; overflow-x:hidden"><table width="571" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="542" style="height:20px; background-color:#6295FD;"><span style="font:bold 12px arial;color:#FFFFFF;">&nbsp; Sales Performance Indicators:</span></td>
        <td width="29" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Sales Indicators</b></font><br><br>A bird\'s eye view of your main sales channels, with gross totals based on sales generated.<br><br><b>Retail</b> - Basic cart sales generated through your website.<br><br><b>Corp.</b> - Corporate cart sales generated through your website.<br><br><b>Resell</b> - Reseller/Wholesale sales generated through your website.<br><br><b>Affil.</b> - Affiliate sales generated either from your website or from external marketing.<br><br><b>Ebay</b> - Ebay Auction sales generated via Ebay.com<br><br><b>Banner</b> - Sales generated from Banner and/or image advertising<br><br><b>Email</b> - Sales generated from Email Advertising.<br><br><b>Gift Cert.</b> - Pre-paid or Gift Certificate Sales - please note, this does not effect stock quantities.<br><br><b>% change</b> - Percentage of change based on last period of comparable time span.<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="2" valign="top" style="background-color:#F0F5FB; height:122px;"><table width="571" border="0" cellpadding="0" cellspacing="0">
          <tr style="background-color:#DEEAF8; height:20px;">
            <td align="center"><span style="font:bold 12px arial; color:#0B2D86;">
              <?=date('M Y')?>
            </span></td>
            <td width="45" align="center">&nbsp;Retail&nbsp; </td>
            <td width="45" align="center">&nbsp;Corp.&nbsp;</td>
            <td width="45" align="center">&nbsp;Resell&nbsp;</td>
            <td width="40" align="center">&nbsp;Affil.&nbsp;</td>
            <td align="center">&nbsp; Ebay &nbsp;</td>
            <td align="center"> Banner </td>
            <td align="center">&nbsp; Email &nbsp;</td>
            <td width="55" align="center">Gift Cert </td>
            <td width="65" align="center">&nbsp;% change &nbsp;</td>
            <td width="85" align="center">Gross Totals&nbsp;</td>
          </tr>
          <tr>
            <td height="2"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
          <tr>
            <td width="65" align="center" class="tableinfo_right-btm">Today:</td>
            <td align="center" bgcolor="#F0F5FB" class="tableinfo_right-btm" id="sales_today_retail_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_corp_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_bulk_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_affiliate_count">&nbsp;</td>
            <td width="41" align="center" class="tableinfo_right-btm" id="sales_today_ebay_count">&nbsp;</td>
            <td width="43" align="center" class="tableinfo_right-btm" id="sales_today_banner_count">&nbsp;</td>
            <td width="41" align="center" class="tableinfo_right-btm" id="sales_today_email_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_giftcert_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_percent_change">&nbsp;</td>
            <td class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_today_total_amount">0</span></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Yesterday:</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_retail_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_corp_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_bulk_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_affiliate_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_ebay_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_banner_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_email_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_giftcert_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_percent_change">&nbsp;</td>
            <td bgcolor="#EBF1F5" class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_yesterday_total_amount">0</span></td>
            </tr>
          <tr>
            <td align="center" class="tableinfo_right-btm">This Week </td>
            <td align="center" bgcolor="#F0F5FB" class="tableinfo_right-btm" id="sales_thisweek_retail_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_corp_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_bulk_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_affiliate_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_ebay_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_banner_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_email_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_giftcert_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_percent_change">&nbsp;</td>
            <td class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_thisweek_total_amount">0</span></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Last Week:</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_retail_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_corp_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_bulk_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_affiliate_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_ebay_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_banner_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_email_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_giftcert_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_percent_change">&nbsp;</td>
            <td bgcolor="#EBF1F5" class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_lastweek_total_amount">0</span></td>
            </tr>
          <tr>
            <td align="center" class="tableinfo_right-btm">This Month:</td>
            <td align="center" bgcolor="#F0F5FB" class="tableinfo_right-btm" id="sales_thismonth_retail_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_corp_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_bulk_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_affiliate_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_ebay_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_banner_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_email_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_giftcert_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_percent_change">&nbsp;</td>
            <td class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_thismonth_total_amount">0</span></td>
            </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Last
                Month:</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_retail_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_corp_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_bulk_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_affiliate_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_ebay_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_banner_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_email_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_giftcert_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_percent_change">&nbsp;</td>
            <td bgcolor="#EBF1F5" class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_lastmonth_total_amount">0</span></td>
            </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2" style="background-color:#FFFFFF; height:3px;"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:215px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top"><div style="width:571px; overflow-x:hidden"><table width="571" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
      </tr>
      <tr>
        <td width="542" style="height:20px; background-color:#6295FD;"><span style="font:bold 12px arial;color:#FFFFFF;">&nbsp; Business
            Pulse:</span></td>
        <td width="29" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
	   <tr>
         <td colspan="2" valign="top" style="background-color:#F0F5FB;"><table cellpadding="0" cellspacing="0" border="0" width="570">
<tr>
<td valign="top"><img src="bizpulse.php?width=470&height=210&prv_color=6295FD-1&ytd_color=85B761-1&avg_color=BED9AA-1&biz_color=000080-25&biz_markcolor=E4E4E4-50&biz_mark=8&biz_thick=4&bg_color=F4F4F4&bg_plot_color=FFFFFF&x_font=8-0B2D86&y_font=7-666666&pad_left=49&pad_top=8&pad_bottom=14&pad_right=5&bar_width=71" width="470" height="210"></td>
<td valign="top"><table cellpadding="0" cellspacing="0" border="0" width="92">
<tr>
<td valign="top" align="left" style="padding-top:10px; padding-right:3px;"><img src="images/exec-graph-key.jpg" width="92" height="94"></td>
</tr>
<tr>
<td style="padding-left:5px; padding-top:10px;"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td><font style="font-weight:bold; color:#FF0000; font-family:Tahoma; font-size:11px">
Year-To-Date</font></td>
</tr>
<tr>
<td style="padding-top:4px;">
$<span id="sales_thisyear_total_amount">0</span><br> 
</tr>
<tr><td>
----------------<br>
</tr>
<tr><td>
<font style=" color: #999999; font-family:Tahoma; font-size:11px">Last YTD<br>
</tr>
<tr><td style="padding-top:4px;">
$<span id="sales_thisyear_previous_amount">0</span></font></td></tr></table></td>
</tr>
</table></td>
</tr>

</table></td>
      </tr>
      <tr>
        <td colspan="2" align="center" style="background-color:#F0F5FB; height:25px;">
		<table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                          <td style="padding-left:6px;"><a href="#">view
                              full report</a></td>
                        </tr>
                    </table>
		
		</td>
      </tr>
      
      <tr>
        <td colspan="2" style="background-color:#FFFFFF; height:3px;"></td>
      </tr>
    
    </table>
    </div></td>
    </tr>
</table></td>
      </tr>  
    </table></div></td>
    </tr>
</table>
</td>
  </tr>
  
  <tr>
  <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top"><div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD;"><span style="font:bold 12px arial;color:#FFFFFF;">&nbsp; Email
                           Correspondence:</span></td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="283" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td width="71" align="center" style="font:bold 12px arial;"><b>Subject:</b></td>
                           <td width="53" align="center">Unread</td>
                           <td width="53" align="center" >Flagged</td>
                           <td width="53" align="center" style="color:#FF0000">Urgent</td>
                           <td width="53" align="center">&nbsp;</td>
                         </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td colspan="2" style="padding-top:3px; background-color:#F0F5FB; height:122px;"><table width="284" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="71" align="center" class="tableinfo_right-btm"><a href="#">General:</a></td>
                          <td width="53" align="center" class="tableinfo_right-btm">0</td>
                          <td width="53" align="center" class="tableinfo_right-btm">0</td>
                          <td width="53" align="center" class="tableinfo_right-btm">0</td>
                          <td width="53" align="center" class="tableinfo_right-end"><a href="#"><u>view</u></a></td>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">Order
                              Status:</a></td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end"><a href="#"><u>view</u></a></td>
                        </tr>
                        <tr>
                          <td align="center" class="tableinfo_right-btm"><a href="#">Support:</a></td>
                          <td align="center" class="tableinfo_right-btm">0</td>
                          <td align="center" class="tableinfo_right-btm">0</td>
                          <td align="center" class="tableinfo_right-btm">0</td>
                          <td align="center" class="tableinfo_right-end"><a href="#"><u>view</u></a></td>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><a href="#">Totals:</a></td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">0</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end"><a href="#"><u>view</u></a></td>
                        </tr>
                        <tr>
                          <td colspan="5" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                              <tr>
                                <td><img src="images/mailbox-icon.gif" width="14" height="15"></td>
                                <td style="padding-left:6px;"><a href="/mail/src/login.php">open mail manager</a></td>
                              </tr>
                          </table></td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td colspan="2" style="background-color:#FFFFFF; height:3px;"></td>
                    </tr>
            </table></div></td>
    <td style="width:5px;"></td>
    <td valign="top"><div style="width:283px; overflow-x:hidden"><table width="283" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial;color:#FFFFFF;">&nbsp; Pay-Per-Click Search Summary & Costs</td>
                       <td width="22" align="right" style="padding-right:7px; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td><table width="283" border="0" cellpadding="0" cellspacing="0">
                             <tr>
                               <td width="71" align="center" style="font:bold 12px Arial;"><b>Yesterday:</b></td>
                               <td width="53" align="center">Google</td>
                               <td width="53" align="center">Yahoo</td>
                               <td width="53" align="center">Other</td>
                               <td width="53" align="center">Totals</td>
                             </tr>
                           </table></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;"><?

  $stats=Array();
  foreach(Array('adwords','overture','other') AS $scope) $stats[$scope]=Array(avail=>0,clicks=>0,conv=>0,cost=>0.0);
  $date_today=date('Y-m-d',time());
  $date_yesterday=date('Y-m-d',time()-86400);
  $ppc_cache_query=tep_db_query("SELECT * FROM ppc_stats WHERE start_date='$date_yesterday' AND finish_date='$date_today'");
  while ($ppc_cache=tep_db_fetch_array($ppc_cache_query)) {
    $stats[$ppc_cache['ppc_source']]['clicks']=$ppc_cache['ppc_clicks'];
    $stats[$ppc_cache['ppc_source']]['cost']=$ppc_cache['ppc_cost'];
    $stats[$ppc_cache['ppc_source']]['conv']=$ppc_cache['ppc_conversions'];
    $stats[$ppc_cache['ppc_source']]['avail']=$stats[$ppc_cache['ppc_source']]['cache']=1;
  }

  if (!$stats['adwords']['avail']) {
    $campaign_objs=APIlity_getAllCampaigns();
    if (is_array($campaign_objs)) {
      $stats['adwords']['avail']=1;
      foreach ($campaign_objs AS $c) {
        $camp_stats=$c->getCampaignStats(date('Y-m-d',time()-86400),date('Y-m-d',time()));
        $stats['adwords']['clicks']+=$camp_stats['clicks'];
        $stats['adwords']['cost']+=$camp_stats['cost'];
        $stats['adwords']['conv']+=$camp_stats['conversions'];
      }
    }
  }
  foreach ($stats AS $scope=>$stats_row) {
    if (!isset($stats_row['cache']) && $stats_row['avail']) {
      tep_db_query("INSERT IGNORE INTO ppc_stats (start_date,finish_date,ppc_source,ppc_clicks,ppc_cost,ppc_conversions) VALUES ('$date_yesterday','$date_today','$scope','".$stats_row['clicks']."','".$stats_row['cost']."','".$stats_row['conv']."')");
    }
  }
  $stats_total=Array('avail'=>0);
  foreach ($stats AS $stats_row) {
    if ($stats_row['avail']) {
      $stats_total['avail']=1;
      foreach ($stats_row AS $stats_key=>$stats_val) $stats_total[$stats_key]+=$stats_val;
    }
  }
  $stats['total']=&$stats_total;
  
?><table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="71" align="center" class="tableinfo_right-btm">Click Cost:</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
                          <td width="53" align="center" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_clickcost"><?=$st['avail']?($st['clicks']?sprintf("$%.2f",$st['cost']/$st['clicks']):'-'):'n/a'?></td>
						  <? } ?>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"># of Clicks</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
						  <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_clicks"><?=$st['avail']?(sprintf("%d",$st['clicks'])):'n/a'?></td>
						  <? } ?>
                        </tr>
                        <tr>
                          <td align="center" class="tableinfo_right-btm">Conv. Rate</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
                          <td align="center" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_convrate"><?=$st['avail']?($st['clicks']?sprintf("%.2f%%",$st['conv']/$st['clicks']*100):'-'):'n/a'?></td>
						  <? } ?>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Cost / Conv.</td>
						  <? foreach ($stats AS $scope=>$st) { ?>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_<?=$scope?>_convcost"><?=$st['avail']?($st['conv']?sprintf("%.2f",$st['cost']/$st['conv']):'-'):'n/a'?></td>
						  <? } ?>
                        </tr>
                      </table>





<!--table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="71" align="center" class="tableinfo_right-btm">Click Cost:</td>
                          <td width="53" align="center" class="tableinfo_right-btm" id="ppc_yesterday_adwords_clickcost">&nbsp;</td>
                          <td width="53" align="center" class="tableinfo_right-btm" id="ppc_yesterday_overture_clickcost">&nbsp;</td>
                          <td width="53" align="center" class="tableinfo_right-btm" id="ppc_yesterday_other_clickcost">&nbsp;</td>
                          <td width="53" align="center" class="tableinfo_right-btm" id="ppc_yesterday_total_clickcost">&nbsp;</td>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"># of Clicks</td>
			  <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_adwords_clicks">&nbsp;</td>
			  <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_overture_clicks">&nbsp;</td>
			  <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_other_clicks">&nbsp;</td>
			  <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_total_clicks">&nbsp;</td>
                        </tr>
                        <tr>
                          <td align="center" class="tableinfo_right-btm">Conv. Rate</td>
                          <td align="center" class="tableinfo_right-btm" id="ppc_yesterday_adwords_convrate">&nbsp;</td>
                          <td align="center" class="tableinfo_right-btm" id="ppc_yesterday_overture_convrate">&nbsp;</td>
                          <td align="center" class="tableinfo_right-btm" id="ppc_yesterday_other_convrate">&nbsp;</td>
                          <td align="center" class="tableinfo_right-btm" id="ppc_yesterday_total_convrate">&nbsp;</td>
                        </tr>
                        <tr>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Cost / Conv.</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_adwords_convcost">&nbsp;</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_overture_convcost">&nbsp;</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_other_convcost">&nbsp;</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="ppc_yesterday_total_convcost">&nbsp;</td>
                        </tr>
                      </table-->






		      
		      
		      
		      </td>
                    </tr>
                    <tr>
                      <td colspan="2" style="padding:5px; background-color:#F0F5FB"><table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                          <td style="padding-left:6px;"><a href="#">view
                              full report</a></td>
                        </tr>
                    </table></td>
                    </tr>
                    <tr>
                      <td colspan="2" style="background-color:#FFFFFF; height:3px;"></td>
                    </tr>
                   </table></div></td>
  </tr>
</table></td>
  </tr>
</table></div>
<script language="JavaScript">

setBreadcrumb(Array({title:'Executive Dashboard',link:document.location}));

var trafficPieCurrentUrl;

function setBox(id,val) {
  var box=$(id);
  if (!box) return false;
  box.innerHTML=val;
  return true;
}

function statsFeed(req) {
  var data=parseXMLFeed(req);
  if (!data) return;
  if (data.traffic) trafficFeed(data.traffic);
  if (data.sales) salesFeed(data.sales);
  if (data.ppc) ppcFeed(data.ppc);
}

function statsRequest(lst) {
  var feeds=new Array();
  for (var f in lst) feeds[feeds.length]=f+'='+(lst[f].join?lst[f].join(','):lst[f]);
  if (!feeds.length) return;
  new ajax('stats_feed.xml.php?'+feeds.join('&'), { onComplete: statsFeed });
}

function populateFields(prefix,sections,subs,data,addall) {
  for (var range in data) if (data[range]) {
    var sec;
    var total={};
    for (var sub in subs) total[sub]=0;
    for(var si=0;sec=sections[si];si++) {
      var displ=addall;
      for (var sub in subs) {
        var val=(data[range][sec] && data[range][sec][sub])?data[range][sec][sub]:subs[sub];
        if (setBox(prefix+range+'_'+sec+'_'+sub,val)) displ=true;
      }
      if (displ && data[range][sec] && !data[range].total) {
        for (var sub in subs) {
          var val=Number(data[range][sec][sub]);
          if (!isNaN(val)) total[sub]+=val;
	}
      }
      if (data[range]['percent_change']) {
	var p=String(data[range]['percent_change']);
	var c=null;
	if (p.match(/^\+/)) c='#00BF00'; else if (p.match(/^-\d/)) c='red';
	setBox(prefix+range+'_percent_change',(c?'<span style="color:'+c+'">'+p+'</span>':p));
      }
    }
    if (!data[range].total) for (var sub in subs) setBox(prefix+range+'_total_'+sub,total[sub]);
  }
}

function trafficFeed(data) {
  populateFields('traffic_',trafficSources,{count:0},data);
  if (data.thismonth) {
    var source;
    var total=0;
    for(var source in data.thismonth) if (!isNaN(data.thismonth[source].count)) total+=Number(data.thismonth[source].count);
    var pie=new Array();
    for(var si=0;source=trafficSources[si];si++) {
      var box=$('traffic_thismonth_'+source+'_percent');
      var val=data.thismonth[source]?Number(data.thismonth[source].count)*100/total:0;
      if (isNaN(val)) val=0;
      if (box) box.innerHTML=val.toFixed(1);
      if (val) pie[pie.length]=trafficPieColors[si]+':'+Math.floor(val*10);
    }
    var img=$('traffic_pie');
    if (img) {
      pieurl=trafficPieUrl+pie.join(',');
      if (trafficPieCurrentUrl!=pieurl) trafficPieCurrentUrl=img.src=pieurl;
    }
  }
}

function salesFeed(data) {
  populateFields('sales_',salesSections,{count:0,amount:0},data);
}

function ppcFeed(data) {
  for (var range in data) {
    var sec;
    var total;
    for (sec in data[range]) {
      if (!total) total={clicks:0,conv:0,cost:0.0};
      for (var t in total) total[t]+=Number(data[range][sec][t]);
    }
    data[range].total=total;
    for (sec in data[range]) {
      var s=data[range][sec];
      s.clickcost=Number(s.clicks)?Number(s.cost/s.clicks).toFixed(2):'-';
      s.convcost=Number(s.conv)?Number(s.cost/s.conv).toFixed(2):'-';
      s.convrate=Number(s.clicks)?Number(s.conv/s.clicks*100).toFixed(1):'-';
    }
  }
  populateFields('ppc_',ppcSources,{clicks:'n/a',conv:'n/a',cost:'n/a',convrate:'n/a',convcost:'n/a',clickcost:'n/a'},data);
}


var statsCheckInterval=10;
var statsRefreshed=new Array();

function refreshData() {
  var refr={};
  var st;
  for(var i=0;st=statsList[i];i++) {
    var rf=false;
    if (statsRefreshed[i]==undefined) {
      rf=true;
      statsRefreshed[i]=st.interval;
    } else if (statsRefreshed[i]<=0) {
      rf=st.interval>0;
      statsRefreshed[i]+=st.interval;
    }
    statsRefreshed[i]-=statsCheckInterval;
    if (rf) {
      for (f in st.stats) {
        if (!refr[f]) refr[f]=new Array();
	for (var j=0;st.stats[f][j];j++) refr[f][refr[f].length]=st.stats[f][j];
      }
    }
  }
  statsRequest(refr);
  setTimeout("refreshData()",statsCheckInterval*1000);
}

refreshData();
</script>
</body>
</html>
