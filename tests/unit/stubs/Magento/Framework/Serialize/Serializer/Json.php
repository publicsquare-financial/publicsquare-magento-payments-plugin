<?php

namespace Magento\Framework\Serialize\Serializer;

class Json
{
    public function serialize($data)
    {
        return json_encode($data);
    }

    public function unserialize($string)
    {
        return json_decode($string, true);
    }
}


