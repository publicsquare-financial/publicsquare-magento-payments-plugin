<?php

namespace Magento\Sales\Api;

interface TransactionRepositoryInterface
{
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}