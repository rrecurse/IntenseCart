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
var fontStyle="normal 11px arial";
var fontColor=["#000000","#0033FF"];
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
var itemBackColor=["#EEEFEF","#FFFFFF"];
var itemBackImage=["",""];
var itemBorderWidth=1;
var itemBorderColor=["#EEEFEF","#999999"];
var itemBorderStyle=["solid","dotted"];
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
  if (AdminPermission('orders')) {
?>

["&nbsp;Order Manager &nbsp;","javascript:loadintoIframe('myframe', 'orders.php')", , , , , , , , ],
        ["|Orders & Sales","javascript:loadintoIframe('myframe', 'orders.php')", , , , , , , , ],
        ["|Create New Order","javascript:loadintoIframe('myframe', 'create_order.php')", , , , , , , , ],
        ["|View Returns","javascript:loadintoIframe('myframe','returns.php');", , , , , , , , ],
        ["|Create Return","javascript:loadintoIframe('myframe','return_product.php');", , , , , , , , ],
       
    ["&nbsp; Customers &amp; Accounts &nbsp; ","", , , , , , , , ],
        ["|Create Account","javascript:loadintoIframe('myframe', 'create_account.php')", , , , , , , , ],
        ["|Customer Manager","javascript:loadintoIframe('myframe', 'customers.php')", , , , , , , , ],
        ["|Reseller Manager","javascript:loadintoIframe('myframe', 'customers.php?vendors=1')", , , , , , , , ],
       // ["|Customer &amp; Reseller Reports","javascript:loadintoIframe('myframe', 'javascript:void(0);')", , , , , , , , ],

<?
  }
?>


<?
  if (AdminPermission('inventory')) {
?>

 ["&nbsp; Inventory &amp; Products &nbsp;","", , , , , , , , ],
        ["|Categories &amp; Products","javascript:loadintoIframe('myframe','categories.php');", , , , , , , , ],
        ["|Featured Product Control","javascript:loadintoIframe('myframe','featured.php');", , , , , , , , ],
        ["|Manufacturer Manager","javascript:loadintoIframe('myframe','manufacturers.php');", , , , , , , , ],
        ["|Specials Manager","javascript:loadintoIframe('myframe','specials.php');", , , , , , , , ],
//        ["|Cross-Sell Controller","javascript:loadintoIframe('myframe','xsell.php');", , , , , , , , ],
        ["|Customer Product Reviews","javascript:loadintoIframe('myframe','reviews.php');", , , , , , , , ],
        ["|Product Reports","javascript:loadintoIframe('myframe','stats_sales_report.php');", , , , , , , , ],

<?
  }
?>


<?
  if (AdminPermission('marketing')) {
?>
    ["&nbsp; Marketing Tools &nbsp;","javascript:loadintoIframe('myframe', 'marketing.php')", , , , "_blank", , , , ],
        ["|PPC Ad Manager","javascript:loadintoIframe('myframe', 'apilitax/index.php')", , , , , , , , ],
        ["|Affiliate Manager","javascript:loadintoIframe('myframe', 'affiliate_affiliates.php')", , , , , , , , ],
        ["|Bulk Email Campaigns","javascript:loadintoIframe('myframe', 'newsletters.php')", , , , , , , , ],
        ["|Product Feeds","javascript:loadintoIframe('myframe', 'module_config.php?set=dbfeed')", , , , , , , , ],
   // ["&nbsp; Auction &amp; MarketPlace &nbsp;","", , , , , , , , ],
       // ["|Auctions","javascript:loadintoIframe('myframe', 'marketing.php')", , , , , , , , ],
      //  ["|Product Comparison","javascript:loadintoIframe('myframe', 'marketing.php')", , , , , , , , ],
        // ["|Ebay Auctions","javascript:loadintoIframe('myframe', 'marketing.php')", , , , , , , , ],
       // ["|Ebay Auctions","javascript:loadintoIframe('myframe', 'marketing.php')", , , , , , , , ],
    ["&nbsp; WebMail &nbsp;","", , , , , , , , ],
        ["|Launch Webmail","javascript:loadintoIframe('myframe','../../mail/src/webmail.php');", , , , , , , , ],
        ["|Mailbox Manager","javascript:loadintoIframe('myframe','mailboxes.php');", , , , , , , , ],
<?
  }
