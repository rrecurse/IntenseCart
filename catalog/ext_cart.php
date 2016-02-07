<?php

// Remember current directory
$orig_dir=getcwd();


//edit next line to change to your catalogue directory ( the .. goes up a directory level)
chdir('/usr/share/IXcore/catalog');

include('includes/application_top.php');

ob_start(); //start output buffering
// You can change the next line to use whatever box you require for example product info,best sellers,whats new,etc
//include(DIR_WS_BOXES.'best_sellers.php');//include another file for the box you want

//include(DIR_WS_BOXES.'specials.php');
include(DIR_WS_BOXES.'remote_cart.php');

$box_buffer=ob_get_contents();//save output
ob_end_clean();//stop output buffering

//Correct image paths, edit next line for your site details
$box_buffer=str_replace("src=\"images", "src=\"http://www.zwaveproducts.com/images",$box_buffer);

$box_buffer=str_replace("'", "\'" ,$box_buffer);	// escape qoutes
$box_buffer=str_replace("\n", "') \n document.write('" ,$box_buffer);  // add newlines
// alter table size if required
echo "document.write('" . '<table border="0" width="160" cellspacing="0" cellpadding="0">' . "')\n";
echo "document.write('" . $box_buffer ."')\n";
echo "document.write(' </table>')\n";
chdir($orig_dir);//move back to original directory
?>
