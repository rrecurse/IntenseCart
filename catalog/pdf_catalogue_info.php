<?php

/* PDF Catalogs v.2.0.1 
   Based on PDF Catalogs v.1.4 by gurvan.riou@laposte.net 
*/
  
  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PDF_CATALOGUE);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_PDF_CATALOGUE));
	
	// Directory where the generated PDF files will be stored!
	// If you mofify the name of this directory, please modify accordingly the 
	// catalog/admin/pdf_config.php file!!
	// Don't forget to change the permissions of this directory to 755!
	define('DIR_WS_PDF_CATALOGS','catalogues/');
	
	// Filename to use as a base for the name of the generated PDF files.
	// If you mofify the name of this directory, please modify accordingly the 
	// For Categories catalog
	// catalog/admin/pdf_config.php file!!
	define('PDF_FILENAME_CATEGORIES','categories');
	// For catalog
  define('PDF_FILENAME','catalog');
	    
?>

<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td  valign="top"><table border="0" width="100%"  cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_image(DIR_WS_IMAGES . 'table_background_specials.gif', HEADING_TITLE, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<!--Gesamtkatalog begin-->

      <!--  Show the Intro File 
      <tr>
        <td class="main"><br><?//php include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PDF_DEFINE_INTRO); ?><br></td>
      </tr> -->
			<!--  Show the Description in the pdf_catalogue_info.php -->
      <tr>
        <td class="main" align="left"><?php echo TEXT_PDF_DESCRIPTION; ?></td>
      </tr> 
      <tr>
        <td>&nbsp;</td>
      </tr>
      <tr>
	<td class="main" align="center"><?php 
            $file = DIR_WS_PDF_CATALOGS . PDF_FILENAME . "_" . $lng->language['code'] . ".pdf";
	    $sizecatalog = filesize($file)/pow(2,20);
            $formatted = sprintf("%0.2f MB", $sizecatalog);
            echo "<img width=16 height=16 src=\"images/adobe_pdf.gif\" align=middle>&nbsp;";
	    echo '<a href="' . $file . '" target="_blank\"><b>' . TEXT_PDF_FILE .'</b></a> (' . $formatted . ')';
?>
        <p><br>
	</td>
      </tr>
<!--Gesamtkatalog end-->	  	  
      <tr>
        <td class="main" align="left"><?php echo TEXT_PDF_DESCRIPTION2; ?></td>
      </tr>
			<tr>
			  <td class="main" align="center">
			  
			  <?php 
				
			$file = DIR_WS_PDF_CATALOGS . PDF_FILENAME_CATEGORIES . "_" . $lng->language['code'] . ".pdf";

//************************************************************************************************************************
			
			if(!isset($GO_ON)){
				//Neuerung v 1.6 by Michael Palmer
				//Anzeigen der Kategorien und auswahl über checkbox mit speicherung in einem Array
				//Übergeben des Array´s über Standardvariable da die Session durch behinderung der Admin-Session
				//nicht benutzt werden konnte!!!!!
				//echo "<form action=\"\" method=post>";
				tep_draw_form ('create_pdf', 'pdf_catalogue.php', 'action=preview');
				
				$i = 0;
				
				echo "<table><tr><td class=\"main\">";
				
				$categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '0' and c.categories_id = cd.categories_id and cd.language_id='" . (int)$languages_id ."' order by sort_order, cd.categories_name");
  					while ($categories = tep_db_fetch_array($categories_query))  {
//					echo tep_draw_radio_field ('catid', $categories['categories_id']) . $categories['categories_name'] . '<br />';
					//echo "<img width=16 height=16 src=\"images/adobe_pdf.gif\" align=middle>&nbsp;";
					//echo '<a href="' . $file . '" target="_blank\"><b>' . TEXT_PDF_FILE .'</b></a> (' . $formatted . ')';
					echo '<a target="_blank" href='.tep_href_link ('pdf_catalogue.php', 'action=preview&catid='.$categories['categories_id']).'>'.tep_image(DIR_WS_IMAGES.'adobe_pdf.gif').'&nbsp;'.$categories['categories_name'] .'</a><br />';
   
   //					echo "<input name=\"KATEGORIE_SELECTED2[$i]\" type=\"checkbox\" value=". $categories['categories_id'] .">". $categories['categories_name'] . $KATEGORIE_SELECTED2[$i]."<br>";																	
											
                    	$i++;
					}   
				
				//echo "<input type=hidden name=GO_ON value=1>";
				//echo "<br><input type=hidden name=ANZAHL value=$i>";
				//echo "<input type=submit name=submit value='" . IMAGE_BUTTON_CONTINUE ."'>";
	//			echo tep_image_submit (DIR_WS_LANGUAGES.$language.'/buttons/button_continue.php', TEXT_BUTTON_CONTINUE);
				echo "</td></tr></table>";
				echo "</form>";
			}
			
			if(isset($GO_ON) && isset($KATEGORIE_SELECTED2)){
				//Neuerung v 1.6 by Michael Palmer
				//Bestätigung der ausgewählten Kategorien und abschicken des Formulars zur erzeugung der PDF
			echo tep_draw_form('pdf_quantity', tep_href_link(FILENAME_PDF_KATALOG, tep_get_all_get_params(array('action')) . '')); 
				//echo "<form action=". FILENAME_PDF_KATALOG ." method=post>";
				
				echo  PDF_TXT_AUSWAHL ;
				
				$iCOUNT = 0;
				
				for ($i = 0; $i < $ANZAHL; $i++)
				{
					if ($KATEGORIE_SELECTED2[$i] != "")
					{
					 $KATEGORIE_SELECTED[$iCOUNT] = $KATEGORIE_SELECTED2[$i];
					 $iCOUNT++;
					 
					 	$categories_query = tep_db_query("select cd.categories_id, cd.categories_name from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where cd.categories_id= '$KATEGORIE_SELECTED2[$i]' and cd.language_id='" . (int)$languages_id ."' order by cd.categories_name");
  						while ($categories = tep_db_fetch_array($categories_query))  
						{
    					 	 echo "<b>".$categories['categories_name']."</b><br>";
							 break;											
						}
						
					}
				}
				
				echo PDF_TXT_KLICK;
				
				//Array in Normale Variable Speichern damit die Daten mit einer iFrame weiter gegeben werden können!!!!
				for ($i = 0; $i < $iCOUNT; $i++)
				{
					if (!isset($KAT_SELECTED_ARRAY))
					{
					 $KAT_SELECTED_ARRAY = $KATEGORIE_SELECTED[$i];
					}
					else
					{
				 	 $KAT_SELECTED_ARRAY = $KAT_SELECTED_ARRAY."|".$KATEGORIE_SELECTED[$i];
				 	}
				}						
				
				//Übergeben der Anzahl, PDF_ACTION und Array Variable
				echo "<input type=hidden name=file value=$file><input type=hidden name=KAT_SELECTED_ARRAY value=$KAT_SELECTED_ARRAY><input type=hidden name=PDF_ACTION value=1>";
				echo "<br><br><input type=hidden name=ANZAHL_KATEGORIEN value=$iCOUNT>";
				echo "<input type=submit name=submit value='" . PDF_TXT_GENERATE ."'>";
				echo "</form>";
			}
			elseif(isset($GO_ON))
			{
			 echo "<font color=#FF0000>".PDF_TXT_ERROR1 ."<a href=javascript:history.back() TARGET=_self>".PDF_TXT_ERROR2 ."</a>".PDF_TXT_ERROR1 ."";
			}
			
//************************************************************************************************************************
				
				?>
				
				</td>
			</tr>
			<tr>
        <td class="main" align="center"><?php echo TEXT_PDF_DOWNLOAD; ?></td>
      </tr>
			<tr>
        <td class="main" align="center"><?php 
				echo '<a href="http://www.adobe.com/products/acrobat/readstep2.html" target="_blank">';
				echo tep_image(DIR_WS_IMAGES . 'getacro.gif'); 
				?></a></td>
      </tr>
			<tr>
        <td class="main" align="center"><?php echo TEXT_PDF_END; ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
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
