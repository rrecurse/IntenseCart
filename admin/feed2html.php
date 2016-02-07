<?php 

class RSS_export {
	var $channeltags = array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
	var $itemtags = array('title', 'link', 'description',  'pubDate');
	var $imagetags = array('title', 'url', 'link', 'width', 'height');
	var $textinputtags = array('title', 'description', 'name', 'link');
	function my_preg_match ($pattern, $subject) {
		preg_match($pattern, $subject, $out);

		if(isset($out[1])) {
			if ($this->CDATA == 'content') {$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));}
         elseif ($this->CDATA == 'strip') {$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));}
			if ($this->cp != '') $out[1] = iconv($this->rsscp, $this->cp.'//TRANSLIT', $out[1]);
			return trim($out[1]);}
        else {return '';}
  	}
	function unhtmlentities ($string) {
		$trans_tbl = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);
		$trans_tbl = array_flip ($trans_tbl);
		$trans_tbl += array('&apos;' => "'");
		return strtr ($string, $trans_tbl);
	}
	function Get ($rss_url) {
		if ($this->cache_dir != '') {
			$cache_file = $this->cache_dir . '/rsscache_' . md5($rss_url);
			$timedif = @(time() - filemtime($cache_file));
			if ($timedif < $this->cache_time) {
				$result = unserialize(join('', file($cache_file)));
				if ($result) $result['cached'] = 1;
			} else {
				$result = $this->Parse($rss_url);
				$serialized = serialize($result);
				if ($f = @fopen($cache_file, 'w')) {
					fwrite ($f, $serialized, strlen($serialized));
					fclose($f);
				}
				if ($result) $result['cached'] = 0;
			}
		}
		else {
			$result = $this->Parse($rss_url);
			if ($result) $result['cached'] = 0;
		}
		return $result;
	}

	function Parse ($rss_url) {
		if ($f = @fopen($rss_url, 'r')) {
			$rss_content = '';
			while (!feof($f)) {
				$rss_content .= fgets($f, 4096);
			}
			fclose($f);

			$result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);
			if ($result['encoding'] != '')
				{ $this->rsscp = $result['encoding']; }
			else
				{ $this->rsscp = $this->default_cp; } 

			preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
			foreach($this->channeltags as $channeltag)
			{
				$temp = $this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $out_channel[1]);
				if ($temp != '') $result[$channeltag] = $temp; 
			}
			if ($this->date_format != '' && ($timestamp = strtotime($result['lastBuildDate'])) !==-1) {$result['lastBuildDate'] = date($this->date_format, $timestamp);}
			preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);
			if (isset($out_textinfo[2])) {
				foreach($this->textinputtags as $textinputtag) {$temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
  					if ($temp != '') $result['textinput_'.$textinputtag] = $temp;}
			}
			preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
			if (isset($out_imageinfo[1])) {
				foreach($this->imagetags as $imagetag) {
					$temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
					if ($temp != ''){$result[ 'image_'.$imagetag] = $temp;}
             else { $result[ 'image_'.$imagetag]="nope1"; }
				}
			}
			preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
			$rss_items = $items[2];
			$i = 0;
			$result['items'] = array(); 
			foreach($rss_items as $rss_item) {
				if ($i < $this->items_limit || $this->items_limit == 0) {
					foreach($this->itemtags as $itemtag) {
						$temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
						if ($temp != '') $result['items'][$i][$itemtag] = $temp;
					}
					if ($this->stripHTML && $result['items'][$i]['description']) $result['items'][$i]['description'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['description'])));
					if ($this->stripHTML && $result['items'][$i]['title']) $result['items'][$i]['title'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['title'])));
					if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {$result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);}
					$i++;
				}
			}
			$result['items_count'] = $i;
			return $result;
		}
		else
		{
			return False;
		}
	}
}

/////////////////
 
// Your RSS feed:
$rss_feed="http://feeds.searchenginewatch.com/searchenginewatchexperts"; // Good Internet Marketing!
//$rss_feed="http://feeds.feedburner.com/OnlineMarketingSEOBlog"; // Good Internet Marketing!


// Template for the feed:
   $template="rss-template.rat";
   $DateFormat=" M d Y, g:i a"; 
if (isset($_REQUEST["RSSFILE"])) {
  $rss_feed = $_REQUEST["RSSFILE"];
}

if (isset($_REQUEST["TEMPLATE"])) {
  $template = $_REQUEST["TEMPLATE"];
}

$FeedMaxItems = 1;
if (isset($_REQUEST["MAXITEMS"])) {
  $FeedMaxItems = $_REQUEST["MAXITEMS"];
}

