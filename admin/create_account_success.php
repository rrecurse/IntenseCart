<?php
/*
  Step-By-Step Manual Order Entry Verion 0.5
  Customer Entry through Admin 
*/

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_ACCOUNT_SUCCESS);


?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">

  <title><?php echo TITLE ?></title>

<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td valign="top" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            
            <td valign="top" class="main"><div align="center" class="pageHeading"><?php echo HEADING_TITLE; ?></div><br><?php printf(TEXT_ACCOUNT_CREATED,$customer_id); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="right"><br><?php echo '<a href="' . $origin_href . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
      </tr>
    </table></td>


  </tr>
</table>



</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>