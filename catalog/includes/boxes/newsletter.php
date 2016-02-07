<?php
/*
  $Id: information.php,v 1.5 2002/01/11 22:04:06 dgw_ Exp $
  
 
  
  
*/
?>
<!-- subscribers //-->
          <tr>
            <td>

<?php
  $info_box_contents = array();
  $info_box_contents[] = array('align' => 'left',
                               'text'  => BOX_HEADING_SUBSCRIBERS
                              );
  new infoBoxHeading($info_box_contents, false, false);

  ?>

<script language="JavaScript1.2" type="text/javascript">
        function verify(form)
        {
           var passed = false;
        var blnRetval, intAtSign, intDot, intComma, intSpace, intLastDot, intDomain, intStrLen;
        if (form.Email){
                       intAtSign=form.Email.value.indexOf("@");
                        intDot=form.Email.value.indexOf(".",intAtSign);
                        intComma=form.Email.value.indexOf(",");
                        intSpace=form.Email.value.indexOf(" ");
                        intLastDot=form.Email.value.lastIndexOf(".");
                        intDomain=intDot-intAtSign;
                        intStrLen=form.Email.value.length;
                // *** CHECK FOR BLANK EMAIL VALUE
                   if (form.Email.value == "" )
                   {
                alert("You have not entered an email address.");
                form.Email.focus();
                passed = false;
                }
                // **** CHECK FOR THE  @ SIGN?
                else if (intAtSign == -1)
                {

                alert("Your email address is missing the \"@\".");
                        form.Email.focus();
                passed = false;

                }
                // **** Check for commas ****

                else if (intComma != -1)
                {
                alert("Email address cannot contain a comma.");
                form.Email.focus();
                passed = false;
                }

                // **** Check for a space ****

                else if (intSpace != -1)
                {
                alert("Email address cannot contain spaces.");
                form.Email.focus();
                passed = false;
                }

                // **** Check for char between the @ and dot, chars between dots, and at least 1 char after the last dot ****

                else if ((intDot <= 2) || (intDomain <= 1)  || (intStrLen-(intLastDot+1) < 2))
                {
                alert("Please enter a valid Email address.\n" + form.Email.value + " is invalid.");
                form.Email.focus();
                passed = false;
                }
                else {
                        passed = true;
                }
        }
        else    {
                passed = true;
        }
        return passed;
  }
        //-->
</script>
<?php
  $info_box_contents = array();
  $info_box_contents[] = array('align' => 'left',
                               'text'  =>  '<span class="smallText"><b>Note:</b> Registered customers go to: <a href="' . tep_href_link(FILENAME_ACCOUNT_NEWSLETTERS, '', 'NONSSL') . '"><u>Your Account</u></a> to subscribe.</span><br><br><form name="newsletter" action="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBE, '', 'NONSSL') . '" method="post" onSubmit="return verify(this);">' . TEXT_EMAIL . '<br><input type="text" name="Email" value="" checked size="15" maxlength="35"><br>' . TEXT_NAME . '<br><input type="text" name="lastname" value="" checked size="15" maxlength="35">' . '<input type="submit" name="Submit" value="go">' . '<a href="' . tep_href_link(FILENAME_NEWSLETTERS, '', 'NONSSL') . '"></form>');

  new infoBox($info_box_contents);
?>
            </td>
          </tr>
<!-- subscribers_eof //-->