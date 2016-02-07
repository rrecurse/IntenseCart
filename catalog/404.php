<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	//require('includes/application_top.php');

	//require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_404);

	function try_url($try_url) {

		$url_query = tep_db_query("SELECT url_new FROM ". TABLE_URL_REWRITE ." WHERE url_new = '".$try_url.".html' LIMIT 1");

		if(tep_db_num_rows($url_query) > 0) {

			$url_new = tep_db_result($url_query,0);

			tep_db_free_result($url_query);

			return $url_new;

		}

		foreach(array('.php','.html') AS $sufx) {
			if(file_exists(DIR_FS_CATALOG.$try_url.$sufx)) {
				return $try_url.$sufx;
			}
		}

		return NULL;
	}

	// # sanitize URL for tiny_url() function.
	$sanitized_url = preg_replace('|[^/\w]+|','-',preg_replace('/\.\w*$/','',$_SERVER['REQUEST_URI']));

	$url_found = try_url($sanitized_url);

	if(!empty($url_found)) {

		$url_found = str_replace('/','::',str_replace('-',' ',preg_replace('|^/(.*?)\.\w+$|','$1',$url_found)));

		echo '<p>The following page satisfies your request:</p>
				<ul>
					<li><a href="'.tep_href_link($url_found).'">'.$url_found.'</a></li>
				</ul>';
	}

	$words = array();

	foreach(preg_split('/\//',$_SERVER['REQUEST_URI']) AS $word) {
    	$word = trim(preg_replace('/\W+/',' ',preg_replace('/\.\w*$/','',$word)));
		if($word!='') $words[strtolower($word)] = $word;
	}

?>

<html <?php echo HTML_PARAMS; ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
		<link rel="stylesheet" type="text/css" href="stylesheet.css">
	</head>
	<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

		<table width="100%">
			<tr>
				<td><h2>File not found ...</h2>
				<p>Please also try the following pages:</p>

<?php

	if(sizeof($words) > 0) {

    	$swap_query = tep_db_query("SELECT sws_word,sws_replacement FROM searchword_swap WHERE sws_word IN ('".join("','",$words)."')");

		while($swap_row = tep_db_fetch_array($swap_query)) {
			$words[strtolower($swap_row['sws_word'])] = $swap_row['sws_replacement'];
		}

		tep_db_free_result($swap_query);

		$search_conds = array();
    	$related_urls = array();

		foreach($words AS $word) {
			foreach (preg_split('/ /',$word) AS $search_word) {
				$search_conds[]='(rw.url_new REGEXP \'[\-/]'.$search_word.'[\-\.][^/]*$\')';
			}
		}

		$search_query = tep_db_query("SELECT rw.url_new
									  FROM url_rewrite rw
									  LEFT JOIN url_rewrite_map rwm ON rwm.url_new = rw.url_new
									  WHERE  ".join(' AND ',$search_conds)."
									  GROUP BY rwm.item_id LIMIT 10
									 ");

		while($search_row=tep_db_fetch_array($search_query)) {
			if ($url_found != $search_row['url_new']) {
				$related_urls[]=$search_row['url_new'];
			}
		}
		tep_db_free_result($search_query);

		if(sizeof($related_urls)) {

			echo '<ul>';
			foreach ($related_urls AS $related_url) {
				echo '<li><a href="'.tep_href_link($related_url).'">'.str_replace('/','::',str_replace('-',' ',preg_replace('|^/(.*?)\.\w+$|','$1',$related_url))).'</a></li>';
			}
			echo '</ul>';

		}

	}
?>
<p>You may also try Advanced Search:</p>
<ul>
<?php

	foreach ($words AS $word) {
		echo '<li><a href="'.tep_href_link(FILENAME_ADVANCED_SEARCH_RESULT,'keywords='.$word).'">'.$word.'</a></li>';
	}
?>

</ul>
</td></tr>
</table>
<?php

	include(DIR_WS_MODULES .  FILENAME_SITEMAP);

	//require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
