<?php
//error_reporting(0);
require("includes/application_top.php");
error_reporting(E_ALL);

// Database config & class
$db_config = array(
	"servername" => DB_SERVER,
	"username"	=> DB_SERVER_USERNAME,
	"password"	=> DB_SERVER_PASSWORD,
	"database"	=> DB_DATABASE
);
if(extension_loaded("mysqli")) require_once("includes/classes/class._database_i.php"); 
else require_once("includes/classes/class._database.php"); 

// Tree class
require_once("includes/classes/class.tree.php"); 

$jstree = new json_tree();

#$jstree->_create_default();
#die();

if(isset($_GET["reconstruct"])) {
	$jstree->_reconstruct();
	die();
}
if(isset($_GET["analyze"])) {
	echo $jstree->_analyze();
	die();
}

if($_REQUEST["operation"] && strpos("_", $_REQUEST["operation"]) !== 0 && method_exists($jstree, $_REQUEST["operation"])) {
	header("HTTP/1.0 200 OK");
	header('Content-type: text/json; charset=utf-8');
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Pragma: no-cache");

	echo $jstree->{$_REQUEST["operation"]}($_REQUEST);

	die();
}
header("HTTP/1.0 404 Not Found"); 
?>