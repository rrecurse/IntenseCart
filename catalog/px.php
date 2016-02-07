<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

// # Email pixel tracking


require('includes/application_top.php');


// # Create an true color image, 1x1 pixel in size
$image = imagecreatetruecolor(1,1);

// # set transparency to 100%
imagealphablending($image,true);

// # Create alpha channel for transparent layer
$col = imagecolorallocatealpha($image,255,255,255,127);

// # Allocate the background color
imagesetpixel($image,1,1,$col);
imagesavealpha($image,true);

// # Set the image type
header("content-type:image/png");

// # Avoid cache time on browser side
header("Content-Length: 42");
header("Cache-Control: private, no-cache, no-cache=Set-Cookie, proxy-revalidate");
header("Expires: Wed, 11 Jan 2000 12:59:00 GMT");
header("Last-Modified: Wed, 11 Jan 2010 12:59:00 GMT");
header("Pragma: no-cache");

// # Create a JPEG file from the image
imagepng($image);

// # Free memory associated with the image
imagedestroy($image);

// # insert trackable data into database.

if((isset($_GET['ref']) && $_GET['ref'] == 'email') && !empty($_GET['email']) && !empty($_GET['nID'])) {

	$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? tep_db_input($_SERVER['REMOTE_ADDR']) : '';
	$useragent = (!empty($_SERVER['HTTP_USER_AGENT'])) ? tep_db_input($_SERVER['HTTP_USER_AGENT']) : '';
	$email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
	$newletters_id = (int)$_GET['nID'];


	$view_check = tep_db_query("SELECT newsletters_id, email, ip, user_agent
							   FROM ".TABLE_NEWSLETTER_STATS."
							   WHERE newsletters_id = '".$newletters_id."'
							   AND email = '".$email."'
							   AND ip = '".$ip."'
							   AND user_agent = '".$useragent."'
							  ");

	if(tep_db_num_rows($view_check) > 0) { 

		tep_db_query("UPDATE ".TABLE_NEWSLETTER_STATS."
     				 SET last_view = NOW(),
     				 view_count = (view_count + 1)
					 WHERE email = '".$email."'
					 AND newsletters_id = ".$newletters_id."
					 AND ip = '".$ip."'
					 AND user_agent = '".$useragent."'
					");
	} else { 

		tep_db_query("INSERT INTO ".TABLE_NEWSLETTER_STATS."
        			  SET newsletters_id = ".tep_db_input($newletters_id).",
					  email = '".$email."',
					  last_view = NOW(),
					  ip = '".$ip."',
					  user_agent = '".$useragent."',
					  view_count = (view_count + 1)
					");
	}
}
?>