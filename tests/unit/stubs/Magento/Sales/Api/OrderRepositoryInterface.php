<?php

namespace Magento\Sales\Api;

interface OrderRepositoryInterface
{
    public function save(\Magento\Sales\Api\Data\OrderInterface $entity);
    public function get($id);
}