<?
  if (posix_getuid()==48) {
    foreach ($_SERVER AS $k=>$v) putenv("$k=$v");
    passthru('/usr/sbin/phpsu',$rt);
    exit;
  }
?>
