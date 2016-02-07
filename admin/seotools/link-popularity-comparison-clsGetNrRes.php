<?
class NrRes
{
/*function curl_string ($url)
{

       $ch = curl_init();
		$user_agent = "Mozilla/4.0";
       curl_setopt ($ch, CURLOPT_URL, $url);
       curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent);
       curl_setopt ($ch, CURLOPT_COOKIEJAR, "c:\cookie.txt");
       curl_setopt ($ch, CURLOPT_HEADER, 1);
       curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
       curl_setopt ($ch, CURLOPT_TIMEOUT, 120);
       $result = curl_exec ($ch);
       curl_close($ch);
	   echo $result;
       return $result;
  
}*/
function get_content($url)
{
	$numberOfSeconds=10;   
	$tmp=parse_url($url);
	$domain=$tmp['host'];
	$resourcePath=str_replace('http://','',$url);
	$resourcePath=str_replace($domain,'',$resourcePath);
	
	$socketConnection = fsockopen($domain, 80, $errno, $errstr, $numberOfSeconds);
	$res = '';
	fputs($socketConnection, "GET $resourcePath HTTP/1.0\r\nHost: $domain\r\n\r\n");
	while (!feof($socketConnection))
		$res .= fgets($socketConnection, 128);
	fclose ($socketConnection);

	//echo $res;
   return($res);
}
function getRes2Array($url)
{
	$result=array();
	$result['url']=trim($url);
	$result['google']=$this->getResGoogle($url);
	$result['yahoo']=$this->getResYahoo($url);
	$result['alltheweb']=$this->getResAllTheWeb($url);
	//$result['altavista']=$this->getResAltaVista($url);
	//$result['hotbot']=$this->getResHotBot($url);
	$result['msn']=$this->getResMSN($url);
	$tmp=$result['google']+$result['yahoo']+$result['alltheweb']+$result['altavista']+$result['hotbot']+$result['msn'];
	$result['total']=$tmp;
	return $result;
}
function getResGoogle($url)
{
	$url="http://www.google.com/search?q=".urlencode("site:").urlencode($url);
	$page=$this->get_content($url);
	$results=$this->get_value($page,'of about <b>','</b>');
	return $results;
}
function getResYahoo($url)
{
	$url="http://siteexplorer.search.yahoo.com/search?p=".urlencode($url)."&ei=UTF-8&fr=sfp&n=20&fl=0&x=wrt";
	$page=$this->get_content($url);
//	echo '<code>'.htmlspecialchars($page).'</code>';
	$results=$this->get_value($page,'of about <strong>','</strong>');
	return $results;
}
function getResAllTheWeb($url)
{
	$url="http://www.alltheweb.com/search?avkw=fogg&cat=web&cs=utf-8&q=".urlencode("link:").urlencode($url)."&_sb_lang=any";
	$page=$this->get_content($url);
	$results=$this->get_value($page,'of <span class="ofSoMany">','</span>');
	return $results;
}
function getResAltaVista($url)
{
	$url="http://www.altavista.com/web/results?q=".urlencode("link:").urlencode($url)."&kgs=0&kls=0&avkw=qtrp";
	$page=$this->get_content($url);
	$results=$this->get_value($page,'AltaVista found ',' results');
	return $results;
}
function getResHotBot($url)
{
	$url="http://www.hotbot.com/default.asp?prov=Inktomi&query=".urlencode("linkdomain:").urlencode($url)."&ps=&loc=searchbox&tab=web";
	$page=$this->get_content($url);
	$results=$this->get_value($page,' of ',')</div>');
	return $results;
}
function getResMSN($url)
{
	$url="http://www.bing.com/search?q=".urlencode($url)."&FORM=MSNH11&qs=n";
	$page=$this->get_content($url);
	$results=$this->get_value($page,' of ',' results containing <strong>');
	$results+=$this->get_value($page,' of ',' results </h3>');
	return $results;
}

function get_value(&$contents,$begin,$end)
{
/*
	$first=strpos($contents,$begin);
	$first=$first+strlen($begin);
	$contents=substr($contents,$first,strlen($contents)-$first);
	$last=strpos($contents,$end);
	$length=$last;
	$value=substr($contents,0,$length);
*/

	if (!preg_match('|'.quotemeta($begin).'([\s\d\,]+)'.quotemeta($end).'|',$contents,$p)) return 0;
	$value=ereg_replace("[, ]","",$p[1]);
	if(is_numeric($value)) return $value;
	else return 0;
}
}
?>
