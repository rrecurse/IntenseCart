<?php


/* // ********** PAY PER CLICK CONFIGURATION SECTION ************

// Pay per click referral URLs used - to make this work you have to set up your pay-per-click
// URLs like this : http://www.yoursite.com/catalog/index.php?ref=xxx&keyw=yyy
// where xxx is a code representing the PPC service and yyy is the keyword being used
// to generate that referral. Here's an example :
// http://www.yoursite.com/catalog/index.php?ref=adwords&keyw=gameboy
// which might be used for the keyword "gameboy" in a google adwords campaign.
// The keyword part is optional - if you don't use it in a particular campaign, you 
// Just set up the $ppc array like that in the example for googlead below */


//$ppc = array ('adwords' => array ('title' => 'Google Adwords', 'keywords' => 'testword:testword,aeron chair SN: Aeron chair - Search,aeron chair-CN: Aeron chair - Content,mesh chairs SN: Mesh Chairs,mesh chairs CN: Mesh Chairs'),

//'adCenter' => array ('title' => 'MSN adCenter', 'keywords' => 'aeron%20chair: Aeron chair'),

//'yahoo' => array ('title' => 'Yahoo', 'keywords' => 'aeron%20chair: Aeron chair'));
							 
//Set the following to true to enable the PPC referrer report							
//Eventually, this will probably be moved into the configuration menu
//in admin, where it really should be!
 
define ('SUPERTRACKER_USE_PPC', true);				



// ********** PAY PER CLICK CONFIGURATION SECTION EOF ************

  require('includes/application_top.php');
  require(DIR_WS_LANGUAGES . $language . '/supertracker.php');
  include ('includes/classes/currencies.php');
	$currency = new currencies();	
	
	
	
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
    $src['traffic_type']='referral';
    $src['traffic_source']=$rfr[1];
  }
  
  if ($src) IXdb::store('update','supertracker',$src,'tracking_id='.$row['tracking_id']);
}
	
	
	
	
  function draw_geo_graph($geo_hits,$country_names,$total_hits) {
	  echo '<table cellpadding=2 cellspacing=0 border=0>';
	  $max_pixels = 200;
	  arsort($geo_hits);
	  foreach ($geo_hits as $country_code=>$num_hits) {
		  $country_name = $country_names[$country_code];
			$bar_length = ($num_hits/$total_hits) * $max_pixels;
			$percent_hits = round(($num_hits/$total_hits) * 100,2);
			//Create a random colour for each bar
      srand((double)microtime()*1000000);
      $r = dechex(rand (0, 255));
      $g = dechex(rand (0, 255));
      $b = dechex(rand (0, 255));			
			
			echo '<tr><td>' . $country_name . ': </td><td><div style="display:justify;background:#' . $r . $g . $b . ';border:1px solid #000;height:10px;width:' . $bar_length . '"></div></td><td>' . $percent_hits . '%</td></tr>'; 
		}
	  echo '</table>';
  }//end function
	
	if (isset($_GET['action'])) $action = $_GET['action'];
	if ($action == 'del_rows') {
    $rows_to_delete = $_POST['num_rows'];
		$del_query  = "DELETE from supertracker WHERE 1 ORDER by tracking_id ASC LIMIT " . $rows_to_delete;
		$del_result = tep_db_query ($del_query);	
	}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Visitors Statistics</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="js/css.css">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>

<script language="javascript">
  function page_redirect(url,date_from,date_to) {
	  url=url.value;
	  if (date_from && date_from.value) url+='&date_from='+date_from.value;
	  if (date_to && date_to.value) url+='&date_to='+date_to.value;
		location.href = url;
	}
</script>

 

 
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="571" cellspacing="0" cellpadding="0">
          <tr>
            <td><table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top" style="padding:5px; padding-right:1px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="45"><img src="images/stats-icon.gif"  border="0"></td>
    <td style="font:bold 17px arial; white-space:nowrap">&nbsp;Visitors Statistics</td>
  </tr>
</table>
</td>
  </tr>
</table>
</td>
            <td align="right" style="padding:4px; padding-right:1px; padding-bottom:6px;">
			<form name="report_select" style="margin:0">
			<table border="0" cellspacing="0" cellpadding="0" style="background-color:#FFFFFF; border:solid 1px #D9E4EC;">
<tr>
<td colspan="3" style="padding-top:5px; padding-bottom:5px;">
  <table width="325" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr>
                      <td width="25"><img src="images/mag-icon.gif" width="15" 
height="15" hspace="5"></td>
                      <td width="80" align="center" nowrap 
style="color:#6295FD"><b>Date Search</b>&nbsp;</td>
                      <td width="212" align="right" style="padding-right:3px;">
					  
					  <table border="0" cellpadding="0" cellspacing="0">
                    				    <tr>
                        				<td align="right" style="padding-top:2px;">
<input type="text" name="date_from" style="width:85px; font:bold 9px arial;" onClick="parent.window.popUpCalendar(this,this,'mm-dd-yyyy',document);" value="<?=isset($_GET['date_from'])?$_GET['date_from']:date('m-01-Y')?>" size="12" maxlength="11" textfield></td>
							<td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="parent.window.popUpCalendar(document.report_select.date_from,document.report_select.date_from,'mm-dd-yyyy',document);" style="cursor:pointer"></td>
                        				<td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                        				<td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="parent.window.popUpCalendar(document.report_select.date_from,this,'mm-dd-yyyy',document);" style="width:85px; font:bold 9px arial;" value="<?=isset($_GET['date_to'])?$_GET['date_to']:date('m-d-Y')?>" size="12" maxlength="11" textfield></td>
                        				<td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="parent.window.popUpCalendar(document.report_select.date_from,document.report_select.date_to,'mm-dd-yyyy',document);" style="cursor:pointer"></td>
                        </tr>
