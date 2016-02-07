<?php

// README CONFIGURATION BOF ################################
/*

	There are 3 different button types in the button section,
	Ordering tracking number prompt button, Date prompt button, 
	and regular button. It should not be hard to identify which
	is which as the order and date prompt buttons contain
	more javascript, so you can cut and paste or change these
	buttons to your liking. Do not forget you have to change
	numbers in the button code to select proper order status as well
	(Second number passed to function after TEXT_COMMENT_XX).


*/
// README CONFIGURATION EOF ################################

 require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_COMMENT_BAR);
?>

<style type="text/css">
.cbutton { width: 90px; font-family: Verdana; font-size: 9px; padding: 0px; background-color: #FFFFFC; border-bottom: 2px solid #003366; border-right: 1px solid #003366; border-top: 1px solid #003366; border-left: 1px solid #003366; cursor: pointer; cursor: hand; }
</style>
<script language="javascript"><!--
	var usrdate = '';
   function updateComment(obj,statusnum) {
			var textareas = document.getElementsByTagName('textarea');
			var myTextarea = textareas.item(0);
			{
			myTextarea.value = obj;
			}
			if (statusnum!=null) {
			  var selects = document.getElementsByTagName('select');
			  var theSelect = selects.item(0);
			  theSelect.selectedIndex = statusnum;
			}

			return false;
			
   }

   function killbox() {
			var box = document.getElementsByTagName('textarea');
			var killbox = box.item(0);
			killbox.value = '';
			return false;

	}

	function getdate () {
			usrdate = prompt("<?php echo TEXT_PROMPT; ?>"); 

	}

	function getrack () {
			usrtrack = prompt("<?php echo TEXT_TRACKNO; ?>"); 		

	}
//--></script>
</head>
      <table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td colspan="3" align="left" style="font-family: verdana; font-size: 9px; font-weight: bold;">
			<font color="#0033CC">Add Predefined Comments</font>
			</td>
		</tr>
      <table border="0" width="100%" cellspacing="0" cellpadding="0">
	  <tr>

<!-- Button Section -->
       <button class="cbutton" onClick="getrack(); return updateComment('<?php echo(TEXT_COMMENT_01); ?>' + usrtrack,'2');"><?php echo TEXT_BUTTON_01; ?></button>&nbsp;
       <button class="cbutton" onClick="getrack(); return updateComment('<?php echo(TEXT_COMMENT_02); ?>' + usrtrack,'2');"><?php echo TEXT_BUTTON_02; ?></button>&nbsp; 
       <button class="cbutton" onClick="return updateComment('<?php echo(TEXT_COMMENT_03); ?>');"><?php echo TEXT_BUTTON_03; ?></button>&nbsp;
       <button class="cbutton" onClick="getdate(); return updateComment('<?php echo(TEXT_COMMENT_04); ?>' + usrdate,'1');"><?php echo TEXT_BUTTON_04; ?></button>&nbsp;
       <button class="cbutton" onClick="return updateComment('<?php echo(TEXT_COMMENT_05); ?>','0');"><?php echo TEXT_BUTTON_05; ?></button> 
	   <!--  <button class="cbutton" onClick="return updateComment('<?php echo(TEXT_COMMENT_06); ?>','0');"><?php echo TEXT_BUTTON_06; ?></button>&nbsp; 
 <button class="cbutton" onClick="return updateComment('<?php echo(TEXT_COMMENT_07); ?>','0');"><?php echo TEXT_BUTTON_07; ?></button>&nbsp; 
       <button class="cbutton" onClick="return updateComment('<?php echo(TEXT_COMMENT_08); ?>','0');"><?php echo TEXT_BUTTON_08; ?></button>&nbsp;
       <button class="cbutton" onClick="return updateComment('<?php echo(TEXT_COMMENT_09); ?>','0');"><?php echo TEXT_BUTTON_09; ?></button>&nbsp;
       <button class="cbutton" onClick="return updateComment('<?php echo(TEXT_COMMENT_10); ?>','0');"><?php echo TEXT_BUTTON_10; ?></button>&nbsp;
//--><button class="cbutton" onClick="return killbox();"><?php echo TEXT_BUTTON_RESET; ?></button>
</td>
		</tr>

    </table></td>

