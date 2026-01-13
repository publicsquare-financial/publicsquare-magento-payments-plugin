<?php
namespace Magento\Framework\Notification;

interface NotifierInterface
{
    public function addNotice($title, $description, $url = '', $isInternal = true);
}