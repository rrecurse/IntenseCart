<?php
/*

  IntenseCart, E-Commerce and Internet Marketing Solutions
  http://www.intensecart.com

  Copyrights (c) 2006 - 2015 IntenseCart eCommerce.

  Redistribution without explicit written consent is forbidden under US and International Copyright Laws
*/

  require('includes/application_top.php');

  if (defined('SITE_SERVICE_TYPE')) {
    if (SITE_SERVICE_TYPE=='intensesite') {
       header('Status: 302 Redirect');
       header('Location: '.HTTP_SERVER.DIR_WS_ADMIN.'index-site.php');
       exit;
    }
  }

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
  
  $dash_qry=tep_db_query("SELECT dash_name,dash_permission FROM admin_dash GROUP BY dash_name ORDER BY dash_name!='".addslashes(getAdminOption('default_dash'))."'");

	$Dashboards = array();

	while($dash_row=tep_db_fetch_array($dash_qry)) {
		if (AdminPermission($dash_row['dash_permission'])) {
			$Dashboards[] = array(id => 'dashboard.php?dash='.urlencode($dash_row['dash_name']),'text' => $dash_row['dash_name']);
		}
	}
  
  if(isset($_COOKIE['iframe_src_myframe'])) $FramePage=$_COOKIE['iframe_src_myframe'];

  if(isset($FramePage)) {
    $FramePageFile=preg_replace('|.*/|','',str_replace(' ','+',$FramePage));
    foreach ($Dashboards AS $dash) if ($FramePageFile==$dash['id']) $Dashboard=$FramePageFile;
  }
  if(!isset($Dashboard)) $Dashboard=$Dashboards[0]['id'];

  $dpage = getAdminOption('default_page');

  if(!isset($FramePage)) {
    if(!$dpage) $dpage=$Dashboard;
    $FramePage=HTTP_SERVER.DIR_WS_ADMIN.$dpage;
  }


	 // # image found test - check for missing images

	$thedir = IX_PATH_SITE.'images/';

	$image_find_query = tep_db_query("SELECT p.products_id, p.products_image, p.products_image_xl_1, p.products_image_xl_2,p.products_image_xl_3,p.products_image_xl_4, pd.products_name
									  FROM products p
									  LEFT JOIN products_description pd ON p.products_id = pd.products_id
									  WHERE p.products_status = 1 
									  AND p.products_price > 0
									  #AND (p.products_image = '' OR p.products_image IS NULL 
										#   OR p.products_image = '' OR p.products_image_xl_1 IS NULL 
										#   OR p.products_image_xl_1 = '' OR p.products_image_xl_2 IS NULL
										#   OR p.products_image_xl_3 = '' OR p.products_image_xl_3 IS NULL 
										#   OR p.products_image_xl_4 = '' OR p.products_image_xl_4 IS NULL)
									  GROUP BY pd.products_name
									");

	while($img =  mysql_fetch_array($image_find_query)) { 
		
		if(!file_exists($thedir.$img['products_image'])) { 
			echo $thedir.$img['products_image'] . ' - ' . $img['products_name'] . ' - Missing from '.$thedir.'<br>';

			//tep_db_query("UPDATE products SET products_image=NULL WHERE products_id = '".$img['products_id']."' AND products_status = '0'");
		}

		if(!file_exists($thedir.$img['products_image_xl_1'])) { 
			echo $thedir.$img['products_image_xl_1'] . ' - ' . $img['products_name'] . ' - Missing from '.$thedir.'<br>';
			
			//tep_db_query("UPDATE products SET products_image_xl_1 = NULL WHERE products_id = '".$img['products_id']."'");
		}

		if(!file_exists($thedir.$img['products_image_xl_2'])) { 
			echo $thedir.$img['products_image_xl_2'] . ' - ' . $img['products_name'] . ' - Missing from '.$thedir.'<br>';
		
			//tep_db_query("UPDATE products SET products_image_xl_2 = NULL WHERE products_id = '".$img['products_id']."'");
		}

		if(!file_exists($thedir.$img['products_image_xl_3'])) { 
			echo $thedir.$img['products_image_xl_3'] . ' - ' . $img['products_name'] . ' - Missing from '.$thedir.'<br>';

			//tep_db_query("UPDATE products SET products_image_xl_3 = NULL WHERE products_id = '".$img['products_id']."'");
		}
	}


?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<META HTTP-EQUIV="MSThemeCompatible" CONTENT="no">
<title><?php echo TITLE; ?></title>

<!--[if !IE]> -->
<link rel="stylesheet" href="js/css.css" type="text/css">
<!-- <![endif]-->

<!--[if IE]>
<link rel="stylesheet" href="js/css-ie.css" type="text/css">
<![endif]-->

<script async  src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="js/tips.js"></script>
<script type="text/javascript" src="js/iframe.js"></script>

<script src="js/jquery-2.0.3.min.js"></script>

<script type="text/javascript">
  var Dashboard='<?=$Dashboard?>';
  var Dashboards={};
  <? foreach ($Dashboards AS $dash) { ?>Dashboards['<?=$dash['id']?>']='<?=$dash['text']?>';
  <? } ?>

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
      for (var i=0;cont[i];i++) html[html.length]=cont[i].link?'<a href="'+(cont[i].link.toString().match(/^javascript:/)?cont[i].link:'javascript:loadintoIframe(\'myframe\',\''+cont[i].link+'\')')+'" class="breadcrumblink">'+cont[i].title+'<\/a>':cont[i].title;
      bc.innerHTML=html.join('&nbsp;&raquo;&nbsp;');
      return true;
    }
    return false;
  }


