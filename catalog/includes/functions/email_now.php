<?php

	function email_now_expand($email_template,$tpl,$sec='text',$nl="\n") {

		$parsed = array();

		preg_match_all('/(.*?)(\[([\w\.]+)\]|$)/s',$email_template,$parsed);

		$rs='';

		for ($i=0;$parsed[0][$i]!='';$i++) {

			$rs .= $parsed[1][$i];

			if($parsed[2][$i]) {

				$var = $parsed[3][$i];
				$p = $tpl;

				foreach(explode('.',$var) AS $k) {
					if(is_array($p)) $p = $p[$k];
				}
				
				if(is_array($p)) $p = $p[$sec];
				$rs .= isset($p)?$p:$parsed[2][$i];
			}

		}

		return preg_replace('/\r?\n/s',$nl,$rs);
	}


	function email_now($t_key,$tpl,$extra,$lang=NULL) {
		if(SEND_EMAILS != 'true') return false;
		if(!isset($lang) || !$lang) $lang=$language;
		$tpquery = tep_db_query("SELECT * FROM email_now_templates WHERE email_template_key='$t_key' ORDER BY language_id!='$lang',language_id LIMIT 1");

		if($tpinfo=tep_db_fetch_array($tpquery)) {

			$message = new email(array('X-Mailer: IntenseCart'));

			if(EMAIL_USE_HTML=='true' && $tpinfo['send_mode']=='html') {

				$message->add_html(email_now_expand($tpinfo['email_template_html'],$tpl,'html',' '),email_now_expand($tpinfo['email_template_text'],$tpl));

			} else {

				$message->add_text(email_now_expand($tpinfo['email_template_text'],$tpl));
			}


			// # Send message
			$message->build_message();
			$to_name = email_now_expand($tpinfo['to_name'],$tpl,'text',' ');
			$to_email = email_now_expand($tpinfo['to_email'],$tpl,'text',' ');
			$from_name = email_now_expand($tpinfo['from_name'],$tpl,'text',' ');
			$from_email = email_now_expand($tpinfo['from_email'],$tpl,'text',' ');
			$subj = email_now_expand($tpinfo['email_subject'],$tpl,'text',' ');

			if(!empty($to_name)) { 

				$message->send($to_name,$to_email,$from_name,$from_email,$subj);

				if(is_array($extra)) {

					foreach ($extra AS $cc) {

						$ar = array();
	
						if (preg_match('/^\s*(.*?)\s*<\s*(.*?)\s*>/',$cc,$ar)) {
							$cc_name=$ar[1];
							$cc_email=$ar[2];
						} else {
							$cc_name='';
							$cc_email=$cc;
						}

						$message->send($cc_name,$cc_email,$from_name,$from_email,$subj." [Fwd: $to_name <$to_email>]");
					}
		
				}
			}
		
		} else return false;
	}

?>
