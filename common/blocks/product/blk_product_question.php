<?
ini_set('display_errors','On');
error_reporting(E_ALL ^ E_NOTICE);
class blk_product_question extends IXblock {

  function render(&$body) {
$product_question_success ='';
//VISUAL VERIFY CODE start
require('/usr/share/IXcore/catalog/includes/functions/visual_verify_code.php');
//require(DIR_WS_FUNCTIONS . 'visual_verify_code.php');
    $code_query = tep_db_query("select code from visual_verify_code where ixsid = '" . tep_session_id($_GET[tep_session_name()]) . "'");
    $code_array = tep_db_fetch_array($code_query);
    $code = $code_array['code'];

    tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE ixsid='" . $vvcode_oscsid . "'"); //remove the visual verify code associated with this session to clean database and ensure new results

    $user_entered_code = $_POST['visual_verify_code'];
    if (!(strcasecmp($user_entered_code, $code) == 0)) {    //make the check case insensitive
        $error = true;
       // $messageStack->add('product_question', VISUAL_VERIFY_CODE_ENTRY_ERROR);
    }
//VISUAL VERIFY CODE stop
?>
 <?php 

if($GLOBALS['product_question_success'] == 'yes') { ?>
<table border="0" cellspacing="0" cellpadding="0" class="prodQuestion_messageTable">          <tr>
            <td class="prodQuestion_messagesent">Your message was successfully sent.  We'll reply as soon as possible.<br><br></td>
          </tr>
</table>
<?php
 } else { 
?>

<?php if($GLOBALS['product_question_success'] == 'no') { ?>

<table border="0" cellspacing="0" cellpadding="0" class="prodQuestion_messageTable">          <tr>
            <td class="prodQuestion_messageNOTsent">MESSAGE NOT SENT - Please check your details, such as captcha code and try again..<br><br></td>
          </tr>
</table>
<?php
 }
?>
<form names="product_question" method="post" action="/index.php?products_id=<?php echo $GLOBALS['products_id']?>&amp;action=product_question">

<div id="product_question">

	  <input id="product_name" type="hidden" name="product_name" value="<?php echo strip_tags($GLOBALS['products_name'])?>">
<table border="0" cellspacing="0" cellpadding="0" class="prodQuestion_maintable">
        <tr>
<td class="prodQuestion_title">Have a product related question?</td>
</tr>
<tr>
            <td class="prodQuestion_Email">Email Address:</td>
          </tr>
          <tr>
            <td class="prodQuestion_Emailinput"><input id="email" type="text" name="email"></td>
          </tr>
          <tr>
            <td class="prodQuestion_Questiontitle">What&lsquo;s your Question?</td>
          </tr>
          <tr>
            <td><textarea id="enquiry" name="enquiry" class="prodQuestion_textarea" cols="50" rows="5"></textarea></td>
          </tr>
          
        </table>

<table>
  <tr>
        <td class="main"><b><?php echo VISUAL_VERIFY_CODE_CATEGORY; ?></b></td>
      </tr>
      <tr>
        <td class="main"><?php echo VISUAL_VERIFY_CODE_TEXT_INTRO; ?><br></td>
      </tr>
  
	  <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
          <td class="main"><?php echo VISUAL_VERIFY_CODE_TEXT_INSTRUCTIONS; ?></td>
                <td><?php echo tep_draw_input_field('visual_verify_code','','id="visual_verify_code"','text') . '&nbsp;' . '<span class="inputRequirement">' . VISUAL_VERIFY_CODE_ENTRY_TEXT . '</span>'; ?></td>

                <td class="main">
                  
                  <?php
                  // ----- begin garbage collection --------
$included_code_query = tep_db_query("SELECT ixsid, code, dt FROM " . TABLE_VISUAL_VERIFY_CODE);
$endtime = time();

while ($included_code = tep_db_fetch_array($included_code_query)) {
  $starttime=mktime(
    substr($included_code['dt'], 6, 2),	// hour
    substr($included_code['dt'], 8, 2),	// minute
    substr($included_code['dt'], 10, 2),// second
    substr($included_code['dt'], 2, 2),	// month
    substr($included_code['dt'], 4, 2),	// day
    substr($included_code['dt'], 0, 2)	// year
  );
  $timediff = intval(($endtime-$starttime)/3600);

  if ($timediff > 5) {	// 5+ hours should be enough to fill in a form
    tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE code='" .$included_code['code'] . "' AND dt='" .$included_code['dt'] . "'");
  }  
}
// ----- end garbage collection --------

 //can replace the following loop with $visual_verify_code = substr(str_shuffle (VISUAL_VERIFY_CODE_CHARACTER_POOL), 0, rand(3,6)); if you have PHP 4.3
                    $visual_verify_code = "";
                    for ($i = 1; $i <= rand(3,6); $i++){
                          $visual_verify_code = $visual_verify_code . substr(VISUAL_VERIFY_CODE_CHARACTER_POOL, rand(0, strlen(VISUAL_VERIFY_CODE_CHARACTER_POOL)-1), 1);
                     }
                     $vvcode_oscsid = tep_session_id($_GET[tep_session_name()]);
                     tep_db_query("DELETE FROM " . TABLE_VISUAL_VERIFY_CODE . " WHERE ixsid='" . $vvcode_oscsid . "'");
                     $sql_data_array = array('ixsid' => $vvcode_oscsid, 'code' => $visual_verify_code);
                     tep_db_perform(TABLE_VISUAL_VERIFY_CODE, $sql_data_array);
//                     $visual_verify_code = "";
$vvc = $visual_verify_code;


echo'<img src="/CaptchaSecurityImages.php?vvc='.$vvc.'" alt="" />';
?>
                </td>
                <td class="main"><?php echo VISUAL_VERIFY_CODE_BOX_IDENTIFIER; ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<tr>
            <td class="prodQuestion_submit">


<input type="image" name="submit_question" onClick="productQuestion($('product_name').value,$('email').value,$('enquiry').value,$('visual_verify_code').value)" src="/layout/img/buttons/english/button_continue.gif">


<!--input type="image" src="/layout/img/buttons/english/button_continue.gif"--></td>
          </tr>
</table>

</div>

</form>

<script type="text/javascript">
function productQuestionSend() {
  $('product_question').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Verifying Captcha code, please wait...<br><img src="images\/loading_bar.gif" alt=""><\/td><\/tr><\/table>';
  new ajax ('<?php echo !empty($_SERVER['HTTPS'])?'https':'http';?><?php echo '://'.$_SERVER['HTTP_HOST'];?>/index.php?action=product_question', {postBody: 'product_name='+document.product_question.product_name.value+'&email='+document.product_question.email.value+'&enquiry='+document.product_question.enquiry.value+'&visual_verify_code='+document.product_question.visual_verify_code.value, update: $('product_question')});
}
</script>
<?
} // end if sent condition 

  } // end function render(&$body)

function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'nocontent': '';
      default: return true;
    }
  }

}
?>