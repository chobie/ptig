<?php
namespace Chobie\IO;

class OutputStream2 extends OutputStream
{
    protected function __write($buffer)
    {
        echo $buffer;
        return uv_write($this->socket, $buffer);
    }
}