</script>
<style type="text/css">

/* topmenu */

#nav {padding:0; margin:0; list-style:none; height:21px; background-color:#EEEFEF; position:relative; z-index:500; font-weight:normal; font-size: 11px; font-family: tahoma;}

#nav li.top {display:block; float:left; height:21px;}

#nav li a.top_link {display:block; border:dotted 1px #EEEFEF; float:left; height:18px; line-height:18px; color:#000; text-decoration:none; padding:0 0 0 12px; cursor:pointer;}
#nav li a.top_link span {float:left; display:block; padding:0 9px 0 0; height:19px;}
#nav li a.top_link span.down {float:left; display:block; padding:0 9px 0 0; height:19px;}

#nav li:hover a.top_link {color:#0033FF; border:dotted 1px #999;}
#nav li:hover a.top_link span {}
#nav li:hover a.top_link span.down {}

/* Default list styling */

#nav li:hover {position:relative; z-index:200; opacity:0.95; filter:alpha(opacity=95); background-color:#FFF}

#nav li:hover ul.sub {left:1px; top:20px; padding:4px; white-space:nowrap; width:105px; height:auto; z-index:300; background-color:#EEEFEF}
#nav li:hover ul.sub li {display:block; height:20px; position:relative; float:left; width:100%; font-weight:normal;}
#nav li:hover ul.sub li a {display:block; font-size:11px; height:18px; width:110px; line-height:18px; text-indent:5px; color:#000; text-decoration:none;}
#nav li ul.sub li a.fly {background:#EEEFEF url(images/arrow.gif) 95% 7px no-repeat;}
#nav li:hover ul.sub li a:hover {color:#0033FF; border:dotted 1px #999; width:108px; background-color:#FFF;}
#nav li:hover ul.sub li a.fly:hover {background:#EEEFEF url(images/arrow_over.gif) 95% 7px no-repeat; color:#0033FF;}


