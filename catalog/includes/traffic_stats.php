<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

  function update_traffic_stats($rf) {

    $parse = parse_url($rf);

    $url = $parse['host'].$parse['path'];

	//if (strtolower(SITE_DOMAIN) == strtolower($parse['host'])) return;

    $src_query = tep_db_query("SELECT traffic_source FROM IXcore.traffic_sources WHERE '".$url."' LIKE referer_url");
    $src_row = tep_db_fetch_array($src_query);

    $src = ($src_row) ? $src_row['traffic_source'] : 'other';

	if($src == 'other') {

		// # if referral isnt set or $_GET ref isnt set, claim as direct / bookmark
		if((strtolower(SITE_DOMAIN) == strtolower($parse['host'])) && ($_GET['ref'] == 'email')) {
			$src = 'email';
		} elseif(strtolower(SITE_DOMAIN) == strtolower($parse['host'])) {
			$src = 'direct';
		}

	}

    if ($src == '-') return;

	$referrer_IP = (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : 'NULL';
	$thatDate = date('Y-m-d', time());

    tep_db_query("UPDATE traffic_stats 
					SET hit_count = hit_count+1 
					WHERE traffic_date = '".$thatDate."' 
					AND traffic_source='".$src."'
					AND user_ip = '".$referrer_IP."'
				  ");

    if (tep_db_affected_rows() < 1) {
		tep_db_query("INSERT IGNORE INTO traffic_stats 
						SET traffic_date = '".$thatDate."', 
						traffic_source = '".$src."', 
						hit_count = 1, 
						user_ip = '".$referrer_IP ."'");

	}
	
	if(!isset($_SESSION['orders_source']) || empty($_SESSION['orders_source'])) {
		session_start();
    	$_SESSION['orders_source'] = $src;

		if($src == 'email' && !empty($_GET['nID'])) { 
			$_SESSION['nID'] = (int)$_GET['nID'];
		} 	
	}
  
}


if(isset($_SERVER['HTTP_REFERER']) && !isset($_GET['ref'])) { 
	update_traffic_stats($_SERVER['HTTP_REFERER']);
} elseif(isset($_GET['ref'])) {
	update_traffic_stats(urlencode($_GET['ref']));

// # if referral isnt set or $_GET ref isnt set, claim as direct / bookmark
} elseif (!isset($_SERVER['HTTP_REFERER']) && !isset($_GET['ref'])){ 
	update_traffic_stats(SITE_DOMAIN);
}
?>