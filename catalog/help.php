<?php 

	if(isset($_REQUEST['phone'])){
		$to = 'chrisd@zwaveproducts.com';

		$subject = 'Customer problem at checkout';

		$from = 'support@zwaveproducts.com';

		$phone = preg_replace('/(\d{3})([.-])?(\d{3})\2(\d{4})/', '$1-$3-$4', $_REQUEST['phone']);
	
		$headers = 'From: '.$from . "\r\n" .
				   'Reply-To: '.$from. "\r\n" .
				   'X-Mailer: PHP/' . phpversion();

		$message = 'IP Address:  '.$_SERVER['REMOTE_ADDR']. "\r\n" .
				   'Issue: '. $_REQUEST['issue']. "\r\n" .
			       'Phone: '. $phone . "\r\n" .
				   'Browser: '. $_REQUEST['browserT'] . "\r\n" .
				   'Version: '.  filter_var($_REQUEST['browserV'], FILTER_SANITIZE_STRING);

		if (preg_match('/^\(?[0-9]{3}\)?|[0-9]{3}[-. ]? [0-9]{3}[-. ]?[0-9]{4}$/', $phone)) {
			mail($to,$subject,$message,$headers);
		}

	}

$host = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
$host = $host .'://'.$_SERVER['HTTP_HOST'];
?>

<script type="text/javascript">
jQuery.noConflict();
jQuery(document).ready(function(){    
    jQuery("form#checkoutProblems").submit(function(event) {
        event.preventDefault();
	
		// grab all feilds and serialize
		var formData = jQuery(this).serialize();

		// do some validation on the help form
		var valid = DoValidatePhone();
		if(valid === false) return;

		var issue = jQuery("#issue").val();
		var browserT = jQuery("#browserT").val();
		var browserV = jQuery("#browserV").val();
		var phone = jQuery("#phone").val();

		if(issue == 'none') {
			alert('Please specify what issue you\'re having'); 
			return;
		}
	
		if(browserT == 'none') {
			alert('Please specify what web browser you\'re using'); 
			return;
		}
	
		if(phone == '' || null === phone) {
			alert('Phone cannot be blank'); 
			return;
		}
		jQuery.ajax({
            type: "POST",
            url: "help.php",
            data: formData,
            success: function(){
				jQuery("#helpTable").html('<table border="0" width="100%" cellpadding="5"><tr><td align="center" style="font:bold 12px arial; color:red">Thank You. Your alert has been sent. We will contact you at the phone number you listed.<\/td><\/tr><\/table>').css({'height' : '100%'});
			}
        });
		return false;
    });

});



function DoValidatePhone() {
             
    var stripped = jQuery("#phone").val();
    var isGoodMatch = stripped.match(/^[0-9\s(-)]*$/);
    if (!isGoodMatch) {
        alert("The Contact number contains invalid characters." + stripped);
        return false;
    }
}

</script>

<form name="checkoutProblems" id="checkoutProblems" method="POST">
<table width="200" cellpadding="5" cellspacing="0" style="border:dashed 1px #999999; border-radius:5px">
<tr><td>
<table width="200" cellpadding="10" cellspacing="0" style="cursor: pointer;" id="expander">
<tr>
<td colspan="2" align="center" style="padding:6px 0 4px 0"><span style="color: #009933; user-select: none;-moz-user-select: none;"><b style="font:bold 15px arial">Problems Checking Out? </b></span><br>
  <span style="font:normal 11px arial; color:#999999;user-select: none;-moz-user-select: none; line-height:20px">(Click here to let us know!)</span> </td>
</tr>
</table>
<table width="200" height="250" cellpadding="10" cellspacing="0" id="helpTable" style="display:none;">
<tr>
  <td colspan="2" style="padding:0" align="center"><b style="font:bold 13px arial">What's the issue?</b> 
<span style="font:normal 11px arial;">(Errors?)</span> </td>
</tr>
<tr>
  <td colspan="2"  align="center">
	<select name="issue" id="issue">
    <option selected="selected" value="none">Please Select:</option>
	<option value="I'm not sure">I'm not sure</option>
    <option value="Processing too long">Processing too long</option>
    <option value="I'm getting Errors">I'm getting Errors</option>
    <option value="Card being rejected">Card being rejected</option>
    <option value="My card type isnt listed">My card type isnt listed</option>
  </select>  </td>
</tr>

<tr>
<td colspan="2" style="padding:10px 0 0 0" align="center"><b style="font:bold 13px arial">What Browser?</b>  <span style="font:normal 11px arial;">(IE?,  Firefox?)</span> </td>
</tr>
<tr>
<td colspan="2"  align="center">
  <select name="browserT" id="browserT">
	<option selected="selected" value="none">Please Select:</option>
	<option value="Not Sure">I'm not sure</option>
	<option value="IE">Internet Explorer</option>
	<option value="FF">FireFox</option>
	<option value="gchrome">Google Chrome</option>
	<option value="safari">Safari</option>
					  <option value="mobile">Mobile Browser</option>
  </select>  </td>
</tr>
<tr>
  <td align="right"><b style="padding:0 0 10px 0; font:bold 13px arial"> Version?</b></td>
  <td align="left" style="padding:0"><input name="browserV" id="browserV" type="text" value="" size="13"></td>
</tr>
<tr>
  <td colspan="2" align="center"><b style="font:bold 12px arial">Call me to complete my order?</b></td>
  </tr>
<tr>
  <td align="right"><b style="font:bold 12px arial"> Phone:</b></td>
  <td align="center" style="padding:0 5px 0 0"><input name="phone" id="phone" type="text" value="" size="13"></td>
</tr>
<tr>
  <td colspan="2" align="center"><input type="image" src="<?php echo (defined('CDN_CONTENT') ? CDN_CONTENT : '');?>/layout/img/buttons/english/button_send.gif"></td>
  </tr>
</table>
</td></tr></table>
</form>
