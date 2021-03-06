<?php
ini_set('display_errors', 1);
  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  require(DIR_WS_FUNCTIONS . 'whos_online.php');
  require(DIR_WS_CLASSES.'ipinfodb.class.php');
  $currencies = new currencies();

// Version number text
  $wo_version = '3.6.6.2';
/*
  Configuration Values
    Set these to easily personalize your Whos Online
*/

// Display format for "last refresh time" (12 or 24 hour clock)
  $time_format = 12;
// Seconds that a visitor is considered "active"
  $active_time = 180;
// Seconds before visitor is removed from display
  $track_time = 300;

 // # mjc mike challis wordwrap referer url
  $referer_wordwrap_chars = 150; // <= set to number of characters to wrap to

// Automatic refresh times in seconds and display names
//   Time and Display Text order must match between the arrays
//   "None" is handled separately in the code
  $refresh_time = array(     15,     30,    60,     120,     300,    600 );
  $refresh_display = array( '0:15', '0:30', '1:00', '2:00', '5:00', '10:00' );
  $refresh_values = array();
  $refresh_values[] = array('id' => 'none', 'text' => TEXT_NONE_);
  $refresh_values[] = array('id' => '15', 'text' => '0:15');
  $refresh_values[] = array('id' => '30', 'text' => '0:30');
  $refresh_values[] = array('id' => '60', 'text' => '1:00');
  $refresh_values[] = array('id' => '120', 'text' => '2:00');
  $refresh_values[] = array('id' => '300', 'text' => '5:00');
  $refresh_values[] = array('id' => '600', 'text' => '10:00');

  $show_type = array();
  $show_type[] = array('id' => '', 'text' => TEXT_NONE_);
  $show_type[] = array('id' => 'all', 'text' => TEXT_ALL);
  $show_type[] = array('id' => 'bots', 'text' => TEXT_BOTS);
  $show_type[] = array('id' => 'cust', 'text' => TEXT_CUSTOMERS);

// Images used for status lights
  $status_active_cart = 'icon_status_cart.gif'; // replace word cart with green if you don't want the new icon.
  $status_active_cart_top = 'icon_status_cart_top.gif';
  $status_inactive_cart = 'icon_status_cart_red.gif';
  $status_active_nocart = 'summary_customers.gif';
  $status_inactive_nocart = 'summary_customers_red.gif';
  $status_active_bot = 'icon_status_green_border_light.gif';
  $status_inactive_bot = 'icon_status_red_border_light.gif';

// Text color used for table entries - different colored text for different users
//   Named colors and Hex values should work fine here
  $fg_color_bot = 'maroon';
  $fg_color_admin = 'darkblue';
  $fg_color_guest = 'green';
  $fg_color_account = 'blue'; // '#000000'; // Black
  

//Function to get IP address geolocation data from IPInfoDB and update whos_online table
function updateIps($ips){
	//Old,incesure  method
	//$d = file_get_contents("http://ipinfodb.com/ip_query2.php?ip=$ips");     
		
	//Load the class
	$ipinfodb = new ipinfodb;
	$ipinfodb->setKey('53aebdaa6d47b6bc01bd17c5729b60b0206402d72a496aa840e2cf5f24fb1aab');
	
	//Explode the IP list
	$ipsArr = explode(",",$ips);
	
	if (sizeof($ipsArr) > 0){
		$nb_results = sizeof($ipsArr);
		for ($i=0;$i<$nb_results;$i++){		
			$answer = $ipinfodb->getGeoLocation($ipsArr[$i]);
		
			$ip = mysql_real_escape_string($ipsArr[$i]);
			$country_code = mysql_real_escape_string($answer['CountryCode']);
			$country_name = mysql_real_escape_string($answer['CountryName']);
			$region_name = mysql_real_escape_string($answer['RegionName']);
			$city = mysql_real_escape_string($answer['City']);
			$latitude = mysql_real_escape_string($answer['Latitude']);
			$longitude = mysql_real_escape_string($answer['Longitude']);
			$ip_update_sql = "UPDATE `" . TABLE_WHOS_ONLINE . "` SET `country_code` = '$country_code',`country_name` = '$country_name', `region_name` = '$region_name', `city` = '$city', `latitude` =  '$latitude', `longitude` = '$longitude' WHERE `ip_address` = '$ip'";
			tep_db_query($ip_update_sql);
		}	
	}
}


//Add IP address geolocation to whos_online table
  function add_geolocation(){
    
	$ip_query = tep_db_query("SELECT `ip_address` FROM `" . TABLE_WHOS_ONLINE . "` WHERE `country_code` = '' GROUP BY ip_address");
    while($ip_data = tep_db_fetch_array($ip_query)) $ips[] = $ip_data['ip_address'];
	
	$k = 0;
	$ips_explode = array();
	//explode IP array by 20
	for($i=0;$i<count($ips);$i++){
        if (!(($i+1) % 20)) $k++;
        $ips_explode[$k][] = $ips[$i];
    }
	for($i=0;$i<count($ips_explode);$i++){
		if(count($ips_explode[$i])){
			$ips_cs = implode(",",$ips_explode[$i]);
			updateIps($ips_cs);
		}
	}
  }
  
