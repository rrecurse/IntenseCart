<?php
/*
  $Id: marketing.php,v 1.16 2006/06/24 02:33:05 hpdl Exp $

  IntenseCart, Internet Marketing and E-Commerce Solutions
  http://www.intenseCart.com

  Copyright (c) 2006 IntenseCart LLC

*/
?>

          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_MARKETING,
                     'link'  => tep_href_link('mail.php?selected_box=marketing'));

  if ($selected_box == 'marketing') {
    $contents[] = array('text'  => '&nbsp;<a href="' . tep_href_link(FILENAME_HEADER_TAGS_ENGLISH, '', 'NONSSL') . '" class="menuBoxContentLink" style="color:#333333;">META Tag Control</a><br>' .
                                   '<font style="font-size:9px;">&#8226; <a href="' . tep_href_link(FILENAME_HEADER_TAGS_CONTROLLER, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_HEADER_TAGS_ADD_A_PAGE . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_HEADER_TAGS_ENGLISH, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_HEADER_TAGS_ENGLISH . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_HEADER_TAGS_FILL_TAGS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_HEADER_TAGS_FILL_TAGS . '</a></font><br>----------------------<br>' .
                                   
                           // ### Email Marketing Panel ## //

                              '&nbsp;<a href="mail.php?selected_box=marketing" class="menuBoxContentLink" style="color:#333333;">Email Marketing</a><br>' .
                              '<font style="font-size:9px;">&#8226; <a href="' . tep_href_link(FILENAME_NEWSLETTERS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_CUSTOMERS_NEWSLETTER_MANAGER . '</a><br>' .
                              '&#8226; <a href="' . tep_href_link(FILENAME_NEWSLETTERS_EXTRA_DEFAULT, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_EXTRA_DEFAULT . '</a><br>&#8226; <a href="' . tep_href_link(FILENAME_NEWSLETTERS_EXTRA_INFOS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_EXTRA_INFOS . '</a><br>' .
                                   //'&#8226; <a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_UPDATE . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_SUBSCRIBERS_VIEW . '</a><br>----------------------<br>' . 
                                   
                                   '&nbsp;<a href="' . tep_href_link(FILENAME_AFFILIATE_SUMMARY, 'selected_box=marketing') . '" class="menuBoxContentLink" style="color:#333333;">Affiliate Control</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_SUMMARY, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_SUMMARY . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_CUSTOMERS_GROUPS, '', 'NONSSL') . '" class="menuBoxContentLink">Commissions</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_PAYMENT, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_PAYMENT . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_SALES, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_SALES . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_CLICKS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_CLICKS . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_BANNER_MANAGER, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_BANNERS . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_NEWS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_NEWS . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_NEWSLETTERS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_NEWSLETTER_MANAGER . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_AFFILIATE_CONTACT, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_AFFILIATE_CONTACT . '</a><br>----------------------<br>' .

                                '&nbsp;<a href="' . FILENAME_BANNER_MANAGER . '" class="menuBoxContentLink" style="color:#333333;">Banner &amp; Ad Control</a><br>' .
                                '&#8226; <a href="' . tep_href_link(FILENAME_BANNER_MANAGER) . '" class="menuBoxContentLink">' . BOX_TOOLS_BANNER_MANAGER . '</a><br>----------------------<br>' .

                                '&nbsp;<a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=961') . '" class="menuBoxContentLink" style="color:#333333;">Tracking</a><br>' .
                                '&#8226; <a href="' . tep_href_link('ad_campaigns.php') . '" class="menuBoxContentLink">' . 'Ad Campaigns' . '</a><br>' .
                                '&#8226; <a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=961') . '" class="menuBoxContentLink">' . 'Conversion &amp; Metrics' . '</a><br>----------------------<br>' .

                           // #### Refered by Control #### // 
                               '&nbsp;<a href="' . tep_href_link(FILENAME_REFERRALS, '', 'NONSSL') . '" class="menuBoxContentLink" style="color:#333333;">Referral Control</a><br>----------------------<br>' .
// #### Refered by Control #### //
                                   '&nbsp;<font class="menuBoxContentLink" style="color:#333333;">Feeds and Maps<br>' .
'&#8226; <a href="' . FILENAME_BANNER_MANAGER . '" class="menuBoxContentLink" style="color:#333333;">Google Site Maps</a><br>' .
'&#8226; <a href="' . FILENAME_BANNER_MANAGER . '" class="menuBoxContentLink" style="color:#333333;">Update Froogle</a><br>----------------------<br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_KEYWORDS) . '" class="menuBoxContentLink">' . BOX_TOOLS_KEYWORDS . '</a><br>'); 

  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>

