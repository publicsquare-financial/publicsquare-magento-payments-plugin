<?php

namespace PublicSquare\Payments\Exception;

use PublicSquare\Payments\Exception\PSQException;
use function parent;

class NotConfiguredException extends PSQException
{
    private ?string $configKey;
    private ?string $configLabel;

    public function __construct(
        string $configKey,
        string $configLabel,
        int $propagateHttpResponseCode = 500
    )
    {
       parent::__construct(propagateHttpResponseCode: $propagateHttpResponseCode, message:  'Configuration key [' . $configKey . '] "' . $configLabel . '" is not set!');
       $this->configKey = $configKey;
       $this->configLabel = $configLabel;
    }

    /**
     * @return string|null
     */
    public function getConfigKey(): ?string
    {
        return $this->configKey;
    }

    /**
     * @return string|null
     */
    public function getConfigLabel(): ?string
    {
        return $this->configLabel;
    }

}