<?php
class blk_box_rss extends IXblock {
  function requireContext() {
    return Array();
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'content': return !!$this->rss;
      case 'nocontent': return !$this->rss;
      default: return true;
    }
  }

	function renderSection($sec,&$body,$args) {
		switch ($sec) {

			case 'item':

				if(!empty($this->rss['items'])) { 
					foreach($this->rss['items'] AS $idx => $this->rssitem) {
						if ($this->args['max'] && $idx>=$this->args['max']) break;
						$this->renderBody($body);
					}

					unset($this->rssitem);

				} else { 
					break;
				}
			
			break;

			default: $this->renderBody($body);
		}
	}

  function var_fmt($v,$args) {
    $v=html_entity_decode($v);
    if ($args['max'] && strlen($v)>$args['max']) $v=substr($v,0,$args['max']-3).'...';
    if ($args['dateFormat']) $v=date($args['dateFormat'],strtotime($v));
    return $v;
  }

  function getVar($var,$args) {
    if ($var=='image') return $this->rss['image_url']?"<a href=\"{$this->rss['image_link']}\"><img src=\"{$this->rss['image_url']}\" alt=\"{$this->rss['image_title']}\" vspace=\"1\" border=\"0\" /></a>\n":'';
    if (preg_match('/^feed(.)(.*)/',$var,$pp)) {
      $v=strtoupper($pp[1]).$pp[2];
      if (isset($this->rss[$v])) return $this->var_fmt($this->rss[$v],$args);
    }
    if (isset($this->rssitem) && isset($this->rssitem[$var])) return $this->var_fmt($this->rssitem[$var],$args);
    if (isset($this->rss[$var])) return $this->var_fmt($this->rss[$var],$args);
    return NULL;
  }

  function render(&$body) {
    include_once(DIR_FS_COMMON."classes/rss_export.php");
 
// # Your RSS feed:
    $rss_feed=$this->args['rss'];

// # http://feeds.feedburner.com/OnlineMarketingSEOBlog // Good Internet Marketing!
// # http://www.platinax.co.uk/forum/external.php // No article text
// # http://feeds.feedburner.com/SocialPatterns // No article text


// Template for the feed:
    $DateFormat="m/d/Y"; 

//   error_reporting(E_ERROR);	
    $rss = new RSS_export; 
    $rss->cache_dir = DIR_FS_CATALOG_LOCAL; 
    $rss->cache_time = 1200; 
    $from = 1;
    $rss->date_format = $DateFormat;
    $rss->stripHTML=true;
    $this->rss=$rss->get($rss_feed);
//    srand(posix_getpid()+microtime());
    if ($this->rss && $this->args['random']) shuffle($this->rss['items']);
    $this->renderBody($body);    
  }
}
?>
