<table align="center" width="70%" border=0 bgcolor="#FFFFFF" cellspacing="0" cellpadding="2">
<tr bgcolor="#FFFFFF">
	<td align="center" height="41" valign="middle"> <div align="center"><font color="#49516f"><b><font face="Arial" size=2>URL List</font></b></font></div></td>
	<td align="center" height="41" valign="middle"> <div align="center"><font color="#49516f"><b><font face="Arial" size=2>Total</font></b></font></div></td>
	<td align="center" height="41" valign="middle"> <div align="center"><font color="#49516f"><b><font face="Arial" size=2>Alltheweb</font></b></font></div></td>
	<td align="center" height="41" valign="middle"> <div align="center"><font color="#49516f"><b><font face="Arial" size=2>Google<br>AOL</font></b></font></div></td>
	<td align="center" height="41" valign="middle"> <div align="center"><font color="#49516f"><b><font face="Arial" size=2>MSN</font></b></font></div></td>
	<td align="center" height="41" valign="middle"> <div align="center"><font color="#49516f"><b><font face="Arial" size=2>Yahoo!<br>Altavista<br>Fast</font></b></font></div></td>
</tr>
<?
foreach($res_final as $k=>$t)
{
echo "<tr bgcolor=".get_color($stat[$t][0]['total']).">
	<td align=center bgcolor=".get_color($stat[$t][0]['total'])." height=16 valign=top><font face=Arial size=2>";
	if($t==$urls[0] || $t==$urls[1] || $t==$urls[2] || $t==$urls[3]) echo "<b>$t</b>";
	else echo $t;
	echo "</font></td>
	<td align=center bgcolor=".get_color($stat[$t][0]['total'])." height=16 valign=top><font face=Arial size=2><b>".number_format($stat[$t][0]['total'])."</b>&nbsp;</font></td>
	<td align=center bgcolor=".get_color($stat[$t][0]['total'])." height=16 valign=top><font face=Arial size=2><a target=\"new\" href=\"http://www.alltheweb.com/search?avkw=fogg&cat=web&cs=utf-8&q=".urlencode("link:").urlencode($t)."&_sb_lang=any\">".number_format($stat[$t][0]['alltheweb'])."</a>&nbsp;</font></td>
	<td align=center bgcolor=".get_color($stat[$t][0]['total'])." height=16 valign=top><font face=Arial size=2><a target=\"new\" href=\"http://www.google.com/search?as_lq=".urlencode($t)."&btnG=Search\">".number_format($stat[$t][0]['google'])."</a>&nbsp;</font></td>
	<td align=center bgcolor=".get_color($stat[$t][0]['total'])." height=16 valign=top><font face=Arial size=2><a target=\"new\" href=\"http://www.bing.com/search?q=" . urlencode($t)."&FORM=MSNH11&qs=n\">".number_format($stat[$t][0]['msn'])."</a>&nbsp;</font></td>
	<td align=center bgcolor=".get_color($stat[$t][0]['total'])." height=16 valign=top><font face=Arial size=2><a target=\"new\" href=\"http://search.yahoo.com/search?p=".urlencode("link:http://").urlencode($t)."&ei=UTF-8&fr=sfp&n=20&fl=0&x=wrt\">".number_format($stat[$t][0]['yahoo'])."</a>&nbsp;</font></td>
</tr>";
}
?>
</table>
<br><br>

