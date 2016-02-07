<?php

	require('includes/application_top.php');

	// # Added for information pages
	if(empty($_GET['info_id'])) {
		$info_id = '';
		die("No page found.");
	} else {
		$info_id = $_GET['info_id'];
	}

	$info_query = tep_db_query("SELECT * FROM ".TABLE_INFORMATION." WHERE visible='1' AND information_id='".$info_id."'");
	$info = tep_db_fetch_array($info_query);

	$INFO_TITLE = stripslashes($info['info_title']);
	$INFO_DESCRIPTION = html_entity_decode(stripslashes($info['description']), ENT_NOQUOTES);

  // # Only replace cariage return by <BR> if NO HTML found in text
  // # Added as noticed by infopages module
  if(!preg_match("/([\<])([^\>]{1,})*([\>])/i", $INFO_DESCRIPTION)) {
          $INFO_DESCRIPTION = str_replace("\r\n", "<br>\r\n", $INFO_DESCRIPTION ); 
  }

  // # Need to do a check for if session is found...
  $INFO_FILELINK =  FILENAME_INFORMATION .(stristr(tep_href_link(FILENAME_INFORMATION, '', 'NONSSL'), '?') ? '&' : '?') . 'info_id=' . $info['information_id'];

  $breadcrumb->add($INFO_TITLE, tep_href_link($INFO_FILELINK, '', 'NONSSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo $INFO_TITLE; ?></td>
            <td align="right"><?php echo tep_image(DIR_WS_IMAGES . 'table_background_default.gif', $INFO_TITLE, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo $INFO_DESCRIPTION ; ?><br></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>

</table>

</td>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
