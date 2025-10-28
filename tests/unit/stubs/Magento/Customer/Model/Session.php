<?php

namespace Magento\Customer\Model;


interface Session
{
    function getCustomerId();
    function getCustomer();
}