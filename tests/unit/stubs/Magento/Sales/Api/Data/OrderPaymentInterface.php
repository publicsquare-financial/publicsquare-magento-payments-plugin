<?php

namespace Magento\Sales\Api\Data;

interface OrderPaymentInterface
{
    function getAdditionalInformation(): array;
    function setAdditionalInformation(array $additionalInformation);
}