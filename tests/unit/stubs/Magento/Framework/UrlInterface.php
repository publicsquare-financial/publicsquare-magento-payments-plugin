<?php

namespace Magento\Framework;

interface UrlInterface
{
    public function getUrl($route = null, $params = []);
}