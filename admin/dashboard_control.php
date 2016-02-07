<?php
require('includes/application_top.php');

include(DIR_WS_INCLUDES.'header.php'); //DIR_WS_INCLUDES.

define('DIR_FS_DASH_BOX',DIR_FS_MODULES.'dash_box/');

function get_title($table_name)
	{
		$table_file=DIR_FS_DASH_BOX.$table_name.".php";
		include($table_file);
		$str = $table_name;
		$obj = new $str();
		$table_title=$obj->title;
		return $table_title;
	}

if(empty($task))$task=1;
// delete from table
if ($task=="3")
	{
		if($desc=="new")
			{	if ($dashboard!="" && $dashboard!="0")
					{
						$sql="select dash_table, sort_order from admin_dash where dash_name='$dashboard'";
						$result=tep_db_query($sql);
						$row=tep_db_num_rows($result);
						if($row=="0") $desc="update"; //not exist
						else
							{
								echo '<font size="2" color="red">Dashboard with  name '.$dashboard. ' exists already.</font><br/>';
								$task="2";
								$dashboard="0";
							}
					}
				else 
					{
						echo '<font size="2" color="red">The name of dashboard cannot be empty.</font><br/>';
						$task="2";
						$dashboard="0";
					}
			}
		if($desc=="delete")
			{
				$sql="delete from admin_dash where dash_name='$dashboard'";
				$result=tep_db_query($sql);
				if ($page=="admin")	$task="1";
				else $desc="update";
					
			}
		if($desc=="update")
			{	
				$count=0;
				$arr=array("", "0");
				$no_empty=array_diff($order, $arr); 
				$num_order=count($no_empty); 
				for ($i=0; $i<$up_num; $i++)
					{	
						$stre = "enable".$i; 
						if ($$stre=="1")
							{	
								if(empty($order[$i]))
									{
										$order[$i]=$num_order+1;
										$num_order++;
									}
								$sql="insert into admin_dash (dash_name,dash_table,sort_order,dash_permission) values ('$dashboard', '$up_table[$i]', '$order[$i]', '$dash_permission')";
								$result=tep_db_query($sql);
							}
					}
				$task="1";
			}
		
	}
// select unique dash_names
if ($task=="1")
	{
	$sql="select dash_name from admin_dash";
	$result=tep_db_query($sql);
	$count=0;
	while ($row=tep_db_fetch_array($result))
		{	
			$dash[$count]="";
			$identity=0;
			for ($i=0; $i<=$count; $i++)
			{
				if ($dash[$i]==$row['dash_name'])
					{
						$identity=1;
						break;
					}
					
			}
			if ($identity==0)
				{
					$dash[$count]=$row['dash_name'];
					$count++;
				}
		}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Dashboard Control</title>
<link rel="stylesheet" href="js/tabber.css" TYPE="text/css" MEDIA="screen">
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="STYLESHEET" href="/admin/js/css.css" type="text/css">
</head>

<body style="background:transparent; margin:10px">
<table width="551" cellpadding="0" cellspacing="0" border="0" style="padding: 0 0 15px 0;">
<tr>
<td width="58"><img src="images/roles-icon.png" width="48" height="48"></td> <td class="pageHeading"> Dashboard Manager</td>
</tr>
</table>
<table width="551" cellpadding="5">
<tr style="background-color:#6296FC;">
<td style="color:#FFFFFF;"><b>Dashboard Name</b></td> <td align="center" style="color:#FFFFFF;"><b>Update</b></td> 
<td align="center" style="color:#FFFFFF;"><b>Remove</b></td>
</tr>
</table>
<table width="551" cellpadding="5">
<?php 
	if ($count==0)
		{
?>
	<tr>
		<td colspan="3">You have no dashboards...... </td>
	</tr>
<?php
		}
	else
		{
?>
	<tr>
		<td height="10" colspan="3"></td>
	</tr>
<?php
	for ($i=0; $i<$count; $i++)
	{
		$phrase="Dashboard ".$dash[$i]." will be removed for ever!";
?>	

	
	<tr>
		<td><?=$dash[$i]?></td>
		<td width="115" align="center">

<table cellpadding="3"><tr><td><a href="./dashboard_control.php?dashboard=<?=$dash[$i]?>&task=2&desc=edit">Edit</a></td><td><a href="./dashboard_control.php?dashboard=<?=$dash[$i]?>&task=2&desc=edit"><img src="images/accept-icon.gif" width="14" height="14" border="0"></a></td></tr></table>

</td>
		
		<td width="134" align="center"><table cellpadding="3"><tr><td>
<a href="./dashboard_control.php?dashboard=<?=$dash[$i]?>&task=3&desc=delete&page=admin"
			onClick="return window.confirm('<?=$phrase?>')">Delete</a></td>
<td><a href="./dashboard_control.php?dashboard=<?=$dash[$i]?>&task=3&desc=delete&page=admin"
			onClick="return window.confirm('<?=$phrase?>')"><img src="images/remove-icon.gif" width="14" height="14" border="0"></td></tr></table>
</td>
	</tr>
<?php
		}
	}
?>
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
	<tr>

<td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="22"><img src="images/add-icon.jpg" width="14" height="14" alt=""></td>
    <td><a href="./dashboard_control.php?dashboard=0&task=2&desc=edit">New Dashboard</a></td>
  </tr>
</table></td>
	</tr>
</table>
</body>
</html>
<?
	}// end task_1
	