?>

<?
  if (AdminPermission('ADMIN')) {
?>

 ["&nbsp; Reports &nbsp;","", , , , , , , , ],
        ["|Best Sellers To-date","javascript:loadintoIframe('myframe', 'stats_products_purchased.php')", , , , , , , , ],
        ["|Daily Product Sales","javascript:loadintoIframe('myframe', 'stats_sales.php?selected_box=reports&by=date')", , , , , , , , ],
        ["|External Ad Results","javascript:loadintoIframe('myframe', 'stats_ad_results.php')", , , , , , , , ],
        ["|Products Viewed","javascript:loadintoIframe('myframe', 'supertracker.php?special=prod_coverage&date_from=<? echo date("n")?>-01-<? echo date("Y")?>&date_to=<? echo date("n-j-Y")?>')", , , , , , , , ],
        ["|Sales Statistics","javascript:loadintoIframe('myframe', 'stats_sales_report.php')", , , , , , , , ],
        ["|Sales Summary","javascript:loadintoIframe('myframe', 'stats_averagesales.php');", , , , , , , , ],
        ["|Top Customers","javascript:loadintoIframe('myframe', 'stats_customers.php')", , , , , , , , ],
        ["|Traffic Statistics","javascript:loadintoIframe('myframe', 'supertracker.php')", , , , , , , , ],
        ["|Top Referrers","javascript:loadintoIframe('myframe', 'stats_referral_sources.php');", , , , , , , , ],
  
  ["&nbsp; Tools &nbsp;","", , , , , , , , ],
          ["|Page Builder","javascript:loadintoIframe('myframe', 'information_manager.php');", , , , , , , , ],
          //  ["||Stylize Modules","javascript:loadintoIframe('myframe', 'javascript:void(0);');", , , , , , , , ],
        ["|Site Keyword Suggest","javascript:loadintoIframe('myframe', 'stats_keywords.php');", , , , , , , , ],
        ["|Backup Manager","javascript:loadintoIframe('myframe', 'backup.php');", , , , , , , , ],
        ["|Who's Online Now?","javascript:loadintoIframe('myframe', 'whos_online.php');", , , , , , , , ],
        ["|Last 10 Visitors","javascript:loadintoIframe('myframe','supertracker.php?special=last_ten');", , , , , , , , ],
        ["|Bulk Catalog Updater","javascript:loadintoIframe('myframe','ez_populate.php');", , , , , , , , ],
        ["|Database Maintenance","javascript:loadintoIframe('myframe','clear_db.php');", , , , , , , , ],
   



 ["&nbsp; Settings &nbsp;","", , , , , , , , ],

    
["|Configuration","", , , , , , , , ],

["||Master Configuration","", , , , , , , , ],
	["|||Store Defaults","javascript:loadintoIframe('myframe', 'configuration.php?gID=1')", , , , , , , , ],
	["|||Maximum Values","javascript:loadintoIframe('myframe', 'configuration.php?gID=3');", , , , , , , , ],
	["|||Minimum Values","javascript:loadintoIframe('myframe', 'configuration.php?gID=2');", , , , , , , , ],
	["|||WYSIWYG Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=112');", , , , , , , , ],




 ["||Catalog & Listings","", , , "Adjust rows/cols & sorting", , , , , ],
 	["|||Catalog Layout","javascript:loadintoIframe('myframe', 'configuration.php?gID=8');", , , , , , , , ],
 	["|||Cross-sell Display Control","javascript:void(0);", , , , , , , , ],
	["||||Cross-sell Channels","javascript:loadintoIframe('myframe', 'xsell_channels.php');", , , , , , , , ],
	["||||Cross-sell Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=307');", , , , , , , , ],
	["|||Featured Product Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=99');", , , , , , , , ],
	["|||Image Size & Control","javascript:loadintoIframe('myframe', 'configuration.php?gID=4');", , , , , , , , ],



["||Checkout Control","", , , , , , , , ],


	["|||Order Status Control","javascript:loadintoIframe('myframe', 'orders_status.php');", , , , , , , , ],
	["|||Stock Conditions","javascript:loadintoIframe('myframe', 'configuration.php?gID=9');", , , , , , , , ],
	["|||Checkout Email Template","javascript:loadintoIframe('myframe', 'email_now.php?email_template_key=checkout_confirm&lng=1');", , , , , , , , ],

["|||Captured Customer Details","javascript:loadintoIframe('myframe', 'configuration.php?gID=5');", , , , , , , , ],
["|||Customer Account Extensions","javascript:loadintoIframe('myframe', 'module_config.php?set=custaccount')", , , , , , , , ],
// ["|||Purchase Without Account","javascript:loadintoIframe('myframe', 'configuration.php?gID=40');", , , , , , , , ],



["||Localization / Tax","", , , , , , , , ],

	["|||Taxes &amp; Zones","", , , , , , , , ],
	["||||Active Tax Zones","javascript:loadintoIframe('myframe', 'geo_zones.php');", , , , , , , , ],
	["||||Tax Classes","javascript:loadintoIframe('myframe', 'tax_classes.php');", , , , , , , , ],
	["||||Tax Rates","javascript:loadintoIframe('myframe', 'tax_rates.php');", , , , , , , , ],
	["||||Country Zones","javascript:loadintoIframe('myframe', 'zones.php');", , , , , , , , ],
	["||||ISO Country Codes","javascript:loadintoIframe('myframe', 'countries.php');", , , , , , , , ],
	["|||Currencies","javascript:loadintoIframe('myframe', 'currencies.php');", , , , , , , , ],
	["|||Languages","javascript:loadintoIframe('myframe', 'languages.php');", , , , , , , , ],


["||Marketing Module Config","", , , , , , , , ],

	["|||Keyword Catcher Control","javascript:loadintoIframe('myframe', 'stats_keywords.php');", , , , , , , , ],
	["|||Advert. Cost Reporting","javascript:loadintoIframe('myframe', 'maintenance_admin.php');", , , , , , , , ],
	["|||Affiliate Program Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=900');", , , , , , , , ],
	["|||Newsletter Settings","", , , , , , , , ],
		["||||Newsletter Admin","javascript:loadintoIframe('myframe', 'newsletters.php');", , , , , , , , ],
		["||||Newsletter Header/Footer","javascript:loadintoIframe('myframe', 'newsletters_extra_infos.php');", , , , , , , , ],
		["||||Newsletter Subscribers","javascript:loadintoIframe('myframe', 'newsletters_subscribers_view.php');", , , , , , , , ],
		["||||Newsletter Defaults","javascript:loadintoIframe('myframe', 'newsletters_extra_default.php');", , , , , , , , ],
	["|||Pay-Per-Click Settings","javascript:loadintoIframe('myframe', 'module_config.php?set=ppc_ads')", , , , , , , , ],
	["|||Conversion Tracking","javascript:loadintoIframe('myframe', 'configuration.php?gID=961')", , , , , , , , ],
	["|||Product Feeds","javascript:loadintoIframe('myframe', 'module_config.php?set=dbfeed');", , , , , , , , ],
	["|||Referral Source Manager","javascript:loadintoIframe('myframe', 'referrals.php')", , , , , , , , ],
	["|||Tell a Friend Settings","javascript:loadintoIframe('myframe', 'email_now.php?email_template_key=tell_a_friend');", , , , , , , , ],
// ["|||SEO URLs Control","javascript:loadintoIframe('myframe', 'configuration.php?gID=1130');", , , , , , , , ],


            

["||Payment Modules","javascript:loadintoIframe('myframe', 'module_config.php?set=checkout')", , , , , , , , ],


            ["||Returns Controller","javascript:loadintoIframe('myframe','returns.php');", , , , , , , , ],
                ["|||Edit Return Reasons","javascript:loadintoIframe('myframe','returns_reasons.php');", , , , , , , , ],
                ["|||Edit Refund Methods","javascript:loadintoIframe('myframe','refund_methods.php');", , , , , , , , ],
                ["|||Edit Return Status","javascript:loadintoIframe('myframe','returns_status.php');", , , , , , , , ],
                ["|||Edit Return Text","javascript:loadintoIframe('myframe','return_text.php');", , , , , , , , ],
                ["|||Return Status Email","javascript:loadintoIframe('myframe','email_now.php?email_template_key=return_notify');", , , , , , , , ],
                ["|||Return Confirmation Email","javascript:loadintoIframe('myframe','email_now.php?email_template_key=return_confirm');", , , , , , , , ],

            ["||Shipping Control","", , , , , , , , ],
                ["|||Shipping Modules","javascript:loadintoIframe('myframe', 'shipping.php?set=shipping')", , , , , , , , ],
                ["|||Packaging & Tracking","javascript:loadintoIframe('myframe', 'configuration.php?gID=7');", , , , , , , , ],
           // ["||System & Logging","", , , , , , , , ],
             //   ["|||Cache Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=11');", , , , , , , , ],
                //    ["||||Cache Control","javascript:loadintoIframe('myframe', 'cache.php');", , , , , , , , ],
               // ["|||GZip Compression","javascript:loadintoIframe('myframe', 'configuration.php?gID=14');", , , , , , , , ],
               // ["|||Sessions Settings","javascript:loadintoIframe('myframe', 'configuration.php?gID=15');", , , , , , , , ],
               // ["|||System Logs","javascript:loadintoIframe('myframe', 'configuration.php?gID=10');", , , , , , , , ],
           // ["||System Email","javascript:loadintoIframe('myframe', 'configuration.php?gID=12');", , , , , , , , ],
            
            



            
            ["||Store Locator Control","", , , , , , , , ],
            ["|||Store Locations","javascript:loadintoIframe('myframe', 'store_locator.php');", , , , , , , , ],
            ["|||Store Types","javascript:loadintoIframe('myframe', 'store_types.php');", , , , , , , , ],
            ["|||Google Map Key","javascript:loadintoIframe('myframe', 'module_config.php?set=geomaps');", , , , , , , , ],

["||Data Synchronizing","", , , , , , , , ],
["|||QuickBooks Setup","javascript:loadintoIframe('myframe', 'qbi_create.php');", , , , , , , , ],
["|||EDI Configuration","javascript:loadintoIframe('myframe', 'configuration.php?gID=693');", , , , , , , , ],
["|||Order Syndicator","javascript:loadintoIframe('myframe', 'module_config.php?set=orderfeed');", , , , , , , , ],
["|||Order Import Profiles","javascript:loadintoIframe('myframe', 'import_orders.php');", , , , , , , , ],

         

        
["|Role Manager","", , , , , , , , ],

["||User Management","javascript:loadintoIframe('myframe','admins.php');", , , , , , , , ],
["||Dash Board Control","javascript:loadintoIframe('myframe', 'dashboard_control.php');", , , , , , , , ],
["|Domain Name Control","javascript:loadintoIframe('myframe', 'domain_redirect.php');", , , , , , , , ],


//["|Master Config","javascript:loadintoIframe('myframe', 'configuration.php?gID=6&cID=90')", , , , , , , , ],



<?
  }
?>


<?
  if (AdminPermission('supervisor')) {
?>

<?
  }
?>


<?
  if (AdminPermission('exec')) {
?>

<?
  }
?>


<?
  if (AdminPermission('supervisor')) {
?>

<?
  }
?>

<?
  if (AdminPermission('sales')) {
?>

<?
  }
?>


   
];
dm_init();
