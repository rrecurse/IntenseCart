<?php

	function GetAdminUser($nocache=0) {
		if(!$nocache) {
			if(!defined('ADMIN_USER')) define('ADMIN_USER',GetAdminUser('nocache'));
			return ADMIN_USER;
		}

		$newsessn='';

		if(isset($_SERVER['REMOTE_USER'])) {

			if(isset($_GET['admin_logout'])) {
				header("HTTP/1.1 401 Logout");
				exit();
			}
		
			return $_SERVER['REMOTE_USER'];

		} else {

			if(isset($_POST['_admin_login']) && isset($_POST['_admin_password'])) {

				if(preg_match('/^\.(.*)/',$_POST['_admin_login'])) {
					$htpasswd = DIR_FS_CORE.'/htaccess/.htpasswd.global';
				} else {
					$htpasswd=DIR_FS_SITE_ADMIN.FILENAME_HTPASSWD;
				}
				
				// # added 3 second sleep() to decrease bot brute force login attempts.
				sleep(3);	
//error_log(print_r($_SERVER['REMOTE_ADDR'],1));
				$fd = fopen($htpasswd,'r');
			
				if (!$fd) return NULL;

				while (1) {

					$line = fgets($fd,1024);
					if($line=='') break;

					if (preg_match('/^([^#\s]+?):(.+)/',$line,$split)) {
						
						if ($split[1]==$_POST['_admin_login']) {

							if ($split[2]==crypt($_POST['_admin_password'],substr($split[2],0,2))) {
								$newsessn = $_POST['_admin_login'];
							}

						    break;
						}
					}
				} // # end while

				fclose($fd);
			
			} else if(isset($_COOKIE['admin_sessid'])) {

				if (isset($_GET['admin_logout'])) {

					// # commented out to stop deleteing admin sessions everytime admin logs-out. 
					// # Purging this field is better left as a maintence routine. 
					// # Security auditing and reporting is now possible with this removed.
			        //tep_db_query("DELETE FROM admin_sessions WHERE admin_sessid='".$_COOKIE['admin_sessid']."' AND admin_addr='".$_SERVER['REMOTE_ADDR']."'");
    
				} else {

					// # added 3 second sleep() to decrease bot brute force login attempts.
					sleep(3);

					$user_query = tep_db_query("SELECT admin_user 
												FROM admin_sessions 
												WHERE admin_sessid = '".$_COOKIE['admin_sessid']."' 
												AND (ignore_addr=1 OR admin_addr='".$_SERVER['REMOTE_ADDR']."') 
												AND access_time >= DATE_SUB(NOW(),INTERVAL expire_minutes MINUTE)
											  ");

					if($user_row=tep_db_fetch_array($user_query)) {

						tep_db_query("UPDATE admin_sessions 
									  SET access_time = NOW() 
									  WHERE admin_sessid = '".$_COOKIE['admin_sessid']."'
									");
						return $user_row['admin_user'];
					}
				}

				setcookie('admin_sessid',NULL,0,'/');
				setcookie('admin_user',NULL,0,'/');
			}
		}

		if($newsessn) {
			
			$sessid='';
			list($seed) = preg_split('/ /',microtime());
			srand(time+$seed*10000000);

			for ($i=1; $i<=4; $i++) {
				$sessid .= sprintf("%04X",rand()&0xFFFF);
			}

			tep_db_query("DELETE FROM admin_sessions 
						  WHERE access_time < DATE_SUB(NOW(),INTERVAL expire_minutes MINUTE)
						");

			$tmout = isset($_POST['_admin_keep_session']) ? 10000 : ADMIN_SESSION_TIMEOUT+0;

			if($tmout < 1) $tmout=90;
			$ign = 0;

			// # check if session exists

			$current_user = tep_db_query("SELECT * FROM admin_sessions WHERE admin_sessid = '".$_COOKIE['admin_sessid']."'");

			if(tep_db_num_rows($current_user) < 1) { 

				tep_db_query("REPLACE INTO admin_sessions (admin_sessid,admin_user,admin_addr,access_time,expire_minutes,ignore_addr) 
							  VALUES ('$sessid','$newsessn','".$_SERVER['REMOTE_ADDR']."',NOW(),'$tmout','$ign')
							");

			}

			setcookie('admin_user',$newsessn,(isset($_POST['_admin_keep_name'])?time()+1461*86400:NULL),'/');
			setcookie('admin_sessid',$sessid,(isset($_POST['_admin_keep_session'])?time()+1461*86400:NULL),'/');

			header("HTTP/1.0 302 Reload");
			header("Location: ".HTTP_SERVER.$_SERVER['REQUEST_URI']);
		
			 exit();
			//return $newsessn;
		}

		return NULL;
	}


	function CheckAdminPermission($user,$grps) {

		if(preg_match('/^\./',$user)) {
			return true;
		}

		if(!is_array($grps)) {
			$grps = array($grps);
		}

		if(in_array('SUPER',$grps)) {
			return false;
		}

		if (in_array('ALL',$grps)) {
			return true;
		}

		$qry = tep_db_query("SELECT admin_group 
							 FROM ".TABLE_ADMIN_PERMISSIONS." 
							 WHERE admin_user = '".$user."'
							");

		while($row=tep_db_fetch_array($qry)) {
			if($row['admin_group']=='ALL' || in_array($row['admin_group'],$grps)) return true;
		}

		return false;
	}

	function GetAdminFilePermissions($file) {

		$file = str_replace(DIR_FS_ADMIN,'',$file);
		$grps = array();

		$qry = tep_db_query("SELECT admin_file,admin_file_group 
							 FROM ".TABLE_ADMIN_FILES." 
							 WHERE admin_file='".$file."'
							");

		while($row=tep_db_fetch_array($qry)) $grps[]=$row['admin_file_group'];
		
		return $grps;
	}


	function GetAdminFiles($user='') {
		if(!$user) $user = GetAdminUser();
		$files = array();

		$qry = tep_db_query("SELECT DISTINCT f.admin_file 
							 FROM ".TABLE_ADMIN_FILES." f,".TABLE_ADMIN_PERMISSIONS." p 
							 WHERE f.admin_file_group = 'ALL' 
							 OR (p.admin_user='$user' AND (f.admin_file_group=p.admin_group OR p.admin_group='ALL'))
							");

		while ($row=tep_db_fetch_array($qry)) $files[$row['admin_file']]=$row['admin_file'];
		return $files;
	}

	$MyAdminPermissions = array();

	function AdminPermission($grp) {
		global $MyAdminPermissions;
		if(!isset($MyAdminPermissions[$grp])) $MyAdminPermissions[$grp]=CheckAdminPermission(GetAdminUser(),$grp);
		return $MyAdminPermissions[$grp];
	}

	$MyAdminFields = NULL;

	function getAdminOption($fld) {
		global $MyAdminFields;

		if(!isset($MyAdminFields)) {

			$myadminQuery = tep_db_query("SELECT * FROM admin_users 
										  WHERE admin_user = '".addslashes(GetAdminUser())."'
										");

			$MyAdminFields = tep_db_fetch_array($myadminQuery);
		}

//error_log(print_r($MyAdminFields[$fld], 1));
	return isset($MyAdminFields[$fld]) ? $MyAdminFields[$fld] : NULL;

	}
?>