<?php
require dirname(__DIR__) . "/OAuth.php";
require dirname(__DIR__) . "/twitteroauth.php";

define("CONSUMER_KEY", "uoSgZWThDlCDJA1G5GNZg");
define("CONSUMER_SECRET", "3nrp5n4evnJBOiT0ssPvtz7LZXaw8W5jFtBtBKUwG4");

$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
$request_token = $twitter->getRequestToken("");

$token = $request_token['oauth_token'];
$secret = $request_token['oauth_token_secret'];

echo "please authenticate with this link\n";
echo $twitter->getAuthorizeURL($token);
echo "\n";
echo "\n";
echo "php authenticate.php {$token} {$secret} <PIN>";
