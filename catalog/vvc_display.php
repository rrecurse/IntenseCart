<?php
require('includes/application_top.php');
require(DIR_WS_FUNCTIONS . 'visual_verify_code.php');
$code_query = tep_db_query("select code from visual_verify_code where (ixsid = '" . $HTTP_GET_VARS['vvc'] . "' OR code = '" . $HTTP_GET_VARS['vvc'] . "')");
$code_array = tep_db_fetch_array($code_query);
$code = $code_array['code'];
vvcode_render_code($code);
?>
