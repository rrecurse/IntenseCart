<?php 
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	require('includes/application_top.php');

	define('TABLE_EMAIL_NOW_TEMPLATES','email_now_templates');

	if(isset($_POST['email_template_key'])) {
		$key = $_POST['email_template_key'];
		$lng = $_POST['lng'];
		
		$email_now_query = tep_db_query("SELECT * FROM ".TABLE_EMAIL_NOW_TEMPLATES." 
										 ORDER BY email_template_key!='$key',language_id!='$lng',email_template_key,language_id LIMIT 1
										");
		if($row=tep_db_fetch_array($email_now_query)) {
			if($row['email_template_key']!=$key || $row['language_id']!=$lng) {
				$row['language_id'] = $lng;
				$row['email_template_key'] = $key;
				$set = Array();
				foreach ($row AS $fld=>$r) {
					if (isset($_POST[$fld])) $row[$fld] = $_POST[$fld];
				}
	
				tep_db_query("INSERT INTO ".TABLE_EMAIL_NOW_TEMPLATES." (".join(',',array_keys($row)).") VALUES ('".join("','",array_map('addslashes',array_values($row)))."')");
    }

				$sql = "UPDATE ".TABLE_EMAIL_NOW_TEMPLATES." SET "; foreach(array('email_template_title','from_name','from_email','to_name','to_email','email_subject','email_template_html','email_template_text','send_mode') AS $fld) {
				$sql .= "$fld='".addslashes($_POST[$fld])."',";
				}
				$sql .= "modify_date=NOW() WHERE email_template_key='".tep_db_input($key)."' AND language_id='".tep_db_input($lng)."'";
			
				tep_db_query($sql);
			}
            	$messageStack->add('Successfully Updated '. $key, 'success');
		} else { 
		        $messageStack->add($key . 'Not Updated', 'warning');
		} 
  
		$lng = isset($_GET['lng']) ? $_GET['lng'] : 1;
  
		$tpinfo=NULL;

		if(isset($_GET['email_template_key'])) {
			$email_template_key=$_GET['email_template_key'];
			$email_now_query=tep_db_query("SELECT * FROM ".TABLE_EMAIL_NOW_TEMPLATES." ORDER BY email_template_key!='$email_template_key',language_id!='$lng',email_template_key,language_id LIMIT 1");
			$tpinfo=tep_db_fetch_array($email_now_query);
		}


?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="js/css.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript">

	var curField;
	function InsertVar(v) {
		if (curField) {
			var caretPos = curField.selectionStart;
			var textAreaTxt = $(curField).val();
			$(curField).val(textAreaTxt.substring(0, caretPos) + '['+v+']' + textAreaTxt.substring(caretPos));
			curField.focus();
		} else alert('Place your mouse cursor in a field first');
	}
 

	function text2html(src,dst) {
		if ((dst.value!='') && !window.confirm('Warning!\nThis will overwrite the existing HTML template')) return false;
  dst.value=src.value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/\r?\n/g,'<BR>\n');
 }

 function html2text(src,dst) {
  if ((dst.value!='') && !window.confirm('Warning!\nThis will overwrite the existing text template')) return false;
  var sp=src.value.replace(/\s+/g,' ').explode('<');
  var d=sp[0];
  for (var i=1;sp[i]!=null;i++) {
   var s=sp[i];
   var rp='';
   if (s.match(/^(BR|P|TR|DIV|BLOCKQUOTE|H[1-9])\b/))
    rp='\n';
   else if (s.match(/^(TD|TH)\b/))
    rp='\t';
   else if (s.match(/^(HR)\b/))
    rp='\n-----------------------------------------\n';
   d+=s.replace(/.*?>/,rp);
  }
  dst.value=d.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&amp;/g,'&');
 }
</script>
</head>

<body style="margin:0; background-color:transparent;">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>

