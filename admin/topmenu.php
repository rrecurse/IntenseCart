<?php
  define('ADMIN_PERMISSION','ALL');
?>

<ul id="nav">

   
<?php
  if (AdminPermission('orders')) {
?>

	<li class="top"><a href="javascript:void(0);" class="top_link"><span class="down">Order Manager</span></a>
	<ul class="sub">
			<li><a href="javascript:loadintoIframe('myframe', 'orders.php');">Orders &amp; Sales</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'create_order.php');">Create New Order</a></li>
			<li><a href="javascript:loadintoIframe('myframe','returns.php');">View Returns</a></li>
			<li><a href="javascript:loadintoIframe('myframe','return_product.php');">Create Return</a></li>
		</ul>	
	</li>

	<li class="top"><a href="javascript:void(0);" class="top_link"><span class="down">Customer Manager</span></a>
	<ul class="sub">
			<li><a href="javascript:loadintoIframe('myframe', 'create_account.php');">Create Account</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'customers.php');">Customer Manager</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'customers.php?vendors=1');">Vendor Manager</a></li>
		</ul>		
	</li>

<?php
  }
?>

<?php
  if (AdminPermission('inventory')) {
?>

<li class="top"><a href="javascript:void(0);" class="top_link"><span class="down">Inventory Manager</span></a>
		<ul class="sub">
			<li><a href="javascript:loadintoIframe('myframe','categories.php');">Category &amp; Products</a></li>
			<li><a href="javascript:loadintoIframe('myframe','featured.php');">Featured Products</a></li>
			<li><a href="javascript:loadintoIframe('myframe','manufacturers.php');">Brands Manager</a></li>
			<li><a href="javascript:loadintoIframe('myframe','specials.php');">Specials Manager</a></li>
			<li><a href="javascript:loadintoIframe('myframe','reviews.php');">Customer Reviews</a></li>
			<li><a href="javascript:loadintoIframe('myframe','stats_sales_report.php');">Product Reports</a></li>
		</ul>
	</li>
<?php
  }
?>

<?php
  if (AdminPermission('marketing')) {
?>
	<li class="top"><a href="javascript:void(0);" class="top_link"><span class="down">Marketing Tools </span></a>
		<ul class="sub">
			<li><a href="javascript:loadintoIframe('myframe','apilitax/index.php');">PPC Ad Manager</a></li>
			<li><a href="javascript:loadintoIframe('myframe','affiliate_affiliates.php');">Affiliate Manager</a></li>
			<li><a href="javascript:loadintoIframe('myframe','newsletters.php');">Email Marketing</a></li>
			<li><a href="javascript:loadintoIframe('myframe','module_config.php?set=dbfeed');">Product Feeds</a></li>
		</ul>	
	</li>

<?php
  }
?>
	<li class="top"><a href="javascript:void(0);" class="top_link"><span class="down">&nbsp;WebMail&nbsp;</span></a>
		<ul class="sub">
			<li><a href="http://e.zwaveproducts.com" target="_blank">Launch Webmail</a></li>
			<!--li><a href="javascript:loadintoIframe('myframe','../../mail/src/webmail.php');">Launch Webmail</a></li>
			<li><a href="javascript:loadintoIframe('myframe','mailboxes.php');">Mailbox Manager</a></li-->

		</ul>
	</li>
	<li class="top"><a href="javascript:void(0);"  class="top_link"><span class="down">Reports</span></a>
		<ul class="sub">
			<li><a href="javascript:loadintoIframe('myframe', 'stats_products_purchased.php');">Best Sellers</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'stats_sales.php?selected_box=reports&amp;by=date');">Daily Product Sales</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'stats_ad_results.php');">External Ad Results</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'supertracker.php?special=prod_coverage&amp;date_from=<?php echo date("n")?>-01-<?php echo date("Y")?>&amp;date_to=<?php echo date("n-j-Y")?>');">Products Viewed</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'returns_report.php?&amp;date_from=01%2F01%2F<?php echo date("Y")?>&amp;date_to=<?php echo date("n\/j\/Y")?>');">Refunds Report</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'stats_sales_report.php');">Sales Statistics</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'stats_averagesales.php');">Sales Summary</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'stats_customers.php');">Top Customers</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'supertracker.php');">Traffic Statistics</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'stats_referral_sources.php');">Top Referrers</a></li>
		</ul>
	</li>
