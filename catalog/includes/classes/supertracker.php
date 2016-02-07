<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

class supertracker {
  
	function __construct(){
	global $cart, $HTTP_GET_VARS, $customer_id;
		
		if(isset($customer_id)) { 
			$this->customer_id = $customer_id;
		} elseif(isset($_SESSION['customer_id'])) { 
			$this->customer_id = $_SESSION['customer_id'];
		} else {
			$this->customer_id = '';
		}

	}
	
	function update() {

  	global $cart, $HTTP_GET_VARS, $customer_id;
	
   // # Comma Separate List of IPs which should not be recorded, for instance, your own PCs IP address, or that of your server if you are using Cron Jobs, etc

	// # ip's in order are: bingbots, googlebots,
	$excluded_ips = '65.55.24.219,65.55.24.237,65.55.52.96,65.55.24.237,157.55.35.49,157.55.33.20,157.56.93.150,157.55.39.69,157.55.39.70, 207.46.13.16,207.46.13.86,207.46.13.124,157.56.93.49,157.55.36.35,157.55.36.35,157.56.229.87,157.55.33.22,157.55.33.248,157.55.32.77,157.56.93.62,65.55.52.113,65.55.24.233,66.249.66.116,66.249.75.116,132.239.10.67,132.239.17.103';

    $record_session = true;
		$ip_address = $_SERVER['REMOTE_ADDR'];
		
		if(!empty($excluded_ips)) {
			$ex_array = explode(',',$excluded_ips);
			foreach ($ex_array as $key => $ex_ip) {
				//if ($ip_address == $ex_ip) {
				if (preg_match('/$ex_ip/i', $ip_address)) { 
					$record_session = false;
					$bot_here = true;
				}
			}
		}

	// # EXCLUDE BOTS BEGIN - REMOVE THIS LINE IF YOU WANT TO EXCLUDE BOTS FROM THE STATS

	$agent = $_SERVER['HTTP_USER_AGENT'];

	$bot_here = false;

	$arr = array("bingbot","bingbot/2.0","Operabot","Nail","ZumBot","Zookabot","ZookaBot","YRSpider","YowedoBot","YoudaoBot","YodaoBot","YioopBot","Yanga","YandexBot","Yandex","YandexBot/3.0","Yahoo! JAPAN","Yahoo!","yacybot","Yaanb","XML Sitemaps Generator","XmarksFetch","wscheck.com","wsAnalyzer","Wotbox","woriobot","Woko","WMCAI_robot","WinWebBot","WillyBot","Willow Internet Crawler","wikiwix-bot","WikioFeedBot","Whoismindbot","WeSEE:Search","WebWatch/Robot_txtChecker","WebRankSpider","WebNL","webmastercoffee","webinatorbot","WebImages","Web-sniffer","WBSearchBot","WatchMouse","WASALive-Bot","voyager","VoilaBot","Visbot","VideoSurf_bot","Vagabondo","Urlfilebot", "Urlbot","urlfan-bot","UptimeRobot","UptimeDog","Updownerbot","UnwindFetchor","UnisterBot","uMBot-FC","UASlinkChecker","Twikle","Twiceler","TwengaBot","TurnitinBot","trendictionbot ","trendictionbot","Touche","Toread-Crawler","Topicbot","TinEye","Thumbshots.ru","thumbshots-de-Bot","ThumbShots-Bot","Thumbnail.CZ robot","Technoratibot","taptubot","Tagoobot","Szukacz","SygolBot","SWEBot","SurveyBot","Surphace Scout","suggybot","Strokebot","Steeler","StatoolsBot","StackRambler","SSLBot","Spinn3r","SpiderLing","Speedy","spbot","Sosospider","SolomonoBot","sogou spider","SniffRSS","Snapbot","smart.apnoti.com Robot","Sitedomain-Bot","sistrix","ShowyouBot","ShopWiki","Shelob","SeznamBot","Setoozbot","Setoozbot ","SEOkicks-Robot","SEOENGBot","SEODat","SeoCheckBot","SemrushBot","Semager","SearchmetricsBot","Search17Bot","search.KumKie.com","ScoutJet","Scooter","Scarlett","SBSearch","SBIder","SanszBot","SAI Crawler","RyzeCrawler","Ruky-Roboter","RSSMicro.com RSS/Atom Feed Robot","Ronzoobot","rogerbot","Robozilla","Robots_Tester","RankurBot","RADaR-Bot","R6 bot","quickobot","QuerySeekerSpider","Qualidator.com Bot","Qseero","Qirina Hurdler","psbot","proximic","ProCogSEOBot","ProCogBot","PostPost","Pompos","pmoz.info ODP link checker","Plukkie","Pixray-Seeker","PiplBot","pingdom.com_bot","percbotspider","Peew","peerindex","Peepowbot","Peeplo Screenshot Bot","ParchBot","PaperLiBot","Panscient web crawler","page_verifier","PagePeeker","Page2RSS","ownCloud Server Crawler","OsObot","OrgbyBot","OpenWebSpider","OpenindexSpider","OpenCalaisSemanticProxy","Ocelli","oBot","Nymesis","nworm","Nutch", "Nutch-2.1", "Nuhk","nodestackbot","NLNZ_IAHarvester2013","Nigma.ru","NextGenSearchBot","Netseer","NetResearchServer","netEstate Crawler","NetcraftSurveyAgent","NerdByNature.Bot","NaverBot","Najdi.si","MSRBOT","MSNBot","msnbot","msnbot-media","Mp3Bot","Motoricerca-Robots.txt-Checker","MojeekBot","moba-crawler","MnoGoSearch","MLBot","MJ12bot","MIA Bot","MetaURI API","MetaURI","Metaspinner/0.01","MetamojiCrawler","MetaJobBot","MetaHeadersBot","MetaGeneratorCrawler","MeMoNewsBot","meanpathbot","Ru bot","magpie-crawler","livedoor ScreenShot","LinkWalker","linkdex.com","LinkAider","Link Valet Online","Linguee Bot","LinguaBot","Lijit","LexxeBot","LemurWebCrawler","L.webis","KeywordDensityRobot","Karneval-Bot","Kalooga","Jyxobot","JUST-CRAWLER","Job Roboter Spider","JikeSpider","JadynAveBot","IstellaBot","IntegromeDB","Infohelfer","Influencebot","imbot","iCjobs","ichiro","ICC-Crawler","ia_archiver","HubSpot Connect","HuaweiSymantecSpider","HostTracker.com","HomeTags","HolmesBot","Holmes","heritrix","HeartRails_Capture","HatenaScreenshot","Hailoobot","GurujiBot","GrapeshotCrawler","Grahambot","Googlebot","Googlebot-Mobile/2.1","Googlebot/2.1;","gonzo","GOFORITBOT","Girafabot","GingerCrawler","Gigabot","Genieo Web filter","GeliyooBot","GarlikCrawler","Gaisbot","FyberSpider","Fooooo_Web_Video_Crawl","FollowSite Bot","Flocke bot","FlipboardProxy","FlightDeckReportsBot","Flatland Industries Web Spider","firmilybot","findlinks","FeedFinder/bloggz.se","FeedCatBot","FauBot","fastbot crawler","Falconsbot","FairShare","factbot","facebookplatform","FacebookExternalHit","Ezooms","ExB Language Crawler","Exabot","EvriNid","EventGuruBot","Eurobot","EuripBot","Esribot","envolk","emefgebot","EdisterBot","eCairn-Grabber","EasyBib AutoCite","drupact","DripfeedBot","dotSemantic","DotBot","Dot TK - spider","DomainDB","DNS-Digger-Explorer","Dlvr.it/1.0","DKIMRepBot","discoverybot","DealGates Bot","DCPbot","DBLBot","Daumoa","Crowsnest","Crawler4j","Covario-IDS","CorpusCrawler","copyright sheriff","CompSpyBot","coccoc","CloudServerMarketSpider","CligooRobot","cityreview","CirrusExplorer","Charlotte","ChangeDetection","CCBot","CatchBot","Castabot","CareerBot","CamontSpider","Butterfly","Browsershots","BotOnParade","botmobi","bot.wsowner.com","bot-pge.chlooe.com","bnf.fr_bot","BlogPulse","BlinkaCrawler","BLEXBot","Blekkobot","bl.uk_lddc_bot","bixocrawler","biwec","bitlybot","BDFetch","baypup","Baiduspider","Bad-Neighborhood","BacklinkCrawler","BabalooSpider","Automattic Analytics Crawler","Ask Jeeves/Teoma","archive.org_bot","arachnode.net","AraBot","AportWorm","Apercite","AntBot","AMZNKAssocBot","amibot","Amagit.COM","akula","aiHitBot","AhrefsBot","AdsBot-Google","adressendeutschland.de","adidxbot","AddThis","AcoonBot","Accelobot","Abrave Spider","AboutUsBot","Aboundexbot","abby","80legs","50.nu","4seohuntBot","200PleaseBot","192.comAgent","LinkedInBot/1.0", "LinkedInBot","linkdexbot","ProductAdsBot","Twitterbot","Twitterbot/1.0","TjoosBot","JCE","NerdyBot","Insitesbot","ADmantX","Aboundex","AdnormCrawler","ZmEu","YisouSpider","Xenu","WhatWeb", "susu1","susu2","curl","python","file_put_contents","file_exists","x5d.su");

//$firstBot = array_pop($arr);
//$cleanBots = "DELETE FROM supertracker WHERE browser_string LIKE '%".mysql_real_escape_string($firstBot)."%' ";

	foreach ($arr as $bot) {

	  //$cleanBots .= "OR browser_string LIKE '%".mysql_real_escape_string($bot)."%' ";
		if (stripos(strtolower($agent), $bot) !== false) {
			$bot_here = true;
			//error_log($bot);
		}
	}

if ($bot_here) { 
	$record_session = false;
	//tep_db_query($cleanBots . 'LIMIT 10');
}

// # END EXCLUDE BOTS SECTION

	// # Stops us doing anything more if this IP is one of the 
	// # ones we have chosen to exclude
	
    if ($record_session) {		

  		$existing_session = false;
  		$thirty_ago_timestamp = time() - (30*60);
  		$thirty_mins_ago = date('Y-m-d H:i:s', $thirty_ago_timestamp);	
  		$browser_string = addslashes(tep_db_input(urldecode($_SERVER['HTTP_USER_AGENT'])));
			$ip_array = explode ('.',$ip_address);
			$ip_start = $ip_array[0] . '.' . $ip_array[1];			
    
		// # Find out if this user already appears in the supertracker db table	
		// # First thing to try is customer_id, if they are signed in
			
		if (isset($_SESSION['supertracker_id'])) {

    		$query = "SELECT * FROM supertracker WHERE tracking_id ='" . $_SESSION['supertracker_id'] . "'  and last_click > '" . $thirty_mins_ago . "'";
    		$result = tep_db_query($query);
			if (tep_db_num_rows ($result) > 0) $existing_session = true;

		} elseif(!empty($this->customer_id)) {

    		$query = "SELECT * FROM supertracker WHERE customer_id ='" . $this->customer_id . "'  and last_click > '" . $thirty_mins_ago . "'";
    		$result = tep_db_query($query);
			if (tep_db_num_rows ($result) > 0)	$existing_session = true;

		}
			

			// # Next, we try this: compare first 2 parts of the IP address (Class B), and the browser
			// # Identification String, which give us a good chance of locating the details for a given user. I reckon 
			// # that the chances of having more than 1 user within a 30 minute window with identical values
			// # is pretty small, so hopefully this will work and should be more reliable than using Session IDs.
				
  		if (!$existing_session) {			
    		$query = "SELECT browser_string, ip_address, last_click 
						FROM supertracker 
						WHERE browser_string ='".$browser_string."' 
						AND ip_address LIKE '".$ip_start . "%' 
						AND last_click > '".$thirty_mins_ago."'";

    		$result = tep_db_query($query);

  	  		if (tep_db_num_rows ($result) > 0) {
				$existing_session = true;
			}
        
		}
			
		// # If that didn't work, and we have something in the cart, we can use that to try and find the 
		// # record instead. Obviously, people with things in their cart don't just appear from nowhere!
		if(!$existing_session) {
			if ($cart->count_contents()>0) {
				$query = "SELECT * FROM supertracker WHERE cart_total ='" . $cart->show_total() . "'  and last_click > '" . $thirty_mins_ago . "'";
				$result = tep_db_query($query);
				if (tep_db_num_rows ($result) > 0) $existing_session = true;
			}
		}
  
			
	// # Having worked out if we have a new or existing user session lets record some details.

  		if ($existing_session) {
		// # Existing tracked session, so just update relevant existing details

			$tracking_data = tep_db_fetch_array($result);
			$tracking_id = $tracking_data['tracking_id'];	
			$products_viewed = $tracking_data['products_viewed'];
			$added_cart = $tracking_data['added_cart'];
  			$completed_purchase = $tracking_data['completed_purchase'];
  			$num_clicks = $tracking_data['num_clicks']+1;
			$categories_viewed = unserialize($tracking_data['categories_viewed']);	
			$cart_contents = $tracking_data['cart_contents'];
			$cart_total = $tracking_data['cart_total'];				
			$order_id = $tracking_data['order_id'];		

	        if(!empty($this->customer_id)) { 
				$cust_id = $this->customer_id;
			} else { 
				$cust_id = $tracking_data['customer_id'];
			}
			
			$current_page = addslashes(tep_db_prepare_input(urldecode($_SERVER['REQUEST_URI'])));
    		$last_click = date('Y-m-d H:i:s', time());
			
			
			// # Find out if the customer has added something to their cart for the first time
			if (($added_cart!='true') && ($cart->count_contents()>0)) $added_cart = 'true';
 			
			// # Has a purchase just been completed?
  			if ((strstr($current_page, 'checkout_success.php')) && ($completed_purchase != 'true')) {

				$completed_purchase = 'true';

				$order_result = tep_db_query("SELECT orders_id FROM orders WHERE customers_id = '" . $cust_id . "' ORDER BY date_purchased DESC");
		
				if (tep_db_num_rows($order_result) > 0) {
					$order_row = tep_db_fetch_array($order_result);
					$order_id = $order_row['orders_id'];
				}
			}
			
  			// # If customer is looking at a product, add it to the list of products viewed

  			if (isset($_GET['products_id'])) {
				$current_product_id = (int)$_GET['products_id'];
				if (!strstr($products_viewed, '*' . $current_product_id . '?')) {
  			    
				// #Product hasn't been previously recorded as viewed
  				  $products_viewed .= '*' . $current_product_id . '?';	 
				}
  			}
			
  			// # Store away their cart contents
			// # But, the cart is dumped at checkout, so we don't want to overwrite the stored cart contents
			// # In this case
  			 $current_cart_contents = serialize($cart->contents);
				 if (strlen($current_cart_contents)>6) {
				   $cart_contents = $current_cart_contents;
					 $cart_total = $cart->show_total();
				 }
				 
			
			
  			// # If we are on index.php, but looking at category results, make sure we record which category
  			if (strpos($current_page, 'index.php')) {
  			  if (isset($_GET['cPath'])) {
  				  $cat_id = $_GET['cPath'];
  					$cat_id_array = explode('_',$cat_id);
  					$cat_id = $cat_id_array[sizeof($cat_id_array)-1];
  					$categories_viewed[$cat_id]=1;
  				}
  			}

  			$categories_viewed = serialize($categories_viewed);			
  			$query = "UPDATE supertracker set last_click='" . $last_click . "', exit_page='" . $current_page . "', num_clicks='" . $num_clicks . "', added_cart='" . $added_cart . "', categories_viewed='" . $categories_viewed . "', products_viewed='" . $products_viewed . "', customer_id='" . $cust_id . "', completed_purchase='" . $completed_purchase . "', cart_contents='" . $cart_contents . "', cart_total = '" . $cart_total . "', order_id = '" . $order_id . "' where tracking_id='" . $tracking_id . "'";
  		  tep_db_query($query);

	} else {

  		// # New vistor, so record referrer, etc
		// # Next line defines pages on which a new visitor should definitely not be recorded
			 $prohibited_pages = array('/core/px.php', 
									   '/core/login.php',
									   '/core/checkout_shipping.php',
                                       '/core/checkout_payment.php',
                                       '/core/checkout_process.php', 
                                       '/core/checkout_confirmation.php',
                                       '/core/checkout_success.php',
                                       '/core/orders_total.php',
                                       '/core/shipping_options.php',
                                       '/core/checkout_ot.php'
										);

  		 $current_page = addslashes(tep_db_input(urldecode($_SERVER['PHP_SELF'])));

			if(!in_array($current_page, $prohibited_pages)) {

    			$refer_data = addslashes(tep_db_input(urldecode($_SERVER['HTTP_REFERER'])));
    			$refer_data = explode('?', $refer_data);
    			$referrer=$refer_data[0];
    			$query_string=$refer_data[1];
			 
    			$ip = tep_db_input($_SERVER['REMOTE_ADDR']);
  				$browser_string = addslashes(tep_db_input(urldecode($_SERVER['HTTP_USER_AGENT'])));
			 
    		    include(DIR_WS_INCLUDES . "geoip.inc");
		        $gi = geoip_open(DIR_WS_INCLUDES . "GeoIP.dat",GEOIP_STANDARD);	 
        		$country_name = geoip_country_name_by_addr($gi, $ip);
		      	$country_code = strtolower(geoip_country_code_by_addr($gi, $ip));		
        		geoip_close($gi);		  	
		 
				$time_arrived = date('Y-m-d H:i:s', time());

				$landing_page = addslashes(tep_db_prepare_input(urldecode($_SERVER['REQUEST_URI'])));

				$query = "INSERT INTO supertracker 
						  SET ip_address = '".$ip."', 
						  browser_string = '".$browser_string."',
						  country_code = '".$country_code."', 
						  country_name = '".$country_name."',
						  referrer = '".$referrer."',
						  referrer_query_string = '".$query_string."',
						  landing_page = '".$landing_page."',
						  exit_page = '".$current_page."',
						  time_arrived = '".$time_arrived."',
						  last_click = '" . $time_arrived . "'";		 

				tep_db_query($query);


		// # if source is email and the get requests for email and newsletter ID are not empty, continue
		if( (isset($_GET['ref']) && $_GET['ref'] == 'email') && isset($_GET['email']) && isset($_GET['nID'])) {

			if(!tep_session_is_registered('orders_source')) { 
				session_start();
				tep_session_register('email');
				$_SESSION['orders_source'] = 'email';

			} elseif(tep_session_is_registered('orders_source') && $_SESSION['orders_source'] != 'email') {

				tep_session_unregister('orders_source');
				session_start();
				tep_session_register('email');
				$_SESSION['orders_source'] = 'email';

			}

			// # set the session we will use on checkout_process.php to flag this as a conversion for the email
			if(!tep_session_is_registered('nID')) { 
				session_start();
				tep_session_register('nID');
				$_SESSION['nID'] = (int)$_GET['nID'];

			}

			// # insert trackable data into database.
			// # assign some baseline vars for tracking
			$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? tep_db_input($_SERVER['REMOTE_ADDR']) : '';
			$useragent = (!empty($_SERVER['HTTP_USER_AGENT'])) ? tep_db_input($_SERVER['HTTP_USER_AGENT']) : '';
			$email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
			$newletters_id = (int)$_GET['nID'];

;	

			$view_check = tep_db_query("SELECT newsletters_id, email, ip, user_agent
									   FROM ".TABLE_NEWSLETTER_STATS."
									   WHERE newsletters_id = '".$newletters_id."'
									   AND email = '".$email."'
									   AND ip = '".$ip."'
									  ");

			if(tep_db_num_rows($view_check) > 0) { 

				tep_db_query("UPDATE ".TABLE_NEWSLETTER_STATS."
     						  SET click_count = (click_count + 1)
							  WHERE email = '".$email."'
							  AND newsletters_id = ".$newletters_id."
							  AND ip = '".$ip."'
							 ");
			} else { 

				tep_db_query("INSERT INTO ".TABLE_NEWSLETTER_STATS."
        				  	  SET newsletters_id = ".tep_db_input($newletters_id).",
						  	  email = '".$email."',
						  	  last_view = NOW(),
							  ip = '".$ip."',
							  user_agent = '".$useragent."',
							  click_count = (click_count + 1)
						     ");
	
			}

		}


				return $_SESSION['supertracker_id'] = tep_db_insert_id();

			} // # END if for prohibited pages   
   	} // # END else
} // # END (Record Exclusion for certain IPs)
		
 	} // # END function update
} // # END Class

?>