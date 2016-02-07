<?php
/*
$id author Puddled Internet - http://www.puddled.co.uk
  email support@puddled.co.uk
   
  

  

  

*/


?>


<!-- body //-->



      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">

          <?

         $account_query = tep_db_query("SELECT customers_firstname, customers_lastname, customers_email_address FROM " . TABLE_CUSTOMERS . " where customers_id = '" . $customer_id . "'");
         $account = tep_db_fetch_array($account_query);
         // query the order table, to get all the product details

?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" cellspacing="0" cellpadding="2" width=100%>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td width="30%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_PRODUCT_RETURN; ?></b><BR></td>
              </tr>



            </table></td>
            <td width="70%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">

                  <tr>
                    <td class="main" colspan="3"><b>&nbsp; &nbsp;<?php echo HEADING_PRODUCTS; ?></b></td>
                  </tr>
<?php

//  $ordered_product_query = tep_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " where order_id = '" . $HTTP_GET_VARS


    echo '          <tr>' . "\n" .
         '            <td class="main" align="right" valign="top" width="30">' . $returned_products['products_quantity'] . '&nbsp;x</td>' . "\n" .
         '            <td class="main" valign="top">' . $returned_products['products_name'];


echo '</td>' . "\n";
echo '            <td class="main" align="right" valign="top">' . $currencies->format(($returned_products['products_price'] + (tep_calculate_tax(($returned_products['products_price']),($returned_products['products_tax'])))) * ($returned_products['products_quantity'])) . '</td>' . "\n" .
         '          </tr>' . "\n";

?>
                </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>


        <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '15'); ?></td>
          </tr>
              <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_BILLING_ADDRESS; ?></b></td>
              </tr>
              <tr>
                <td class="main">&nbsp;</td>
              </tr>
           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' . tep_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br>') . '</td>' . "\n" .
         '              </tr>' . "\n";
           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_DELIVERY_ADDRESS; ?></b></td>
              </tr>
              <tr>
                <td class="main">&nbsp;</td>
              </tr>
           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' . tep_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>') . '</td>' . "\n" .
         '              </tr>' . "\n";
           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>

      <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_USER_EMAIL; ?></b></td>
              </tr>

           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' . $account['customers_email_address'] . tep_draw_hidden_field('support_user_email', $account['customers_email_address']) . '</td>' . "\n" .
         '              </tr>' . "\n";

           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_WHY_RETURN; ?></b></td>
              </tr>
           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                <tr>
            <td class=main width="5%">&nbsp;</td>
            <td class="main" width="95%"><?php //echo tep_draw_input_field('link_url'); ?>
          <?php
            $reason_query = tep_db_query("SELECT return_reason_name FROM " . TABLE_RETURN_REASONS . " where return_reason_id = '" . $returned_products['returns_reason'] . "' and language_id = '" . $languages_id . "'");
            $reason = tep_db_fetch_array($reason_query);

             echo $reason['return_reason_name'];
          ?>
            </td>
          </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
                <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
       <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_TEXT; ?></b></td>
              </tr>
              <tr>
                <td class="main">&nbsp;</td>
              </tr>
           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' . nl2br($returned_products['comments']) . '</td>' . "\n" .
         '              </tr>' . "\n";

           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>
                <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>

       </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td colspan="2" class="main" valign="top" nowrap align="center">




          </tr>
        </table></td>
      </tr>



             
             
             <!--
             
             -->

            </td>
          </tr>
        </table></td>
      </tr>

    </table></td>