#nav li:hover li:hover ul,
#nav li:hover li:hover li:hover ul,
#nav li:hover li:hover li:hover li:hover ul,
#nav li:hover li:hover li:hover li:hover li:hover ul
{left:95px; top:3px; background: #fff; padding:3px;  white-space:nowrap; width:110px; z-index:400; height:auto;}

#nav ul, 
#nav li:hover ul ul,
#nav li:hover li:hover ul ul,
#nav li:hover li:hover li:hover ul ul,
#nav li:hover li:hover li:hover li:hover ul ul
{position:absolute; left:-9999px; top:-9999px; width:0; height:0; margin:0; padding:0; list-style:none;}

#nav li:hover li:hover a.fly,
#nav li:hover li:hover li:hover a.fly,
#nav li:hover li:hover li:hover li:hover a.fly,
#nav li:hover li:hover li:hover li:hover li:hover a.fly
{background:#EEEFEF url(images/arrow_over.gif) 97% 7px no-repeat; color:#0033FF; border:dotted 1px #999; outline:none} 

#nav li:hover li:hover li a.fly,
#nav li:hover li:hover li:hover li a.fly,
#nav li:hover li:hover li:hover li:hover li a.fly
{background:#fff url(images/arrow.gif) 97% 7px no-repeat; color:#000; border-color:#EEEFEF; outline:none;} 

/* end topmenu */

div.tabbed {
	width: 100%;
	min-width:990px;
	margin: 0;
	padding: 10px 0 0 1px;
	height:58px;
	overflow:hidden;
}

ul.tabbed {
	list-style-type: none;
	margin:0;
	padding:0;
}

ul.tabbed li {
	margin: 0 1px 0 0;
	float: left;
	width:16.5%;
}

ul.tabbed a {
	float: left;
	display: block;
	padding: 0 0;
	border: 0;
	border-bottom: 0;
	color: #fff;
	background:transparent url(images/headtabs.jpg) no-repeat right -60px;
	text-decoration: none;
	font:bold 12px arial;
	outline:none;
	line-height:58px;
	height:58px;
	width:100%;
	min-width:165px;
}

ul.tabbed a:hover {
	background:transparent url(images/headtabs.jpg) no-repeat right 0px;
	outline:none;
	color: #333;
	font:bold 12px arial;
	line-height:58px;
	height:58px;
	width:100%;
	min-width:165px;
}

ul.tabbed a.active {
	background:transparent url(images/headtabs.jpg) no-repeat right 0px;
	cursor: default;
	color: #333;
	outline:none;
	font:bold 12px arial;
	line-height:58px;
	height:58px;
	width:100%;
	min-width:165px;
}

ul.tabbed img {border:0;}

.tabs-container {
	clear: left;
	display:none;
}

#headtabs h2 {
	font-size: 100%;
	margin: 0;
}

.pos {position:relative; width:100%; height:58px;}
.pos2 {position:absolute; top:-8px; left:6px;}

@media screen and (max-width: 1450px) {

	.pos3 { 
		position:absolute; 
		width:80px !important;
		top:25%; 
		right:13px !important; 
		text-align:right;
		font:bold 12px arial !important; 
		color:#fff; 
		white-space:wrap;
	}
}

@media screen and (min-width: 1451px) {

	.pos3 {
		position:absolute;
		vertical-align:middle; 
		width:80%; 
		top:40%; 
		right:15px; 
		text-align:right; 
		line-height: 58px;
		height:58px; 
		font:bold 14px arial; 
		color:#fff; 
		margin: 0;
	}

}

a.active .pos3 {color:#333}
a:hover .pos3 {color:#333}

.tipIcon {
	background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABZ0RVh0Q3JlYXRpb24gVGltZQAxMi8yMy8wOVRBsv0AAAAhdEVYdFNvZnR3YXJlAE1hY3JvbWVkaWEgRmlyZXdvcmtzIDQuMOomJ3UAAAMZSURBVHicZZO9S1t7AIaf3+8cTz5OyKLGEIkaUFCCmrWtQisugW4Oukid2+ne/6D/wL1T61w0i6M4VloRq4soV7QOilE0JubDIyeJJ+fzTi1K3/nlWd73EUEQ8DQPDw9aKBR67TjOOyHENICiKNuapn1ptVrf4/G4/bQvngCEZVkTruv+BbzSNE2XUgLgeR6O47Q8z9tRFOXfWCz2HxA8A7Tb7be+73+KRqNdvu9jWRbtdpsgCAiFQsRiMWzbptlsOuFw+EM8Ht/4DbBte8y27a+6rgvDMDg/P8fzPHRdx/M86vU6kUiEXC5HEASUSqUgmUzOxmKxnxKQtm1/jEajolarsbm5SblcZmJigu7ubjKZDNlslqOjI9bX19E0DUVRRLFY/AhI6bruG+Cl7/tsb29TLBbp7e3FMAyWl5cpFAokEgnS6TQHBwfs7e0xMDBAuVx+2Ww236iu674Ph8Py6uqKs7MzbNtma2uLTqdDuVwmkUgghKBeryOlZH9/n6mpKYIgkMVi8b0qhHihqiqlUolms0lXVxeNRgPXdRkdHWVpaYnDw0NOTk4IhUKYponrugBUq9UXEiAIAhRFodPpIIQgGo0SiURYXFzE8zwKhQJSSoQQ+L6PlBLLsrBtG6koyq5t26TTaRzH4desUkpOT09ZW1sDQNd1DMOgp6cHKSW3t7four4rFUX53Ol0/P7+fiYnJ7m5uUEIgaqqNBoNfN8nHo9jmiatVot8Pk+1WqVSqfgjIyOfpRDim6qqP0zTZG5ujrGxMS4vLzFNk+npabLZLBcXF9zf3zM/P08mk2F1dZWhoaEffX1930QQBPi+P9ZoNL5KKYWmaezs7LC7u0symcSyLBRFIZ/PMzw8zMrKCqVSKVhYWJgdHBz8+fvKlmW9rVarnyzL6kokEriuy+PjI7quo+s6d3d3bGxs0Gw2ndnZ2Q+5XG7jmQuAME1z3DCMv6+vr191Oh3d933a7Ta1Wo1KpdJKpVI74+Pj/+RyuaM/ZPoVwzA0VVVf12q1d6ZpTjuOgxBiO5VKfTk+Pv4+MzPzTOf/AbY9oHZ8+CVdAAAAAElFTkSuQmCC) no-repeat 0 0;
width:16px;
height:16px;
cursor:help;
}
<?php 
list($width, $height, $type, $attr) = getimagesize(DIR_WS_IMAGES . 'logo.gif');
?>

	.logo {
		text-align:left;
		height:<?php echo $height;?>px;
		min-height:60px;
		max-height:100px !important; 
		background:transparent url(<?php echo DIR_WS_IMAGES . 'logo.gif'?>) no-repeat 15px 10px;
		outline:none; 
		user-select: none; 
		display:block; 
		width:100%;
	}

</style>
<script type="text/javascript" src="js/topmenu.js"></script>
<script type="text/javascript" src="js/yetii.js"></script>


<?php if($mobile === true) { 

echo '<meta name="viewport" content="width=290, initial-scale=1, maximum-scale=1">';

}
?>
</head>
<body>
    <noscript><br><br>
    <h3 style="color:#FF0000">WARNING - YOU WILL BE UNABLE TO USE INTENSECART
      PROPERLY IF YOU DO NOT ENABLE JAVASCRIPT IN YOUR BROWSER. PLEASE <a href="enable-javascript.html">CLICK
      HERE FOR SIMPLE DIRECTIONS ON HOW TO ENABLE IT</a>.</h3>
    </noscript>
    
    
<table width="90%" style="min-width:1001px" border="0" align="center" cellpadding="0" cellspacing="0">
	  <tr>
	   <td style="height:13px;"></td>
	  </tr>
	  <tr>
	   <td><table border="0" cellpadding="0" cellspacing="0" width="100%">
		  <tr>
		   <td style="width:7px; height:831px; background:url(images/ix_d.jpg) repeat-y"></td>
		   <td valign="top"><table border="0" cellpadding="0" cellspacing="0" width="100%">
			  <tr>
			   <td style="height:7px; background:url(images/ix_e.jpg) repeat-x"></td>
			  </tr>
			  <tr>
			   <td style="height:145px;" valign="middle"><table width="100%" cellpadding="0" border="0" cellspacing="0">
                 <tr>
                   
                   <td style="background: transparent url(images/header-bg.jpg) repeat-y right top">
				   <table style="width:100%;" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="90%" >
<a href="/admin/" class="logo"></a>
</td>
    <td style="text-align:right; padding:15px 10px 5px 0;"><div style="background-color:#FFFFDF; width:380px; height:37px; border:1px solid #CCC; padding:4px 10px 5px 0;"><table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td width="35" valign="top"><div style="position:relative;"><div style="position:absolute; top:-9px; left:-9px;"><img src="images/admin_user.png" alt="" width="36" height="48"></div></div></td><td>

<?php 
	$query = tep_db_query("SELECT * FROM `admin_sessions` 
						   WHERE admin_user NOT LIKE 'dbfeed_%'
						   ORDER BY `admin_sessions`.`access_time` DESC 
						   LIMIT 1, 1
						  ");

	echo '<table border="0" cellspacing="0" cellpadding="1" align="right"><tr><td nowrap>
			<b style="color:#7395C3; font:bold 11px arial;">Domain:&nbsp; </b><b style="font:bold 11px arial;">' .preg_replace('/^www\./','',SITE_DOMAIN) . '</b></td>';

	echo '<td rowspan ="2" style="text-align:right; line-height:17px; padding-left:17px;" nowrap><b style="color:#7395C3; font:bold 11px arial;">Last login:&nbsp;</b> <b style="font:bold 11px arial;">';

	if(tep_db_num_rows($query) > 0 ) {
		while ($user = tep_db_fetch_array($query)) {
			echo date('n/j/Y g:ia', strtotime($user['access_time'])-18000);
			echo '</b><br><b style="color:#7395C3">by:&nbsp;</b> <b style="font:bold 11px arial;">' . ($user['admin_user'] == '.intensecart' ? 'Admin' : $user['admin_user']). '</b>';
		}
		tep_db_free_result($query);
	} else {

		echo 'No users found!';
	}

	echo '</td></tr><td nowrap>';

		$logged_in_query = tep_db_query("SELECT admin_user FROM admin_sessions WHERE admin_sessid = '". $_COOKIE['admin_sessid'] ."'");

		$logged_in = (tep_db_num_rows($logged_in_query) > 0 ? tep_db_result(	$logged_in_query,0) : '');

	echo '<b style="color:#7395C3; font:bold 11px arial;">Logged in as:</b> &nbsp;<b style="font:bold 11px arial; text-transform: capitalize;">' . ($logged_in == '.intensecart' ? 'Admin' : $logged_in) . '</b>';
	echo '</td>';
	echo '</tr></table>';
?>

</td></tr></table></div></td>
  </tr>
  <tr>
    <td colspan="2" valign="top">
<div style="position:relative; height:75px;">
	<div style="position:absolute; top:0; left: 0px; width:100%; overflow-x:hidden;">

		<div id="headtabs" class="tabbed">

			<ul id="headtabs-nav" class="tabbed">
				
				<li class="activeli"><a class="active" href="#tab1" onMousedown="loadintoIframe('myframe','orders.php'); sampleAccordion.openPanel(sampleAccordion.getPanels()[1]);"><div class="pos"><div class="pos2"><img src="images/headtab1.png" alt=""></div><div class="pos3">Order Manager</div></div></a></li>

				<li class="democlass"><a class="" href="#tab2" onMousedown="loadintoIframe('myframe','customers.php'); sampleAccordion.openPanel(sampleAccordion.getPanels()[2]);"><div class="pos"><div class="pos2"><img src="images/headtab2.png" alt=""></div><div class="pos3">Customer Manager</div></div></a></li>

				<li class=""><a class="" href="#tab3" onMousedown="loadintoIframe('myframe','stats_products_backordered.php'); sampleAccordion.openPanel(sampleAccordion.getPanels()[3]);"><div class="pos"><div class="pos2"><img src="images/headtab3.png" alt=""></div><div class="pos3">Inventory Manager</div></div></a></li>

				<li class=""><a class="" href="#tab4" onMousedown="loadintoIframe('myframe','seotools/seo-tools.php'); sampleAccordion.openPanel(sampleAccordion.getPanels()[6]);"><div class="pos"><div class="pos2"><img src="images/headtab4.png" alt=""></div><div class="pos3">Marketing Manager</div></div></a></li>

				<li class=""><a class="" href="#tab5" onMousedown="loadintoIframe('myframe','information_manager.php'); sampleAccordion.openPanel(sampleAccordion.getPanels()[7]);"><div class="pos"><div class="pos2"><img src="images/headtab5.png" alt=""></div><div class="pos3">Page Manager</div></div></a></li>

				<li class=""><a class="" href="#tab6" onMousedown="loadintoIframe('myframe','stats_sales_report.php?report=4&amp;startDate=0&amp;endDate=&amp;filter=0001'); sampleAccordion.openPanel(sampleAccordion.getPanels()[5]);"><div class="pos"><div class="pos2"><img src="images/headtab6.png" alt=""></div><div class="pos3">Reports Manager</div></div></a></li>
			</ul>
    
			<div class="tabs-container">
				<div class="tab" id="tab1"></div>	
				<div class="tab" id="tab2"></div>	
				<div class="tab" id="tab3"></div>	
				<div class="tab" id="tab4"></div>
				<div class="tab" id="tab5"></div>
				<div class="tab" id="tab6"></div>
			</div>

		</div>

<script type="text/javascript">
<!-- //
var tabber1 = new Yetii({
id: 'headtabs',
persist: true
});
// -->
</script></div>
</div></td>
  </tr>
</table>

<!--table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="250" valign="top" style="padding-top:10px"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td height="70" style="padding:10px; text-align:center;"><a href="/admin/"><?php echo tep_image(DIR_WS_IMAGES . 'logo.gif',  TITLE, '', ''); ?></a></td>
  </tr>
  <tr>
    <td valign="bottom" style="padding:0 0 5px 0; text-align:center;"><img src="images/livehelp.jpg" width="145" height="54" border="0" onMousedown="loadintoIframe('myframe','../../knowledgebase/index.php')" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[9]);" style="cursor:help" alt=""></td>
  </tr>
</table></td>
    <td align="right" valign="top" style="height:145px; background-image:url(images/header-bg.jpg); background-repeat: repeat-y; background-position: top right;">
	<table border="0" align="right" cellpadding="0" cellspacing="0">
                     <tr>
                       <td style="width:90px; height:145px;"><img src="images/nav1.png" alt="Order Managment" width="90" height="145" hspace="4" border="0" style="cursor:pointer" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[1]);" onMousedown="loadintoIframe('myframe','orders.php')"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav2.png" alt="Customer Manager" width="85" height="145" hspace="4" border="0" style="cursor:pointer" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[2]);" onMousedown="loadintoIframe('myframe','customers.php')"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav3.png" alt="Inventory &amp; Product Manager" width="85" height="145" hspace="4" border="0" style="cursor:pointer" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[3]);" onMousedown="loadintoIframe('myframe','stats_products_backordered.php')"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav4.png" alt="Market Place &amp; Auction Manager" width="85" height="145" hspace="4" border="0" style="cursor:pointer" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[6]);" onMousedown="loadintoIframe('myframe','module_config.php?set=dbfeed')"></td>

                       <td style="width:85px; height:145px;"><img src="images/nav5.png" alt="Search Engine Marketing Manager" width="85" height="145" hspace="4" border="0" style="cursor:pointer" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[6]);" onMousedown="loadintoIframe('myframe','seotools/seo-tools.php')"></td>

