<?php

namespace Magento\Framework\App;

class ObjectManager
{
    public static function getInstance()
    {
        return new self();
    }

    public function get($className)
    {
        return new \stdClass();
    }
}


