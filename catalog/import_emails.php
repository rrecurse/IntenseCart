<?
  include('includes/application_top.php');
  $u = tep_db_query("SELECT customers_email_address FROM customers WHERE customers_email_address != ''");
  while ($e = tep_db_fetch_array($u)) {
    if (strpos($e['customers_email_address'], 'dynamoeffects') === false) tep_newsletter($e['customers_email_address']);
  }
echo 'Done';
?>
