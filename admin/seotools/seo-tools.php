<?php $domain = preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);?>
<html>
<head>
<title>SEO Tools</title> 
<link rel="stylesheet" type="text/css" href="../js/iwebtools.css">
<link rel="stylesheet" type="text/css" href="/admin/js/css.css">
<script type="text/javascript" src="../js/gjs.js"></script> 
<script type="text/javascript" src="../js/iwebtools.js"></script>

<style type="text/css"> .nl { border-left-width: 1px; border-right-width: 1px; border-top: 1px solid #2767AD; border-bottom: 1px solid #2767AD"; } td{font-family:arial,sans-serif} td{color:#000} .j{width:34em} </style> 

<script type="text/javascript">
 
  function contentChanged() {
    return top.resizeIframe('myframe');
  }
  
  function rmenuInFrame(event) {
    return top.showMenu(event,'myframe');
  }

  var isSetBreadcrumb;
  function setBreadcrumb(cont) {
    if (isSetBreadcrumb) return true;
    isSetBreadcrumb=1;
    return top.setBreadcrumb(cont);
  }
  
  if (!window.noDefaultBreadcrumb) {
    var titleElement=document.getElementsByTagName('head')[0].getElementsByTagName('title')[0];
    var breadcrumbs=Array({},{});
    if (titleElement) breadcrumbs.push({title:titleElement.innerHTML,link:document.location});
    setBreadcrumb(breadcrumbs);
  }
  
</script>

<style>
a:link, a:visited { font: normal 14px arial; color: #000000; text-decoration: none; }
a:active { font: normal 14px arial; color: #000000; text-decoration: none; }
a:hover { font: normal 14px arial; color: #CC6600; text-decoration: underline; }

body, td, div, span {font: normal 12px arial; color: #666666;}
</style>
</head> 

<body onload="contentChanged(); document.pageform.submit();" style="margin:0; background:transparent;">


<? if (array_key_exists( 'backlinks', $_GET ) ) {
echo '
<!-- Backlink Checker -->
<table border="0" cellpadding="2" width="100%" cellspacing="1">
<tr>
<td> 
<table border="0" cellpadding="2" width="100%" cellspacing="2" height="184">
<tr>
<td width="37"><img border="0" src="../images/backlink_checker.gif"></td>
<td valign="top" width="501"> <font size="5">Backlink Checker<br> </font><font color="#515151"><span style="font-size: 8pt">Find those backlinks linking to you, their Description, Language and Size.</span></font></td>
</tr>
<tr> 
<td valign="top" colspan="2">
<table border="0" cellpadding="0" style="border-collapse: collapse" width="100%">
<tr>
<td height="20" width="26" align="center"> <img src="../images/red_arrow.gif" border="0" onClick="contentChanged();"></td>
<td bgcolor="#F8F8F8" width="95%"> <font style="font-size: 8pt"><div id="bc_text" onclick="contentChanged();"><a href="#" onclick="hs(\'bc\');"><u> How do I use this tool? [+]</u></a></div></font></td>
</tr>
<tr>
<td></td> 
<td>
<div style="display: none;" id="bc">
<table border="1" cellpadding="3" width="100%" style="border-collapse: collapse" bordercolor="#D4D4D4" height="30" cellspacing="3" bgcolor="#EEEEEE">
<tr>
<td valign="middle">
<table border="0" cellspacing="1" style="border-collapse: collapse" width="100%">
<tr>
<td>
<font style="font-size: 8pt"><b>How to use this tool</b><br>1. Enter the exact website address of the page you want to check the backlinks for into the text box. (eg. '. $_SERVER['SERVER_NAME'] . ' or '.$domain.')
<br>
<br>2. Click the "Check!" button
<br>
<br>The results will be shown in a table, 20 results per page will be displayed. 
<br>
<br>Click the <img src="../images/o.gif"> icon to view more details about the website.<br><br></font></td> </tr> <tr> <td height="30" onclick="contentChanged();"> <a href="#" onclick="hs(\'bc\');return false;"> <font style="font-size: 8pt; font-weight: 700"><u>Hide this box</u></font></a></td> </tr> </table></td> </tr> </table> </div></td> </tr> </table> </td> </tr> <tr> <td valign="top" align="center" colspan="2"> 


<form method="get" name="pageform" action="http://www.iwebtool.com/tool/tools/backlink_checker/backlink_checker.php"  target="pageframe" onsubmit="return validate(this);">
<table border="0" style="border-collapse: collapse" width="100%">
<tr>
<td height="91" valign="top">
<table style="border-collapse: collapse" width="100%" height="76" class="tooltop">
<tr>
<td>
<table border="0" width="100%" cellspacing="5">
<tr>
<td height="28"><b><font size="2">Your domain:
</font></b></td>
<td height="28">
<span style="font:bold 14px arial">'.$_SERVER["SERVER_NAME"].'</span>
<input name="domain" type="hidden" value="'.$_SERVER["SERVER_NAME"].'"></td>
<td height="28">
<input type="submit" value="Check!" style="float: left"></td>
</tr>

</table>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td>
<iframe name="pageframe" width="100%" height="680" class="toolbot" frameborder="0">
</iframe></td>
</tr>
</table>
</form>
<script language="JavaScript">
function validate(theform) {
if (theform.domain.value == "") { alert("No domain provided"); return false; }
return true;
}
</script>
<!-- Backlink Checker -->';
} 
elseif (isset($_GET['tool2'])) {
echo 'some other tool 2'; 
} 

elseif (isset($_GET['tool3'])) { 
echo 'some other 3'; 
}
elseif (empty($_GET[''])) {
echo '
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="5">
    <tr>
      <td width="50%" valign="top">
<div style="padding:10px; font:bold 17px arial;">Search Engines</div>
          <table width="100%" cellpadding="0" cellspacing="0" onclick="document.location=\'seo-tools.php?backlinks\'" onmouseover="this.className=\'glow\'" onmouseout="this.className=\'\'" style="border-collapse: collapse;" bordercolor="#ffffff" cellpadding="0" border="1">
<tr>
<td><table width="100%" cellpadding="5" cellspacing="2">
                    
                      <tr>
                        <td width=35><img src="../images/backlink_checker.gif" border="0"></td>
                        <td><a title="Backlink Checker" style="FONT-SIZE: 8pt; COLOR: #002448" href="seo-tools.php?backlinks"><b>Backlink Checker</b></a><br>
                          <font style="FONT-SIZE: 8pt" color=#808080>Find a list of backlinks linking to you.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="window.location=\'google_banned.php\'" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding=5 cellspacing=5>
                    
                      <tr>
                        <td width=35><img src="../images/google_banned.gif"></td>
                        <td><a title="Google Banned Checker Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="google_banned.php"><b>Google
                              Banned Checker</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Discover if your website is banned on Google.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="window.location=\'pagerank_prediction.php\'" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/pagerank_prediction.gif"></td>
                        <td><a 
                        title="PageRank Prediction - Predict Page Rank Predictor" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="pagerank_prediction.php"><b>Google
                              PageRank Prediction</b></a><br>
                          <font 
                        style="FONT-SIZE: 8pt" color=#808080>Predict your future
                          Google PageRank.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" style="BORDER-COLLAPSE: collapse" onclick="window.location=\'keyword_density.php\'" onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/keyword_density.gif"></td>
                        <td><a title="Keyword Density Checker Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="keyword_density.php"><b>Keyword
                              Density Checker</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Discover what keywords appear on your pages.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="window.location=\'keyword_suggestion.php\'" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/keyword_suggestion.gif"></td>
                        <td><a title="Keyword Suggestion Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="keyword_suggestion.php"><b>Keyword
                              Suggestion</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Find related keywords matching your search.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="window.location=\'link_popularity.php\'" onmouseout="this.className=\'\'" 
            bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/link_popularity.gif"></td>
                        <td><a title="Link Popularity Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="link_popularity.php"><b>Link
                              Popularity</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Retrieve your backlinks from search engines.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table><table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="window.location=\'link-popularity-comparison.php\'" onmouseout="this.className=\'\'" 
            bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/link_popularity.gif"></td>
                        <td><a title="Link Popularity Tool" style="FONT-SIZE: 8pt; COLOR: #002448" href="link-popularity-comparison.php"><b>Link Popularity Comparison</b></a><br>
                          <font style="FONT-SIZE: 8pt; color:#808080;">Ccompare
                          your link popularity against competing sites.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/multirank\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img 
src="../images/multirank.gif"></td>
                        <td><a title="Rank Checker" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/multirank"><b>Multi-Rank
                              Checker</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>View your Google PageRank and Alexa Ranking
                          in bulk.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/pagerank_checker\');" onmouseout="this.className=\'\'" 
            bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/pagerank_checker.gif"></td>
                        <td><a title="PageRank Checker" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/pagerank_checker"><b>PageRank
                              Checker</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>View your Google PageRank on differnet
                          Google servers.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/rank\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/rank.gif"></td>
                        <td><a title="Ranking Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/rank"><b>Rank Checker</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Get a overview of your website\'s ranking.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/search_engine_position\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/search_engine_position.gif"></td>
                        <td><a title="Search Engine Position Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/search_engine_position"><b>Search
                              Engine Position</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Locate your search listings on Google and
                          Yahoo!.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/search_listings_preview\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/search_listings_preview.gif"></td>
                        <td><a title="Search Listings Preview" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/search_listings_preview"><b>Search
                              Listings Preview</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Preview your website on Google, MSN and
                          Yahoo! Search.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/spider_view\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img 
                      src="../images/spider_view.gif"></td>
                        <td><a title="Search Engine Spider Simulator" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/spider_view"><b>Spider
                              View</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Discover how spider bots view your website.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/visual_pagerank\');" onmouseout="this.className=\'\'" 
            bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/visual_pagerank.gif"></td>
                        <td><a title="Visual PageRank View" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/visual_pagerank"><b>Visual
                              PageRank</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>View the PageRank of links visually rather
                          than in text.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table cellspacing=0 cellpadding=0 width="100%" border=0>
            
              <tr>
                <td valign=top>&nbsp;</td>
              </tr>
              <!--  <tr>
                <td valign=top height=30><font 
              size=4>Miscellaneous</font></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/anonymous_emailer\');" onmouseout="this.className=\'\'" 
            bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table cellspacing=2 cellpadding=2>
                    
                      <tr>
                        <td width=35><img src="../images/anonymous_emailer.gif"></td>
                        <td><a title="Anonymous Emailer" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/anonymous_emailer"><b>Anonymous
                              Emailer</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Send e-mails to users anonymously.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/link_shortener\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table cellspacing=2 cellpadding=2>
                    
                      <tr>
                        <td width=35><img src="../images/link_shortener.gif"></td>
                        <td><a 
                        title="Link Shortener - Short/Shortcut Link -  Hits Counter" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/link_shortener"><b>Link
                              Shortener</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Make a long web address short and easy
                          to remember.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/md5\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table cellspacing=2 cellpadding=2>
                    
                      <tr>
                        <td width=35><img src="../images/md5.gif"></td>
                        <td><a 
                        title="md5 Encrypt - Converter / Generator - Encryption" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/md5"><b>md5 Encrypt</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Encrypt text to MD5.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/online_calculator\');" onmouseout="this.className=\'\'" 
            bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table cellspacing=2 cellpadding=2>
                    
                      <tr>
                        <td width=35><img src="../images/online_calculator.gif"></td>
                        <td><a 
                        title="Online Calculator - Free Maths Calculating" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/online_calculator"><b>Online
                              Calculator</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>A simple online calculator.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/browser_details\');" onmouseout="this.className=\'\'\'" 
            bordercolor=#ffffff cellpadding=0 width="100%" border=1>
            
              <tr>
                <td><table cellspacing=2 cellpadding=2>
                    
                      <tr>
                        <td width=35><img src="../images/browser_details.gif"></td>
                        <td><a 
                        title="Find my IP Address - Get my Browser Details" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/browser_details"><b>Your
                              Browser Details</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>View your IP address and your browser details.</font></td>
                      </tr>
                    
                </table></td>
              </tr> -->
            
      </table></td>
      <td valign=top width=30>&nbsp;</td>
      <td width="50%" valign=top><div style="padding:10px; font:bold 17px arial;">Domain Checkups</div>
          <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/alexa_traffic_rank\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/alexa_traffic_rank.gif"></td>
                        <td><a title="Alexa Ranking Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/alexa_traffic_rank"><b>Alexa
                              Traffic Rank</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>View and compare Alexa Ranking graphs.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
          </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" 
            onclick="gurl(\'/domain_availability\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/domain_availability.gif"></td>
                        <td><a title="Domain Suggestion Tool - Suggestions" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/domain_availability"><b>Domain
                              Availability</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Check the availability of domains.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/domain_lookup\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/domain_lookup.gif"></td>
                        <td><a 
                        title="Domain Information Look-up (Domain Age, Dmoz Listing)" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/domain_lookup"><b>Domain
                              Look-up</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Retrieve a range of information about a
                          domain.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/whois\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/whois.gif"></td>
                        <td><a title="Domain whois information" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/whois"><b>Domain Whois</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Retrieve domain whois information.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/instant\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/instant.gif"></td>
                        <td><a title="Instant Domain Checker" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/instant"><b>Instant Domain
                              Checker</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Check the availability of domains instantly.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/ping\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img src="../images/ping.gif"></td>
                        <td><a title="Ping Test Tool" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/ping"><b>Ping Test</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Check the presence of an active connection.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
        <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/reverse_ip\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img 
                      src="../images/reverse_ip.gif"></td>
                        <td><a title="Reverse IP Look-up - Reverse Host Lookup" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/reverse_ip"><b>Reverse
                              IP/Look-up</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Resolve a host to an IP address.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
            
        </table>
       <table onmouseover="this.className=\'glow\'" 
            style="BORDER-COLLAPSE: collapse" onclick="gurl(\'/speed_test\');" 
            onmouseout="this.className=\'\'" bordercolor=#ffffff cellpadding=0 
            width="100%" border=1>
            
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    
                      <tr>
                        <td width=35><img 
                      src="../images/speed_test.gif"></td>
                        <td><a title="Website Speed Test" 
                        style="FONT-SIZE: 8pt; COLOR: #002448" 
                        href="http://www.iwebtool.com/speed_test"><b>Website
                              Speed Test</b></a><br>
                          <font style="FONT-SIZE: 8pt" 
                        color=#808080>Find out how fast your website loads.</font></td>
                      </tr>
                    
                </table></td>
              </tr>
        </table>
</td>
    </tr>
</table>';

} ?>
</body> </html> 