<?php
require dirname(__DIR__) . "/OAuth.php";
require dirname(__DIR__) . "/twitteroauth.php";

define("CONSUMER_KEY", "uoSgZWThDlCDJA1G5GNZg");
define("CONSUMER_SECRET", "3nrp5n4evnJBOiT0ssPvtz7LZXaw8W5jFtBtBKUwG4");

$dir = sprintf("%s/.ptig", getenv("HOME"));
$path = sprintf("%s/config.yml", $dir);

if (file_exists($path)) {
    echo "$path exists. please remove first";
    return;
}

$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SERVER['argv'][1], $_SERVER['argv'][2]);
$content = $twitter->getAccessToken($_SERVER['argv'][3]);

if (!is_dir($dir)) {
    mkdir($dir);
}

if (file_put_contents($path, sprintf("oauth_token: %s\noauth_token_secret: %s\n",
    $content['oauth_token'],
    $content['oauth_token_secret']))) {
    echo "write config file successfully\n";
}