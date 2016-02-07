<?php
str_replace("/peacetoyscom-20/", "/proxy/proxy.php?proxy_url=astore.amazon.com/peacetoyscom-20/", $body);
//         
//         USAGE: "proxy_url" required parameter. For example:
//                http://www.mydomain.com/proxy.php?proxy_url=http://www.yahoo.com
//

// proxy.php requires Troy's class_http. http://www.troywolf.com/articles
// Alter the path according to your environment.
require_once("class_http.php"); 

$proxy_url = isset($_GET['proxy_url'])?$_GET['proxy_url']:false;
if (!$proxy_url) {
    header("HTTP/1.0 400 Bad Request");
    echo "proxy.php failed because proxy_url parameter is missing";
    exit();
}

// Instantiate the http object used to make the web requests.
// More info about this object at www.troywolf.com/articles
if (!$h = new http()) {
    header("HTTP/1.0 501 Script Error");
    echo "proxy.php failed trying to initialize the http object";
    exit();
}

$h->url = $proxy_url;
$h->postvars = $_POST;
if (!$h->fetch($h->url)) {
    header("HTTP/1.0 501 Script Error");
    echo "proxy.php had an error attempting to query the url";
    exit();
}

// Forward the headers to the client.
$ary_headers = split("\n", $h->header);
foreach($ary_headers as $hdr) { header($hdr); }


// Send the response body to the client.

echo $h->body;

?>


