<?php

require('includes/application_top.php');

session_start();

$db = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD) or die("Could not connect.");

if(!$db) 

	die("no db");

if(!mysql_select_db(DB_DATABASE,$db))

 	die("No database selected.");

if(isset($_POST['submit']))

   {

     $filename=$_POST['filename'];

     $handle = fopen("/home/clients/" . DB_SERVER_USERNAME . "/httpdocs/layout/$filename", "r");

     while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)

     {

    

       $import="INSERT into subscribers(subscribers_firstname,subscribers_lastname,subscribers_email_address,customers_newsletter) values('$data[0]','$data[1]','$data[2]','$data[3]')";

       mysql_query($import) or die(mysql_error());

     }

     fclose($handle);

     print "Import done";

 

   }

   else

   {

print "<html><head><title>Email address importer</title></head><body>";

	  print "<b>Email address importer</b><br><br>";

      print "<form action='email_import.php' method='post'>";

      print "Type file name to import:<br>";

      print "<input type='text' name='filename' size='20'><br>";

      print "<input type='submit' name='submit' value='submit'></form>";

      print "<br>Please upload your csv file to <b>/layout/</b> before executing.";

/*echo '<!--';
echo DB_SERVER_USERNAME;
echo '<br>';
echo DB_SERVER_PASSWORD;
echo '<br>';
echo DB_DATABASE;
echo '-->'; */

print "</body></html>";

   }
   ?>