<td style="cursor:pointer"><img src="images/nav7.png" alt="Template, Content and File Manager" width="85" height="145" hspace="4" border="0" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[7]);" onMousedown="loadintoIframe('myframe','information_manager.php')"></td>

                       <td style="padding-right:8px"><img src="images/nav6.png" alt="Sales Reports, Trend Charts and Traffic Graphs" width="85" height="145" hspace="2" border="0" style="cursor:pointer" onclick="sampleAccordion.openPanel(sampleAccordion.getPanels()[5]);" onMousedown="loadintoIframe('myframe','stats_sales_report.php?report=4&amp;startDate=0&amp;endDate=&amp;filter=0001');"></td>

</tr>
</table></td>
  </tr>
</table-->
</td>
                 </tr>
               </table></td>
			  </tr>
			  <tr>
			   <td class="breadcrumb-bar" style="height:24px;">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="white-space:nowrap; font: bold 11px tahoma; color:#FFFFFF; padding-left:10px;"><span id="breadcrumb" class="breadcrumblink"></span></td>
    <td width="50" style="font:bold 11px tahoma; color:#FFFFFF; padding-right:10px; text-align:right"><a href="index.php?admin_logout=1" class="breadcrumblink">Logoff</a></td>
  </tr>
</table>
 </td>
			  </tr>
			  <tr>
			   <td style="height:25px; background-color:#EEEFEF"><table width="100%" border="0" cellspacing="0" cellpadding="0" align="left">
  <tr>
    <td align="left" style="padding:0 0 0 5px;"><?php include 'topmenu.php';?></td>
    <td style="width:60px;" align="right"><table width="60" border="0" cellpadding="0" cellspacing="0">
      <tr>
