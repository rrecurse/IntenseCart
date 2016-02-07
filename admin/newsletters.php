<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


  require('includes/application_top.php');

	if(!isset($_GET['nID']) && $_GET['action'] != 'new') {

		// # Retrieve the $firstID by date_added
		$firstID = (int)tep_db_result(tep_db_query("SELECT newsletters_id FROM ". TABLE_NEWSLETTERS ." ORDER BY date_added DESC LIMIT 1"),0);

	} elseif(!isset($_GET['nID']) && isset($_GET['action']) && $_GET['action'] == 'new' && $action != 'insert') {

		$firstID = (int)tep_db_result(tep_db_query("SELECT newsletters_id + 1 FROM ". TABLE_NEWSLETTERS ." ORDER BY date_added DESC LIMIT 1"),0);

	}


	$page = (!empty($_GET['page']) ? (int)$_GET['page'] : 1);

	// # Set the $firstID as main nID if not passed to script
	$nID = (isset($_GET['nID'])) ? (int)$_GET['nID'] : $firstID;

	// # Check to see if the current selection $hasContent - will throw error if used and GET nID isnt passed
	if(isset($_GET['nID'])) {
		$hasContent = tep_db_result(tep_db_query("SELECT content FROM ".TABLE_NEWSLETTERS." WHERE newsletters_id = '".$_GET['nID']."'"), 0);
	}

	// # select the default newsletter if nID not passed to script
	$newsletter_query = tep_db_query("SELECT n.*, 
											 si.module_subscribers,
											 sd.unsubscribea AS default_foot, 
											 si.unsubscribea AS custom_foot												
									  FROM " . TABLE_NEWSLETTERS . " n
									  LEFT JOIN " . TABLE_SUBSCRIBERS_INFOS . " si ON si.newsletters_id = n.newsletters_id
									  LEFT JOIN " . TABLE_SUBSCRIBERS_DEFAULT . " sd ON sd.module_subscribers = n.module
									  WHERE n.newsletters_id = '".(int)$nID."'
									");

	$newsletter = tep_db_fetch_array($newsletter_query);

	// # Build $List count for each main mailing list.
	// # Add more more module types and edit insert queries appropriatly to extend

	$theModule = $newsletter['module'];
	$theList = '';
	if($theModule == 'subscribers'){

 		$subscribers_query = tep_db_query("SELECT * 
									FROM (SELECT DISTINCT c.customers_email_address, 
											c.customers_id,
											c.customers_firstname, 
											c.customers_lastname,
											c.customers_newsletter
											FROM " . TABLE_CUSTOMERS . "  c
											WHERE c.customers_email_address NOT LIKE '%@marketplace.amazon.com'
											AND c.customers_group_id = '0'
											AND c.customers_newsletter = '1'	
									
									UNION ALL

										SELECT DISTINCT s.subscribers_email_address,
												s.customers_id,
												s.subscribers_firstname,
												s.subscribers_lastname,
												s.customers_newsletter
										FROM subscribers s
										LEFT JOIN " . TABLE_CUSTOMERS . " c ON c.customers_email_address = s.subscribers_email_address
		  								WHERE c.customers_email_address IS NULL
		  								AND s.customers_newsletter = '1') AS table1
									");	

		$subscribers_array = tep_db_fetch_array ($subscribers_query);
		$theList = tep_db_num_rows($subscribers_query);
 
	} elseif($theModule == 'vendors_subscribers'){
 
		$vendors_query = tep_db_query ("SELECT * FROM ". TABLE_CUSTOMERS ." WHERE customers_newsletter = 1 AND customers_group_id > 1");
		$vendors = tep_db_fetch_array ($vendors_query);
		$theList = tep_db_num_rows($vendors_query);
 
	} elseif($theModule == 'affiliate_subscribers'){
 
		$affiliate_query = tep_db_query ("SELECT * FROM " . TABLE_AFFILIATE . " WHERE affiliate_newsletter = 1");
		$affiliates = tep_db_fetch_array ($affiliate_query);
		$theList = tep_db_num_rows($affiliate_query);
 
	} elseif($theModule == 'product_notifications'){
		$ids = implode (',', $GLOBALS['chosen']);
		$productNotify_query = tep_db_query ("SELECT DISTINCT pn.customers_id FROM ". TABLE_PRODUCTS_NOTIFICATIONS ." pn, ".TABLE_CUSTOMERS." c WHERE c.customers_id = pn.customers_id AND pn.products_id IN(". $ids .") AND c.customers_newsletter = 1");
		$theList = tep_db_num_rows($productNotify_query);

	} elseif($theModule == 'test_subscribers'){
 
		$test_query = tep_db_query ("SELECT * FROM ". TABLE_CUSTOMERS ." WHERE customers_newsletter = 1 AND customers_group_id = 0 AND customers_email_address LIKE '%chrisd@zwaveproducts.com'");
		$test = tep_db_fetch_array ($test_query);
		$theList = tep_db_num_rows($test_query);
	}

	// # For Ajax modal preview request and generating thumbnail.
	if(isset($_GET['preview']) && $_GET['preview'] == 1) {

echo '
<table width="100%" cellpadding="5" cellspacing="0" border="0" style="background-color:#FFF; border-radius:5px">
<tr>
		<td class="main"><font color="#ff0000"><b>'.TEXT_TITRE_INFO.'</b></font></td>	
		</tr>		
		<tr>	
			<td class="main">'.sprintf(TEXT_COUNT_CUSTOMERS, $theList) .'</td>	
		</tr>		
		<tr>	
			<td class="main">'.TEXT_BULLETIN_NUMB . "&nbsp;" .'<font color="#0000ff">'.$newsletter['newsletters_id'].'</font></td>	
		</tr>		
		<tr>	
			<td class="main">'.TEXT_MODULE . "&nbsp;" .'<font color="#0000ff">'. $newsletter['module_subscribers'] .'</font></td>	
		</tr>		
		<tr>	
			<td class="main">'.TEXT_NEWSLETTER_FROM . "&nbsp;" .'<font color="#0000ff">'.$newsletter['fromMail'] .'</font></td>	
		</tr>
		<tr>	
			<td class="main">'.TEXT_NEWSLETTER_TITLE . "&nbsp;" .'<font color="#0000ff">'.$newsletter['title'] .'</font></td>	
		</tr>
		<tr>	
			<td class="main">'.TEXT_SUBJECT_MAIL . "&nbsp;" .'<font color="#0000ff">'.$newsletter['subject'] .'</font></td>	
		</tr>
</table>';	

		echo '<a id="close_x" class="close" style="position:absolute; top:5px; right:5px;" href="#"><img src="images/lightbox_close_button.png" border="0"></a>';
		echo '<hr>
			  <div id="thenews">';

		$thecontent = str_replace('src="http://', 'src="'.(!empty( $_SERVER['HTTPS']) ? 'https://' : 'http://'), $newsletter['content']);

		$email_foot = (!empty($newsletter['custom_foot'])) ? $newsletter['custom_foot'] : $newsletter['default_foot'];

		// # remove any body tags
		$content = preg_replace('/<body.*?>/i', '', $newsletter['content']);

		// # remove everything between style tags
		$content = preg_replace('#(<style.*?>).*?(</style>)#', '$1$2', $content);

		// # now remove style tags
		$content = preg_replace('/<style.*?>/i', '', $content);

		// # remove ending style tags and any loose line breaks & tabs
		$content = str_replace(array("\r\n\t", "\r\n", "\r", "\n", "\t", "</style>"),'', $content);

		$footer = str_replace(array('</body>', '</BODY>'), '', $email_foot);
		$footer = str_replace(array("\r\n", "\r", "\n", "\t"),'',$footer);

		$html = (!empty($newsletter['content'])) ? $content.$footer : '<b>NO CONTENT</b>';

		// # now strip all remaining white space and replace with single space
		$html = preg_replace('/\s+/', ' ', $html);
		$html = nl2br($html);
//error_log(print_r($html,1));

		$doc = new DOMDocument();
		$doc->recover = TRUE;

		$doc->loadHTML($html);
	
		$div = $doc->createElement('div');
		$body = $doc->getElementsByTagName('body')->item(0);
		while ($body->firstChild){
			$div->appendChild($body->firstChild);
		}

		$html = $doc->saveHTML($div);

		echo html_entity_decode($html) . '</div>';
	exit();
	}

// # END Add List selection to Queue

	$action = (isset($_GET['action']) ? $_GET['action'] : '');

	if (tep_not_null($action)) {
    	switch ($action) {
  

		  // # Flag as draft and do not send
		  case 'ready':
    	  case 'draft':
        	$newsletter_id = tep_db_prepare_input($_GET['nID']);
	        $ready = (($action == 'ready') ? 'pending' : 'draft');
    	    tep_db_query("UPDATE " . TABLE_NEWSLETTERS . " SET status ='".$ready."' WHERE newsletters_id = '".(int)$newsletter_id."'");
        	tep_redirect(tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $_GET['nID']));
        	break;

 

      case 'insert':
      case 'update':

		$newsletter_id = (!empty($_POST['newsletter_id']) ? (int)$_POST['newsletter_id'] : (int)$_GET['nID']);
       
		$newsletter_module = tep_db_prepare_input($_POST['module']);

        $title = tep_db_prepare_input($_POST['title']);

        $fromMail = ($_POST['fromMail'] != STORE_OWNER_EMAIL_ADDRESS ? tep_db_prepare_input($_POST['fromMail']) : STORE_OWNER_EMAIL_ADDRESS);

        $subject = tep_db_prepare_input($_POST['subject']);

        $content = str_replace(array('&lt;','&gt;', '&amp;nbsp;','&nbsp;'),array('<','>','&nbsp;','&nbsp;'), htmlspecialchars(tep_db_prepare_input($_POST['content']), ENT_NOQUOTES,'UTF-8'));

		$email_foot = str_replace(array('&lt;','&gt;', '&amp;nbsp;','&nbsp;'), array('<','>','&nbsp;','&nbsp;'), htmlspecialchars(tep_db_prepare_input($_POST['email_footer']), ENT_NOQUOTES,'UTF-8'));

		// # form the Google Analytics tracking URL
		$googleTrackURL = htmlspecialchars('?utm_source='.urlencode($newsletter_module).'&utm_medium=email&utm_campaign='.urlencode($title).'&ref=email&email=[customer_email]&nID='.$newsletter_id);
		
		// # scan all $content href's for existing google analytics tracking URL
		// # if not detect (is false) then append all href's with this google URL
		// # nuance - if changing email title/subject, will append an addition set with '?'


		if(strpos($content, $googleTrackURL) === false) {
			$content = preg_replace('/<a href="(.*?)"/i', '<a href="$1'. $googleTrackURL .'"', $content);
		}


		// # scan through content of main email body and footer for href's containing our dynamic vars [view_online]
		// # since we appended the google analytics tracking URL, we want to ensure there are no double '?' in URL
		if(stripos($content, '[view_online]')) { 
			$content = str_replace('[view_online]?', '[view_online]&amp;', $content);
		}

		if(strpos($email_foot, $googleTrackURL) === false) {
			$email_foot = preg_replace('/<a href="(.*?)"/i', '<a href="$1'. $googleTrackURL .'"', $email_foot);
		}

		if(stripos($email_foot, '[view_online]')) { 
			$email_foot = str_replace('[view_online]?', '[view_online]&amp;', $email_foot);
		}

		if(stripos($email_foot, '[unsubscribe_link]')) { 
			$email_foot = str_replace('[unsubscribe_link]&amp;', '[unsubscribe_link]?', $email_foot);
		}

		// # Retrieve the availibility of HTTPS and add a colon and slash-slash to the end.
		$theHTTP = '"' . (!empty( $_SERVER['HTTPS']) ? 'https://' : 'http://') . rtrim($_SERVER['HTTP_HOST'],'/').'/';


		// # Replace all URLs that do not contain domain with protocol (http) and domain.
		$content = str_replace('"/', $theHTTP, $content);

		$content_text = tep_db_prepare_input($_POST['content_text']);

		$content_text = str_replace('"/', $theHTTP, $content_text);

		$email_foot = str_replace('"/', $theHTTP, $email_foot);
		
		// # END Replace all URLs that do not contain domain


		$date_scheduled = date('Y-m-d H:i:s',strtotime(tep_db_prepare_input($_POST['event_time'])));

		$priority = tep_db_prepare_input($_POST['priority']);

        $newsletter_error = false;

        if( empty($title) || empty($subject) || empty($module) ) {
			$messageStack->add(ERROR_NEWSLETTER_TITLE, 'error');
			$newsletter_error = true;
        }
		
		$todaysDate = strtotime(date("Y-m-d 00:00:00", time()));
		$theDate = strtotime($date_scheduled);


		if ($theDate <= $todaysDate) {
          $messageStack->add(ERROR_NEWSLETTER_DATE, 'error');
          $newsletter_error = true;
        }

		// # POST of image via jQuery from html2canvas
		// # Attempt to create file and pass to GD.
		// # Return portion of the POST starting from the base64 string -
		// # Skip 'data:image/png;base64,' part specifically.
		// # Pass base64 html2canvas string into php-GD and resize to 60% dimensions

		$postHtml2Canvas = substr($_POST['source'],strpos($_POST['source'],",")+1);	

		if(!empty($_POST['source']) && isset($_GET['nID'])) {

			//file_put_contents($_SERVER['DOCUMENT_ROOT'] .'/admin/images/nID_'.$_GET['nID'].'.jpg',base64_decode($postHtml2Canvas));

			$html2Canvasim = imagecreatefromstring(base64_decode($postHtml2Canvas));
			$width = imagesx($html2Canvasim);
			$height = imagesy($html2Canvasim);
			$newwidth = $width * .35;
			$newheight = $height * .35;
		
			$thescreen = imagecreatetruecolor($newwidth, $newheight);
			imagecopyresized($thescreen, $html2Canvasim, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

			imagejpeg($thescreen, $_SERVER['DOCUMENT_ROOT'] .'/admin/images/nID_'.$_GET['nID'].'.jpg', 94);
			imagedestroy($thescreen);
		}


	if ($newsletter_error == false) {

    	$sql_data_array = array('title' => $title,
								'fromMail' => $fromMail,
								'subject' =>$subject,
                                'content' => $content,
                                'content_text' => $content_text,
                                'module' => $newsletter_module,
								'date_scheduled' => $date_scheduled,
								'priority' => $priority);


		if($action == 'insert') {

	       //$sql_data_array['date_added'] = NOW();
			$sql_data_array['status'] = 'pending';
		
			// # Add priority	
			$sql_data_array['priority'] = tep_db_prepare_input($_POST['priority']);

			// # Add Event Time
			$sql_data_array['date_scheduled'] = tep_db_prepare_input(date('Y-m-d H:i:s', strtotime($_POST['event_time'])));

			// # Perform the insert
            tep_db_perform(TABLE_NEWSLETTERS, $sql_data_array);

            $newsletter_id = tep_db_insert_id();

			// # Determine module to use by ID in the subscribers_default table.
			// # This whole bit needs to be redone.

			if ($newsletter_module=='subscribers') {
				  $news_id_subscriber = '1';  
			} elseif ($newsletter_module=='affiliate_subscribers')	{	
				  $news_id_subscriber = '2';
			} elseif ($newsletter_module=='product_notification') {	
				  $news_id_subscriber = '3';
			} elseif ($newsletter_module=='test_subscribers')	{	
				  $news_id_subscriber = '4';
			} elseif ($newsletter_module=='vendor_subscribers')	{	
				  $news_id_subscriber = '5';
			// # Determine if subscribers module ID selected. In this case its currently '1' in the subscribers_default table.
			} else {	
				  $news_id_subscriber = '1';
			}

	  //if(empty($newsletter['custom_foot'])) {
			// # Insertion of new newsletter subscribers with header and footer by default - Reading Basic subcribers_default
			$latest_news_query = tep_db_query("SELECT news_id, module_subscribers, header, status, unsubscribea, unsubscribeb 
											   FROM ".TABLE_SUBSCRIBERS_DEFAULT." 
											   WHERE news_id = '".$news_id_subscriber."'
											 ");

      		$latest_news = tep_db_fetch_array($latest_news_query);
			$nlatest_news = new objectInfo($latest_news);
			
			// # Adds slashes to single quotes
			$module_subscribers = preg_replace("#[']#", "\'", $nlatest_news->module_subscribers);
			$header = preg_replace("#[']#", "\'", $nlatest_news->header);
			$unsubscribea = preg_replace("#[']#", "\'", $nlatest_news->unsubscribea);
			$unsubscribeb = preg_replace("#[']#", "\'", $nlatest_news->unsubscribeb);



		    // # Writing in the base subcribers_infos
			tep_db_query("INSERT INTO " . TABLE_SUBSCRIBERS_INFOS . " 
						  SET newsletters_id = '".$newsletter_id."', 
						  status = 1, 
						  module_subscribers = '".$module_subscribers."', 
						  header = '".$header."', 
						  date_added = NOW(), 
						  unsubscribea = '".(!empty($email_foot) ? $email_foot : $unsubscribea)."', 
						  unsubscribeb = '".$unsubscribeb."'
						");
		//}

		} elseif ($action == 'update') {

			tep_db_perform(TABLE_NEWSLETTERS, $sql_data_array, 'update', "newsletters_id = '" . (int)$newsletter_id . "'");

    	    // # Updating the email footer in subscribers_infos table
			tep_db_query("UPDATE ".TABLE_SUBSCRIBERS_INFOS."
						  SET module_subscribers = '".$newsletter['module_subscribers']."', 
						  date_added = NOW(), 
						  unsubscribea = '".$email_foot."'
						  WHERE newsletters_id = '".$newsletter_id."'
						");
		}

		tep_redirect(tep_href_link(FILENAME_NEWSLETTERS, (!empty($page) ? 'page='. $page . '&' : '') . 'nID='. $newsletter_id));
    
	} else {

	$action = 'new';
}
	break;

	case 'deleteconfirm':
		$newsletter_id = tep_db_prepare_input($_GET['nID']);

		tep_db_query("DELETE FROM ". TABLE_NEWSLETTERS ." WHERE newsletters_id = '". (int)$newsletter_id ."'");
		tep_db_query("DELETE FROM ". TABLE_NEWSLETTERS_QUEUE ." WHERE newsletters_id = '". (int)$newsletter_id ."'");

		// # Remove the header and footer of the newsletter
		tep_db_query("DELETE FROM ". TABLE_SUBSCRIBERS_INFOS ." WHERE newsletters_id = '". (int)$newsletter_id ."'");

		// # optimize the tables
		tep_db_query("OPTIMIZE TABLE ". TABLE_NEWSLETTERS);
		tep_db_query("OPTIMIZE TABLE ". TABLE_NEWSLETTERS_QUEUE);
		tep_db_query("OPTIMIZE TABLE ". TABLE_SUBSCRIBERS_INFOS);


		// # Remove the cooresponding thumbnail preview if it exists.
		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/admin/images/nID_'.$newsletter_id.'.jpg')) {
			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/images/nID_'.$newsletter_id.'.jpg');	
		} 

		tep_redirect(tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page));
	break;

		case 'delete':
		case 'new': if (isset($_GET['nID']) ? $_GET['nID'] : $nID) break;
		case 'send':
		case 'confirm_send':
	
	        $newsletter_id = tep_db_prepare_input($_GET['nID']);
        break;
    }
  }

	if(!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Email Manager');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo HEADING_TITLE ?></title>

<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>

<script type="text/javascript" src="js/jquery.lightbox_me.js"></script>
<script type="text/javascript" src="js/popcalendar.js"></script>



<script type="text/javascript">
jQuery.noConflict();
	jQuery(document).ready(function(jQuery) {
		var thepanelH = window.parent.jQuery(window.parent.document).find('#thePanel').height();
		jQuery('body').height('body'+thepanelH-25);
		top.resizeIframe('myframe');
});

</script>

<link rel="stylesheet" type="text/css" href="js/css.css">
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css" >


<style type="text/css">
textarea {width:100% !important;}

.dataTableRowSelected .dataTableContent {
	font:bold 11px arial;
}

.newsTable .dataTableHeadingRow:hover {
	background-color:#6295FD;
}
.newsTable td {
	font: normal 11px arial;
}
.newsTable tr:hover {
	background-color:#FFFFC4;
}

.preview {
	cursor: pointer;
}

#thenews {
	padding:0;
	margin:0;
}
#thenews table {
	border:0;
	border-spacing: 0;
	padding:0;
	margin:0;
	border-collapse: collapse;
}

#thenews table td {
	border:0;
	border-spacing: 0;
	padding:0;
	margin: 0;
	border-collapse: collapse;
	display: table-cell;
}

#thenews tr {
   display: table-row;
	margin: -3px 0;
}

