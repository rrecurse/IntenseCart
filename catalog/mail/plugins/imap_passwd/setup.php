<?php

include_once(SM_PATH . 'functions/i18n.php');
bindtextdomain('imap_passwd', '../plugins/imap_passwd/locale');

function squirrelmail_plugin_init_imap_passwd() {
  global $squirrelmail_plugin_hooks;
  $squirrelmail_plugin_hooks['optpage_register_block']['imap_passwd'] = 'imap_passwd_optpage_register_block';
  return;
}

function imap_passwd_optpage_register_block() {
  global $optpage_blocks;

  textdomain('imap_passwd');

  $optpage_blocks[] = array (
    'name' => _("Change Password"),
    'url'  => '../plugins/imap_passwd/options.php',
    'desc' => _("Change the password to your email account. You will need to know your current password to make changes."),
    'js'   => false);

  textdomain('squirrelmail');
}

function imap_passwd_version() {
  return '1.0.0';
}

?>
