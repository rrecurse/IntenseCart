<br><table cellpadding="10" cellspacing="0" align="center" style="border:solid 1px #3366cc; width:551px">
<tr>
	<td align="center" bgcolor="#e0e5ff">
		<b style="font:bold 14px arial">Link Popularity Comparison Tool</b>
	</td>
</tr>
<tr>
	<td align="center" bgcolor="#FFFFFF"><font size="3">
<form action="link-popularity-comparison.php" method="POST">
<input type="hidden" name="c" value="1">
<p>
<div style="font:normal 12px arial"><b>Your Website</b> (eg:  <?= $_SERVER['HTTP_HOST']?>)</div>
&nbsp; &nbsp; &nbsp; <input type="text" name="urls[0]" value="<?= $_SERVER['HTTP_HOST']?>" size="50"><br><br>
	<span style="font:bold 12px arial; text-align:left">Compare to:</span><br>
#1 <input type="text" name="urls[1]" value="<?=$urls[1]?>" size="50"><br>
#2 <input type="text" name="urls[2]" value="<?=$urls[2]?>" size="50"><br>
#3 <input type="text" name="urls[3]" value="<?=$urls[3]?>" size="50">
</p>
<input type="submit" value="Go">
</form>
<p style="font:normal 11px arial; text-align:justify">Shows you how your link profile compares to leading competitors. Link quality tends to matter much more than link quantity. <a href="http://www.e-marketing-news.co.uk/Oct04/RichLinking.html">Ranking near the top of the search results is  self reinforcing</a>. When people create various automated spam sites or legitimately reference sites they are far more inclined to reference sites at the top of the results than sites which are nowhere to be found. It can take a year or more to build up a competitive quality link profile in competitive marketplaces.</p>
<p style="font:normal 11px arial; text-align:justify">Please note that Google only shows an exceptionally small sample of the linkage data they know of. Some of the other engines show a much larger sample. Google still counts many of the links they do not show when you do a backlink check on Google, but due to <a href="http://www.seobook.com/archives/000661.shtml">algorithms like TrustRank</a> Google seems harder to manipulate  than Yahoo! or MSN using techniques like bulk low quality link spam.</p></td>
</tr>
</table>


