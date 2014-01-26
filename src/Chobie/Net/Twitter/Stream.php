<?php
namespace Chobie\Net\Twitter;

class Stream
{
    protected $stream;

    public function __construct($consumer_key, $consumer_secret, $token, $token_secret)
    {
        $signature_method = "HMAC-SHA1";

        $method = "GET";
        $url = "https://userstream.twitter.com/1.1/user.json";
        $keywords = array("php");

        $timestamp = time();
        $nonce = md5(mt_rand());
        $data = null;

        $base = join("&", array(
            $method,
            rawurlencode($url),
            rawurlencode(join("&", array(
                "oauth_consumer_key=" . rawurlencode($consumer_key),
                "oauth_nonce=" . $nonce,
                "oauth_signature_method=" . $signature_method,
                "oauth_timestamp=" . $timestamp,
                "oauth_token=" . $token,
                "oauth_version=1.0",
            )))
        ));
        $secret = rawurlencode($consumer_secret) . '&' .
            rawurlencode($token_secret);

        $raw_hash = hash_hmac('sha1', $base, $secret, true);
        $signature = rawurlencode(base64_encode($raw_hash));
        $oauth = 'OAuth oauth_consumer_key="' . $consumer_key . '", ' .
            'oauth_nonce="' . $nonce . '", ' .
            'oauth_signature="' . $signature . '", ' .
            'oauth_signature_method="' . $signature_method . '", ' .
            'oauth_timestamp="' . $timestamp . '", ' .
            'oauth_token="' . $token . '", ' .
            'oauth_version="' . "1.0" . '"';

        $ctx = stream_context_create(
            array(
                'http' => array(
                    'method' => $method,
                    'header' =>
                        "Authorization: " . $oauth . "\r\n" .
                        "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $data,
                    'protocol_version' => 1.1
                )
            )
        );

        $stream = fopen($url, 'r', false, $ctx);
        stream_set_blocking($stream, 0);
        $this->stream = $stream;
    }

    public function consume($limit = 100, $fucntion)
    {
        $i = 0;
        while (!feof($this->stream)) {
            $line = fgets($this->stream);
            if ($line) {
                $obj = json_decode($line, true);
                if ($obj) {
                    $fucntion($obj);
                }
            }
            if ($i > $limit) {
                break;
            }
            $i++;
        }
    }
}