</table></td>
              </tr>
	    </table></td></tr>
		<tr>
		  <td width="105" style="white-space:nowrap" align="right">Select report: &nbsp; </td>

		  <td width="200" align="right"><div style="position:relative; top:0; left:0; height:22px; width:200px;"><select name="report_selector" style="width:196px;" onchange="page_redirect(this,this.form.date_from,this.form.date_to)">
								<option value=""><?php echo TABLE_TEXT_MENU_TEXTE; ?></option>
								<option value="supertracker.php?report=refer"><?php echo TEXT_TOP_REFERRERS; ?></option>
								<option value="supertracker.php?report=success_refer"><?php echo TEXT_TOP_SALES;?></option>
								<option value="supertracker.php?special=geo"><?php echo TEXT_VISITORS;?></option>								
								<option value="supertracker.php?special=keywords"><?php //echo TEXT_SEARCH_KEYWORDS;?>Search Keywords Used</option>
								<!--option value="supertracker.php?special=keywords_last24"><?php echo TEXT_SEARCH_KEYWORDS_24;?></option>			
								<option value="supertracker.php?special=keywords_last72"><?php echo TEXT_SEARCH_KEYWORDS_3;?></option>			
								<option value="supertracker.php?special=keywords_lastweek"><?php echo TEXT_SEARCH_KEYWORDS_7;?></option>			
								<option value="supertracker.php?special=keywords_lastmonth"><?php echo TEXT_SEARCH_KEYWORDS_30;?></option-->						
								<option value="supertracker.php?report=exit"><?php echo TEXT_TOP_EXIT_PAGES;?></option>
                                <option value="supertracker.php?report=exit_added">Top Exit Pages (Dropped Cart!)</option>
								<option value="supertracker.php?report=ave_clicks"><?php echo TEXT_AVERAGE_CLICKS;?></option>								
								<option value="supertracker.php?report=ave_time"><?php echo TEXT_AVERAGE_TIME_SPENT;?></option>																
								<option value="supertracker.php?special=prod_coverage"><?php echo TEXT_PRODUCTS_VIEWED_REPORT;?></option>	 	
								<option value="supertracker.php?special=last_ten"><?php echo TEXT_LAST_TEN_VISITORS;?></option>
<?php if (SUPERTRACKER_USE_PPC) {?>						
								<option value="supertracker.php?special=ppc_summary"><?php echo TEXT_PPC_REFERRAL;?></option>			
<?php } ?>
							</select></div>							</td>

		  <td style="padding-right:10px;" width="20">&nbsp;<a href="javascript:document.report_select.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a>                          </td>
		</tr>
</table>
			</form>
            </td>
          </tr>
        </table>

<div style="571px; overflow-x:hidden;">


<table border="0" width="571" cellspacing="0" cellpadding="0">
  <tr>
    <td width="571" valign="top" style="padding-top:10px;" colspan="2">
		<table border="0" width="571" cellspacing="0" cellpadding="0">
  		<tr>
		    <td>
			
		    </td>
		</tr>
  		<tr>
		    <td>
				   <!-- <div style="font:bold 17px verdana; color:#666666;">Traffic Statistics</div>-->
					
						
						<div style="font:bold 11px tahoma; color:#FFFFFF; position:absolute; display:none; visibility:hidden;" class="supertracker_contact">
<!--<strong><?php echo TEXT_DATABASE_INFO; ?></strong>-->
<?php
            $maint_query = "select tracking_id, time_arrived from supertracker order by tracking_id ASC";
						$maint_result = tep_db_query($maint_query);
						$num_rows = tep_db_num_rows($maint_result);
						$maint_row = tep_db_fetch_array($maint_result);
						echo '<span class="supertracker_text">' . sprintf(TEXT_TABLE_DATABASE, $num_rows, tep_date_short($maint_row['time_arrived'])) . '</span><br>';
						echo '<form name="del_rows" action="supertracker.php?action=del_rows" method="post"><br>' . TEXT_TABLE_DELETE . ' <input style="height:19px; width:45px" name="num_rows" size=10> &nbsp; <input style="height:19px; font:10px arial;" type="submit" value="' . TEXT_BUTTON_GO  . '"></form>';
?>
						</div>
						
					<!--	<div class="supertracker_text" style="font:11px tahoma; padding-bottom:10px;">	
						  <?php echo TABLE_TEXT_MENU_DESC_TEXTE; ?> <form name="report_select">
						    <table border="0" cellpadding="0" cellspacing="0">
                    				    <tr>
                        				<td align="right" style="padding-top:2px;">
<input type="text" name="date_from" style="font:bold 9px arial;" onClick="parent.window.popUpCalendar(this,this,'mm-dd-yyyy',document);" value="" size="12" maxlength="11" textfield></td>
							<td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="parent.window.popUpCalendar(document.report_select.date_from,document.report_select.date_from,'mm-dd-yyyy',document);" style="cursor:pointer"></td>
                        				<td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                        				<td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="parent.window.popUpCalendar(document.report_select.date_from,this,'mm-dd-yyyy',document);" style="font:bold 9px arial;" value="" size="12" maxlength="11" textfield></td>
                        				<td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="parent.window.popUpCalendar(document.report_select.date_from,document.report_select.date_to,'mm-dd-yyyy',document);" style="cursor:pointer"></td>
                        </tr>
