<?php

namespace Magento\Sales\Api\Data;

interface TransactionInterface
{
    function getOrderId(): ?string;

}