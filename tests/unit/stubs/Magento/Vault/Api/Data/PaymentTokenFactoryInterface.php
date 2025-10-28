<?php

namespace Magento\Vault\Api\Data;

interface PaymentTokenFactoryInterface
{
    const TOKEN_TYPE_CREDIT_CARD = 'credit_card';

    function create(string $type);
}