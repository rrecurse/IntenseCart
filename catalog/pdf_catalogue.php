<?php

require('includes/application_top.php');

    switch ($HTTP_GET_VARS['action']) {
      case 'preview':
        require(DIR_WS_INCLUDES.'pdf_config.php');
        require(DIR_WS_CLASSES.'pdf_fpdf.php');
        require(DIR_WS_CLASSES.'pdf_catalogue.php');
        require(DIR_WS_LANGUAGES . $language . '/' . 'pdf_catalogue.php');
        $languages_string = '';
		    $category_id = (isset($HTTP_GET_VARS['catid'])) ? (int)$HTTP_GET_VARS['catid'] : '';
		    $prod_id = (isset($HTTP_GET_VARS['pid'])) ? (int)$HTTP_GET_VARS['pid'] : '';

	  // Rigadin: only for selected language
        $pdf=new PDF();
        $pdf->Open();
        $pdf->SetDisplayMode("real");
        $pdf->AliasNbPages();
			  if ($prod_id<>'') {
			    $pdf->OneProduct($languages_id, $languages['code'], $prod_id);
			  } else {
				    if(SHOW_NEW_PRODUCTS) $pdf->NewProducts($languages_id,$languages['code']);
            $pdf->CategoriesTree($languages_id,$lng->language['code'],$category_id);
            $pdf->CategoriesListing($languages_id,$lng->language['code']);
            if (SHOW_INDEX) {
              $pdf->DrawIndex();
            }
			  }
			  $pdf->CleanFiles(DIR_FS_CATALOG . DIR_WS_PDF_CATALOGS); // Remove old temporary files starting with "menizzi" and ending with "tmp.pdf"
			  $file=basename(tempnam(DIR_FS_CATALOG . DIR_WS_PDF_CATALOGS,'tmp'));
        rename(DIR_FS_CATALOG . DIR_WS_PDF_CATALOGS.$file,DIR_FS_CATALOG . DIR_WS_PDF_CATALOGS.'prodpdf_'.$file.'.pdf');
        $file = 'prodpdf_'.$file.'.pdf';
        $pdf->Output(DIR_FS_CATALOG . DIR_WS_PDF_CATALOGS .$file, 'F');
			  $filetoopen = DIR_WS_PDF_CATALOGS . $file;
			  echo "<HTML><SCRIPT>document.location='$filetoopen';</SCRIPT></HTML>";
			  exit(); // We want to display only the pdf, not the page
        break;
      default:
			exit();
    }

?>
