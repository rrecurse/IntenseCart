 function showLoginForm() {
    $('login_form').innerHTML=
' <form name="login" action="\/checkout.php?action=login&amp" method="POST">\n'+
'          <table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" class="infoBox">\n'+
'            <tr class="infoBoxContents">\n'+
'              <td style="padding-top:10px;">\n'+
'                <table width="100%" border="0" align="center" cellpadding="5" cellspacing="5">\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b>*My e-mail address is:  &nbsp;<\/b><\/td>\n'+
'                    <td class="main"><input type="text" name="email_address" style="width:150px; height:20px"><\/td>\n'+
'                  <\/tr>\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b>*My password is:&nbsp;<\/b><\/td>\n'+
'                    <td class="main"><input type="password" name="password" style="width:150px; height:20px"><\/td>\n'+
'                  <\/tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" class="smallText" align="center"><a href="\/password_forgotten.php">Password forgotten? Click here.<\/a><\/td>\n'+
'                  <\/tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" align="center" style="padding-right:5px;"><input type="image" src="\/layout\/img\/buttons\/english\/button_login.gif" style="border:none" alt="Sign In" title=" Sign In "><\/td>\n'+
'                  <\/tr>\n'+
'                <\/table>\n'+
'              <\/td>\n'+
'            <\/tr>\n'+
'          <\/table>\n'+
'          <\/form>\n';
  }