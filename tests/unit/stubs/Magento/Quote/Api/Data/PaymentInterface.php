<?php

namespace Magento\Quote\Api\Data;

interface PaymentInterface
{
    public function getAdditionalInformation(): array;
    public function setAdditionalInformation(array $additionalData);

}