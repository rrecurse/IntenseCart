<?php $domain =  preg_replace('/^www\./','www.',$_SERVER['HTTP_HOST']);?>
<?
  require_once('../includes/application_top.php');

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Link Popularity Comparison Tool</title>
<link rel="STYLESHEET" href="/admin/js/css.css" type="text/css">
<script type="text/javascript" src="/admin/includes/general.js"></script>
<script type="text/javascript" src="/admin/js/prototype.lite.js"></script>
<script type="text/javascript" src="/admin/js/expander-list.js"></script>

</head>

<?

/*
============================================================================================================

        Version              : 1.0

        Description          : Free Link Popularity Comparison Tool

        Copyright            : (c) SeoBook.com, licensed under the GPL ( http://www.gnu.org/licenses/gpl.txt )

        Function             : Allows you to compare your link popularity to other sites.

============================================================================================================
*/

include 'link-popularity-comparison-clsGetNrRes.php';

$c=$_REQUEST['c'];
$urls=$_REQUEST['urls'];



switch ($c)
{
case 1:
{
	
	for($i=1;$i<1000;$i++) echo "\n";
	flush();
	$urlsx=array();
	foreach($urls as $key=>$url)
	{
		if(strlen($url)>2)
		{
			$tmp=str_replace('http://','',$url);
			$tmp=trim($tmp,'/');
			$urlsx[]=$tmp;
		}
	}
	$urls=$urlsx;
	if(strlen($urls['0'])<5) 
	{
		echo '<font class=Arial color=red><br>Please enter at least one url to check .<br></font>';
		break;
	}
	flush();
	ob_start();
	$work = new NrRes($url);
	$res_1=$work->getRes2Array($urls[0]);//print_r($res_1);
	if(strlen($urls[1])>3) $res_2=$work->getRes2Array($urls[1]);//print_r($res_2);
	if(strlen($urls[2])>3) $res_3=$work->getRes2Array($urls[2]);//print_r($res_3);
	if(strlen($urls[3])>3) $res_4=$work->getRes2Array($urls[3]);//print_r($res_4);
	$stat=array();
	$res_final=array();
	$stat[$res_1[url]]=array($res_1);
	if(strlen($urls[1])>3) $stat[$res_2[url]]=array($res_2);
	if(strlen($urls[2])>3) $stat[$res_3[url]]=array($res_3);
	if(strlen($urls[3])>3) $stat[$res_4[url]]=array($res_4);
	
	$res_final[$res_1[total]]=$res_1[url];
	if(strlen(trim($urls[1]))>3) $res_final[$res_2[total]]=$res_2[url];
	if(strlen(trim($urls[2]))>3) $res_final[$res_3[total]]=$res_3[url];
	if(strlen(trim($urls[3]))>3) $res_final[$res_4[total]]=$res_4[url];
	ksort($res_final);
	include 'link-popularity-comparison-results.php';
}
}
function get_color($x)
{
	if($x<=1000) return "#FFFFFF";
	if($x<=5000 && $x>1000) return "#CDCA9C";
	if($x<=20000 && $x>5000) return "#94CE62";
	if($x<=100000 && $x>20000) return "#6299C5";
	if($x<=500000 && $x>100000) return "#CD956A";
	if($x>500000) return "#9C959C";
}

if (!$urls[0]) $urls[0]=preg_replace('/^www\./','',SITE_DOMAIN);

include 'link-popularity-comparison-form.php';

?>



