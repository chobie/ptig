<?php
namespace Chobie\Net\Twitter;

class Stream
{
    protected $stream;

    protected $consumer_key;
    protected $consumer_secret;
    protected $token;
    protected $token_secret;

    public function __construct($consumer_key, $consumer_secret, $token, $token_secret)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->token = $token;
        $this->token_secret = $token_secret;

        $this->connect();
    }

    protected function connect()
    {
        $signature_method = "HMAC-SHA1";

        $method = "GET";
        $url = "https://userstream.twitter.com/1.1/user.json";

        $timestamp = time();
        $nonce = md5(mt_rand());
        $data = null;

        $base = join("&", array(
            $method,
            rawurlencode($url),
            rawurlencode(join("&", array(
                "oauth_consumer_key=" . rawurlencode($this->consumer_key),
                "oauth_nonce=" . $nonce,
                "oauth_signature_method=" . $signature_method,
                "oauth_timestamp=" . $timestamp,
                "oauth_token=" . $this->token,
                "oauth_version=1.0",
            )))
        ));
        $secret = rawurlencode($this->consumer_secret) . '&' .
            rawurlencode($this->token_secret);

        $raw_hash = hash_hmac('sha1', $base, $secret, true);
        $signature = rawurlencode(base64_encode($raw_hash));
        $oauth = 'OAuth oauth_consumer_key="' . $this->consumer_key . '", ' .
            'oauth_nonce="' . $nonce . '", ' .
            'oauth_signature="' . $signature . '", ' .
            'oauth_signature_method="' . $signature_method . '", ' .
            'oauth_timestamp="' . $timestamp . '", ' .
            'oauth_token="' . $this->token . '", ' .
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
        stream_set_timeout($stream, 2);

        $this->stream = $stream;
    }

    public function consume($limit = 100, $fucntion)
    {
        $i = 0;

        // NOTE(chobie): stream wrapperを使っている以上接続断は検知できないはずなので再起動して
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