#thenews img {
	border-spacing: 0;
	padding:0;
	margin: -3px 0;
}

</style>


</head>
<body style="background-color:transparent; overflow-y:hidden">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<script type="text/javascript">

	// # Convert HTML to plain text, onclick. Requires some reformating of text.
	function html2text(src,dst) {
		if ((dst.value!='') && !window.confirm('Warning!\nThis will overwrite the existing text template')) return false;

		var sp = src.value.replace(/\s+/g,' ').split('<');
		var d = sp[0];
		for (var i = 1;sp[i] != null; i++) {
 		var s=sp[i];
 		var rp='';
 		if (s.match(/^(br|p|tr|div|blockquote|h[1-9])\b/))
			rp='\n';
		else if (s.match(/^(td|th)\b/))
			rp='\t';
		else if (s.match(/^(hr)\b/))
			rp='\n-----------------------------------------\n';
			d+=s.replace(/.*?>/,rp);
		}
		dst.value=d.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&amp;/g,'&').replace(/\s{2,}/g, '\n\n');
	}
</script>
<?php 
if((isset($_GET['action']) && $_GET['action'] == 'new') || (isset($_GET['action']) && $_GET['action'] == 'update')) {  ?>
<script type="text/javascript" src="js/popcalendar.js"></script>

<?php } ?>

