<?php

// ############################################
/*  Copyright (c) 2006 - 2016 IntenseCart eCommerce  */
// ############################################


  if (STORE_SESSIONS == 'mysql') {
    //if (!$SESS_LIFE = get_cfg_var('session.gc_maxlifetime')) {
      $SESS_LIFE = 1209600;
    //}

    function _sess_open($save_path, $session_name) {
      return true;
    }

    function _sess_close() {
      return true;
    }

    function _sess_read($key) {
		if(!empty($key) && $key != '') { 
	      $value_query = tep_db_query("SELECT value FROM " . TABLE_SESSIONS . " WHERE sesskey = '" . tep_db_input($key) . "' AND expiry > '" . time() . "'");
    	  $value = tep_db_fetch_array($value_query);
		}

      if (isset($value['value'])) {
        return $value['value'];
      }

      return false;
    }

	function _sess_write($key, $val) {
		global $SESS_LIFE;

		$expiry = time() + $SESS_LIFE;
		$value = $val;

	    $record_session = true;
		$bot_here = false;

		$agent = $_SERVER['HTTP_USER_AGENT'];

		$bots = array("bingbot","bingbot/2.0","Operabot","Nail","ZumBot","Zookabot","ZookaBot","YRSpider","YowedoBot","YoudaoBot","YodaoBot","YioopBot","Yanga","YandexBot","Yandex","YandexBot/3.0","Yahoo! JAPAN","Yahoo!","yacybot","Yaanb","XML Sitemaps Generator","XmarksFetch","wscheck.com","wsAnalyzer","Wotbox","woriobot","Woko","WMCAI_robot","WinWebBot","WillyBot","Willow Internet Crawler","wikiwix-bot","WikioFeedBot","Whoismindbot","WeSEE:Search","WebWatch/Robot_txtChecker","WebRankSpider","WebNL","webmastercoffee","webinatorbot","WebImages","Web-sniffer","WBSearchBot","WatchMouse","WASALive-Bot","voyager","VoilaBot","Visbot","VideoSurf_bot","Vagabondo","Urlfilebot", "Urlbot","urlfan-bot","UptimeRobot","UptimeDog","Updownerbot","UnwindFetchor","UnisterBot","uMBot-FC","UASlinkChecker","Twikle","Twiceler","TwengaBot","TurnitinBot","trendictionbot ","trendictionbot","Touche","Toread-Crawler","Topicbot","TinEye","Thumbshots.ru","thumbshots-de-Bot","ThumbShots-Bot","Thumbnail.CZ robot","Technoratibot","taptubot","Tagoobot","Szukacz","SygolBot","SWEBot","SurveyBot","Surphace Scout","suggybot","Strokebot","Steeler","StatoolsBot","StackRambler","SSLBot","Spinn3r","SpiderLing","Speedy","spbot","Sosospider","SolomonoBot","sogou spider","SniffRSS","Snapbot","smart.apnoti.com Robot","Sitedomain-Bot","sistrix","ShowyouBot","ShopWiki","Shelob","SeznamBot","Setoozbot","Setoozbot ","SEOkicks-Robot","SEOENGBot","SEODat","SeoCheckBot","SemrushBot","Semager","SearchmetricsBot","Search17Bot","search.KumKie.com","ScoutJet","Scooter","Scarlett","SBSearch","SBIder","SanszBot","SAI Crawler","RyzeCrawler","Ruky-Roboter","RSSMicro.com RSS/Atom Feed Robot","Ronzoobot","rogerbot","Robozilla","Robots_Tester","RankurBot","RADaR-Bot","R6 bot","quickobot","QuerySeekerSpider","Qualidator.com Bot","Qseero","Qirina Hurdler","psbot","proximic","ProCogSEOBot","ProCogBot","PostPost","Pompos","pmoz.info ODP link checker","Plukkie","Pixray-Seeker","PiplBot","pingdom.com_bot","percbotspider","Peew","peerindex","Peepowbot","Peeplo Screenshot Bot","ParchBot","PaperLiBot","Panscient web crawler","page_verifier","PagePeeker","Page2RSS","ownCloud Server Crawler","OsObot","OrgbyBot","OpenWebSpider","OpenindexSpider","OpenCalaisSemanticProxy","Ocelli","oBot","Nymesis","nworm","Nutch", "Nutch-2.1", "Nuhk","nodestackbot","NLNZ_IAHarvester2013","Nigma.ru","NextGenSearchBot","Netseer","NetResearchServer","netEstate Crawler","NetcraftSurveyAgent","NerdByNature.Bot","NaverBot","Najdi.si","MSRBOT","MSNBot","msnbot","msnbot-media","Mp3Bot","Motoricerca-Robots.txt-Checker","MojeekBot","moba-crawler","MnoGoSearch","MLBot","MJ12bot","MIA Bot","MetaURI API","MetaURI","Metaspinner/0.01","MetamojiCrawler","MetaJobBot","MetaHeadersBot","MetaGeneratorCrawler","MeMoNewsBot","meanpathbot","Mail.Ru bot","magpie-crawler","livedoor ScreenShot","LinkWalker","linkdex.com","LinkAider","Link Valet Online","Linguee Bot","LinguaBot","Lijit","LexxeBot","LemurWebCrawler","L.webis","KeywordDensityRobot","Karneval-Bot","Kalooga","Jyxobot","JUST-CRAWLER","Job Roboter Spider","JikeSpider","JadynAveBot","IstellaBot","IntegromeDB","Infohelfer","Influencebot","imbot","iCjobs","ichiro","ICC-Crawler","ia_archiver","HubSpot Connect","HuaweiSymantecSpider","HostTracker.com","HomeTags","HolmesBot","Holmes","heritrix","HeartRails_Capture","HatenaScreenshot","Hailoobot","GurujiBot","GrapeshotCrawler","Grahambot","Googlebot","Googlebot-Mobile/2.1","Googlebot/2.1;","gonzo","GOFORITBOT","Girafabot","GingerCrawler","Gigabot","Genieo Web filter","GeliyooBot","GarlikCrawler","Gaisbot","FyberSpider","Fooooo_Web_Video_Crawl","FollowSite Bot","Flocke bot","FlipboardProxy","FlightDeckReportsBot","Flatland Industries Web Spider","firmilybot","findlinks","FeedFinder/bloggz.se","FeedCatBot","FauBot","fastbot crawler","Falconsbot","FairShare","factbot","facebookplatform","FacebookExternalHit","Ezooms","ExB Language Crawler","Exabot","EvriNid","EventGuruBot","Eurobot","EuripBot","Esribot","envolk","emefgebot","EdisterBot","eCairn-Grabber","EasyBib AutoCite","drupact","DripfeedBot","dotSemantic","DotBot","Dot TK - spider","DomainDB","DNS-Digger-Explorer","Dlvr.it/1.0","DKIMRepBot","discoverybot","DealGates Bot","DCPbot","DBLBot","Daumoa","Crowsnest","Crawler4j","Covario-IDS","CorpusCrawler","copyright sheriff","CompSpyBot","coccoc","CloudServerMarketSpider","CligooRobot","cityreview","CirrusExplorer","Charlotte","ChangeDetection","CCBot","CatchBot","Castabot","CareerBot","CamontSpider","Butterfly","Browsershots","BotOnParade","botmobi","bot.wsowner.com","bot-pge.chlooe.com","bnf.fr_bot","BlogPulse","BlinkaCrawler","BLEXBot","Blekkobot","bl.uk_lddc_bot","bixocrawler","biwec","bitlybot","BDFetch","baypup","Baiduspider","Bad-Neighborhood","BacklinkCrawler","BabalooSpider","Automattic Analytics Crawler","Ask Jeeves/Teoma","archive.org_bot","arachnode.net","AraBot","AportWorm","Apercite","AntBot","AMZNKAssocBot","amibot","Amagit.COM","akula","aiHitBot","AhrefsBot","AdsBot-Google","adressendeutschland.de","adidxbot","AddThis","AcoonBot","Accelobot","Abrave Spider","AboutUsBot","Aboundexbot","abby","80legs","50.nu","4seohuntBot","200PleaseBot","192.comAgent","LinkedInBot/1.0", "LinkedInBot","linkdexbot","ProductAdsBot","Twitterbot","TjoosBot","JCE","NerdyBot", "Serf", "Slurp","Insitesbot","ADmantX","Aboundex","AdnormCrawler","TweetmemeBot","applebot","GoogleImageProxy","Gimme60bot","MegaIndex","PageBot","Cliqzbot","ContextAd","CrystalSemanticsBot","linkapediabot","uMBot","Findxbot","linkfluence","Xenu");


		$hacks = array("susu1","susu2","curl","python","file_put_contents","file_exists","x5d.su","r0r.me","Typhoeus");


		foreach ($bots as $bot) {
			if (stripos(strtolower($agent), $bot) !== false) {	
				$bot_here = true;
				//error_log($bot . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' - ' . gethostbyaddr($_SERVER['REMOTE_ADDR']));
			}

		}

		foreach ($hacks as $hack) {
			if (stripos(strtolower($val), $hack) !== false) {	
				$bot_here = true;
				error_log('SCRAPER ALERT! ' . $hack . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' - ' . gethostbyaddr($_SERVER['REMOTE_ADDR']));
			}

		}


		if ($bot_here) { 
			$record_session = false;
		}

		if(!empty($val) || $val != '') { 

			$check_query = tep_db_query("SELECT COUNT(0) AS total FROM " . TABLE_SESSIONS . " WHERE sesskey = '" . tep_db_input($key) . "'");
			$check = tep_db_fetch_array($check_query);

			if ($check['total'] > 0 && $record_session !== false) {

				return tep_db_query("UPDATE " . TABLE_SESSIONS . " 
									 SET expiry = '" . tep_db_input($expiry) . "', 
									 value = '" . tep_db_input($value) . "',
									 ip_address = '".tep_db_input($_SERVER['REMOTE_ADDR'])."'
									 WHERE sesskey = '" . tep_db_input($key) . "'
									");
			} elseif($record_session !== false) {
//error_log($key . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' - ' . gethostbyaddr($_SERVER['REMOTE_ADDR']));
				return tep_db_query("INSERT IGNORE INTO " . TABLE_SESSIONS . " 
									 SET sesskey = '".tep_db_input($key)."',
									 expiry = '".tep_db_input($expiry)."',
									 value = '".tep_db_input($value)."', 
									 ip_address = '".tep_db_input($_SERVER['REMOTE_ADDR'])."'
									");
			}
		}
    }

    function _sess_destroy($key) {
      $val = IXdb::read("SELECT value FROM " . TABLE_SESSIONS ." WHERE sesskey =".IXdb::quote($key),NULL,'value');
      if ($val) IXtracker::disposeSession($sess=unserialize($val));
      return tep_db_query("DELETE FROM  " . TABLE_SESSIONS ." WHERE sesskey =".IXdb::quote($key));
    }


	function _sess_gc($maxlifetime) {

		// # not quite positive why this is writing the sessions to file anyway
		// # condition above is for MYSQL storage of sessions only.
		//$fd = fopen(DIR_FS_SITE.'public_html/tmp/sess-gc/sess-gc','a');
		//fwrite($fd,date('Y-m-d H:i:s').' '.DB_DATABASE."\n");

		$qry = IXdb::query("SELECT sesskey, value FROM " . TABLE_SESSIONS ." WHERE expiry < " . time());

		if(tep_db_num_rows($qry) > 0 ) {

			while ($row = IXdb::fetch($qry)) {

				//fwrite($fd," - ".$row['sesskey']."\n");
	
				if (class_exists('IXtracker')) {
					IXtracker::disposeSession($sess=unserialize($row['value']));
			    }
    
				// # commented out since we disabled above session file writing (fopen) routine.
				//fwrite($fd," =\n");
				IXdb::query("DELETE FROM " . TABLE_SESSIONS ." WHERE sesskey = '" . $row['sesskey'] . "'");
			}
		}
    
		// # commented out since we disabled above session file writing (fwrite) routine.
		//fclose($fd);
		return true;
	}



	session_set_save_handler('_sess_open', '_sess_close', '_sess_read', '_sess_write', '_sess_destroy', '_sess_gc');

} // # END if (STORE_SESSIONS == 'mysql')

  function tep_session_start() {
    return session_start();
  }

	function tep_session_register($variable) {
		global $session_started;

		if ($session_started == true) {

			if (isset($GLOBALS[$variable])) {
				$_SESSION[$variable] =& $GLOBALS[$variable];
			} else {
				$_SESSION[$variable] = null;
			}
		}

		return false;
	}

  function tep_session_is_registered($variable) {
                if (PHP_VERSION < 4.3) {
                  return session_is_registered($variable);
                } else {
                  return isset($_SESSION) && array_key_exists($variable, $_SESSION);
                }
  }

  function tep_session_unregister($variable) {
                if (PHP_VERSION < 4.3) {
                  return session_unregister($variable);
                } else {
                  unset($_SESSION[$variable]);
                }
  }  

  function tep_session_id($sessid = '') {
    if (!empty($sessid)) {
      return session_id($sessid);
    } else {
      return session_id();
    }
  }

  function tep_session_name($name = '') {
    if (!empty($name)) {
      return session_name($name);
    } else {
      return session_name();
    }
  }

  function tep_session_close() {
    if (PHP_VERSION >= '4.0.4') {
      return session_write_close();
    } elseif (function_exists('session_close')) {
      return session_close();
    }
  }

  function tep_session_destroy() {
    return session_destroy();
  }

  function tep_session_save_path($path = '') {
    if (!empty($path)) {
      return session_save_path($path);
    } else {
      return session_save_path();
    }
  }

  function tep_session_recreate() {

      $session_backup = $_SESSION;

      unset($_COOKIE[tep_session_name()]);

      tep_session_destroy();

      if (STORE_SESSIONS == 'mysql') {
        session_set_save_handler('_sess_open', '_sess_close', '_sess_read', '_sess_write', '_sess_destroy', '_sess_gc');
      }

      tep_session_start();

      $_SESSION = $session_backup;
      unset($session_backup);
  }
?>
