<?php
$imap_server_type = 'cyrus';
$default_folder_prefix = '';
$use_authenticated_smtp = true;
$pop_before_smtp = false;
$show_prefix_option = false;
$optional_delimiter = '.';
$default_sub_of_inbox = true;
$default_move_to_trash = true;
$default_move_to_sent = true;
$default_save_as_draft = true;
$show_prefix_option = false;
$list_special_folders_first = true;
$use_special_folder_color = true;
$auto_expunge = true;
$show_contain_subfolders_option = false;
$auto_create_special = true;
$delete_folder = false;
$noselect_fix_enable = false;
$plugins[] = 'avelsieve';
$plugins[] = 'imap_passwd';