<?php
namespace Chobie\IO;

class OutputStream extends NetworkStream
{
    protected $replace = '`';

    protected function __write($buffer)
    {
        return fwrite($this->socket, $buffer);
    }

    public function write($message)
    {
        $args = func_get_args();
        array_shift($args);

        $key = "";
        $value = "";
        $tmp = array();
        if (count($args)) {
            if (count($args) % 2 != 0) {
                throw new \InvalidArgumentException("parameter doesn't match");
            }

            for ($i = 0; $i < count($args); $i++) {
                if ($i % 2 == 0) {
                    $key = $args[$i];
                } else {
                    $value = $args[$i];

                    $tmp[$key] = $value;
                    unset($key);
                    unset($value);
                }
            }
            foreach ($tmp as $key => $value) {
                $message = str_replace(sprintf("%s%s%s", $this->replace, $key, $this->replace), $value, $message);
            }
        }

        $this->__write($message);
    }

    public function writeln($message)
    {
        $message = $message . "\r\n";

        $args = func_get_args();
        array_shift($args);
        array_unshift($args, $message);

        return call_user_func_array(array($this, "write"), $args);
    }
}