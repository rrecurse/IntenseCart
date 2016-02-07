<?
  header('Content-Type: text/javascript');
  define('ADMIN_PERMISSION','ALL');
  include('../includes/application_top.php');
?>
/*
   Deluxe Menu Data File
   Created by Deluxe Tuner v2.0
   http://deluxe-menu.com
*/


// -- Deluxe Tuner Style Names
var itemStylesNames=[];
var menuStylesNames=[];
// -- End of Deluxe Tuner Style Names

//--- Common
var isHorizontal=1;
var smColumns=1;
var smOrientation=0;
var smViewType=0;
var dmRTL=0;
var pressedItem=-2;
var itemCursor="pointer";
var itemTarget="_self";
var statusString="link";
var blankImage="";

//--- Dimensions
var menuWidth="";
var menuHeight="";
var smWidth="";
var smHeight="";

//--- Positioning
var absolutePos=0;
var posX="";
var posY="";
var topDX=1;
var topDY=1;
var DX=-4;
var DY=2;

//--- Font
var fontStyle="normal 11px Trebuchet MS, Tahoma";
var fontColor=["#000000","#FFFFFF"];
var fontDecoration=["none","none"];
var fontColorDisabled="#AAAAAA";

//--- Appearance
var menuBackColor="#EEEFEF";
var menuBackImage="";
var menuBackRepeat="repeat";
var menuBorderColor="";
var menuBorderWidth=0;
var menuBorderStyle="solid";

//--- Item Appearance
var itemBackColor=["#EEEFEF","#0A246A"];
var itemBackImage=["",""];
var itemBorderWidth=0;
var itemBorderColor=["#EEEFEF","#FFFFFF"];
var itemBorderStyle=["none","none"];
var itemSpacing=2;
var itemPadding="3px";
var itemAlignTop="left";
var itemAlign="left";
var subMenuAlign="";

//--- Icons
var iconTopWidth=16;
var iconTopHeight=16;
var iconWidth=16;
var iconHeight=16;
var arrowWidth="";
var arrowHeight="";
var arrowImageMain=["",""];
var arrowImageSub=["",""];

//--- Separators
var separatorImage="";
var separatorWidth="100%";
var separatorHeight="3";
var separatorAlignment="left";
var separatorVImage="";
var separatorVWidth="3";
var separatorVHeight="100%";
var separatorPadding="0px";

//--- Floatable Menu
var floatable=0;
var floatIterations=6;
var floatableX=1;
var floatableY=1;

//--- Movable Menu
var movable=0;
var moveWidth=12;
var moveHeight=20;
var moveColor="#DECA9A";
var moveImage="";
var moveCursor="move";
var smMovable=0;
var closeBtnW=15;
var closeBtnH=15;
var closeBtn="";

//--- Transitional Effects & Filters
var transparency="90";
var transition=3;
var transOptions="";
var transDuration=200;
var transDuration2=200;
var shadowLen=3;
var shadowColor="#B1B1B1";
var shadowTop=0;

//--- CSS Support (CSS-based Menu)
var cssStyle=0;
var cssSubmenu="";
var cssItem=["",""];
var cssItemText=["",""];

//--- Advanced
var dmObjectsCheck=0;
var saveNavigationPath=1;
var showByClick=0;
var noWrap=1;
var pathPrefix_img="";
var pathPrefix_link="";
var smShowPause=200;
var smHidePause=1000;
var smSmartScroll=1;
var smHideOnClick=1;
var dm_writeAll=0;

//--- AJAX-like Technology
var dmAJAX=0;
var dmAJAXCount=0;

//--- Dynamic Menu
var dynamic=0;

//--- Keystrokes Support
var keystrokes=0;
var dm_focus=1;
var dm_actKey=113;


