<?
$IXcoreAuthRow=NULL;
function IXcore_check_admin($auth=NULL) {
  global $IXcoreAuthRow;
  if (!$IXcoreAuthRow && isset($_COOKIE['admin_sessid'])) {
    define('DIR_FS_IXCORE_SITE',preg_replace('|/[^/]*/?$|','/',$_SERVER['DOCUMENT_ROOT']));
    require_once(DIR_FS_IXCORE_SITE.'conf/configure.php');
    $lnk=mysql_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD);
    mysql_select_db(DB_DATABASE,$lnk);
    if (!$lnk) return NULL;
    $qry=mysql_query("SELECT p.admin_user FROM admin_sessions s,admin_permissions p WHERE s.admin_sessid='".$_COOKIE['admin_sessid']."' AND (s.ignore_addr=1 OR s.admin_addr='".$_SERVER['REMOTE_ADDR']."') AND s.access_time>=DATE_SUB(NOW(),INTERVAL s.expire_minutes MINUTE) AND s.admin_user=p.admin_user AND p.admin_group IN ('ALL','$auth')",$lnk);
    $IXcoreAuthRow=mysql_fetch_assoc($qry);
    mysql_close($lnk);
  }
  if ($row=$IXcoreAuthRow) return Array('user'=>DB_DATABASE,'admin'=>$row['admin_user'],'domain'=>SITE_DOMAIN,'email'=>'support@'.preg_replace('/^www\./','',SITE_DOMAIN));
  return NULL;
}
?>