<table border="0" width="100%" cellspacing="2" cellpadding="5">
<tr><td width="50"><img src="images/icons/emailManager.png" alt=""></td>
<td style="height:48px; line-height:48px;" class="pageHeading"><?php echo HEADING_TITLE; ?></td>
</tr></table>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    
    <td valign="top">

<?php
if ($action == 'new') {

		$form_action = 'insert';

    	$parameters = array('title' => '', 'content' => '', 'module' => '');

    	$nInfo = new objectInfo($parameters);

		if (isset($_GET['nID'])) {
			$form_action = 'update';

			$nID = tep_db_prepare_input($_GET['nID']);
		
			$newsletter_query = tep_db_query("SELECT n.*, 
													 si.module_subscribers,
													 sd.unsubscribea AS default_foot, 
													 si.unsubscribea AS custom_foot												
											  FROM " . TABLE_NEWSLETTERS . " n
											  LEFT JOIN " . TABLE_SUBSCRIBERS_INFOS . " si ON si.newsletters_id = n.newsletters_id
											  LEFT JOIN " . TABLE_SUBSCRIBERS_DEFAULT . " sd ON sd.module_subscribers = n.module
											  WHERE n.newsletters_id = '" . (int)$nID . "'
											 ");

			$newsletter = tep_db_fetch_array($newsletter_query);

			$nInfo->objectInfo($newsletter);
		} elseif($HTTP_POST_VARS) {
			$nInfo->objectInfo($HTTP_POST_VARS);
		}

		$file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
		$directory_array = array();
		if ($dir = dir(DIR_WS_MODULES . 'newsletters/')) {
			while ($file = $dir->read()) {
				if(!is_dir(DIR_WS_MODULES . 'newsletters/' . $file)) {
          			if(substr($file, strrpos($file, '.')) == $file_extension) {
						$directory_array[] = $file;
					}
				}
			}
			sort($directory_array);
			$dir->close();
		}

		for ($i=0, $n=sizeof($directory_array); $i<$n; $i++) {
			
			$modules_array[] = array('id' => substr($directory_array[$i], 0, strrpos($directory_array[$i], '.')), 'text' => substr($directory_array[$i], 0, strrpos($directory_array[$i], '.')));
		}

		// #
		// # The Main page form
		// #
		
		echo tep_draw_form('newsletter', FILENAME_NEWSLETTERS, (!empty($page) ? 'page=' . $page . '&': '') . 'event=add&action=' . $form_action); 

		//# if updating (and not inserting new) create a hidden input for nID and POST during submission.
		if ($form_action == 'update') echo tep_draw_hidden_field('newsletter_id', $nID); 
 

		//# if the newsletter has HTML contents, run it through DOMDocument for preview and thumb creation.
		if(!empty($hasContent)) {

			echo '<div id="thenews" style="position:absolute; display:none; top:0; left:0;">';
	
			$email_foot = (!empty($newsletter['custom_foot'])) ? $newsletter['custom_foot'] : $newsletter['default_foot'];

			// # remove any body tags
			$content = preg_replace('/<body.*?>/i', '', $newsletter['content']);
			$email_foot = str_replace(array("\r\n","\n","\r"), "\n", $email_foot);
			$email_foot = str_replace(array('</body>', '</BODY>', "\n".'</body>'), '', $email_foot);
	
			$html = (!empty($newsletter['content'])) ? $content.$email_foot : '<b>NO CONTENT</b>';
	
			$doc = new DOMDocument();
			$doc->recover = true;
			$doc->strictErrorChecking = false;

			$doc->loadHTML($html);
		
			$div = $doc->createElement('div');
			$body = $doc->getElementsByTagName('body')->item(0);
			while ($body->firstChild){
				$div->appendChild($body->firstChild);
			}
	
			$html = $doc->saveHTML($div);
	
			echo $html . '</div>';
?>

<script src="js/jquery-2.0.3.min.js" type="text/javascript"></script>
<script src="js/html2canvas.js" type="text/javascript"></script>
<script src="js/jquery.plugin.html2canvas.js" type="text/javascript"></script>
<script type="text/javascript">
jQuery.noConflict();
jQuery(document).ready(function(jQuery) {

if(jQuery("#thenews").is(':hidden')) {
	jQuery("#thenews").css({'display':'block', 'visibility':'hidden'});
}

	// # Make these kick ass screenshot!
html2canvas(jQuery("#thenews"), {
	onrendered: function(canvas) {
		var imgURL = canvas.toDataURL("image/png");	
		jQuery.post("newsletters.php?action=insert&nID=<?php echo (isset($_GET['nID']) ? $_GET['nID'] : $nID)?>", {"source":imgURL}, function(data){});
	   		},
			allowTaint:true,
			taintTest:false
	});
setInterval(function(){	jQuery("#thenews").css({'display':'none'}) },1000);
});	
</script>

<?php } ?>

<table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin:0 0 0 10px">
          <tr>
            <td>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
          <tr>
            <td width="100">Mailing List: </td>
            <td>
<?php 

	$new_modules_array = array();

	foreach($modules_array as $key => $val) {
		foreach($val as $node => $value) {
			$value = str_replace('_',' ',$value);
			$value = ucwords($value);
			$new_modules_array[] = $value;
		}
	}
//var_dump($new_modules_array);

	echo tep_draw_pull_down_menu('module', $modules_array, $nInfo->module); ?>
	</td>
          </tr>
          <tr>
            <td colspan="2" height="10"></td>
          </tr>
          <tr>
            <td><?php echo TEXT_NEWSLETTER_TITLE; ?></td>
            <td><?php echo tep_draw_input_field('title', $nInfo->title, '', true); ?></td>
          </tr>
          <tr>
            <td colspan="2" height="10"></td>
          </tr>
          <tr>
            <td><?php echo TEXT_NEWSLETTER_FROM; ?></td>
            <td><?php echo tep_draw_input_field('fromMail', ($nInfo->fromMail != STORE_OWNER_EMAIL_ADDRESS ? $nInfo->fromMail : STORE_OWNER_EMAIL_ADDRESS), '', true); ?></td>
          </tr>
          <tr>
            <td colspan="2" height="10"></td>
          </tr>
          <tr>
            <td><?php echo TEXT_NEWSLETTER_SUBJECT; ?></td>
            <td><?php echo tep_draw_input_field('subject', $nInfo->subject, 'style="width:80%;"', true); ?></td>
          </tr>
          <tr>
            <td colspan="2" height="10"></td>
          </tr>
          <tr>
            <tr>
			<td>Event date:</td>
            <td>
			
<table border="0" cellspacing="0" cellpadding="0">
<tr>
			<td style="padding-right:5px;"><input type="text" name="event_time" onclick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?php echo ($nInfo->date_scheduled == '0000-00-00 00:00:00' || empty($nInfo->date_scheduled)) ? date('m/d/Y', strtotime(' +1 day')) : date('m/d/Y', strtotime($nInfo->date_scheduled))?>" size="12" maxlength="22" textfield></td> 
			<td valign="top" style="padding:0 15px 0 0"><img src="images/calander2.gif" border="0" alt="" style="cursor:pointer;" onclick="self.popUpCalendar(document.newsletter.event_time,document.newsletter.event_time,'mm/dd/yyyy',document);"></td>
            <td style="padding:0 5px 0 0;">Priority: </td><td><?php echo tep_draw_input_field('priority', $nInfo->priority, 'style="text-align:center; background-color:#FFFFC4;" size="2" maxlength="2"', true); ?></td>
</tr></table>
</td>
          </tr>
</table>
</td>
<td>
<?php 
if(!empty($hasContent)) {
	echo '<img src="images/preview.png" width="113" height="41" alt="" class="preview" id="preview_button">';
}
?>
</td>
          </tr>
</table>

<table border="0" cellspacing="0" cellpadding="5" width="100%">
	<tr>
		<td style="padding:0 10px 0 10px"><br><?php echo TEXT_NEWSLETTER_CONTENT; ?><br>
	
<?php 
	echo tep_draw_textarea_field('content', 'soft id="content" style="width:100%"', '20', '20', $nInfo->content);?>
<br>
<br>
<img src="/admin/images/button_addtext-green.png" id="addPlainText" style="cursor:pointer;"> <?php if(!empty($email_foot)) echo '<img src="/admin/images/button_editfoot-orange.png" id="editFooter" style="cursor:pointer;">';?>

<div id="plainTextBox" style="display:none;">
<br>
Plain text version: <input type="button" value="HTML &gt;&gt; Text" onclick="html2text(this.form.content,this.form.content_text)"><br>

<?php echo tep_draw_textarea_field('content_text', 'soft id="content_text"', '20', '15', $nInfo->content_text); ?>
</div>
<div id="editFooterDiv" style="display:none;">
<?php echo tep_draw_textarea_field('email_footer', 'soft id="email_footer"', '20', '15', $email_foot); ?>
</div>
</td>
          </tr>

        </table>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding: 10px 10px 0 10px">
			<tr>
				<td style="font:normal 12px arial; color:#666">
Google Analytics Tracking URLs automatically added to all html href tags. <a href="http://support.google.com/googleanalytics/bin/answer.py?hl=en&answer=55578" target="_blank">Reference</a>
</td> <td nowrap align="right">
<?php 

//echo (($form_action == 'insert') ? tep_image_submit('button_save.gif', IMAGE_SAVE) : tep_image_submit('button_update.gif', IMAGE_UPDATE)).'

echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, (!empty($page) ? 'page=' . $page . '&' : '') . (isset($_GET['nID']) ? 'nID='.$_GET['nID'] : '')) . '"><img src="images/back-red.png" alt="'.IMAGE_CANCEL.'" border="0"></a> &nbsp; <img src="images/publish-green.png" width="220" height="52" alt="Save & Publish" border="0" id="submitContent" style="cursor:pointer">'; 
?>
</td></tr>
</table>
<input type="hidden" name="newsletter_id" value="<?php echo (!empty($_GET['nID']) ? (int)$_GET['nID'] : ''); ?>">

	<script type="text/javascript">
	jQuery.noConflict();
	jQuery(document).ready(function(jQuery) {

	var plainTextOpen = false;
	var emailFootOpen = false;

		jQuery("#addPlainText").click(function() {
			 jQuery("#plainTextBox").slideToggle( "slow", function() {
				// # Animation complete.
				if (jQuery("#plainTextBox").is(":hidden")) {
					plainTextOpen = false;
					if(emailFootOpen) jQuery('body').css( "height", "-=265px" );
				} else {
					plainTextOpen = true;
					if(emailFootOpen) jQuery('body').css( "height", "+=265px" );
				}
				top.resizeIframe('myframe');
			});
			
		});

		jQuery("#editFooter").click(function() {
			 jQuery("#editFooterDiv").slideToggle( "slow", function() {
				// # Animation complete.
				if (jQuery("#editFooterDiv").is(":hidden")) {
					emailFootOpen = false;
					if(plainTextOpen) jQuery('body').css( "height", "-=265px" );
				} else {
					emailFootOpen = true;
					if(plainTextOpen) jQuery('body').css( "height", "+=265px" );
				}
				top.resizeIframe('myframe');	
			});
			
		});

	
		jQuery('#submitContent').click(function(e) {
		   	e.preventDefault();
			var	thePost = '<?php echo FILENAME_NEWSLETTERS . (!empty($page) ? '?page=' . $page . '&': '?') . 'event=add&action=' . $form_action ?>';

			// # get today's date & event date as objects for comparison.
			var currentDate = new Date();
			var eventDate = new Date(jQuery('[name="event_time"]').val());

			// # jump through hoops to get today's date.
			var d = currentDate.getDate();
			var m = currentDate.getMonth() + 1;
			var Y = currentDate.getFullYear();
			var todaysDate = m+'/'+d+'/'+Y;

			if((eventDate <= currentDate) || jQuery('[name="event_time"]').val() == '') {
				alert('Your Event date must be greater then today\'s date of '+todaysDate+'. \nTry tomorrow\'s date!');
			} else if(jQuery('[name="title"]').val() ==''){
				alert('Your must specify a Title.');
			} else if (jQuery('[name="subject"]').val() == ''){ 
				alert('Your must specify a Subject Line! \nTry using vars like [customer_firstname] and [store_name]');
			} else if (jQuery('[name="priority"]').val() == ''){ 
				alert('You must specify a Priority! \nThis can be 0 through 10, with 0 being the highest priority. ');
			} else { 
				jQuery.post(thePost, jQuery("form").serialize(), function () {
					alert('Email Saved');
				});
			}
		}); // # end submit form and refresh click function
	
	});

</script>
 </form>
</td>
     </tr>

<?php
  } elseif ($action == 'send') {

		$nID = tep_db_prepare_input($_GET['nID']);

		$newsletter_query = tep_db_query("SELECT n.*, 
												 si.module_subscribers,
												 sd.unsubscribea AS default_foot, 
												 si.unsubscribea AS custom_foot												
										  FROM " . TABLE_NEWSLETTERS . " n
										  LEFT JOIN " . TABLE_SUBSCRIBERS_INFOS . " si ON si.newsletters_id = n.newsletters_id
										  LEFT JOIN " . TABLE_SUBSCRIBERS_DEFAULT . " sd ON sd.module_subscribers = n.module
										  WHERE n.newsletters_id = '".(int)$nID."'
										 ");

		$newsletter = tep_db_fetch_array($newsletter_query);

		$nInfo = new objectInfo($newsletter);

		include(DIR_WS_LANGUAGES . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
		include(DIR_WS_MODULES . 'newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));

		$module_name = $nInfo->module;

		$email_foot = (!empty($nInfo->custom_foot)) ? $nInfo->custom_foot : $nInfo->default_foot;

		$module = new $module_name($nInfo->newsletters_id, $module_name, $nInfo->title, $nInfo->fromMail, $nInfo->subject, $nInfo->content, $email_foot);

echo '<tr>
        <td>';

		if ($module->show_choose_audience) { 
			echo $module->choose_audience(); 
		} else { 
			echo $module->confirm(); 
		}
//echo (isset($module->show_choose_audience)) ? $module->choose_audience() : $module->confirm();
?>

</td>
      </tr>


<?php
  } elseif ($action == 'confirm') {
		$nID = tep_db_prepare_input($_GET['nID']);

		$newsletter_query = tep_db_query("SELECT n.*, 
												 si.module_subscribers,
												 sd.unsubscribea AS default_foot, 
												 si.unsubscribea AS custom_foot												
										  FROM " . TABLE_NEWSLETTERS . " n
										  LEFT JOIN " . TABLE_SUBSCRIBERS_INFOS . " si ON si.newsletters_id = n.newsletters_id
										  LEFT JOIN " . TABLE_SUBSCRIBERS_DEFAULT . " sd ON sd.module_subscribers = n.module
										  WHERE n.newsletters_id = '" . (int)$nID . "'
										  ");

		$newsletter = tep_db_fetch_array($newsletter_query);

		$nInfo = new objectInfo($newsletter);

		include(DIR_WS_LANGUAGES . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
		include(DIR_WS_MODULES . 'newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));

		$module_name = $nInfo->module;

		$email_footer = (!empty($nInfo->custom_foot)) ? $nInfo->custom_foot : $nInfo->default_foot;

		$module = new $module_name($nInfo->newsletters_id, $nInfo->module_subscribers, $nInfo->title, $nInfo->fromMail, $nInfo->subject, $nInfo->content, $email_footer);

?>
      <tr>
        <td><?php echo $module->confirm(); ?></td>
      </tr>
<?php
  } elseif ($action == 'confirm_send') {

		$nID = tep_db_prepare_input($_GET['nID']);

		$newsletter_query = tep_db_query("SELECT n.*, 
												 si.module_subscribers,
												 sd.unsubscribea AS default_foot, 
												 si.unsubscribea AS custom_foot												
										  FROM " . TABLE_NEWSLETTERS . " n
										  LEFT JOIN " . TABLE_SUBSCRIBERS_INFOS . " si ON si.newsletters_id = n.newsletters_id
										  LEFT JOIN " . TABLE_SUBSCRIBERS_DEFAULT . " sd ON sd.module_subscribers = n.module
										  WHERE n.newsletters_id = '" . (int)$nID . "'
										  ");

		$newsletter = tep_db_fetch_array($newsletter_query);

		$nInfo = new objectInfo($newsletter);

		include(DIR_WS_LANGUAGES . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
		include(DIR_WS_MODULES . 'newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));

		$module_name = $nInfo->module;

		$nInfo->content = preg_replace('~>\s+<~', '><', $nInfo->content);

		$email_footer = (!empty($nInfo->custom_foot)) ? $nInfo->custom_foot : $nInfo->default_foot;

		$module = new $module_name($nInfo->newsletters_id, $nInfo->module_subscribers, $nInfo->title, $nInfo->fromMail, $nInfo->subject, $nInfo->content, $email_footer);
	
	    $newsletterCount =  tep_db_num_rows(tep_db_query("SELECT `email` FROM ".TABLE_NEWSLETTER_QUEUE." WHERE newsletters_id = '".(int)$nID."'"));

		echo '<tr>
        		<td>
					<div><b>'. ($newsletterCount > 0 ? TEXT_PLEASE_WAIT :'') . '</b></div>
				</td>
			 </tr>';

	tep_set_time_limit(0);
	flush();
	$module->send($nInfo->newsletters_id);

echo '<tr>
        <td>Emails left to send: '.$newsletterCount .'</td>
      </tr>
      <tr>
        <td class="main" style="padding: 0 0 10px 0">';
if($newsletterCount == 0) echo '<font color="#ff0000"><b>'. TEXT_FINISHED_SENDING_EMAILS .'</b></font>';
echo '</td>
</tr>
      <tr>
        <td align="center"><a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $_GET['nID']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a></td>
      </tr>';


	// # END SEND AND CONFIRM HERE
  } else {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="4">
          <tr>
            <td valign="top">

<table width="100%" cellspacing="0" cellpadding="5" border="0" class="newsTable">
              <tr class="dataTableHeadingRow">
			<th class="dataTableHeadingContent" align="center">Preview</th>
			<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_NEWSLETTERS; ?></th>
			<th class="dataTableHeadingContent" align="left" style="padding:0 10px">Date Scheduled</th>
			<th class="dataTableHeadingContent" align="left" style="padding:0 10px">Mailing List</th>
			<th class="dataTableHeadingContent" align="center">Priority</th>
			<th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></th>
              </tr>
<?php

	$newsletters_query_maxrows = 7;
	//$newsletters_query_maxrows = (!defined('MAX_DISPLAY_SEARCH_RESULTS') ? 7 : MAX_DISPLAY_SEARCH_RESULTS);

    $newsletters_query_raw = "SELECT n.newsletters_id, 
									 n.title, 
									 n.subject, 
									 LENGTH(n.content) AS content_length, 
									 n.module, 
									 n.content, 
									 n.content_text, 
									 n.date_added, 
									 n.date_sent, 
									 n.date_scheduled, 
									 n.status, 
									 n.priority,
									 n.send_count,
									 SUM(ns.view_count) AS view_count,
									 SUM(ns.click_count) AS click_count,
									 SUM(ns.conversions) AS conversions,
									 SUM(ns.conv_amount) AS conv_amount,
									 SUM(ns.unsubscribed) AS unsubscribed
							  FROM " . TABLE_NEWSLETTERS . " n
							  LEFT JOIN newsletter_stats ns ON ns.newsletters_id = n.newsletters_id
							  GROUP BY n.newsletters_id
							  ORDER BY n.date_added DESC";



    $newsletters_split = new splitPageResults($page, $newsletters_query_maxrows, $newsletters_query_raw, $newsletters_query_numrows);

    $newsletters_query = tep_db_query($newsletters_query_raw);

    while ($newsletters = tep_db_fetch_array($newsletters_query)) {
		$newsletters_module = str_replace('_',' ',$newsletters['module']);
		$newsletters_module = ucwords($newsletters_module);

    if ((!isset($_GET['nID']) || (isset($_GET['nID']) && ($_GET['nID'] == $newsletters['newsletters_id']))) && !isset($nInfo) && (substr($action, 0, 3) != 'new')) $nInfo = new objectInfo($newsletters);


      if (isset($nInfo) && is_object($nInfo) && ($newsletters['newsletters_id'] == $nInfo->newsletters_id) ) {
			echo '<tr class="dataTableRowSelected" id="defaultSelected">' . "\n";
      } else {
			echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\''.tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $newsletters['newsletters_id']).'\'" style="cursor:pointer">' . "\n";
      }
?>
		<td class="dataTableContent" align="center" style="width:60px; padding-top 2px;">
<?php 
		// # If screenshot of newsletter exists, then display.
		$theThumb = $_SERVER['DOCUMENT_ROOT'].'/admin/images/nID_'.$newsletters['newsletters_id'].'.jpg';
		if(file_exists($theThumb) && isset($nInfo) && is_object($nInfo) && ($newsletters['newsletters_id'] == $nInfo->newsletters_id)) { 
		echo '<img src="/admin/images/nID_'.$newsletters['newsletters_id'].'.jpg" border="0" alt="'.$newsletters['title'].'" title="'.$newsletters['title'].'" width="50" class="preview">';

		// # if screenshot exists, but row is not selected, skip preview link
		} elseif(file_exists($theThumb) && $newsletters['newsletters_id'] != $_GET['nID']) { 
	echo '<img src="/admin/images/nID_'.$newsletters['newsletters_id'].'.jpg" border="0" alt="'.$newsletters['title'].'" title="'.$newsletters['title'].'" width="50">';

		// # if the preview thumb doesnt exist show the link to the edit page / action.
		} elseif(!file_exists($theThumb)) {
		echo '<a href="'. tep_href_link(FILENAME_NEWSLETTERS, 'page='.$page.'&nID='.$newsletters['newsletters_id']. '&action=new') . '"><img src="images/icons/addplus.png" width="32" height="32" border="0" alt=""> </a>';
		}

?>
			</td>
			<td class="dataTableContent"><?php echo $newsletters['title']; ?></td>
			<td class="dataTableContent" align="left" style="padding:0 10px"><?php echo date('m/d/Y - ga', strtotime($newsletters['date_scheduled'])); ?></td>
			<td class="dataTableContent" align="left" style="padding:0 10px;"><?php echo $newsletters_module; ?></td>
			<td class="dataTableContent" align="center"><?php echo $newsletters['priority']; ?></td>
			<td class="dataTableContent" align="center">

	<?php 

		// # calculate percentage of completion based on send count and queue total
		$send_count = tep_db_result(tep_db_query("SELECT COUNT(0) FROM ". TABLE_NEWSLETTERS_QUEUE ." WHERE newsletters_id = '".$newsletters['newsletters_id']."'"), 0);
		$total_send_count = tep_db_result(tep_db_query("SELECT COALESCE(send_count,0) FROM ". TABLE_NEWSLETTERS ." WHERE newsletters_id = '".$newsletters['newsletters_id']."'"), 0);

		$percentDone = ($send_count > 0 ? number_format(($total_send_count-$send_count)/$total_send_count * 100, 0, '.', '') : '100') . '% Complete';

		if($newsletters['status'] == 'pending') { 
			echo tep_image(DIR_WS_ICONS . 'queued.png', 'Pending / Scheduled: '. date('m/d/Y - ga', strtotime($newsletters['date_scheduled']))); 
		} elseif($newsletters['status'] == 'processing') { 
			echo tep_image(DIR_WS_ICONS . 'processing.gif', 'Sending Now: '. date('m/d/Y - ga', strtotime($newsletters['date_sent']))); 
			echo '<div style="padding: 10px 2px 0 2px;">' . $percentDone . '</div>';
		} elseif($newsletters['status'] == 'completed') { 
			echo tep_image(DIR_WS_ICONS . 'complete.png', 'Completed: '. date('m/d/Y - ga', strtotime($newsletters['date_sent']))); 
			echo '<div style="padding: 10px 2px 0 2px;">' . $percentDone . '</div>';
		} elseif($newsletters['status'] == 'partial') { 
			echo tep_image(DIR_WS_ICONS . 'partial.png', 'Date Sent: '. date('m/d/Y - ga', strtotime($newsletters['date_sent']))); 
			echo '<div style="padding: 10px 2px 0 2px;">' . $percentDone . '</div>';
		} elseif($newsletters['status'] == 'canceled') { 
			echo tep_image(DIR_WS_ICONS . 'canceled.png', 'Mailing Canceled'); 
			echo '<div style="padding: 10px 2px 0 2px;">' . $percentDone . '</div>';
		} elseif($newsletters['status'] == 'draft') { 
			echo tep_image(DIR_WS_ICONS . 'draft.png', 'Saved as Draft'); 
		} elseif($newsletters['status'] == 'failed') { 
			echo tep_image(DIR_WS_ICONS . 'cross.gif', 'Mailing Failed');
			echo '<div style="padding: 10px 2px 0 2px;">' . $percentDone . '</div>';
		}

	?>
			</td>
		</tr>
<?php
    }
?>
</table>
<table width="100%" cellpadding="5" cellspacing="0" border="0">
              <tr>
                <td colspan="6">

<table border="0" width="100%" cellspacing="0" cellpadding="0">

                 <tr>
                    <td align="right" colspan="2" style="padding:5px 0 15px 0"><?php echo '<a href="'.tep_href_link(FILENAME_NEWSLETTERS, 'action=new').'"><img src="images/newMail-green.png" alt="'.IMAGE_NEW_NEWSLETTER.'"></a>'; ?></td>
                  </tr>
                  <tr>
                    <td class="smallText" valign="top"><?php echo $newsletters_split->display_count($newsletters_query_numrows, $newsletters_query_maxrows, $page, TEXT_DISPLAY_NUMBER_OF_NEWSLETTERS); ?></td>
                    <td class="smallText" align="right"><?php echo $newsletters_split->display_links($newsletters_query_numrows, $newsletters_query_maxrows, MAX_DISPLAY_PAGE_LINKS, $page); ?></td>
                  </tr>
                </table>

</td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>'. $nInfo->title .'</b>');

      $contents = array('form' => tep_draw_form('newsletters', FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $nInfo->newsletters_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $nInfo->title . '</b>');
      $contents[] = array('align' => 'left', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page='. $page . '&nID='. $_GET['nID']) .'">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;



    default:
      if (is_object($nInfo)) {
	
        $heading[] = array('text' => '<b>' . $nInfo->title . '</b>');
		$hasContent = tep_db_result(tep_db_query("SELECT content FROM ".TABLE_NEWSLETTERS." WHERE newsletters_id = '".$nInfo->newsletters_id."'"), 0);
		$preview = (!empty($hasContent)) ? '<img src="images/preview.png" alt="Preview '.$nInfo->title.'" title="Preview - '.$nInfo->title.'" class="preview" id="preview_button"><div id="preview_load" style="padding-top: 10px; height:31px; display:none;">Generating Preview <br> <img src="/admin/images/loading_green.gif" width="100" height="6" style="padding-top:10px;"></div>' : '';

        if ($nInfo->status != 'completed') {

          $contents[] = array('align' => 'center', 'text' => '<table border="0" width="100%" cellspacing="0" cellpadding="0"><tr><td>
<a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page='.$page . '&nID=' . $nInfo->newsletters_id . '&action=new').'" class="newframe"><img src="images/editMail.png" alt="'.IMAGE_EDIT.'" border="0"></a></td><td>'.(($nInfo->status != 'processing') ? '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $nInfo->newsletters_id . '&action=delete') . '"><img src="images/removeMail.png" alt="" border="0"></a>' : '<img style="opacity:0.30" src="images/removeMail.png" alt="">' ) . '</td></tr><tr><td>'.$preview.'</td>
<td>

'.(($nInfo->status != 'processing') ? '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $nInfo->newsletters_id . '&action=send') . '"><img src="images/scheduleMail_button.png" alt="'.IMAGE_SEND.'"></a>' : '<img style="opacity:0.30" src="images/scheduleMail_button.png" alt="">' ) . '
</td>
</tr></table>
'.(($nInfo->status != 'processing') ? '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $nInfo->newsletters_id . '&action=draft') . '"><img src="images/savedraft.png" alt="" title="Save Draft for later" border="0"></a>' : '<img style="opacity:0.30" src="images/savedraft.png" alt="">' ));
        } elseif ($nInfo->status == 'draft') {
          $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page='.$page . '&nID=' . $nInfo->newsletters_id . '&action=new').'"><img src="images/editMail.png" alt="'.IMAGE_EDIT.'" border="0"></a><a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $nInfo->newsletters_id . '&action=delete') . '"><img src="images/removeMail.png" alt="" border="0"></a> <a href="'. tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page .'&nID='. $nInfo->newsletters_id .'&action=ready').'">'.tep_image_button('button_lock.gif', IMAGE_LOCK).'</a>'. $preview);

		$contents[] = array('text' => '<br> Email awaiting lock');
        } elseif ($nInfo->status == 'completed') { 

		 $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page='.$page . '&nID=' . $nInfo->newsletters_id . '&action=new').'"><img src="images/editMail.png" alt="'.IMAGE_EDIT.'" border="0"></a><a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $page . '&nID=' . $nInfo->newsletters_id . '&action=delete') . '"><img src="images/removeMail.png" alt="" border="0"></a>'. (!empty($preview) ? '<br>' .$preview : ''));
			$contents[] = array('text' => '<br> Email succesfully completed');
		}

	// # if the thumb exists, show a recalculated size based on downsized original thumb
	// # Existing or not, recalculate the byte size into kilobytes by dividing by 1024

	if(file_exists($_SERVER['DOCUMENT_ROOT'] .'/admin/images/nID_'.$nInfo->newsletters_id.'.jpg')) {

		$originalImage = filesize($_SERVER['DOCUMENT_ROOT'] .'/admin/images/nID_'.$nInfo->newsletters_id.'.jpg');
		$originalsize = $originalImage / .35;

		$totalMessageSize = number_format((($originalsize + $nInfo->content_length) / 1024),2);

        $contents[] = array('align' => 'center', 'text' => '<br><img src="/admin/images/nID_'.$nInfo->newsletters_id.'.jpg" border="0" alt="'.$nInfo->title.'" title="'.$nInfo->title.'" width="200" class="preview"><br><br>', 'link' => '');

	} else { 
	
		$totalMessageSize = number_format((($nInfo->content_length) / 1024), 2);
	}

	if($nInfo->status == 'completed' && $nInfo->date_sent != NULL) {

		$contents[] = array('text' => '<div style="font:normal 10px arial; color: #000; line-height: 18px">Send count: &nbsp ' . $nInfo->send_count . '
									   <br>View count: &nbsp; '. $nInfo->view_count .'
									   <br>Open rate: &nbsp; '. ($nInfo->view_count  > 0 && $nInfo->send_count > 0 ? number_format((($nInfo->view_count / $nInfo->send_count) * 100),2) : '0') . '%
									   <br>Click count: &nbsp; '. $nInfo->click_count .'
									   <br>Click rate: &nbsp; '. ($nInfo->click_count > 0 ? number_format((($nInfo->click_count / $nInfo->view_count) * 100),2) : '0') . '%
									   <br>Conversions: &nbsp; '. $nInfo->conversions .'
									   <br>Conv. rate: &nbsp; '. ($nInfo->conversions > 0 ? number_format((($nInfo->conversions / $nInfo->send_count) * 100),2) : '0') . '%
									   <br>Conv. total: $'. $nInfo->conv_amount .'
									   <br>Unsubscribed: &nbsp ' . $nInfo->unsubscribed .'
									   <br>Unsubscribe rate: '. ($nInfo->unsubscribed > 0 ? number_format((($nInfo->unsubscribed / $nInfo->send_count) * 100),2) : '0') . '%
									   </div>');	
	}

	if(!empty($hasContent)) {
		$contents[] = array('text' => '<br>' . TABLE_HEADING_SIZE . ': '. $totalMessageSize .' KB &nbsp;(includes images).');	
	}

	$contents[] = array('text' => '<br>' . TEXT_NEWSLETTER_DATE_ADDED . ' ' . tep_date_short($nInfo->date_added));
	$contents[] = array('text' => 'Date Scheduled: ' . tep_date_short($nInfo->date_scheduled));

	if($nInfo->status == 'completed' && $nInfo->date_sent != NULL) {

		$contents[] = array('text' => TEXT_NEWSLETTER_DATE_SENT . ' ' . tep_date_short($nInfo->date_sent));
	}

}
    
	break;


  }
	// # the right side column.
  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '<td width="230" valign="top" style="padding:5px">';

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '</td>' . "\n";
  }
?>
          </tr>
        </table>




</td></tr>
<?php
  }
?>
</table></td>

  </tr>
</table>
<script type="text/javascript" src="js/jquery.lightbox_me.js"></script>
<div id="thepreview" style="display:none;"></div>
<script type="text/javascript">
jQuery.noConflict();
	jQuery(document).ready(function(jQuery) {
		// # preview modal
		var body = jQuery('body').height();

		var thepanelH = window.parent.jQuery(window.parent.document).find('#thePanel').height();
		jQuery('body').height(thepanelH-25);

		jQuery('.preview').click(function(e) {
  			e.preventDefault();

			jQuery('#preview_button').hide();
			jQuery('#preview_load').fadeIn('fast');

			jQuery('#thepreview').load('newsletters.php?preview=1&nID=<?php echo $nInfo->newsletters_id ?>');

			// # added setimeout delay of 3 seconds to allow thepreview div to load before making size adjustments
			setTimeout(function() {

				jQuery('#thepreview').lightbox_me({
    				centered: false, 
       				onLoad: function() { 
   	   	    			jQuery('#thepreview').find('input:first').focus();
							var thepreviewH = jQuery('#thepreview').height();
	
							if(thepreviewH < thepanelH){
								jQuery('body').height(thepanelH);
							} else {
								jQuery('body').height(thepreviewH+25);
							}
		
							top.resizeIframe('myframe');
							jQuery('#preview_load').hide();
							jQuery('#preview_button').show();
						}, // # end onLoad function
	
					onClose: function() {
						jQuery('body').height(thepanelH-25);
						top.resizeIframe('myframe');
					}
				});

			}, 3500);

		}); // # end launch preview click function
	});

</script>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
