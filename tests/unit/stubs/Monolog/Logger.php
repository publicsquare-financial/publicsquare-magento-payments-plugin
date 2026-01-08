<?php

namespace Monolog;

class Logger
{
    public array $messages = [];
    public function __construct()
    {

    }
    function debug(...$args)
    {
        array_push($this->messages, $args);
        print_r($args);
    }
    function info(...$args)
    {
       array_push($this->messages, $args);
       print_r($args);

    }
    function error(...$args) {
        array_push($this->messages, $args);
        print_r($args);
    }
    function withName(string $name) {
        $named = clone $this;
        $named->name = $name;
        return $named;
    }
}