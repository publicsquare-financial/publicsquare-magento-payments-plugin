<?php

namespace Magento\Sales\Api\Data;

interface TransactionSearchResultInterface
{
        /**
     * Gets collection items.
     *
     * @return \Magento\Sales\Api\Data\TransactionInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Set collection items.
     *
     * @param \Magento\Sales\Api\Data\TransactionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

}