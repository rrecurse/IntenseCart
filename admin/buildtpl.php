<?php
// included into categories.php - executed via url var $_GET['buildtpl']
//check for category path 
if(isset($cPath) && $cPath > 0) {
$myFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".$cPath."_".$cInfo->categories_id.".html";
$myCatPath = $cPath."_".$cInfo->categories_id;
$myExistingFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".$cPath."_".($cInfo->categories_id - 1).".html";
} else {
$myFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".$cInfo->categories_id.".html";
$myCatPath = $cInfo->categories_id;
$myExistingFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".($cInfo->categories_id - 1).".html";
}

$fh = fopen($myFile, 'w') or die("can't open file ".$myFile." - check that it exists in your layout directory");

// check for existence of existing template
if (file_exists($myExistingFile)) {
$stringData = file_get_contents ($myExistingFile)."\n";
} else {
// default to index.php.html if no previous template according to category id found.
$stringData = file_get_contents ("/home/".LINUX_USER_NAME."/public_html/layout/index.php.html")."\n";
}

fwrite($fh, $stringData);
fclose($fh);
$lnxuser = LINUX_USER_NAME;
//set permissions and ownership to user
chmod("$myFile", 0777);
//chown($myFile, $lnxuser);
//exec('chmod 0777 $myFile');
exec('chown '.$lnxuser.' '.$myFile.'');
?>
