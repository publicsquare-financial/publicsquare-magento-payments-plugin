<?php

namespace Monolog;

class Logger
{
    public array $messages = [];
    function debug(...$args)
    {
        array_push($this->messages, $args);
    }
    function info(...$args)
    {
       array_push($this->messages, $args);
    }
}