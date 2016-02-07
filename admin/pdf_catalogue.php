<?php

//define('FPDF_FONTPATH','font/');

require('includes/application_top.php');
require(DIR_FS_CATALOG.DIR_WS_INCLUDES.'pdf_config.php');  // Configuration is on the catalog side
require(DIR_FS_CATALOG_CLASSES.'pdf_fpdf.php');     // PDF class is on the catalog side
require(DIR_FS_CATALOG_CLASSES.'pdf_catalogue.php');     // PDF catalogue class is on the catalog side
require(DIR_FS_CATALOG_LANGUAGES . $language . '/' . 'pdf_catalogue.php');

//$products_index_array;


    switch ($HTTP_GET_VARS['action']) {
      case 'save':
        $languages = tep_get_languages();
        $languages_string = '';
      
	  // Rigadin: only for selected language
        for ($i=0; $i<sizeof($languages); $i++) {
		  if ($languages[$i]['id'] == $languages_id) {
		    $language_name = $languages[$i]['name'];
            $pdf=new PDF();
            $pdf->Open();
            $pdf->SetDisplayMode("real");
            $pdf->AliasNbPages();
            if(SHOW_NEW_PRODUCTS) $pdf->NewProducts($languages[$i]['id'],$languages[$i]['code']);
            $pdf->CategoriesTree($languages[$i]['id'],$languages[$i]['code']);
			
            $pdf->CategoriesListing($languages[$i]['id'],$languages[$i]['code']);
            if (SHOW_INDEX) {
                $pdf->DrawIndex();
            }
            $pdf->Output(DIR_FS_CATALOG . DIR_WS_PDF_CATALOGS . PDF_FILENAME . "_" . $languages[$i]['code'].".pdf",false);
		  }
        }

        break;
      case 'preview':
        $languages = tep_get_languages();
        $languages_string = '';
      
	  // Rigadin: only for selected language
        for ($i=0; $i<sizeof($languages); $i++) {
		  if ($languages[$i]['id'] == $languages_id) {
            $pdf=new PDF();
            $pdf->Open();
            $pdf->SetDisplayMode("real");
            $pdf->AliasNbPages();
            if(SHOW_NEW_PRODUCTS) $pdf->NewProducts($languages[$i]['id'],$languages[$i]['code']);
            $pdf->CategoriesTree($languages[$i]['id'],$languages[$i]['code']);
            $pdf->CategoriesListing($languages[$i]['id'],$languages[$i]['code']);
            if (SHOW_INDEX) {
                $pdf->DrawIndex();
            }
            $pdf->Output(DIR_FS_CATALOG . DIR_WS_PDF_CATALOGS . PDF_FILENAME . "_" . $languages[$i]['code'].".pdf",'I');
			die;
		  }
        }
        break;
      default:
    }

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>PDF Catalog Generation &amp; Preview</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
    <td width="100%" valign="top" colspan="2">
     <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td>
          <table border="0" width="100%" cellspacing="0" cellpadding="0">
            <tr>
              <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
              <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
            </tr>
          </table>
	</td>
      </tr>
<?php
    switch ($HTTP_GET_VARS['action']) {
      case 'save':
?>
      <tr>
	<td>
	  <table>
    	    <tr>
		<td class="main"><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo PDF_GENERATED . " <font color=red>$language_name</font>";  ?></td>
	    </tr>
	  </table>
        </td>
      </tr>
<?php
        break;
      default:
        echo '<tr><td class="main"><br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp' . PDF_PRE_GENERATED . '&nbsp;&nbsp;';
        echo tep_draw_form('language', FILENAME_PDF_CATALOGUE, 'action=save');
		//echo tep_image_submit('button_preview.gif', IMAGE_PREVIEW) . '&nbsp;<a href="' . tep_href_link(FILENAME_PDF_CATALOGUE, 'action=preview&lngdir=' . $HTTP_GET_VARS['lngdir']) . '">';
		echo '<a target="_blank" href="' . tep_href_link(FILENAME_PDF_CATALOGUE, 'action=preview') .'">' . tep_image_button('button_preview.gif', IMAGE_PREVIEW) . '</a>';
        echo tep_image_submit('button_save.gif', IMAGE_SAVE) . '&nbsp;<a href="' . tep_href_link(FILENAME_PDF_CATALOGUE, '') . '">';
        echo "</td></tr></form>";
    }
?>      
     </table>
    </td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