// Determines status and cart of visitor and displays appropriate icon.
  // mjc mike challis modified next line, added $the_ip for count active guests and customers feature
  function tep_check_cart($customer_id, $session_id, $the_ip) {
    global $status_active_cart, $status_active_cart_top, $status_inactive_cart, $status_active_nocart, $status_inactive_nocart, $status_inactive_bot, $status_active_bot, $active_time;
    // mjc added next line for count active guests and customers without duplicates
    global $ip_addrs_active;

    // Pull Session data from the correct source.

    // First we check if the session exist in a file, else we will use mysql
if ((file_exists(tep_session_save_path() . '/sess_' . $session_id)) && (filesize(tep_session_save_path() . '/sess_' . $session_id) > 0)) {
        $session_data = file(tep_session_save_path() . '/sess_' . $session_id);
        $session_data = trim(implode('', $session_data));
      } else {
      	         $session_data = tep_db_query("select value from " . TABLE_SESSIONS . " WHERE sesskey = '" . $session_id . "'");
                 $session_data = tep_db_fetch_array($session_data);
                 $session_data = trim($session_data['value']);
      }


    // mjc mike challis bof added to fix shopping cart indicator bug
    # the bug was .. When one of the visitors has an item in their cart,
    # every "customer" has the Active with Cart or Inactive with Cart icon blinking.

    $products =0;
    if ($length = strlen($session_data)) {
      #contents";a:0: <= no products in cart
      #contents";a:5: <= 5 products in cart
      preg_match('|contents";a:(\d+):|i',$session_data, $find);
      $products = $find[1];
    }
    // mjc mike challis eof added to fix shopping cart indicator bug

    $which_query = $session_data;
    $who_data =   tep_db_query("SELECT time_entry, time_last_click
                                 FROM " . TABLE_WHOS_ONLINE . "
                                 WHERE session_id='" . $session_id . "'
								 GROUP BY ip_address");
    $who_query = tep_db_fetch_array($who_data);

    // Determine if visitor active/inactive
    $xx_mins_ago_long = (time() - $active_time);

    if($customer_id < 0) {
	// inactive 
      if ($who_query['time_last_click'] < $xx_mins_ago_long) {
        return tep_image(DIR_WS_IMAGES . $status_inactive_bot, TEXT_STATUS_INACTIVE_BOT);
	// active 
      } else {
        return tep_image(DIR_WS_IMAGES . $status_active_bot, TEXT_STATUS_ACTIVE_BOT);
      }
    }	

    // Determine active/inactive and cart/no cart status
    // no cart
    // mjc mike challis modified the next line to fix shopping cart indicator bug
    if ($products == 0 ) {
      // inactive
      if ($who_query['time_last_click'] < $xx_mins_ago_long) {
        return tep_image(DIR_WS_IMAGES . $status_inactive_nocart, TEXT_STATUS_INACTIVE_NOCART);
      // active
      } else {
            // mjc mike challis added next 3 lines for count active guests and customers without duplicates
            if (!in_array($the_ip,$ip_addrs_active)) {
             $the_ip != $_SERVER["REMOTE_ADDR"] and $ip_addrs_active[]=$the_ip;
            }
        return tep_image(DIR_WS_IMAGES . $status_active_nocart, TEXT_STATUS_ACTIVE_NOCART);
      }
    // cart
    } else {
      // inactive
      if ($who_query['time_last_click'] < $xx_mins_ago_long) {
        return tep_image(DIR_WS_IMAGES . $status_inactive_cart, TEXT_STATUS_INACTIVE_CART);
      // active
      } else {
        // mjc mike challis added next 3 lines for count active guests and customers without duplicates
            if (!in_array($the_ip,$ip_addrs_active)) {
             $the_ip != $_SERVER["REMOTE_ADDR"] and $ip_addrs_active[]=$the_ip;
            }
        return tep_image(DIR_WS_IMAGES . $status_active_cart, TEXT_STATUS_ACTIVE_CART);
      }
    }
  }

  /* Display the details about a visitor */
  function display_details($country_code) {
    global $whos_online, $is_bot, $is_admin, $is_guest, $is_account;
    // mjc mike challis added next line for wordwrap
    global $referer_wordwrap_chars;

    // Display Name
	if ($whos_online['full_name'] != 'Guest'){
		echo '<b>' . TABLE_HEADING_FULL_NAME . ':</b> ' . $whos_online['full_name'];
		echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';
	}

    // Display Customer ID for non-bots
    if ( !$is_bot and $whos_online['customer_id'] != 0 ){
      echo '<b>' . TABLE_HEADING_CUSTOMER_ID . ':</b> ' . $whos_online['customer_id'];
      echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';
    }

 

echo '<b>' . TABLE_HEADING_IP_ADDRESS . ':</b> ' . "<a href='http://www.ipinfodb.com/ip_locator.php?ip=$whos_online[ip_address]' target='_new'>" . $whos_online['ip_address'] . "</a>";


    // whois url with variable added in admin    :     echo '<b>' . TABLE_HEADING_IP_ADDRESS . ':</b> ' . "<a href='" . AZER_WHOSONLINE_WHOIS_URL . $whos_online['ip_address'] . "' target='_new'>" . $whos_online['ip_address'] . "</a>";

    echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';

    // Display User Agent
	$user_agent = $whos_online['user_agent'];
	$browser_icon = get_browser_icon($user_agent);
	$os_icon = get_os_icon($user_agent);
	//Angel - Actualización a la versión 3.6.7.2
    //echo '<b>' . TEXT_USER_AGENT . ':</b> ' . $browser_icon . ' ' . $os_icon . ' ' . $user_agent;
	echo '<b>' . TEXT_USER_AGENT . ':</b> ' . $browser_icon . ' ' . $os_icon . '<a href="http://user-agent-string.info/?Fuas=' . $whos_online['user_agent'] . '&test=spamno&action=analyze" target="_new">' . $user_agent . '</a>';
	//
    echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';

	//Geolocation
	//echo '<b>' . TEXT_COUNTRY . ':</b> ' . '<img src="' . DIR_WS_IMAGES . 'flags/' . strtolower($country_code) . '.png" border=0 />' . '&nbsp;' . $whos_online['country_name'];
	echo '<b>' . TEXT_COUNTRY . ':</b> ' . stripslashes($whos_online['country_name']);
    echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';
	echo '<b>' . TEXT_REGION . ':</b> ' . stripslashes($whos_online['region_name']);
    echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';
	echo '<b>' . TEXT_CITY . ':</b> ' . stripslashes($whos_online['city']);
    echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';

    // Display Session ID.  Bots with no Session ID, have it set to their IP address.  Don't display these.
    if ( $whos_online['session_id'] != $whos_online['ip_address'] ) {
      echo '<b>' . TEXT_OSCID . ':</b> ' . $whos_online['session_id'];
      echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';
    }

    // Display Referer if available
    // mjc mike challis wordwrap referer
    if($whos_online['http_referer'] != "" ) {
		$referer_icon = format_referer_url($whos_online['http_referer'], 'icon');
		$pretty_referer_link = format_referer_url(rawurldecode(htmlspecialchars($whos_online['http_referer'])), 'link');
		echo '<b>' . TABLE_HEADING_HTTP_REFERER . ': </b>' . $referer_icon . '<a href="' . htmlspecialchars($whos_online['http_referer']) . '" target="_blank">' . wordwrap($pretty_referer_link, $referer_wordwrap_chars, "<br>", true) . '</a>';
		echo '<br clear="all">' . tep_draw_separator('pixel_trans.gif', '10', '4') . '<br clear="all">';
    }
  }

  // Time to remove old entries
  $xx_mins_ago = (time() - $track_time);

  // remove entries that have expired
  tep_db_query("delete from " . TABLE_WHOS_ONLINE . " where time_last_click < '" . $xx_mins_ago . "'");
  
  //Run the geolocation function
  add_geolocation();
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">

<?php
  // WOL 1.6 - Cleaned up refresh
  // mjc mike challis bof - more standard use of get vars on refresh
  if(  isset($_GET['refresh'])&& is_numeric($_GET['refresh'])  ){  
    echo '<meta http-equiv="refresh" content="' . htmlspecialchars($_GET['refresh']) . ';URL=' . FILENAME_WHOS_ONLINE . '?' . htmlspecialchars($_SERVER["QUERY_STRING"]) . '">';
  }
  // mjc mike challis eof - more standard use of get vars on refresh
  // WOL 1.6 EOF
?>

    <title>Live Visitor Viewer</title>
    <link rel="stylesheet" type="text/css" href="/admin/includes/stylesheet.css">
    <script type="text/javascript" language="javascript" src="/admin/includes/general.js"></script>
    <script type="text/javascript" language="javascript" src="/admin/js/boxover.js"></script>
	<script type="text/javascript" src="js/prototype.lite.js"></script>
	<script type="text/javascript" src="js/expander-list.js"></script>
<style type="text/css">
body {background-color:transparent !important;}
.dataTableHeadingContent{text-align:center;}
</style>
<script type="text/javascript">
  if (window==top) {
    document.cookie="iframe_src_myframe="+document.location;
    document.location="/admin/index.php";
  } else {
// ----------
    if (0 && document.getElementById) {
      if (!document.all) {
        document.addEventListener("mouseup",rmenuInFrame,false);
      } else {
	document.attachEvent("oncontextmenu",rmenuInFrame); 
	document.attachEvent("onclick",rmenuInFrame);
      }
      document.oncontextmenu=new Function("return false") ;
    }
  }
  
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

	<?php
	if ($_GET['map'] != 'show'){
	?>
	</head>
	<body>
	<?php
	} else {
	?>
<!-- Mimic Internet Explorer 7 -->
  	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" >
	<?php echo '<script src="//maps.google.com/maps?file=api&amp;v=2&amp;key=' . GOOGLE_MAPS_KEY . '" type="text/javascript"></script>'; ?>
    <script type="text/javascript">
    var map;
	var geoXml;
	var toggleState = 1;
	
	function initialize() {
	  if (GBrowserIsCompatible()) {
	  	//Angel - Arreglo para que salgan las burbujas en el Mapa del Mundo
		//geoXml = new GGeoXml("<?php //echo HTTP_CATALOG_SERVER . "/visitors_georss.php";?>");
		geoXml = new GGeoXml("<?php echo HTTP_CATALOG_SERVER . DIR_WS_CATALOG . "visitors_georss.php";?>");
		map = new GMap2(document.getElementById("map_canvas"));
		map.setCenter(new GLatLng(36,2), 1); 
		map.addControl(new GLargeMapControl());
		map.addControl(new GLargeMapControl());
		map.addOverlay(geoXml);
	  }
	}
    </script>
	
  </head>
  <body onLoad="initialize()" onUnload="GUnload()">
  <?php
	}
	?>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>



    <table border="0" width="100%" cellspacing="2" cellpadding="2">
      <tr>
        <td width="100%" valign="top">
          <table border="0" width="100%" cellspacing="0" cellpadding="2">
            <tr>
              <td>
                <table border="0" width="100%" cellspacing="0" cellpadding="0">
                  <tr>
					<td class="pageHeading"><?php echo HEADING_TITLE ?></td>
                    <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td align="left" valign="middle">

                <!-- Display Profile links -->
 
                <table border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td>
                      <?php 
                        echo tep_draw_form('update', FILENAME_WHOS_ONLINE, '', 'get');
                          if (isset($_GET['info'])) {echo tep_draw_hidden_field('info', $_GET['info']);}
                          echo tep_draw_hidden_field(tep_session_name(), tep_session_id());
                      ?>
                        <table border="0" cellspacing="4" cellpadding="0">
                          <tr>
                            <td class="smallText" align="left">
                              <?php echo TEXT_SET_REFRESH_RATE . ': ' ?>
                            </td>
                            <td class="smallText" align="left">
                              <?php echo tep_draw_pull_down_menu('refresh', $refresh_values, $_GET['refresh'], 'onChange="this.form.submit();"') . '<br>'; ?>
                            </td>
                          </tr>
                          <tr>
                            <td class="smallText" align="left">
                              <?php echo TEXT_PROFILE_DISPLAY . ': ' ?>
                            </td>
                            <td class="smallText" align="left">
                              <?php echo tep_draw_pull_down_menu('show', $show_type, $_GET['show'], 'onChange="this.form.submit();"') . '<br>'; ?>
                            </td>
                          </tr>
                          <tr>
                            <td class="smallText" align="left">
                              <?php echo TEXT_SHOW_BOTS . ': ' ?>
                            </td>
                            <td class="smallText" align="left">
                              <?php echo '<input type="checkbox" name="bots" value="show" onclick="this.form.submit()"' . ($_GET['bots'] == 'show' ? ' checked="checked"': '') . '>';  ?>
                            </td>
                          </tr>
                          <tr>
                            <td class="smallText" align="left">
                              <?php echo TEXT_SHOW_MAP . ': ' ?>
                            </td>
                            <td class="smallText" align="left">
                              <?php echo '<input type="checkbox" name="map" value="show" onclick="this.form.submit()"' . ($_GET['map'] == 'show' ? ' checked="checked"': '') . '>';  ?>
                            </td>
                          </tr>
                        </table>
                      </form>
                    </td>
                  </tr>
                </table>          
              </td>

              <!-- Status Legend - Uses variables for image names -->
              <td align="right" class="smallText" valign="middle">
<div style="height:50px; width:260px; background-color:#FFF; border: 1px solid #CCC; padding:10px 10px 0 10px;">
                <table border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td class="smallText"><?php echo
                      tep_image(DIR_WS_IMAGES . $status_active_cart_top, TEXT_STATUS_ACTIVE_CART) . '&nbsp;' . TEXT_STATUS_ACTIVE_CART . '&nbsp;&nbsp;';?>
                    </td>
     	              <td class="smallText"><?php echo
                      tep_image(DIR_WS_IMAGES . $status_inactive_cart, TEXT_STATUS_INACTIVE_CART) . '&nbsp;' . TEXT_STATUS_INACTIVE_CART . '&nbsp;&nbsp;';?>
                    </td>
                  </tr>
                  <tr>
                    <td class="smallText"><?php echo
                      tep_image(DIR_WS_IMAGES . $status_active_nocart, TEXT_STATUS_ACTIVE_NOCART) . '&nbsp;' . TEXT_STATUS_ACTIVE_NOCART   .'&nbsp;&nbsp;';?>
                    </td>
     	              <td class="smallText"><?php echo
                      tep_image(DIR_WS_IMAGES . $status_inactive_nocart, TEXT_STATUS_INACTIVE_NOCART) . '&nbsp;' . TEXT_STATUS_INACTIVE_NOCART   . '&nbsp;&nbsp;';?>
                    </td>
                  </tr>
                  <tr>
                    <td class="smallText"><?php echo
                      tep_image(DIR_WS_IMAGES . $status_active_bot, TEXT_STATUS_ACTIVE_BOT) . '&nbsp;' . TEXT_STATUS_ACTIVE_BOT . '&nbsp;&nbsp;';?>
                    </td>
     	              <td class="smallText"><?php echo
                      tep_image(DIR_WS_IMAGES . $status_inactive_bot, TEXT_STATUS_INACTIVE_BOT) . '&nbsp;' . TEXT_STATUS_INACTIVE_BOT . '&nbsp;&nbsp;';?>
                    </td>
                  </tr>
                </table>
</div>
<table>
<tr>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text' => '<b>' . TABLE_HEADING_SHOPPING_CART . '</b>');
  // mjc mchallis modified next line to get $info from get vars
  if (isset($_GET['info'])) {
	$info = $_GET['info'];
  	
// First we check if the session exist in a file, else we will use mysql
if ( (file_exists(tep_session_save_path() . '/sess_' . $info)) && (filesize(tep_session_save_path() . '/sess_' . $info) > 0) ) {
        $session_data = file(tep_session_save_path() . '/sess_' . $info);
        $session_data = trim(implode('', $session_data));
      } else {
      $session_data = tep_db_query("select value from " . TABLE_SESSIONS . " WHERE sesskey = '" . $info . "'");
      $session_data = tep_db_fetch_array($session_data);
      $session_data = trim($session_data['value']);
    }
    
    if ($length = strlen($session_data)) {

        $start_cart = strpos($session_data, 'cart|O');
        $start_currency = strpos($session_data, 'currency|s');

      // if we found the 'cart' tag in the session data
      // workaround for timeout when suhosin session data encryption is in effect
      if ($start_cart !== false) {
        for ($i=$start_cart; $i<$length; $i++) {
          if ($session_data[$i] == '{') {
            if (isset($tag)) {
              $tag++;
            } else {
              $tag = 1;
            }
          } elseif ($session_data[$i] == '}') {
            $tag--;
          } elseif ( (isset($tag)) && ($tag < 1) ) {
            break;
          }
        } // end for loop

        $session_data_cart = substr($session_data, $start_cart, $i);
        $session_data_currency = substr($session_data, $start_currency, (strpos($session_data, ';', $start_currency) - $start_currency + 1));

        session_decode($session_data_cart);
        session_decode($session_data_currency); 
        $cart = $_SESSION['cart'];
        $currency = $_SESSION['currency'];

        if (is_object($cart)) {
		$products = $cart->get_products();

          for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
            $contents[] = array('text' => $products[$i]['quantity'] . ' x ' . $products[$i]['name']);
          }
          if (sizeof($products) > 0) {
            $contents[] = array('text' => tep_draw_separator('pixel_black.gif', '100%', '1'));
            $contents[] = array('align' => 'right', 'text' => TEXT_SHOPPING_CART_SUBTOTAL . ' ' . $currencies->format($cart->show_total(), true, $currency));
          } else {
            $contents[] = array('text' => '<i>' . TEXT_EMPTY . '</i>');
          }
        }
      }
    }
  }  
     // Show shopping cart contents for selected entry
