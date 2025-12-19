<?php

namespace Magento\Vault\Api;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\PaymentToken;

interface PaymentTokenRepositoryInterface
{
    function save(PaymentTokenInterface $paymentToken);
}