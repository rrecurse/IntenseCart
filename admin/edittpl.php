<?php
if(isset($cPath) && $cPath > 0) {
$myFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".$cPath."_".$cInfo->categories_id.".html";
$myCatPath = $cPath."_".$cInfo->categories_id;
$myExistingFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".$cPath."_".($cInfo->categories_id - 1).".html";
} else {
$myFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".$cInfo->categories_id.".html";
$myCatPath = $cInfo->categories_id;
$myExistingFile = "/home/".LINUX_USER_NAME."/public_html/layout/index.php_".($cInfo->categories_id - 1).".html";
}
if ($changefile) {
$slash = stripslashes($_POST['edittpl']);
$filetochange = $myFile;
$filetochangeOpen = fopen($filetochange,"w") or die ("Error editing.");
fputs($filetochangeOpen,$slash);
fclose($filetochangeOpen) or die ("Error Closing File!");
}

?>

<div id="light" class="white_content" style="display:none;">

<script type="text/javascript">
<!--
function confirmation() {
	var answer = confirm('are you sure you want to close without saving?')
	if (answer){
document.getElementById('light').style.display='none';document.getElementById('fade').style.display='none';	
	} else {
return false;
	}
}
//-->
</script>

<form method="post" action="" name="myform" style="margin:0">
<div style="width:100%; text-align:right;"><div style="float:left; padding-top:5px; font:bold 12px arial">Now editing: index.php_<?php echo $myCatPath;?>.html</div><div style="float:right;padding-bottom:5px">
<a href="javascript:void(0);" onclick="confirmation()"><img src="images/lightbox_close_button.png" alt="" width="25" height="29"></a></div>
</div>
<textarea rows="40" cols="10" name="edittpl" style="width:100%; height:89%; font:normal 11px arial">
<?php
// Implode CSS
$filetochange = $myFile;
print (implode("",file($filetochange)));
?>
</textarea><br><br>
<input type="image" src="includes/languages/english/images/buttons/button_save.gif" value="save" name="changefile"> <img src="includes/languages/english/images/buttons/button_cancel.gif" style="cursor:pointer;" onclick="confirmation();">
</form>
</div>

<div id="fade" class="black_overlay"></div>
