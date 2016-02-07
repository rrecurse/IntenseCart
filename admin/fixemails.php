<?
include('includes/application_top.php');

$email_query = @mysql_query("SELECT customers_id, customers_email FROM customers WHERE LOCATE('(' , customers_email) > 0");
while ($e = @mysql_fetch_assoc($email_query)) {
  echo $e['customers_email'] . '<br>';;
}
?>
