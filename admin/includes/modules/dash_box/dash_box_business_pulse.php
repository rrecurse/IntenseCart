<?php
class dash_box_business_pulse
	{
	  var $table_cols=2;
  	  var $table_rows=1;
	  var $title="Business Pulse";

  	  function render() {
?>
<div style="margin-right:5px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2" style="background-color:#FFFFFF; height:3px;"></td>
      </tr>
      <tr>
        <td colspan="2" style="height:215px;"> 
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
  			<tr>
    		<td valign="top">
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
        			<td class="dashbox_bluetop">&nbsp; Business Pulse:</td>
        			<td width="29" align="right" style="padding:3px 0 0 0; background-color:#6295FD;">
					<div class="helpicon" onmouseover="ddrivetip('&lt;font class=featuredpopName&gt;&lt;b&gt;Business Pulse Graph&lt;/b&gt;&lt;/font&gt;&lt;br&gt;&lt;br&gt;Comparison chart of overall revenue performance of current year against previous year.&lt;br&gt;')" onmouseout="hideddrivetip()">					</div></td>
      			</tr>
      			<tr>
        			<td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
      			</tr>
	   			<tr>
         			<td colspan="2" valign="top" style="background-color:#F0F5FB;">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tr>
							<td valign="top" align="center" id="chartHeight">
<img id="chartImage" src="bizpulse.php?width=1365&amp;height=350&amp;qtyprv_color=6295FD-1&amp;qty_color=85B761-1&amp;ret_color=BED9AA-1&amp;retprv_color=BED9AA-1&amp;ytd_color=11911C-25&amp;ytd_markcolor=E4E4E4-50&amp;ytd_mark=9&amp;ytd_thick=5&amp;prv_color=9FB9D6-25&amp;prv_markcolor=E4E4E4-50&amp;prv_mark=9&amp;prv_thick=5&amp;bg_color=F4F4F4&amp;bg_plot_color=FFFFFF&amp;x_font=10-0B2D86&amp;y_font=10-0B861D&amp;pad_left=40&amp;pad_top=13&amp;pad_bottom=20&amp;pad_right=50&amp;bar_width=72" width="100%" height="100%">

<script>

jQuery(document).ready(function() {

	var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;

	jQuery(window).resize(function() {

		width = (window.innerWidth > 0) ? window.innerWidth : screen.width;

		if(width > 1090) { 


			jQuery('#chartImage').attr("src", "bizpulse.php?width=1365&height=350&qtyprv_color=6295FD-1&qty_color=85B761-1&ret_color=BED9AA-1&retprv_color=BED9AA-1&ytd_color=11911C-25&ytd_markcolor=E4E4E4-50&ytd_mark=9&ytd_thick=5&prv_color=9FB9D6-25&prv_markcolor=E4E4E4-50&prv_mark=9&prv_thick=5&bg_color=F4F4F4&bg_plot_color=FFFFFF&x_font=10-0B2D86&y_font=10-0B861D&pad_left=40&pad_top=13&pad_bottom=20&pad_right=50&bar_width=72");

		} else {

			jQuery('#chartImage').attr("src", "bizpulse.php?width=750&height=271&qtyprv_color=6295FD-1&qty_color=85B761-1&ret_color=BED9AA-1&retprv_color=BED9AA-1&ytd_color=11911C-25&ytd_markcolor=E4E4E4-50&ytd_mark=8&ytd_thick=4&prv_color=9FB9D6-25&prv_markcolor=E4E4E4-50&prv_mark=8&prv_thick=4&bg_color=F4F4F4&bg_plot_color=FFFFFF&x_font=8-0B2D86&y_font=8-0B861D&pad_left=40&pad_top=13&pad_bottom=15&pad_right=38&bar_width=72");

		}
	
		top.resizeIframe('myframe');
	});

});

</script>

</td>
							<td valign="top" width="105" align="left">
							<table cellpadding="0" cellspacing="0" border="0" width="100%">
								<tr>
									<td valign="top" align="left" style="padding:10px 3px 0 0;">
									<img src="data:image/jpg;base64,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" width="92" height="115" alt="" />
</td>
	</tr>
<tr>
									<td style="padding-left:5px; padding-top:10px;">
									<table border="0" cellpadding="0" cellspacing="0">
										<tr><td><b>Year-To-Date</b>
										</td></tr>
										<tr>
											<td style="padding:4px 0 8px 0; border-bottom:dotted 1px #333; color: #11911C">
												$<span id="sales_thisyear_total_amount">&nbsp;--</span><br> 
										  </td>
										</tr>
										<tr>
											<td style="padding:6px 0 0 0; color: #9FB9D6;"><b>Last YTD</b></td>
										</tr>
										<tr><td style="padding:4px 0 10px 0; color: #999">
											$<span id="sales_lastyear_total_amount">&nbsp;--</span>
											
										</td></tr>
										<tr>
											<td style="border-top: 1px dotted; padding:6px 0 0 0" nowrap><b>YTD % Change</b>
											<div style="padding-top:4px; width:100%; margin:0 auto" id="sales_thisyear_percent_change">--</div></td>
										</tr>
									</table></td>
								</tr>
						
					</table></td>
				</tr>

			</table></td>
      		</tr>
      		<tr>
        		<td colspan="2" align="center" style="background-color:#F0F5FB; height:45px;">
					<table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><img src="data:image/jpg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAsICAgJCAwJCQwRCwoLERQPDAwPFBcSEhISEhcYExQUFBQTGBYaGxwbGhYiIiQkIiIuLi4uLjAwMDAwMDAwMDD/2wBDAQwMDBAQEBcRERcYFBMUGB4bHBwbHiQeHh8eHiQpIyAgICAjKSYoJCQkKCYrKykpKyswMDAwMDAwMDAwMDAwMDD/wAARCAAUABQDAREAAhEBAxEB/8QAGQAAAgMBAAAAAAAAAAAAAAAAAAUBAgYD/8QAJxAAAgECBgEDBQAAAAAAAAAAAQMCBBEABRITITEiBhRRIyRSYXP/xAAaAQACAwEBAAAAAAAAAAAAAAAEBQABAgMG/8QAJhEAAQIFAgcBAQAAAAAAAAAAAQACAxExUXESIQQiM2GhsdFBgf/aAAwDAQACEQMRAD8A3nvW0lGkqOiJYwFbATPlxHJMr3F+e8FiGHEzsKYQpiFukD9JrlMKyu21VAVeLlQ1AyjePdv1fC9sZry4Nq2qPhsm9oNCjJ6p1XQxc+xYZTiTEaR4yMRxc/GOgUjsDHloostm1WFnLV7hnFrWeR4M/uOCRx3hizZsQ2A9JY7dzMn2nOZ1K4iuNwdpEtXI/Md/GPO8L1o+U5hDmh5Xf0s2LsmWyPUpttb+ksMRRY4vqn+elR6WqrEJg+YW6bpWMVS09s8TJZPeCAZtJlSV8XQJEiN72RTQqHuqlzqZ2W3b4gnkWB8vpYpwaJHSNx3+rTS6Z5jseynLxVNokt90yBnHUYxgkDn4G1iP0hxGkefqoaiJknwv/9k=" width="20" height="20" alt="" /></td>
                          <td style="padding-left:6px;"><a href="stats_sales_report.php?report=4&filter=0001">view full report</a></td>
                        </tr>
                    </table>
		
				</td>
      		</tr>
      
      		<tr>
        		<td colspan="2" style="background-color:#FFFFFF; height:3px;"></td>
      		</tr>
    
    	</table>
    </td>
    </tr>
</table>
</td>
</tr>
</table></div>




	<script type="text/javascript">
	<!--
<?php
//	include_once(DIR_FS_DASH_BOX_JS."main_dash.js");

?>
	// -->
	</script>

<script type="text/javascript">
	jQuery.noConflict();


    jQuery(document).ready(function(){ 

		var thediv = jQuery('body', window.parent.document).find("#thePanel").html();
		if(jQuery(thediv).css('width') < "10px") {	
			jQuery("#chartHeight").css({height : '350px'});
			jQuery("body").css('height','+=82');	
			resizeCaller();
		} else if(jQuery(thediv).css('width') == "178px") {
			jQuery("#chartHeight").css({height : '268px'});
			resizeCaller();
		}
	});

</script>
<?
  }
}
?>

