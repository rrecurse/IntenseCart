
<script language="javascript">

function applyManagerDiscount(amount) {
  var url='<?=HTTPS_SERVER?>/admin/manager_discount_box.php';
  url+='?amount='+escape(amount);
  new ajax(url, {method: 'get', update: $('manager_discount_box')});
}

</script>

<table>
<tr>
  <td>Manager Discount:</td>
  <td><input type="text" name="manager_discount_amount" onChange="applyManagerDiscount(this.value)"><button onClick="return false;">Apply</button></td>
  <td><div id="manager_discount_box"></div></td>
</tr>
</table>