?>
                    <td valign="top">
 <?php
if(!empty($contents)) {
  $box = new box;
   echo $box->infoBox($heading, $contents);
}
?>
                    </td>
</tr>
</table>
              </td>
            </tr>
            <tr>
              <td colspan="2" class="pageHeading" align="center">
                <font size="2" face="Arial" color="blue">
                  <script type="text/javascript" language="JavaScript">
                    <!-- Begin
                    Stamp = new Date();
                    document.write('<?php echo TEXT_LAST_REFRESH. '&nbsp;'; ?>');
                    var Hours;
                    var Mins;
                    var Time;
                    Hours = Stamp.getHours();
                    if (<?php echo $time_format; ?> == 12) {
                      if (Hours >= 12) {
                        Time = " pm";
                        Hours -= 12;
                      } else {
                        Time = " am";
                      }
                      if (Hours == 0) {
                        Hours = 12;
                      }
                    } else {
                      Time = "";
                    }
                    Mins = Stamp.getMinutes();
                    if (Mins < 10) {
                     Mins = "0" + Mins;
                    }
                    document.write('&nbsp;' + Hours + ":" + Mins + Time );
                    // End -->
                  </script>
                </font>
              </td>
            </tr>
            <tr>
              <td colspan="2" valign="top">
                <table border="0" width="100%" cellspacing="0" cellpadding="0">
                  <tr>
                    <td valign="top">
                      <table border="0" width="100%" cellspacing="0" cellpadding="2">
                        <tr class="dataTableHeadingRow">
                          <td class="dataTableHeadingContent" colspan="2" nowrap align="center"><?php echo TABLE_HEADING_ONLINE; ?></td>
                          <td class="dataTableHeadingContent" nowrap><?php echo TABLE_HEADING_FULL_NAME; ?></td>
                          <td class="dataTableHeadingContent" nowrap>Country</td>
                          <td class="dataTableHeadingContent" nowrap><?php echo TABLE_HEADING_IP_ADDRESS; ?></td>
                          <td class="dataTableHeadingContent" nowrap><?php echo TABLE_HEADING_ENTRY_TIME; ?></td>
                          <td class="dataTableHeadingContent" nowrap><?php echo TABLE_HEADING_LAST_CLICK; ?></td>
                          <td class="dataTableHeadingContent" width="200"><?php echo TABLE_HEADING_LAST_PAGE_URL; ?>&nbsp;</td>
                          <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_USER_SESSION; ?>&nbsp;</td>
                          <td class="dataTableHeadingContent" align="center" nowrap><?php echo TABLE_HEADING_HTTP_REFERER; ?>&nbsp;</td>
                        </tr>

