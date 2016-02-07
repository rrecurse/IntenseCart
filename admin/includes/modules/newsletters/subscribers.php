<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

// # Relation: newsletter.php


class subscribers {
	var $show_choose_audience, $newsletters_id, $module_subscribers, $title, $subject, $content, $foot;

	function __construct($newsletters_id, $module_subscribers, $title, $fromMail, $subject, $content, $foot) {
      $this->show_choose_audience = false;
      $this->newsletters_id = $newsletters_id;
      $this->module_subscribers = $module_subscribers;
      $this->title = $title;
      $this->fromMail = $fromMail;
      $this->subject = $subject;
      $this->content = $content;
      $this->foot = $foot;
    }

    function choose_audience() {
      return false;
    }

function confirm() {
      global $HTTP_GET_VARS;

      $mail_query = tep_db_query("SELECT COUNT(*) AS count
								  FROM (SELECT DISTINCT c.customers_email_address, 
														c.customers_firstname, 
														c.customers_lastname,
														c.customers_newsletter
								FROM " . TABLE_CUSTOMERS . "  c
								WHERE c.customers_email_address NOT LIKE '%@marketplace.amazon.com'
								AND c.customers_group_id = '0'
								AND c.customers_newsletter = '1'	
								
								UNION ALL

								SELECT DISTINCT s.subscribers_email_address,
												 s.subscribers_firstname,
												 s.subscribers_lastname,
												 s.customers_newsletter
								FROM subscribers s
								LEFT JOIN " . TABLE_CUSTOMERS . " c ON c.customers_email_address = s.subscribers_email_address
		  						WHERE c.customers_email_address IS NULL
		  						AND s.customers_newsletter = '1') AS table1													
								");

      $mail = tep_db_fetch_array($mail_query);

$confirm_string = '
		<table border="0" cellspacing="0" cellpadding="5" width="100%" align="center">
			<tr>
				<td class="main"><font color="#ff0000"><b>'. TEXT_TITRE_INFO . '</b></font></td>
			</tr>
			<tr>
				<td class="main">' . sprintf(TEXT_COUNT_CUSTOMERS, $mail['count']) . '</td>
			</tr>
			<tr>
				<td class="main">' . TEXT_BULLETIN_NUMB . "&nbsp;" . '<font color="#0000ff">' . $this->newsletters_id . '</font></td>
			</tr>
			<tr>
				<td class="main">' . TEXT_MODULE . "&nbsp;" . '<font color="#0000ff">' . $this->module_subscribers . '</font></td>
			</tr>
			<tr>
				<td class="main">' . TEXT_NEWSLETTER_TITLE . "&nbsp;" . '<font color="#0000ff">' . $this->title . '</font></td>
			</tr>
			<tr>
				<td class="main">' . TEXT_NEWSLETTER_FROM . "&nbsp;" . '<font color="#0000ff">' . $this->fromMail . '</font></td>
			</tr>
			<tr>
				<td class="main">' . TEXT_SUBJECT_MAIL . '&nbsp;' . '<font color="#0000ff">' . $this->subject . '</font></td>
			</tr>
			<tr>
				<td class="main"><font color="#ff0000"><b>'.TEXT_TITRE_VIEW . '</b></font></td>
			</tr>
			<tr>
				<td class="main" align="center"><div id="thenews">' . $this->header . $this->content . $this->foot.'</div></td>
			</tr>
			<tr>
				<td align="right" style="padding:5px 15px 0 0"><a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $_GET['page'] . '&nID=' . $_GET['nID'] . '&action=confirm_send') . '">' . tep_image_button('button_send.gif', IMAGE_SEND) . '</a> <a href="' . tep_href_link(FILENAME_NEWSLETTERS, 'page=' . $_GET['page'] . '&nID=' . $_GET['nID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a></td>
			</tr>
		</table>';

		tep_db_query("UPDATE ".TABLE_NEWSLETTERS." SET send_count = '".$mail['count']."' WHERE newsletters_id = '".$this->newsletters_id."'");

      return $confirm_string;
    }


	function send($newsletter_id) {
    
		// # routine for selecting and adding appropriate user select into newsletters_queue table of database.
			
		// # SELECT only retail customers price group who are NOT amazon customers and who ARE subscribed.
		$mail_query = tep_db_query("SELECT * 
									FROM (SELECT DISTINCT c.customers_email_address, 
											c.customers_id,
											c.customers_firstname, 
											c.customers_lastname,
											c.customers_newsletter
											FROM " . TABLE_CUSTOMERS . "  c
											WHERE c.customers_email_address NOT LIKE '%@marketplace.amazon.com'
											AND c.customers_group_id = '0'
											AND c.customers_newsletter = '1'	
									
									UNION ALL

										SELECT DISTINCT s.subscribers_email_address,
												s.customers_id,
												s.subscribers_firstname,
												s.subscribers_lastname,
												s.customers_newsletter
										FROM subscribers s
										LEFT JOIN " . TABLE_CUSTOMERS . " c ON c.customers_email_address = s.subscribers_email_address
		  								WHERE c.customers_email_address IS NULL
		  								AND s.customers_newsletter = '1') AS table1
									");
/*	

		$mail_query = tep_db_query("SELECT customers_id, customers_firstname, customers_lastname, customers_email_address 
								    FROM " . TABLE_CUSTOMERS . "  c
									WHERE c.customers_email_address NOT LIKE '%@marketplace.amazon.com'
									AND c.customers_group_id = '0'
									AND c.customers_newsletter = '1'
								  ");
*/

		$home_domain =  str_replace(array('https://', 'http://', 'www.'),'',SITE_DOMAIN);

		$known_domains = array('gmail.com', 'yahoo.com', 'hotmail.com', 'aol.com', $home_domain);

		while ($mail = tep_db_fetch_array($mail_query)) {

			$ok_insert = false;

			preg_match('/@(.*)/', $mail['customers_email_address'], $domain);

			if(in_array($domain[1], $known_domains)) {
				$ok_insert = true;
			} else {
				if(checkdnsrr($domain[1], "MX")) { 
					$ok_insert = true;
				} else {
					tep_db_query("DELETE FROM subscribers WHERE subscribers_email_address = '".$mail['customers_email_address']."'");
					$ok_insert = false;
				}
			}
	
			if($ok_insert) { 
				
				$known_domains[] = $domain[1];

				// # routine for adding appropriate user select into newsletter_queue table of database.
				tep_db_query("INSERT IGNORE INTO newsletter_queue 
							  SET newsletters_id = '".(int)$newsletter_id."',
							  user_id = '".(!empty($mail['customers_id']) ? $mail['customers_id'] : '0')."',
							  firstname = '".mysql_real_escape_string($mail['customers_firstname'])."',
							  lastname = '".mysql_real_escape_string($mail['customers_lastname'])."',
							  email = '".$mail['customers_email_address']."',
							  updated = NOW(),
							  status = 'pending'
							");
			}

		}

		// # Update the newsletter send count after MX validation.

		$newCount = tep_db_result(tep_db_query("SELECT COUNT(0) FROM newsletter_queue WHERE newsletters_id = '".$newsletter_id."'"),0);
		tep_db_query("UPDATE newsletters SET send_count = '".$newCount."' WHERE newsletters_id = '".$newsletter_id."'");
    }
  }
?>
