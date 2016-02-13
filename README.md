IntenseCart eCommerce Platform v1.5.0
=====================================
<img hspace="20" align="right" width="25%" src="http://www.intensecart.com/images/intenseCart-screen1.jpg"> IntenseCart integrates directly with many of the Internet's leading Market Places including Amazon and eBay, Comparison Shopping sites such Google Shopping, Shopzilla and PriceGrabber, Search Engines like Google, and All Major Shipping Carriers including UPS (with integrated WorldShip support!), DHL, FedEx and USPS for real-time rates and tracking. We also offer various Payment Gateways including Paypal (with Direct Payments and Express Checkout!), Google Checkout, GoeMerchant and Authorize.Net. IntenseCart is also integrated with accounting such as QuickBooks and contains powerful Supply Chain capabilities including Order Feeds and Inventory Updating for Order Fulfillment capabilties.


IntenseCart features rich, responsive storefront design capabilities, analytics & reporting capabilities, customer & inventory management, payment & shipment processing, and a full suite of multi-channel internet marketing tools, including API integrations with comparison shopping platforms and marketplaces. Our real-time dashboards & reporting provide critical business intelligence, showcasing key performance segments & trends.

The IntenseCart platform helps you increase revenue and customer retention, while reducing overhead and service costs by allowing you to focus on running your business, not micro-managing online operations. IntenseCart's highly flexible modules provide maximum customizing to snugly fit your business model.

Features
--------

* Real-time, role-based administrative dashboards including Sales and Traffic Analytics.
* Completely search engine friendly. Includes a broad range of SEO tools & competitive analysis tools.
* Real-time Shipping Rates & Tracking, Credit Card Processing, Reporting and much more!
* Order & Customer Management, including manual order entry and editing.
* Powerful imaging & cataloging feature including alternative views, attribute color thumb swapping, "Complete the look" cross-selling and much more.
* Promotion Coupon Codes and Vouchers: Great for seasonal promotions and incentive driven marketing initiatives.
* Seamlessly automate Drop-shipments from your warehouses with our XML or CSV order exporter!
* QuickBooks ready! Seamlessly pass your orders and inventory from your intenseCart admin back into your QB software.
* Easy Product Populate for bulk product additions and updates using a simple spread-sheet saved / CSV format.
* Gift Certificates including optional e-card or generated code batches for physical printing and delivery!
* Multiple Sales Channels & Product Feeds including Amazon, Yahoo Shopping, Shopzilla and many more!
* Affiliate and Sales Agent program control including Multi-Tier commission system & reporting.


B2B solutions for wholesalers/manufacturers
-------------------------------------------
		
* Quantity price break profiles for vendors including quantity structures & price differences using unique logins.
* Our WorldShip XML module will automatically send new orders to your WorldShip software, creating labels with no data entry! Just stick the labels to your boxes!
* Our EDI module provides a powerful integration mechanism into your ERP platform. X12 compliant!
* Purchase Order module for Vendors. Run Open balances with a Net30 recon? This module is ideal for the task.
* Stock Level XML module for integration of remote stock levels. Allows your online store to use real-time stock levels from 3rd party XML compatible systems.


----------
Usage
-----

IntenseCart works by centralizing your IXcore directory for use among your server users. For example, all users with the appropriate symlinks in their /home/[theuser] directories will have the capability to run intenseCart store fronts. Simply build the file structure needed to use IntenseCart. This includes symlinks such as /home/[theuser]/public_html/core/ which would point to your IXcore directory. 

Remember to replace [theuser] with your actual user. ***Example:*** /home/foo/, where foo replaces [theuser]

In this version, we've placed our IXcore directory inside /usr/share/. So your symlink would point to /usr/share/IXcore.

> mkdir /home/[theuser]/public_html

Assuming you've placed the IXcore directory in your /usr/share/ directory:

> cd /home/[theuser]/public_html/

> ln -s /usr/share/IXcore/catalog/ core

You will also need a symlink to the admin:

> mkdir /home/[theuser]/public_html/admin

> cd /home/[theuser]/public_html/admin

> ln -s /usr/share/IXcore/admin/ core

Inside the /usr/share/IXcore/admin/ directory, please create an .htaccess file with the following directives:

