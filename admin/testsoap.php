<?php ob_start(); ?>
<?php include("includes/application_top.php"); ?>
	<?php include(DIR_WS_INCLUDES. 'header.php'); ?>
<?php include("nusoap/functions.php"); ?>
<pre>
<?php $array = tep_main_categories(); ?>
<?php //print_r($array); ?>
</pre>



<select name="cat">
<?php
function getid($id) {

$k=0;
global $array;
foreach($array as $a) {

	if ($a['id'] == $id)
		return $k;

	$k++;
}

}

function getparent($i,$id,$first = 'false') {
		
	if ($first == 'true')
		$value = '';
		
	global $array;
	
	if ($array[$i]['pID'] == 0) {
		return $array[$i]['text'] . ' >> ';
	}
	else { 

		$newid = getid($array[$i]['pID']);
								
		
		if ($first != 'true')
				$value .= $array[$i]['text'] . ' >> ';
				
		return getparent($newid,$array[$newid]['id'],'false') . $value;
		
	}
}


$i=0;
foreach ($array as $a) {


	if ($a['pID'] == "0") {

?>

<option value="<?=$a['text']?>"><?=$a['text']?></option>
<?php 
	}
	else {
		$val = getparent($i,$a['id'],'true');
		
		$val = $val . $a['text'];
		$total = explode(">>",$val);
	
		print_r($total);
	
		if (count($total) == "2")
			$spaces = '&nbsp;&nbsp;';
		else
			$spaces = '&nbsp;&nbsp;&nbsp;&nbsp;';
?>

<option value="<?=$val?>"><?php echo $spaces . $a['text'];?></option>
<?php
	}
	
	$i++;
}

?>
</select>
