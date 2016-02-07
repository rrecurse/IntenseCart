<?php
/*

  Copyright (c) 2003 IntenseCart eCommerce
*/
 
  require('includes/application_top.php'); 
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_HEADER_TAGS_CONTROLLER);
 
  /****************** READ IN FORM DATA ******************/
  $categories_fill = $_POST['group1'];
  $products_fill = $_POST['group2'];
  
  $checkedCats = array();
  $checkedProds = array();
  
  $languages = tep_get_languages();
  $languages_array = array();
  for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
    $languages_array[] = array('id' => $i + 1, //$languages[$i]['id'],
                               'text' => $languages[$i]['name']);
  }
  $langID = $languages_id;
  
  /****************** FILL THE CATEGORIES ******************/
   
  if (isset($categories_fill))
  {
    $langID = $_POST['fill_language'];
    
    if ($categories_fill == 'none') 
    {
       $checkedCats['none'] = 'Checked';
    }
    else
    { 
      $categories_tags_query = tep_db_query("select categories_name, categories_id, categories_htc_title_tag, categories_htc_desc_tag, categories_htc_keywords_tag, language_id from categories_description where language_id = '" . $langID . "'");
      while ($categories_tags = tep_db_fetch_array($categories_tags_query))
      {
        $updateDP = false;
        
        if ($categories_fill == 'empty')
        {
           if (! tep_not_null($categories_tags['categories_htc_title_tag']))
             $updateDB = true;
           $checkedCats['empty'] = 'Checked';
        }
        else if ($categories_fill == 'full')
        {
           $updateDB = true;
           $checkedCats['full'] = 'Checked';
        }
        else      //assume clear all
        {
           tep_db_query("update categories_description set categories_htc_title_tag='', categories_htc_desc_tag = '', categories_htc_keywords_tag = '' where categories_id = '" . $categories_tags['categories_id']."' and language_id  = '" . $langID . "'");
           $checkedCats['clear'] = 'Checked';
        }
           
        
        if ($updateDB)
          tep_db_query("update categories_description set categories_htc_title_tag='".addslashes($categories_tags['categories_name'])."', categories_htc_desc_tag = '". addslashes($categories_tags['categories_name'])."', categories_htc_keywords_tag = '". addslashes($categories_tags['categories_name']) . "' where categories_id = '" . $categories_tags['categories_id']."' and language_id  = '" . $langID . "'");
      }
    }
  }
  else
    $checkedCats['none'] = 'Checked';
   
  /****************** FILL THE PRODUCTS ******************/  

  
  if (isset($products_fill))
  {
    $langID = $_POST['fill_language'];
    
    if ($products_fill == 'none') 
    {
       $checkedProds['none'] = 'Checked';
    }
    else
    { 
      $products_tags_query = tep_db_query("select products_name, products_description, products_id, products_head_title_tag, products_head_desc_tag, products_head_keywords_tag, language_id from " . TABLE_PRODUCTS_DESCRIPTION . " where language_id = '" . $langID . "'");
      while ($products_tags = tep_db_fetch_array($products_tags_query))
      {
        $updateDP = false;
        
        if ($products_fill == 'empty')
        {
          if (! tep_not_null($products_tags['products_head_title_tag']))
            $updateDB = true;
          $checkedProds['empty'] = 'Checked';
        }
        else if ($products_fill == 'full')
        {
          $updateDB = true;
          $checkedProds['full'] = 'Checked';
        }
        else      //assume clear all
        {
          tep_db_query("update products_description set products_head_title_tag='', products_head_desc_tag = '', products_head_keywords_tag =  '' where products_id = '" . $products_tags['products_id'] . "' and language_id='". $langID ."'");
          $checkedProds['clear'] = 'Checked';
        }
      
        if ($updateDB)
          tep_db_query("update products_description set products_head_title_tag='".addslashes($products_tags['products_name'])."', products_head_desc_tag = '". addslashes(strip_tags($products_tags['products_name']))."', products_head_keywords_tag =  '" . addslashes($products_tags['products_name']) . "' where products_id = '" . $products_tags['products_id'] . "' and language_id='". $langID ."'");
      }  
    }
  }
  else
    $checkedProds['none'] = 'Checked';

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<style type="text/css">
td.HTC_Head {color: sienna; font-size: 24px; font-weight: bold; } 
td.HTC_subHead {color: sienna; font-size: 14px; } 
</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
     <tr>
      <td class="HTC_Head"><?php echo HEADING_TITLE_FILL_TAGS; ?></td>
     </tr>
     <tr>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
     <tr>
      <td class="HTC_subHead"><?php echo TEXT_FILL_TAGS; ?></td>
     </tr>
     
     <!-- Begin of Header Tags -->      
     
     <tr>
      <td align="right"><?php echo tep_draw_form('header_tags', FILENAME_HEADER_TAGS_FILL_TAGS, '', 'post') . tep_draw_hidden_field('action', 'process'); ?></td>
       <tr>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
     </tr>
     <tr>
      <td class="main" ><?php echo 'Language' . ' '. tep_draw_pull_down_menu('fill_language', $languages_array, $langID);?></tr>
     </tr>     
       <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
       </tr>
       
       <tr>
        <td><table border="0" width="40%">
         <tr class="smallText">
          <th>CATEGORIES</th>
          <th>PRODUCTS</th>
         </tr> 
         <tr class="smallText">          
          <td align=left><INPUT TYPE="radio" NAME="group1" VALUE="none" <?php echo $checkedCats['none']; ?>> Skip all tags</td>
          <td align=left><INPUT	TYPE="radio" NAME="group2" VALUE="none" <?php echo $checkedProds['none']; ?>> Skip all tags</td>
         </tr>
         <tr class="smallText"> 
          <td align=left><INPUT TYPE="radio" NAME="group1" VALUE="empty"<?php echo $checkedCats['empty']; ?> > Fill only empty tags</td>
          <td align=left><INPUT	TYPE="radio" NAME="group2" VALUE="empty" <?php echo $checkedProds['empty']; ?>> Fill only empty tags</td>
         </tr>
         <tr class="smallText"> 
          <td align=left><INPUT	TYPE="radio" NAME="group1" VALUE="full" <?php echo $checkedCats['full']; ?>> Fill all tags</td>
          <td align=left><INPUT	TYPE="radio" NAME="group2" VALUE="full" <?php echo $checkedProds['full']; ?>> Fill all tags</td>
         </tr>
         <tr class="smallText"> 
          <td align=left><INPUT	TYPE="radio" NAME="group1" VALUE="clear" <?php echo $checkedCats['clear']; ?>> Clear all tags</td>
          <td align=left><INPUT	TYPE="radio" NAME="group2" VALUE="clear" <?php echo $checkedProds['clear']; ?>> Clear all tags</td>
         </tr>
        </table></td>
       </tr> 
       
       <tr>
        <td><table border="0" width="40%">
         <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
         </tr>
         <tr> 
          <td align="center"><?php echo (tep_image_submit('button_update.gif', IMAGE_UPDATE) ) . ' <a href="' . tep_href_link(FILENAME_HEADER_TAGS_ENGLISH, tep_get_all_get_params(array('action'))) .'">' . '</a>'; ?></td>
         </tr>
        </table></td>
       </tr>
      </form>
      </td>
     </tr>
     <!-- end of Header Tags -->

         
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