var menuItems = [

<?
  if (AdminPermission('marketing')) {
?>
    ["&nbsp; Marketing &nbsp;","javascript:loadintoIframe('myframe', 'marketing.php')", , , , "_blank", , , , ],
        ["|PPC Ad Manager","javascript:loadintoIframe('myframe', 'apilitax/index.php')", , , , , , , , ],
        ["|Affiliate Manager","javascript:loadintoIframe('myframe', 'marketing.php')", , , , , , , , ],
        ["|Bulk Email Campaigns","", , , , , , , , ],
    ["&nbsp; WebMail &nbsp;","", , , , , , , , ],
        ["|Launch Webmail","javascript:loadintoIframe('myframe','../../mail/index.php');", , , , , , , , ],
        ["|Mailbox Manager","javascript:loadintoIframe('myframe','mailboxes.php');", , , , , , , , ],
<?
  }
?>
    ["&nbsp; Reports &nbsp;","", , , , , , , , ],   
        ["|External Ad Results","javascript:loadintoIframe('myframe', 'stats_ad_results.php')", , , , , , , , ],
        ["|Most Viewed","javascript:loadintoIframe('myframe', 'stats_products_viewed.php')", , , , , , , , ],
        ["|Traffic Statistics","", , , , , , , , ],
        ["|Top Referrers","javascript:loadintoIframe('myframe', 'stats_referral_sources.php');", , , , , , , , ],
    ["&nbsp; Tools &nbsp;","", , , , , , , , ],
        ["|Site Designer","", , , , , , , , ],
            ["||Page Builder","", , , , , , , , ],
            ["||Stylize Modules","", , , , , , , , ],
        ["|Options","", , , , , , , , ],
        ["|Backup Manager","javascript:loadintoIframe('myframe', 'backup.php');", , , , , , , , ],
        ["|Who's Online Now?","javascript:loadintoIframe('myframe', 'whos_online.php');", , , , , , , , ],
        ["|Last 10 Visitors","javascript:loadintoIframe('myframe','supertracker.php?special=last_ten');", , , , , , , , ],
        ["|Configuration","javascript:loadintoIframe('myframe', 'configuration.php?gID=1')", , , , , , , , ],

["||Dashboards","javascript:loadintoIframe('myframe', 'dashboard_control.php');", , , , , , , , ],
["|||Configuration","javascript:loadintoIframe('myframe', 'configuration.php?gID=693');", , , , , , , , ],

          
 ["||Localization","", , , , , , , , ],
["|||Languages","javascript:loadintoIframe('myframe', 'languages.php');", , , , , , , , ],


            
            ["||Maximum Values","javascript:loadintoIframe('myframe', 'configuration.php?gID=3');", , , , , , , , ],
            ["||Minimum Values","javascript:loadintoIframe('myframe', 'configuration.php?gID=2');", , , , , , , , ],
            ["||Payment Modules","javascript:loadintoIframe('myframe', 'payment.php?set=payment')", , , , , , , , ],

            ["||System & Logging","", , , , , , , , ],
                ["|||Cache Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=11');", , , , , , , , ],
                    ["||||Cache Control","javascript:loadintoIframe('myframe', 'cache.php');", , , , , , , , ],
                ["|||GZip Compression","javascript:loadintoIframe('myframe', 'configuration.php?gID=14');", , , , , , , , ],
                ["|||Sessions Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=15');", , , , , , , , ],
                ["|||System Logs","javascript:loadintoIframe('myframe', 'configuration.php?gID=10');", , , , , , , , ],
            ["||System Email","javascript:loadintoIframe('myframe', 'configuration.php?gID=12');", , , , , , , , ],
            ["||Affiliate Program Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=900');", , , , , , , , ],
            ["||Marketing Module Settings","", , , , , , , , ],
                ["|||SEO URLs Control","javascript:loadintoIframe('myframe', 'configuration.php?gID=1130');", , , , , , , , ],
            ["||WYSIWYG Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=112');", , , , , , , , ],
            ["||Domain Control","javascript:loadintoIframe('myframe', 'domain_redirect.php');", , , , , , , , ],
        ["|Role Manager","javascript:loadintoIframe('myframe','admins.php');", , , , , , , , ],
        ["|Referral Source Manager","javascript:loadintoIframe('myframe', 'javascript:void(0);')", , , , , , , , ],
        ["|Pay-Per-Click API User","javascript:loadintoIframe('myframe', 'configuration.php?gID=92')", , , , , , , , ],
];

dm_init();
