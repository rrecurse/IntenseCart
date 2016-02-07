<?php

define('HEADING_TITLE', 'Quickbooks Import QBI');

// Menu
define('QBI_MENU_CREATE', 'Create iif file');
define('QBI_MENU_PRODUCTS', 'Set Up Products');
define('QBI_MENU_SHIP', 'Set Up Shipping');
define('QBI_MENU_PRODUCTSMATCH', 'Match Products');
define('QBI_MENU_SHIPMATCH', 'Match Shipping');
define('QBI_MENU_CONFIG', 'Configure');
define('QBI_MENU_UTILITIES', 'Utilities');

// Menu (new)
define('MENU_1', 'Create iif');
define('MENU_2', 'Set Up');
define('MENU_2A', 'Products');
define('MENU_2B', 'Discounts/Fees');
define('MENU_2C', 'Shipping');
define('MENU_2D', 'Payment');
define('MENU_3', 'Match');
define('MENU_3A', 'Products');
define('MENU_3B', 'Discounts/Fees');
define('MENU_3C', 'Shipping');
define('MENU_3D', 'Payment');
define('MENU_4', 'Configure');
define('MENU_5', 'About');

// Setup files
define('SETUP_FILE_FOUND1', 'Found iif import file');
define('SETUP_FILE_FOUND2', '. Import now?');
define('SETUP_FILE_MISSING', 'Did not find iif import file.');
define('SETUP_FILE_BUTTON', 'Import iif File');
define('SETUP_SUCCESS', 'Setup successful!');
define('SETUP_NAME', 'Name');
define('SETUP_DESC', 'Description');
define('SETUP_ACCT', 'Account');
define('SETUP_ACTION', 'Action');
define('SETUP_NO_CHANGE', 'No change');
define('SETUP_UPDATED', 'Updated');
define('SETUP_ADDED', 'Added');

// Match
define('MATCH_BUTTON', 'Update Matches On This Page');
define('MATCH_PAGE', 'Results page:');
define('MATCH_PREV', 'Previous');
define('MATCH_NEXT', 'Next');
define('MATCH_SUCCESS', 'Matches updated.');
define('MATCH_OSC', ''.STORE_NAME);
define('MATCH_QB', 'Quickbooks');

// Warnings
define('WARN_CONFIGURE', 'QB Import must be set up and configured before use.');
define('WARN_CONFIGURE_LINK', 'Configure QB Import now.');

// Errors
define('ERROR_DIRECTORY_NOT_WRITEABLE', 'Error: I can not write to this directory. Please set the right user permissions on: %s');
define('ERROR_DIRECTORY_DOES_NOT_EXIST', 'Error: Directory does not exist: %s');
?>