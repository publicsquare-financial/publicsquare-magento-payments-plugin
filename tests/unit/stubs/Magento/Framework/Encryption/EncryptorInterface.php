<?php

namespace Magento\Framework\Encryption;


interface EncryptorInterface
{

    function hash(string $val): string;
    function encrypt($val);
    function decrypt($val);
}