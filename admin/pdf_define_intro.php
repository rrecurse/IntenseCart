<?php

  require('includes/application_top.php');

// This will cause it to look for 'mainpage.php'

  $HTTP_GET_VARS['filename'] = 'pdf_define_intro.php'; // dans CATALOG

  switch ($HTTP_GET_VARS['action']) {
    case 'save':
      if ( ($HTTP_GET_VARS['lngdir']) && ($HTTP_GET_VARS['filename']) ) {
        if ($HTTP_GET_VARS['filename'] == $language . '.php') {
          $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['filename'];
        } else {
          $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir'] . '/' . $HTTP_GET_VARS['filename'];
        }
        if (file_exists($file)) {
          if (file_exists('bak' . $file)) {
            @unlink('bak' . $file);
          }
          @rename($file, 'bak' . $file);
          $new_file = fopen($file, 'w');
          $file_contents = stripslashes($HTTP_POST_VARS['file_contents']);
          fwrite($new_file, $file_contents, strlen($file_contents));
          fclose($new_file);
        }
        tep_redirect(tep_href_link(FILENAME_PDF_DEFINE_INTRO, 'lngdir=' . $HTTP_GET_VARS['lngdir']));
      }
      break;
  }

  if (!$HTTP_GET_VARS['lngdir']) $HTTP_GET_VARS['lngdir'] = $language;

  $languages_array = array();
  $languages = tep_get_languages();
  $lng_exists = false;
  for ($i=0; $i<sizeof($languages); $i++) {
    if ($languages[$i]['directory'] == $HTTP_GET_VARS['lngdir']) $lng_exists = true;
        $languages_array[] = array('id' => $languages[$i]['directory'],
                               'text' => $languages[$i]['name']);
    }
  if (!$lng_exists) $HTTP_GET_VARS['lngdir'] = $language;
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
  
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
  
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr><?php echo tep_draw_form('lng', FILENAME_PDF_DEFINE_INTRO, '', 'get'); ?>
            <td class="pageHeading"><?php echo BOX_INFORMATION_PDF_DEFINE_INTRO; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '1', HEADING_IMAGE_HEIGHT); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_pull_down_menu('lngdir', $languages_array, '', 'onChange="this.form.submit();"'); ?></td>
          </form></tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if ( ($HTTP_GET_VARS['lngdir']) && ($HTTP_GET_VARS['filename']) ) {
    if ($HTTP_GET_VARS['filename'] == $language . '.php') {
      $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['filename'];
    } else {
      $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir'] . '/' . $HTTP_GET_VARS['filename'];
    }
    if (file_exists($file)) {
      $file_array = @file($file);
      $file_contents = @implode('', $file_array);

      $file_writeable = true;
      if (!is_writeable($file)) {
        $file_writeable = false;
        $messageStack->reset();
        $messageStack->add(sprintf(ERROR_DESTINATION_NOT_WRITEABLE, $file), 'error');
        echo $messageStack->output();
      }

?>
          <tr>
            <td class="main"><?php echo PDF_DEFINE_INTRO; ?></td>
          </tr>
          <tr>
          <?php echo tep_draw_form('language', FILENAME_PDF_DEFINE_INTRO, 'lngdir=' . $HTTP_GET_VARS['lngdir'] . '&filename=' . $HTTP_GET_VARS['filename'] . '&action=save'); ?>
            <td><table border="0" cellspacing="0" cellpadding="2">
              <tr>
<!--                <td class="main"><b><?php echo $HTTP_GET_VARS['filename']; ?></b></td> -->
                    <td><br></td>
              </tr>
              <tr>
                <td class="main"><?php echo tep_draw_textarea_field('file_contents', 'soft', '88', '30', $file_contents, (($file_writeable) ? '' : 'readonly')); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td align="right">
<?php               if ($file_writeable) {
                        echo tep_image_submit('button_save.gif', IMAGE_SAVE) . '&nbsp;<a href="' . tep_href_link(FILENAME_PDF_DEFINE_INTRO, 'lngdir=' . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
                    } else {
                        echo '<a href="' . tep_href_link(FILENAME_PDF_DEFINE_INTRO, 'lngdir=' . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>';
                    }
?>              </td>
              </tr>
            </table></td>
          </form></tr>

<?php
    } else {
?>
          <tr>
            <td class="main"><b><?php echo TEXT_FILE_DOES_NOT_EXIST; ?></b></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td><?php echo '<a href="' . tep_href_link(FILENAME_PDF_DEFINE_INTRO, 'lngdir=' . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
          </tr>
<?php
    }
  } else {
        $filename = $HTTP_GET_VARS['lngdir'] . '.php';
?>
          <tr>
            <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="smallText"><a href="<?php echo tep_href_link(FILENAME_PDF_DEFINE_INTRO, 'lngdir=' . $HTTP_GET_VARS['lngdir'] . '&filename=' . $filename); ?>"><b><?php echo $filename; ?></b></a></td>
<?php
    $dir = dir(DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir']);
    $left = false;
    if ($dir) {
      $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
      while ($file = $dir->read()) {
        if (substr($file, strrpos($file, '.')) == $file_extension) {
          echo '                <td class="smallText"><a href="' . tep_href_link(FILENAME_PDF_DEFINE_INTRO, 'lngdir=' . $HTTP_GET_VARS['lngdir'] . '&filename=' . $file) . '">' . $file . '</a></td>' . "\n";
          if (!$left) {
            echo '              </tr>' . "\n" .
                 '              <tr>' . "\n";
          }
          $left = !$left;
        }
      }
      $dir->close();
    }
?>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_FILE_MANAGER, 'current_path=' . DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_file_manager.gif', IMAGE_FILE_MANAGER) . '</a>'; ?></td>
          </tr>
<?php
  }
?>
        </table></td>
      </tr>
    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
