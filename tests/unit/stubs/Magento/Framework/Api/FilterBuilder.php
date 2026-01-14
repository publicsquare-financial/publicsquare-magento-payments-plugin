<?php

namespace Magento\Framework\Api;

interface FilterBuilder
{
    public function setField($field) ;
    public function setValue($field) ;
    public function create();
}