</table>

						  <select name="report_selector" onchange="page_redirect(this,this.form.date_from,this.form.date_to)">
								<option value=""><?php echo TABLE_TEXT_MENU_TEXTE; ?></option>
								<option value="supertracker.php?report=refer"><?php echo TEXT_TOP_REFERRERS; ?></option>
								<option value="supertracker.php?report=success_refer"><?php echo TEXT_TOP_SALES;?></option>
								<option value="supertracker.php?special=geo"><?php echo TEXT_VISITORS;?></option>								
								<option value="supertracker.php?special=keywords"><?php echo TEXT_SEARCH_KEYWORDS;?></option>
								<option value="supertracker.php?special=keywords_last24"><?php echo TEXT_SEARCH_KEYWORDS_24;?></option>			
								<option value="supertracker.php?special=keywords_last72"><?php echo TEXT_SEARCH_KEYWORDS_3;?></option>			
								<option value="supertracker.php?special=keywords_lastweek"><?php echo TEXT_SEARCH_KEYWORDS_7;?></option>			
								<option value="supertracker.php?special=keywords_lastmonth"><?php echo TEXT_SEARCH_KEYWORDS_30;?></option>						
								<option value="supertracker.php?report=exit"><?php echo TEXT_TOP_EXIT_PAGES;?></option>
								<option value="supertracker.php?report=exit_added"><?php echo TEXT_TOP_EXIT_PAGES_NO_SALE;?></option>
								<option value="supertracker.php?report=ave_clicks"><?php echo TEXT_AVERAGE_CLICKS;?></option>								
								<option value="supertracker.php?report=ave_time"><?php echo TEXT_AVERAGE_TIME_SPENT;?></option>																
								<option value="supertracker.php?special=prod_coverage"><?php echo TEXT_PRODUCTS_VIEWED_REPORT;?></option>	
								<option value="supertracker.php?special=last_ten"><?php echo TEXT_LAST_TEN_VISITORS;?></option>
<?php if (SUPERTRACKER_USE_PPC) {?>						
								<option value="supertracker.php?special=ppc_summary"><?php echo TEXT_PPC_REFERRAL;?></option>			
<?php } ?>
							</select>
							<input type="submit" value="Go">
							
							</form>
					    </div>			
					</div>-->
			  </td>
		  </tr>
<?php

  function fmt_date($date) {
    list($m,$d,$y)=preg_split('|[\-\/]|',$date);
    if ($d<=0 || $m<=0) return NULL;
    if ($y=='') $y=date('Y');
    if ($y<50) $y+=2000;
    return sprintf("%04d-%02d-%02d",$y,$m,$d);
  }

  $date_range_query='';
  if (isset($_GET['date_from']) && $date_from=fmt_date($_GET['date_from'])) $date_range_query.="and time_arrived>='$date_from' ";
  if (isset($_GET['date_to']) && $date_to=fmt_date($_GET['date_to'])) $date_range_query.="and time_arrived<'$date_to'+INTERVAL 1 DAY ";
