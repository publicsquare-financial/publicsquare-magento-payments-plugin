<?php
namespace Magento\AdminNotification\Model\ResourceModel\Inbox;

class CollectionFactory
{
    public function create()
    {
        return new Collection();
    }
}