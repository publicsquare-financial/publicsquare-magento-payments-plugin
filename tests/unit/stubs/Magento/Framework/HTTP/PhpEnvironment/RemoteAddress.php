<?php

namespace Magento\Framework\HTTP\PhpEnvironment;

interface RemoteAddress
{

    public function getRemoteHost();
    public function getRemoteAddress();
}