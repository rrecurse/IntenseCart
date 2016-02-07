<?php
class dash_box_sales_performance {
 	 	var $table_cols=2;
 		var $table_rows=1;
		var $title="Sales Performance Indicators";

  		function render() {
?>
<div style="margin-right:5px;">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
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
        <td class="dashbox_bluetop">&nbsp; Sales Performance Indicators:</td>
        <td width="29" align="right" style="padding:3px 0 0 0; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Sales Indicators</b></font><br><br>A bird\'s eye view of your main sales channels, with gross totals based on sales generated.<br><br><b>Retail</b> - Basic cart sales generated through your website.<br><br><b>Vendors</b> - Dealer sales generated through your website and/or Dealer portal.<br><br><b>Marketplace.</b> - Marketplace Sales generated via APIs and Data Feeds.<br><br><b>Affil.</b> - Affiliate sales generated either from your website of from external marketing.<br><br><b>Banner</b> - Sales generated from Banner and/or image advertising<br><br><b>Email</b> - Sales generated from Email Advertising.<br><br><b>Gift Cert.</b> - Pre-paid or Gift Certificate Sales - please note, this does not effect stock quantities.<br><br><b>% change</b> - Percentage of change based on last period of comparable time span.<br>')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="2" valign="top" style="background-color:#F0F5FB; height:122px;">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr style="background-color:#DEEAF8; height:20px;">
            <td width="16%" align="center"><span style="font:bold 12px arial; color:#0B2D86;">
              <?php echo date('M Y', time())?>
            </span></td>
            <td width="8%" align="center">Retail</td>
            <!--td width="8%" align="center">Corporate</td-->
            <td width="8%" align="center">Vendors</td>
            <td width="8%" align="center">Amazon</td>
            <td width="8%" align="center">Affiliates</td>
            <td width="8%" align="center">Banner</td>
            <td width="8%" align="center">&nbsp; Email &nbsp;</td>
            <td width="8%" align="center" nowrap>&nbsp; Gift Cert &nbsp;</td>
            <td width="8%" align="center">Total</td>
            <td width="8%" align="center">&nbsp;% change &nbsp;</td>
            <td width="12%" align="right" style="padding-right:13px;">Gross Totals</td>
          </tr>
          <tr>
            <td colspan="11" height="2"></td>
          </tr>
          <tr>
            <td align="center" class="tableinfo_right-btm">Today:</td>
            <td align="center" bgcolor="#F0F5FB" class="tableinfo_right-btm" id="sales_today_retail_count">&nbsp;</td>
            <!--td align="center" class="tableinfo_right-btm" id="sales_today_corp_count">&nbsp;</td-->
            <td align="center" class="tableinfo_right-btm" id="sales_today_vendor_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_amazon_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_affiliate_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_banner_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_email_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_product_giftcert_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_total_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_today_percent_change">&nbsp;</td>
            <td align="right" class="tableinfo_right-end" style="padding-right:15px;">$<span id="sales_today_total_amount">0</span></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Yesterday:</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_retail_count">&nbsp;</td>
            <!--td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_corp_count">&nbsp;</td-->
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_vendor_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_amazon_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_affiliate_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_banner_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_email_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_product_giftcert_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_total_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_yesterday_percent_change">&nbsp;</td>
            <td bgcolor="#EBF1F5" class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_yesterday_total_amount">0</span></td>
            </tr>
          <tr>
            <td align="center" class="tableinfo_right-btm">This Week </td>
            <td align="center" bgcolor="#F0F5FB" class="tableinfo_right-btm" id="sales_thisweek_retail_count">&nbsp;</td>
            <!--td align="center" class="tableinfo_right-btm" id="sales_thisweek_corp_count">&nbsp;</td-->
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_vendor_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_amazon_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_affiliate_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_banner_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_email_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_product_giftcert_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_total_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thisweek_percent_change">&nbsp;</td>
            <td class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_thisweek_total_amount">0</span></td>
          </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Last Week:</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_retail_count">&nbsp;</td>
            <!--td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_corp_count">&nbsp;</td-->
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_vendor_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_amazon_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_affiliate_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_banner_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_email_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_product_giftcert_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_total_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastweek_percent_change">&nbsp;</td>
            <td bgcolor="#EBF1F5" class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_lastweek_total_amount">0</span></td>
            </tr>
          <tr>
            <td align="center" class="tableinfo_right-btm">This Month:</td>
            <td align="center" bgcolor="#F0F5FB" class="tableinfo_right-btm" id="sales_thismonth_retail_count">&nbsp;</td>
            <!--td align="center" class="tableinfo_right-btm" id="sales_thismonth_corp_count">&nbsp;</td-->
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_vendor_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_amazon_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_affiliate_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_banner_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_email_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_product_giftcert_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_total_count">&nbsp;</td>
            <td align="center" class="tableinfo_right-btm" id="sales_thismonth_percent_change">&nbsp;</td>
            <td class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_thismonth_total_amount">0</span></td>
            </tr>
          <tr>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">Last
                Month:</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_retail_count">&nbsp;</td>
            <!--td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_corp_count">&nbsp;</td-->
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_vendor_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_amazon_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_affiliate_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_banner_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_email_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_product_giftcert_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_total_count">&nbsp;</td>
            <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="sales_lastmonth_percent_change">&nbsp;</td>
            <td bgcolor="#EBF1F5" class="tableinfo_right-end" align="right" style="padding-right:15px;">$<span id="sales_lastmonth_total_amount">0</span></td>
            </tr>
        </table>
	</td>
	</tr>
	</table>
</div>

<script type="text/javascript">
<!--
<?php include_once(DIR_FS_DASH_BOX_JS."main_dash.js"); ?>
// -->
</script>
<?php
  }
}
?>