<?php

namespace Magento\Framework\Controller\Result;


use Magento\Framework\Controller\ResultInterface;

interface Redirect extends ResultInterface
{

    function setPath($path, array $params = []): Redirect;

}