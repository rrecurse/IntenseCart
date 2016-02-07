<?php
/*
Released under the GNU license
 
* 
* 
* This class extends the pdf class and makes all the magic things happening in your pdf product listing.
* It is a separate file so it can be used from admin and catalog sides.
* 
*/


class PDF extends FPDF {
//Colonne courante
var $col=0;
//Ordonnée du début des colonnes
var $y0;
var $categories_string_spe = '';
var $categories_string = '';
var $categories_id = '';
var $levels = '';
var $parent_category_name;
var $ifw = 0;     //internal width  margin for the products (image and text) description
var $text_fw = 0; //text width for the products (text) description
var $ifh = 0;     //internal height margin for the products description 
var $products_index_array;
var $products_index_list='';

  function Header()
  {
  //Background Color
    $background_color_table=explode(",",BACKGROUND_COLOR);
    $this->SetFillColor($background_color_table[0], $background_color_table[1], $background_color_table[2]);
    $this->ifw = $this->fw * 0.95; // A4 portrait = 200 
    $this->ifh = $this->fh * 0.87; // A4 portrait = 260
    $this->Rect(0,0,$this->fw,$this->fh,F); // Draw background
	
    //Logo: If LOGO_IMAGE defined, show image with logo, else show text
    if (PDF_LOGO) {
      $this->Image(DIR_FS_CATALOG_IMAGES.PDF_LOGO,10,8,0,0);
			$logo_size = getimagesize(DIR_FS_CATALOG_IMAGES.PDF_LOGO);
			$logo_y = $logo_size[1] * PDF_TO_MM_FACTOR + 8; // Location of the bottom of the image.
    } else {	
        $this->SetFont('Arial','B',18);
	      $this->SetLineWidth(0);
        $w=$this->GetStringWidth(PDF_TITLE)+6;
        //$this->SetX((210-$w)/2);
	      $this->SetFillColor(100,100,100);
        $this->Cell($w,9,PDF_TITLE,0,0,'C');
    }
    //Année en cour
    //$aujourdhui = getdate();
    //$annee = strftime(PDF_DATE_FORMAT);

    $this->SetFont('Arial','B',12);
    //$this->Cell(0,9,$annee."    ",0,1,'R');  // Print date of creation
	  //$addlines = 20; // Vertical space after the date
	
    $lines_array = explode ("\n",PDF_TXT_HEADER_INFO); // Put each line in a cell of array
	  $wmax=0;
    // Find longest line
	  reset ($lines_array);
	  foreach ($lines_array as $s){
	    if (strlen($s)>$wmax) {
	      $wmax = strlen($s);
		    $smax = $s;
      }
	  } // foreach;
	  $wmax=$this->GetStringWidth($smax)+6; // Get lenght of longest line
    $this->SetX ($this->fw -$this->rMargin - $wmax); // Move to right side - margin - length of longest line
	  $this->MultiCell($wmax,5,PDF_TXT_HEADER_INFO,0); // Print store name and address on several lines
	  //$addlines = 5 - sizeof ($lines_array); // Vertical space below store name and address
	
    if (PDF_LOGO) {
        //$this->Ln($addlines);
				$this->Ln(2);
    } else {
        $this->Ln(2);
    } 
    $x=$this->GetX();
    //$y='27.00125';// Fixed value below the logo and the header text = Problem if size of text or image is different than usual
		$y = $this->GetY(); // Place vertical cursor after text
		// Put cursor more down if logo height is bigger than text
		if ($logo_y > $y) {
		  $y = $logo_y +2;
			$this->SetY ($y);
		}
    $this->Line($x,$y,$this->ifw,$y); // Write a line below logo and text
    $this->Ln(3); // Add some space below the line
    // Now we are below the header and we can start printing products, save the vertical coordinate.
    $this->y0=$this->GetY();
 }

 function Footer()
 {
    //Pied de page
    $this->SetY(-20);
    $x=$this->GetX();
    $y=$this->GetY();
    $this->SetLineWidth(0.2);
    $this->Line($x,$y,$this->ifw,$y);
    $this->SetFont('Arial','I',8);
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}   ',0,0,'R');
	
