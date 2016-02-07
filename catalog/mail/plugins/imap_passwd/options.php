<?php

define('SM_PATH','../../');

include_once (SM_PATH . 'include/validate.php');
include_once (SM_PATH . 'functions/i18n.php');
include_once (SM_PATH . 'functions/page_header.php');
include_once (SM_PATH . 'include/load_prefs.php');
include_once (SM_PATH . 'plugins/imap_passwd/config.php');

bindtextdomain('imap_passwd', SM_PATH . 'plugins/imap_passwd/locale');

global $username, $color, $imap_passwd_min_length, $imap_passwd_max_length, $imap_passwd_script_path; 

if (isset ($_POST['submit_password'])) {
  $userid = $username;

  $oldpass = $_POST['oldpass'];
  $newpass = $_POST['newpass'];
  $newpass2 = $_POST['newpass2'];
  $errmsg = "";

  $key = $_COOKIE['key'];
  $onetimepad = $_SESSION['onetimepad'];
  $clear = OneTimePadDecrypt($key, $onetimepad);

  if (strlen($oldpass) == 0) { 
    $errmsg = _("Please enter your old password.");
  } else if (strlen($newpass) == 0) {
    $errmsg = _("Please enter a new password.");
  } else if (strcmp($oldpass, $newpass) == 0) {
    $errmsg = _("New password must be different from old password.");
  } else if (strlen($newpass) < $imap_passwd_min_length) {
    $errmsg = _("New password must be at least") . ' <b>' . $imap_passwd_min_length . '</b> ' . _("characters long.");
  } else if (strlen($newpass) > $imap_passwd_max_length) {
    $errmsg = _("New password cannot be more than") . ' <b>' . $imap_passwd_max_length . '</b> ' . _("characters long.");
  } else if (strcmp($newpass, $newpass2) != 0) {
    $errmsg = _("New passwords don't match.");
  } else if (strcmp($clear, $oldpass) != 0) {
    $errmsg = _("Old password is incorrect.");
  } else {
    // execute the change password script
    $fullpath = dirname(__FILE__) . "/" . $imap_passwd_script_path;

    $cmd = popen($fullpath, 'w');
    fwrite($cmd, "$userid\n");
    fwrite($cmd, "$oldpass\n");
    fwrite($cmd, "$newpass\n");
    $rv = pclose($cmd);
    if ($rv != 100) {
      $errmsg = _("Error changing password: ") . $rv;
    }
  }

  if (strlen($errmsg) > 0) { 
    imap_passwd_showpage($errmsg, $oldpass, $newpass, $newpass2);
  } else {
    textdomain('imap_passwd');

    echo '<META HTTP-EQUIV="REFRESH" CONTENT="0;URL=../../src/signout.php?imap_passwd">' . "\n";
    echo '<script language="javascript">' . "\n";
    echo '<!--' . "\n";
    echo 'var path = window.location.pathname.substr(0,window.location.pathname.lastIndexOf("/"));' . "\n";
    echo "setTimeout('parent.window.location = " . '"" + path + "/../../src/signout.php?imap_passwd";' . "', 0);\n";
    echo "//-->\n</script>\n";
    echo "<h2>" . _("Your password has been changed.") . "</h2>\n";
    echo _("Please") . ' <a href="' . SM_PATH . 'src/signout.php?imap_passwd" target="_top">';
    echo _("logout") . '</a> ' . _("and log back in using your new password.") . "\n";

    textdomain('squirrelmail');
  }
} else {
  imap_passwd_showpage('', '', '', '');
}

function imap_passwd_showpage($errmsg, $oldpass, $newpass, $newpass2) {
  $color = $GLOBALS['color'];
  $maxlength = $GLOBALS['imap_passwd_max_length'];

  displayPageHeader($color, 'None');
  textdomain('imap_passwd');

  echo '<table align="center" bgcolor="' . $color[0] . '" width="95%" cellpadding="1" cellspacing="0" border="0"' . "\n";
  echo '<tr>' . "\n";
  echo '<td align="center">' . "\n";
  echo '<b>' . _("Options - Change Password") . '</b><br>' . "\n";
  echo '<table width="100%" cellpadding="5" cellspacing="0" border="0">' . "\n";
  echo '<tr>' . "\n";
  echo '<td align="center" bgcolor="' . $color[4] . '">' . "\n";

  echo '<form name="f" action="options.php" method="post">' . "\n";
  echo '<table width="100%" cellpadding="2" cellspacing="2" border="0">' . "\n";

  if (strlen($errmsg) > 0) {
    echo '<tr><td align="center" colspan="2"><font color="' . $color[2] . '">' . $errmsg . '</font></td></tr>' . "\n";
  }

  echo '<tr><td align="right">' . _("Old Password:") . '</td><td align="left"><input type="password" name="oldpass" value="' .
       htmlspecialchars($oldpass) . '"></td></tr>' . "\n";  
  echo '<tr><td align="right">' . _("New Password:") . '</td><td align="left">' .
       '<input type="password" maxlength="' . $maxlength . '" name="newpass" value="' .
       htmlspecialchars($newpass) . '"></td></tr>' . "\n";  
  echo '<tr><td align="right">' . _("New Password (again):") . '</td><td align="left">' .
       '<input type="password" maxlength="' . $maxlength . '" name="newpass2" value="' .
       htmlspecialchars($newpass2) . '"></td></tr>' . "\n";  

  echo '<tr><td align="center" colspan="2"><font color="' . $color[2] . '">' .
       _("NOTE: Once your password has been changed") . '<br>' . _("you will automatically be logged out.") .
       '</font></td></tr>' . "\n";
  echo '<tr><td align="center" colspan="2"><input type="submit" value="' . _("Change Password") . '" name="submit_password"></td></tr>' . "\n";
  echo '</table></form>';
  echo '</td></tr></table></td></tr></table></body></html>';

  textdomain('squirrelmail');
}

?>