//  echo "Date Range: $date_range_query\n";

 if (isset($HTTP_GET_VARS['report'])) {
   $report=$HTTP_GET_VARS['report'];
	 $headings=array();
	 $row_data=array();
   if ($report=='refer') { 
	 	 $title = TEXT_TOP_REFFERING_URL;
     $headings[]=TEXT_RANKING;
     $headings[]=TEXT_REFFERING_URL;
     $headings[]=TEXT_NUMBER_OF_HITS;
		 
		 $row_data[]='referrer';
		 $row_data[]='total';		 
		 $tracker_query_raw="SELECT IF(referrer='','Direct/Bookmark',referrer) AS referrer, COUNT(*) as total FROM supertracker WHERE 1 $date_range_query GROUP BY referrer order by total DESC";
	 }
	 
	 if ($report=='success_refer') {
	 	 $title = TEXT_SUCCESSFUL;
     $headings[]=TEXT_SERIAL;
     $headings[]=TEXT_REFFERING_URL;
     $headings[]=TEXT_NUMBER_OF_SALES;
		 
		 $row_data[]='referrer';
		 $row_data[]='total';
		 $tracker_query_raw="SELECT IF(referrer='','Direct/Bookmark',referrer) AS referrer, COUNT(*) as total FROM supertracker WHERE completed_purchase = 'true' $date_range_query group by referrer order by total DESC";	 
	 }
	 
	 if ($report=='exit') {
	 	 $title =TEXT_TOP_PAGES_EXIT;
     $headings[]=TEXT_SERIAL;
     $headings[]=TEXT_EXIT_PAGE;
	 $headings[]=TEXT_NUMBER_OF_OCCURRENCES;
		 
		 $row_data[]='exit_page';
		 $row_data[]='total';
		 $tracker_query_raw="SELECT *, COUNT(*) as total FROM supertracker where completed_purchase='false' $date_range_query group by exit_page order by total DESC";	 
	 }	 
	 
	 if ($report=='exit_added') {
	 	 $title = TEXT_TOP_EXIT_PAGES_NO_SALE;
     $headings[]=TEXT_SERIAL;
     $headings[]=TEXT_EXIT_PAGE;
     $headings[]=TEXT_NUMBER_OF_OCCURRENCES;
		 
		 $row_data[]='exit_page';
		 $row_data[]='total';
		 $tracker_query_raw="SELECT *, COUNT(*) as total FROM supertracker where completed_purchase='false' and added_cart='true' $date_range_query group by exit_page order by total DESC";	 
	 }	 
	 
	 if ($report=='ave_clicks') {
	 	 $title = TEXT_CLICKS_BY_REFFERRER_REPORT;
     $headings[]=TEXT_SERIAL;
     $headings[]=TEXT_REFFERING_URL;
     $headings[]=TEXT_NUMBER_OF_CLICKS;
		 
		 $row_data[]='referrer';
		 $row_data[]='ave_clicks';
		 $tracker_query_raw="SELECT *, AVG(num_clicks) as ave_clicks FROM supertracker WHERE 1 $date_range_query group by referrer order by ave_clicks DESC";	 
	 }	 
	 
	 if ($report=='ave_time') {
	 	 $title = TEXT_AVERAGE_TIME_ON_SITE_BY;
     $headings[]=TEXT_SERIAL;
     $headings[]=TEXT_REFFERING_URL;
     $headings[]=TEXT_AVERAGE_LENGTH_OF_TIME;
		 
		 $row_data[]='referrer';
		 $row_data[]='ave_time';
		 $tracker_query_raw="SELECT *, AVG(UNIX_TIMESTAMP(last_click) - UNIX_TIMESTAMP(time_arrived))/60 as ave_time FROM supertracker WHERE 1 $date_range_query group by referrer order by ave_time DESC";	 
	 }	 	 
	 

  $tracker_query = tep_db_query($tracker_query_raw);

?>

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" colspan="2" style="padding-bottom:10px;"><?php echo $title; ?></td>
            
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
<?php 
              foreach ($headings as $h) {
                echo '<td class="dataTableHeadingContent">' . $h . '</td>';
							}
?>
              </tr>
							
<?php


  $counter = 0;
  while ($tracker = tep_db_fetch_array($tracker_query)) {
		$counter++;

?>
              <tr class="dataTableRow">
							<td class="dataTableContent"><?php echo $counter?></td>
							
<?php             
              foreach ($row_data as $r) {
	    						if ($tracker[$r]=='') $tracker[$r]='[none]';
							  if (strlen($tracker[$r]) > 50) $tracker[$r] = substr($tracker[$r],0,50); 	
  							echo '<td class="dataTableContent"' . $style_override . '>' . $tracker[$r] . '</td>';
							}
?>							
              </tr>
<?php
  }
?>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table>
<?php 
  } //End if 
	
 if (isset($HTTP_GET_VARS['special'])) {
       if ($HTTP_GET_VARS['special'] == 'keywords_last24') {
?>
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
	   echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent">' . TABLE_TEXT_KEY_PHRASE_24 . '</td><td class="dataTableHeadingContent">' . TEXT_NUMBER_OF_HITS . '</td></tr>';
	   $keywords_used = array();
     $keyword_query = "select * from supertracker where DATE_ADD(time_arrived, INTERVAL 1 DAY) >= now() ";
		 $keyword_result = tep_db_query ($keyword_query);
		 while ($keywords = tep_db_fetch_array($keyword_result)) {
		   $key_array = explode ('&', $keywords[referrer_query_string]);
			 for ($i=0; $i<sizeof($key_array); $i++) {
			  if (substr($key_array[$i], 0,2) == 'q=') { 
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}
			  if (substr($key_array[$i], 0,2) == 'p=') {  
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}				
			  if (strstr($key_array[$i], 'query=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],6, strlen($key_array[$i])-6))] +=1;
				}
			  if (strstr($key_array[$i], 'keyword=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],8, strlen($key_array[$i])-8))] +=1;
				}
			  if (strstr($key_array[$i], 'keywords=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],9, strlen($key_array[$i])-9))] +=1;
				}												
			 }
		 }
		 //Need a function to sort $keywords_used into order of no. of hits at some stage!
		 arsort($keywords_used);
		 foreach ($keywords_used as $kw=>$hits) {
		  echo '<tr class="dataTableRow"><td class="dataTableContent">' . $kw . '</td><td class="dataTableContent">' . $hits . '</td></tr>';
		 }
?>
		      </table>
		   </td>
		 </tr>
<?php		 
    }//End Keywords Report last 24h

    if ($HTTP_GET_VARS['special'] == 'keywords_last72') {
?>
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
	   echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent">' . TABLE_TEXT_KEY_PHRASE_3 . '</td><td class="dataTableHeadingContent">' . TEXT_NUMBER_OF_HITS . '</td></tr>';
	   $keywords_used = array();
     $keyword_query = "select * from supertracker where DATE_ADD(time_arrived, INTERVAL 3 DAY) >= now() ";
		 $keyword_result = tep_db_query ($keyword_query);
		 while ($keywords = tep_db_fetch_array($keyword_result)) {
		   $key_array = explode ('&', $keywords[referrer_query_string]);
			 for ($i=0; $i<sizeof($key_array); $i++) {
			  if (substr($key_array[$i], 0,2) == 'q=') { 
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}
			  if (substr($key_array[$i], 0,2) == 'p=') {  
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}				
			  if (strstr($key_array[$i], 'query=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],6, strlen($key_array[$i])-6))] +=1;
				}
			  if (strstr($key_array[$i], 'keyword=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],8, strlen($key_array[$i])-8))] +=1;
				}
			  if (strstr($key_array[$i], 'keywords=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],9, strlen($key_array[$i])-9))] +=1;
				}												
			 }
		 }
		 //Need a function to sort $keywords_used into order of no. of hits at some stage!
		 arsort($keywords_used);
		 foreach ($keywords_used as $kw=>$hits) {
		  echo '<tr class="dataTableRow"><td class="dataTableContent">' . $kw . '</td><td class="dataTableContent">' . $hits . '</td></tr>';
		 }
?>
		      </table>
		   </td>
		 </tr>
<?php		 
    }//End Keywords Report last 72h

    if ($HTTP_GET_VARS['special'] == 'keywords_lastweek') {
?>
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
	   echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent">' . TABLE_TEXT_KEY_PHRASE_7 . '</td><td class="dataTableHeadingContent">' . TEXT_NUMBER_OF_HITS . '</td></tr>';
	   $keywords_used = array();
     $keyword_query = "select * from supertracker where DATE_ADD(time_arrived, INTERVAL 7 DAY) >= now() ";
		 $keyword_result = tep_db_query ($keyword_query);
		 while ($keywords = tep_db_fetch_array($keyword_result)) {
		   $key_array = explode ('&', $keywords[referrer_query_string]);
			 for ($i=0; $i<sizeof($key_array); $i++) {
			  if (substr($key_array[$i], 0,2) == 'q=') { 
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}
			  if (substr($key_array[$i], 0,2) == 'p=') {  
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}				
			  if (strstr($key_array[$i], 'query=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],6, strlen($key_array[$i])-6))] +=1;
				}
			  if (strstr($key_array[$i], 'keyword=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],8, strlen($key_array[$i])-8))] +=1;
				}
			  if (strstr($key_array[$i], 'keywords=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],9, strlen($key_array[$i])-9))] +=1;
				}												
			 }
		 }
		 //Need a function to sort $keywords_used into order of no. of hits at some stage!
		 arsort($keywords_used);
		 foreach ($keywords_used as $kw=>$hits) {
		  echo '<tr class="dataTableRow"><td class="dataTableContent">' . $kw . '</td><td class="dataTableContent">' . $hits . '</td></tr>';
		 }
?>
		      </table>
		   </td>
		 </tr>
<?php		 
    }//End Keywords Report last 7d

    if ($HTTP_GET_VARS['special'] == 'keywords_lastmonth') {
?>
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
	   echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent">' . TABLE_TEXT_KEY_PHRASE_30 . '</td><td class="dataTableHeadingContent">' . TEXT_NUMBER_OF_HITS . '</td></tr>';
	   $keywords_used = array();
     $keyword_query = "select * from supertracker where DATE_ADD(time_arrived, INTERVAL 30 DAY) >= now() ";
		 $keyword_result = tep_db_query ($keyword_query);
		 while ($keywords = tep_db_fetch_array($keyword_result)) {
		   $key_array = explode ('&', $keywords[referrer_query_string]);
			 for ($i=0; $i<sizeof($key_array); $i++) {
			  if (substr($key_array[$i], 0,2) == 'q=') { 
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}
			  if (substr($key_array[$i], 0,2) == 'p=') {  
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}				
			  if (strstr($key_array[$i], 'query=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],6, strlen($key_array[$i])-6))] +=1;
				}
			  if (strstr($key_array[$i], 'keyword=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],8, strlen($key_array[$i])-8))] +=1;
				}
			  if (strstr($key_array[$i], 'keywords=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],9, strlen($key_array[$i])-9))] +=1;
				}												
			 }
		 }
		 //Need a function to sort $keywords_used into order of no. of hits at some stage!
		 arsort($keywords_used);
		 foreach ($keywords_used as $kw=>$hits) {
		  echo '<tr class="dataTableRow"><td class="dataTableContent">' . $kw . '</td><td class="dataTableContent">' . $hits . '</td></tr>';
		 }
?>
		      </table>
		   </td>
		 </tr>
<?php		 
    }//End Keywords Report last month

    if ($HTTP_GET_VARS['special'] == 'keywords') {
?>
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
	   echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent">' . TABLE_TEXT_KEY_PHRASE . '</td><td class="dataTableHeadingContent">' . TEXT_NUMBER_OF_HITS . '</td></tr>';
	   $keywords_used = array();
     $keyword_query = "select * from supertracker WHERE 1 $date_range_query";
		 $keyword_result = tep_db_query ($keyword_query);
		 while ($keywords = tep_db_fetch_array($keyword_result)) {
		   $key_array = explode ('&', $keywords[referrer_query_string]);
			 for ($i=0; $i<sizeof($key_array); $i++) {
			  if (substr($key_array[$i], 0,2) == 'q=') { 
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}
			  if (substr($key_array[$i], 0,2) == 'p=') {  
  				$keywords_used[str_replace('+', ' ', substr($key_array[$i],2, strlen($key_array[$i])-2))] +=1;
				}				
			  if (strstr($key_array[$i], 'query=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],6, strlen($key_array[$i])-6))] +=1;
				}
			  if (strstr($key_array[$i], 'keyword=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],8, strlen($key_array[$i])-8))] +=1;
				}
			  if (strstr($key_array[$i], 'keywords=')) {
				  $keywords_used[str_replace('+', ' ', substr($key_array[$i],9, strlen($key_array[$i])-9))] +=1;
				}												
			 }
		 }
		 //Need a function to sort $keywords_used into order of no. of hits at some stage!
		 arsort($keywords_used);
		 foreach ($keywords_used as $kw=>$hits) {
		  echo '<tr class="dataTableRow"><td class="dataTableContent">' . $kw . '</td><td class="dataTableContent">' . $hits . '</td></tr>';
		 }
?>
		      </table>
		   </td>
		 </tr>
<?php		 
    }//End Keywords Report 
		
		if ($HTTP_GET_VARS['special'] == 'last_ten') {
		
		   if (isset($HTTP_GET_VARS['offset'])) $offset = $HTTP_GET_VARS['offset'];
			 else $offset = 0; 		

			 if (isset($HTTP_GET_VARS['refer_match'])) {
			   $match_refer_string = " and referrer like '%" . $HTTP_GET_VARS['refer_match'] . "%'";
				 $refer_match = $HTTP_GET_VARS['refer_match'];
			 }
			 else {
			    $match_refer_string = '';
					$refer_match = '';
			}

			 
			 if (isset($HTTP_GET_VARS['filter'])) {
			   $filter = $HTTP_GET_VARS['filter'];
			 }
			 else $filter = 'all';

			 switch ($filter) {
			 
			   case 'all' :
				 
    		  if ($refer_match == '') $lt_query = "select * from supertracker WHERE 1 $date_range_query ORDER by last_click DESC LIMIT " . $offset . ",10";
					else $lt_query = "select * from supertracker where referrer like '%" . $refer_match . "%' $date_range_query ORDER by last_click DESC LIMIT " . $offset . ",10";				 
				 break;
				 
				 case 'bailed' :
    		   $lt_query = "select * from supertracker where added_cart = 'true' and completed_purchase = 'false' " . $match_refer_string . " $date_range_query ORDER by last_click DESC LIMIT " . $offset . ",10";				 
				 break;
				 
				 case 'completed' :
    		   $lt_query = "select * from supertracker where completed_purchase = 'true'  " . $match_refer_string . " $date_range_query ORDER by last_click DESC LIMIT " . $offset . ",10";				 
				 break;
			 
			 } // end switch


		  $lt_result= tep_db_query ($lt_query);
?>
     <table width="100%" border=0 cellspacing=0 cellpadding=0>
		   <tr>
			   <td class="dataTableContent">
           <form name="filter_select" action="supertracker.php" method="get" onchange="this.submit()">
					 <input type="hidden" name="special" value="last_ten">
        		 Show : <select name="filter">
      	    	 <option value="all" <?php if ($filter == 'all') echo 'selected';?>><?php echo TEXT_SHOW_ALL; ?></option>
        	  	 <option value="bailed" <?php if ($filter == 'bailed') echo 'selected';?>><?php echo TEXT_BAILED_CARTS; ?></option>
          		 <option value="completed" <?php if ($filter == 'completed') echo 'selected';?>><?php echo TEXT_SUCCESSFUL_CHECKOUTS; ?></option>		 
						 </select>
             <br><?php echo TEXT_AND_OR_ENTER; ?><input type="text" size="15" name="refer_match" value="<?php echo $refer_match;?>">
						 <input type="submit" value = "Update">						 
      		 </form>
				 </td>
			</tr>
		</table>			
								
<?php
		 while ($lt_row = tep_db_fetch_array($lt_result)) {
			 $customer_ip = $lt_row['ip_address'];
			 $country_code = $lt_row['country_code'];
			 $country_name = $lt_row['country_name'];			 

 			 
			 $customer_id = $lt_row['customer_id'];
			 if ($customer_id != 0) {
			   $cust_query = "select * from customers where customers_id ='" . $customer_id . "'";
				 $cust_result = tep_db_query ($cust_query);
				 $cust_row = tep_db_fetch_array($cust_result);
				 $customer_name = $cust_row['customers_firstname'] . ' ' . $cust_row['customers_lastname'];
			 }
			 else $customer_name = "Guest";
			 $referrer = $lt_row['referrer'] . '?' . $lt_row['referrer_query_string'];
			 if ($referrer == '?') $referrer = 'Direct Access / Bookmark';
			 $landing_page = $lt_row['landing_page'];
			 $last_page_viewed = $lt_row['exit_page'];
			 $time_arrived = $lt_row['time_arrived'];
			 $last_click = $lt_row['last_click'];
			 $num_clicks = $lt_row['num_clicks'];
			 $added_cart = $lt_row['added_cart'];
			 $completed_purchase = $lt_row['completed_purchase'];
			 $browser_string = $lt_row['browser_string'];
			 
			 if ($lt_row['products_viewed'] != '') {
  			 $products_viewed = $lt_row['products_viewed'];
  			 $prod_view_array = explode ('*',$products_viewed);
			}
			else $products_viewed = '';
      if($country_code==''){
        $country_code='pixel_trans';
      }	
      if ($country_name=='') $country_name='Network/Proxy';

      echo '<table width="100%" border=0 cellspacing=0 cellpadding=5 style="border:1px solid #000;">';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_IP . '</b><a href="http://www.showmyip.com/?ip=' . $customer_ip . '" target="_blank">' . $customer_ip . ' (' . $country_name . ')' . tep_image(DIR_WS_IMAGES . 'geo_flags/' . $country_code . '.gif') . ' - ' . gethostbyaddr($customer_ip) . '</td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_CUSTOMER_BROWSER_IDENT . '</b>' . $browser_string . '</td></tr>';			
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_NAME . '</b>' . $customer_name . '</td></tr>';
  		echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_REFFERED_BY . '<a href="' . $referrer . '" target="_blank">' . $referrer . '</a></b></td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_LANDING_PAGE . '</b>' . $landing_page . '</td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_LAST_PAGE_VIEWED . '</b>' . $last_page_viewed . '</td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_TIME_ARRIVED . '</b>' . tep_datetime_short($time_arrived) . '</td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_LAST_CLICK . '</b>' . tep_datetime_short($last_click) . '</td></tr>';
			
      $time_on_site = strtotime($last_click) - strtotime($time_arrived);
			$hours_on_site = floor($time_on_site /3600);
      $minutes_on_site = floor( ($time_on_site - ($hours_on_site*3600))  / 60);
      $seconds_on_site = $time_on_site - ($hours_on_site *3600) - ($minutes_on_site * 60);
			$time_on_site = $hours_on_site . 'hrs ' . $minutes_on_site . 'mins ' . $seconds_on_site . ' seconds'; 

			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_TIME_ON_SITE . '</b>' . $time_on_site . '</td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_NUMBER_OF_CLICKS . '</b>' . $num_clicks . '</td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_ADDED_CART . '</b>' . $added_cart . '</td></tr>';
			echo '<tr><td class="dataTableContent"><b>' . TABLE_TEXT_COMPLETED_PURCHASE . '</b>' . $completed_purchase . '</td></tr>';
			
			if ($completed_purchase == 'true') {
			   $order_q = "select ot.text as order_total from orders as o, orders_total as ot where o.orders_id=ot.orders_id and o.orders_id = '" . $lt_row['order_id'] . "' and ot.class='ot_total'";
				 $order_result = tep_db_query($order_q);
				 if (tep_db_num_rows($order_result)>0) {
				   $order_row = tep_db_fetch_array($order_result);
      		 echo '<tr><td class="dataTableContent">' . TABLE_TEXT_ORDER_VALUE . $order_row['order_total'] . '</td></tr>';				 
				 }
			}
			
		  $categories_viewed = unserialize($lt_row['categories_viewed']);
			if (!empty($categories_viewed)) {
			  $cat_string = '';
			  foreach ($categories_viewed as $cat_id=>$val) {
				  $cat_query = "select * from categories as c, categories_description as cd where c.categories_id=cd.categories_id and c.categories_id='" . $cat_id . "'";
					$cat_result = tep_db_query($cat_query);
					$cat_row = tep_db_fetch_array($cat_result);
					$cat_string .= $cat_row['categories_name'] . ',';
				}
				$cat_string = rtrim($cat_string, ',');
        echo '<tr><td class="dataTableContent"><strong>' . TABLE_TEXT_CATEGORIES . '</strong>' . $cat_string . '</td></tr>';						
			}
			
			
			if ($products_viewed != '') {
  			echo '<tr><td class="dataTableContent"><strong>' . TABLE_TEXT_PRODUCTS . ' </strong><table cellspacing=0 cellpadding=0 border=0><tr>';
			  foreach ($prod_view_array as $key=>$product_id) {
				  $product_id = rtrim($product_id, '?');
					if ($product_id != '') {
            $prod_query = "select * from products as p, products_description as pd where p.products_id=pd.products_id and p.products_id='" . $product_id . "'";
  					$prod_result = tep_db_query($prod_query);
  					$prod_row = tep_db_fetch_array($prod_result);
  					echo '<td><table cellspacing=0 cellpadding=2 border=0 align="center" style="border:1px solid #000;"><tr><td align="center">' . tep_image(DIR_WS_CATALOG_IMAGES . $prod_row['products_image'], $prod_row['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</td></tr><tr><td class="dataTableContent" align="center">' . $prod_row['products_name'] . '</td></tr></table></td>';
					}				
				}
  			  echo '</tr></table></td></tr>';
			}
							
		  $cart_contents = unserialize($lt_row['cart_contents']);

			if (!empty($cart_contents)) {
  			echo '<tr><td class="dataTableContent"><strong>' . TABLE_TEXT_CUSTOMERS_CART . '(value=' . $currency->format($lt_row['cart_total']) . ') : </strong><table cellspacing=0 cellpadding=0 border=0><tr>';			
				foreach ($cart_contents as $product_id => $qty_array) {
            $prod_query = "select * from products as p, products_description as pd where p.products_id=pd.products_id and p.products_id='" . $product_id . "'";
  					$prod_result = tep_db_query($prod_query);
  					$prod_row = tep_db_fetch_array($prod_result);
  					echo '<td><table cellspacing=0 cellpadding=2 border=0 align="center" style="border:1px solid #000;"><tr><td align="center">' . tep_image(DIR_WS_CATALOG_IMAGES . $prod_row['products_image'], $prod_row['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</td></tr><tr><td class="dataTableContent" align="center">' . $prod_row['products_name'] . '</td></tr><tr><td class="dataTableContent">' . TABLE_TEXT_QUANTITY . $qty_array['qty'] . '</td></tr></table></td>';									
				}
  			echo '</tr></table></td></tr>';
			}						
															
			echo '</table>';			  								
			
		
		 }//End While
?>
<strong><a href="supertracker.php?special=last_ten&offset=<?php echo $offset + 10;?>&filter=<?php echo $filter;?>&refer_match=<?php echo $refer_match;?>"><?php echo TABLE_TEXT_NEXT_TEN_RESULTS; ?></a></strong>
<?php
		}//End Special "Last Ten" Report
		
  //PPC Summary report
if ($HTTP_GET_VARS['special'] == 'ppc_summary') {
echo '<table width="100%" border=0 cellspacing=0 cellpadding=5 style="border:1px solid #000;">'; 
$ppc_query=tep_db_query("SELECT * FROM ".TABLE_AD_CAMPAIGNS);
while ($ppc=tep_db_fetch_array($ppc_query)) {
$ref_code=$ppc['campaign_ref'];
$scheme_name = $ppc['campaign_title'];
$keywords = $ppc['campaign_keywords'];

$ppc_q = "SELECT * from supertracker where landing_page like '%ref=" . $ref_code . "%' $date_range_query";
$ppc_result = tep_db_query ($ppc_q);
$ppc_num_refs = tep_db_num_rows($ppc_result);
echo '<tr><td colspan="4" style="font:bold 15px arial;text-decoration:underline; padding-top:10px; color:#0000FF;">' . $scheme_name . ' (ref='.$ref_code.') - Total Referrals ' . $ppc_num_refs . '</td></tr>';
// display headings
echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent">&nbsp;&nbsp;(keyw): Admin Desc</td>';
echo '<td class="dataTableHeadingContent">Referrals</td><td class="dataTableHeadingContent">Avg Time</td><td class="dataTableHeadingContent">Avg Clicks</td>';
echo '<td class="dataTableHeadingContent"># Purchased</td><td class="dataTableHeadingContent">Conversion Rate</td>';

if ($keywords != '') {
$keyword_array = explode(',',$keywords);
foreach ($keyword_array as $key => $val) {
//$colon_pos = strpos ($val, ':');
//$keyword_code = substr($val,0,$colon_pos);
//$keyword_friendly_name = substr($val,$colon_pos+1,strlen($val)-$colon_pos);
$keyword_code = $val;
$ppc_key_q = "SELECT *, count(*) as count, AVG(num_clicks) as ave_clicks, AVG(UNIX_TIMESTAMP(last_click) - UNIX_TIMESTAMP(time_arrived))/60 as ave_time from supertracker where landing_page like '%ref=" . $ref_code . "&keyw=" . addslashes(addslashes(addslashes($keyword_code))) . "%' $date_range_query group by landing_page";
$ppc_key_result = tep_db_query($ppc_key_q);
$ppc_row = tep_db_fetch_array($ppc_key_result);
$ppc_key_refs = $ppc_row['count'];

// conversion data
$ppc_key_p_sql = "SELECT COUNT(*) as conversions FROM supertracker where landing_page like '%ref=" . $ref_code . "&keyw=" . addslashes(addslashes(addslashes($keyword_code))) . "%' and completed_purchase='true' $date_range_query";
$ppc_key_p_array = tep_db_fetch_array(tep_db_query($ppc_key_p_sql));

if ($ppc_key_p_array['conversions'] == 0)
$conversion_rate = 0;
else
$conversion_rate = ($ppc_key_p_array['conversions']/$ppc_key_refs)*100;

//test
//echo '<tr class="dataTableContent"><td>('.$keyword_code.'):'.$keyword_friendly_name.'</td>';
echo '<tr class="dataTableContent"><td>'.$keyword_code.'</td>';
echo '<td>&nbsp;'.$ppc_key_refs.'</td><td>&nbsp;'.number_format($ppc_row['ave_time'],2).'</td><td>&nbsp;'.number_format($ppc_row['ave_clicks']).'</td>';
echo '<td>&nbsp;'.$ppc_key_p_array['conversions'].'</td><td>&nbsp;'.number_format($conversion_rate,2).'%</td>';

}
}
}
echo '</table>';

}//End PPC Summary Report 
		
	 
   if ($HTTP_GET_VARS['special'] == 'geo') {
?>
	 <tr>
		<td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
	   echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent">' . TABLE_TEXT_COUNTRY . '</td></tr>'; 
	   $geo_query = "select count(*) as count, country_code, IF(country_name='','Network/Proxy',country_name) AS country_name from supertracker WHERE 1 $date_range_query GROUP by country_code";
		 $geo_result = tep_db_query($geo_query);
		 $geo_hits = array();
		 $country_names = array();
		 $total_hits = 0;
		 while ($geo_row = tep_db_fetch_array($geo_result)) {
		   $total_hits += $geo_row['count'];
			 $country_code = strtolower($geo_row['country_code']);
			 $geo_hits[$country_code] = $geo_row['count'];
			 $country_names[$country_code] = $geo_row['country_name'];			 
		 }
		 draw_geo_graph($geo_hits,$country_names,$total_hits);
	 }//End Geo Report
	 
   if ($HTTP_GET_VARS['special'] == 'prod_coverage') {
	 
	 		 if (isset($HTTP_GET_VARS['agent_match'])) {
			   $agent_match = $HTTP_GET_VARS['agent_match'];
			   $match_agent_string = " and browser_string like '%" . $agent_match . "%'";
			 }
			 else {
			    $match_agent_string = '';
					$agent_match = '';
			}
?>
     <table width="100%" border=0 cellspacing=0 cellpadding=0>
		   <tr>
			   <td class="dataTableContent">
           <form name="filter_select" action="supertracker.php" method="get" onchange="this.submit()">
					 <input type="hidden" name="special" value="prod_coverage">
             
             <?php echo TEXT_STRING_FILTER; // modified by azer ?> 
             <input type="text" size="15" name="agent_match" value="<?php echo $agent_match;?>">
						 <input type="submit" value = "Update">						 
      		 </form>
				 </td>
			</tr>
		</table>			
<?php

	  $view_count=Array();
	  $cov_q = IXdb::query("select products_viewed from supertracker where 1 $date_range_query " . $match_agent_string);
	  while ($cov_row=IXdb::fetch($cov_q)) {
	    if (preg_match_all('/\d+/',$cov_row['products_viewed'],$cov_p)) foreach ($cov_p[0] AS $pid) $view_count[$pid]++;
	  }

	   $prod_q = "select p.products_id, pd.products_name from products as p, products_description as pd where p.products_id=pd.products_id and p.products_status='1'";
		 $prod_result = tep_db_query($prod_q);
		 $prod_coverage = array();
		 while ($prod_row = tep_db_fetch_array($prod_result)) {
//		   $cov_q = "select * from supertracker where products_viewed like '%" . $prod_row['products_id'] . "%' $date_range_query " . $match_agent_string ;
//			 $cov_result = tep_db_query($cov_q);
//			 $prod_coverage[$prod_row['products_name']] = tep_db_num_rows($cov_result);
			 if (isset($view_count[$prod_row['products_id']])) $prod_coverage[$prod_row['products_name']] = $view_count[$prod_row['products_id']];
			
		 } // End While loop
		 arsort($prod_coverage); 
?>
     <table cellpadding=2 cellspacing=0 border=0 width="100%">
       <tr><td class="pageHeading" colspan=2 align="left"><?php echo TABLE_TEXT_PRODUCT_COVERAGE_REPORT; ?></td></tr>		 
       <tr class="dataTableHeadingRow"><td class="dataTableHeadingContent"><?php echo TABLE_TEXT_PRODUCT_NAME; ?></td><td class="dataTableHeadingContent"><?php echo TABLE_TEXT_NUMBER_OF_VIEWING; ?></td></tr>			 
<?php
     foreach ($prod_coverage as $prod_name => $hits) {
		   echo '<tr><td class="dataTableContent">' . $prod_name . '</td><td class="dataTableContent">' . $hits . '</td></tr>';
		 }		 
?>
		 </table>
<?php		 		 
	 } // End Product Coverage Report	 
 }
?>		
		
		</td>

  </tr>
</table>
</div>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
