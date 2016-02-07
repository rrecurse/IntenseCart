<?php

if (!isset($_GET['tools'])) {
$tools = "seo-tools"; // Default page
} else {
$tools = $_GET['tools'];
}

include ("../../admin/seotools/".$tools.".php");

?> 