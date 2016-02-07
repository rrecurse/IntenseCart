<?php

	require_once(DIR_WS_LANGUAGES . $language . '/' . FILENAME_SITEMAP); 

	require DIR_WS_CLASSES . 'category_tree2.php';

	$catTree = new catTree;


	    
	$info_query = tep_db_query("SELECT information_id,info_title 
								FROM ".TABLE_INFORMATION." 
								WHERE visible = 1 
								AND languages_id = '".$languages_id."' 
								AND info_title NOT LIKE 'inc\_%' ORDER BY v_order
								");
?>

     <table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td width="25%" valign="top" style="padding:5px 0 0 0;">

<?php
	while ($info_row = tep_db_fetch_array($info_query)) {

		echo '<a href="'.tep_href_link(FILENAME_INFORMATION,'info_id='.$info_row['information_id']).'" class="sitemap_infopages">'.$info_row['info_title'].'<br>';
	}

	tep_db_free_result($info_query);
?>
	<br><br>

<?php 

	echo '<a href="'.tep_href_link(FILENAME_SPECIALS) . '" class="sitemap_infopages">' . PAGE_SPECIALS . '</a><br>' . 
		 '<a href="'.tep_href_link(FILENAME_CONTACT_US) . '" class="sitemap_infopages">' . BOX_INFORMATION_CONTACT . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_ADVANCED_SEARCH) . '" class="sitemap_infopages">' . PAGE_ADVANCED_SEARCH . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_TELL_A_FRIEND) . '" class="sitemap_infopages">' . PAGE_TELL_A_FRIEND . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_SHOPPING_CART) . '" class="sitemap_infopages">' . PAGE_SHOPPING_CART . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_CHECKOUT_SHIPPING) . '" class="sitemap_infopages">' . PAGE_CHECKOUT_SHIPPING . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_ACCOUNT) . '" class="sitemap_infopages">' . PAGE_ACCOUNT . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_WISHLIST) . '" class="sitemap_infopages">' . PAGE_WISHLIST . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_ACCOUNT_EDIT) . '" class="sitemap_infopages">' . PAGE_ACCOUNT_EDIT . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_ADDRESS_BOOK) . '" class="sitemap_infopages">' . PAGE_ADDRESS_BOOK . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_ACCOUNT_HISTORY) . '" class="sitemap_infopages">' . PAGE_ACCOUNT_HISTORY . '</a><br>' .
		 '<a href="'.tep_href_link(FILENAME_ACCOUNT_NOTIFICATIONS) . '" class="sitemap_infopages">' . PAGE_ACCOUNT_NOTIFICATIONS . '</a>'; 

?>

		</td>
		<td valign="top">
			<table width="100%" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td> <?php echo $catTree->buildTree(); ?></td>
				 </tr>
			</table>
		</td>
	</tr>
</table>