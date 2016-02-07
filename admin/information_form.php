<?php


	$title = $edit[info_title];
	$replace = array("&", " ", "--");
	$withme = array("", "-", "-");
	$output = str_replace($replace, $withme, $title);
	$hostname = $_SERVER['SERVER_NAME'];

	// # detect if file is include type to hide irrelavent feilds. 
	if(substr($title,0,4) == 'inc_') { 
		$include_file = true;
	} else {
		$include_file = false;
	}

?>

	<tr class="pageHeading">
		<td><?php echo $title ?></td>
	</tr>
	<tr class="dataTableRow">
		<td class="main">
<?php

//echo QUEUE_INFORMATION_LIST;
	$data = browse_information();
	$no = 1;
	if (sizeof($data) > 0) {
		while (list($key, $val)=each($data)) {
		//echo "$val[v_order], ";
		$no++;
		}
	}
?>
		</td>
	</tr>
	<tr>
		<td class="formAreaTitle">
			<table width="100%" border="0" cellpadding="0" cellspacing="2">
				<tr>
        	    	<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
				</tr>
				<tr>
					<td class="formAreaTitle"><?php echo STATUS_INFORMATION;?></td>
					<td class="main"> 
<?php
	if($edit[visible] == 1) {
		echo tep_draw_radio_field('visible', '1', "checked") . '&nbsp;' . INFORMATION_ACTIVE . '&nbsp;' . tep_draw_radio_field('visible', '0') . '&nbsp;' . INFORMATION_DEACTIVE;
	} else {
		echo tep_draw_radio_field('visible', '1') . '&nbsp;' . INFORMATION_ACTIVE . '&nbsp;' . tep_draw_radio_field('visible', '0', "checked") . '&nbsp;' . INFORMATION_DEACTIVE;
	}
?>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="height:10px;"><b></b></td>
				</tr>

				<tr>
					<td class="formAreaTitle">Sort Order:<?//php echo QUEUE_INFORMATION;?></td>
					<td class="formAreaTitle">
<?php 
	if ($edit[v_order]) {
		$no = $edit[v_order];
	}
	
	echo tep_draw_input_field('v_order', "$no", 'size=3 maxlength=4'); 
?>
					</td>
				</tr>
				<tr>
					<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
				</tr>

<?php 
	
	if($include_file === false) { 

		echo '
			<tr>
				<td class="formAreaTitle">HTML Title: </td><td>'. tep_draw_input_field('page_title',$edit['page_title'], 'size="50"') .'</td>
			</tr>
			<tr>
				<td class="formAreaTitle">Meta Description Tag:</td><td>'. tep_draw_input_field('htc_description',$edit['htc_description'], 'size="50"') .'</td>
			</tr>
			<tr>
				<td class="formAreaTitle">Meta Keywords Tag:</td><td>'. tep_draw_input_field('htc_keywords',$edit['htc_keywords'], 'size="50"') .'</td>
			</tr>
			<tr>
            	<td colspan="2">'.  tep_draw_separator('pixel_trans.gif', '1', '10').'</td>
			</tr>';
	}

?>
			<tr>
				<td class="formAreaTitle">Filename:<?php //echo TITLE_INFORMATION;?></td>
				<td class="formAreaTitle"><?php echo tep_draw_input_field('info_title', "$edit[info_title]", 'size=50 maxlength=255'); ?></td>
			</tr>
			<tr>
            	<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '6'); ?></td>
			</tr>

<?php

	if($include_file === false && $_GET['a_information'] != 'Added') { 

		echo '
			<tr>
				<td class="formAreaTitle">Page Location:</td>
				<td class="formAreaTitle">';


		echo '<a href="http://' . $hostname . '/' . $output . '.html" target="_blank">http://' . $hostname . '/' . $output . '.html</a>';

		echo '
				</td>
			</tr>
			<tr>
				<td colspan="2">'. tep_draw_separator('pixel_trans.gif', '1', '10') .'</td>
			</tr>';
	}
?>
			<tr>

				<td colspan="2" class="formAreaTitle">Content:<?php //echo DESCRIPTION_INFORMATION;?><br>
<?php

	$descrip = htmlentities($edit[description], ENT_NOQUOTES, 'UTF-8');

	echo tep_draw_textarea_field('description', '', '100', '15', $descrip, 'style="width:99%; height:350px;"'); 
?>
				</td>
			</tr>

			<tr>
				<td></td>
				<td align="right" style="padding:10px">

<script type="text/javascript">
jQuery.noConflict()
	jQuery(document).ready(function() {
		jQuery(window).keypress(function(event) {
		    if (!(event.which == 115 && event.ctrlKey) && !(event.which == 19)) return true;
		    document.forms["updateinfo"].submit();
		    event.preventDefault();
		    return false;
		});
	});
</script>

<?php
	echo tep_image_submit('button_save.gif', IMAGE_INSERT);
	echo '&nbsp; <a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, '', 'NONSSL') . '">' . tep_image_button('button_back.gif', IMAGE_CANCEL) . '</a>';
 ?>
 


				</td>
			</tr>
		</table>
</form>
        </td></tr>

<?php if (0 || HTML_AREA_WYSIWYG_DISABLE_IPU == 'Enable') { ?>
      <script language="JavaScript1.2" defer>
          // MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.7 Products Description HTML - Body
             var config = new Object();  // create new config object
             config.width = "<?php echo HTML_AREA_WYSIWYG_WIDTH_IPU; ?>px";
             config.height = "<?php echo HTML_AREA_WYSIWYG_HEIGHT_IPU; ?>px";
             config.bodyStyle = 'background-color: <?php echo HTML_AREA_WYSIWYG_BG_COLOUR; ?>; font-family: "<?php echo HTML_AREA_WYSIWYG_FONT_TYPE; ?>"; color: <?php echo HTML_AREA_WYSIWYG_FONT_COLOUR; ?>; font-size: <?php echo HTML_AREA_WYSIWYG_FONT_SIZE; ?>pt;';
             config.debug = <?php echo HTML_AREA_WYSIWYG_DEBUG; ?>;
             editor_generate('description',config);
             </script>
       <?php } ?>
