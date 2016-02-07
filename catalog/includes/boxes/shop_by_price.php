<?php
require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_SHOP_BY_PRICE);


  
   // $info_box_contents = array();
    
    
    //new infoBoxHeading($info_box_contents, false, false);

    $info_box_contents = array();

    $ranges = preg_split('/,/',SHOP_PRICE_RANGES);
	
	for ($range=0; $range<sizeof($ranges); $range++) {
    	$info_box_contents[] = array('align' => 'left',
                                 'text'  => '<div style="position:relative;"><table border="0" cellpadding="0" cellspacing="0" width="100%">
	  <tr> 
            <td valign="top" width="16"><span class="shopbyprice_raquo">&raquo;</span></td>
            <td><div class="shopbyprice_txt"><a href="' . tep_href_link(FILENAME_SHOP_BY_PRICE, 'range=' . $ranges[$range].'-'.(isset($ranges[$range+1])?$ranges[$range+1]:'') , 'NONSSL') . '">' . (isset($ranges[$range+1])?sprintf($price_ranges[0],$ranges[$range]+1,$ranges[$range+1]):sprintf($price_ranges[1],$ranges[$range]+1)) . '</a></div></td>
	  </tr>
	</table></div>' 
                                );
	}				
    new infoBox($info_box_contents);
  
?>
