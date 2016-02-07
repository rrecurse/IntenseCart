<?php
//
// RSS to HTML webpage script
//
// Copyright 2006 IntenseCart.com
//
// Website: http://www.intensecart.com
// Support: support@intensecart.com
// 
// License: RESTRICTED - NO REDISTRIBUTION
// This script may NOT be used freely for business or personal use
//
class RSS_export {
	var $channeltags = array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
	var $itemtags = array('title', 'link', 'description',  'pubDate');
	var $imagetags = array('title', 'url', 'link', 'width', 'height');
	var $textinputtags = array('title', 'description', 'name', 'link');


	function __construct() { 

		$this->default_cp = 'UTF-8';
	}

	function my_preg_match ($pattern, $subject) {

		preg_match($pattern, $subject, $out);

		if(isset($out[1])) {
			if ($this->CDATA == 'content') {
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			} elseif ($this->CDATA == 'strip') {
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			}

			if ($this->cp != '') $out[1] = iconv($this->rsscp, $this->cp.'//TRANSLIT', $out[1]);

			return trim($out[1]);
		} else {
			return '';
		}
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

			if ($result['encoding'] != '') { 
				$this->rsscp = $result['encoding']; 
			} else { 
				$this->rsscp = $this->default_cp; 
			} 

			preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);

//error_log(print_r($rss_content,1));
			foreach($this->channeltags as $channeltag)	{

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

?>
