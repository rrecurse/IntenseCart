<?php
class dash_box_marketing_tips
	{
		var $table_cols=2;
	 	var $table_rows=1;
		var $title="Marketing Tips";

  		function render() 
		{
?>
<div>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
                     <tr>
                       <td style="height:16px; background-color:#6295FD; font:bold 11px arial; color:#FFFFFF;">&nbsp; Today's
                         Marketing Tips:</td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#19487E;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#FFFFFF;"></td>
                     </tr>
                     <tr>
                       <td style="height:1px; background-color:#8CA9C4;"></td>
	      </tr>
                     <tr>
                       <td valign="top" style="padding-top:3px;"><table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top" style="padding:5px 5px 5px 10px; color:#333333; font: bold 11px arial; height:100px;">

 <?php require_once('feed2html.php');  ?> 

</td>
  </tr>
</table>
</td>
</tr>
</table>
</div>
<?
  }
}
?>