<?php
  // Order by is on Last Click. Also initialize total_bots and total_admin counts
  $whos_online_query = tep_db_query("SELECT * FROM " . TABLE_WHOS_ONLINE . " WHERE last_page_url <> '/visitors_georss.php' GROUP BY ip_address ORDER BY time_last_click DESC");
  $total_bots = 0;
  $total_admin = 0;
  $total_guests = 0;
  $total_loggedon = 0;
  $total_dupes = 0;
  // mjc added next line for count active guests and customers feature
  $ip_addrs_active = array();
  $ip_addrs = array();
  // mjc added next line to force info from the get var
  isset($_GET['info']) and $info = $_GET['info'];
  while ($whos_online = tep_db_fetch_array($whos_online_query)) {
    $time_online = ($whos_online['time_last_click'] - $whos_online['time_entry']);
    if ((!isset($_GET['info']) || (isset($_GET['info']) && ($_GET['info'] == $whos_online['session_id']))) && !isset($info)) {
      $info = $whos_online['session_id'];
    }

    $hostname = $whos_online['hostname'];

	//IP Adress geolocation
	$country_code = $whos_online['country_code'];
	$country_name = $whos_online['country_name'];
	$region_name = $whos_online['region_name'];
	$city = $whos_online['city'];
	$latitude = $whos_online['latitude'];
	$longitude = $whos_online['longitude'];

    //Check for duplicates
    if (in_array($whos_online['ip_address'],$ip_addrs)) {$total_dupes++;};
    $ip_addrs[] = $whos_online['ip_address'];

    // Display Status
    //   Check who it is and set values
    $is_bot = $is_admin = $is_guest = $is_account = false;

    if ($whos_online['customer_id'] < 0) {  
      $total_bots++;
      $fg_color = $fg_color_bot;
      $is_bot = true;

      // Admin detection
    } elseif ($whos_online['ip_address'] == $_SERVER["REMOTE_ADDR"]) {
      $total_admin++;
      $fg_color = $fg_color_admin;
      $is_admin = true;
    // Guest detection (may include Bots not detected by Prevent Spider Sessions/spiders.txt)
    } elseif ($whos_online['customer_id'] == 0) {
      $fg_color = $fg_color_guest;
      $is_guest = true;
      $total_guests++;
    // Everyone else (should only be account holders)
    } else {
      $fg_color = $fg_color_account;
      $is_account = true;
      $total_loggedon++;
    }

    if (!($is_bot && !isset($_GET['bots']))) {

    if ($whos_online['session_id'] == $info) {
       if($whos_online['http_referer'] != "")
       {
        $http_referer_url = $whos_online['http_referer'];
       }
      // mjc added "onclick" to allow refresh by clicking on selected row
      echo '
                        <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_WHOS_ONLINE, tep_get_all_get_params(array('info', 'action')) . 'info=' . $whos_online['session_id'], 'NONSSL') . '\'">' . "\n";
    } else {
      echo '
                        <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_WHOS_ONLINE, tep_get_all_get_params(array('info', 'action')) . 'info=' . $whos_online['session_id'], 'NONSSL') . '\'">' . "\n";
    }

