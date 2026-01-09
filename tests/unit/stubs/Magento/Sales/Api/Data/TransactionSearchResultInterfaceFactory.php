<?php

namespace Magento\Sales\Api\Data;

interface TransactionSearchResultInterfaceFactory
{
function create(array $data = []): TransactionSearchResultInterface;
}