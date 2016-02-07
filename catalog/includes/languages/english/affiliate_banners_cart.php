<?php
/*
  $Id: affiliate_banners_text.php,v 2.00 2003/10/12

  Affiliate

  

  
  

  

  
*/
define('NAVBAR_TITLE', 'Affiliate Program');
define('NAVBAR_TITLE_AFFILIATE_BANNER_CART', 'Affiliate Shopping Cart');
define('HEADING_TITLE', 'Affiliate Program - Embed Affiliate Cart!');

define('TEXT_AFFILIATE_NAME', 'Link Name:');
define('TEXT_INFORMATION', '<b style="font:bold 14px arial; color:red;">Step 1: </b><br><br><b>Copy and paste the code below, anywhere on your website with enough space to accommodate a minimum width of atleast 200px PER COLUMN.</b> <small> - If you use the default parameters, your minimum width will be 825px (3 columns at 200px plus an additional 225px for the left side column). </small><br>');

define('TEXT_INFORMATION2', 'You may adjust the following parameters of the embed code:<br><br>
<b><u>cols</u></b> = <small>Adjust the amount of columns in the layout. For more columns, you\'ll need to make sure your page width can accommodate.</small><br><br>
<b><u>items_per_page</u></b> = <small>Adjust the maximum amount of items shown on a single page before pagination.</small><br><br>
<b><u>width</u></b> = <small>The width of EACH column.</small><br><br>
<b><u>spec</u></b> = <small>Decide if you would like the Specials to show in the right column. Set to 0 for false.</small><br><br>
<b><u>ref</u></b> = <small>DO NOT change this. This is your Affiliate ID. If you change this, you will not get credit for sales generated through your cart!</small>');

define('TEXT_AFFILIATE_INFO', '<hr size="1"><br><b style="font:bold 14px arial; color:red;">Step 2:</b><br><br>Create a <b>SEPERATE</b> html file and name it &nbsp;<b style="color:red">helper.html</b> <br><br>
<b>Copy the code shown below and paste into your new file helper.html</b> <br><br>
Place helper.html in the <b>TOP / ROOT</b> directory of your website. This will be the same level as your home page index file.');
?>