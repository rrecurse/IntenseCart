<?php

$sslorno = (getenv('HTTPS') == 'on' ? 'SSL' : 'NONSSL');

echo tep_draw_form('quick_find', tep_href_link(FILENAME_ADVANCED_SEARCH_RESULT, '', $sslorno, true), 'get', 'autocomplete="off"');

echo '<div class="autosuggestbox" style="position:absolute;">
		<div class="autosuggestTitle" style="position:absolute;">' .BOX_HEADING_SEARCH . '</div>

		<div class="autosuggestSelectDiv" style="position:absolute;">' . tep_draw_input_field('keywords', BOX_HEADING_SEARCH, ' class="autosuggestSelect" onKeyUp="setAutoSuggest(this.value,\'auto_suggest\',{max:'.tep_js_quote(AUTOSUGGEST_MAX).',img_width:'.tep_js_quote(AUTOSUGGEST_THUMB_WIDTH).',img_height:'.tep_js_quote(AUTOSUGGEST_THUMB_HEIGHT).'})" onBlur="if(this.value==\'\')this.value='.tep_js_quote(BOX_HEADING_SEARCH).'; setAutoSuggest(\'\',\'auto_suggest\')" onFocus="if(this.value=='.tep_js_quote(BOX_HEADING_SEARCH).')this.value=\'\';" id="autocompleteOFF"') . '</div>';

	echo '<div class="autosuggestButton" style="position:absolute;">' . tep_hide_session_id() . tep_image_submit('searchgo.gif', BOX_HEADING_SEARCH) . '

<input type="hidden" name="search_in_description" value="1">
<input type="hidden" name="pfrom" value="0.01">
<input type="hidden" name="pto" value="">
</div>
<div id="auto_suggest" style="position:absolute; z-index:1000;"></div>
</div>
</form>';

// # loadfile js function located in /includes/sts_display_output.php

	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		echo '<script>loadfile("'.CDN_CONTENT.'/js/search_suggest.js", "js");</script>';
	} else { 
		echo '<script>loadfile("/js/search_suggest.js", "js");</script>';
	}
?>
