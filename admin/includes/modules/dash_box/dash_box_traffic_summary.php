<?php
class dash_box_traffic_summary {
		var $table_cols=2;
	 	var $table_rows=1;
		var $title="Organic Traffic";

  		function render() {
?>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td width="50%" valign="top" style="padding:0 5px 0 0">
<div style="width:100%;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
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
        <td class="dashbox_bluetop">&nbsp; <?php echo $this->title ?> Summary:</td>
        <td align="right" style="padding:3px 0 0 0; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Organic Traffic</b></font><br><br>Lists your natural or organic traffic from the top search engines.')" onMouseout="hideddrivetip()"> </div></td>
      </tr>
      <tr>
        <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      </tr>
      <tr>
        <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="83" align="center"><span style="font:bold 12px arial; color:#0B2D86;"><?php echo date('M Y')?></span></td>
            <td width="50" align="center">Google</td>
            <td width="50" align="center">Yahoo</td>
            <td width="50" align="center">Bing</td>
            <td width="50" align="center">AOL</td>
            <td width="50" align="center">Direct</td>
            <!--td width="50" align="center">Other</td-->
            <td width="50" align="center">Totals</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2" align="center" style="padding-top:3px; background-color:#F0F5FB;">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="83" align="center" class="tableinfo_right-btm" style="height:27px !important;"><a href="#">Yesterday:</a></td>
              <td width="50" align="center" class="tableinfo_right-btm" id="traffic_yesterday_google_count">&nbsp;</td>
              <td width="50" align="center" class="tableinfo_right-btm" id="traffic_yesterday_yahoo_count">&nbsp;</td>
              <td width="50" align="center" class="tableinfo_right-btm" id="traffic_yesterday_bing_count">&nbsp;</td>
              <td width="50" align="center" class="tableinfo_right-btm" id="traffic_yesterday_aol_count">&nbsp;</td>
              <td width="50" align="center" class="tableinfo_right-btm" id="traffic_yesterday_direct_count">&nbsp;</td>
              <!--td width="50" align="center" class="tableinfo_right-btm" id="traffic_yesterday_other_count">&nbsp;</td-->
              <td width="50" align="center" class="tableinfo_right-end" id="traffic_yesterday_total_count"><a href="#"><b>&nbsp;</b></a></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" style="height:27px !important;"><a href="#">Last Week:</a></td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_bing_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_direct_count">&nbsp;</td>
              <!--td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastweek_other_count">&nbsp;</td-->
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastweek_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" class="tableinfo_right-btm" style="height:27px !important;"><a href="#">This Month:</a></td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_google_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_yahoo_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_bing_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_aol_count">&nbsp;</td>
              <td align="center" class="tableinfo_right-btm" id="traffic_thismonth_direct_count">&nbsp;</td>
              <!--td align="center" class="tableinfo_right-btm" id="traffic_thismonth_other_count">&nbsp;</td-->
              <td align="center" class="tableinfo_right-end" id="traffic_thismonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <tr>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" style="height:27px !important;"><a href="#">Last Month:</a></td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_google_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_yahoo_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_bing_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_aol_count">&nbsp;</td>
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_direct_count">&nbsp;</td>
              <!--td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" id="traffic_lastmonth_other_count">&nbsp;</td-->
              <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-end" id="traffic_lastmonth_total_count"><b><a href="#">&nbsp;</a></b></td>
            </tr>
            <!--tr>
              <td colspan="7" align="center" style="padding:10px 5px; border-bottom:1px solid #FFFFFF; color:#FF0000">*
                Dashboard data is refreshed  every 5 minutes. </td>
              </tr-->
        </table>
          <div align="center" style="padding: 10px 0 0 0">
                          <div style="display:inline-block;">
<img src="data:image/jpg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAsICAgJCAwJCQwRCwoLERQPDAwPFBcSEhISEhcYExQUFBQTGBYaGxwbGhYiIiQkIiIuLi4uLjAwMDAwMDAwMDD/2wBDAQwMDBAQEBcRERcYFBMUGB4bHBwbHiQeHh8eHiQpIyAgICAjKSYoJCQkKCYrKykpKyswMDAwMDAwMDAwMDAwMDD/wAARCAAUABQDAREAAhEBAxEB/8QAGQAAAgMBAAAAAAAAAAAAAAAAAAUBAgYD/8QAJxAAAgECBgEDBQAAAAAAAAAAAQMCBBEABRITITEiBhRRIyRSYXP/xAAaAQACAwEBAAAAAAAAAAAAAAAEBQABAgMG/8QAJhEAAQIFAgcBAQAAAAAAAAAAAQACAxExUXESIQQiM2GhsdFBgf/aAAwDAQACEQMRAD8A3nvW0lGkqOiJYwFbATPlxHJMr3F+e8FiGHEzsKYQpiFukD9JrlMKyu21VAVeLlQ1AyjePdv1fC9sZry4Nq2qPhsm9oNCjJ6p1XQxc+xYZTiTEaR4yMRxc/GOgUjsDHloostm1WFnLV7hnFrWeR4M/uOCRx3hizZsQ2A9JY7dzMn2nOZ1K4iuNwdpEtXI/Md/GPO8L1o+U5hDmh5Xf0s2LsmWyPUpttb+ksMRRY4vqn+elR6WqrEJg+YW6bpWMVS09s8TJZPeCAZtJlSV8XQJEiN72RTQqHuqlzqZ2W3b4gnkWB8vpYpwaJHSNx3+rTS6Z5jseynLxVNokt90yBnHUYxgkDn4G1iP0hxGkefqoaiJknwv/9k=" width="20" height="20" alt="" />
</div>
                          <div style="display:inline-block; height:20px; vertical-align:top; line-height:20px; padding-left:6px;"><a href="stats_traffic.php">view full report</a></div>
                         </div>

</td>
      </tr>
    </table></div>
</td>
<td width="50%" valign="top" style="padding:0 5px 0 0">
<div style="width:100%;">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
                     <tr>
        <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
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
                       <td class="dashbox_bluetop">
							&nbsp; <?php echo $this->title ?>  Averages:
						</td>
                       <td align="right" style="padding:3px 0 0 0; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Organic Traffic Averages</b></font><br><br>Displays a graphic chart based on inbound organic / natural traffic averages.')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="padding-top:1px; background-color:#DEEAF8; height:20px;">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
                         <tr>
                           <td><span style="font:bold 12px arial; color:#0B2D86;">&nbsp; <?php echo date('F Y')?></span></td>
                         </tr>
</table></td>
                    </tr>
                    <tr>
                      <td colspan="2" valign="top" style="padding-top:3px; background-color:#F0F5FB;">
<table width="100%" border="0" cellpadding="2" cellspacing="0">
                        <tr>
                          <td class="tableinfo_right-btm" style="padding:2px 0 0 9px">
							<div style="display:inline-block; height:11px; width:12px; border:1px solid #333333; background-color:#FF0000;"></div>
                              <div style="display:inline-block; padding-left:6px; white-space:nowrap; vertical-align:top; height:11px">
								<a href="#">Google Search:</a>
							</div>
							</td>
                          <td width="65" align="center" class="tableinfo_right-btm"><span id="traffic_thismonth_google_percent">0</span>%</td>
                          <td height="105" colspan="2" rowspan="5" align="center" style="vertical-align:middle; padding:10px; min-width:105px">

<img src="data:image/gif;base64,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" id="traffic_pie" width="100%" height="100%"> </td>
                        </tr>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_right-btm" style="padding:2px 0 0 9px">
							<div style="display:inline-block; height:11px; width:12px; border:1px solid #333333; background-color:#FF90FF;"></div>
                              <div style="display:inline-block; padding-left:6px; white-space:nowrap; vertical-align:top; height:11px">
								<a href="#">Yahoo Search:</a>
							</div>
						</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><span id="traffic_thismonth_yahoo_percent">0</span>%</td>
                        </tr>
                        <tr>
                          <td class="tableinfo_right-btm" style="padding:2px 0 0 9px">
							<div style="display:inline-block; height:11px; width:12px; border:1px solid #333333; background-color:#5B5BFF;"></div>
                              <div style="display:inline-block; padding-left:6px; white-space:nowrap; vertical-align:top; height:11px">
								<a href="#">Bing Search:</a>
							</div>
						</td>
                          <td align="center" class="tableinfo_right-btm"><span id="traffic_thismonth_bing_percent">0</span>%</td>
                        </tr>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_right-btm" style="padding:2px 0 0 9px">
							<div style="display:inline-block; height:11px; width:12px; border:1px solid #333333; background-color:#90C6FF;"></div>
                              <div style="display:inline-block; padding-left:6px; white-space:nowrap; vertical-align:top; height:11px">
								<a href="#">AOL Search:</a>
							</div>
						</td>
                          <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm"><span id="traffic_thismonth_aol_percent">0</span>%</td>
                        </tr>
                        <tr>
                          <td class="tableinfo_right-btm" style="padding:2px 0 0 9px">
							<div style="display:inline-block; height:11px; width:12px; border:1px solid #333333; background-color:#5BFF5B;"></div>
                              <div style="display:inline-block; padding-left:6px; white-space:nowrap; vertical-align:top; height:11px">
								<a href="#">Direct / Bookmark:</a>
							</div>
						</td>
                          <td align="center" class="tableinfo_right-btm"><span id="traffic_thismonth_direct_percent">0</span>%</td>
                        </tr>
                        <tr>
                          <td class="tableinfo_right-btm" style="padding:2px 0 0 9px">
							<div style="display:inline-block; height:11px; width:12px; border:1px solid #333333; background-color:#5BFF5B;"></div>
                              <div style="display:inline-block; padding-left:6px; white-space:nowrap; vertical-align:top; height:11px">
								<a href="#">Other / Unknown:</a>
							</div>	
						</td>
                          <td align="center" class="tableinfo_right-btm"><span id="traffic_ thismonth_other_percent">0</span>%</td>
                        </tr>
  
                      </table>
					</td>
                    </tr>
                   
                   </table>
    </div>
</td></tr></table>
	<script language="javascript">
	<!--
<?php
	include_once(DIR_FS_DASH_BOX_JS."main_dash.js");
	//include_once(DIR_FS_DASH_BOX_JS."ext_dash.js");
?>
	// -->
	</script>
<?php
  }
}
?>