```
RewriteEngine on

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^/?(.*)$ /admin/core/$1 [L]
```

And inside the /usr/share/IXcore/catalog/ directory, please create an .htaccess file with the following directives:

```
RewriteEngine on

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^/?(.*)$ /core/$1 [L]
```

Inside the /usr/share/IXcore/admin directory, also create an images directory:

> mkdir /home/[theuser]/public_html/admin/images/

> chown apache:apache /home/[theuser]/public_html/admin/images/

> chmod g+w /home/[theuser]/public_html/admin/images/


Once your core directories are setup, you will need to create the following directories inside /home/[theuser]:

***/cache/*** <br>
***/certs/***<br>
***/conf/*** <br>
***/logs/***

> mkdir /home/[theuser]/cache/

> mkdir /home/[theuser]/conf/

> mkdir  /home/[theuser]/logs/

The directory structure inside /home/[theuser]/ should look like:

**cache**<br>
**certs**<br>
**conf**<br>
**logs**<br>
**public_html**


You must also create the following configuration files:

Inside your /home/[theuser]/cache/ directory, you will need an empty file called: config_cache.php with appropriate write permissions for Apache.

> touch /home/[theuser]/cache/config_cache.php

> chown apache:apache /home/[theuser]/cache/config_cache.php

> chmod g+w /home/[theuser]/cache/


And inside your /conf/ directory, add the following to a file called configure.php

> vi /home/[theuser]/conf/configure.php

Add the following configuration constants:

```
<?
  define('DB_SERVER','localhost');
  define('DB_SERVER_USERNAME','[theDATABASEuser]');
  define('DB_SERVER_PASSWORD',getenv('ixcore_db_password'));
  define('DB_DATABASE','[theDATABASEname]');

?>
```

The ixcore_db_password constant is defined in your Apache's vurtual host as a SetEnv variable. It is then processed using PHP's getenv() function.

The database username and password are what you would set when you setup your database.

Now give the file proper permissions.

> chown [theuser] /home/[theuser]/conf/configure.php

> chmod g+w /home/[theuser]/conf/configure.php


**Important Step - Configuring your virtual host to work with the installation by utilizing the SetEnv function as well as directory structure.**

Your virtual host should resemble:

```
NameVirtualHost [YOURIPADDRESS]:80
<VirtualHost [YOURIPADDRESS]:80>
	ServerName www.YOURDOMAINNAME.com
	ServerAlias YOURDOMAINNAME.com
	DocumentRoot /home/[theuser]/public_html

	SetEnvIfNoCase Request_URI "\.(gif)|(jpg)|(png)|(bmp)|(css)|(js)|(ico)|(swf)|(txt)|(eot)$" dontlog

	ErrorLog /home/[theuser]/logs/error_log
	CustomLog /home/[theuser]/logs/access_log combined env=!dontlog
	
	# Your Database Password
	SetEnv ixcore_db_password [YOURDBPASSWORD]

	<Directory "/home/[theuser]/public_html">
		Options -Indexes +FollowSymLinks
		Order allow,deny
		Allow from all
		AllowOverride All
	</Directory>
</VirtualHost>

NameVirtualHost [YOURIPADDRESS]:443
<VirtualHost [YOURIPADDRESS]:443>
	ServerName *.YOURDOMAINNAME.com
	ServerAlias YOURDOMAINNAME.com
	DocumentRoot /home/[theuser]/public_html

	SetEnvIfNoCase Request_URI "\.(gif)|(jpg)|(png)|(css)|(js)|(ico)|(swf)|(txt)|(eot)$" dontlog
	SetEnvIf Request_URI "^/admin/index-menu\.php$" dontlog
	SetEnvIf Request_URI "^/mod_pagespeed_beacon$" dontlog

	ErrorLog /home/[theuser]/logs/error_log
	CustomLog /home/[theuser]/logs/access_log combined env=!dontlog

	# Your Database Password
	SetEnv ixcore_db_password [YOURDBPASSWORD]

	<Directory "/home/[theuser]/public_html">
		Options -Indexes +FollowSymLinks
		Order allow,deny
		Allow from all
		AllowOverride All
	</Directory>

	SSLEngine on

	SSLCACertificateFile "/home/[theuser]/certs/YOURCERTBUNDLE.crt"
	SSLCertificateFile "/home/[theuser]/certs/YOURCERTNAME.crt"
	SSLCertificateKeyFile "/home/[theuser]/certs/YOURCERTKEYNAME.key"
</VirtualHost>

```

**Remember to replace your database password on the line SetEnv ixcore_db_password [YOURDBPASSWORD]**.


Now that we have IntenseCart configured, we'll need to create a few more front end directories and default files.

Please create the following directories inside /home/[theuser]/public_html/

> mkdir /home/[theuser]/public_html/layout/

> mkdir /home/[theuser]/public_html/layout/templates

> mkdir /home/[theuser]/public_html/images

> mkdir /home/[theuser]/public_html/images/cache

> mkdir /home/[theuser]/public_html/local

> chown -R apache:apache /home/[theuser]/public_html/images /home/[theuser]/public_html/local

> chmod -R g+w /home/[theuser]/public_html/images  /home/[theuser]/public_html/local

And inside /home/[theuser]/public_html/layout/ add a file called index_0.php.html

> touch /home/[theuser]/public_html/layout/index_0.php.html

A home page example file is outlined below. In this case, there were homepage specific features and structure needed, but normally your tables and design elements would be defined in your templates folder inside /layout/

```
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="/layout/css/featured.css" media="screen">
    <!--$headcontent-->
    <div class="wrapper" id="sb-site">
        <div class="header row clearfix">
            <div style="width:auto; height:auto;" class="headTable col-1-1">
                <div style="height:37px">
                    <div class="headlinks_parentDiv"><a href="/" class="headlink">Home</a> &nbsp; | &nbsp; <a href="/create_account.php?apply=vendor" class="headlink">Dealers</a> &nbsp; | &nbsp; <a href="https:/contact_us.php" class="headlink">Contacts</a> &nbsp; | &nbsp; <a href="/sitemap.php" class="headlink">Site Map</a>
                    </div>
                    <div class="shopping_cart_parentDiv">$shopping_cart_popup</div>
                </div>

                <div class="searchtop">
                    <div class="logo-main">
                        <a href=""></a>
                    </div>
                    <div class="search_suggest_parentDiv">$searchsuggestbox</div>
                </div>

                <div class="nav">$inc_header</div>

                <div class="login-header" style="width:auto; height:45px; line-height:45px;">$login_header_text</div>
            </div>
        </div>

        <div class="row clearfix content_body">
            <div id="left" class="leftmenu col-1-3">$inc_left</div>

            <div id="content_div" class="col-2-3">

                <div class="slider">

                    <div class="slide">
                        <a href="/Bundles/-Lighting-Kits/Wireless-Lighting-Control-Three-Way-Dimmer-Kit.html" onclick="onGAPromo('PROMO_DRAGONTECH_3WAY-KIT', 'Dragon Tech  Plus Three-Way Dimmer Kit', 'front-DragonTech-3Way.jpg', 'Home Page slider', 'click');"><img src="/banners/front-DragonTech-3Way.jpg" alt="Dragon Tech  Plus Three-Way Dimmer Kit" border="0" width="100%">
                        </a>
                    </div>

                    <div class="slide">
                        <a href="/Lighting/-Light-Bulbs/Domitech-DTA19-750-27-Plus-Dimmable-Smart-LED-Bulb-60W.html" onclick="onGAPromo('PROMO_DOMITECH_BULB', 'Domitech Smart LED Light Bulb', 'front22.jpg', 'Home Page slider', 'click');"><img src="/banners/front22.jpg" alt="Domitech Smart LED Light Bulb" border="0" width="100%">
                        </a>
                    </div>

                    <div class="slide">
                        <a href="/Bundles/-Lighting-Kits/VeraEdge-and-Aeon-Labs-5-Piece-Lighting-Control-Bundle.html" onclick="onGAPromo('PROMO_VERAEDGE_AEON_5KIT', 'VeraEdge &amp; Aeon Labs 5pc kit promo', 'front18.jpg', 'Home Page slider', 'click');"><img src="/banners/front18.jpg" alt="VeraEdge &amp; Aeon Labs 5pc kit" border="0" width="100%">
                        </a>
                    </div>

                </div>


                <div class="banner-homepage2"></div>

                <div IXclass="blk_listing_featured" IXtemplate="default" IXargs="sort=random,items_per_page=4"></div>

                <br>
                <br>

                <div class="popular-cats">Popular Categories</div>

                <div class="homecatTable">

                    <div class="col-1-2 homecat">
                        <div style="float:left">
                            <span class="homecat-title"> Controllers</span>
                            <br>
                            <a href="http:/Controllers/All-In-One-Gateways.html">All-In-One Zwave Gateways</a>
                            <br>
                            <a href="http:/Controllers/Appliance-Control.html"> Appliance Control</a>
                            <br>
                            <a href="http:/Controllers/Remotes.html"> Remotes</a>
                            <br>
                            <a href="http:/Controllers/Software-Controllers.html"> Software Control</a>
                            <br>
                            <a href="http:/Controllers/Wall-Consoles.html"> Wall Consoles</a>
                            <br>
                            <a href="http:/Controllers/Mobile-Control.html"> Mobile Control</a>

                        </div>
                        <div class="home-cat-img home-cat1">
                            <a href="/Controllers.html"></a>
                        </div>

                    </div>

                    <div class="col-1-2 homecat">

                        <div style="float:left">
                            <span class="homecat-title">Lighting &amp; Appliance </span>
                            <br>
                            <a href="/Lighting/Dimmer-Switches.html"> Dimmer Switches</a>
                            <br>
                            <a href="http:/Lighting/Plugin-Modules.html"> Plugin Modules</a>
                            <br>
                            <a href="/Lighting/-Light-Bulbs.html"> Light Bulbs</a>
                            <br>
                            <a href="/Lighting/Wall-Receptacles.html"> Wall Receptacles</a>
                            <br>
                            <a href="/Lighting/Wall-Switches.html"> Wall Switches</a>

                        </div>
                        <div class="home-cat-img home-cat2">
                            <a href="/Lighting.html"></a>
                        </div>
                    </div>



                    <div class="col-1-2 homecat">

                        <div style="float:left">
                            <span class="homecat-title"> Security </span>
                            <br>
                            <a href="/Security/Motion-Detectors-Sensors.html">Motion Sensors</a>
                            <br>
                            <a href="/Security/Door-Locks.html"> Door Locks</a>
                            <br>
                            <a href="/Security/Monitoring-Gateways.html">Monitoring Gateways</a>
                            <br>
                            <a href="/Security/Security-Cameras.html">Security Cameras</a>
                        </div>
                        <div class="home-cat-img home-cat4">
                            <a href="/Security.html"></a>
                        </div>

                    </div>

                    <div class="col-1-2 homecat">
                        <div style="float:left">
                            <span class="homecat-title"> Kits &amp; Bundles </span>
                            <br>
                            <a href="http:/Bundles/Getting-Started-Kit.html">Getting Started Kits</a>
                            <br>
                            <a href="/Bundles/Lighting-Kits-Starter.html">Lighting Control Kits</a>
                            <br>
                            <a href="/Bundles/Security-Kits-Starter.html">Security Bundles</a>
                            <br>
                            <a href="/Climate-Control.html">Energy Monitoring Kits</a>
                            <br>
                            <a href="/Bundles/-Home-Control-Kits.html">Complete  Systems</a>

                        </div>
                        <div class="home-cat-img home-cat3">
                            <a href="/Lighting.html"></a>
                        </div>

                    </div>
                </div>
                </td>
                </tr>
                </table>


            </div>

        </div>
        <div class="row clearfix">
            <div class="footerBody col-1-1" style="">
                <div style="width:100%; max-width:942px; height:415px; margin:0 auto;" class="footerTable">

                    <div class="footer_topDiv">

                        <div class="footer-logo" style="float:left; display: block; width: 50%; height: 86px; margin: 0 0 60px 0;" title=" Products for the home or business">
                            <div style="text-align:left; font:normal 11px arial; color:#e1e1e1; margin:101px 0 0 0;">Copyrights &copy; 2006 - 2015 - All rights reserved.
                                <div style="color:#666; padding-top:10px;">The trademarks and copyrights are the property of their respective owner.</div>
                            </div>
                        </div>

                        <div class="footer-email" style="float:right">
                            <form style="margin: 0px" name="newsletter" action="/newsletters_subscribe.php" method="post">
                                <table border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="110"></td>
                                        <td><font style="font-size:10px; color:#CCC">First Name:</font>
                                        </td>
                                        <td><font style="padding-left:5px; font-size:10px; color:#CCC">Last Name:</font>
                                        </td>
                                        <td colspan="2"><font style="padding-left:5px; font-size:10px; color:#CCC">Email:</font>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td valign="top">
                                            <input id="firstname" style="border: #666 1px solid; width: 81px; height: 20px" name="firstname">
                                        </td>
                                        <td valign="top" style="padding-left:5px">
                                            <input id="lastname" style="border: #666 1px solid; width: 81px; height: 20px" name="lastname">
                                        </td>
                                        <td valign="top" style="padding-left:5px;">
                                            <input id="Email" style="border: #666 1px solid; width: 81px; height: 20px" name="Email">
                                        </td>
                                        <td valign="top" style="padding-left:5px" class="footer-go">
                                            <input type="submit" value=" " width="32" height="23">
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </div>
                    </div>

                    <div style="float:left; width:100%; color:#FFF; padding:15px 0 15px 15px;">

                        <div class="footer_linkDiv1">
                            <div class="footlink"> Products</div>
                            <div class="footlink"><a href="/Controllers.html" class="footlink" title=" Controllers"> Controllers</a>
                                <br> <a href="/Lighting.html" class="footlink" title=" Lighting"> Lighting</a>
                                <br> <a href="/Security.html" class="footlink" title=" Security"> Security</a>
                                <br> <a href="/Climate-Control.html" class="footlink" title=" Climate Control">Climate Control</a>
                                <br> <a href="/Controllers/Mobile-Control.html" class="footlink" title=" Mobile Control">Mobile Control</a>
                                <br> <a href="/Bundles.html" class="footlink" title=" Bundles"> Bundles</a>
                            </div>
                        </div>

                        <div class="footer_linkDiv2">
                            <div class="footlink">Your Account</div>
                            <div class="footlink"> <a href="/login.php" class="footlink">Sign-In</a>
                                <br> <a href="/account_history.php" class="footlink">Order History</a>
                                <br> <a href="/account_history.php" class="footlink">Order Status</a>
                                <br> <a href="/Shipping-Information.html" class="footlink">Shipping Info</a>
                                <br> <a href="/Returns.html" class="footlink">Return Policy</a>
                            </div>
                        </div>

                        <div class="footer_linkDiv3">
                            <div class="footlink"> Ask The Experts</div>
                            <div class="footlink"> <a href="http:/How-It-Works.html" class="footlink">How  Works</a>
                                <br> <a href="/contact_us.php" class="footlink">Submit a Question</a>
                                <br> <a href="http:/FAQ.html" class="footlink">Questions & Answers </a>
                            </div>
                        </div>

                        <div class="footer_linkDiv4">
                            <div class="footlink">Company Info</div>
                            <div class="footlink"> <a href="/contact_us.php" class="footlink">Contact Us </a>
                                <br> <a href="https://www.zwavepro.com" class="footlink" target="_blank">Dealer Sign-up </a>
                                <br> <a href="/affiliate_summary.php" class="footlink">Affiliate Program</a>
                                <br> <a href="/Satisfaction-Guarantee.html" class="footlink">Our Guarantee</a>
                                <br> <a href="/Privacy-Policy.html" class="footlink">Privacy Policy </a>
                            </div>
                        </div>

                        <div class="footer_membersDiv">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:0 5px 0 15px;">
                                <tr>
                                    <td colspan="3" style="padding:0 0 15px 0; color:#e1e1e1">Proud members of: </td>
                                </tr>
                                <tr>
                                    <td width="33%" align="center">
                                        <div class="spons-zwavealliance" title="Certified "></div>
                                    </td>
                                    <td width="33%" align="center">
                                        <div class="spons-zwave" title="Certified "></div>
                                    </td>
                                    <td align="right" style="padding:0 0 0 5px" class="AuthorizeNet">
                                        <div class="AuthorizeNetSeal" style="width:100%; text-align:center;">
                                            <script type="text/javascript">
                                                var ANS_customer_id = "58154255-7cf3-416c-b8d2-11206d3b7e16"
                                            </script>
                                            <script type="text/javascript" src="//verify.authorize.net/anetseal/seal.js"></script>
                                            <a href="http://www.authorize.net/" id="AuthorizeNetText" target="_blank"></a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" align="right">
                                        <div style="float:right; padding:25px 0 0 0">
                                            <table border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td style="font:normal 11px arial; padding-right:7px; color:#FFF;">We accept:</td>
                                                    <td>
                                                        <div class="footer-weaccept" title="We accept Amex, Via, MasterCard Discover"></div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>


                    <div style="float:left; width:100%; height:67px; line-height:37px; color:#FFF; padding:15px 0 15px 15px;">
                        <a href="/" class="headlink">Home</a>&nbsp; | &nbsp;<a href="https://www.zwavepro.com/" class="headlink">Vendors</a>&nbsp; | &nbsp;<a href="/contact_us.php" class="headlink">Contacts</a>&nbsp; | &nbsp;<a href="/sitemap.php" class="headlink">Site Map</a>
                    </div>


                </div>
                <script>
                    jQuery(document).ready(function($) {
                        jQuery.noConflict();
                        var myheight = jQuery('#content_div').height();
                        if (myheight > 960) {
                            jQuery("#compatible").show();
                            jQuery("#compatible-banner").hide();
                        } else {
                            jQuery("#compatible").hide();
                            jQuery("#compatible-banner").show();
                        }
                    });
                </script>
            </div>
        </div>
    </div>

    <div class="sb-slidebar sb-left sb-width-custom" data-sb-width="30%">
        <div class="slidebar-menu">
            <ul>
                <li><a href="/Controllers.html" class="nav" style="border-top-left-radius:5px !important;">Controllers</a>
                </li>
                <li><a href="/Lighting.html" class="nav"> Lighting</a>
                </li>
                <li><a href="/Security.html" class="nav"> Security</a>
                </li>
                <li><a href="/Climate-Control.html" class="nav">Climate Control</a>
                </li>
                <li><a href="/Bundles.html" class="nav"> Bundles</a>
                </li>
                <li><a href="/-Sales.html" class="nav"> SALES!</a>
                </li>
                <li><a href="/Ask-the-Experts.html" class="nav">Ask the Experts</a>
                </li>
            </ul>
        </div>
    </div>

    <script async src="/js/jquery.bxslider.min.js"></script>
    <script src="/js/slidebars.min.js"></script>

    <script>
        jQuery.noConflict();

        setTimeout(function() {
            jQuery('[id^="oauth2relay"]').hide();
        }, 1500);

        var publishHeight = document.body.offsetHeight;

        jQuery(document).ready(function($) {
            jQuery(window).load(function() {

                // # cuarosel slider
                jQuery('.slider').bxSlider({
                    auto: true,
                    minSlides: 1,
                    maxSlides: 1,
                    slideWidth: 720,
                    slideMargin: 0
                });

                jQuery.slidebars({
                    disableOver: 778,
                    hideControlClasses: true
                });

            });

            publishHeight = document.body.offsetHeight;
            window.onload = function(event) {
                window.setInterval('publishHeight', 300);
            }

        });

        loadfile('/layout/css/slidebars2.css', 'css');
        loadfile('/layout/css/jquery.bxslider.css', 'css');
        loadfile('/layout/css/qview.css', 'css');
        loadfile('/layout/css/xSellprodListings.css', 'css');
    </script>
    </body>

</html>

```

Detailed and advanced configuration options will be made available via the [Wiki](https://github.com/rrecurse/IntenseCart/wiki).

----------
Support
-------------

If you need support, please contact us at [support@intensecart.com](mailto:support@intensecart.com "support@intensecart.com")

----------
Contributions
-------------

We are including this in an open source repository in hopes that you will contribute and help keep the project alive. 

The easiest way to do so is to file bugs and include a test case with your pull requests.