	// START Rigadin: add footer info in center
	$this->SetXY (10,-20);
	$this->MultiCell(0,5,html_entity_decode(PDF_TXT_FOOTER_INFO),0, 'C');
	// END
 }
 
 function CheckPageBreak($h)
 {
    //If high h will go too far, manually add page
    if($this->GetY()+$h>$this->PageBreakTrigger) $this->AddPage($this->CurOrientation);
 }
 
 function NbLines($w,$txt)
    {
	//Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
	$cw=&$this->CurrentFont['cw'];
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 and $s[$nb-1]=="\n")
		$nb--;
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
		$c=$s[$i];
		if($c=="\n")
		{
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
			continue;
		}
		if($c==' ')
			$sep=$i;
		$l+=$cw[$c];
		if($l>$wmax)
		{
			if($sep==-1)
			{
				if($i==$j)
					$i++;
			}
			else
				$i=$sep+1;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
		}
		else
			$i++;
	}
	return $nl;
    }

 function LineString($x,$y,$txt,$cellheight)
 {
    //calculate the width of the string
    $stringwidth=$this->GetStringWidth($txt);
    //calculate the width of an alpha/numerical char
    $numberswidth=$this->GetStringWidth('1');
    $xpos=($x+$numberswidth);
    $ypos=($y+($cellheight/2));
    $this->Line($xpos,$ypos,($xpos+$stringwidth),$ypos);
 }
 
 function ShowImage(&$width,&$height,$link,$path)
 {
    //$width=min($width,MAX_IMAGE_WIDTH);
    //$height=min($height,MAX_IMAGE_HEIGHT);
      $image_ext = substr(strtolower($path), (strlen($path)-4),4);
			if (!file_exists($path) || (($image_ext <>".jpg" ) && ($image_ext <> ".gif") && ($image_ext <> '.png') && substr(strtolower($path), (strlen($path)-5),5)<> ".jpeg")) {
					$path = DIR_FS_CATALOG_IMAGES.DEFAULT_IMAGE;
			}

   if(RESIZE_IMAGES) {
	    $destination =DIR_FS_CATALOG."catalogues/";
	    if(substr(strtolower($path), (strlen($path)-4),4)==".jpg" || substr(strtolower($path), (strlen($path)-5),5)==".jpeg") {
       $src=imagecreatefromjpeg($path);
   	 } else if (substr(strtolower($path), (strlen($path)-4),4)==".png") {
      	  $src=imagecreatefrompng($path);
   	 } else {
      	  echo "Only PNG and JPEG";
            exit();
   	 }
   
   	$array=explode("/", $path);
   	$last=sizeof($array);
        $size = getimagesize($path);
   	if($size[0] > $size[1]) {
     	    $im=imagecreate($width/PDF_TO_MM_FACTOR, $height/PDF_TO_MM_FACTOR);
            imagecopyresized($im, $src, 0, 0, 0, 0,$width/PDF_TO_MM_FACTOR, $height/PDF_TO_MM_FACTOR, $size[0], $size[1]);
   	} else {
     	    $im=imagecreate($height/PDF_TO_MM_FACTOR,$width/PDF_TO_MM_FACTOR);
            imagecopyresized($im, $src, 0, 0, 0, 0, $height/PDF_TO_MM_FACTOR, $width/PDF_TO_MM_FACTOR, $size[0], $size[1]);
  	}
  	if(!imagejpeg($im, $destination.$array[$last-1])) {
    	    exit();
    	}

        $path=$destination.$array[$last-1];
        $this->SetLineWidth(1);  
	$this->Cell($width+3,$height,"",1,0);
	$this->SetLineWidth(0.2);
	$this->Image($path,($this->GetX()-$width), $this->GetY(), $width, $height,'',$link);
	$this->SetFont('Arial','',8);
	unlink($path);
    } else {
	$this->SetLineWidth(1);
	// NH $this->Cell($width,$height,"",1,0);
	$this->Cell($width+3,$height,"",SIZE_BORDER_IMAGE,0);
	$this->SetLineWidth(0.2);
	//NH $this->Image($path,($this->GetX()-$width), $this->GetY(), $width, $height,'',$link);
	$this->Image($path,($this->GetX()-$width), $this->GetY(),$width ,'' ,'',$link);
	$this->SetFont('Arial','',8);
    }
 }