if ($task=="2")
	{
		// scan directory dash_box
		$dr=@opendir(DIR_FS_DASH_BOX);
  		if(!$dr) {echo "Open Dash_box Error"; return;}
  		$j=0;
		//echo "scan directory dash_box</br>";
 		while(($e=readdir($dr))!=false)
  		{
  		
  			if(DIR_FS_DASH_BOX.'/'.$e=='.' || DIR_FS_DASH_BOX.'/'.$e=='..') continue;
  			if(!@is_file(DIR_FS_DASH_BOX.'/'.$e)) continue; 
  			$NameType=explode(".", $e);
  			if($NameType[1]=="php" )
			{
				$table[$j]=$NameType[0];
				$title[$j]=get_title($table[$j]);
				//echo "title[".$j."]".$title[$j];
				//echo "    e=".$e;
				//echo "    dash: ".$table[$j]."</br>";
				$j++;
				
				
			}	
		
		}// END scan directory dash_box
		$num_table=$j;
		$num_active=0;
		$table_passive=$table;
		$table_passive_title=$title;
		$perm='';
		$perm_lst=Array('ALL'=>false);
		$p_qry=tep_db_query("SELECT DISTINCT admin_file_group FROM ".TABLE_ADMIN_FILES);
		while ($row=tep_db_fetch_array($p_qry)) $perm_lst[$row['admin_file_group']]=true;
		$perm_menu=Array();
		foreach ($perm_lst AS $p=>$v) $perm_menu[]=Array('id'=>$p,'text'=>$p);

		// edit dashboard
		if ($dashboard!="0")
		{

		$sql="select * from admin_dash where dash_name='$dashboard' order by sort_order";
		$result=tep_db_query($sql);
		$i=0;
		while ($row=tep_db_fetch_array($result))
			{
				$perm=$row['dash_permission'];
				$table_active[$i]=$row['dash_table'];
				//$table_active_title[$i]=get_title($table_active[$i]);
				for ($k=0; $k<$num_table; $k++)
					{
						if ($table_active[$i]==$table[$k])
							$table_active_title[$i]=$title[$k];
					}
				$table_sort[$i]=$row['sort_order'];
				$i++;
			}
		$num_active=$i;
		$num_passive=$num_active;
		unset($table_passive);
		unset($table_passive_title);
		for ($i=0; $i<=$num_table; $i++)
			{
			$identity=0;
			for ($j=0; $j<=$num_active; $j++)
				{
				if ($table[$i]==$table_active[$j]) 
					{
						$identity++; break;
					}
				}
			if ($identity==0) 
				{
					$table_passive[$num_passive]=$table[$i];
					$table_passive_title[$num_passive]=$title[$i];
					//$table_passive_title[$num_passive]=get_title($table[$i]);
					$num_passive++;
				}
			}
		}// ----END edit dashboard
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Dashboard Control</title>
<link rel="stylesheet" href="js/tabber.css" TYPE="text/css" MEDIA="screen">
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" href="js/css.css" type="text/css">
<script type="text/javascript" src="js/tips.js"></script>
</head>

<body style="background:transparent; margin:10px;">
<table width="551" cellpadding="0" cellspacing="0" border="0" style="padding: 0 0 15px 0;">
<tr>
<td width="58"><img src="images/roles-icon.png" width="48" height="48"></td> <td class="pageHeading"> Dashboard Manager &raquo; <?=$dashboard?></td>
</tr>
</table>

<form action="./dashboard_control.php" method="post">
<?php
	if ($dashboard!="0") //edit dash
	{
?>
	<input type="hidden" name="dashboard" value="<?=$dashboard?>" />
	<input type="hidden" name="desc" value="delete" />
	
	
	
<?php
	}
	else
	{
?>
	Name of a new dashboard: <input type="text" name="dashboard" maxlength="48" size="48" />
	<input type="hidden" name="desc" value="new" />
<?php
	}
?>
<input type="hidden" name="task" value="3" />
<br />
<table cellpadding="5">
<tr>
<td><b>Security Permission:</b></td><td><?=tep_draw_pull_down_menu('dash_permission',$perm_menu,$perm)?></td><td><div class="helpicon" onMouseover="ddrivetip('Select an administrative role which has access to this dashboard. If you select ALL, the dashboard will be available to all administrators.')" onMouseout="hideddrivetip()"></div></td></tr></table>

<table cellpadding="5" width="551">
<tr style="background-color:#6296FC;">
<td><b style="color:#FFFFFF;">Table</b></td>
	<td align="center"><b style="color:#FFFFFF;">Enable</b></td>
	<td align="center"><b style="color:#FFFFFF;">Sort</b></td></tr>
<?php
	
	for ($i=0; $i<$num_active; $i++)
	{
?>
	<tr>
		<td><?=$table_active_title[$i]?></td>
		<td align="center">
			<input type="hidden" name="up_table[]" value="<?=$table_active[$i]?>"/>
			<input type="checkbox" name="enable<?=$i?>"  value="1" checked="checked"/>
		</td>
		<td align="center"><select name="order[]">
          <?php
			for ($num=1; $num<=$num_table; $num++)
				{
?>
         	 <option>
           		 <?=$num?>
		 	 </option>
			 
          <?php
				}
?>
          	<option SELECTED><?=$table_sort[$i]?>
	    	</select>
		</td>
	</tr>
<?php
	}
	for ($i=$num_active; $i<$num_table; $i++)
	{
?>
	<tr>
		<td><?=$table_passive_title[$i]?></td>
		<td align="center">
			<input type="hidden" name="up_table[]" value="<?=$table_passive[$i]?>"/>
			<input type="checkbox" name="enable<?=$i?>"  value="1" />
		</td>
		<td align="center"><select name="order[]">
          <?php
			for ($num=1; $num<=$num_table; $num++)
				{
?>
         	 <option>
           		 <?=$num?>
		 	 </option>
			 
          <?php
				}
?>
          	<option SELECTED>
	    	</select>
		</td>
	</tr>
<?php
	}
?>
</table>
<input type="hidden" name="up_num" value="<?=$num_table?>" />

<br /><input type="submit" value="<?=$dashboard!="0"?"Update":"Add"?>">
</form><br /><br />
<a href="./dashboard_control.php?task=1">Return to dashboards listing</a>
</body>
</html>
<?		
	}
?>