$RandomItems=1;
if (isset($_REQUEST["RANDOM"])) {
  $RandomItems = $_REQUEST["RANDOM"];
}

   error_reporting(E_ERROR);	
   $rss = new RSS_export; 
   $rss->cache_dir = './temp'; 
   $rss->cache_time = 1200; 
   $from = 1;
   $rss->date_format = $DateFormat;
   if ($rs = $rss->get($rss_feed)) 
    { 
     $theData = file($template);
     $count = 0;
     $from = -1;
     foreach($theData as $line)
      {
        if ((strstr($line,"NOCRLF=")) || (strstr($line,"NAME=")) || (strstr($line,"FILEEXT=")) || (strstr($line,"DATEFORMAT=")) || (strstr($line,"TIMEFORMAT="))) {
        $line="";
        }   
    	$line=str_replace("%Copyright%", "$rs[copyright]\n", $line); 
        $line=str_replace("%Copyright%", "", $line); 
        $line=str_replace("%Language%", "$rs[language]\n", $line); 
        $line=str_replace("%Language%", "", $line); 
        $line=str_replace("%Editor%", "$rs[managingEditor]\n", $line); 
        $line=str_replace("%Editor%", "", $line); 
        $line=str_replace("%Webmaster%", "$rs[webMaster]\n", $line); 
        $line=str_replace("%Webmaster%", "", $line); 
        $line=str_replace("%FeedPubTime%", "$rs[lastBuildDate]\n", $line); 
        $line=str_replace("%FeedPubTime%", "", $line); 
        $line=str_replace("%Rating%", "$rs[rating]\n", $line); 
        $line=str_replace("%Rating%", "", $line); 
        $line=str_replace("%Docs%", "$rs[docs]\n", $line); 
        $line=str_replace("%Docs%", "", $line); 

        $line=str_replace("%FeedTitle%", "$rs[title]\n", $line); 
        // $line=str_replace("%FeedLink%", "<a href=\"$rs[link]\">$rs[title]</a>\n", $line); 
        $line=str_replace("%FeedLink%", "$rs[link]\n", $line); 
        $line=str_replace("%FeedDescription%", $rs[description], $line);

        $line=str_replace("&lt;", "<", $line);
        $line=str_replace("&gt;", ">", $line);

        $line=str_replace("&nbsp;", " ", $line);
        $line=str_replace("&quot;", " ", $line);
        $line=str_replace("&copy;", " ", $line);
        $line=str_replace("&reg;", " ", $line);
        $line=str_replace("&trade;", " ", $line);
        $line=str_replace("&euro;", "?", $line);
        $line=str_replace("&bdquo;", " ", $line);
        $line=str_replace("&ldquo;", " ", $line);
        $line=str_replace("&laquo;", " ", $line);
        $line=str_replace("&raquo;", " ", $line);
        $line=str_replace("&sect;", " ", $line);
        $line=str_replace("&amp;", "&", $line);
        $line=str_replace("&#151;", " ", $line);
        $line=str_replace("&apos;", "'", $line);
        
if ($rs['image_url'] != '') { 
           $line=str_replace("%ImageItem%", "<a href=\"$rs[image_link]\"><img src=\"$rs[image_url]\" alt=\"$rs[image_title]\" vspace=\"1\" border=\"0\" /></a>\n", $line);
         }
          else {
           $line=str_replace("%ImageItem%", "", $line);
         }
        $count = $count+1;
	if (strstr($line,"%BeginItemsRecord%")){
        $from = $count; 
        }   
	if ($from == -1){ echo $line;}
     } 

        $linecount = 0;

        foreach($rs['items'] as $item)
        {

        if ($RandomItems == 1) {

           $seeder = hexdec(substr(md5(microtime()), -8)) & 0x7fffffff;
 	   mt_srand($seeder);
           $c=mt_rand(0,1);
           if ($c == 0) {
              $seeder = hexdec(substr(md5(microtime()), -8)) & 0x7fffffff;
 	      mt_srand($seeder);
              continue;
              }
        } 

        if ($linecount == $FeedMaxItems) {
           break;
           }
        ++$linecount;
       
       $strcount=0;	
       foreach($theData as $line){
          $strcount=$strcount+1;
          if ($strcount>=$from){ 
          $line=str_replace("%BeginItemsRecord%", "", $line);
          $line=str_replace("%ItemTitle%", $item['title'], $line);
          $line=str_replace("%ItemLink%", $item['link'], $line);
          $line=str_replace("%ItemDescription%",$item['description'], $line);
          $line=str_replace("%ItemPubTime%", $item['pubDate'], $line);
          $line=str_replace("%ItemPubTime%", "", $line);

          $line=str_replace("%EndItemsRecord%", "", $line);
// Added for feeds with tracking pixel or non-secure image paths
          $line=str_replace("src=\"http:", "src=\"https:", $line);
          $line=str_replace("&lt;", "<", $line);
          $line=str_replace("&gt;", ">", $line);
          $line=str_replace("&nbsp;", " ", $line);
          $line=str_replace("&quot;", " ", $line);
          $line=str_replace("&copy;", " ", $line);
          $line=str_replace("&reg;", " ", $line);
          $line=str_replace("&trade;", " ", $line);
          $line=str_replace("&euro;", "?", $line);
          $line=str_replace("&bdquo;", " ", $line);
          $line=str_replace("&ldquo;", " ", $line);
          $line=str_replace("&laquo;", " ", $line);
          $line=str_replace("&raquo;", " ", $line);
          $line=str_replace("&sect;", " ", $line);
          $line=str_replace("&amp;", "&", $line);
          $line=str_replace("&#151;", " ", $line);
          $line=str_replace("&apos;", "'", $line);

   	  echo $line;           }
	  }
         } 
   } 
   else 
   { 
    echo "Error: An error occured while parsing RSS file. Please contact us at: support@intensecart.com\n"; 
   } 
?> 
