<?php
if ($_GET['url'] != $SERVER_["SERVER_NAME"]) {
		if ($_GET['keyword'] != '') {
			$keywords[0] = $_GET['keyword'];
			$url = $_GET['url'];
			$keyword_input = $_GET['keyword'];
		}
	} elseif ($_POST['url'] != '') {
		if (strpos($_POST['keyword'],"\n")) {
			$keywords = str_replace("\n",",",$_POST['keyword']);
			$keywords = explode(",",$keywords);
		} else {
			$keywords[0] = $_POST['keyword'];
		}
		$keyword_input = $_POST['keyword'];
		$url = $_POST['url'];
	}
	$i = 0;
	if ($keywords[$i] != '') {
		while ($keywords[$i] != '') {
			$keyword_implode = str_replace(' ','+',$keywords[$i]);
			$fetch_url = "http://www.google.com/search?num=50&q=" . $keyword_implode . "&btnG=Search";
			ob_start();
			include_once($fetch_url);
			$page = ob_get_contents();
			ob_end_clean();  
			
			$page = str_replace('<b>','',$page);
			$page = str_replace('</b>','',$page);
			//preg_match('/008000\">(.+)<\/font><nobr>/i', $page, $match);
			preg_match_all('/<font color=#008000>(.*)<\/font>/', $page, $match);
			$r = 0;
			$position = '0';
			while ($match[0][$r] != '') {
				if ($position == '0') {
					if (strpos($match[0][$r],$url)) {
						$position = $r+1;
					} 
				}
				$r++;
			} 
			$google_position = $position;

			// Yahoo check
			$position = '0';

			$pages_to_check = 4;
			$serp = 1;
			$total_yahoo = 0;
			while ($serp <= $pages_to_check && $position < '1') {
				if ($serp != 1) {
					$num = ($serp-1)*10+1;
					$append = "&b=" . $num;
				} else {
					$append = '';
				}
				$fetch_url = "http://search.yahoo.com/search?p=" . $keyword_implode . "&prssweb=Search&ei=UTF-8&fl=0&pstart=1&fr=moz2" . $append;
				ob_start();
				include_once($fetch_url);
				$page = ob_get_contents();
				ob_end_clean();  
				
				$page = str_replace('<b>','',$page);
				$page = str_replace('</b>','',$page);
				preg_match_all('/<em class=yschurl>(.*)<\/em>/', $page, $match);
				$r = 0;
				$position = '0';
				while ($match[0][$r] != '') {
					$total_yahoo++;
					if ($position == '0') {
						if (strpos($match[0][$r],$url)) {
							$position = $total_yahoo;
						} 
					}
					$r++;
				} 
				$serp++;
			}
			$yahoo_position = $position;

			// MS Search check
			$position = '0';
			$pages_to_check = 4;
			$serp = 1;
			$total = 0;
			while ($serp <= $pages_to_check && $position < '1') {
				if ($serp != 1) {
					$num = ($serp-1)*10+1;
					$append = "&first=" . $num;
				} else {
					$append = '';
				}
				$fetch_url = "http://search.msn.com/results.aspx?q=" . $keyword_implode . $append;
				
				ob_start();
				include_once($fetch_url);
				$page = ob_get_contents();
				ob_end_clean();  
				
				$page = str_replace('<strong>','',$page);
				$page = str_replace('</strong>','',$page);
	
				//preg_match_all('/ <\/p><ul><li class="first">(.*)<\/li>/', $page, $match);
				preg_match('/<\/p><ul><li class="first">(.*)<\/li> <li>/', $page, $match);
				$array = explode('<h2>SPONSORED SITES</h2>',$match[0]);
				$array2 = explode('<h3>', $array[0]);
				$r = 0;
				$position = '0';
				while ($array2[$r] != '') {
					//echo strip_tags($array[$r]) . '<br><br><br><br>';
					$total++;
					if ($position == '0') {
						if (strpos($array2[$r],$url)) {
							$position = $total;
						} 
					}
					$r++;
				} 
				$serp++;
			}
			$msn_position = $position;
			$keyword_table .= '
				<tr>
					<td>' . $keywords[$i] . '</td>
					<td>' . $google_position . '</td>
					<td>' . $yahoo_position . '</td>
					<td>' . $msn_position . '</td>
				</tr>';
			$i++;
		}
		$keyword_table = '
			<table class="result-table" cellspacing="1">
				<tr>
					<th>Keyword</th>
					<th>Google</th>
					<th>Yahoo</th>
					<th>MSN</th>
				</tr>' . $keyword_table . '
			</table>';
	}
?>



<html>
<head>
	<title>SEO Rank Checker</title>
	
</head>
<body>
<h1>SEO Rank Checker v.2.1</h1>
<?php echo $keyword_table; ?>
<hr>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<table>
		<tr>
			<td>URL: </td>
			<td><input type="text" name="url" value="<?php echo $url; ?>" size="40" /> <em>ex. mysite.com</em><br />
			</td>
		</tr>
		<tr>
			<td>Keywords: </td>
			<td><em>Enter each keyword on it's own line</em><br />
				<textarea name="keyword" cols="30" rows="10"><?php echo $keyword_input; ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2" class="align-center"><input class="normal-input" type="submit" value="Check Rank &gt;&gt;" /></td>
		</tr>
	</table>

</form>
</body>
</html>