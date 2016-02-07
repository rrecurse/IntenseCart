<?php
/*
  $Id: index.php,v 1.01 2006/09/23 09:38:31 dgw_ Exp $

  IntenseCart, E-Commerce and Internet Marketing Solutions
  http://www.intensecart.com

  Copyrights (c) 2007 IntenseGroup Inc.

  Redistribution without explicit written consent is forbidden under US and International Copyright Laws
  */

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
  $languages = tep_get_languages();
  $languages_array = array();
  $languages_selected = DEFAULT_LANGUAGE;
  for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
    $languages_array[] = array('id' => $languages[$i]['code'],
                               'text' => $languages[$i]['name']);
    if ($languages[$i]['directory'] == $language) {
      $languages_selected = $languages[$i]['code'];
    }
  }
  
//  $AdminFiles=GetAdminFiles();
  
  $dash_qry=tep_db_query("SELECT DISTINCT dash_name FROM admin_dash");
  $Dashboards=Array();
  while ($dash_row=tep_db_fetch_array($dash_qry)) $Dashboards[]=Array(id=>'dashboard.php?dash='.urlencode($dash_row['dash_name']),'text'=>$dash_row['dash_name']);
/*
  $Dashboards=Array(
    Array(id=>'main-exec.php',text=>'Executive Dashboard'),
    Array(id=>'main-marketing.php',text=>'Marketing'),
    Array(id=>'main-sales.php',text=>'Sales Management'),
    Array(id=>'main-inventory.php',text=>'Inventory Manager'),
  );
  
  foreach ($Dashboards AS $key=>$dash) if (!isset($AdminFiles[$dash['id']])) unset($Dashboards[$key]);
*/
  
  if (isset($_COOKIE['iframe_src_myframe'])) $FramePage=$_COOKIE['iframe_src_myframe'];
  if (isset($FramePage)) {
    $FramePageFile=preg_replace('|.*/|','',preg_replace('|\?.*|','',$FramePage));
    foreach ($Dashboards AS $dash) if ($FramePageFile==$dash['id']) $Dashboard=$FramePageFile;
  }
  if (!isset($Dashboard)) $Dashboard=$Dashboards[0]['id'];
  if (!isset($FramePage)) $FramePage=HTTP_SERVER.DIR_WS_ADMIN.$Dashboard;
  
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?php echo TITLE; ?> &nbsp; &nbsp; .:: Fueled by IntenseCart ::. &nbsp; &nbsp; &nbsp; &nbsp;</title>
<link rel="STYLESHEET" href="js/css.css" type="text/css">
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="js/tips.js"></script>
<script type="text/javascript" src="js/iframe.js"></script>

<script>
  var Dashboard = '<?php echo $Dashboard;?>';
  var Dashboards = new Array();

<?php 
	foreach ($Dashboards AS $dash) {
		echo "Dashboards['". $dash['id'] ."'] = '". $dash['text'] ."';
	}
?>

  function getElementText(p) {
    if (!p) return null;
    var rs;
    for (var e=p.firstChild;e;e=e.nextSibling) {
      var txt;
      txt=(e.data!=undefined)?e.data:getElementText(e);
      if (txt) rs=rs?rs+' '+txt:txt;
    }
    return rs;
  }
  
  var breadcrumbPanelTab;
  function setBreadcrumb(cont) {
    var bc=document.getElementById('breadcrumb');
    if (bc) {
      if (cont[0] && !cont[0].title) cont[0]={title:Dashboards[Dashboard],link:Dashboard};
      if (cont[1] && !cont[1].title) {
        var ctrl=sampleAccordion.getPanelTab(sampleAccordion.currentPanel);
	if (ctrl) {
	  breadcrumbPanelTab=ctrl;
          cont[1]={title:getElementText(ctrl),link:'javascript:breadcrumbPanelTab.onclick()'};
	}
      }
      var html=new Array();
      for (var i=0;cont[i];i++) html[html.length]=cont[i].link?'<a href="'+(cont[i].link.toString().match(/^javascript:/)?cont[i].link:'javascript:loadintoIframe(\'myframe\',\''+cont[i].link+'\')')+'" class="breadcrumblink">'+cont[i].title+'</a>':cont[i].title;
      bc.innerHTML=html.join(' <b>&raquo;</b> ');
      return true;
    }
    return false;
  }
  
