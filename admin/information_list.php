<tr class="pageHeading">
	<td valign="top" width="40"><img src="/admin/images/webpage_control-icon.png" width="48" height="48"></td>
	<td style="padding:10px;" align="left"> <?php echo $title ?></td>
</tr>
<tr>
	<td colspan="2">

		<table border="0" width="100%"  cellpadding="5" cellspacing="0" bgcolor="#FFFFFF">
			<tr class="dataTableHeadingRow">
		        <td align="center" class="dataTableHeadingContent" style="width:40px;"><?php echo ID_INFORMATION;?></td>
        		<td align="center" class="dataTableHeadingContent" style="width:70px;" nowrap>Sort Order</td>
		        <td align="left" class="dataTableHeadingContent"><?php echo TITLE_INFORMATION;?></td>
        		<td align="center" class="dataTableHeadingContent" style="width:60px;"><?php echo PAGE_PREVIEW;?></td>
		        <td align="center" class="dataTableHeadingContent" style="width:75px;"><?php echo PUBLIC_INFORMATION;?></td>
        		<td align="center" class="dataTableHeadingContent" style="width:60px;"><?php echo EDIT_INFORMATION;?></td>
		        <td align="center" class="dataTableHeadingContent" style="width:60px;"><?php echo DELETE_INFORMATION;?></td>

			</tr>
<?php

 if (sizeof($data) > 0) {

	foreach ($data as $key => $val) { 

?>
			<tr class=" <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd');?>">

				<td align="center" class="dataTableContent"><?php echo $val[information_id];?></td>
				<td align="center" class="dataTableContent"><?php echo $val[v_order];?></td>
				<td align="left" class="dataTableContent">
					<a href="<?php echo HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php?info_id=' . $val[information_id];?>" target="_blank"><?php echo $val[info_title];?>
				</td>
				<td align="center" class="dataTableContent"><a href="<?php echo HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php?info_id=' . $val[information_id];?>" target="_blank"><?php echo tep_image(DIR_WS_ICONS . 'preview.gif', PAGE_PREVIEW . ' = ' . $val[info_title], '', '');?>
				</td>
				<td align="center" class="dataTableContent">
<?php

	if ($val[visible]==1) {

		echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN . ' = ' . $val[info_title], 10, 10) . '&nbsp;&nbsp; <a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Visible&information_id=$val[information_id]&visible=$val[visible]") . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT . ' = ' . $val[info_title], 10, 10) . '</a>';

	} else {

		echo '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Visible&information_id=$val[information_id]&visible=$val[visible]") . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT . ' = ' . $val[info_title], 10, 10) . '</a> &nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED . ' = ' . $val[info_title], 10, 10);

	}
?>
</td>
    <td align=center class="dataTableContent">
<?php echo '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Edit&information_id=$val[information_id]", 'NONSSL') . '">' . tep_image(DIR_WS_ICONS . 'edit.gif', EDIT_ID_INFORMATION . " $val[info_title]") . '</a>'; ?>
</td>
    <td align=center class="dataTableContent">
<?php echo '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Delete&information_id=$val[information_id]", 'NONSSL') . '">' . tep_image(DIR_WS_ICONS . 'delete.gif', DELETE_ID_INFORMATION . " $val[info_title]") . '</a>'; ?>
</td>
   </tr>

<?php
	} // # end foreach

}

?>
 
</table>

<?php 

 if(sizeof($data_inc) > 0) {

?>

<div style="padding:10px 5px; font:bold 14px arial;">Includes:</div>

<table border="0" width="100%"  cellpadding="5" cellspacing="0" bgcolor="#FFFFFF">
<tr class="dataTableHeadingRow">
        <td align="center" class="dataTableHeadingContent" style="width:40px;"><?php echo ID_INFORMATION;?></td>
        <td align="center" class="dataTableHeadingContent" style="width:70px;" nowrap>Sort Order</td>
        <td align="left" class="dataTableHeadingContent" colspan="2"><?php echo TITLE_INFORMATION;?></td>
        <td align="center" class="dataTableHeadingContent" style="width:75px;"><?php echo PUBLIC_INFORMATION;?></td>
        <td align="center" class="dataTableHeadingContent" style="width:60px;"><?php echo EDIT_INFORMATION;?></td>
        <td align="center" class="dataTableHeadingContent" style="width:60px;"><?php echo DELETE_INFORMATION;?></td>

</tr>
<?php foreach ($data_inc as $key => $val) { 
?>

   <tr class=" <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd');?>">
    <td align="center" class="dataTableContent"><?php echo $val[information_id];?></td>
    <td align="center" class="dataTableContent"><?php echo $val[v_order];?></td>
    <td align="left" class="dataTableContent" colspan="2"><a href="<?php echo HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php?info_id=' . $val[information_id];?>" target="_blank"><?php echo $val[info_title];?></td>
    <td align="center" class="dataTableContent">

<?php

	if ($val[visible]==1) {

		echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN . ' = ' . $val[info_title], 10, 10) . '&nbsp;&nbsp; <a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Visible&information_id=$val[information_id]&visible=$val[visible]") . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT . ' = ' . $val[info_title], 10, 10) . '</a>';

	} else {

		echo '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Visible&information_id=$val[information_id]&visible=$val[visible]") . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT . ' = ' . $val[info_title], 10, 10) . '</a> &nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED . ' = ' . $val[info_title], 10, 10);

	}
?>
</td>
    <td align=center class="dataTableContent">
<?php echo '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Edit&information_id=$val[information_id]", 'NONSSL') . '">' . tep_image(DIR_WS_ICONS . 'edit.gif', EDIT_ID_INFORMATION . " $val[info_title]") . '</a>'; ?>
</td>
    <td align=center class="dataTableContent">
<?php echo '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, "a_information=Delete&information_id=$val[information_id]", 'NONSSL') . '">' . tep_image(DIR_WS_ICONS . 'delete.gif', DELETE_ID_INFORMATION . " $val[info_title]") . '</a>'; ?>
</td>
   </tr>

<?php
	} // # end foreach

echo '</table>';
} 
?>

<?php if(sizeof($data) < 1 || sizeof($data_inc) < 1) { 

echo '<table border="0" width="100%" cellpadding="5" cellspacing="0" bgcolor="#FFFFFF">
  <tr bgcolor="#DEE4E8">
    <td class="main">'. ALERT_INFORMATION.'</td>
   </tr>
</table>';
}
?>

	</td>
</tr>
<tr>
	<td align="right" colspan="2" style="padding:20px 10px 0 0;">
		<?php echo '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER, 'a_information=Added', 'SSL') . '">' . tep_image_button('button_new_file.gif', ADD_INFORMATION) . '</a>'; ?>
	</td>
</tr>
<!--tr>
<td colspan="2"><hr size="1" width="99%"></td>
</tr>
<tr>
<td valign="top" width="40"><img src="/admin/images/file_manager-icon.png" width="48" height="48"></td><td style="padding:10px;"><a href="file_manager.php" style="font:bold 17px Arial; color: #053389;">File Manager</a></td>
</tr-->