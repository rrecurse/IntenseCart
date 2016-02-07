<?php
/*
  $Id: catalog.php,v 1.20 2002/03/16 00:20:11 hpdl Exp $
  
  
  Copyright (c) 2002 IntenseCart eCommerce
  
*/
?>
<!-- newsletter //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_NEWSLETTER,
                     'link'  => tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('selected_box')) . 'selected_box=newsletter'));

  if ($selected_box == 'newsletter') {
    $contents[] = array('text'  =>

// ########## Contribution Admin Acces Level ##########
// NOTICE INSTALLATION : Si vous avez la contribution Admin Acces Level suivez les consigne ci-dessous
// Veuillez supprimer les commentaires de la ligne 34 à 38 (tep_admin_files_boxes) et mettre le reste en commentaires ligne 28 à 32

// Mettre en commentaire (//) si vous n'avez pas la contribution Admin Access Level
    				   '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_CUSTOMERS_NEWSLETTER_MANAGER . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_EXTRA_DEFAULT, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_EXTRA_DEFAULT . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_EXTRA_INFOS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_EXTRA_INFOS . '</a><br>' .
                                   //'<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_UPDATE . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_NEWSLETTER_SUBSCRIBERS_VIEW . '</a>'																	 

// A Supprimer les commentaires (//) pour la contribution Admin Acces Level
	//tep_admin_files_boxes(FILENAME_NEWSLETTERS, BOX_CUSTOMERS_NEWSLETTER_MANAGER) .
    //tep_admin_files_boxes(FILENAME_NEWSLETTERS_EXTRA_DEFAULT, BOX_NEWSLETTER_EXTRA_DEFAULT).
	//tep_admin_files_boxes(FILENAME_NEWSLETTERS_EXTRA_INFOS, BOX_NEWSLETTER_EXTRA_INFOS) .
    //tep_admin_files_boxes(FILENAME_NEWSLETTERS_UPDATE, BOX_NEWSLETTER_UPDATE) .   				   		    
	//tep_admin_files_boxes(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, BOX_NEWSLETTER_SUBSCRIBERS_VIEW) 
  );
// ########## END - Contribution Admin Acces Level ##########
  }
  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- newsletter_eof //-->
