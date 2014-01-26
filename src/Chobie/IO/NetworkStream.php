<?php
namespace Chobie\IO;

class NetworkStream extends Stream
{
    protected $socket;
    protected $stream;

    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    public function getLine()
    {
        return stream_get_line($this->socket, 8192, "\r\n");
    }
}
