<?php
namespace Chobie\IO;

class Stream
{
    protected $buffer;
    protected $offset = 0;

    public function __construct($data = null)
    {
        $this->buffer = $data;
    }

    public function append($data)
    {
        $this->buffer .= $data;
    }

    public function isEmpty()
    {
        if ($this->offset >= strlen($this->buffer)){
            return true;
        } else {
            return false;
        }
    }

    public function getLine()
    {
        for ($i = $this->offset; $i < strlen($this->buffer); $i++) {
            if ($this->buffer[$i] == "\r") {
                if ($this->buffer[$i+1] == "\n") {
                    $i++;
                }
                break;
            } else if ($this->buffer[$i] == "\n") {
                $i++;
                break;
            }
        }

        $buffer = substr($this->buffer, $this->offset, $i - $this->offset);
        $this->offset = $i+1;
        // MEMO(chobie): remove non printable codes at this time.
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $buffer));
    }
}