<td width="20"> <a href="javascript:loadintoIframe('myframe','javascript:history.go(-1);');"><img src="images/nav-back-sm.gif" alt="Navigate back" width="12" height="15" border="0"></a></td>
        <td width="20"><a href="javascript:document.getElementById('myframe').contentWindow.location.reload();"><img src="images/nav-reload-sm.gif" alt="Reload data" width="13" height="15" border="0"></a></td>
        <td width="20"><a href="javascript:loadintoIframe('myframe','javascript:history.go(1)
;');"><img src="images/nav-next-sm.gif" alt="Navigate forward" width="12" height="15" border="0"></a></td>
      </tr>
    </table></td>
  </tr>
</table>
</td>
			  </tr>
			  <tr>
			   <td style="height:5px; background:url(images/ix_k.jpg) repeat-x"></td>
			  </tr>
			  <tr>
			   <td><table border="0" cellpadding="0" cellspacing="0" width="100%">
				  <tr>
				   <td style="width:3px; height:662px; background-color:#F1F1F1"></td>
				   <td id="thePanel" align="center" valign="top" style="width:178px; height:618px; background-color:#F1F1F1">
				   <div id="maintab" style="width:178px;"></div></td>
                   <td id="theBar">
<img class="collapsePanel" src="images/pixel_trans.gif" alt="Collapse Panel" width="5" height="117" border="0"></td>
				   <td valign="top" align="left" style="height:618px; padding:0 0 5px 0; background-color:#F0F5FB">
				   <!--script type="text/javascript" src="popcalendar.js"></script-->
				   <iframe id="myframe" name="contentiframe" src="<?=$FramePage?>" scrolling="no" marginwidth="0" marginheight="0" frameborder="0" style="overflow:visible; width:100%;"></iframe> </td>
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
	   <td style="width:773px; height:71px;"><?php include 'footer.php'; ?></td>
	  </tr>
