
<script language="javascript">

function doMemberLogin(email,crypt) {
  $('member_login_email').value=email;
  $('member_login_crypt').value=crypt;
  $('member_login_email').form.submit();
}

var lookupTimeout;
var lookupName;

function MembersLookup(name) {
  clearTimeout(lookupTimeout);
  lookupTimeout=setTimeout('MembersLookupLaunch("'+name+'")',500);
}

function MembersLookupLaunch(name) {
  clearTimeout(lookupTimeout);
  if (name==lookupName) return;
  lookupName=name;
  if (name!='') {
    var url='<?=$find_members_box_url?>';
    url+='?name='+escape(name);
    url+='&max=10';
    $('members_lookup').innerHTML='Looking Up...';
    new ajax(url, {method: 'get', update: $('members_lookup')});
  } else {
    $('members_lookup').innerHTML='';
  }
}

</script>

<input type="hidden" name="email_address" value="" id="member_login_email">
<input type="hidden" name="password_crypt" value="" id="member_login_crypt">

<table>
<tr>
  <td valign="top">Quick Find Member:<br><input type="text" name="member_login_quick_find" onKeyUp="MembersLookup(this.value)" onChange="MembersLookupLaunch(this.value)">
  </td>
  <td valign="top"><div id="members_lookup"></div>
  </td>
</tr>
</table>
