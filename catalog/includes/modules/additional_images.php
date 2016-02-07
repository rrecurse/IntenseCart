    <tr>
      <td><table width="100%">
       <tr>

<?php
    if($product_info['products_image_xl_1'] != '') {
?>
     <td align="center" class="smallText">
           <?php echo tep_image(DIR_WS_IMAGES . $product_info['products_image_xl_1'], $product_info['products_name'], LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT, 'hspace="1" vspace="1"'); ?>
      </td>
<?php
    }

  if($product_info['products_image_xl_2'] != '') {
?>
     <td align="center" class="smallText">
           <?php echo tep_image(DIR_WS_IMAGES . $product_info['products_image_xl_2'], $product_info['products_name'], LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT, 'hspace="1" vspace="1"'); ?>
      </td>
<?php
    }

    if($product_info['products_image_xl_3'] != '') {
?>
     <td align="center" class="smallText">
           <?php echo tep_image(DIR_WS_IMAGES . $product_info['products_image_xl_3'], $product_info['products_name'], LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT, 'hspace="1" vspace="1"'); ?>
      </td>
<?php
    }
?>
</tr>
<tr>


<?php
    if($product_info['products_image_xl_4'] != '') {
?>
     <td align="center" class="smallText">
           <?php echo tep_image(DIR_WS_IMAGES . $product_info['products_image_xl_4'], $product_info['products_name'], LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT, 'hspace="1" vspace="1"'); ?>
      </td>
<?php
    }

    if($product_info['products_image_xl_5'] != '') {
?>
     <td align="center" class="smallText">
           <?php echo tep_image(DIR_WS_IMAGES . $product_info['products_image_xl_5'], $product_info['products_name'], LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT, 'hspace="1" vspace="1"'); ?>
      </td>
<?php
    }
?>


<?php
    if($product_info['products_image_xl_6'] != '') {
?>
     <td align="center" class="smallText">
           <?php echo tep_image(DIR_WS_IMAGES . $product_info['products_image_xl_6'], $product_info['products_name'], LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT, 'hspace="1" vspace="1"'); ?>
      </td>
<?php
    }
?>

     </tr>
        </table></td>
     </tr>