</table>


<div style="display:none">    
				   <div class="Accordion" id="sampleAccordion">
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onclick="loadintoIframe('myframe',Dashboard)" onmouseover="preloadLMenu('Dashboards')">
<table width="176" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/butA-icon.jpg" width="28" height="28" alt=""></td>
    <td style=" padding-top:3px; padding-left:6px; white-space: nowrap;">Performance Dashboard</td>
    <td valign="top" style="padding-left:1px; padding-right:1px; padding-top:1px;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Performance Dashboards</b></font><br>Depending on your permissions, you have access to some very useful dashboards containing bird\'s eye views of your performance indicators.<br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Dashboards')" id="left_menu_Dashboards">
		</div>
	</div>
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onclick="loadintoIframe('myframe','orders.php')" onmouseover="preloadLMenu('Orders')">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but1-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px;white-space: nowrap;">Order Manager</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Order Management</b></font><br>Manage your Incoming Orders and Shipments as well as Returns and Exchanges. Manage your Order exporting into Quickbooks and daily manual batches through your Payment Gateway. Generate Discount Coupon Codes for instant deals.<br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Orders')" id="left_menu_Orders">
			</div>
	</div>
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onclick="loadintoIframe('myframe','customers.php')" onmouseover="preloadLMenu('Customers')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but2-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;">Customer Management</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Customer Manager</b></font><br>Manage your Customers and Vendors. You can create new accounts and well as pricing groups for your vendors and resellers.<br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Customers')" id="left_menu_Customers">
		</div>
	</div>
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onclick="loadintoIframe('myframe','stats_products_backordered.php')" onmouseover="preloadLMenu('Inventory')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but3-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;">Inventory &amp; Products</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Inventory Control</b></font><br>Manage, Add, Edit your Categories and Products. Create new product lines, or edit any existing. Edit or Add product photos, attributes, cross channel marketing and much more. Please review the extensive knowledge base articles regarding Inventory Control and Product Management.<br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Inventory')" id="left_menu_Inventory">
		</div>
	</div>
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onclick="loadintoIframe('myframe','supply_request.php')" onmouseover="preloadLMenu('Suppliers')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but4-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;">Supply Chain Manager</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Supply Control</b></font><br>Create supply requests and manage your suppliers including create, edit and delete.<br><br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Suppliers')" id="left_menu_Suppliers">
		</div>
	</div>
	
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onclick="loadintoIframe('myframe','stats_sales_report.php')" onmouseover="preloadLMenu('Reports')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but5-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;">Reports &amp; Analytics</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Reporting</b></font><br>In-depth sales reporting, traffic statistics and analytics.<br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Reports')" id="left_menu_Reports">
		</div>
	</div>
	
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onmouseover="preloadLMenu('Marketing')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but6-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;">Marketing Console</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Marketing Control Panel</b></font><br>Various utilities such as Email Marketing Manager, Affiliate Manager, SEO Tools including META-Data manager and Campaign Tracker and much more...')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Marketing')" id="left_menu_Marketing">
		</div>
	</div>
	
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onmouseover="preloadLMenu('Design')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but7-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;"> Design &amp; File Manager</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Design &amp; File Manager</b></font><br>Edit your Home Page, or information pages as well as manage files and templates.')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Design')" id="left_menu_Design">
		</div>
	</div>
	<div class="AccordionPanel">
		<div class="AccordionPanelTab" onmouseover="preloadLMenu('Webmail')"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but8-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;">Mailbox Manager </td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Email Account Manager</b></font><br>Add, Modify or Delete email addresses, as well as access your Webmail.')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('Webmail')" id="left_menu_Webmail">
		</div>
	</div>
	

	<!--div class="AccordionPanel">
	<div class="AccordionPanelTab" onclick="loadintoIframe('myframe','../knowledgebase/index.php')"  onmouseover="preloadLMenu('KnowledgeBase')">


