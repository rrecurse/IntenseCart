<?php

/* This funtion has the responsibility of displaying the actual visual code with random results.
   It randomly picks an x and y position as well as font size for each character in the visual code
*/
function vvcode_render_code($code) {
        if (!empty($code)) {
            $imwidth=200;
            $imheight=40;
         $font_size = $imheight * 0.65;
         $font_position = $imheight * 0.30;
header('Content-Type: image/jpeg');
$im = @ImageCreate ($imwidth, $imheight) or die ("Cannot Initialize new GD image stream");
$background_color = ImageColorAllocate ($im, 255,255,255);
            $text_color = ImageColorAllocate ($im, 20,40,100);
            $border_color = ImageColorAllocate ($im, 0,0,0);
         $noise_color = ImageColorAllocate($im, 200, 200, 200);
         
         /* generate random dots in background */
            for( $n=0; $n<($imwidth*$imheight)/3; $n++ ) {
            imagefilledellipse($im, mt_rand(0,$imwidth), mt_rand(0,$imheight), 1, 1, $noise_color);
            }
         /* generate random lines in background */
            for( $n=0; $n<($imwidth*$imheight)/150; $n++ ) {
            imageline($im, mt_rand(0,$imwidth), mt_rand(0,$imheight), mt_rand(0,$imwidth), mt_rand(0,$imheight), $noise_color);
            }
      
         //strip any spaces that may have crept in
            //end-user wouldn't know to type the space! :)
            $code = str_replace(" ", "", $code);
            $x=0;

            $stringlength = strlen($code);

            for ($i = 0; $i< $stringlength; $i++) {
                 $x = $x + $font_size;
                 $y = $font_position;
             $font = ImageLoadFont("/usr/share/IXcore/catalog/includes/fonts/automatic.gdf");
                 $single_char = substr($code, $i, 1);
                 imagechar($im, $font, $x, $y, $single_char, $text_color);
                }


            imagerectangle ($im, 0, 0, $imwidth-1, $imheight-1, $border_color);
            ImageJpeg($im);
            ImageDestroy;
        }
  }
?>