//Rangement de l'arbre (Level est le niveau de sous-categorie)
 function Order($cid, $level, $foo, $cpath)
 {
    if ($cid != 0) {
	if($level>1) {
	    $nbspaces=7;
            $dessinrep="|___ ";
	    //j'inverse le dessin
	    $revstring = strrev($dessinrep);
            //je lui ajoute nbspace pour chaque niveau de sous-repertoire
	    $revstring .= str_repeat(" ",$nbspaces*($level-2));
	    //je réinverse la chaine
	    $this->categories_string_spe .= strrev($revstring);			  
	} 
	$this->levels .=$level." ";
	$this->categories_id .= $cid." ";
	$this->categories_string .= $foo[$cid]['name'];
        $this->categories_string_spe .=  $foo[$cid]['name'];
     
        if (SHOW_COUNTS) {
            $products_in_category = tep_products_in_category_count($cid,'false');
            if ($products_in_category > 0) {
                $this->categories_string_spe .= ' (' . $products_in_category . ')';
            }
        }
	$this->categories_string .= "\n";
        $this->categories_string_spe .= "\n";
    }
    //Parcourir l'arbre des categories (lecture de la table de hachage comme en Perl)
    if (sizeof($foo) > 0 ) {
        foreach ($foo as $key => $value) {
            if ($foo[$key]['parent'] == $cid) {
                $this->Order($key, $level+1, $foo, $cid);
            }
        }
    }
 }

 function ParentsName($current_category_level,$i,&$categorieslevelsarray, &$categoriesnamearray)
 {

    $k=$i;
    while($k>0)	{
    	if($categorieslevelsarray[$k] == ($current_category_level-1)) {
	    $this->$parent_category_name=$categoriesnamearray[$k];
            break;
    	}	
	$k--;
    }
 }
 
 function CalculatedSpace($y1,$y2,$imageheight)
 {
    //Si les commentaires sont - importants que l'image au niveau de l'espace d'affichage
    if(($h2=$y2-$y1) < $imageheight) {
        $this->Ln(($imageheight-$h2)+3);
    } else {
        $this->Ln(3);
    }
 }
 
  function PrepareIndex($name,$manufacturer,$category)
 {
    $this->products_index_array[] = array (
                                        'name' => substr($name,0,55),
                                        'manufacturer' => substr($manufacturer,0,20),
                                        'category' => substr($category,0,18),
                                        'page' => $this->PageNo());
 }

  function DrawIndex()
 {
    //5 = hauteur des cellules
    $h= 5 * sizeof($this->products_index_array) ."<br>";
    if($h< $this->ifh) {
	$this->CheckPageBreak($h);
    }
    $this->AddPage();
    $this->Ln(5);
//    echo "<br>HHHH sizeof= " . sizeof($this->products_index_array);

    if (!function_exists(CompareIndex)) {
        function CompareIndex($a, $b)
       {
    //        return strcmp($a['name'], $b['name']);
            return strncasecmp($a['name'],$b['name'],8); // seulement les 8 premiers caracteres
       }
    }
    usort($this->products_index_array, CompareIndex);

    $this->SetFont('Courier','B',11);
    $this->Cell(1,11,"",0,0);
    $this->MultiCell($this->ifw,11,PDF_INDEX_HEADER,0,'C');
    $this->SetFont('Courier','',11);
    if (strlen(INDEX_SEPARATOR) < 1) {
        $index_separator=" ";
    } else {
        $index_separator=INDEX_SEPARATOR;
    }
    foreach ($this->products_index_array as $key => $value) {
        if (strlen($value['manufacturer']) > 0) {
            $ligne_index = str_pad($value['name']." - ". $value['manufacturer'],53,$index_separator,STR_PAD_RIGHT);
        } else {
            $ligne_index = str_pad($value['name'],53,$index_separator,STR_PAD_RIGHT);
        }
	$ligne_index .= str_pad($value['category'],18,$index_separator,STR_PAD_LEFT);
	$ligne_index .= str_pad($value['page'], 5, $index_separator, STR_PAD_LEFT);
	$this->Cell(1,6,"",0,0);
	$this->MultiCell(0,6,$ligne_index,0,'C');
//        echo "<br>HHHH : " . $ligne_index;
    }
//    echo "<br>HHHH wpt =" .$this->wPt .  " fw =" . $this->fw.  " ifw =" . $this->ifw ." text_fw =" . $this->text_fw;
//    echo "<br>HHHH hpt =" .$this->hPt .  " fh =" . $this->fh.  " ifh =" . $this->ifh;
 }

 function DrawCells($data_array)
 {
 // This function prints a product
 
   $totallines=0;
	 for($i=2;$i<(sizeof($data_array)-1);$i++)
	 {
	    //Calculates number of lines occupied by a MultiCell of width w (w, MultiCell)
		// w = document width - image width
	    $totallines+=$this->NbLines(($this->ifw -$data_array[0]),$data_array[$i]);
	 }
	 
	 //5 = hauteur des cellules
	 $h=5*($totallines+1);//."<br>";
	 
	 //si la description du produit ne prend pas toute la page
	 if($h< $this->ifh)
	 {
	    $this->CheckPageBreak($h);
	 }
	 
	 
	 if(SHOW_PRODUCTS_LINKS)
	 { // NH   DIR_WS_CATALOG
	 	$link=HTTP_CATALOG_SERVER . DIR_WS_CATALOG ."product_info.php?products_id=".$data_array[10]."&language=".$data_array[11];
	 }
	 else
	 {
	 	 $link='';
	 }
	 
	 if(SHOW_IMAGES && strlen($data_array[12])) // [12] is image path
	 {
	 
	 // Check if the image exists. If not, replace by default image.
	 	 if (!file_exists($data_array[12])) {
			 $data_array[12] = DIR_FS_CATALOG_IMAGES.DEFAULT_IMAGE;
		 }
	 	//If Image Width and Image Height are defined
	 	if(strlen($data_array[0])>1 && strlen($data_array[1])>1)
		{ 
			$this->ShowImage($data_array[0],$data_array[1],$link,$data_array[12]);
      $y1=$this->GetY();
		}
    //If only Image Width is defined
		else if(strlen($data_array[0])>1 && strlen($data_array[1]))
		{   
		    $heightwidth=getimagesize($data_array[12]);
		    $data_array[0]=$data_array[0]; // Width defined, keep it
		    $data_array[1]=$heightwidth[1]/$heightwidth[0] *$data_array[0];
        $this->ShowImage($data_array[0],$data_array[1],$link,$data_array[12]);
	 	    $y1=$this->GetY();
		}
		//If only Small Image Height is defined
		else if(strlen($data_array[0]) && strlen($data_array[1])>1)
		{
		  $heightwidth=getimagesize($data_array[12]);
      $data_array[0]=$heightwidth[0]/$heightwidth[1] * $data_array[1]; // src.width / src.height * dest.height
		  $data_array[1]=$data_array[1];
	 	  $this->ShowImage($data_array[0],$data_array[1],$link,$data_array[12]);
      $y1=$this->GetY();
		}
		else
		{
		  $heightwidth=getimagesize($data_array[12]);
      $data_array[0]=$heightwidth[0]*PDF_TO_MM_FACTOR;
		  $data_array[1]=$heightwidth[1]*PDF_TO_MM_FACTOR;
      $this->ShowImage($data_array[0],$data_array[1],$link,$data_array[12]);
	 	  $y1=$this->GetY();
		}
		
		//Margin=10
		$this->SetX(10);
	}
	else
	{
		$data_array[0]=$data_array[1]=0;
		$y1=$this->GetY();
		$this->SetFont('Arial','',8);
	}
	// Calcul l'espace libre a droite de l'image
        $this->text_fw = $this->ifw - 18 - $data_array[0];
	 
	 if(SHOW_NAME)
	 {
	    if(strlen($data_array[2]))
	    {
	        // Cell(marge gauche, hauteur, text, bordure, )
		  $this->Cell($data_array[0]+6,5,"",0,0);
          $x=$this->GetX();
          $y=$this->GetY();
          $name_color_table=explode(",",NAME_COLOR);
          $this->SetFillColor($name_color_table[0],$name_color_table[1],$name_color_table[2]);
		  $this->SetFont('Arial','B',14);
 		  $this->MultiCell($this->text_fw,5,$data_array[2],PRODUCTS_BORDER,'L',1);
		  $this->SetFont('Arial','',12);
		  $this->Ln(5);
        }
	 }
	 if(SHOW_MODEL)
	 {
	 	if(strlen($data_array[3]))
		{
	 		$this->Cell($data_array[0]+6,5,"",0,0);
	 		$this->MultiCell($this->text_fw,5,PDF_TXT_MODEL.$data_array[3],PRODUCTS_BORDER,'L');
		}
	 }

	 if(SHOW_DATE_ADDED)
	 {
	 	if(strlen($data_array[4]))
		{
	    	$this->Cell($data_array[0]+6,5,"",0,0);
	 		$this->MultiCell($this->text_fw,5,$data_array[4],PRODUCTS_BORDER,'L');
		}
	 }
	 if(SHOW_MANUFACTURER)
	 {
	    if(strlen($data_array[5]))	{
	 	$this->Cell($data_array[0]+6,5,"",0,0);
	    $this->SetFont('Arial','I');
        $this->MultiCell($this->text_fw,5,PDF_TXT_MANUFACTURER.$data_array[5],PRODUCTS_BORDER,'L');
		$this->SetFont('Arial','');
	    }
	 }
	 // NH  si il n'y a pas de bordure, ajout d'un petit separateur
/*	 if (!PRODUCTS_BORDER) {
            $this->Cell($data_array[0]+6,2,"",0,0);
            $x=$this->GetX();
            $y=$this->GetY();
            $this->MultiCell($this->text_fw,1,"",0,'C');
            //$this->LineString($x+3,$y,"                 ",2);
            $this->Line($x+4,$y,$x+15,$y);
	 }
*/
	 if(SHOW_DESCRIPTION)
	 {
	 	if(strlen($data_array[6]))
		{

/*		
			  $description_array = explode ("\n",$data_array[6]); // Now we have each line in an array
			  reset ($description_array);
			  $product_description = '';
			  $color_found = false;
			  foreach ($description_array as $str) {
			    $pos = strpos($str,':');
			    if ($pos===false) {
			      $product_description.= trim($str)."\n";
			    } else {
				  if ($color_found) { // If color lines already processed
				      $sizes = explode ('-',substr ($str,$pos+1));
					  $size_eye = trim($sizes[0]);
					  $size_bridge = trim($sizes[1]);
					  $size_temple = trim($sizes[2]);
					  $product_description .= "\n"; // Add a line between colors and sizing
					  $product_description.= trim(substr ($str,0,$pos+1))."\n";
					  $product_description.= '  Eye: '. "\t\t\t\t\t\t".$size_eye ."\n";
					  $product_description.= '  Bridge: '. "\t\t".$size_bridge ."\n";
					  $product_description.= '  Temple: '. "".$size_temple ."\n";
				      //$product_description.= trim(substr ($str,$pos+1))."\n";
				  } else {
				      $product_description.= trim(substr ($str,0,$pos+1))."\n";
				      $product_description.= trim(substr ($str,$pos+1))."\n";
				      $color_found = true;
				    }
				}
			  }		
*/		
		
	 		$this->Cell($data_array[0]+6,5,"",0,0);
	 		$this->MultiCell($this->text_fw,5,$data_array[6] ,PRODUCTS_BORDER,'L');
		}
	 }
	 if(SHOW_TAX_CLASS_ID)
	 {
	 	if(strlen($data_array[7]))
		{
            $this->Cell($data_array[0]+6,5,"",0,0);
	 		$this->MultiCell($this->text_fw,5,$data_array[7],PRODUCTS_BORDER,'L');
		}
	 
	 }
	 if(VAT == '1')
	 {
	 	 $vatprice_query=tep_db_query("select p.products_id, p.products_tax_class_id, tr.tax_rate from " . TABLE_PRODUCTS . " p, " . TABLE_TAX_RATES . " tr where p.products_id = '" . $data_array[10] . "' and p.products_tax_class_id = tr.tax_class_id");
		while($vatprice1 = tep_db_fetch_array($vatprice_query)) {
		$steuer = $vatprice1['tax_rate'];
		}
		$vatprice=sprintf("%01.".DIGITS_AFTER_DOT."f",(($steuer/100)*$data_array[9])+$data_array[9]);
		$vatspecialsprice=sprintf("%01.".DIGITS_AFTER_DOT."f",(($steuer/100)*$data_array[8])+$data_array[8]);
	 }
	 else
	 {
	 	$vatprice=sprintf("%01.".DIGITS_AFTER_DOT."f",$data_array[9]);
	 	$vatspecialsprice=sprintf("%01.".DIGITS_AFTER_DOT."f",$data_array[8]);
	 }
	 if(SHOW_PRICES)
	 {
            // NH  si il n'y a pas de bordure, ajout d'un petit separateur
                if (!PRODUCTS_BORDER) {
                    $this->Cell($data_array[0]+6,2,"",0,0);
                    $x=$this->GetX();
                    $y=$this->GetY();
                    $this->MultiCell($this->text_fw,1,"",0,'C');
                    //$this->LineString($x+3,$y,"                 ",2);
                    $this->Line($x+4,$y,$x+15,$y);
                }

	 	if(strlen($data_array[8])) //If special price 
		{		
		    $this->Cell($data_array[0]+6,5,"",0,0);
		
                    $x=$this->GetX();
		    $y=$this->GetY();
		    $specials_price_color_table=explode(",",SPECIALS_PRICE_COLOR);
		    $this->SetTextColor($specials_price_color_table[0],$specials_price_color_table[1],$specials_price_color_table[2]);
		    $this->SetFont('Arial','B','');


		    if(CURRENCY_RIGHT_OR_LEFT == 'R') {
                        $this->MultiCell($this->text_fw,5,$vatprice.CURRENCY."\t\t\t".$vatspecialsprice.CURRENCY,PRODUCTS_BORDER,'L'); // le rajout d'un param  ,1 remplie la couleur de fond );
		    } else if (CURRENCY_RIGHT_OR_LEFT == 'L') {
	  		$this->MultiCell($this->text_fw,5,CURRENCY.$vatprice."\t\t\t".CURRENCY.$vatspecialsprice,PRODUCTS_BORDER,'L'); // le rajout d'un param  ,1 remplie la couleur de fond );
		    } else {
                        echo "<b>Choose L or R for CURRENCY_RIGHT_OR_LEFT</b>";
			exit();
		    }
                    $this->LineString($x,$y,$vatprice.CURRENCY,5);
		}
		else if(strlen($data_array[9]))
		{
		    $this->Cell($data_array[0]+6,5,"",0,0);
                    if(CURRENCY_RIGHT_OR_LEFT == 'R') {
			$this->MultiCell($this->text_fw,5,$vatprice.CURRENCY,PRODUCTS_BORDER,'L');
		    }else if(CURRENCY_RIGHT_OR_LEFT == 'L') {
			$this->MultiCell($this->text_fw,5,CURRENCY.$vatprice,PRODUCTS_BORDER,'L');
		    } else {
		    	echo "<b>Choose L or R for CURRENCY_RIGHT_OR_LEFT</b>";
	    		exit();
		    }	
		}
		$this->SetTextColor(0,0,0);
	 }
	 $y2=$this->GetY();
	 
	 //si la description du produit ne prend pas toute la page
	 if($h< $this->ifh)
	 {
		 $this->CalculatedSpace($y1,$y2,$data_array[1]);
 	 }
	 else
	 {
	 	$this->Ln(5);
	 }

	 
	 
/* START Rigadin: add images below description
    $query_xl_images="select p.products_image_xl_1, p.products_image_xl_2, p.products_image_xl_3, p.products_image_xl_4, p.products_image_xl_5, p.products_image_xl_6, p.products_image_xl_7, p.products_image_xl_8
						                from " . TABLE_PRODUCTS . " p
						                where p.products_id='".$data_array[10]."'";

    $query_xl = tep_db_query($query_xl_images);
    $print_xl = tep_db_fetch_array($query_xl);
	   
	   $x = 10;
	   $sm_width = LARGE_IMAGE_WIDTH * PDF_SMALL_IMAGE_FACTOR;
	   $sm_weight = LARGE_IMAGE_HEIGHT * PDF_SMALL_IMAGE_FACTOR;
    for($i=1;$i<=8;$i++) {
	     //if ((substr($print_xl["products_image_xl_$i"], 0, strlen($data_array[2]))==$data_array[2]) &&(substr($print_xl["products_image_xl_$i"],-6)=='xl.jpg')) {
	       $this->SetX($x);//,$y1 + $data_array[1]+5);
  						$this->ShowImage($sm_width,$sm_weight,$link,DIR_FS_CATALOG_IMAGES.$print_xl["products_image_xl_$i"]);
	       $x+=$sm_width + 5;
	      //}
    }
	 $this->setXY (10,$this->GetY() + $sm_weight);
	 $this->Ln(2*PRODUCTS_SEPARATOR);
*/ // END

/*
// START Rigadin: line separation between products
    if ($this->product_separator) {
      $x=$this->GetX();
      $y=$this->GetY();
	  $this->SetFillColor(0,0,0);
      //$this->Rect($x,$y,$this->ifw - $this->rMargin,1,'F');
	  $this->Line($x,$y,$this->ifw,$y);
	  $this->Ln(PRODUCTS_SEPARATOR);
	  $this->product_separator = false; // No separator after 2nd product
	} else $this->product_separator = true;
// END Rigadin
*/
 
 }
 
  function pdf_get_categories($categories_array = '', $parent_id = '0', $indent = '') {
    global $languages_id;

    if (!is_array($categories_array)) $categories_array = array();

    $categories_query = tep_db_query("select c.categories_id, cd.categories_name from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where parent_id = '" . (int)$parent_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by sort_order, cd.categories_name");
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_array[$categories['categories_id']] = array('parent' => $parent_id,
                                                              'name' => $indent . $categories['categories_name']);

      if ($categories['categories_id'] != $parent_id) {
        $categories_array = $this->pdf_get_categories($categories_array, $categories['categories_id'], $indent);
      }
    }

    return $categories_array;
  }
 
 function CategoriesTree($languages_id,$languages_code, $parent_id='0')
 { 
/*    if ($category_id=='') $category_and = '';
      else $category_and = " and c.categories_id='".$category_id."' ";
    //selectionne toute les categories
    $query = "SELECT c.categories_id, cd.categories_name, c.parent_id
             FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd
             WHERE c.categories_id = cd.categories_id and cd.language_id='" . $languages_id ."'".$category_and."
	           ORDER by sort_order, cd.categories_name";

    $categories_query = tep_db_query($query);
    while ($categories = tep_db_fetch_array($categories_query)) {
     //Table de hachage
      $foo[$categories['categories_id']] = array(
		    'name' => $categories['categories_name'],
			  'parent' => $categories['parent_id']);
    }
		*/
		$foo = $this->pdf_get_categories($categories_array,$parent_id);
		//die(print_r($foo, true));

    $this->Order($parent_id, 0, $foo, '');

    if (SHOW_INTRODUCTION) {
	    $this->AddPage();
        $this->TitreChapitre("");
	    $this->AddPage();
        $this->TitreChapitre("");
        $this->Ln(18);
        $file= DIR_FS_CATALOG_LANGUAGES . tep_get_languages_directory($languages_code) . '/pdf_define_intro.php';

//            echo "<br>HHHH " . $file;
        if (file_exists($file)) {
            $file_array = @file($file);
            $file_contents = @implode('', $file_array);
            $this->MultiCell(0,6,html_entity_decode(strip_tags($file_contents)),$this->ifw,1,'J');
        }

    }
    $this->SetFont('Arial','',DIRECTORIES_TREE_FONT_SIZE);
    if (SHOW_TREE) {
        $this->Ln(15);
        $this->MultiCell(0,6,$this->categories_string_spe,0,1,'L');
    }

 }
 
 function CategoriesListing($languages_id, $languages_code)
 {   
    $this->products_index_array=array();
    $this->products_index_list='';
    $this->index_lenght=0;

    //Recuperation de toutes les categories dans l'ordre
    $categoriesidarray=explode(" ",$this->categories_id);
    $categoriesnamearray=explode("\n",$this->categories_string);
    $categorieslevelsarray=explode(" ",$this->levels);
	  
    //Set the pixels dimensions we would like for images
    $imagewidth= MAX_IMAGE_WIDTH;
    $imageheight= MAX_IMAGE_HEIGHT;
	
    for($i=0; $i<sizeof($categoriesidarray)-1; $i++) {
        $category_count_products = tep_products_in_category_count($categoriesidarray[$i],'false');
        if (!((!SHOW_EMPTY_CATEGORIES) and ($category_count_products < 1))) {
            $taille=0;
			$this->product_separator = true; // Added by Rigadin
            $current_category_id=$categoriesidarray[$i];
            $current_category_name=$categoriesnamearray[$i];
            $current_category_level=$categorieslevelsarray[$i];
						// pd.products_info as products_description
            $requete_prod="select p.products_id, pd.products_name, pd.products_info as products_description, p.products_image, p.products_model, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, p.products_date_added, m.manufacturers_name
								  from " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c
								  where products_status = '1' and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and p2c.categories_id='".$current_category_id."'
								  order by pd.products_name, p.products_date_added DESC";

            $SHOW_catalog_query = tep_db_query($requete_prod);
            while ($print_catalog = tep_db_fetch_array($SHOW_catalog_query)) {
			  // Change html to linefeeds
			  $description = str_replace(array('</tr>','</TR>','<br>','<BR>','<br />'), "\n", $print_catalog['products_description']);

                $print_catalog_array[$taille++] = array(
                            'id' => $print_catalog['products_id'],
			            	'name' => $print_catalog['products_name'],		                          
							'description' => $description,
			                'model' => $print_catalog['products_model'],
			            	'image' => $print_catalog['products_image'],
		            		'price' => $print_catalog['products_price'],					    
							'specials_price' => $print_catalog['specials_new_products_price'],
    		       			'tax_class_id' => $print_catalog['products_tax_class_id'],
	               			'date_added' => tep_date_long($print_catalog['products_date_added']),
    	               		'manufacturer' => $print_catalog['manufacturers_name']);
            }

            //recherche le nom de la categorie pere
            $this->$parent_category_name='';
            $this->ParentsName($current_category_level,$i,$categorieslevelsarray, $categoriesnamearray);
                            
            if (($current_category_level == 1) and (CATEGORIES_PAGE_SEPARATOR)) {
                $this->AddPage();
                $this->Ln(120);
                $this->SetFont('Arial','',12);
                $titles_color_table=explode(",",CENTER_TITLES_CELL_COLOR);
                $this->SetFillColor($titles_color_table[0], $titles_color_table[1], $titles_color_table[2]);
                $this->Cell(45,5,"",0,0);
                $this->MultiCell(100,10,$current_category_name,1,'C',1);
            }
    
            if ($taille > 0) { // categorie non vide
                $this->AddPage();
                if (strlen($this->$parent_category_name) > 0 ) {
                    $this->TitreChapitre($this->$parent_category_name. CATEGORIES_SEPARATOR .$current_category_name);
                } else {
                    $this->TitreChapitre($current_category_name);
                }
                $this->Ln(3); // NH
                $this->SetFont('Arial','',11);

                for($j=0; $j<$taille; $j++ ) {
                    // NH si pas d'image definie, image par default 
                    if (strlen($print_catalog_array[$j]['image']) > 0) {
                        $imagepath=DIR_FS_CATALOG_IMAGES.$print_catalog_array[$j]['image'];
                    } else {
                        $imagepath=DIR_FS_CATALOG_IMAGES.'/'.DEFAULT_IMAGE;
                    }
                    $id=$print_catalog_array[$j]['id'];
                    $name=html_entity_decode(rtrim(strip_tags($print_catalog_array[$j]['name'])));
                    $model=rtrim(strip_tags($print_catalog_array[$j]['model']));
                    $description=html_entity_decode(rtrim(strip_tags($print_catalog_array[$j]['description'])));
                    $manufacturer=html_entity_decode(rtrim(strip_tags($print_catalog_array[$j]['manufacturer'])));
                    $price=rtrim(strip_tags($print_catalog_array[$j]['price']));
                    $specials_price=rtrim(strip_tags($print_catalog_array[$j]['specials_price']));
                    $tax_class_id=rtrim(strip_tags($print_catalog_array[$j]['tax_class_id']));
                    $date_added=rtrim(strip_tags($print_catalog_array[$j]['date_added']));
			
                    $data_array=array($imagewidth,$imageheight,$name,$model,$date_added,$manufacturer,$description,$tax_class_id,$specials_price,$price,$id,$languages_code,$imagepath);
                    $this->Ln(PRODUCTS_SEPARATOR); // NH blank space before the products description cells 
                    $this->DrawCells($data_array);
                    if (SHOW_INDEX) {
                        switch (INDEX_EXTRA_FIELD) {
                            case 1 : $this->PrepareIndex($name,$manufacturer,$current_category_name);
                                    break;
                            case 2 : $this->PrepareIndex($name,$model,$current_category_name);
                                    break;
                            case 3 : $this->PrepareIndex($name,$date_added,$current_category_name);
                                    break;
                           default : $this->PrepareIndex($name,"",$current_category_name);
                        }
                    }
                }
            }
        }
    }   
 }
 
  function OneProduct($languages_id, $languages_code, $products_id) {
    $products_query_raw = "select p.products_id, pd.products_name, pd.products_info as products_description, p.products_image, p.products_model, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, p.products_date_added, m.manufacturers_name from " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where products_status = '1' and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and p.products_id='".$products_id."'";
	
    $products_query = tep_db_query($products_query_raw);
    if (tep_db_num_rows($products_query)>0) {
      $products_new = tep_db_fetch_array($products_query);
	    $description = str_replace(array('</tr>','</TR>','<br>','<BR>','<br />'), "\n", $products_new['products_description']);
      $products_new_array = array('id' => $products_new['products_id'],
                                  'name' => $products_new['products_name'],
                                  'image' => $products_new['products_image'],
				                  				'description' => $description,
				                  				'model' => $products_new['products_model'],
                                  'price' => $products_new['products_price'],
                                  'specials_price' => $products_new['specials_new_products_price'],
                                  'tax_class_id' => $products_new['products_tax_class_id'],
                                  'date_added' => tep_date_long($products_new['products_date_added']),
                                  'manufacturer' => $products_new['manufacturers_name']);
      $this->AddPage();
	
      //Set the pixels dimensions we would like for images
      $imagewidth= MAX_IMAGE_WIDTH;
      $imageheight= MAX_IMAGE_HEIGHT;
    
	    $id=$products_new_array['id'];
      $name=html_entity_decode(rtrim(strip_tags($products_new_array['name']))); // Rigadin
	    $model=rtrim(strip_tags($products_new_array['model']));
	    $description=html_entity_decode(rtrim(strip_tags($products_new_array['description']))); // Rigadin
      $manufacturer=html_entity_decode(rtrim(strip_tags($products_new_array['manufacturer']))); // Rigadin
	    $price=rtrim(strip_tags($products_new_array['price']));
	    $specials_price=rtrim(strip_tags($products_new_array['specials_price']));
	    $tax_class_id=rtrim(strip_tags($products_new_array['tax_class_id']));
	    $date_added=rtrim(strip_tags($products_new_array['date_added']));
			
	    $imagepath=DIR_WS_IMAGES.$products_new_array['image'];
	    $data_array=array($imagewidth,$imageheight,$name,$model,$date_added,$manufacturer,$description,$tax_class_id,$specials_price,$price,$id,$languages_code,$imagepath);
	    $this->DrawCells($data_array);
	  } else die('Product not found');
  }
 
 function NewProducts($languages_id, $languages_code)
 {
    $products_new_query_raw = "select p.products_id, pd.products_name, pd.products_description, p.products_image, p.products_model, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, p.products_date_added, m.manufacturers_name from " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where products_status = '1' and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id order by p.products_date_added DESC, pd.products_name";
	
    $products_new_query = tep_db_query($products_new_query_raw);
   
    while($products_new = tep_db_fetch_array($products_new_query)) {
        $products_new_array[] = array('id' => $products_new['products_id'],
                                  'name' => $products_new['products_name'],
                                  'image' => $products_new['products_image'],
				  												'description' => $products_new['products_description'],
				  												'model' => $products_new['products_model'],
                                  'price' => $products_new['products_price'],
                                  'specials_price' => $products_new['specials_new_products_price'],
                                  'tax_class_id' => $products_new['products_tax_class_id'],
                                  'date_added' => tep_date_long($products_new['products_date_added']),
                                  'manufacturer' => $products_new['manufacturers_name']);
    }
  
    $this->AddPage();
    $this->Ln(120);
    $this->SetFont('Arial','',12);
    $new_color_table=explode(",",NEW_CELL_COLOR);
    $this->SetFillColor($new_color_table[0], $new_color_table[1], $new_color_table[2]);
    $this->Cell(45,5,"",0,0);
    $this->MultiCell(100,10,NEW_TITLE,1,'C',1);
    $this->Ln(100);
	
    //Set the pixels dimensions we would like for images
    $imagewidth= MAX_IMAGE_WIDTH;
    $imageheight= MAX_IMAGE_HEIGHT;
    
    for($nb=0; $nb<MAX_DISPLAY_PRODUCTS_NEW; $nb++) {
	    $id=$products_new_array[$nb]['id'];
      $name=html_entity_decode(rtrim(strip_tags($products_new_array[$nb]['name']))); // Rigadin
	    $model=rtrim(strip_tags($products_new_array[$nb]['model']));
	    $description=html_entity_decode(rtrim(strip_tags($products_new_array[$nb]['description']))); // Rigadin
      $manufacturer=html_entity_decode(rtrim(strip_tags($products_new_array[$nb]['manufacturer']))); // Rigadin
	    $price=rtrim(strip_tags($products_new_array[$nb]['price']));
	    $specials_price=rtrim(strip_tags($products_new_array[$nb]['specials_price']));
	    $tax_class_id=rtrim(strip_tags($products_new_array[$nb]['tax_class_id']));
	    $date_added=rtrim(strip_tags($products_new_array[$nb]['date_added']));
			
	    $imagepath=DIR_FS_CATALOG_IMAGES.$products_new_array[$nb]['image'];
	    $data_array=array($imagewidth,$imageheight,$name,$model,$date_added,$manufacturer,$description,$tax_class_id,$specials_price,$price,$id,$languages_code,$imagepath);
	    $this->DrawCells($data_array);
    }
 }

 function TitreChapitre($lib) {
    //Titre
    $this->SetFont('Arial','',12);
    $titles_color_table=explode(",",HEIGHT_TITLES_CELL_COLOR);
    $this->SetFillColor($titles_color_table[0], $titles_color_table[1], $titles_color_table[2]);
    $this->Cell(0,6,$lib,$this->ifw,1,'L',1);
    $this->Ln(2);
    //Sauvegarde de l'ordonnée
    $this->y0=$this->GetY();
 }
function CleanFiles($dir)
{
    //Delete temporary files
    $t=time();
    $h=opendir($dir);
    while($file=readdir($h))
    {
        if(substr($file,0,11)=='prodpdf_tmp' and substr($file,-4)=='.pdf')
        {
            $path=$dir.'/'.$file;
            if($t-filemtime($path)>3600)
                @unlink($path);
        }
    }
    closedir($h);
}
} // END class
?>