<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding-left:3px; padding-top:3px;"><img src="images/but9-icon.jpg" width="28" height="28" alt=""></td>
    <td width="127" style="padding-left:6px; padding-top:3px; white-space: nowrap;">Knowledge Base</td>
    <td valign="top" style="padding-left:3px; padding-right:2px; padding-top:1px; cursor:help;"><img class="tipIcon" src="data:image/gif;base64,R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" width="16" height="16" alt="" onMouseover="ddrivetip('<font class=featuredpopName><b>Knowledge Base</b></font><br>Help Desk &amp; Knowledge Base<br>')" onMouseout="hideddrivetip()"></td>
  </tr>
</table></div>
		<div class="AccordionPanelContent" onExpanderOpenPanel="loadLMenu('KnowledgeBase')" id="left_menu_KnowledgeBase"></div>
	</div-->


</div>
<script language="JavaScript" type="text/javascript">
function LMenuLoadComplete(req,id) {
//  window.alert(id);
  var box=$('left_menu_'+id);
  if (box) {
    box.innerHTML=req.responseText;
    var scrs=box.getElementsByTagName('script');
    for (var i=0;scrs[i];i++) eval(scrs[i].innerHTML);
  }
  LMenuLoaded[id]=true;
  sampleAccordion.openPendingPanel();
}

