<!-- /*<?//
function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}
?>

<table border="0" cellpadding="0" cellspacing="0">
<tr> 
    <td><a href="<?echo curPageURL();?>?language=en"><?php echo tep_image(DIR_WS_IMAGES . 'lang_a.gif', 'English'), ''; ?></a></td>
    <td><a href="<?echo curPageURL();?>?language=es"><?php echo tep_image(DIR_WS_IMAGES . 'lang_b.gif', 'Espa&ntilde;ol'), ''; ?></a></td>
    <td><a href="<?echo curPageURL();?>?language=de"><?php echo tep_image(DIR_WS_IMAGES . 'lang_e.gif', 'Deutsch'), ''; ?></a></td>
  </tr>
</table>*/-->

<table border="0" cellpadding="0" cellspacing="0">
<tr> 
    <td><a href="<?=preg_replace('/\?.*/','',$_SERVER['REQUEST_URI']).'?language=en'?>"><?php echo tep_image(DIR_WS_IMAGES . 'lang_a.gif', 'English'), ''; ?></a></td>
    <td><a href="<?=preg_replace('/\?.*/','',$_SERVER['REQUEST_URI']).'?language=es'?>"><?php echo tep_image(DIR_WS_IMAGES . 'lang_b.gif', 'Espa&ntilde;ol'), ''; ?></a></td>
    <td><a href="<?=preg_replace('/\?.*/','',$_SERVER['REQUEST_URI']).'?language=de'?>"><?php echo tep_image(DIR_WS_IMAGES . 'lang_e.gif', 'Deutsch'), ''; ?></a></td>
  </tr>
</table>