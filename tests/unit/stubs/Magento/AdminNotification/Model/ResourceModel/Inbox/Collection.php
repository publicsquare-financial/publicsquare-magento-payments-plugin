<?php
namespace Magento\AdminNotification\Model\ResourceModel\Inbox;

class Collection
{
    public function addFieldToFilter($field, $value)
    {
        return $this;
    }

    public function addRemoveFilter()
    {
        return $this;
    }

    public function getSize()
    {
        return 0;
    }
}