<?php
  if (AdminPermission('ADMIN')) {
?>
	<li class="top"><a href="javascript:void(0);"  class="top_link"><span class="down">&nbsp;Tools &nbsp;</span></a>
		<ul class="sub">
			<li><a href="javascript:loadintoIframe('myframe', 'information_manager.php');">Page Builder</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'stats_keywords.php');">Keyword Suggest</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'backup.php');">Backup Manager</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'whos_online.php');">Who's Online?</a></li>
			<li><a href="javascript:loadintoIframe('myframe','supertracker.php?special=last_ten');">Last 10 Visitors</a></li>
			<li><a href="javascript:loadintoIframe('myframe','ez_populate.php');">Bulk Catalog Update</a></li>
			<li><a href="javascript:loadintoIframe('myframe','clear_db.php');">Maintenance</a></li>
		</ul>
	</li>

	<li class="top"><a href="javascript:void(0);" class="top_link"><span class="down">Settings</span></a>
		<ul class="sub">

<li><a href="javascript:void(0);" class="fly">Configuration</a>
	<ul>
						<li class="mid"><a href="javascript:void(0);" class="fly">Master Config.</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=1');">Store Defaults</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=3');">Maximum Values</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=2');">Minimum Values</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=112');">WYSIWYG Settings</a></li>
							</ul>
						</li>

						<li><a href="javascript:void(0);" class="fly">Catalog Config.</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=8');">Catalog Config.</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'xsell_channels.php');">Cross-sell Channels</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=307');">Cross-sell Config.</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=99');">Featured Config.</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=4');">Images Config.</a></li>	
							</ul>
						</li>

						<li><a href="javascript:void(0);" class="fly">Checkout Control</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe', 'orders_status.php');">Order Status Control</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=9');">Stock Conditions</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'email_now.php?email_template_key=checkout_confirm&amp;lng=1');">
							Checkout Email Template</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=5');">Captured Customer Details</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'module_config.php?set=custaccount');">
							Customer Account Extensions</a></li>	
							</ul>
						</li>

						<li><a href="javascript:void(0);" class="fly">Taxes &amp; Locale</a>
							<ul>

							<li><a href="javascript:void(0);" class="fly">Taxes &amp; Zones</a>
								<ul>
								<li><a href="javascript:loadintoIframe('myframe', 'geo_zones.php');">Active Tax Zones</a></li>	
								<li><a href="javascript:loadintoIframe('myframe', 'tax_classes.php');">Tax Classes</a></li>	
								<li><a href="javascript:loadintoIframe('myframe', 'tax_rates.php');">Tax Rates</a></li>	
								<li><a href="javascript:loadintoIframe('myframe', 'zones.php');">Country Zones</a></li>	
								<li><a href="javascript:loadintoIframe('myframe', 'countries.php');">ISO Country Codes</a></li>	
								</ul>
							</li>
						
							<li><a href="javascript:loadintoIframe('myframe', 'currencies.php');">Currencies</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'languages.php');">Languages</a></li>		
							</ul>
						</li>

						<li><a href="javascript:void(0);" class="fly">Marketing Config.</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe', 'stats_keywords.php');">Keyword Catcher</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'maintenance_admin.php');">Cost Reporting</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=900');">Affiliate Config.</a></li>
	
							<li><a href="javascript:void(0);" class="fly">Newsletter Config.</a>
								<ul>
								<li><a href="javascript:loadintoIframe('myframe', 'newsletters.php');">Newsletter Admin</a></li>
								<li><a href="javascript:loadintoIframe('myframe', 'newsletters_extra_infos.php');">Newsletter Header/Footer</a></li>
								<li><a href="javascript:loadintoIframe('myframe', 'newsletters_subscribers_view.php');">Newsletter Subscribers</a></li>
								<li><a href="javascript:loadintoIframe('myframe', 'newsletters_extra_default.php');">Newsletter Defaults</a></li>
								</ul>
							</li>

							<li><a href="javascript:loadintoIframe('myframe', 'module_config.php?set=ppc_ads');">Pay-Per-Click Settings</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=961');">Conversion Tracking</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'module_config.php?set=dbfeed');">Product Feeds</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'referrals.php');">Referral Source Manager</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'email_now.php?email_template_key=tell_a_friend');">
							Tell Friend Config</a></li>
							</ul>
						</li>

						<li><a href="javascript:loadintoIframe('myframe', 'module_config.php?set=checkout');">Payment Modules</a></li>

						<li><a href="javascript:loadintoIframe('myframe','returns.php');" class="fly">Returns Config.</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe','returns_reasons.php');">Edit Return Reasons</a></li>	
							<li><a href="javascript:loadintoIframe('myframe','refund_methods.php');">Edit Refund Methods</a></li>	
							<li><a href="javascript:loadintoIframe('myframe','returns_status.php');">Edit Return Status</a></li>	
							<li><a href="javascript:loadintoIframe('myframe','return_text.php');">Edit Return Text</a></li>	
							<li><a href="javascript:loadintoIframe('myframe','email_now.php?email_template_key=return_notify');">
							Return Status Email</a></li>	
							<li><a href="javascript:loadintoIframe('myframe','email_now.php?email_template_key=return_confirm');">
							Return Confirm Email</a></li>	
							</ul>
						</li>

						<li><a href="javascript:void(0);" class="fly">Shipping Control</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe', 'shipping.php?set=shipping');">Shipping Modules</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=7');">Packaging &amp; Tracking</a></li>
							</ul>
						</li>

						<li><a href="javascript:void(0);" class="fly">Store Locator</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe', 'store_locator.php');">Locations</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'store_types.php');">Store Types</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'module_config.php?set=geomaps');">Google Map Key</a></li>
							</ul>
						</li>

						<li><a href="javascript:void(0);" class="fly">Data Sync</a>
							<ul>
							<li><a href="javascript:loadintoIframe('myframe', 'qbi_create.php');" class="fly">QuickBooks Setup</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'configuration.php?gID=693');">EDI Configuration</a></li>	
							<li><a href="javascript:loadintoIframe('myframe', 'module_config.php?set=stock');">Stock Feeds</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'module_config.php?set=orderfeed');">Order Syndicator</a></li>
							<li><a href="javascript:loadintoIframe('myframe', 'import_orders.php');">Order Import Profiles</a></li>
							</ul>
						</li>
</ul>
</li>
		
		<li><a href="javascript:void(0);" class="fly">Role Manager</a>
			<ul>
			<li><a href="javascript:loadintoIframe('myframe','admins.php');">User Management</a></li>
			<li><a href="javascript:loadintoIframe('myframe', 'dashboard_control.php');">Dash Board Control</a></li>
			</ul>
		</li>

		<li><a href="javascript:loadintoIframe('myframe', 'domain_redirect.php');">Domain Control</a></li>

	</ul>
</li>
<?php
  }
?>

<?php
  if (AdminPermission('supervisor')) {
?>

<?php
  }
?>


<?php
  if (AdminPermission('exec')) {
?>

<?php
  }
?>


<?php
  if (AdminPermission('supervisor')) {
?>

<?php
  }
?>

<?php
  if (AdminPermission('sales')) {
?>

<?php
  }
?>

</ul>