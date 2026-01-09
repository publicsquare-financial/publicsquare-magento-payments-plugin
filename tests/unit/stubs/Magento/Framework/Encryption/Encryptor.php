<?php

namespace Magento\Framework\Encryption;

interface Encryptor
{
    public function encrypt($data);

    public function decrypt($data);
}