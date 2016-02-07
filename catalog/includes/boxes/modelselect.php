<?
  $cat_query = tep_db_query("SELECT c.categories_id as id, cd.categories_name as name FROM categories c LEFT JOIN categories_description cd ON (c.categories_id = cd.categories_id) WHERE cd.language_id = '".$languages_id."' AND c.parent_id = '0' ORDER BY cd.categories_name");
  $dd1 = '';
  while ($cat = tep_db_fetch_array($cat_query)) {
    $dd1 .= '<option value="' . $cat['id'] . '">' . $cat['name'] . "</option>\r\n";
  }
?>
<table align="center" cellpadding="0" cellspacing="0" style="width:158px; ">

<tr>
<td valign="top">
<form name="formgoto" action="index.php?action=quickjump" method="post">
<table border="0" cellpadding="0" cellspacing="0" style="width:160px;">
  <tr>
    <td align="center" style="padding-top:4px; padding-left:4px;"><font color="#336699" style="font-size: 15px; font-weight:bold;">Quick
        Model Search</font></td>
  </tr>
  <tr>
    <td style="padding-top:4px; padding-left:5px;"><font color="#336699" style="font-size: 11px; font-weight:bold;">Start Your Search Here!</font></td>
  </tr>
  <tr>
    <td style="padding-top:4px;" align="center"><select id="dd1" name="dd1" onChange="gendd2()" style="width: 148px; font-size: 8pt; font-family:Verdana;">
      <? echo $dd1; ?>
    </select></td>
  </tr>
  <tr>
    <td style="padding-top:4px;" align="center"><select id="select" name="dd2" onChange="gendd3()" style="width: 148px; font-size: 8pt; font-family:Verdana;">
      <option></option>
    </select></td>
  </tr>
  <tr>
    <td style="padding-top:4px;" align="center"><select id="select2" name="dd3" style="width: 148px; font-size: 8pt; font-family:Verdana;">
      <option></option>
    </select></td>
  </tr>
  <tr>
  <td align="center" style="padding-top:2px;"><? echo tep_image_submit('go.gif', BOX_HEADING_TELL_A_FRIEND); ?></td>
  </tr>
</table>
</form>

</td>
</tr>
</table>
<script type="text/javascript" language="javascript">gendd2();</script>