?>
                          <!-- Status Light -->
<?php
                    // mjc mike challis added ,$whos_online['ip_address'] for count active guests and customers without duplicates feature
?>
                          <td class="dataTableContent" align="left" valign="top"><?php echo '&nbsp;' . tep_check_cart($whos_online['customer_id'], $whos_online['session_id'], $whos_online['ip_address']); ?></td>

                          <!-- Time Online -->
                          <td class="dataTableContent" valign="top"><font color="<?php echo $fg_color; ?>"><?php echo gmdate('H:i:s', $time_online); ?></font>&nbsp;</td>

                          <!-- Name -->
                          <?php
                          echo '
                          <td class="dataTableContent" valign="top"><font color="' . $fg_color .'">';

                          // WOL 1.6 Restructured to Check for Guest or Admin
                          if ( $is_guest || $is_admin ){
                            echo $whos_online['full_name'] . '&nbsp;';
                          // Check for Bot
                          } elseif ( $is_bot ) {
                            // Tokenize UserAgent and try to find Bots name
                            $tok = strtok($whos_online['full_name']," ();/");
                            while ($tok !== false) {  // edited from forum perfectpassion
                              if ( strlen(strtolower($tok)) > 3 )
                                if ( !strstr(strtolower($tok), "mozilla") &&
                                     !strstr(strtolower($tok), "compatible") &&
                                     !strstr(strtolower($tok), "msie") &&
                                     !strstr(strtolower($tok), "windows")
                                   ) {
                                  echo "$tok";
                                  break;
                                }
                              $tok = strtok(" ();/");
                            }
                          // Check for Account
                          } elseif ( $is_account ) {
                            echo '<a HREF="customers.php?selected_box=customers&cID=' . $whos_online['customer_id'] . '&action=edit">';
                            echo '<font color="' . $fg_color . '">' . $whos_online['full_name'] . '</font></a>';
                          } else {
                            echo TEXT_ERROR;
                          }
                          echo '</font></td>';
                          ?>

                          <!-- IP Address -->
						  <?php
						  $isp_icon = get_isp_icon($hostname);
						  ?>
	<td class="dataTableContent" width="30" align="center">
