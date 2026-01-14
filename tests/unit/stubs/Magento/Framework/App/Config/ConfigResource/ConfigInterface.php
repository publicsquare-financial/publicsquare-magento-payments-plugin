<?php

namespace Magento\Framework\App\Config\ConfigResource;

interface ConfigInterface
{
    public function saveConfig($path, $value, $scope = 'default', $scopeId = 0);
}