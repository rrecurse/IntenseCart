<?php

define('HEADING_TITLE_CONTROLLER', '<font style="font:bold 18px Verdana; color:#727272;">META Tags Page Control</font>');
define('HEADING_TITLE_ENGLISH', '<font style="font:bold 18px Verdana; color:#727272;">META Tags Text Control</font>');
define('HEADING_TITLE_FILL_TAGS', '<font style="font:bold 18px Verdana; color:#727272;">META Tags Fill-tag Control</font>');
define('TEXT_INFORMATION_ADD_PAGE', '<b>Add a New Page</b> - This option adds the code for a page into the files mentioned 
above. Note that it does not add an actual page. To add a page, enter the name of the file, with or without the .php extension..');
define('TEXT_INFORMATION_DELETE_PAGE', '<b>Delete a New Page</b> - This option will remove the code for a page from the
above files.'); 
define('TEXT_INFORMATION_CHECK_PAGES', '<b>Check Missing Pages</b> - This option allows you to check which files in your
shop do not have entries in the above files. Note that not all pages should have entries. For example,
any page that will use SSL like Login or Create Account. To view the pages, click Update and then select the drop down list.'); 

define('TEXT_PAGE_TAGS', '<font style="font:bold 11px arial;">In order for Header Tags to display information on a page, an entry for that page must be made into the includes/header_tags.php and includes/languages/english/header_tags.php files
(where english would be the language you are using). The options on this page will allow you to add, delete
and check the code in those files.</font>');
define('TEXT_ENGLISH_TAGS', '<font style="font:bold 11px arial;">The main purpose of META Tags is to give each of the pages in your shop a unique title, description and keyword tag for each page. The individual sections are named after the page they belong to. So, to change the title of your home page, edit the title of the "index" section below.</font>');
define('TEXT_FILL_TAGS', '<font style="font:bold 11px arial;">This option allows you to fill in the meta tags added by
Header Tags. Select the appropriate setting for both the categories and products tags
and then click Update. If you select the Fill Only Empty Tags, then tags already
filled in will not be overwritten.</font> ');
?>