<?php
	if (file_exists(DIR_WS_IMAGES . 'flags/' . strtolower($country_code) . '.png')){
	$flag_img = '<img src="' . DIR_WS_IMAGES . 'flags/' . strtolower($country_code) . '.png" border=0 />'; 


	if ( $is_admin ) {
	echo '<span title="header=[Your Info] body=[<b> ' . TEXT_COUNTRY . ':</b> ' . stripslashes($whos_online['country_name']) . '<br><b>' . TEXT_REGION . ':</b> ' . stripslashes($whos_online['region_name']) . ' <br><b> ' . TEXT_CITY . ':</b> ' . stripslashes($whos_online['city']) . '<br><b>' . TEXT_USER_AGENT . ':</b> ' . stripslashes($whos_online['user_agent']). ']">' . $flag_img . '</span>' . '&nbsp;';
	} elseif ( $hostname == 'unknown' || $hostname == '' || $hostname == NULL || empty($hostname)) {
	echo '<span title="header=[Visitor Info] body=[<b> ' . TEXT_COUNTRY . ':</b> ' . stripslashes($whos_online['country_name']) . '<br><b>' . TEXT_REGION . ':</b> ' . stripslashes($whos_online['region_name']) . ' <br><b> ' . TEXT_CITY . ':</b> ' . stripslashes($whos_online['city']) . '<br><b>Hostname: </b>'.$hostname. '<br><b>' . TEXT_USER_AGENT . ':</b> ' . stripslashes($whos_online['user_agent']). ']">' . $flag_img . '</span>' . '&nbsp;';
	} else {
	echo '<span title="header=[Visitor Info] body=[<b> ' . TEXT_COUNTRY . ':</b> ' . stripslashes($whos_online['country_name']) . '<br><b>' . TEXT_REGION . ':</b> ' . stripslashes($whos_online['region_name']) . ' <br><b> ' . TEXT_CITY . ':</b> ' . stripslashes($whos_online['city']) . '<br><b>Hostname: </b>'.$hostname. '<br><b>' . TEXT_USER_AGENT . ':</b> ' . stripslashes($whos_online['user_agent']). ']">' . $flag_img . '</span>' . '&nbsp;';
	}
} 
?>
	</td>
	<!--td class="dataTableContent" valign="top">
											<?php
										/*	if ( $is_admin ) {
											} elseif ( $hostname == 'unknown' ) {
												echo $isp_icon;
											} else {
												echo $isp_icon;
											}
										*/	?>
										</td-->
										<td class="dataTableContent" valign="top" width="85"> 
											<?php
											// Show 'Admin' instead of IP for Admin
											if ( $is_admin ) {
												echo '<font color="' . $fg_color . '">' . TEXT_ADMIN . '</font>' . "\n";
											} elseif ( $hostname == 'unknown' ) {
												echo '<font color="' . $fg_color . '">' .$whos_online['ip_address'] . '</font>' . "\n";
											} else {
												echo '<a href="http://www.ipinfodb.com/ip_locator.php?ip=' . $whos_online['ip_address'] . '" target="_blank">';
												echo '<font color="' . $fg_color . '">' . $whos_online['ip_address'] . '</font></a>' . "\n";
											}
										?> 
										</td>
						
                          <?php

                          if ($time_format == 12) {
                            $format_string = "h:i:s&\\nb\sp;a";
                          } else {
                            $format_string = "H:i:s";
                          }
                          ?>

                          <!-- Time Entry -->
                          <td class="dataTableContent" valign="top"><font color="<?php echo $fg_color; ?>"><?php echo date($format_string, $whos_online['time_entry']); ?></font></td>

                          <!-- Last Click -->
                          <td class="dataTableContent" align="center" valign="top"><font color="<?php echo $fg_color; ?>"><?php echo date($format_string, $whos_online['time_last_click']); ?></font>&nbsp;</td>

                          <!-- Last URL -->
                          <td class="dataTableContent" valign="top"><?php
                            $temp_url_link = (isset($whos_online['last_page_url'])) ? $whos_online['last_page_url'] : NULL;
                            if (preg_match('/^(.*)' . tep_session_name() . '=[a-f,0-9]+[&]*(.*)/i', $whos_online['last_page_url'], $array)) {
                              $temp_url_display =  $array[1] . $array[2];
                            } else {
                              $temp_url_display = $whos_online['last_page_url'];
                            }

                            // WOL 1.6 - Removes IXsid from the Last Click URL and the link
                            if ( $IXsid_position = strpos($temp_url_display, "IXsid") )
                              $temp_url_display = substr_replace($temp_url_display, "", $IXsid_position - 1 );
                            if ( $IXsid_position = strpos($temp_url_link, "IXsid") )
                              $temp_url_link = substr_replace($temp_url_link, "", $IXsid_position - 1 );

                            // escape any special characters to conform to HTML DTD
                            $temp_url_display = htmlspecialchars($temp_url_display);

                            // alteration for last url product name  bof
                            if (strpos($temp_url_link,'product_info.php')) {
                              if (strpos($temp_url_link,'products_id=')) {
                                //Standard IX install using parameters
                                $temp = strstr($temp_url_link,'?');
                                $temp=str_replace('?','',$temp);
                                $parameters=explode("&",$temp);

                                $i=0;
                                while($i < count($parameters)) {
                                  $a=explode("=",$parameters[$i]);
                                  if ($a[0]=="products_id") { $products_id=$a[1]; }
                                  $i++;
                                }
                              } elseif (strpos($temp_url_link,'products_id/')) {
                                //IX search-engine safe URL
                                $temp = strstr($temp_url_link,'products_id');
                                $temparr=explode("\/",$temp);
                                $products_id=$temparr[1];
                              } else {
                                //couldn't figure it out
                                $products_id = '';
                              }
                              if ($products_id) {
                                $product_query=tep_db_query("select products_name from " . TABLE_PRODUCTS_DESCRIPTION. " where products_id='" . $products_id . "' and language_id = '" . $languages_id . "'");
                                $product = tep_db_fetch_array($product_query);
                                $display_link = $product['products_name'].' <i>(Product)</i>';
                              } else {
                                $display_link = $temp_url_display;
                              }
                            } elseif (strpos($temp_url_link,'cPath')) {
                              if (strpos($temp_url_link,'cPath=')) {
                                //Standard IX install using parameters
                                $temp = strstr($temp_url_link,'?');
                                $temp=str_replace('?','',$temp);
                                $parameters=explode("&",$temp);

                                $i=0;
                                while($i < count($parameters)) {
                                  $a=explode("=",$parameters[$i]);
                                  if ($a[0]=="cPath") { $cat=$a[1]; }
                                  $i++;
                                }
                              } elseif (strpos($temp_url_link,'cPath/')) {
                                //IX search-engine safe URL
                                $temp = strstr($temp_url_link,'cPath');
                                $temparr=explode("\/",$temp);
                                $cat=$temparr[1];
                              } else {
                                //couldn't figure it out
                                $cat = '';
                              }

                              $parameters=explode("_",$cat);
 
                              $i=0;
                              while($i < count($parameters)) {
                                $category_query=tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id='" . $parameters[$i] . "' and language_id = '" . $languages_id . "'");
                                $category = tep_db_fetch_array($category_query);
                                if ($i>0) { $cat_list.=' / ' . $category['categories_name']; } else { $cat_list=$category['categories_name']; }

                                $i++;
                              }
                              $display_link = $cat_list.' <i>(Category)</i>';
                            } else {
                              $display_link = $temp_url_display;
                            }

                            // alteration for last url product name  eof
 
                            // Get product and category from Ultimate SEO URLs bof
                            if ( preg_match('/^(.*)-p-(.*).html/',$temp_url_link,$matches) ) {
                              $products_id=$matches[2];
                              $product_query=tep_db_query("select products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id='" . $products_id . "' and language_id = '" . $languages_id . "'");
                              $product = tep_db_fetch_array($product_query);

                              $display_link = $product['products_name'].' <i>(Product)</i>';
                            } elseif ( preg_match('/^(.*)-c-(.*).html/',$temp_url_link,$matches) ) {
                              $cat=$matches[2];
                              $parameters=explode("_",$cat);

                              $i=0;
                              while($i < count($parameters)) {
                                $category_query=tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id='" . $parameters[$i] . "' and language_id = '" . $languages_id . "'");
                                $category = tep_db_fetch_array($category_query);
                                if ($i>0) { $cat_list.=' / '.$category['categories_name']; } else { $cat_list=$category['categories_name']; }

                                $i++;
                              }
                              $display_link = $cat_list.' <i>(Category)</i>';
                            }
                            // Get product and category from Ultimate SEO URLs eof

                            echo '<a href="' . (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . htmlspecialchars($temp_url_link) . '" target="_blank"><font color="'.$fg_color.'">'.$display_link.'</font></a></td>';
                          ?>

                          <!-- IXsid? -->
                          <td class="dataTableContent" align="center" valign="top"><font color="<?php echo $fg_color; ?>"><?php echo (($whos_online['session_id'] != $whos_online['ip_address']) ? TEXT_IN_SESSION : TEXT_NO_SESSION);?></font></td>

                          <!-- Referer? -->
                          <td class="dataTableContent" align="center" valign="top"><font color="<?php echo $fg_color; ?>"><?php echo (($whos_online['http_referer'] == "") ? TEXT_HTTP_REFERER_NOT_FOUND : TEXT_HTTP_REFERER_FOUND);?></font></td>
                        </tr>

                      <?php
                        // mjc mchallis modified next line for more standard use of query get vars
                        if (($_GET['show'] == 'all') || (($_GET['show'] == 'bots') && $is_bot) || (($_GET['show'] == 'cust') && ( $is_guest || $is_account || $is_admin )) ) {
                      ?>
                        <tr class="dataTableRow">
                          <td class="dataTableContent" colspan="3"></td>
                          <td class="dataTableContent" colspan="6"><font color="<?php echo $fg_color; ?>"><?php display_details($country_code); ?></font></td>
                        </tr>
                      <?php
                        }
    } // closes "if $isbot statement
  } // closes "while" statement
                      ?>
