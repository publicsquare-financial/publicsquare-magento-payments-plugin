<?php

namespace PublicSquare\Payments\Block;

use PublicSquare\Payments\Helper\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Csp\Helper\CspNonceProvider;

class Payment extends Template
{
    // /**
    //  * @var ConfigProviderInterface
    //  */
    // private $config;
    /**
     * @var CspNonceProvider
     */
    private $cspNonceProvider;

    /**
     * @var Config
     */
    private $configProvider;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ConfigProviderInterface $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        CspNonceProvider $cspNonceProvider,
        Config $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->cspNonceProvider = $cspNonceProvider;
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
        return $this->cspNonceProvider->generateNonce();
    }
}