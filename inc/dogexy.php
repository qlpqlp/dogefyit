<?php
// we include the initial configurations
include("config.php");

//Much try to sanityse all GET requests
$_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);

// So do not allow any outsider to directly use this proxy
if ($_SERVER["REMOTE_ADDR"] != $config["server_ip"]){ 
    header('Location: '.$config["server_url"].'/?sad=1');
    die();
};

// Such add appropriate headers to allow CORS on proxy
header('Content-Type: text/html');
header('Access-Control-Allow-Origin: *'); // Allow requests from any domain (not recommended for production)

// we build a second URL to use as path for new URLs on the page to be able also to navidate on Dogefyit
$dogefyit_url = explode("/",$_GET['url']);
$dogefyit_such_url = $dogefyit_url[0]."//".$dogefyit_url[2]."/";

// we check if the Shibe UserAgent is a mobile or not and try to emulate
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36';
if ($_GET['m'] == 1){
    $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Mobile/15E148 Safari/604.1';
};

// We try to set the shibe UserAgent amd by default we try to force English (does not work on some websites like Google, you have to force it on server side)
$options = array(
    'http' => array(
        'method' => 'GET',
        'header' => 'User-Agent: ' . $userAgent. '\r\n'.
                    'Accept-Language: en-US,en;q=0.9\r\n',
    )
);
$context = stream_context_create($options);

// We fetch data from the URL using file_get_contents() with the custom context
$content = file_get_contents($_GET['url'], false, $context);

// some patch for amazon specific that does not like how I manipulate using DOM do to javascript
// we fetch if the URL contains the word amazon
$pows = strpos($_GET['url'], "amazon");
if ($pows !== false) {

    // we try to search all <a> tags to get the current HREF and change it to Dogefyit also
    $content = preg_replace_callback('/<a[^>]*\s+href=([\'"])(.*?)\\1[^>]*>/', function($match) use ($config,$dogefyit_such_url) {
    $currentHref = $match[2];

    // we make a new URL with dogefy
    $newHref = $config["server_url"] . "/?paw=".$dogefyit_such_url.urlencode($currentHref);
    return str_replace($currentHref, $newHref, $match[0]);

}, $content);

}else{ // if not amazon we manipulate URLs using DOM to change the URLS to also be Dogefyit

// We create a new DOMDocument
$dom = new DOMDocument();
$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

// We create a new DOMXPath object to query the DOMDocument
$xpath = new DOMXPath($dom);

// We fetch all anchor tags using XPath query
$anchorTags = $xpath->query('//a');

// We loop through each anchor tag and modify the href attribute
foreach ($anchorTags as $anchorTag) {
    // we get the current URL on the page
    $currentHref = $anchorTag->getAttribute('href');

    // some pages add the full URL, so we try to remove the duplicate URL
    $currentHref = str_replace($dogefyit_url[0]."//www.".$dogefyit_url[2]."/", "", urldecode($currentHref));
    $currentHref = str_replace($dogefyit_such_url, "", $currentHref);

    // We change the URL
    $newHref = $config["server_url"] . "/?paw=".$dogefyit_such_url.urlencode($currentHref);
    
    // We set the modified href attribute
    $anchorTag->setAttribute('href', $newHref);
}

// we get the modified HTML content from the DOMDocument
$content = $dom->saveHTML();
};

// We show all modified content using this Proxy aka Sogexy :P
echo $content;
?>