var LMenuLoaded={};
function loadLMenu(id) {
  if (LMenuLoaded[id]==undefined) {
    LMenuLoaded[id]=false;
    new ajax('index-menu.php?sec='+id,{ onComplete:LMenuLoadComplete, onCompleteArg:id });
  }
  return LMenuLoaded[id];
}

function preloadLMenu(id) {
  for (var i in LMenuLoaded) if (!LMenuLoaded[i]) return true;
  loadLMenu(id);
  return true;
}

var sampleAccordion = new Spry.Widget.Accordion("sampleAccordion",{enableClose:false, panelCookie:'adminMenuPanel', defaultPanel:'<?=isset($_COOKIE['adminMenuPanel'])?$_COOKIE['adminMenuPanel']:0?>'});
</script></div>
    
<script language="JavaScript" type="text/javascript">
  document.getElementById('maintab').appendChild(document.getElementById('sampleAccordion'));
</script>

<script type="text/javascript">
jQuery.noConflict();

var host = window.location.protocol+'//'+ window.location.host;
var url = jQuery('#myframe').attr('src');
var pos = url.indexOf('?');
var query = url.substring(49,pos+15);

jQuery(document).ready(function(){

	jQuery("#theBar").click(function () { 

		if(jQuery('#maintab').css('width') == "178px") {	
				jQuery('#thePanel, #maintab').animate({'width' : '0'}, 400);
				jQuery(".collapsePanel").toggleClass("active");
				resizeCaller();

			// # detect the src= of the iframe and listen for dashboard.php
			// # this is for chart resizing on panel collapse. the other state is below
			if(jQuery('#myframe').attr('src') == host+'/admin/dashboard.php'+ query){
				jQuery("#myframe").contents().find("#chartHeight").animate({height : '325px'});
				jQuery("#myframe").contents().find("body").css('height','+=82');	
				resizeCaller();
			}
	

	    } else {
			jQuery('#thePanel, #maintab').animate({'width' : '178'}, 400);
    	    jQuery(".collapsePanel").removeClass("active");

			//if (jQuery(document).height() > jQuery(window).height()) { 
			//}

			if(jQuery("#myframe").contents().find("body").width() > 800) {
				//jQuery("#myframe").contents().find("body").css('height','+=150');	
				resizeCaller();
			} else {
				resizeCaller();
			}

			setTimeout(function(){resizeIframe('myframe')},500);
	
			if (jQuery('#myframe').attr('src') == host+'/admin/dashboard.php'+ query){
	
				jQuery("#myframe").contents().find("#chartHeight").animate({height : '268px'});	
				jQuery("#myframe").contents().find("body").css('height','-=82');
				setTimeout(function(){resizeIframe('myframe')},300);
			}
		}

    	return false;
	});
});

jQuery(window).resize(function() {
	top.resizeIframe('myframe');
});

</script>
</body>
</html>