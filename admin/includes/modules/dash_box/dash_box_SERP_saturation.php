<?php
define('SITE_DOMAIN_LIST', $_SERVER['SERVER_NAME']);
@include_once(DIR_FS_CATALOG_LOCAL.'header_tags_'.$language.'.php');

    class dash_box_SERP_saturation
	{
		var $table_cols=1;
	 	var $table_rows=1;
		var $title="SERP Saturation";
		var $compare_date, $compare_time, $se, $se_values, $se_mod, $mods;
		
		function dash_box_SERP_saturation()
        {
            $moduleset=tep_module('searchengine');
            $this->mods=$moduleset->getModules();
			$this->se = 'overall';
            if (isset($_SESSION['SERP_se'])) {
                $this->se = $_SESSION['SERP_se'];
            };
            if (isset($_GET['SERP_se'])) {
                $this->se = $_GET['SERP_se'];
            };
            $this->se_values .= "<option value='overall'".(($this->se == 'overall')?' selected':'').">Overall</option>";
            foreach($this->mods as $mod) {
				$selected = '';
                if ($this->se == get_class($mod)) {
                    $this->se_mod = $mod;
					$selected = " selected";
                };
                $this->se_values .= "<option value='".get_class($mod) ."'$selected>" . $mod->getName() . "</option>";
            };
            if (!isset($_SESSION['SERP_compare_date'])) {
                $this->compare_date = date("m-d-Y", mktime(0,0,0,date('m')-1,date('d'),date('Y')));
            } else {
                $this->compare_date = $_SESSION['SERP_compare_date'];
            };
            if (isset($_GET['SERP_compare_date'])) $this->compare_date = $_GET['SERP_compare_date'];
            list($month, $day, $year) = explode('-', $this->compare_date);
            $this->compare_time = mktime(0, 0, 0, $month, $day, $year);
            tep_session_register('SERP_compare_date');
            tep_session_register('SERP_se');
			$_SESSION['SERP_compare_date'] = $this->compare_date;
			$_SESSION['SERP_se'] = $this->se;
		}

        function render()
        {
?>
<div style="width:800; overflow-x:hidden"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                     <tr>
        <td colspan="3" style="height:1px; background-color:#FFFFFF"></td>
      </tr>
  <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
    </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td width="261" style="height:20px; background-color:#6295FD; font:bold 12px arial; color:#FFFFFF;">&nbsp; SERP Saturation for <? echo $_SERVER['SERVER_NAME']?></td>
                       <td width="22" align="right" style="padding:3px 0 0 0; background-color:#6295FD;"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b>Search Engine Results Pages</b></font><br><br>The page searchers see after they\'ve entered their query into the search box. <br>')" onMouseout="hideddrivetip()"> </div></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                      <td height="20" colspan="2" valign="top" style="padding-top:1px; background-color:#DEEAF8;">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="color:#0B2D86; font-size:12px;">&nbsp; <b>Last Month</b></td><td valign="top" rowspan="4" style="background-color:#F0F5FB;">
<div id="SERP_container">
<?php
	$this->render_content();
?>
</div>
</td>
</tr>
<tr>
    <td width="110" align="center">Google</td>
</tr>
<tr>
    <td align="center">Yahoo</td>
</tr>
<tr>
    <td align="center">MSN</td>
  </tr>
</table>

</td></tr>
<tr>
<td colspan="2">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td height="20" align="center" style="padding:5px;"><table border="0" align="center" cellpadding="0" cellspacing="0">
                            <tr>
                              <td><img src="images/graph-icon.jpg" width="16" height="15"></td>
                              <td style="padding-left:6px;"><a href="supertracker.php">view full report</a></td>
                            </tr>
                          </table></td>
                        </tr>
        </table>                      </td>
    </tr>                 
                   </table>
</div>
	<script type="text/javascript">
	<!--
	function dash_box_SERP_onblur() {
		field =  document.getElementById('compare_date_changed'); 
		if (field.value == 1) dash_box_SERP_update();
	}
	function dash_box_SERP_update_hidden() {
		field = document.getElementById('compare_date_changed');
		field.value = '1';
	}
	function dash_box_SERP_update() {
		block = document.getElementById('SERP_container') ;
		SERP_se = document.getElementById('se');
		SERP_compare_date = document.getElementById('compare_date');
		SERP_compare_date_changed = document.getElementById('compare_date_changed');
		new ajax ( '/admin/dashboard_ajax.php?box=dash_box_SERP_saturation&SERP_compare_date=' + SERP_compare_date.value + '&' + 'SERP_se=' + SERP_se.options[SERP_se.selectedIndex].value, {update: block} );
		SERP_compare_date_changed.value = 0;
	}
	function ctlToonBlur(){
		dash_box_SERP_update();
	}
<?
	include_once(DIR_FS_DASH_BOX_JS."main_dash.js");
	include_once(DIR_FS_DASH_BOX_JS."ext_dash.js");
?>
	// -->
	</script>
<?
  }

  function array_add($a1, $a2) {
	$result = array();
	foreach ($a1 as $key => $value) {
		if ($key == 'links') continue;
		$result[$key] = $value + $a2[$key];
	};
	return $result;
  }
  
  function get_links_diff($a1, $a2) {
	$result = array();
	if (is_array($a1)) {
	foreach ($a1 as $link => $pos) {
		switch (true) {
			case (isset($a2[$link]) && ($pos < $a2[$link])):
				$result['moveddown']++;
				unset($a2[$link]);
				break;
			case (isset($a2[$link]) && ($pos > $a2[$link])):
				$result['movedup']++;
				unset($a2[$link]);
				break;
			case (!isset($a2[$link])):
				$result['gain']++;
				break;
		};
	};
	};
	if (is_array($a2)) {
	foreach ($a2 as $link => $pos) {
		if (!isset($a1[$link])) {
			$result['loss']++;
		};
	};
	};
	return $result;
  }
  
  function render_cell($content)
  {
      return "<div style=\"text-align:center; height:25px; line-height:25px;\" class=\"tableinfo_right-btm\">$content</div>\n";
  }
  
  function render_rhead($title)
  {
      return "<div style=\"text-align:center; background-color:#DEEAF8; height:30px; line-height:30px;\">$title:</div>\n";
  }
  
  function retrieve_data($data,$index,$group,$key)
  {
        if (!$data[$index]) {
            return '-';
        }
        return (int) $data[$index][$group][$key];
  }
  
  function render_content() {
		$keywords = explode(',',HEAD_KEY_TAG_ALL);
        $mod_data=array();
        $mod_keys=array(
          'searchengine_google',
          'searchengine_yahoo',
          'searchengine_msn'
        );
		foreach($mod_keys as $mod_key) {
            if (!$this->mods[$mod_key]) {
                $mod_data[]=array();
                continue;
            }
            $mod=&$this->mods[$mod_key];
			$current_result = $mod->getSearchStats($keywords, time(), 30);
			$current = $this->parseResult($current_result);
			$compare_result = $mod->getSearchStats($keywords, $this->compare_time, 30);
			$compare = $this->parseResult($compare_result);
			$diff = $this->get_links_diff($current['links'], $compare['links']);

            $mod_data[]=array(
                'current'=>$current,
                'compare'=>$compare,
                'diff'=>$diff
            );
		}
		
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "<tr><td class=\"tableinfo_right-btm\">\n";

		echo $this->render_rhead("Visibility Score");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'current','score'));
        }

        echo "</td><td class=\"tableinfo_right-btm\">\n";
        echo $this->render_rhead("#1 Positions");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'current','first'));
        }
        echo "</td><td class=\"tableinfo_right-btm\" width=70>\n";
        echo $this->render_rhead("Top 5");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'current','top5'));
        }
        echo "</td><td class=\"tableinfo_right-btm\" width=70>\n";
        echo $this->render_rhead("Top 10");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'current','top10'));
        }
        echo "</td><td class=\"tableinfo_right-btm\" width=70>\n";
        echo $this->render_rhead("Top 20");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'current','top20'));
        }
        echo "</td><td class=\"tableinfo_right-btm\" width=70>\n";
        echo $this->render_rhead("Top 30");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'current','top30'));
        }
        echo "</td><td class=\"tableinfo_right-btm\">\n";
        echo $this->render_rhead("Moved Up");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'diff','movedup'));
        }
        echo "</td><td class=\"tableinfo_right-btm\">\n";
        echo $this->render_rhead("Moved Down");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'diff','moveddown'));
        }
        echo "</td><td class=\"tableinfo_right-btm\" width=60>\n";
        echo $this->render_rhead("Gains");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'diff','gain'));
        }
        echo "</td><td class=\"tableinfo_right-btm\">\n";
        echo $this->render_rhead("Losses");
        for($i=0;$i<count($mod_data);$i++) {
            echo $this->render_cell($this->retrieve_data($mod_data,$i,'diff','loss'));
        }
        echo "</td></tr>\n";
        echo "</table>\n";
  }
  
  function parseResult ($results) {
			if (!is_array($results)) return array();
            $domains = explode(',', SITE_DOMAIN_LIST);
            while (list(,$value) = each($domains)) {
                $http_domains[] = 'http:\/\/' . trim(quotemeta($value));
            };
            $domains_regexp = implode('|', $http_domains);
			$current = array (
				'first'	=> 0,
				'top5'	=> 0,
				'top10'	=> 0,
				'top20'	=> 0,
				'top30'	=> 0,
				'score'	=> 0,
				'links'	=> array()
			);
            foreach ($results AS $result) while (list($pos, $value) = each($result)) {
                if (preg_match("/^($domains_regexp)/is", $value)) {
                    $se_pos = $pos+1;
                    $our_links[] = $se_pos;
		    if ($se_pos == 1) $current['first']++;
		    else if ($se_pos<6) $current['top5']++;
		    else if ($se_pos<11) $current['top10']++;
		    else if ($se_pos<21) $current['top20']++;
		    else if ($se_pos<31) $current['top30']++;
                    $current['score'] += 31 - $se_pos;
		    if (!isset($current['links'][$value]) || $current['links'][$value]>$se_pos) $current['links'][$value] = $se_pos;
                };
            };
		return $current;
  }
}

?>