<?php
 if ($tpinfo) {
?>

<td valign="top" colspan="2">
<?php echo tep_draw_form('email_now','email_now.php','lng='.$lng, 'post')?>
<P>Title: <?php echo tep_draw_input_field('email_template_title',$tpinfo['email_template_title'])?></P>
<input type="hidden" name="lng" value="<?php echo $lng?>">
<input type="hidden" name="email_template_key" value="<?php echo $email_template_key?>">
<input type="hidden" name="sort_order" value="<?php echo $tpinfo['sort_order']?>">
<table>
<tr><td>From:</td><td>
 <table>
 <tr><td>Name:</td><td><?php echo tep_draw_input_field('from_name',$tpinfo['from_name'],'onFocus="curField=this"')?></td></tr>
 <tr><td>Email:</td><td><?php echo tep_draw_input_field('from_email',$tpinfo['from_email'],'onFocus="curField=this"')?></td></tr>
 </table>
</td></tr>
<tr><td>To:</td><td>
 <table>
 <tr><td>Name:</td><td><?php echo tep_draw_input_field('to_name',$tpinfo['to_name'],'onFocus="curField=this"')?></td></tr>
 <tr><td>Email:</td><td><?php echo tep_draw_input_field('to_email',$tpinfo['to_email'],'onFocus="curField=this"')?></td></tr>
 </table>
</td></tr>
<tr><td>Subject:</td><td><?php echo tep_draw_input_field('email_subject',$tpinfo['email_subject'],'onFocus="curField=this"')?></td></tr>
<tr><td>Format:</td><td><?php echo tep_draw_pull_down_menu('send_mode',Array(Array(id=>'text',text=>'Text Only'),Array(id=>'html',text=>'HTML')),$tpinfo['send_mode'])?></td></tr>
<tr>
<td colspan="2">
Text Template:<br>
<?php echo tep_draw_textarea_field('email_template_text','soft',64,16,$tpinfo['email_template_text'],'onFocus="curField=this"')?></td></tr>
<tr><TD colspan=2 align="center"><input type="button" value="Text &gt;&gt; HTML" onClick="text2html(this.form.email_template_text,this.form.email_template_html)"><input type="button" value="HTML &gt;&gt; Text" onClick="html2text(this.form.email_template_html,this.form.email_template_text)"></td></tr>
<tr>
<td colspan="2">HTML Template:<br>
<?php echo tep_draw_textarea_field('email_template_html','soft',64,16,$tpinfo['email_template_html'],'onFocus="curField=this"')?></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="update" value="Update"></td></tr>

</table>
</form>
</td> <td valign="top">
<h3>Insert Tag</h3>
<ul>
<?php 
	$stack='';
	preg_match_all('/(.*?)(\:(.*?))?(\,|\(|\)|$)/',$tpinfo['vars_list'],$vars);
	for ($i=0;$vars[0][$i]!='';$i++) {
		$v = $vars[1][$i];
		$sep = $vars[4][$i];
		if ($v!='') {
			$desc='';
			echo '<li>';
			if ($vars[2][$i]) {
				$desc = $vars[3][$i];
			} else {
			    $sp = explode('_',$v);
				foreach($sp AS $s) $s=ucfirst($s);
				$desc = join(' ',$sp);
			}
		
			if ($sep!='(') {
				echo '<a href="javascript:void(0)" onclick="InsertVar(\''.$stack.$v.'\')">'.$desc.'</a>';
			} else {
				echo $desc;
			}
		}
	switch ($sep) {
		
	case '(':
		$stack.=$v.'.';
		echo "<ul>\n";
	break;
  
	case ')':
		if (preg_match('/(.*\.)./',$stack,$stkp)) {
			$stack=$stkp[1];
		} else {
			$stack='';
		}
		echo "</ul></li>\n";
	break;

	default:
		if ($v!='') echo "</li>\n";
  	} // # END switch
 }
?>
</ul>
</td></tr>

<?php 
 } else {
?>
<td valign="top">
<h2>Email Templates</H2>
<table border="1">
<?php 
  $tpquery=tep_db_query("SELECT email_template_key,language_id,email_template_title FROM ".TABLE_EMAIL_NOW_TEMPLATES." ORDER BY sort_order,language_id");
  $tplist=array();
  while ($tpinfo=tep_db_fetch_array($tpquery)) {
   if (!isset($tplist[$tpinfo['email_template_key']])) $tplist[$tpinfo['email_template_key']]['title']=$tpinfo['email_template_title'];
   $tplist[$tpinfo['email_template_key']]['language'][$tpinfo['language_id']]=1;
  }
	$languages=tep_get_languages();

	foreach ($tplist AS $tpkey => $tpdata) {
		echo '<tr><td>'.$tpdata['title'].'</td><td>';
		foreach ($languages AS $lang) {
			echo '<a href="email_now.php?email_template_key='.$tpkey.'&lng='.$lang['id'].'">'.tep_image(DIR_WS_CATALOG_LANGUAGES.$lang['directory'].'/images/'.$lang['image'], $lang['name']).'</a>';
	}
	
	echo '</td></tr>'; 
	}

?>
</table>
</td></tr>
<?php 
}
?>
</table>

</body>
</html>

<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
