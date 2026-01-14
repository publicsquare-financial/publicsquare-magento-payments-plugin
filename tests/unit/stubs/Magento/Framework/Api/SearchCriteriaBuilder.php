<?php

namespace Magento\Framework\Api;

interface SearchCriteriaBuilder
{
    public function addFilters(array $filter);
    public function create();

}