<?php
  //Display HTTP referer, if any
  // mjc mike challis added wordwrap to referer url
  if(isset($http_referer_url)) {
?>
                        <tr>
                          <td class="smallText" colspan="9"><?php echo '<strong>' . TEXT_HTTP_REFERER_URL . ': </strong><a href="' . htmlspecialchars($http_referer_url) . '" target="_blank">' . wordwrap(htmlspecialchars($http_referer_url), $referer_wordwrap_chars, "<br>", true) . '</a>'; ?></td>
                        </tr>
<?php
  }
?>
<?php
  $total_sess = tep_db_num_rows($whos_online_query);
  // Subtract Bots and Me from Real Customers.  Only subtract me once as Dupes will remove others
  $total_cust = $total_sess - $total_dupes - $total_bots - (($total_admin > 1) ? 1 : $total_admin);
?>
                        <tr>
                          <!-- WOL 1.4 - Added Bot and Me counts -->
                          <td class="smallText" colspan="9"><br>
                            <table border="0" cellpadding="0" cellspacing="0" width="600">
                              <tr>
                                <td class="smallText" align="right" width="30"><?php print "$total_sess" ?></td>
                                <?php if ($total_sess == 1){ ?>
                                <td class="smallText" align="left" width="570">&nbsp;&nbsp;<?php echo TEXT_NUMBER_OF_CUSTOMER; ?></td>
                                <?php } else { ?>
                                <td class="smallText" align="left" width="570">&nbsp;&nbsp;<?php echo TEXT_NUMBER_OF_CUSTOMERS; ?></td>
                                <?php } ?>
								</tr>
								<tr>
								<td class="smallText" align="right" width="30"><?php print "$total_dupes" ?></td>
								<?php if ($total_dupes == 1){ ?>
								<td class="smallText" align="left" width="570">&nbsp;&nbsp;<?php echo TEXT_DUPLICATE_IP; ?></td>
								<?php } else { ?>
								<td class="smallText" align="left" width="570">&nbsp;&nbsp;<?php echo TEXT_DUPLICATE_IPS; ?></td>
								<?php } ?>
								</tr>
								<tr>
								<td class="smallText" align="right" width="30"><?php print "$total_bots" ?></td>
								<?php if ($total_bots == 1){ ?>
								<td class="smallText" width="570">&nbsp;&nbsp;<?php echo TEXT_BOT; ?></td>
								<?php } else { ?>
								<td class="smallText" width="570">&nbsp;&nbsp;<?php echo TEXT_BOTS; ?></td>
								<?php } ?>
								</tr>
								<tr>
                                <td class="smallText" align="right" width="30"><?php print "$total_admin" ?></td>
                                <td class="smallText" width="570">&nbsp;&nbsp;<?php echo TEXT_ME; ?></td>
                              </tr>
                              <tr>
                                <td class="smallText" align="right" width="30"><?php print "$total_cust" ?></td>
							  <?php if ($total_cust == 1){
										if (count($ip_addrs_active) == 1){ ?>
											<td class="smallText" width="570">&nbsp;&nbsp;<?php echo TEXT_REAL_CUSTOMER; if(count($ip_addrs_active) > 0) echo ', <font color="' . $fg_color_guest . '">' . count($ip_addrs_active) . TEXT_ACTIVE_CUSTOMER . '</font>'; ?></td>
										<?php } else { ?>
											<td class="smallText" width="570">&nbsp;&nbsp;<?php echo TEXT_REAL_CUSTOMER; if(count($ip_addrs_active) > 0) echo ', <font color="' . $fg_color_guest . '">' . count($ip_addrs_active) . TEXT_ACTIVE_CUSTOMERS . '</font>'; ?></td>
									<?php } } else {
										if (count($ip_addrs_active) == 1){ ?>
											<td class="smallText" width="570">&nbsp;&nbsp;<?php echo TEXT_REAL_CUSTOMERS; if(count($ip_addrs_active) > 0) echo ', <font color="' . $fg_color_guest . '">' . count($ip_addrs_active) . TEXT_ACTIVE_CUSTOMER . '</font>'; ?></td>
										<?php } else { ?>
											<td class="smallText" width="570">&nbsp;&nbsp;<?php echo TEXT_REAL_CUSTOMERS; if(count($ip_addrs_active) > 0) echo ', <font color="' . $fg_color_guest . '">' . count($ip_addrs_active) . TEXT_ACTIVE_CUSTOMERS . '</font>'; ?></td>
									<?php } } ?>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td class="smallText" colspan="9"> 
                            <?php echo "<b>" . TEXT_MY_IP_ADDRESS . ":</b>&nbsp;".$_SERVER["REMOTE_ADDR"]; ?><br>
                            <?php echo TEXT_NOT_AVAILABLE;?>
                          </td>
                          <!-- WOL 1.4 eof -->
                        </tr>
                      </table>
                    </td>


                  </tr>
                </table>
              </td>
            </tr>
<?php 
if(isset($_GET['map']))
echo'<tr><td align="center" colspan="2"><div id="map_canvas" style="width:99%; height: 400px"></div></td></tr>';
?>
          </table>
        </td>
      </tr>

    </table>

  </body>
</html>
<?php 
require(DIR_WS_INCLUDES . 'application_bottom.php'); 
?>