</script>

<style type="text/css">
<!--
a { color:#080381; text-decoration:none; }
a:hover { color:#aabbdd; text-decoration:underline; }
a.text:link, a.text:visited { color: #000000; text-decoration: none; }
a:text:hover { color: #000000; text-decoration: underline; }
a.main:link, a.main:visited { color: #ffffff; text-decoration: none; }
A.main:hover { color: #ffffff; text-decoration: underline; }
a.sub:link, a.sub:visited { color: #dddddd; text-decoration: none; }
A.sub:hover { color: #dddddd; text-decoration: underline; }
.heading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 20px; font-weight: bold; line-height: 1.5; color: #D3DBFF; }
.main { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 17px; font-weight: bold; line-height: 1.5; color: #ffffff; }
.sub { font-family:  Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; line-height: 1.5; color: #dddddd; }
.text { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; font-weight: bold; line-height: 1.5; color: #000000; }
.menuBoxHeading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; color: #ffffff; font-weight: bold; background-color: #7187bb; border-color: #7187bb; border-style: solid; border-width: 1px; }
.infoBox { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px; color: #080381; background-color: #f2f4ff; border-color: #7187bb; border-style: solid; border-width: 1px; }
.smallText { font-family: Verdana, Arial, sans-serif; font-size: 10px; }
//-->
</style>

<style> 
.menu  { position: absolute; 
width:  150px; 
top: 0px; 
left: 0px;
background-color:#D4D0C8;
visibility: hidden; 
border: 1px solid;
border-color: #eeeeee #666666 #666666 #eeeeee;
padding: 2px;
z-index: 5000;
filter: alpha(opacity=60) progid:DXImageTransform.Microsoft.Shadow(color=gray,direction=125);;
-moz-opacity:7;
}
.menuitem {
padding: 2px 4px 2px 4px; 
text-decoration: none;
font-family: ms sans serif; 
font-size: 11px;
font-weight: normal; 
display: block;
}

.seperator {
border-top: 1px solid #999999;
border-bottom: 1px solid #eeeeee;
margin: 2px;
}
a.menuitem:link {
color: #000000;
text-decoration:none;
}
a.menuitem:hover {
color: #ffffff;
text-decoration:none;
background-color: #0A246A;
}
a.menuitem:visited {
text-decoration:none;
}
</style>
<style type="text/css" media="all">
*
{
	margin: 0;
	padding: 0;
}

.fisheye{
	text-align: left;
	height: 65px;
	position: relative;
}
a.fisheyeItem
{

    width: 85px;
	position: absolute;
	display: block;
	top: 0;
    left:0;
}
.fisheyeItem img
{
	border: none;
	margin: 0 auto 5px auto;
	width: 100%;
}
.fisheyeItem span
{
	display: none;
	positon: absolute;
}
.fisheyeContainter
{
	height: 145px;
	width: 524px;
    left:-1px;
	position: absolute;
}

.topmenu-font 
{
font-family:Arial; 
font-weight:bold; 
font-size:12px; 
text-align:center;
color:#0958FD;
}
</style>
<!--script type="text/javascript" src="js/topmenu.js"></script>
<script type="text/javascript" src="js/rmenu.js"></script-->

</head>
<body>
    <noscript><br><br>
    <h3 style="color:#FF0000">WARNING - YOU WILL BE UNABLE TO USE INTENSECART
      PROPERLY IF YOU DO NOT ENABLE JAVASCRIPT IN YOUR BROWSER. PLEASE <a href="enable-javascript.html">CLICK
      HERE FOR SIMPLE DIRECTIONS ON HOW TO ENABLE IT</a>.</h3>
    </noscript>
    
    
<table width="773" border="0" align="center" cellpadding="0" cellspacing="0">
	  <tr>
	   <td style="width:773px; height:13px;"></td>
	  </tr>
	  <tr>
	   <td><table border="0" cellpadding="0" cellspacing="0" width="773">
		  <tr>
		   <td style="width:7px; height:831px; background:url(images/ix_d.jpg) repeat-y"></td>
		   <td valign="top" width="760"><table border="0" cellpadding="0" cellspacing="0" width="760">
			  <tr>
			   <td style="width:760px; height:7px; background:url(images/ix_e.jpg) repeat-x"></td>
			  </tr>
			  <tr>
			   <td style="width:760px; height:145px;"><table width="100%" height="145" border="0" cellpadding="0" cellspacing="0">
                 <tr>
                   <td width="236"></td>
                   <td rowspan="2" width="524" height="145" valign="top" style="background-image:url(images/topmenu-bg.jpg); background-repeat:repeat-x;">

<table width="100%" border="0" cellspacing="0" cellpadding="0">
                     <tr><!-- alt="Click for Enterprise Performance Data and Reports" -->

                       <td style="width:90px; height:145px;"><img src="images/nav1.jpg" alt="Order Managment" width="90" height="145" border="0" onMousedown="loadintoIframe('myframe','orders.php')" onClick="sampleAccordion.openPanel(sampleAccordion.getPanels()[1]);" style="cursor:pointer"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav2.jpg" alt="Customer Manager" width="85" height="145" border="0" onMousedown="loadintoIframe('myframe','customers.php')" onClick="sampleAccordion.openPanel(sampleAccordion.getPanels()[2]);" style="cursor:pointer"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav3.jpg" alt="Inventory &amp; Product Manager" width="85" height="145" border="0" onMousedown="loadintoIframe('myframe','categories.php?pclass=product_default')" onClick="sampleAccordion.openPanel(sampleAccordion.getPanels()[3]);" style="cursor:pointer"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav4.jpg" alt="Market Place &amp; Auction Manager" width="85" height="145" border="0" onMousedown="loadintoIframe('myframe','module_config.php?set=dbfeed')" onClick="sampleAccordion.openPanel(sampleAccordion.getPanels()[6]);" style="cursor:pointer"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav5.jpg" alt="Search Engine Marketing Manager" width="85" height="145" border="0" onMousedown="loadintoIframe('myframe','seotools/seo-tools.php')" onClick="sampleAccordion.openPanel(sampleAccordion.getPanels()[6]);" style="cursor:pointer"></td>

                       <td style="width:94px; height:145px;"><img src="images/nav6.jpg" alt="Sales Reports, Trend Charts and Traffic Graphs" width="94" height="145" border="0" onClick="sampleAccordion.openPanel(sampleAccordion.getPanels()[5]);" onMousedown="loadintoIframe('myframe','stats_sales_report.php');" style="cursor:pointer">
<!--<img src="images/nav6.jpg" alt="Template, Content and File Manager" width="94" height="145" border="0" onClick="loadintoIframe('myframe','information_manager.php')" style="cursor:pointer">--></td>

                     </tr>
                   </table>


<!--<table border="0" cellpadding="0" cellspacing="0" >
  <tr>
    <td valign="top" style="padding-top:10px;">
<table width="524" border="0" cellspacing="5" cellpadding="0" style="font-family:Arial; font-weight:bold; font-size:12px; color:#0958FD;">
  <tr>
    <td height="25" align="center" class="topmenu-font" nowrap>Order Manager</td>
    <td width="85" align="center" class="topmenu-font">Inventory</td>
    <td width="85" align="center" class="topmenu-font">Market Place</td>
    <td width="85" align="center" class="topmenu-font">Marketing</td>
    <td width="85" align="center" class="topmenu-font">Reports</td>
    <td width="85" align="center" class="topmenu-font">Site Designer</td>
  </tr>
</table>
</td>
  </tr>
  <tr>
    <td valign="top"><div id="fisheye" class="fisheye">
	<div style="position:relative; width:524px;">	<div class="fisheyeContainter">
			<a href="#" onClick="loadintoIframe('myframe','orders.php')" class="fisheyeItem"><img src="images/nav1.gif" width="85" /><span></span></a>
			<a href="#" onClick="loadintoIframe('myframe','categories.php')" class="fisheyeItem"><img src="images/nav2.gif" width="85" /><span></span></a>
			<a href="#" class="fisheyeItem" onClick="loadintoIframe('myframe','/seotools/seo-tools.php')"><img src="images/nav3.gif" width="85" /><span></span></a>
			<a href="#" class="fisheyeItem" onClick="loadintoIframe('myframe','/seotools/seo-tools.php')"><img src="images/nav4.gif" width="85" /><span></span></a>
			<a href="#" class="fisheyeItem" onClick="loadintoIframe('myframe','stats_sales_report.php')"><img src="images/nav5.gif" width="85" /><span></span></a>
			<a href="#" class="fisheyeItem" onClick="loadintoIframe('myframe','information_manager.php')"><img src="images/nav6.gif" width="85" /><span></span></a>		</div>
</div></div>
<script type="text/javascript">
	
	$(document).ready(
		function()
		{
			$('#fisheye').Fisheye(
				{
					maxWidth: 10,
					items: 'a',
			     	itemsText: 'span',
					container: '.fisheyeContainter',
					itemWidth: 85,
					proximity: 65,
					halign : 'center'
				}
			)
		}
	);

</script></td>
  </tr>
</table>-->
</td>
                 </tr>
                 <tr>
                   <td valign="top"><table width="81%" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td width="193" height="108"><table width="236" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center" style="height:82px;"><a href="/admin/"><?php echo tep_image(DIR_WS_IMAGES . 'logo-site.gif',  TITLE, '', ''); ?></a></td>
  </tr>
  <tr>
    <td align="center" style="padding:5px 0 5px 0"><img src="images/livehelp.jpg" width="145" height="54" border="0" onMousedown="loadintoIframe('myframe','../../knowledgebase/index.php')" onClick="sampleAccordion.openPanel(sampleAccordion.getPanels()[9]);" style="cursor:help" alt=""></td>
  </tr>
</table>
</td>
                     </tr>
                     <tr>
                       <td align="center"></td>
                     </tr>
                   </table></td>
                 </tr>
               </table></td>
			  </tr>
			  <tr>
			   <td class="breadcrumb-bar" style="height:24px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" style="font: bold 11px tahoma; color:#FFFFFF;">&nbsp;&nbsp; <span id="breadcrumb" class="breadcrumblink"></span></td>
    <td align="right" style="font: bold 11px tahoma; color:#FFFFFF; padding-right:10px;"><a href="index.php?admin_logout=1" class="breadcrumblink">Logoff</a></td>
  </tr>
</table>
 </td>
			  </tr>
			  <tr>
			   <td align="center" style="width:760px; height:25px; background-color:#EEEFEF"><table width="760" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="700" align="right" style="padding-right:10px;"><script type="text/javascript" src="js/data-site.js.php"></script></td>
    <td style="width:60px;"><table width="60" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="20"><a href="javascript:loadintoIframe('myframe','javascript:history.go(-1)
;');"><img src="images/nav-back-sm.gif" alt="Navigate back" width="12" height="15" border="0"></a></td>
        <td width="20"><a href="javascript:document.getElementById('myframe').contentWindow.location.reload();"><img src="images/nav-reload-sm.gif" alt="Reload data" width="13" height="15" border="0" ></a></td>
        <td width="20"><a href="javascript:loadintoIframe('myframe','javascript:history.go(1)
;');""><img src="images/nav-next-sm.gif" alt="Navigate forward" width="12" height="15" border="0"></a></td>
      </tr>
    </table></td>
  </tr>
</table>
</td>
			  </tr>
			  <tr>
			   <td style="width:760px; height:5px; background:url(images/ix_k.jpg) repeat-x"></td>
			  </tr>
			  <tr>
			   <td><table border="0" cellpadding="0" cellspacing="0" width="760">
				  <tr>
				   <td style="width:3px; height:662px; background-color:#F1F1F1"></td>
				   <td align="center" valign="top" style="width:178px; height:618px; background-color:#F1F1F1">
				   <div id="maintab" style="width:178px;">
</div>
</td>
<td style="width:4px; height:618px; background-color:#FFFFFF"></td>
				   <td valign="top" style="width:575px; height:618px; padding-bottom:5px; background-color:#F0F5FB">
				   <script language="javascript" src="popcalendar.js"></script>
				   <iframe id="myframe" name="contentiframe" src="<?=$FramePage?>" scrolling="no" marginwidth="0" marginheight="0" frameborder="0" style="overflow:visible; width:575px;" allowtransparency="true"></iframe>
 </td>
				  </tr>
				</table></td>
			  </tr>
			  <tr>
			   <td style="width:760px; height:7px; background:url(images/ix_p.jpg) repeat-x"></td>
			  </tr>
			</table></td>
		   <td style="width:6px; height:831px; background:url(images/ix_f.jpg) repeat-y"></td>
		  </tr>
		</table></td>
	  </tr>
	  <tr>
	   <td style="width:773px; height:71px;"><?php require(DIR_WS_INCLUDES . 'footer.php'); ?></td>
	  </tr>
</table>


<div style="display:none">    
				   <div class="Accordion" id="sampleAccordion" tabindex="0">
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onClick="loadintoIframe('myframe',Dashboard)">
<table width="176" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/butA-icon.jpg" width="28" height="28" alt=""></td>
    <td style=" padding-top:3px; padding-left:6px;">Performance Dashboard</td>
    <td valign="top" style="padding-left:1px; padding-right:1px; padding-top:1px; cursor:help;"><img src="images/tip-sm.gif" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Performance Dashboards</b></font><br>Depending on your permissions, you have access to some very useful dashboards containing bird\'s eye views of your performance indicators.<br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent">
			<table width="165" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td valign="top"  style="padding-top:6px; padding-bottom:6px;"><? if (sizeof($Dashboards)>1) { ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:25px; padding-left:10px; border:1px solid #D4D4D4;">Switch Dashboard Role</td>
  </tr>
  <tr>
    <td style="padding-top:5px; padding-left:10px; padding-bottom:5px; padding-right:0; border:1px solid #D4D4D4; border-top:0"><form action="" method="get" style="margin:0;"><?=tep_draw_pull_down_menu('dashboard',$Dashboards,'',' style="width:143px; font-size:11px; font-family:arial;" onChange="document.getElementsByTagName(\'a\')[0].focus(); loadintoIframe(\'myframe\',Dashboard=this.value);"')?>
    </form></td>
  </tr>
</table>
<? } ?></td>
      </tr>
    </table></td>
  </tr>
  </table>
		</div>
	</div>


	
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onClick="loadintoIframe('myframe','supertracker.php')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but5-icon.jpg" width="28" height="28"></td>
    <td width="127" style="padding-left:6px; padding-top:3px;">Reports & Analytics</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img src="images/tip-sm.gif" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br><br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent">
		  <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td  style="padding-top:6px; padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                                         
                        <tr>
                          <td width="22" height="23" align="center"><img src="images/watch2-icon.gif" width="19" height="19"></td>
                          <td style="padding-left:5px;"><b><a href="javascript:loadintoIframe('myframe','stats_products_viewed.php');">Most Viewed</a></b></td>
                        </tr>
                        <tr>
                          <td width="22" height="23" align="center"><img src="images/banner-icon.gif" width="21" height="13"></td>
                          <td style="padding-left:5px;"><b><a href="javascript:loadintoIframe('myframe','stats_ad_results.php');">Ad
                                Results</a></b></td>
                        </tr>
                           
                          <td width="22" height="23" align="center"><img src="images/graph-icon.jpg" width="16" height="15"></td>
                          <td style="padding-left:5px;"><b><a href="javascript:loadintoIframe('myframe','supertracker.php');">Traffic
                                Statistics</a></b></td>
                        </tr>
                        <tr>
                          <td width="22" height="23" align="center">&nbsp;</td>
                          <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','stats_average.php');"><b>Stats Summary</b></a>
						  
</td>
                        </tr>
<tr>
                          <td width="22" height="23" align="center">&nbsp;</td>
                          <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','stats_referral_sources.php');"><b>Referal Summary</b></a>
</td>
                        </tr>
                    </table></td>
                  </tr>
              </table></td>
            </tr>
          </table>
		</div>
	</div>
	
	<div class="AccordionPanel">
		<div class="AccordionPanelTab"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but6-icon.jpg" width="28" height="28"></td>
    <td width="127" style="padding-left:6px; padding-top:3px;">Marketing Panel</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img src="images/tip-sm.gif" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br><br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent">
<table border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td  style="padding-top:6px; padding-bottom:6px;"><table width="165" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
  <tr>
    <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
          <td width="22" height="23" align="center"><img src="images/target-icon.gif" width="20" height="20"></td>
<!--/*<td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','apilitax/index.php');"><b>PPC Ad Manager </b></a></td>*/-->
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','product_ads.php?campaigns=1');"><b>PPC Ad Manager </b></a></td>

        </tr>
<tr>
          <td width="22" height="23" align="center"><img src="images/target-icon.gif" width="20" height="20"></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','supertracker.php?special=ppc_summary');"><b>PPC Reports</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/google_icon.gif" width="16" height="16"></td>
       <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','seotools/seo-tools.php');"><b>SEO
             &amp; Submission </b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/seo-icon.gif" width="19" height="19"></td>
          <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','seotools/seo-tools.php');"><b>Optimization Tools</b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/seo-icon.gif" width="19" height="19"></td>
          <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','header_tags_english.php');"><b>META Tags Control</b></a></td>
        </tr>
        <tr>
          <td width="22" height="23" align="center"><img src="images/graph-icon.jpg" width="16" height="15"></td>
          <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','configuration.php?gID=961');"><b>Tracking &amp; Metrics </b></a></td>
        </tr>
        <tr>
          <td height="23" align="center"><img src="images/graph-icon.jpg" width="16" height="15"></td>
          <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','ad_campaigns.php');"><b>Tracking
                URL Creator </b></a></td>
        </tr>
      </table></td>
  </tr>
</table></td>
  </tr>
</table></td>
              </tr>
              <tr>
                <td><table width="165" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td  style="padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
  <tr>
    <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="22" height="23" align="center"><img src="images/newsletter-icon.gif" width="19" height="17"></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','newsletters.php');"><b>Newsletter Manager </b></a></td>
        </tr>
        <tr>
          <td width="22" height="23" align="center"><img src="images/csv-icon.gif" width="17" height="17"></td>
          <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','customer_export.php');"><b>CSV
                Email Export</b></a></td>
        </tr>
      </table></td>
  </tr>
</table></td>
  </tr>
</table></td>
              </tr>
              <tr>
                <td><table width="165" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td  style="padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                        <tr>
                          <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td width="22" height="23" align="center"><img src="images/gavel-icon.gif" width="22" height="13"></td>
                                <td width="127" style="padding-left:5px;"><b><a href="javascript:loadintoIframe('myframe','javascript:void(0);');">Auction
                                      Manager </a></b></td>
                              </tr>
                              <tr>
                                <td height="23" align="right"><img src="images/banner-icon.gif" width="21" height="13"></td>
                                <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','banner_manager.php');"><b>Banner
                                      Manager </b></a></td>
                              </tr>
                              <tr>
                                <td height="23" align="center"><img src="images/feed-icon.gif" width="16" height="16"></td>
                                <td style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','javascript:void(0);');"><b> Feeds
                                  &amp; Site Maps </b></a></td>
                              </tr>
                          </table></td>
                        </tr>
                    </table></td>
                  </tr>
                </table></td>
              </tr>
              <tr>
                <td><table width="165" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
  <tr>
    <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="22" height="23" align="center"><img src="images/affiliate-icon.gif" width="18" height="18"></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','affiliate_summary.php');"><b>Affiliate Summary</b></a></td>
        </tr>
<tr>
          <td width="22" height="23" align="center"><img src="images/consult-icon.gif" width="22" height="21"></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','affiliate_affiliates.php');"><b>Affiliate Manager </b></a></td>
        </tr>
<tr>
          <td width="20" height="23" align="center"><b>&raquo;</b></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','customers_groups.php');"><b>Commission Groups </b></a></td>
        </tr>
<tr>
          <td width="20" height="23" align="center"><b>&raquo;</b></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','affiliate_payment.php');"><b>Disburse Payments</b></a></td>
        </tr>
<tr>
          <td width="20" height="23" align="center"><b>&raquo;</b></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','affiliate_clicks.php');"><b>Affiliate Referrals</b></a></td>
        </tr>
<tr>
          <td width="20" height="23" align="center"><b>&raquo;</b></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','affiliate_banners.php');"><b>Affiliate Banners</b></a></td>
        </tr>
<tr>
          <td width="20" height="23" align="center"><b>&raquo;</b></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','affiliate_news.php');"><b>Announcements</b></a></td>
        </tr>
<tr>
          <td width="20" height="23" align="center"><b>&raquo;</b></td>
          <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','affiliate_contact.php');"><b>Affiliate Newsletter</b></a></td>
        </tr>
      </table></td>
  </tr>
</table></td>
  </tr>
  <tr>
  <td style="padding-bottom:6px">
  <table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
  <tr>
    <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td> <?php


// Affiliate  
$affiliate_sales_raw = "select count(*) as count, sum(affiliate_value) as total, sum(affiliate_payment) as payment from " . TABLE_AFFILIATE_SALES . " ";
$affiliate_sales_query= tep_db_query($affiliate_sales_raw);
$affiliate_sales= tep_db_fetch_array($affiliate_sales_query);

$affiliate_clickthroughs_raw = "select count(*) as count from " . TABLE_AFFILIATE_CLICKTHROUGHS . " ";
$affiliate_clickthroughs_query=tep_db_query($affiliate_clickthroughs_raw);
$affiliate_clickthroughs= tep_db_fetch_array($affiliate_clickthroughs_query);
$affiliate_clickthroughs=$affiliate_clickthroughs['count'];

$affiliate_transactions=$affiliate_sales['count'];
if ($affiliate_transactions>0) {
	$affiliate_conversions = tep_round($affiliate_transactions/$affiliate_clickthroughs,6)."%";
}
else $affiliate_conversions="n/a";

$affiliate_amount=$affiliate_sales['total'];
if ($affiliate_transactions>0) {
	$affiliate_average=tep_round($affiliate_amount/$affiliate_transactions,2);
}
else {
	$affiliate_average="n/a";
}
$affiliate_commission=$affiliate_sales['payment'];

$affiliates_raw = "select count(*) as count from " . TABLE_AFFILIATE . "";
$affiliates_raw_query=tep_db_query($affiliates_raw);
$affiliates_raw = tep_db_fetch_array($affiliates_raw_query);
$affiliate_number= $affiliates_raw['count'];


  $heading = array();
  $contents = array();

  $heading[] = array('params' => 'class="menuBoxHeading"',
                     'text'  => BOX_TITLE_AFFILIATES);

  $contents[] = array('params' => 'class="infoBox"',
                      'text'  => BOX_ENTRY_AFFILIATES . ' ' . $affiliate_number . '<br>' .
                                 BOX_ENTRY_CONVERSION . ' ' . $affiliate_conversions . '<br>' .
                                 BOX_ENTRY_COMMISSION . ' ' . $currencies->display_price($affiliate_commission, ''));

  $box = new box;
  echo $box->menuBox($heading, $contents);

  echo '';



?></td>
          </tr>
        
      </table></td>
  </tr>
</table></td></tr>
</table></td>
              </tr>
            </table>
		</div>
	</div>
	
	<div class="AccordionPanel">
		<div class="AccordionPanelTab"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but7-icon.jpg" width="28" height="28"></td>
    <td width="127" style="padding-left:6px; padding-top:3px;"> Design &amp; File
      Manager</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img src="images/tip-sm.gif" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br><br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent">
		  <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td  style="padding-top:6px; padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                  <tr>
                    <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                          <td height="23" align="center"><img src="images/templates-icon.gif" width="16" height="16"></td>
                          <td style="padding-left:5px;"><A href="javascript:loadintoIframe('myframe','information_manager.php');" ><b>Web Page Control</b></A></td>
                        </tr>
                        <tr>
                          <td width="22" height="23" align="center"><img src="images/files-icon.gif" width="16" height="16"></td>
                          <td style="padding-left:5px;"><A href="javascript:loadintoIframe('myframe','file_manager.php');" ><b>File Manager</b></A></td>
                        </tr>
                    </table></td>
                  </tr>
              </table></td>
            </tr>
          </table>
		</div>
	</div>
	<div class="AccordionPanel">
		<div class="AccordionPanelTab"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but8-icon.jpg" width="28" height="28"></td>
    <td width="127" style="padding-left:6px; padding-top:3px;">Webmail Manager </td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img src="images/tip-sm.gif" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br><br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent">
		  <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td  style="padding-top:6px; padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                  <tr>
                    <td style="padding:7px;"><table width="149" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="22" height="23" align="center"><img src="images/webmail-icon-sm.gif" width="21" height="21"></td>
                        <td width="127"><a href="javascript:loadintoIframe('myframe','../../mail/index.php');" style="padding-left:5px;"><b>Launch
                              Webmail</b></a></td>
                      </tr>
                      <tr>
                        <td height="23" align="center"><img src="images/mailbox-icon.gif" width="14" height="15"></td>
                        <td><a href="javascript:loadintoIframe('myframe','mailboxes.php');" style="padding-left:5px;"><b>Manage
                              Mailboxes</b></a></td>
                      </tr>


                    </table></td>
                  </tr>
              </table></td>
            </tr>
          </table>
		</div>
	</div>
	
	<div class="AccordionPanel">
		<div class="AccordionPanelTab"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but9-icon.jpg" width="28" height="28"></td>
    <td width="127" style="padding-left:6px; padding-top:3px;">Knowledge Base</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img src="images/tip-sm.gif" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Tool Tips</b></font><br><br>More Tools Tips About This Tool Tip<br><br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent">
		  <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td  style="padding-top:6px; padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                  <tr>
                    <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="22" height="23" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14"></td>
                        <td width="127"><a href="javascript:loadintoIframe('myframe','#');" style="padding-left:5px;"><b>Admin  Walkthroughs </b></a></td>
                      </tr>
                                        <tr>
                        <td width="22" height="23" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14"></td>
                        <td style="padding-left:5px;"><A href="javascript:loadintoIframe('myframe','file_manager.php');" ><b> Marketing Tools</b></A><a href="file_manager.php"></a></td>
                      </tr>
                      <tr>
                        <td width="22" height="23" align="center"><img src="images/tip-blue-sm.gif" width="14" height="14"></td>
                        <td style="padding-left:5px;"><A href="javascript:loadintoIframe('myframe','file_manager.php');" ><b>Useful Advice</b></A></td>
                      </tr>
                    </table></td>
                  </tr>
              </table></td>
            </tr>
            <tr>
              <td  style="padding-bottom:6px;"><table width="165" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #B7B7B7; background-color:#FFFFFF;">
                <tr>
                  <td style="padding:7px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="22" height="23" align="center"><img src="images/mag-icon.gif" width="15" height="15"></td>
                        <td width="127" style="padding-left:5px;"><a href="javascript:loadintoIframe('myframe','customer_export.php');"><span style="padding-top:10px;"><b>Quick
                                Search</b></span></a></td>
                      </tr>
                      <tr>
                        <td colspan="2" align="center" style="padding-top:5px; color:#000000">Features, Tools, Products:</td>
                      </tr>
                      <tr>
                        <td colspan="2" align="center" style="padding-top:5px;">
						<form name="form1" method="post" action="" style="margin:0;">
						<input style="font:9pt verdana; width:135px;">
						</form>
						</td>
                        </tr>
                      <tr>
                        <td colspan="2" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td align="center" style="color:#000000;"><u>More Options</u></td>
                            <td align="center" style="padding-top:7px;"><table width="52" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td align="center" style="border:2px solid #D4D4D4; background:#F5F5F5; height:20px; width:52px;"><a href="#" style="color:#000000;">Search</a></td>
                              </tr>
                            </table></td>
                          </tr>
                        </table></td>
                        </tr>
                      
                  </table></td>
                </tr>
              </table></td>
            </tr>
          </table>
		</div>
	</div></div>
<script language="JavaScript" type="text/javascript">
var sampleAccordion = new Spry.Widget.Accordion("sampleAccordion",{enableClose:false, panelCookie:'adminMenuPanel', defaultPanel:'<?=isset($_COOKIE['adminMenuPanel'])?$_COOKIE['adminMenuPanel']:0?>'});
</script></div>
    
<script language="JavaScript" type="text/javascript">
  document.getElementById('maintab').appendChild(document.getElementById('sampleAccordion'));
</script>

<div id="menu" class="menu">
<a href="javascript:void(0);" onMouseDown="document.getElementById('myframe').contentWindow.location.reload()" onMouseUp="window.location.reload();" class="menuitem"><img src="images/nav-reload-sm.gif" width="13" height="15" border="0"> Reload Data</a>
<a href="javascript:loadintoIframe('myframe','create_order.php');" class="menuitem"><img src="images/bag-icon.gif" width="15" height="15" border="0"> &nbsp;Create Quick Order</a>
<div class="seperator"></div>
<a href="#" class="menuitem">...</a>
<a href="#" class="menuitem">...</a>
<div class="seperator"></div>
<a href="#" class="menuitem">...</a>
<a href="javascript:loadintoIframe('myframe','mailto:support@intensecart.com');" class="menuitem"><img src="images/loading.gif" width="16" height="16" border="0"> &nbsp;Contact Support</a></div>
</body>
</html>
