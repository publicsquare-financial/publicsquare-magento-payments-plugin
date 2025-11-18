<?php

namespace PublicSquare\Payments\Block;

use PublicSquare\Payments\Helper\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Payment extends Template
{
    /**
     * @var Config
     */
    private $configProvider;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Config $configProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getPaymentConfig()
    {
        $config = [];
        $config['code'] = $this->getCode();
        $config['pk'] = $this->configProvider->getPublicAPIKey();
        $config['mock'] = method_exists($this->configProvider, 'isApiMockEnabled')
            ? $this->configProvider->isApiMockEnabled()
            : false;
        return json_encode($config, JSON_UNESCAPED_SLASHES);
    }

    public function getCode()
    {
        return Config::CODE;
    }

    /**
     * Get CSP Nonce
     *
     * @return String
     */
    public function getNonce(): string
    {
        return bin2hex(random_bytes(16));
    }
}