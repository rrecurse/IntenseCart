          <tr>
            <td>
<?php
 if(!$HTTP_GET_VARS['products_id'])
   {//ADDED AS PART OF TELL EVEN WITHOUT PRODUCT MOD.  MIGHT WANT TO ADD YOUR NAME WHERE 'us' IS.
   $tellFriendWhat = '<br><div class="tellfriend_txt">We\'d love it if you tell a friend about us <br> (you can also add a messege on the next page)</div>';
   $myProductId = false;
  }
  else
  {
  	$tellFriendWhat = BOX_TELL_A_FRIEND_TEXT;
    $myProductId = $HTTP_GET_VARS['products_id'];
  }

  $info_box_contents = array();
  $info_box_contents[] = array('text' => '');

  new infoBoxHeading($info_box_contents, false, false);

  $info_box_contents = array();
  $info_box_contents[] = array('form' => tep_draw_form('tell_a_friend', tep_href_link(FILENAME_TELL_A_FRIEND, '', 'NONSSL', false), 'post') . ($myProductId ? tep_draw_hidden_field('products_id', $myProductId) : ''),
                               'align' => 'center',
                               'text' => tep_draw_input_field('to_email_address', '', 'size="10"') . '&nbsp;' . tep_image_submit('button_tell_a_friend.gif', BOX_HEADING_TELL_A_FRIEND) . tep_draw_hidden_field('products_id', $myProductId) . tep_hide_session_id() . '<br>' . $tellFriendWhat);

  new infoBox($info_box_contents);
?>
            </td>
          </tr>
