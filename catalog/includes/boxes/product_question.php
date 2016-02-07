<table border="0" cellspacing="0" cellpadding="0" class="prodQuestion_maintable">
          <? if ($product_question_success) { ?>
          <tr>
            <td class="prodQuestion_messagesent">Your message was successfully sent.  We'll reply as soon as possible.<br><br></td>
          </tr>
          <? } ?>
          <?php echo tep_draw_form('contact_us', tep_href_link($_SERVER['PHP_SELF'], 'products_id=' . (int)$HTTP_GET_VARS['products_id'] . '&action=product_question')) . tep_draw_hidden_field('product_name', $product_info['products_name']); ?>
        <tr>
<td class="prodQuestion_title">Have a product related question?</td>
</tr>
<tr>
            <td class="prodQuestion_Email">Email Address:</td>
          </tr>
          <tr>
            <td class="prodQuestion_Emailinput"><?php echo tep_draw_input_field('email'); ?></td>
          </tr>
          <tr>
            <td class="prodQuestion_Questiontitle">What's your Question?</td>
          </tr>
          <tr>
            <td>
<!--textarea name="enquiry" cols="10" rows="5" class="prodQuestion_textarea"></textarea-->

<?php echo tep_draw_textarea_field('enquiry', 'soft', '', '', '', 'class="prodQuestion_textarea"'); ?></td>
          </tr>
          <tr>
            <td class="prodQuestion_submit"><?php echo tep_image_submit('go.gif', IMAGE_BUTTON_CONTINUE); ?></td>
          </tr>
          </form>
        </table>
