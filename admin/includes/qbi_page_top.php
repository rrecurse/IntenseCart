<?php

umask(002);
if (!is_dir(DIR_FS_SHARE.'qbi_input')) @mkdir(DIR_FS_SHARE.'qbi_input',0777);
if (!is_dir(DIR_FS_SHARE.'qbi_output')) @mkdir(DIR_FS_SHARE.'qbi_output',0777);

require_once(DIR_WS_FUNCTIONS . 'qbi_functions.php');
require_once(DIR_WS_CLASSES . 'qbi_classes.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/qbi_styles.css">
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" valign="top" colspan="2">
	<table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%">
		<table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" style="height:45px;"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"></td>
          </tr>
        </table></td>
      </tr>
	</table>
<?php 
	$pageurl = $PHP_SELF;
?>